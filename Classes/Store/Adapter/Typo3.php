<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Store\Adapter;
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
 * This is an alternative entry point to the erfurt library
 *
 * @package Semantic
 * @scope prototype
 */
use \Erfurt\Sparql;
class Typo3 implements AdapterInterface, \Erfurt\Store\Sql\SqlInterface {
	/**
	 * @array
	 */
	protected $graphCache = array();

	/**
	 * @array
	 */
	protected $graphInfoCache;

	/**
	 * @var \t3lib_DB
	 */
	protected $databaseConnection;

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
	 * @var array
	 */
	protected $titleProperties = array(
		'http://www.w3.org/2000/01/rdf-schema#label',
		'http://purl.org/dc/elements/1.1/title'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		if (!isset($GLOBALS['TYPO3_DB'])) {
			throw new \Exception('TYPO3_DB is not available. Are you running inside TYPO3 context?', 1303213706);
		}
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		if (!$this->databaseConnection->isConnected()) {
			throw new \Exception('TYPO3_DB is not connected. Something went wrong inside TYPO3', 1303213794);
		}
	}

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
		// load title properties for graph titles
//		if (isset($this->knowledgeBase->getConfiguration()->properties->title)) {
//			$this->titleProperties = $this->knowledgeBase->getConfiguration()->properties->title->toArray();
//		}
	}

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function addMultipleStatements($graphIri, array $statementsArray, array $options = array()) {
		$graphInfoCache = $this->getGraphInfos();
		$graphId = $graphInfoCache[$graphIri]['graphId'];
		$sqlQuery = 'INSERT IGNORE INTO tx_semantic_statement (g,s,p,o,s_r,p_r,o_r,st,ot,ol,od,od_r) VALUES ';
		$insertArray = array();
		$counter = 0;
		foreach ($statementsArray as $subject => $predicatesArray) {
			foreach ($predicatesArray as $predicate => $objectsArray) {
				foreach ($objectsArray as $object) {
					$sqlString = '';
					$s = $subject;
					$p = $predicate;
					$o = $object;
					// check whether the subject is a blank node
					if (substr((string)$s, 0, 2) === '_:') {
						$s = substr((string)$s, 2);
						$subjectIs = '1';
					} else {
						$subjectIs = '0';
					}
					// check the type of the object
					if ($o['type'] === 'iri') {
						$objectIs = '0';
						$lang = false;
						$dType = false;
					} else {
						if ($o['type'] === 'bnode') {
							if (substr((string)$o['value'], 0, 2) === '_:') {
								$o['value'] = substr((string)$o['value'], 2);
							}
							$objectIs = '1';
							$lang = false;
							$dType = false;
						} else {
							$objectIs = '2';
							$lang = isset($o['lang']) ? $o['lang'] : '';
							$dType = isset($o['datatype']) ? $o['datatype'] : '';
						}
					}
					$sRef = false;
					if (strlen((string)$s) > $this->getSchemaReferenceThreshold()) {
						$subjectHash = md5((string)$s);
						$sRef = $this->insertValueInto('tx_semantic_iri', $graphId, $s, $subjectHash);
						$s = substr((string)$s, 0, 128) . $subjectHash;
					}
					$pRef = false;
					if (strlen((string)$p) > $this->getSchemaReferenceThreshold()) {
						$predicateHash = md5((string)$p);
						$pRef = $this->insertValueInto('tx_semantic_iri', $graphId, $p, $predicateHash);
						$p = substr((string)$p, 0, 128) . $predicateHash;
					}
					$oRef = false;
					if (strlen((string)$o['value']) > $this->getSchemaReferenceThreshold()) {
						$objectHash = md5((string)$o['value']);
						if ($o['type'] === 'literal') {
							$tableName = 'tx_semantic_literal';
						} else {
							$tableName = 'tx_semantic_iri';
						}
						$oRef = $this->insertValueInto($tableName, $graphId, $o['value'], $objectHash);
						$o['value'] = substr((string)$o['value'], 0, 128) . $objectHash;
					}
					$oValue = addslashes($o['value']);
					$sqlString .= "($graphId,'$s','$p','$oValue',";
					#$data = array(
					#    'g'     => $graphId,
					#    's'     => $subject,
					#    'p'     => $predicate,
					#    'o'     => $object['value'],
					#    'st'    => $subjectIs,
					#    'ot'    => $objectIs
					#);
					if ($sRef !== false) {
						$sqlString .= "$sRef,";
					} else {
						$sqlString .= "\N,";
					}
					if ($pRef !== false) {
						$sqlString .= "$pRef,";
					} else {
						$sqlString .= "\N,";
					}
					if ($oRef !== false) {
						$sqlString .= "$oRef,";
					} else {
						$sqlString .= "\N,";
					}
					$sqlString .= "$subjectIs,$objectIs,'$lang',";
					#$data['ol'] = $lang;
					if (strlen((string)$dType) > $this->getSchemaReferenceThreshold()) {
						$dTypeHash = md5((string)$dType);
						$dtRef = $this->insertValueInto('tx_semantic_iri', $graphId, $dType, $dTypeHash);
//						$dType = substr((string)$data['od'], 0, 128) . $dTypeHash;
						$dType = $dTypeHash;
//						$data['od_r'] = $dtRef;
						$sqlString .= "'$dType',$dtRef)";
					} else {
						#$data['od'] = $dType;
						$sqlString .= "'$dType',\N)";
					}
					$insertArray[] = $sqlString;
					$counter++;
					#try {
					#    $this->_dbConn->insert('tx_semantic_statement', $data);
					#    $counter++;
					#} catch (\Exception $e) {
					#    if ($this->_getNormalizedErrorCode() === 1000) {
					#        continue;
					#    } else {
					#        $this->_dbConn->rollback();
					#        throw new \Exception('Bulk insertion of statements failed: ' .
					#                        $this->_dbConn->getConnection()->error);
					#    }
					#}
				}
			}
		}
		$sqlQuery .= implode(',', $insertArray);
		if (defined('_EFDEBUG')) {
			$logger = $this->knowledgeBase->getLog();
			$logger->info('ZendDb multiple statements added: ' . $counter);
		}
		if ($counter > 0) {
			$this->sqlQuery($sqlQuery);
		}
		if ($counter > 100) {
			$this->optimizeTables();
		}
	}

	protected function getNormalizedErrorCode() {
		if ($this->databaseConnection instanceof \Zend_Db_Adapter_Mysqli) {
			switch ($this->databaseConnection->getConnection()->errno) {
				case 1062:
					// duplicate entry
					return 1000;
			}
		} else {
			return -1;
		}
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function addStatement($graphIri, $subject, $predicate, $object, array $options = array()) {
		$statementArray = array();
		$statementArray["$subject"] = array();
		$statementArray["$subject"]["$predicate"] = array();
		$statementArray["$subject"]["$predicate"][] = $object;
		try {
			$this->addMultipleStatements($graphIri, $statementArray);
		}
		catch (\Exception $e) {
			throw new \Exception('Insertion of statement failed:' .
													 $e->getMessage());
		}
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function countWhereMatches($graphIris, $whereSpec, $countSpec, $distinct = false) {
		$query = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');;
		if (!$distinct) {
			$query->setProloguePart("COUNT DISTINCT $countSpec"); // old way: distinct has no effect !!!
		} else {
			$query->setProloguePart("COUNT-DISTINCT $countSpec"); // i made a (uncool) hack to fix this, the "-" is there because i didnt want to change tokenization
		}
		$query->setFrom($graphIris)
				->setWherePart($whereSpec);
		$result = $this->sparqlQuery($query);
		if ($result) {
			return $result;
		}
		return 0;
	}

	/** @see \Erfurt\Store\Sql\SqlInterface */
	public function createTable($tableName, array $columns) {
		throw new \Exception('TYPO3 Backend does not support create table actions. Do it via the exension manager.', 1303219098);
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function createGraph($graphIri, $type = \Erfurt\Store\Store::GRAPH_TYPE_OWL) {
		$data = array(
			'iri' => &$graphIri
		);
		$baseIri = $graphIri;
		if ($baseIri !== '') {
			$data['base'] = $baseIri;
		}
		// insert the new graph into the database
		$this->databaseConnection->exec_INSERTquery('tx_semantic_graph', $data);
		$graphId = $this->lastInsertId();
		$iriRef = false;
		if (strlen($graphIri) > $this->getSchemaReferenceThreshold()) {
			// TODO check this, this is somehow wrong
//			$iriHash = md5($graphIri);
//			$iriData = array(
//				'g' => $graphId,
//				'v' => $graphIri,
//				'vh' => $iriHash);
//			$iriRef = $this->insertValueInto('tx_semantic_iri', $iriRef, $iriData, $iriHash);
//			$updateData = array(
//				'iri' => $iriHash,
//				'iri_r' => $iriRef);
//			$this->databaseConnection->exec_UPDATEquery('tx_semantic_graph', 'id = graphId', $updateData);
		}
		$baseRef = false;
		if (strlen($baseIri) > $this->getSchemaReferenceThreshold()) {
			// TODO check this, this is somehow wrong
//			$baseHash = md5($baseIri);
//			$baseData = array(
//				'g' => $graphId,
//				'v' => $baseIri,
//				'vh' => $baseHash);
//			$baseRef = $this->insertValueInto('tx_semantic_iri', $baseData);
//			$updateData = array(
//				'base' => $baseHash,
//				'base_r' => $baseRef);
//			$this->databaseConnection->exec_UPDATEquery('tx_semantic_graph', 'id = graphId', $updateData);
		}
		// invalidate the cache and fetch graph infos again
		$cache = $this->knowledgeBase->getCache();
		$cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('graph_info'));
		$this->graphInfoCache = null;
		if ($type === \Erfurt\Store\Store::GRAPH_TYPE_OWL) {
			$this->addStatement($graphIri, $graphIri, Erfurt\Vocabulary\Rdf::TYPE, array('type' => 'iri', 'value' => Erfurt\Vocabulary\Owl::ONTOLOGY));
			$this->graphInfoCache = null;
		}
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function deleteMatchingStatements($graphIri, $subject, $predicate, $object, array $options = array()) {
		$graphInfoCache = $this->getGraphInfos();
		$graphId = $graphInfoCache[$graphIri]['graphId'];
		if ($subject !== null && strlen($subject) > $this->getSchemaReferenceThreshold()) {
			$subject = substr($subject, 0, 128) . md5($subject);
		}
		if ($predicate !== null && strlen($predicate) > $this->getSchemaReferenceThreshold()) {
			$predicate = substr($predicate, 0, 128) . md5($predicate);
		}
		if ($object !== null && strlen($object['value']) > $this->getSchemaReferenceThreshold()) {
			$object = substr($object['value'], 0, 128) . md5($object['value']);
		}
		$whereString = '1';
		// determine the rows, which should be deleted by the given parameters
		if ($subject !== null) {
			$whereString .= " AND s = '$subject'";
		}
		if ($predicate !== null) {
			$whereString .= " AND p = '$predicate'";
		}
		if (null !== $subject) {
			if (substr($subject, 0, 2) === '_:') {
				$whereString .= ' AND st = 1';
			} else {
				$whereString .= ' AND st = 0';
			}
		}
		if (null !== $object) {
			if (isset($object['value'])) {
				$whereString .= ' AND o = "' . $object['value'] . '"';
			}
			if (isset($object['type'])) {
				switch ($object['type']) {
					case 'iri':
						$whereString .= ' AND ot = 0';
						break;
					case 'literal':
						$whereString .= ' AND ot = 2';
						break;
					case 'bnode':
						$whereString .= ' AND ot = 1';
						break;
				}
			}
			if (isset($object['lang'])) {
				$whereString .= ' AND ol = "' . $object['lang'] . '"';
			}
			if (isset($object['datatype'])) {
				if (strlen($object['datatype']) > $this->getSchemaReferenceThreshold()) {
					$whereString .= ' AND od = "' . substr($object['datatype'], 0, 128) .
									md5($object['datatype']) . '"';
				} else {
					$whereString .= ' AND od = "' . $object['datatype'] . '"';
				}
			}
		}
		// remove the specified statements from the database
		$ret = $this->databaseConnection->delete('tx_semantic_statement', $whereString);
		// Clean up tx_semantic_iri and tx_semantic_literal table
		$this->_cleanUpValueTables($graphIri);
		// return number of affected rows (>0 means there were triples deleted)
		return $ret;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function deleteMultipleStatements($graphIri, array $statementsArray) {
		$graphInfoCache = $this->getGraphInfos();
		$graphId = $graphInfoCache[$graphIri]['graphId'];
		$this->databaseConnection->beginTransaction();
		try {
			foreach ($statementsArray as $subject => $predicatesArray) {
				foreach ($predicatesArray as $predicate => $objectsArray) {
					foreach ($objectsArray as $object) {
						$whereString = 'g = ' . $graphId . ' ';
						// check whether the subject is a blank node
						if (substr($subject, 0, 2) === '_:') {
							$subject = substr($subject, 2);
							$whereString .= 'AND st = 1 ';
						} else {
							$whereString .= 'AND st = 0 ';
						}
						// check the type of the object
						if ($object['type'] === 'iri') {
							$whereString .= 'AND ot = 0 ';
						} else {
							if ($object['type'] === 'bnode') {
								$whereString .= 'AND ot = 1 ';
							} else {
								$whereString .= 'AND ot = 2 ';
								$whereString .= isset($object['lang']) ? 'AND ol = \'' . $object['lang'] . '\' ' : '';
								$whereString .= isset($object['datatype']) ? 'AND od = \'' . $object['datatype'] .
																			 '\' ' : '';
							}
						}
						if (strlen((string)$subject) > $this->getSchemaReferenceThreshold()) {
							$subjectHash = md5((string)$subject);
							$subject = substr((string)$subject, 0, 128) . $subjectHash;
						}
						if (strlen((string)$predicate) > $this->getSchemaReferenceThreshold()) {
							$predicateHash = md5((string)$predicate);
							$predicate = substr((string)$predicate, 0, 128) . $predicateHash;
						}
						if (strlen((string)$object['value']) > $this->getSchemaReferenceThreshold()) {
							$objectHash = md5((string)$object['value']);
							$object = substr((string)$object['value'], 0, 128) . $objectHash;
						} else {
							$object = $object['value'];
						}
						$whereString .= 'AND s = \'' . $subject . '\' ';
						$whereString .= 'AND p = \'' . $predicate . '\' ';
						$whereString .= 'AND o = \'' . str_replace('\'', '\\\'', $object) . '\' ';
						$this->databaseConnection->delete('tx_semantic_statement', $whereString);
					}
				}
			}
			// if everything went ok... commit the changes to the database
			$this->databaseConnection->commit();
			$this->_cleanUpValueTables($graphIri);
		}
		catch (\Exception $e) {
			// something went wrong... rollback
			$this->databaseConnection->rollback();
			throw new \Exception('Bulk deletion of statements failed.' . $e->getMessage());
		}
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function deleteGraph($graphIri) {
		$graphInfoCache = $this->getGraphInfos();
		if (isset($graphInfoCache[$graphIri]['graphId'])) {
			$graphId = $graphInfoCache[$graphIri]['graphId'];
		} else {
			throw new \Exception('Graph deletion failed: No db id found for graph URL.');
		}
		// remove all rows with the specified graphID from the graphs, statements and namespaces tables
		$this->databaseConnection->delete('tx_semantic_graph', "id = $graphId");
		$this->databaseConnection->delete('tx_semantic_statement', "g = $graphId");
		$this->databaseConnection->delete('tx_semantic_iri', "g = $graphId");
		$this->databaseConnection->delete('tx_semantic_literal', "g = $graphId");
		// invalidate the cache and fetch graph infos again
		$cache = $this->knowledgeBase->getCache();
		$tags = array('graph_info', $graphInfoCache[$graphIri]['graphId']);
		#$cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
		$this->graphCache = array();
		$this->graphInfoCache = null;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function exportRdf($graphIri, $serializationType = 'xml', $filename = false) {
		throw new \Exception('Not implemented yet.');
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function getAvailableGraphs() {
		$graphInfoCache = $this->getGraphInfos();
		$graphs = array();
		foreach ($graphInfoCache as $mInfo) {
			$graphs[$mInfo['graphIri']] = true;
		}
		return $graphs;
	}

	public function getBackendName() {
		return 'ZendDb';
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function getBlankNodePrefix() {
		return 'bNode';
	}

	/**
	 * Returns a list of graph iris, where each graph in the list contains at least
	 * one statement where the given resource iri is subject.
	 *
	 * @param string $resourceIri
	 * @return array
	 */
	public function getGraphsUsingResource($resourceIri) {
		$sqlQuery = 'SELECT DISTINCT g.iri FROM tx_semantic_statement s
                     LEFT JOIN tx_semantic_graph g ON ( g.id = s.g)
                     WHERE s.s = \'' . $resourceIri . '\'';
		$sqlResult = $this->sqlQuery($sqlQuery);
		$result = array();
		foreach ($sqlResult as $row) {
			$result[] = $row['iri'];
		}
		return $result;
	}

	/**
	 * Recursively gets owl:imported graph IRIs starting with $graphIri as root.
	 *
	 * @param string $graphIri
	 */
	public function getImportsClosure($graphIri) {
		$graphInfoCache = $this->getGraphInfos();
		if (isset($graphInfoCache["$graphIri"]['imports'])) {
			return $graphInfoCache["$graphIri"]['imports'];
		} else {
			return array();
		}
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function getGraph($graphIri) {
		// if graph is already in cache return the cached value
		if (isset($this->graphCache[$graphIri])) {
			return clone $this->graphCache[$graphIri];
		}
		$graphInfoCache = $this->getGraphInfos();
		$baseIri = $graphInfoCache[$graphIri]['baseIri'];
		if ($baseIri === '') {
			$baseIri = null;
		}
		// choose the right type for the graph instance and instanciate it
		if ($graphInfoCache[$graphIri]['type'] === 'owl') {
			$m = $this->objectManager->create('\Erfurt\Owl\Graph', $graphIri, $baseIri);
		} else {
			if ($this->graphInfoCache[$graphIri]['type'] === 'rdfs') {
				$m = $this->objectManager->create('\Erfurt\Rdfs\Graph', $graphIri, $baseIri);
			} else {
				$m = $this->objectManager->create('\Erfurt\Rdf\Graph', $graphIri, $baseIri);
			}
		}
		$this->graphCache[$graphIri] = $m;
		return $m;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function getNewGraph($graphIri, $baseIri = '', $type = 'owl') {
		$data = array(
			'iri' => &$graphIri
		);
		if ($baseIri !== '') {
			$data['base'] = $baseIri;
		}
		// insert the new graph into the database
		$this->databaseConnection->insert('tx_semantic_graph', $data);
		$graphId = $this->lastInsertId();
		$iriRef = false;
		if (strlen($graphIri) > $this->getSchemaReferenceThreshold()) {
			// TODO duplicate code like in self::createGraph()
//			$iriHash = md5($iri);
//			$iriData = array(
//				'g' => $graphid,
//				'v' => $iri,
//				'vh' => $iriHash);
//			$iriRef = $this->insertValueInto('tx_semantic_iri', $iriData);
//			$updateData = array(
//				'iri' => $iriHash,
//				'iri_r' => $iriRef);
//			$this->databaseConnection->update('tx_semantic_graph', $updateData, "id = graphId");
		}
		$baseRef = false;
		if (strlen($baseIri) > $this->getSchemaReferenceThreshold()) {
			// TODO duplicate code like in self::createGraph()
//			$baseHash = md5($baseIri);
//			$baseData = array(
//				'g' => $graphid,
//				'v' => $baseIri,
//				'vh' => $baseHash);
//			$baseRef = $this->insertValueInto('tx_semantic_iri', $baseData);
//			$updateData = array(
//				'base' => $baseHash,
//				'base_r' => $baseRef);
//			$this->databaseConnection->update('tx_semantic_graph', $updateData, "id = graphId");
		}
		// invalidate the cache and fetch graph infos again
		$cache = $this->knowledgeBase->getCache();
		$cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('graph_info'));
		$this->graphInfoCache = null;
		if ($type === 'owl') {
			$this->addStatement($graphIri, $graphIri, Erfurt\Vocabulary\Rdf::TYPE, array('type' => 'iri', 'value' => Erfurt\Vocabulary\Owl::ONTOLOGY));
			$this->graphInfoCache = null;
		}
		// instanciate the graph
		$m = $this->getGraph($graphIri);
		return $m;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function getSupportedExportFormats() {
		return array();
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function getSupportedImportFormats() {
		return array();
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function importRdf($graphIri, $data, $type, $locator) {
		// TODO fix or remove
		if ($this->databaseConnection instanceof \t3lib_DB) {
			$parser = $this->objectManager->create('\Erfurt\Syntax\RdfParser', $type);
			$parsedArray = $parser->parse($data, $locator, $graphIri, false);
			$graphInfoCache = $this->getGraphInfos();
			$graphId = $graphInfoCache["$graphIri"]['graphId'];
			// create file
			$tmpDir = $this->knowledgeBase->getTemporaryDirectory();
			$filename = $tmpDir . '/import' . md5((string)time()) . '.csv';
			$fileHandle = fopen($filename, 'w');
			$count = 0;
			$longStatements = array();
			foreach ($parsedArray as $s => $pArray) {
				if (substr($s, 0, 2) === '_:') {
					$s = substr($s, 2);
					$sType = '1';
				} else {
					$sType = '0';
				}
				foreach ($pArray as $p => $oArray) {
					foreach ($oArray as $o) {
						// to long values need to be put in a different table, so we can't bulk insert these
						// values, for they need a foreign key
						if (strlen($s) > $this->getSchemaReferenceThreshold() ||
							strlen($p) > $this->getSchemaReferenceThreshold() ||
							strlen($o['value']) > $this->getSchemaReferenceThreshold() ||
							(isset($o['datatype']) && strlen($o['datatype']) > $this->getSchemaReferenceThreshold())) {
							$longStatements[] = array(
								's' => $s,
								'p' => $p,
								'o' => $o
							);
							continue;
						}
						if ($o['type'] === 'literal') {
							$oType = '2';
						} else {
							if ($o['type'] === 'bnode') {
								if (substr($o['value'], 0, 2) === '_:') {
									$o['value'] = substr($o['value'], 2);
								}
								$oType = '1';
							} else {
								$oType = '0';
							}
						}
						$lineString = $graphId . ';' . $s . ';' . $p . ';' . $o['value'] . ';';
						$lineString .= "\N;\N;\N;";
						$lineString .= $sType . ';' . $oType . ';';
						if (isset($o['lang'])) {
							$lineString .= $o['lang'];
						} else {
							$lineString .= "\N";
						}
						$lineString .= ';';
						if (isset($o['datatype'])) {
							$lineString .= $o['datatype'] . ";\N";
						} else {
							$lineString .= "\N;\N";
						}
						$lineString .= PHP_EOL;
						$count++;
						fputs($fileHandle, $lineString);
					}
				}
			}
			fclose($fileHandle);
			if ($count > 10000) {
				$this->databaseConnection->sql_query('ALTER TABLE tx_semantic_statement DISABLE KEYS');
			}
			$sql = "LOAD DATA INFILE '$filename' IGNORE INTO TABLE tx_semantic_statement
                    FIELDS TERMINATED BY ';'
                    (g, s, p, o, s_r, p_r, o_r, st, ot, ol, od, od_r);";
			$this->databaseConnection->sql_query('START TRANSACTION;');
			$this->databaseConnection->sql_query($sql);
			$this->databaseConnection->sql_query('COMMIT');
			// Delete the temp file
			unlink($filename);
			// Now add the long-value-statements
			foreach ($longStatements as $stm) {
				$sId = false;
				$pId = false;
				$oId = false;
				$dtId = false;
				$s = $stm['s'];
				$p = $stm['p'];
				$o = $stm['o']['value'];
				if (strlen($s) > $this->getSchemaReferenceThreshold()) {
					$sHash = md5($s);
					$sId = $this->insertValueInto('tx_semantic_iri', $graphId, $s, $sHash);
					$s = substr($s, 0, 128) . $sHash;
				}
				if (strlen($p) > $this->getSchemaReferenceThreshold()) {
					$pHash = md5($p);
					$pId = $this->insertValueInto('tx_semantic_iri', $graphId, $p, $pHash);
					$p = substr($p, 0, 128) . $pHash;
				}
				if (strlen($o) > $this->getSchemaReferenceThreshold()) {
					$oHash = md5($o);
					if ($stm['o']['type'] === 'literal') {
						$oId = $this->insertValueInto('tx_semantic_literal', $graphId, $o, $oHash);
					} else {
						$oId = $this->insertValueInto('tx_semantic_iri', $graphId, $o, $oHash);
					}
					$o = substr($o, 0, 128) . $oHash;
				}
				if (isset($stm['o']['datatype']) && strlen($stm['o']['datatype']) > $this->getSchemaReferenceThreshold()) {
					// TODO: where does $oDt come from?
//					$oDtHash = md5($stm['o']['datatype']);
//					$dtId = $this->insertValueInto('tx_semantic_iri', $graphId, $stm['o']['datatype'], $oDtHash);
//					$oDt = substr($oDt, 0, 128) . $oDtHash;
				}
				$sql = "INSERT INTO tx_semantic_statement
                        (g,s,p,o,s_r,p_r,o_r,st,ot,ol,od,od_r)
                        VALUES ($graphId,'$s','$p','$o',";
				if ($sId !== false) {
					$sql .= $sId . ',';
				} else {
					$sql .= "\N,";
				}
				if ($pId !== false) {
					$sql .= $pId . ',';
				} else {
					$sql .= "\N,";
				}
				if ($oId !== false) {
					$sql .= $oId . ',';
				} else {
					$sql .= "\N,";
				}
				if (substr($stm['s'], 0, 2) === '_:') {
					$sql .= '1,';
				} else {
					$sql .= '0,';
				}
				if ($stm['o']['type'] === 'literal') {
					$sql .= '2,';
				} else {
					if ($stm['o']['type'] === 'iri') {
						$sql .= '0,';
					} else {
						$sql .= '1,';
					}
				}
				if (isset($stm['o']['lang'])) {
					$sql .= '"' . $stm['o']['lang'] . '",';
				} else {
					$sql .= "\N,";
				}
				if (isset($stm['o']['datatype'])) {
					if ($dtId !== false) {
						// TODO where does $oDt come from?
//						$sql .= '"' . $oDt . '",' . $dtId . ')';
					} else {
						$sql .= '"' . $stm['o']['datatype'] . '",' . "\N)";
					}
				} else {
					$sql .= "\N,\N)";
				}
				//$this->_dbConn->sql_query($sql);
			}
			if ($count > 10000) {
				$this->databaseConnection->sql_query('ALTER TABLE tx_semantic_statement ENABLE KEYS');
			}
			$this->optimizeTables();
		} else {
			throw new \Exception('CSV import not supported for this database server.');
		}
	}

	public function init() {
		$this->graphInfoCache = null;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function isGraphAvailable($graphIri) {
		$graphInfoCache = $this->getGraphInfos();
		if (isset($graphInfoCache[$graphIri])) {
			return true;
		} else {
			return false;
		}
	}

	/** @see \Erfurt\Store\Sql\SqlInterface */
	public function lastInsertId() {
		return $this->databaseConnection->sql_insert_id();
	}

	/** @see \Erfurt\Store\Sql\SqlInterface */
	public function listTables($prefix = '') {
		return $this->databaseConnection->admin_get_tables();
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function sparqlAsk($query) {
		//TODO works for me...., why hasnt this be enabled earlier? is the same as sparqlQuery... looks like the engine supports it. but there is probably a reason for this not to be supported
		$start = microtime(true);
		$engine = $this->objectManager->create('\Erfurt\Sparql\EngineDb\Adapter\Typo3', $this->databaseConnection, $this->getGraphInfos());
		$parser = $this->objectManager->create('\Erfurt\Sparql\Parser');
		if (!($query instanceof Sparql\Query)) {
			$query = $parser->parse((string)$query);
		}
		$result = $engine->queryGraph($query);
		// Debug executed SPARQL queries in debug mode (7)
		$logger = $this->knowledgeBase->getLog();
		$time = (microtime(true) - $start) * 1000;
		$debugText = 'SPARQL Query (' . $time . ' ms)';
		$logger->debug($debugText);
		return $result;
	}

	/** @see \Erfurt\Store\Adapter\AdapterInterface */
	public function sparqlQuery($query, $options = array()) {
		$resultform = (isset($options[STORE_RESULTFORMAT])) ? $options[STORE_RESULTFORMAT] : STORE_RESULTFORMAT_PLAIN;
		$start = microtime(true);
		$engine = $this->objectManager->create('\Erfurt\Sparql\EngineDb\Adapter\Typo3', $this->databaseConnection, $this->getGraphInfos());
		$parser = $this->objectManager->create('\Erfurt\Sparql\Parser');
		if (!($query instanceof Sparql\Query)) {
			$query = $parser->parse((string)$query);
		}
		$result = $engine->queryGraph($query, $resultform);
		// Debug executed SPARQL queries in debug mode (7)
		$logger = $this->knowledgeBase->getLog();
		$time = (microtime(true) - $start) * 1000;
		$debugText = 'SPARQL Query (' . $time . ' ms)';
//		$logger->debug($debugText);
		return $result;
	}

	/** @see \Erfurt\Store\Sql\SqlInterface */
	public function sqlQuery($sqlQuery, $limit = PHP_INT_MAX, $offset = 0) {
		$start = microtime(true);
		// add limit/offset
		if ($limit < PHP_INT_MAX) {
			$sqlQuery = sprintf('%s LIMIT %d OFFSET %d', (string)$sqlQuery, (int)$limit, (int)$offset);
		}
		$queryType = strtolower(substr($sqlQuery, 0, 6));
		if ($queryType === 'insert' ||
			$queryType === 'update' ||
			$queryType === 'create' ||
			$queryType === 'delete') {
			$result = $this->databaseConnection->sql_query($sqlQuery);
		} else {
			$resultPointer = $this->databaseConnection->sql_query($sqlQuery);
			if ($resultPointer) {
				$result = array();
				while ($row = $this->databaseConnection->sql_fetch_assoc($resultPointer)) {
					$result[] = $row;
				}
			} else {
				return $resultPointer;
			}
		}
		// Debug executed SQL queries in debug mode (7)
//		$logger = $this->knowledgeBase->getLog();
//		$time = (microtime(true) - $start) * 1000;
//		$debugText = 'SQL Query (' . $time . ' ms)';
//		$logger->debug($debugText);
		return $result;
	}

	protected function getGraphInfos() {
		if (null === $this->graphInfoCache) {
			// try to fetch graph and namespace infos... if all tables are present this should not lead to an error.
			$this->fetchGraphInfos();
		}
		return $this->graphInfoCache;
	}

	protected function getSchemaReferenceThreshold() {
		// We use 160, for the max index length is 1000 byte and the unique_stmt index needs
		// to fit in.
		return 160;
	}

	protected function optimizeTables() {
		if ($this->databaseConnection instanceof \Zend_Db_Adapter_Mysqli) {
			$this->databaseConnection->sql_query('OPTIMIZE TABLE tx_semantic_statement');
			$this->databaseConnection->sql_query('OPTIMIZE TABLE tx_semantic_iri');
			$this->databaseConnection->sql_query('OPTIMIZE TABLE tx_semantic_literal');
		} else {
			// not supported yet.
		}
	}

	protected function _cleanUpValueTables($graphIri) {
		if (isset($this->graphInfoCache[$graphIri]['graphId'])) {
			$graphId = $this->graphInfoCache[$graphIri]['graphId'];
		} else {
			throw new \Exception('Failed to clean up value tables: No db id for <' . $graphIri .
													 '> was found.');
		}
		$sql = "SELECT l.id as id, count(l.id)
                FROM tx_semantic_literal l
                JOIN tx_semantic_statement s ON s.g = $graphId AND s.ot = 2 AND s.o_r = l.id
                WHERE l.g = $graphId
                GROUP BY l.id";
		$idArray = array();
		foreach ($this->databaseConnection->fetchAssoc($sql) as $row) {
			$idArray[] = $row['id'];
		}
		if (count($idArray) > 0) {
			$ids = implode(',', $idArray);
			$whereString = "g = $graphId AND id NOT IN ($ids)";
			$this->databaseConnection->delete('tx_semantic_literal', $whereString);
		}
		$sql = "SELECT u.id as id, count(u.id)
                FROM tx_semantic_iri u
                JOIN tx_semantic_statement s ON s.g = $graphId AND (s.s_r = u.id OR s.p_r = u.id OR s.od_r = u.id OR
                (s.ot IN (0, 1) AND s.o_r = u.id))
                WHERE u.g = $graphId
                GROUP BY u.id";
		$idArray = array();
		foreach ($this->databaseConnection->fetchAssoc($sql) as $row) {
			$idArray[] = $row['id'];
		}
		if (count($idArray) > 0) {
			$ids = implode(',', $idArray);
			$whereString = "g = $graphId AND id NOT IN ($ids)";
			$this->databaseConnection->delete('tx_semantic_iri', $whereString);
		}
	}


	protected function insertValueInto($tableName, $graphId, $value, $valueHash) {
		$data = array(
			'g' => &$graphId,
			'v' => &$value,
			'vh' => &$valueHash
		);
		try {
			$this->databaseConnection->exec_INSERTquery($tableName, $data);
		}
		catch (\Exception $e) {
			if ($this->getNormalizedErrorCode() !== 1000) {
				throw new \Exception("Insertion of value into $tableName failed: " .
														 $e->getMessage());
			}
		}
//		$sql = "SELECT id FROM $tableName WHERE vh = '$valueHash'";
		$result = $this->databaseConnection->exec_SELECTgetSingleRow('id', $tableName, 'vh = ' . $valueHash .'');
		if (!$result) {
			throw new \Exception('Fetching of iri id failed: ' .
													 $this->databaseConnection->sql_error());
		}
		$id = $result['id'];
		return $id;
	}

	/**
	 *
	 * @throws \Erfurt\Exception
	 */
	private function fetchGraphInfos() {
		$cache = $this->knowledgeBase->getCache();
		$id = $cache->makeId($this, '_fetchGraphInfos', array());
		$cachedVal = $cache->load($id);
		if ($cachedVal) {
			$this->graphInfoCache = $cachedVal;
		} else {
			$sql = 'SELECT g.id, g.iri, g.iri_r, g.base, g.base_r, s.o, u.v,
                        (SELECT count(*)
                        FROM tx_semantic_statement s2
                        WHERE s2.g = g.id
                        AND s2.s = g.iri
                        AND s2.st = 0
                        AND s2.p = \'' . Erfurt\Vocabulary\Rdf::TYPE . '\'
                        AND s2.o = \'' . Erfurt\Vocabulary\Owl::ONTOLOGY . '\'
                        AND s2.ot = 0) as is_owl_ontology
                    FROM tx_semantic_graph g
                    LEFT JOIN tx_semantic_statement s ON (g.id = s.g
                        AND g.iri = s.s
                        AND s.p = \'' . Erfurt\Vocabulary\Owl::IMPORTS . '\'
                        AND s.ot = 0)
                    LEFT JOIN tx_semantic_iri u ON (u.id = g.iri_r OR u.id = g.base_r OR u.id = s.o_r)';
			$result = $this->sqlQuery($sql);
			if ($result === false) {
				throw new \Exception('Error while fetching graph and namespace informations. Possibly the tables required for TYPO3 Store Adapter are not set up correctly. Check in Extension Manager.', 1303219180);
			} else {
				$this->graphInfoCache = array();
				#$rowSet = $result->fetchAll();
				#var_dump($result);exit;
				foreach ($result as $row) {
					if (!isset($this->graphInfoCache[$row['iri']])) {
						$this->graphInfoCache[$row['iri']]['graphId'] = $row['id'];
						$this->graphInfoCache[$row['iri']]['graphIri'] = $row['iri'];
						$this->graphInfoCache[$row['iri']]['baseIri'] = $row['base'];
						$this->graphInfoCache[$row['iri']]['imports'] = array();
						// set the type of the graph
						if ($row['is_owl_ontology'] > 0) {
							$this->graphInfoCache[$row['iri']]['type'] = 'owl';
						} else {
							$this->graphInfoCache[$row['iri']]['type'] = 'rdfs';
						}
						if ($row['o'] !== null &&
							!isset($this->graphInfoCache[$row['iri']]['imports'][$row['o']])) {
							$this->graphInfoCache[$row['iri']]['imports'][$row['o']] = $row['o'];
						}
					} else {
						if ($row['o'] !== null &&
							!isset($this->graphInfoCache[$row['iri']]['imports'][$row['o']])) {
							$this->graphInfoCache[$row['iri']]['imports'][$row['o']] = $row['o'];
						}
					}
				}
				//var_dump($this->_graphInfoCache);exit;
				// build the transitive closure for owl:imports
				// check for recursive owl:imports; also check for cylces!
				do {
					// indicated whether anything was changed in the array or not and whether loop needs to run again
					$hasChanged = false;
					// test every graph exists in the graph table
					foreach ($this->graphInfoCache as $graphIri) {
						// only owl graphs can import other graphs
						if ($graphIri['type'] !== 'owl') {
							continue;
						}
						foreach ($graphIri['imports'] as $importsIri) {
							if (isset($this->graphInfoCache[$importsIri])) {
								foreach ($this->graphInfoCache[$importsIri]['imports'] as $importsImportIri) {
									if (!isset($graphIri['imports'][$importsImportIri]) &&
										!($importsImportIri === $graphIri['graphIri'])) {
										$this->graphInfoCache[$graphIri['graphIri']]
										['imports'][$importsImportIri] = $importsImportIri;
										$hasChanged = true;
									}
								}
							}
						}
					}
				} while ($hasChanged === true);
			}
			$cache->save($this->graphInfoCache, $id, array('graph_info'));
		}
	}

	/**
	 * Checks whether all needed database table for the adapter are present.
	 *
	 * Currently we need three tables: 'graphs', 'statements' and 'namespaces'
	 *
	 * @throws \Erfurt\Exception
	 * @return boolean Returns true if all tables are present.
	 */
	private function isSetup() {
		$existingTables = $this->listTables();
		if (is_array($existingTables)) {
			if (!in_array('tx_semantic_info', $existingTables) ||
				!in_array('tx_semantic_graph', $existingTables) ||
				!in_array('tx_semantic_statement', $existingTables) ||
				!in_array('tx_semantic_iri', $existingTables) ||
				!in_array('tx_semantic_literal', $existingTables)) {
				return false;
			} else {
				return true;
			}
		} else {
			throw new \Exception('Determining of database tables failed.');
		}
	}

}

?>
