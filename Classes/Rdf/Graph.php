<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Rdf;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This class is a port of the corresponding class of the
 *  {@link http://aksw.org/Projects/Erfurt Erfurt} project.
 *  All credits go to the Erfurt team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Enter descriptions here
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 */
class Graph {

	/**
	 * The graph base IRI. If not set, defaults to the graph IRI.
	 * @var string
	 */
	protected $baseIri = null;

	/**
	 * Denotes whether the graph is editable by the current agent.
	 * @var boolean
	 */
	protected $isEditable = false;

	/**
	 * An array containing options for the graph stored in the system ontology.
	 * @var array
	 */
	protected $graphOptions = null;

	/**
	 * The graph IRI
	 * @var string
	 */
	protected $graphIri = null;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Erfurt namespace management module
	 * @var \Erfurt\Namespaces\Namespaces
	 */
	protected $namespaces = null;

	/**
	 * The graph's title property value
	 * @var string
	 */
	protected $title = null;

	/**
	 * An array of properties used in this graph to express
	 * a resource's human-readable representation.
	 * @var array
	 * @todo remove hard-coded mock title properties
	 */
	protected $titleProperties = array(
		'http://www.w3.org/2000/01/rdf-schema#label',
		'http://purl.org/dc/elements/1.1/title'
	);

	/**
	 * Constructor.
	 *
	 * @param string $graphIri
	 * @param string $baseIri
	 */
	public function __construct($graphIri, $baseIri = null) {
		$this->graphIri = $graphIri;
		$this->baseIri = $baseIri;
	}

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
		$this->namespaces = $knowledgeBase->getNamespaces();
		if (isset($this->knowledgeBase->getSystemOntologyConfiguration()->properties->title)) {
			$this->titleProperties = $this->knowledgeBase->getSystemOntologyConfiguration()->properties->title->toArray();
		}
	}

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns a string representing the graph instance. For convenience
	 * reasons this is in fact the graph IRI.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getGraphIri();
	}

	/**
	 * Adds a statement to this graph
	 *
	 * @param string $subject
	 * @param string $predicate
	 * @param array $object
	 */
	public function addStatement($subject, $predicate, array $object) {
		$this->getStore()->addStatement($this->graphIri, $subject, $predicate, $object);
		return $this;
	}

	/**
	 * Adds multiple statements to this graph.
	 *
	 * Accepts an associative array of statement subjects. The format of the
	 * array must conform to Talis' RDF/PHP specification
	 * ({@link http://n2.talis.com/wiki/RDF_PHP_Specification}).
	 *
	 * @param stdClass $statements
	 */
	public function addMultipleStatements(array $statements) {
		$this->getStore()->addMultipleStatements($this->graphIri, $statements);
		return $this;
	}

	/**
	 * Creates a unique resource IRI with the graph's base IRI as namespace and
	 * a unique ID starting with $spec.
	 *
	 * @param string $spec
	 * @return string
	 */
	public function createResourceIri($spec = '') {
		$prefix = $this->getBaseIri()
				  . $spec;
		// TODO: check uniqueness
		$prefix .= uniqid();
		return $prefix;
	}

	/**
	 * Deletes the statement denoted by subject, predicate, object.
	 *
	 * @param string $subject
	 * @param string $predicate
	 * @param string $object
	 */
	public function deleteStatement($subject, $predicate, $object) {
		$this->getStore()->deleteMatchingStatements($this->graphIri, $subject, $predicate, $object);
	}

	/**
	 * Deletes all statements contained in the associative array from this graph.
	 *
	 * @param string $subject
	 * @param string $predicate
	 * @param string $object
	 */
	public function deleteMultipleStatements(array $statements) {
		$this->getStore()->deleteMultipleStatements($this->graphIri, $statements);
	}

	/**
	 * Deletes all statements that match a certain triple pattern.
	 *
	 * The triple patterns is denoted by subject, predicate, object
	 * where one or two can be <code>null</code>.
	 *
	 * @param string|null $subjectSpec
	 * @param string|null $predicateSpec
	 * @param string|null $objectSpec
	 */
	public function deleteMatchingStatements($subjectSpec, $predicateSpec, $objectSpec, array $options = array()) {
		$this->getStore()->deleteMatchingStatements(
			$this->graphIri,
			$subjectSpec,
			$predicateSpec,
			$objectSpec,
			$options
		);
	}

	/**
	 * Returns the graph base IRI
	 *
	 * @return string
	 */
	public function getBaseIri() {
		if (empty($this->baseIri)) {
			return $this->getGraphIri();
		}
		return $this->baseIri;
	}

	/**
	 * Returns the graph IRI
	 *
	 * @return string
	 */
	public function getGraphIri() {
		return $this->graphIri;
	}

	/**
	 * Returns an array of options (the object part of an RDF/PHP array) or null
	 * if no such options exists. An option is identified through an IRI.
	 *
	 * @param string $optionIri The IRI that identifies the option.
	 * @return array|null An array containing the value(s) for the given option.
	 */
	public function getOption($optionIri) {
		$options = $this->getOptions();
		if (!isset($options[$optionIri])) {
			return null;
		} else {
			return $options[$optionIri];
		}
	}

	/**
	 * Resource factory method
	 *
	 * @return \Erfurt\Rdf\Resource
	 */
	public function getResource($resourceIri) {
		return new $this->objectManager->create('\Erfurt\Rdf\Resource', $resourceIri, $this);
	}

	/**
	 * Returns the graph's title property value.
	 *
	 * @return string
	 */
	public function getTitle() {
		if (null === $this->title) {
			$titleProperties = $this->getTitleProperties();
			$select = '';
			$where = array();
			if (!empty($titleProperties)) {
				foreach ($titleProperties as $key => $iri) {
					$select .= ' ?' . $key;
					$where[] = '{?s <' . $iri . '> ' . '?' . $key . '.}';
				}
				$query = $this->objectManager->get('Erfurt\Sparql\SimpleQueryFactory')->buildFromQueryString(
					'SELECT ' . $select . '
					 WHERE {
						' . implode(' UNION ', $where) . '
						FILTER (sameTerm(?s, <' . $this->getGraphIri() . '>))
					}'
				);
				if ($result = $this->getStore()->sparqlQuery($query)) {
					if (is_array($result) && is_array($result[0])) {
						foreach ($titleProperties as $key => $iri) {
							if (!empty($result[0][$key])) {
								$this->title = $result[0][$key];
							}
							continue;
						}
					}
				}
			}
		}
		return $this->title;
	}

	/**
	 * Returns an array of properties used in this graph to express
	 * a resource's human-readable representation.
	 *
	 * @return array
	 */
	public function getTitleProperties() {
		return $this->titleProperties;
	}

	/**
	 * Returns whether the current agent has edit privileges
	 * on this graph instance.
	 *
	 * @return bool
	 */
	public function isEditable() {
		return $this->isEditable;
	}

	/**
	 * Sets this graph's editable flag.
	 *
	 * @param boolean $editableFlag
	 */
	public function setEditable($editableFlag) {
		$this->isEditable = (boolean)$editableFlag;
		return $this;
	}

	/**
	 * Sets an option for the graph in the SysOnt.
	 * If no value is given, the option will be unset.
	 *
	 * @param string $optionIri The IRI that identifies the option.
	 * @param array|null An array (RDF/PHP object part) of values or null.
	 */
	public function setOption($optionIri, $value = null) {
		if (!$this->isEditable) {
			// User has no right to edit the graph.
			return;
		}
		$sysOntIri = $this->knowledgeBase->getSystemOntologyConfiguration()->graphIri;
		$options = $this->getOptions();
		$store = $this->getStore();
		if (isset($options[$optionIri])) {
			// In this case we need to remove the old values from sysont
			$options = array(
				'use_ac' => false, // We disable AC, for we need to write the system ontology.
			);
			$store->deleteMatchingStatements($sysOntIri, $this->graphIri, $optionIri, null, $options);
		}
		if (null !== $value) {
			$addArray = array();
			$addArray[$this->graphIri] = array();
			$addArray[$this->graphIri][$optionIri] = $value;
			$store->addMultipleStatements($sysOntIri, $addArray, false);
		}
		// TODO add this statement on graph add?!
		// Add a statement graphIri a SysOnt:graph
		$addArray[$this->graphIri] = array();
		$addArray[$this->graphIri][Erfurt\Vocabulary\Rdf::TYPE] = array();
		$addArray[$this->graphIri][Erfurt\Vocabulary\Rdf::TYPE][] = array(
			'value' => 'http://ns.ontowiki.net/SysOnt/Graph',
			'type' => 'iri'
		);
		$store->addMultipleStatements($sysOntIri, $addArray, false);
		// Reset the options
		$this->graphOptions = null;
	}

	/**
	 * Updates this graph if the mutual difference of 2 RDF/PHP arrays.
	 *
	 * Added statements are those that are found in $changed but not in $original,
	 * removed statements are found in $original but not in $changed.
	 *
	 * @param stdClass statementsObject1
	 * @param stdClass statementsObject2
	 */
	public function updateWithMutualDifference(array $original, array $changed) {
		$addedStatements = $this->_getStatementsDiff($changed, $original);
		$removedStatements = $this->_getStatementsDiff($original, $changed);
		// TODO replace this with an injector method
//		if (defined('_EFDEBUG')) {
//			require_once 'Erfurt/App.php';
//			$logger = Erfurt_App::getInstance()->getLog();
//			$logger->debug('added: ', count($addedStatements));
//			$logger->debug('removed: ', count($removedStatements));
//		}
		// var_dump('added: ', $addedStatements);
		// var_dump('removed: ', $removedStatements);
		// exit;
		$this->deleteMultipleStatements($removedStatements);
		$this->addMultipleStatements($addedStatements);
		return $this;
	}

	/**
	 * Sets the internal options array for the graph (if neccessary) and returns it.
	 * The options are actually fetched by the store class.
	 *
	 * @return array An array of all options. If there are no options for the graph
	 * an empty array is returned.
	 */
	protected function getOptions() {
		if (null === $this->graphOptions) {
			$store = $this->getStore();
			$this->graphOptions = $store->getGraphConfiguration($this->graphIri);
		}
		return $this->graphOptions;
	}

	/**
	 * Calculates the difference of two RDF/PHP arrays.
	 *
	 * The difference will contain any statement in the first object that
	 * is not contained in the second object.
	 *
	 * @param stdClass statementsObject1
	 * @param stdClass statementsObject2
	 *
	 * @return stdClass a RDF/JSON object
	 */
	private function _getStatementsDiff(array $statementsObject1, array $statementsObject2) {
		$difference = array();
		// check for each subject if it is found in object 2
		// if it is not, continue immediately
		foreach ($statementsObject1 as $subject => $predicatesArray) {
			if (!array_key_exists($subject, $statementsObject2)) {
				$difference[$subject] = $statementsObject1[$subject];
				continue;
			}
			// check for each predicate if it is found in the current
			// subject's predicates of object 2, if it is not, continue immediately
			foreach ($predicatesArray as $predicate => $objectsArray) {
				if (!array_key_exists($predicate, $statementsObject2[$subject])) {
					$difference[$subject][$predicate] = $statementsObject1[$subject][$predicate];
					continue;
				}
				// for each object we have to check if it exists in object 2
				// (subject and predicate are identical up here)
				foreach ($objectsArray as $key => $object) {
					$found = false;
					foreach ($statementsObject2[$subject][$predicate] as $object2) {
						if ($object['type'] == $object2['type'] && $object['value'] == $object2['value']) {
							if (isset($object['datatype'])) {
								if (isset($object2['datatype']) && $object['datatype'] === $object2['datatype']) {
									$found = true;
								}
							} else {
								if (!isset($object2['datatype'])) {
									if (isset($object['lang'])) {
										if (isset($object2['lang']) && $object['lang'] === $object2['lang']) {
											$found = true;
										}
									} else {
										if (!isset($object2['lang'])) {
											$found = true;
										}
									}
								}
							}
						}
					}
					// if object hasn't been found, add it
					if (!$found) {
						if (!array_key_exists($subject, $difference)) {
							$difference[$subject] = array();
						}
						if (!array_key_exists($predicate, $difference[$subject])) {
							$difference[$subject][$predicate] = array();
						}
						array_push(
							$difference[$subject][$predicate],
							$statementsObject1[$subject][$predicate][$key]
						);
					}
				}
			}
		}
		return $difference;
	}

	// ------------------------------------------------------------------------

	public function sparqlQuery($query, $options = array()) {
		$defaultOptions = array(
			'result_format' => 'plain'
		);
		$options = array_merge($defaultOptions, $options);
		// Do not allow disabling of ac here!
		if (isset($options['use_ac'])) {
			unset($options['use_ac']);
		}
		if (is_string($query)) {
			$query = $this->objectManager->get('Erfurt\Sparql\SimpleQueryFactory')->buildFromString($query);
		}
		// restrict to this graph
		if ($query instanceof \Erfurt\Sparql\SimpleQuery) {
			$query->setFrom(array($this->graphIri));
		} else {
			if ($query instanceof \Erfurt\Sparql\Query2) {
				$query->setFroms(array($this->graphIri));
			}
		}
		return $this->getStore()->sparqlQuery($query, $options);
	}


	public function getStore() {
		return $this->knowledgeBase->getStore();
	}

	/**
	 * Get all namespaces with their prefix
	 * @return array with namespace as key and prefix as value
	 */
	public function getNamespacePrefixes() {
		// $store = $this->getStore();
		// return $store->getNamespacePrefixes($this->graphIri);
		return $this->namespaces->getNamespacePrefixes($this->getGraphIri());
	}

	/**
	 * Get the prefix for one namespaces, will be created if no prefix exists
	 *
	 * @return array with namespace as key and prefix as value
	 */
	public function getNamespacePrefix($namespace) {
		// $store = $this->getStore();
		// return $store->getNamespacePrefix($this->graphIri, $namespace);
		return $this->namespaces->getNamespacePrefix($this->getGraphIri(), $namespace);
	}

	/**
	 * Add a namespace -> prefix mapping
	 * @param $prefix a prefix to identify the namespace
	 * @param $namespace the namespace iri
	 */
	public function addNamespacePrefix($prefix, $namespace) {
		// $ns = $this
		// $store = $this->getStore();
		// $store->addNamespacePrefix($this->graphIri, $prefix, $namespace);
		return $this->namespaces->addNamespacePrefix($this->getGraphIri(), $namespace, $prefix);
	}

	/**
	 * Delete a namespace -> prefix mapping
	 * @param $prefix the prefix you want to remove
	 */
	public function deleteNamespacePrefix($prefix) {
		// $store = $this->getStore();
		// $store->deleteNamespacePrefix($this->graphIri, $prefix);
		return $this->namespaces->deleteNamespacePrefix($this->getGraphIri(), $prefix);
	}

}

?>