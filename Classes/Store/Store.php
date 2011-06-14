<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Store;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 2 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/copyleft/gpl.html.                      *
 *                                                                        */
/**
 * Enter descriptions here
 *
 * @scope prototype
 * @entity
 * @api
 */

define('STORE_RESULTFORMAT', 'result_format');
define('STORE_RESULTFORMAT_PLAIN', 'plain');
define('STORE_RESULTFORMAT_XML', 'xml');
define('STORE_RESULTFORMAT_EXTENDED', 'extended');
define('STORE_USE_AC', 'use_ac');
define('STORE_USE_OWL_IMPORTS', 'use_owl_imports');
define('STORE_USE_ADDITIONAL_IMPORTS', 'use_additional_imports');
define('STORE_TIMEOUT', 'timeout');
class Store implements \Erfurt\Singleton {

	const COUNT_NOT_SUPPORTED = -1;

	/**
	 * Literal type.
	 * @var int
	 */
	const TYPE_LITERAL = 1;

	/**
	 * IRI type.
	 * @var int
	 */
	const TYPE_IRI = 2;

	/**
	 * Balanknode type.
	 * @var int
	 */
	const TYPE_BLANKNODE = 3;

	/**
	 * A proeprty for hiding resources.
	 * @var string
	 */
	const HIDDEN_PROPERTY = 'http://ns.ontowiki.net/SysOnt/hidden';

	/**
	 * The maximum number of iterations for recursive operatiosn.
	 * @var int
	 */
	const MAX_ITERATIONS = 100;

	/**
	 * RDF-S graph identifier.
	 * @var int
	 */
	const GRAPH_TYPE_RDFS = 501;

	/**
	 * OWL graph ientifier.
	 * @var int
	 */
	const GRAPH_TYPE_OWL = 502;

	/**
	 * An RDF/PHP array containing additional configuration options for graphs
	 * in the triple store. This information is stored in the local system
	 * ontology.
	 * @var array
	 *
	 */
	protected $graphConfigurations;

	/**
	 * An Array holding the Namespace prefixes (An array of namespace IRIs (keys) and prefixes) for some graphs
	 * @var array
	 */
	protected $prefixes;

	/**
	 * Special zend logger, which protocolls all queries
	 * Call with function to initialize
	 * @var \Zend_Logger
	 */
	protected $queryLogger;

	/**
	 * Special zend logger, which protocolls erfurt messages
	 * Call with function to initialize
	 * @var \Zend_Logger
	 */
	protected $erfurtLogger;

	/**
	 * The backend adapter instance in use.
	 * @var \Erfurt\Store\Adapter\AdapterInterface|\Erfurt\Store\Sql\Sqlnterface
	 */
	protected $backendAdapter;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Optional methods a backend adapter can implement
	 * @var array
	 */
	protected $optionalMethods = array(
		'countWhereMatches'
	);

	/**
	 * Contains signalSlotDispatcher
	 *
	 * @var \Erfurt\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * Contains storeSettings
	 *
	 * @var array
	 */
	protected $storeSettings;

	/**
	 * Contains systemOntologySettings
	 *
	 * @var array
	 */
	protected $systemOntologySettings;

	/**
	 * Contains versioning
	 *
	 * @var \Erfurt\Versioning\Versioning
	 */
	protected $versioning;

	/**
	 * Number of queries committed
	 * @var int
	 */
	private static $queryCount = 0;

	// TODO elaborate relevance
	private $importsClosure = array();

	/**
	 * Injector method for a \Erfurt\SignalSlot\Dispatcher
	 *
	 * @var \Erfurt\SignalSlot\Dispatcher
	 */
	public function injectSignalSlotDispatcher(\Erfurt\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * Injector method for the store settings
	 *
	 * @var array
	 */
	public function injectSettings(array $settings) {
		$erfurtSettings = $settings['Erfurt'];
		$this->storeSettings = $erfurtSettings['store'];
		$this->systemOntologySettings = $erfurtSettings['systemOntology'];
	}

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injector method for a \Erfurt\Versioning
	 *
	 * @var \Erfurt\Versioning\Versioning
	 */
	public function injectVersioning(\Erfurt\Versioning\Versioning $versioning) {
		$this->versioning = $versioning;
	}

	/**
	 * Lifecycle method after all dependencies were injected
	 *
	 * @throws Exception\BackendMustBeSetException
	 * @return void
	 */
	public function initializeObject() {
		// Backend must be set, else throw an exception.
		if (!isset($this->storeSettings['backend'])) {
			throw new Exception\BackendMustBeSetException('Backend must be set in configuration.', 1302769905);
		}
		// fetch backend specific options from config.
		$backendOptions = (isset($this->storeSettings[$this->storeSettings['backend']])) ? $this->storeSettings[$this->storeSettings['backend']] : array();
		$this->initializeBackend($this->storeSettings['backend'], $backendOptions);
	}

	/**
	 * Initializes the backend
	 *
	 * @param string $backend virtuoso, mysqli, adodb, redland
	 * @param array $backendOptions
	 *
	 * @throws \Erfurt\Store\Exception\StoreException if store is not supported or store does not implement the store
	 * adapter interface.
	 */
	public function initializeBackend($backend, array $backendOptions = array()) {
		// instantiate backend adapter
		$this->backendAdapter = $this->objectManager->create($backend, $backendOptions);
		if (!$this->backendAdapter instanceof Adapter\AdapterInterface) {
			throw new Exception\StoreException('Adapter class must implement Adapter\AdapterInterface.', 1307352883);
		}
	}

	/**
	 * Sets the backend adapter
	 *
	 * @param Adapter\AdapterInterface $adapter
	 */
	public function setBackendAdapter(Adapter\AdapterInterface $adapter) {
		$this->backendAdapter = $adapter;
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Adds statements in an array to the graph specified by $graphIri.
	 *
	 * @param string $graphIri
	 * @param array  $statementsArray
	 *
	 * @throws \Erfurt\Exception
	 */
	public function addMultipleStatements($graphIri, array $statementsArray, $useAc = true) {
		// TODO inject logger
		//		if (defined('_EFDEBUG')) {
		//			$logger = $this->knowledgeBase->getLog();
		//			$logger->info('Store: adding multiple statements: ' . print_r($statementsArray, true));
		//		}
		// check whether graph is available
		if (!$this->isGraphAvailable($graphIri, $useAc)) {
			throw new Exception\StoreException('Graph is not available.', 1307352939);
		}
		// check whether graph is editable
		if (!$this->checkAuthorization($graphIri, 'edit', $useAc)) {
			throw new Exception\StoreException('No permissions to edit graph.', 1307352946);
		}
		$this->backendAdapter->addMultipleStatements($graphIri, $statementsArray);
		//invalidate deprecated Cache Objects
		//TODO reenable cache
		//		$queryCache = $this->knowledgeBase->getQueryCache();
		//		$queryCache->invalidateWithStatements($graphIri, $statementsArray);
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			'onAddMultipleStatements',
			array(
				 'graphIri' => $graphIri,
				 'statements' => $statementsArray,
			));
		$this->graphConfigurations = null;
	}

	/**
	 * Adds a statement to the graph specified by $graphIri.
	 * @param string $graphIri
	 * @param string $subject (IRI or blank node)
	 * @param string $predicate (IRI, no blank node!)
	 * @param string $object (IRI, blank node or literal)
	 * @param array $options An array containing two keys 'subject_type' and 'object_type'. The value of each is
	 * one of the defined constants of \Erfurt\Store\Store: TYPE_IRI, TYPE_BLANKNODE and TYPE_LITERAL. In addtion to this
	 * two keys the options array can contain two keys 'literal_language' and 'literal_datatype', but only in case
	 * the object of the statement is a literal.
	 *
	 * @throws \Erfurt\Exception Throws an exception if adding of statements fails.
	 */
	public function addStatement($graphIri, $subject, $predicate, $object, $needsAuthorization = true) {
		// check whether graph is available
		if ($needsAuthorization && !$this->isGraphAvailable($graphIri)) {
			throw new Exception\StoreException('Graph is not available.', 1307353201);
		}
		// check whether graph is editable
		if ($needsAuthorization && !$this->checkAuthorization($graphIri, 'edit')) {
			throw new Exception\StoreException('No permissions to edit graph.', 1307353204);
		}
		$this->backendAdapter->addStatement($graphIri, $subject, $predicate, $object);
		//invalidate deprecateded Cache Objects
		//TODO reenable cache
		//		$queryCache = $this->knowledgeBase->getQueryCache();
		//		$queryCache->invalidate($graphIri, $subject, $predicate, $object);
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			'onAddStatement',
			array(
				 'graphIri' => $graphIri,
				 'statement' => array(
					 'subject' => $subject,
					 'predicate' => $predicate,
					 'object' => $object
				 )
			));
		$this->graphConfigurations = null;
	}

	/**
	 * Checks whether the store has been set up yet and imports system
	 * ontologies if necessary.
	 */
	public function checkSetup() {
		$returnValue = true;
		// check for system configuration graph
		// We need to import this first, for the schema graph has namespaces definitions, which will be stored in the
		// local config!
		if (!$this->isGraphAvailable($this->systemOntologySettings['graphIri'], false)) {
			//			$logger->info('System configuration graph not found. Loading graph ...');
			$this->versioning->enableVersioning(false);
			$this->getNewGraph($this->systemOntologySettings['graphIri'], '', 'owl', false);
			try {
				if (is_readable($this->systemOntologySettings['graphPath'])) {
					// load SysOnt Graph from file
					$this->importRdf(
						$this->systemOntologySettings['graphIri'],
						$this->systemOntologySettings['graphPath'],
						'rdfxml',
						\Erfurt\Syntax\RdfParser::LOCATOR_FILE,
						false
					);
				} else {
					// load SysOnt Graph from Web
					$this->importRdf(
						$this->systemOntologySettings['graphIri'],
						$this->systemOntologySettings['graphLocation'],
						'rdfxml',
						\Erfurt\Syntax\RdfParser::LOCATOR_URL,
						false
					);
				}
			}
			catch (\Erfurt\Exception $e) {
				// clear query cache completly
				// TODO reenable cache
				//$queryCache = $this->knowledgeBase->getQueryCache();
				//$queryCache->cleanUpCache(array('mode' => 'uninstall'));
				// Delete the graph, for the import failed.
				$this->backendAdapter->deleteGraph($this->systemOntologySettings['graphIri']);
				throw new Exception\StoreException('Import of \'' . $this->systemOntologySettings['graphIri'] . '\' failed -> ' . $e->getMessage());
			}
			if (!$this->isGraphAvailable($this->systemOntologySettings['graphIri'], false)) {
				throw new Exception\StoreException('Unable to load System Ontology graph.');
			}
			$this->versioning->enableVersioning(true);
			//			$logger->info('System graph successfully loaded.');
			$returnValue = false;
		}
		// check for system ontology
		if (!$this->isGraphAvailable($this->systemOntologySettings['schemaIri'], false)) {
			//			$logger->info('System schema graph not found. Loading graph ...');
			$this->versioning->enableVersioning(false);
			$this->getNewGraph($this->systemOntologySettings['schemaIri'], '', 'owl', false);
			try {
				if (is_readable($this->systemOntologySettings['schemaPath'])) {
					// load SysOnt from file
					$this->importRdf(
						$this->systemOntologySettings['schemaIri'],
						$this->systemOntologySettings['schemaPath'],
						'rdfxml',
						\Erfurt\Syntax\RdfParser::LOCATOR_FILE,
						false
					);
				} else {
					// load SysOnt from Web
					$this->importRdf(
						$this->systemOntologySettings['schemaIri'],
						$this->systemOntologySettings['schemaLocation'],
						'rdfxml',
						\Erfurt\Syntax\RdfParser::LOCATOR_URL,
						false
					);
				}
			}
			catch (\Erfurt\Exception $e) {
				// clear query cache completly
				// TODO reenable cache
				//$queryCache = $this->knowledgeBase->getQueryCache();
				//$queryCache->cleanUpCache(array('mode' => 'uninstall'));
				// Delete the graph, for the import failed.
				$this->backendAdapter->deleteGraph($this->systemOntologySettings['schemaIri']);
				throw new Exception\StoreException('Import of \'' . $this->systemOntologySettings['schemaIri'] . '\' failed -> ' . $e->getMessage());
			}
			if (!$this->isGraphAvailable($this->systemOntologySettings['schemaIri'], false)) {
				throw new Exception\StoreException('Unable to load System Ontology schema.');
			}
			$this->versioning->enableVersioning(true);
			//			$logger->info('System schema successfully loaded.');
			$returnValue = false;
		}
		if ($returnValue === false) {
			throw new Exception\StoreException('One or more system graphs imported.', 20);
		}
		return true;
	}


	/**
	 * Creates the table specified by $tableSpec according to backend-specific
	 * create table statement.
	 *
	 * @param array $tableSpec An associative array of SQL column names and columnd specs.
	 */
	public function createTable($tableName, array $columns) {
		if ($this->backendAdapter instanceof \Erfurt\Store\Sql\Sqlnterface) {
			return $this->backendAdapter->createTable($tableName, $columns);
		}
		// TODO: use default SQL store
	}

	/**
	 * Deletes all statements that match the triple pattern specified.
	 *
	 * @param string $graphIri
	 * @param mixed triple pattern $subject (string or null)
	 * @param mixed triple pattern $predicate (string or null)
	 * @param mixed triple pattern $object (string or null)
	 * @param array $options An array containing two keys 'subject_type' and 'object_type'. The value of each is
	 * one of the defined constants of \Erfurt\Store\Store: TYPE_IRI, TYPE_BLANKNODE and TYPE_LITERAL. In addtion to this
	 * two keys the options array can contain two keys 'literal_language' and 'literal_datatype'.
	 *
	 * @throws \Erfurt\Exception
	 */
	public function deleteMatchingStatements($graphIri, $subject, $predicate, $object, $options = array()) {
		if (!isset($options['use_ac'])) {
			$options['use_ac'] = true;
		}
		if ($this->checkAuthorization($graphIri, 'edit', $options['use_ac'])) {
			try {
				$ret = $this->backendAdapter->deleteMatchingStatements(
					$graphIri,
					$subject,
					$predicate,
					$object,
					$options
				);
				// TODO: renable query cache
				//$queryCache = $this->knowledgeBase->getQueryCache();
				//$queryCache->invalidate($graphIri, $subject, $predicate, $object);
				// just trigger if really data operations were performed
				if ((int)$ret > 0) {
					$this->signalSlotDispatcher->dispatch(
						__CLASS__,
						'onDeleteMatchingStatements',
						array(
							 'graphIri' => $graphIri,
							 'resource' => $subject
						));
				}
				return $ret;
			}
			catch (\Erfurt\Store\Adapter\Exception $e) {
				// TODO: Create a exception for too many matching values
				// In this case we log without storing the payload. No rollback supported for such actions.
				$this->signalSlotDispatcher->dispatch(
					__CLASS__,
					'onDeleteMatchingStatements',
					array(
						 'graphIri' => $graphIri,
						 'resource' => $subject
					)
				);
			}
		}
	}

	/**
	 * Deletes statements in an array from the graph specified by $graphIri.
	 *
	 * @param string $graphIri
	 * @param array  $statementsArray
	 *
	 * @throws \Erfurt\Exception
	 */
	public function deleteMultipleStatements($graphIri, array $statementsArray) {
		// check whether graph is available
		if (!$this->isGraphAvailable($graphIri)) {
			throw new Exception\StoreException('Graph is not available.');
		}
		// check whether graph is editable
		if (!$this->checkAuthorization($graphIri, 'edit')) {
			throw new Exception\StoreException('No permissions to edit graph.');
		}
		$this->backendAdapter->deleteMultipleStatements($graphIri, $statementsArray);
		// TODO reenable query cache
		//		$queryCache = $this->knowledgeBase->getQueryCache();
		//		$queryCache->invalidateWithStatements($graphIri, $statementsArray);
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			'onDeleteMultipleStatements',
			array(
				 'graphIri' => $graphIri,
				 'statements' => $statementsArray
			)
		);
	}

	/**
	 * @param string $graphIri The Iri, which identifies the graph.
	 * @param boolean $needsAuthorization Whether to use access control or not.
	 *
	 * @throws \Erfurt\Exception Throws an exception if no permission, graph not existing or deletion fails.
	 */
	public function deleteGraph($graphIri, $needsAuthorization = true) {
		// check whether graph is available
		if (!$this->isGraphAvailable($graphIri, $needsAuthorization)) {
			throw new Exception\StoreException("Graph <$graphIri> is not available and therefore not removable.");
		}
		// check whether graph editing is allowed
		if (!$this->checkAuthorization($graphIri, 'edit', $needsAuthorization)) {
			throw new Exception\StoreException("No permissions to delete graph <$graphIri>.");
		}
		// delete graph
		$this->backendAdapter->deleteGraph($graphIri);
		// and history
		$this->versioning->deleteHistoryForGraph($graphIri);
		// TODO reenable query cache
		//		$queryCache = $this->knowledgeBase->getQueryCache();
		//		$queryCache->invalidateWithGraphIri($graphIri);
		// remove any statements about deleted graph from SysOnt
		if ($this->knowledgeBase->getAcGraph() !== false) {
			$acGraphIri = $this->knowledgeBase->getAcGraph()->getGraphIri();
			// Only do that, if the deleted graph was not one of the sys graphs
			if (($graphIri !== $this->systemOntologySettings('graphIri')) && ($graphIri !== $this->systemOntologySettings('schemaIri'))) {
				$this->backendAdapter->deleteMatchingStatements(
					$acGraphIri,
					null,
					null,
					array('value' => $graphIri, 'type' => 'iri')
				);
				$this->backendAdapter->deleteMatchingStatements(
					$acGraphIri,
					$graphIri,
					null,
					null
				);
				// invalidate for the sysgraph too
				//				$queryCache->invalidateWithGraphIri($acGraphIri);
			}
		}
	}

	/**
	 *
	 * @param string $graphIri
	 * @param string $serializationType One of:
	 *										  - 'xml'
	 *										  - 'n3' or 'nt'
	 * @param mixed $filename Either a string containing a absolute filename or null. In case null is given,
	 * this method returns a string containing the serialization.
	 *
	 * @return string/null
	 */
	public function exportRdf($graphIri, $serializationType = 'xml', $filename = null) {
		$serializationType = strtolower($serializationType);
		// check whether graph is available
		if (!$this->isGraphAvailable($graphIri)) {
			throw new Exception\StoreException("Graph <$graphIri> cannot be exported. Graph is not available.");
		}
		if (in_array($serializationType, $this->backendAdapter->getSupportedExportFormats())) {
			return $this->backendAdapter->exportRdf($graphIri, $serializationType, $filename);
		} else {
			$serializer = \Erfurt\Syntax\RdfSerializer::rdfSerializerWithFormat($serializationType);
			return $serializer->serializeGraphToString($graphIri);
		}
	}

	/**
	 * Searches resources that have literal property values matching $stringSpec.
	 *
	 * @param string $stringSpec The string pattern to be matched
	 * @param string|array $graphIris One or more graph IRIs to be searched
	 * @param array option array
	 */
	public function getSearchPattern($stringSpec, $graphIris, $options = array()) {
		// TODO stringSpec should be more than simple string (parse for and/or/xor etc...)
		$stringSpec = (string)$stringSpec;
		$options = array_merge(array(
									'case_sensitive' => false,
									'filter_classes' => false,
									'filter_properties' => false,
									'with_imports' => true
							   ), $options);
		// execute backend-specific search if available
		if (method_exists($this->backendAdapter, 'getSearchPattern')) {
			return $this->backendAdapter->getSearchPattern($stringSpec, $graphIris, $options);
		}
			// else execute Sparql Regex Fallback
		else {
			$ret = array();
			$s_var = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'resourceIri');
			$p_var = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'p');
			$o_var = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'o');
			$default_tpattern = $this->objectManager->create('Erfurt\Sparql\Query2\Triple', $s_var, $p_var, $o_var);
			$ret[] = $default_tpattern;
			$filter = $this->objectManager->create('Erfurt\Sparql\Query2\Filter',
				$this->objectManager->create('Erfurt\Sparql\Query2\ConditionalOrExpression',
					array(
						 /*new \Erfurt\Sparql\Query2\Regex(
						   $s_var,
						   new \Erfurt\Sparql\Query2\RDFLiteral($stringSpec),
						   $options['case_sensitive'] ? null : new \Erfurt\Sparql\Query2\RDFLiteral('i')
						),*/
						 $this->objectManager->create('Erfurt\Sparql\Query2\Regex',
							 $o_var,
							 $this->objectManager->create('Erfurt\Sparql\Query2\RDFLiteral', $stringSpec),
							 $options['case_sensitive'] ? null : $this->objectManager->create('Erfurt\Sparql\Query2\RDFLiteral', 'i')
						 )
					)
				)
			);
			if ($options['filter_properties']) {
				$ss_var = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'ss');
				$oo_var = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'oo');
				$filterprop_tpattern = $this->objectManager->create('Erfurt\Sparql\Query2\Triple', $ss_var, $s_var, $oo_var);
				$ret[] = $filterprop_tpattern;
				/*
								$filter->getConstraint()->addElement(
									new \Erfurt\Sparql\Query2\Regex(
											$oo_var,
											new \Erfurt\Sparql\Query2\RDFLiteral($stringSpec),
											$options['case_sensitive'] ? null : new \Erfurt\Sparql\Query2\RDFLiteral('i')
										)
								);*/
			}
			$ret[] = $filter;
			return $ret;
		}
	}

	/**
	 * @param boolean $withHidden Whether to return IRIs of hidden graphs, too.
	 * @return array Returns an associative array, where the key is the IRI of a graph and the value
	 * is true.
	 */
	public function getAvailableGraphs($withHidden = false) {
		// backend adapter returns all graphs
		$graphs = $this->backendAdapter->getAvailableGraphs();
		// filter for access control and hidden graphs
		foreach ($graphs as $graphIri => $true) {
			if (!$this->checkAuthorization($graphIri)) {
				unset($graphs[$graphIri]);
			}
			if ($withHidden === false) {
				$graphConfig = $this->getGraphConfiguration($graphIri);
				$hiddenProperty = $this->systemOntologySettings['properties']['hidden'];
				if (isset($graphConfig[$hiddenProperty])) {
					$hidden = current($graphConfig[$hiddenProperty]);
					if ((boolean)$hidden['value']) {
						unset($graphs[$graphIri]);
					}
				}
			}
		}
		return $graphs;
	}

	public function getImportsClosure($graphIri, $withHiddenImports = true, $useAC = true) {
		if (array_key_exists($graphIri, $this->importsClosure)) {
			return $this->importsClosure[$graphIri];
		}
		if ($this->backendName == "Virtuoso") {
			$objectCache = $this->knowledgeBase->getCache();
			$importsClosure = null;
			$importsClosureKey = "ImportsClosure_" . (md5($graphIri));
			$importsClosure = $objectCache->load($importsClosureKey);
			if (is_array($importsClosure)) {
				//nothing ToDo
			} else {
				$queryCache = $this->knowledgeBase->getQueryCache();
				$queryCache->startTransaction($importsClosureKey);
				$importsClosure = $this->_getImportsClosure($graphIri, $withHiddenImports, $useAC);
				$queryCache->endTransaction($importsClosureKey);
				$objectCache->save($importsClosure, $importsClosureKey);
			}
		} else {
			$importsClosure = $this->_getImportsClosure($graphIri, $withHiddenImports, $useAC);
		}
		$this->importsClosure[$graphIri] = $importsClosure;
		return $importsClosure;
	}


	/**
	 * Recursively gets owl:imported graph IRIs starting with $graphIri as root.
	 *
	 * @param string $graphIri
	 */
	private function _getImportsClosure($graphIri, $withHiddenImports = true, $needsAuthorization = true) {
		$currentLevel = $this->backendAdapter->getImportsClosure($graphIri);
		if ($currentLevel == array($graphIri)) {
			return $currentLevel;
		}
		if ($withHiddenImports === true) {
			$importsIri = $this->systemOntologySettings['properties']['hiddenImports'];
			$graphConfig = $this->getGraphConfiguration($graphIri);
			if (isset($graphConfig[$importsIri])) {
				foreach ($graphConfig[$importsIri] as $valueArray) {
					$currentLevel[$valueArray['value']] = $valueArray['value'];
				}
			}
			foreach ($currentLevel as $graphIri) {
				$graphConfig = $this->getGraphConfiguration($graphIri);
				if (isset($graphConfig[$importsIri])) {
					foreach ($graphConfig[$importsIri] as $valueArray) {
						$currentLevel = array_merge(
							$currentLevel,
							$this->getImportsClosure($valueArray['value'], $withHiddenImports)
						);
					}
				}
			}
		}
		return array_unique($currentLevel);
	}

	/**
	 * @param string $graphIri The IRI, which identifies the graph.
	 * @param boolean $useAc Whether to use access control or not.
	 * @throws \Erfurt\Store\Exception\StoreException if the requested graph is not available.
	 * @return \Erfurt\Domain\Model\Rdf\Graph Returns an instance of \Erfurt\Domain\Model\Rdf\Graph or one of its subclasses.
	 */
	public function getGraph($graphIri, $useAc = true) {
		// check whether graph exists and is visible
		if (!$this->isGraphAvailable($graphIri, $useAc)) {
			$systemGraphIri = $this->getOption('graphIri');
			$systemSchemaIri = $this->getOption('schemaIri');
			// check whether requested graph is one of the schema graphs
			if (!$useAc && (($graphIri === $systemGraphIri) || ($graphIri === $systemSchemaIri))) {
				try {
					$this->checkSetup();
				}
				catch (Exception\StoreException $e) {
					if ($e->getCode() === 20) {
						// Everything is fine, system graphs now imported
					} else {
						throw new Exception\StoreException('Check setup failed: ' . $e->getMessage());
					}
				}
				// still not available?
				if (!$this->isGraphAvailable($graphIri, $useAc)) {
					throw new Exception\StoreException("Graph '$graphIri' is not available.");
				}
			} else {
				throw new Exception\StoreException("Graph '$graphIri' is not available.");
			}
		}
		// if backend adapter provides its own implementation
		if (method_exists($this->backendAdapter, 'getGraph')) {
			// … use it
			$graphInstance = $this->backendAdapter->getGraph($graphIri);
		} else {
			// use generic implementation
			$owlQuery = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
			$owlQuery->setProloguePart('ASK')
					->addFrom($graphIri)
					->setWherePart('{<' . $graphIri . '> <' . \Erfurt\Vocabulary\Rdf::NS . 'type> <' . \Erfurt\Vocabulary\Owl::ONTOLOGY . '>.}');
			// TODO: cache this
			if ($this->sparqlAsk($owlQuery, $useAc)) {
				// instantiate OWL graph
				$graphInstance = $this->objectManager->create('Erfurt\Domain\Model\Owl\Graph', $graphIri);
			} else {
				// instantiate RDF-S graph
				$graphInstance = $this->objectManager->create('Erfurt\Domain\Model\Rdfs\Graph', $graphIri);
			}
		}
		// check for edit possibility
		if ($this->checkAuthorization($graphIri, 'edit', $useAc)) {
			$graphInstance->setEditable(true);
		} else {
			$graphInstance->setEditable(false);
		}
		return $graphInstance;
	}

	/**
	 * Returns the number fo queries committed.
	 *
	 * @return int
	 */
	public function getQueryCount() {
		return self::$queryCount;
	}

	/**
	 * Creates a new empty graph instance with IRI $graphIri.
	 *
	 * @param string $graphIri
	 * @param string $baseIri
	 * @param string $type
	 * @param boolean $useAc
	 *
	 * @throws \Erfurt\Store\Exception\StoreException
	 *
	 * @return \Erfurt\Domain\Model\Rdf\Graph
	 */
	public function getNewGraph($graphIri, $baseIri = '', $type = self::GRAPH_TYPE_OWL, $useAc = true) {
		// check graph availablity
		if ($this->isGraphAvailable($graphIri, false)) {
			// if debug mode is enabled create a more detailed exception description. If debug mode is disabled the
			// user should not know why this fails.
			$message = defined('_EFDEBUG')
					? 'Failed creating the graph. Reason: A graph with the same IRI already exists.'
					: 'Failed creating the graph.';
			throw new Exception\StoreException($message);
		}
		// check action access
		// TODO reenable AC
//		if ($useAc && !$this->knowledgeBase->isActionAllowed('GraphManagement')) {
//			throw new Exception\StoreException("Failed creating the graph. Action not allowed!");
//		}
		try {
			$this->backendAdapter->createGraph($graphIri, $type);
		}
		catch (\Erfurt\Store\Adapter\Exception $e) {
			$message = defined('_EFDEBUG')
					? "Failed creating the graph. \nReason: {$e->getMessage()}."
					: 'Failed creating the graph.';
			throw new Exception\StoreException($message);
		}
		// everything ok, create new graph
		// no access control since we have already checked
		return $this->getGraph($graphIri, $useAc);
	}

	/**
	 * Returns inferred objects in realation to a certain set of resources.
	 *
	 * Returned objects are related to objects in the closure of start resources.
	 * Said closure is calculated using hte closure property. If no closure
	 * property is specified, the object property is used instead.
	 *
	 * @todo Implement generic version and call backend implementation if applicable.
	 */
	public function getObjectsInferred($graphIri, $startResources, $objectProperty, $closureProperty = null) {
	}

	/**
	 * Returns a specified config option.
	 *
	 * @param string $optionName
	 * @return string
	 */
	public function getOption($optionName) {
		if (isset($this->options[$optionName])) {
			return $this->options[$optionName];
		}
		return null;
	}

	/**
	 * Returns an array of serialization formats that can be exported.
	 *
	 * @return array
	 */
	public function getSupportedExportFormats() {
		$supportedFormats = array(
			'rdfxml' => 'RDF/XML',
			'ttl' => 'Turtle',
			'rdfjson' => 'RDF/JSON (Talis)'
		);
		return array_merge($supportedFormats, $this->backendAdapter->getSupportedExportFormats());
	}

	/**
	 * Returns an array of serialization formats that can be imported.
	 *
	 * @return array
	 */
	public function getSupportedImportFormats() {
		$supportedFormats = array(
			'rdfxml' => 'RDF/XML',
			'rdfjson' => 'RDF/JSON (Talis)',
			'ttl' => 'Turtle'
		);
		return array_merge($supportedFormats, $this->backendAdapter->getSupportedImportFormats());
	}

	/**
	 *
	 * @param string $graphIri
	 * @param string $locator Either a URL or a absolute file name.
	 * @param string $type One of:
	 *							  - 'auto' => Tries to detect the type automatically in the following order:
	 *											  1. Detect XML by XML-Header => rdf/xml
	 *											  2. If this fails use the extension of the file
	 *											  3. If this fails throw an exception
	 *							  - 'xml'
	 *							  - 'n3' or 'nt'
	 * @param string $locator Denotes whether $data is a local file or a URL.
	 *
	 * @throws \Erfurt\Exception
	 */
	public function importRdf($graphIri, $data, $type = 'auto', $locator = \Erfurt\Syntax\RdfParser::LOCATOR_FILE,
							  $needsAuthentication = true) {
		// TODO reenable cache
//		$queryCache = $this->knowledgeBase->getQueryCache();
//		$queryCache->invalidateWithGraphIri($graphIri);
		if (!$this->checkAuthorization($graphIri, 'edit', $needsAuthentication)) {
			throw new Exception\StoreException("Import failed. Graph <$graphIri> not found or not writable.");
		}
		if ($type === 'auto') {
			// detect file type
			if ($locator === \Erfurt\Syntax\RdfParser::LOCATOR_FILE && is_readable($data)) {
				$pathInfo = pathinfo($data);
				$type = array_key_exists('extension', $pathInfo) ? $pathInfo['extension'] : '';
			}
			if ($locator === \Erfurt\Syntax\RdfParser::LOCATOR_URL) {
				$headers['Location'] = true;
				// set default content-type header
				stream_context_get_default(array(
												'http' => array(
													'header' => 'Accept: application/rdf+xml, application/json, text/rdf+n3, text/plain',
													'max_redirects' => 1 // no redirects as we need the 303 URI
												)));
				do { // follow redirects
					$flag = false;
					$isRedirect = false;
					$headers = @get_headers($data, 1);
					if (is_array($headers)) {
						$http = $headers[0];
						if (false !== strpos($http, '303')) {
							$data = (string)$headers['Location'];
							$isRedirect = true;
						}
					}
				} while ($isRedirect);
				// restore default empty headers
				stream_context_get_default(array(
												'http' => array(
													'header' => ""
												)));
				if (is_array($headers) && array_key_exists('Content-Type', $headers)) {
					$ct = $headers['Content-Type'];
					if (is_array($ct)) {
						$ct = array_pop($ct);
					}
					$ct = strtolower($ct);
					if (substr($ct, 0, strlen('application/rdf+xml')) === 'application/rdf+xml') {
						$type = 'rdfxml';
						$flag = true;
					} else {
						if (substr($ct, 0, strlen('text/plain')) === 'text/plain') {
							$type = 'rdfxml';
							$flag = true;
						} else {
							if (substr($ct, 0, strlen('text/rdf+n3')) === 'text/rdf+n3') {
								$type = 'ttl';
								$flag = true;
							} else {
								if (substr($ct, 0, strlen('application/json')) === 'application/json') {
									$type = 'rdfjson';
									$flag = true;
								} else {
									// RDF/XML is default
									$type = 'rdfxml';
									$flag = true;
								}
							}
						}
					}
				}
				// try file name
				if (!$flag) {
					switch (strtolower(strrchr($data, '.'))) {
						case '.rdf':
							$type = 'rdfxml';
							break;
						case '.n3':
							$type = 'ttl';
							break;
					}
				}
			}
		}
		if (array_key_exists($type, $this->backendAdapter->getSupportedImportFormats())) {
			$result = $this->backendAdapter->importRdf($graphIri, $data, $type, $locator);
			$this->backendAdapter->init();
			return $result;
		} else {
			$parser = $this->objectManager->create('Erfurt\Syntax\RdfParser', $type);
			$retVal = $parser->parseToStore($data, $locator, $graphIri, $needsAuthentication);
			// After import re-initialize the backend (e.g. zenddb: fetch graph infos again)
			$this->backendAdapter->init();
			return $retVal;
		}
	}

	/**
	 * @param string $graphIri The Iri, which identifies the graph to look for.
	 * @param boolean $useAc Whether to use access control or not.
	 *
	 * @return boolean Returns true if graph exists and is available for the user ($useAc === true).
	 */
	public function isGraphAvailable($graphIri, $useAc = true) {
		if ($this->backendAdapter->isGraphAvailable($graphIri) && $this->checkAuthorization($graphIri, 'view', $useAc)) {
			return true;
		}
		return false;
	}

	public function isSqlSupported() {
		return ($this->backendAdapter instanceof \Erfurt\Store\Sql\Sqlnterface);
	}

	/**
	 * Returns the ID for the last insert statement.
	 */
	public function lastInsertId() {
		if ($this->backendAdapter instanceof \Erfurt\Store\Sql\Sqlnterface) {
			return $this->backendAdapter->lastInsertId();
		}
		// TODO: use default SQL store
	}

	/**
	 * Returns an array of SQL tables available in the store.
	 *
	 * @param string $prefix An optional table prefix to filter table names.
	 *
	 * @return array|null
	 */
	public function listTables($prefix = '') {
		if ($this->backendAdapter instanceof \Erfurt\Store\Sql\Sqlnterface) {
			return $this->backendAdapter->listTables($prefix);
		}
		// TODO: use default SQL store
	}

	/**
	 * Executes a SPARQL ASK query and returns a boolean result value.
	 *
	 * @param string $graphIri
	 * @param string $askSparql
	 * @param boolean $useAc Whether to check for access control.
	 */
	public function sparqlAsk(\Erfurt\Sparql\SimpleQuery $queryObject, $useAc = true) {
		// add owl:imports
		foreach ($queryObject->getFrom() as $fromGraphIri) {
			foreach ($this->getImportsClosure($fromGraphIri, true, $useAc) as $importedGraphIri) {
				$queryObject->addFrom($importedGraphIri);
			}
		}
		if ($useAc) {
			$graphsFiltered = $this->filterGraphs($queryObject->getFrom());
			// query contained a non-allowed non-existent graph
			if (empty($graphsFiltered)) {
				return;
				// throw new Exception\StoreException('Query could not be executed.');
			}
			$queryObject->setFrom($graphsFiltered);
			// from named only if it was set
			$fromNamed = $queryObject->getFromNamed();
			if (count($fromNamed)) {
				$queryObject->setFromNamed($this->filterGraphs($fromNamed));
			}
		}
		$queryCache = $this->knowledgeBase->getQueryCache();
		$sparqlResult = $queryCache->load((string)$queryObject, 'plain');
		if ($sparqlResult == \Erfurt\Cache\Frontend\QueryCache::ERFURT_CACHE_NO_HIT) {
			// TODO: check if adapter supports requested result format
			$startTime = microtime(true);
			$sparqlResult = $this->backendAdapter->sparqlAsk((string)$queryObject);
			self::$queryCount++;
			$duration = microtime(true) - $startTime;
			$queryCache->save((string)$queryObject, 'plain', $sparqlResult, $duration);
		}
		return $sparqlResult;
	}

	/**
	 * @param \Erfurt\Sparql\SimpleQuery $queryObject
	 * @param string $resultFormat Currently supported are: 'plain' and 'xml'
	 * @param boolean $useAc Whether to check for access control or not.
	 *
	 * @throws \Erfurt\Exception Throws an exception if query is no string.
	 *
	 * @return mixed Returns a result depending on the query, e.g. an array or a boolean value.
	 */
	public function sparqlQuery($queryObject, $options = array()) {
		// if ($queryObject instanceof \Erfurt\Sparql\Query2)
		//     $this->knowledgeBase->getLog()->info('Store: evaluating a Query2-object (sparql:'."\n".$queryObject.') ');
		$defaultOptions = array(
			STORE_RESULTFORMAT => STORE_RESULTFORMAT_PLAIN,
			STORE_USE_AC => true,
			STORE_USE_OWL_IMPORTS => true,
			STORE_USE_ADDITIONAL_IMPORTS => true
		);
		$options = array_merge($defaultOptions, $options);
		$noBindings = false;
		//typechecking
		if (is_string($queryObject)) {
			$queryObject = \Erfurt\Sparql\SimpleQuery::initWithString($queryObject);
		}
		if (!($queryObject instanceof \Erfurt\Sparql\Query2 || $queryObject instanceof \Erfurt\Sparql\SimpleQuery)) {
			throw new \Exception("Argument 1 passed to " . __CLASS__ . '::sparqlQuery must be instance of \Erfurt\Sparql\Query2, \Erfurt\Sparql\SimpleQuery or string', 1303224590);
		}
		/*
				 * clone the Query2 Object to not modify the original one
				 * could be used elsewhere, could have side-effects
				 */
		if ($queryObject instanceof \Erfurt\Sparql\Query2) { //always clone?
			$queryObject = clone $queryObject;
		}
		//get all graphs
		$all = array();
		$allpre = $this->backendAdapter->getAvailableGraphs(); //really all (without ac)
		foreach ($allpre as $key => $true) {
			$all[] = array('iri' => $key, 'named' => false);
		}
		//get available graphs (readable)
		$available = array();
		if ($options[STORE_USE_AC] === true) {
			$availablepre = $this->getAvailableGraphs(true);
			foreach ($availablepre as $key => $true) {
				$available[] = array('iri' => $key, 'named' => false);
			}
		} else {
			$available = $all;
		}
		// examine froms (for access control and imports) in 5 steps
		// 1. extract froms for easier handling
		$froms = array();
		if ($queryObject instanceof \Erfurt\Sparql\Query2) {
			foreach ($queryObject->getFroms() as $graphClause) {
				$iri = $graphClause->getGraphIri()->getIri();
				$froms[] = array('iri' => $iri, 'named' => $graphClause->isNamed());
			}
		} else { //SimpleQuery
			foreach ($queryObject->getFrom() as $graphClause) {
				$froms[] = array('iri' => $graphClause, 'named' => false);
			}
			foreach ($queryObject->getFromNamed() as $graphClause) {
				$froms[] = array('iri' => $graphClause, 'named' => true);
			}
		}
		// 2. no froms in query -> froms = availableGraphs
		if (empty($froms)) {
			$froms = $available;
		}
		// 3. filter froms by availability and existence - if filtering deletes all -> give empty result back
		if ($options[STORE_USE_AC] === true) {
			$froms = $this->maskGraphList($froms, $available);
			if (empty($froms)) {
				$noBindings = true;
			}
		}
		// 4. get import closure for every remaining from
		if ($options[STORE_USE_OWL_IMPORTS] === true) {
			foreach ($froms as $from) {
				$importsClosure = $this->getImportsClosure($from['iri'], $options[STORE_USE_ADDITIONAL_IMPORTS], $options[STORE_USE_AC]);
				foreach ($importsClosure as $importedGraphIri) {
					$addCandidate = array('iri' => $importedGraphIri, 'named' => false);
					if (in_array($addCandidate, $available) && array_search($addCandidate, $froms) === false) {
						$froms[] = $addCandidate;
					}
				}
			}
		}
		// 5. put froms back
		if ($queryObject instanceof \Erfurt\Sparql\Query2) {
			$queryObject->setFroms(array());
			foreach ($froms as $from) {
				$queryObject->addFrom($from['iri'], $from['named']);
			}
		} else {
			$queryObject->setFrom(array());
			$queryObject->setFromNamed(array());
			foreach ($froms as $from) {
				if (!$from['named']) {
					$queryObject->addFrom($from['iri']);
				} else {
					$queryObject->addFromNamed($from['iri']);
				}
			}
		}
		// if there were froms and all got deleted due to access controll - give back empty result set
		// this is achieved by replacing the where-part with an unsatisfiable one
		// i think this is efficient because otherwise we would have to deal with result formating und variables
		if ($noBindings) {
			if ($queryObject instanceof \Erfurt\Sparql\SimpleQuery) {
				$queryObject->setWherePart('{FILTER(false)}');
			} else {
				if ($queryObject instanceof \Erfurt\Sparql\Query2) {
					$ggp = $this->objectManager->create('Erfurt\Sparql\Query2GroupGraphPattern');
					$ggp->addFilter(false); //unsatisfiable
					$queryObject->setWhere($ggp);
				}
			}
		}
		//querying SparqlEngine or retrieving Result from QueryCache
		//TODO for query cache, please refactor
		$resultFormat = $options[STORE_RESULTFORMAT];
//		$queryCache = $this->knowledgeBase->getQueryCache();
//		$sparqlResult = $queryCache->load((string)$queryObject, $resultFormat);
//		if ($sparqlResult == \Erfurt\Cache\Frontend\QueryCache::ERFURT_CACHE_NO_HIT) {
			// TODO: check if adapter supports requested result format
			$startTime = microtime(true);
			$sparqlResult = $this->backendAdapter->sparqlQuery($queryObject, $options);
			self::$queryCount++;
			$duration = microtime(true) - $startTime;
			if (defined('_EFDEBUG')) {
				$logger = $this->getQueryLogger();
				if ($duration > 1) {
					$slow = " WARNING SLOW ";
				} else {
					$slow = "";
				}
				$logger->debug("SPARQL *****************" . round((1000 * $duration), 2) . " msec " . $slow . "\n" . $queryObject);
			}
//			$queryCache->save((string)$queryObject, $resultFormat, $sparqlResult, $duration);
//		}
		return $sparqlResult;
	}

	/**
	 * Executes a SQL query with a SQL-capable backend.
	 *
	 * @param string $sqlQuery A string containing the SQL query to be executed.
	 * @throws \Erfurt\Store\Exception\StoreException
	 * @return array
	 */
	public function sqlQuery($sqlQuery, $limit = PHP_INT_MAX, $offset = 0) {
		if ($this->backendAdapter instanceof Sql\SqlInterface) {
			$startTime = microtime(true);
			$result = $this->backendAdapter->sqlQuery($sqlQuery, $limit, $offset);
			$duration = microtime(true) - $startTime;
			if (defined('_EFDEBUG')) {
				$logger = $this->getQueryLogger();
				$logger->debug("SQL ***************** " . round((1000 * $duration), 2) . " msec \n" . $sqlQuery);
			}
			return $result;
		}
		// TODO: will throw an exception
		// throw new Exception\StoreException('Current backend doesn not support SQL queries.');
	}

	/**
	 * Get the configuration for a graph.
	 * @param string $graphIri to specity the graph
	 * @return array
	 */
	public function getGraphConfiguration($graphIri) {
		if (null === $this->graphConfigurations) {
			$sysOntGraphIri = $this->getOption('graphIri');
			// Fetch the graph configurations
			$queryObject = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
			$queryObject->setProloguePart('SELECT ?s ?p ?o');
			$queryObject->setFrom(array($sysOntGraphIri));
			$queryObject->setWherePart('WHERE { ?s ?p ?o . ?s a <http://ns.ontowiki.net/SysOnt/Graph> }');
			$queryoptions = array(
				'use_ac' => false,
				'result_format' => 'extended',
				'use_additional_imports' => false
			);
			$stmtArray = array();
			if ($result = $this->sparqlQuery($queryObject, $queryoptions)) {
				foreach ($result['bindings'] as $row) {
					if (!isset($stmtArray[$row['s']['value']])) {
						$stmtArray[$row['s']['value']] = array();
					}
					if (!isset($stmtArray[$row['s']['value']][$row['p']['value']])) {
						$stmtArray[$row['s']['value']][$row['p']['value']] = array();
					}
					if ($row['o']['type'] === 'typed-literal') {
						$row['o']['type'] = 'literal';
					}
					if (isset($row['o']['xml:lang'])) {
						$row['o']['lang'] = $row['o']['xml:lang'];
						unset($row['o']['xml:lang']);
					}
					$stmtArray[$row['s']['value']][$row['p']['value']][] = $row['o'];
				}
			}
			$this->graphConfigurations = $stmtArray;
		}
		if (isset($this->graphConfigurations[$graphIri])) {
			return $this->graphConfigurations[$graphIri];
		}
		return array();
	}

	// ------------------------------------------------------------------------
	// --- Optional Methods ---------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Counts all statements that match the SPARQL graph pattern $whereSpec.
	 *
	 * @param string $graphIri
	 * @param string $whereSpec
	 */
	public function countWhereMatches($graphIri, $whereSpec, $countSpec, $distinct = false) {
		// unify parameters
		if (trim($countSpec[0]) !== '?') {
			// TODO: support $
			$countSpec = '?' . $countSpec;
		}
		if (method_exists($this->backendAdapter, 'countWhereMatches')) {
			if ($this->isGraphAvailable($graphIri)) {
				$graphIris = array_merge($this->getImportsClosure($graphIri), array($graphIri));
				return $this->backendAdapter->countWhereMatches($graphIris, $whereSpec, $countSpec, $distinct);
			} else {
				throw new Exception\StoreException('Graph <' . $graphIri . '> is not available.');
			}
		} else {
			throw new Exception\StoreException('Count is not supported by backend.');
		}
	}

	/**
	 * Returns the class name of the currently used backend.
	 *
	 * @return string
	 */
	public function getBackendName() {
		if (method_exists($this->backendAdapter, 'getBackendName')) {
			return $this->backendAdapter->getBackendName();
		}
		return $this->backendName;
	}

	/**
	 * Returns a list of graph IRIs, where each graph in the list contains at least
	 * one statement where the given resource IRI is used as a subject.
	 *
	 * @param string $resourceIri
	 * @return array
	 */
	public function getGraphsUsingResource($resourceIri, $useAc = true) {
		if (method_exists($this->backendAdapter, 'getGraphsUsingResource')) {
			$backendResult = $this->backendAdapter->getGraphsUsingResource($resourceIri);
			if ($useAc) {
				$realResult = array();
				foreach ($backendResult as $graphIri) {
					if ($this->isGraphAvailable($graphIri, $useAc)) {
						$realResult[] = $graphIri;
					}
				}
				return $realResult;
			} else {
				return $backendResult;
			}
		}
		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT DISTINCT ?graph')
				->setWherePart('WHERE {GRAPH ?graph {<' . $resourceIri . '> ?p ?o.}}');
		$graphResult = array();
		$result = $this->sparqlQuery($query, array('use_ac' => $useAc));
		if ($result) {
			foreach ($result as $row) {
				$graphResult[] = $row['graph'];
			}
		}
		return $graphResult;
	}

	/**
	 * Returns a logo URL.
	 *
	 * @return string
	 */
	public function getLogoIri() {
		if (method_exists($this->backendAdapter, 'getLogoIri')) {
			return $this->backendAdapter->getLogoIri();
		}
	}

	/**
	 * Calculates the transitive closure for a given property and a set of starting nodes.
	 *
	 * The inverse mode (which is enabled by default) can be used to calculate the
	 * rdfs:subClassOf closure of a set of starting classes.
	 * By default this method uses a private SPARQL implementation to actually query and
	 * calculate the closure. Adapters can (and should!) provide their own implementation.
	 *
	 * @param string $propertyIri The property's IRI for which hte closure should be calculated
	 * @param array $startResources An array of resources as starting nodes
	 * @param boolean $inverse Denotes whether the property is inverse, i.e. ?child ?property ?parent
	 * @param int $maxDepth The maximum number of iteration steps
	 */
	public function getTransitiveClosure($graphIri, $property, $startResources, $inverse = true, $maxDepth = self::MAX_ITERATIONS) {
		if (method_exists($this->backendAdapter, 'getTransitiveClosure')) {
			$closure = $this->backendAdapter->getTransitiveClosure($graphIri, $property, (array)$startResources, $inverse, $maxDepth);
		} else {
			$closure = $this->_getTransitiveClosure($graphIri, $property, (array)$startResources, $inverse, $maxDepth);
		}
		return $closure;
	}

	// ------------------------------------------------------------------------
	// --- Protected Methods --------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Checks whether 'view' or 'edit' are allowed on a certain graph. The additional $useAc param
	 * makes it easy to disable access control for internal usage.
	 *
	 * @param string $graphIri The Iri, which identifies the graph.
	 * @param string $accessType Supported access types are 'view' and 'edit'.
	 * @param boolean $useAc Whether to use access control or not.
	 *
	 * @return boolean Returns whether view as the case may be edit is allowed for the graph or not.
	 */
	private function checkAuthorization($graphIri, $accessType = 'view', $useAc = true) {
		return true;
		// TODO REENABLE
		// check whether ac should be used (e.g. ac engine itself needs access to store without ac)
		if ($useAc === false) {
			$logger = $this->getErfurtLogger();
			$logger->warn("Store.php->_checkAc: Doing something without Access Controll!!!");
			$logger->debug("Store.php->_checkAc: GraphIri: " . $graphIri . " accessType: " . $accessType);
			return true;
		} else {
			if ($this->accessControl === null) {
				$this->accessControl = $this->knowledgeBase->getAccessControl();
			}
			return $this->accessControl->isGraphAllowed($accessType, $graphIri);
		}
	}

	/**
	 * Filters a list of graph IRIs according to ACL constraints of the current agent.
	 *
	 * @param array $graphIris
	 */
	private function filterGraphs(array $graphIris) {
		$allowedGraphs = array();
		foreach ($this->getAvailableGraphs(true) as $key => $true) {
			$allowedGraphs[] = $key;
		}
		return array_intersect($graphIris, $allowedGraphs);
	}


	/**
	 * This function is nearly like _filterGraphs, but you specify the mask and
	 * the list parameter is an 2D-Array of the format:
	 * array(
	 *	 array('iri' => 'http://the.graph.iri/1', 'names' => boolean),
	 *	 array('iri' => 'http://the.graph.iri/2', 'names' => boolean),
	 *	 ...
	 * )
	 * while in _filterGraphs the list is a plain list of iris.
	 * We need this function because array_intersect doesn't work on 2D-Arrays.
	 * @param array $list a 2D-Array where the iris are available with $list[<index>]['iri']
	 * @param array $maskIn the mask to apply on the list of the same format as the list
	 * @return array the list witout iri missing in $maskIn
	 */
	private function maskGraphList(array $list, array $maskIn = null) {
		$mask = array();
		if ($maskIn === null) {
			foreach ($this->getAvailableGraphs(true) as $key => $true) {
				$mask[] = $key;
			}
		} else {
			$countMaskIn = count($maskIn);
			for ($i = 0; $i < $countMaskIn; ++$i) {
				$mask[] = $maskIn[$i]['iri'];
			}
		}
		$countList = count($list);
		for ($i = 0; $i < $countList; ++$i) {
			if (array_search($list[$i]['iri'], $mask) === false) {
				// TODO: check if this maybe skips indices ...
				unset($list[$i]);
			}
		}
		return $list;
	}


	/**
	 * Calculates the transitive closure for a given property and a set of starting nodes.
	 *
	 * @see getTransitiveClosure
	 */
	private function _getTransitiveClosure($graphIri, $property, $startResources, $inverse, $maxDepth) {
		$closure = array();
		$classes = $startResources;
		$i = 0;
		$from = '';
		foreach ($this->getImportsClosure($graphIri) as $import) {
			$from .= 'FROM <' . $import . '>' . PHP_EOL;
		}
		while (++$i <= $maxDepth) {
			$where = $inverse ? '?child <' . $property . '> ?parent.' : '?parent <' . $property . '> ?child.';
			$subSparql = 'SELECT ?parent ?child
                FROM <' . $graphIri . '>' . PHP_EOL . $from . '
                WHERE {
                    ' . $where . ' OPTIONAL {?child <http://ns.ontowiki.net/SysOnt/order> ?order}
                    FILTER (
                        sameTerm(?parent, <' . implode('>) || sameTerm(?parent, <', $classes) . '>)
                    )
                }
                ORDER BY ASC(?order)';
			$subSparql = \Erfurt\Sparql\SimpleQuery::initWithString($subSparql);
			// get sub items
			$result = $this->backendAdapter->sparqlQuery($subSparql, array(STORE_RESULTFORMAT => STORE_RESULTFORMAT_PLAIN));
			// break on first empty result
			if (empty($result)) {
				break;
			}
			$classes = array();
			foreach ($result as $row) {
				// $key = $inverse ? $row['child'] : $row['parent'];
				$key = $inverse ? $row['child'] : $row['parent'];
				$closure[$key] = array(
					'node' => $inverse ? $row['child'] : $row['parent'],
					'parent' => $inverse ? $row['parent'] : $row['child'],
					'depth' => $i
				);
				$classes[] = $row['child'];
			}
		}
		// prepare start resources inclusion
		$merger = array();
		foreach ($startResources as $startIri) {
			$merger[(string)$startIri] = array(
				'node' => $startIri,
				'parent' => null,
				'depth' => 0
			);
		}
		// merge in start resources
		$closure = array_merge($merger, $closure);
		return $closure;
	}

	/**
	 * Returns the query logger, lazy initialization
	 *
	 * @return object Zend Logger, which writes to logs/queries.log
	 */
	protected function getQueryLogger() {
		if (null === $this->queryLogger) {
			$this->queryLogger = $this->knowledgeBase->getLog('queries');
		}
		return $this->queryLogger;
	}

	/**
	 * Returns the erfurt logger, lazy initialization
	 *
	 * @return object Zend Logger, which writes to logs/erfurt.log
	 */
	protected function getErfurtLogger() {
		if (null === $this->erfurtLogger) {
			$this->erfurtLogger = $this->knowledgeBase->getLog('erfurt');
		}
		return $this->erfurtLogger;
	}

}

?>