<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb\Adapter;
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
 * SPARQL engine optimized for databases.
 * Generates SQL statements to directly query the database,
 * letting the database system do all the hard work like
 * selecting, joining, filtering and ordering results.
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @subpackage sparql
 * @author Christian Weiske <cweiske@cweiske.de>
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @license http://www.gnu.org/licenses/lgpl.html LGPL
 * @version	$Id: $
 */
use \Erfurt\Sparql;
use \Erfurt\Sparql\EngineDb;
use \Erfurt\Sparql\EngineDb\ResultRenderer;
use \Erfurt\Sparql\EngineDb\SqlGenerator\Adapter;
class Typo3 {

	/**
	 * Sparql Query object.
	 *
	 * @var Sparql\Query
	 */
	protected $query;

	/**
	 * RDF dataset object.
	 *
	 * @var Dataset
	 */
	//protected $dataset;

	/**
	 *   Database connection object.
	 * @var \t3lib_DB zenddb connection
	 */
	protected $databaseConnection;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 *   Internal ID for our graph model.
	 *   Stored in the database along the statements.
	 *   Can be of different types:
	 *   - array: array of modelIds
	 *   - null: all models
	 *
	 * @var array OR null
	 */
	protected $arModelIds;

	/**
	 *   Prepared SQL statements are stored in here.
	 * @var array
	 */
	protected $arPrepared = null;

	/**
	 *   If the prepared statement is really prepared, or if we just emulate it.
	 * @var boolean
	 */
	protected $bRealPrepared = false;

	/**
	 *   SQL generator instance
	 * @var SparqlEngineDb_SqlGenerator
	 */
	protected $sg = null;

	/**
	 *   Type sorting instance
	 * @var SparqlEngineDb_TypeSorter
	 */
	protected $ts = null;

	/**
	 *   Prepared statments preparator instance
	 * @var SparqlEngineDb_Preparator
	 */
	protected $pr = null;

	protected $arModelIdMapping = null;

	// ------------------------------------------------------------------------
	// --- Magic methods ------------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct($dbConn, $arModelIdMapping = array()) {
		$this->databaseConnection = $dbConn;
		$this->arModelIdMapping = $arModelIdMapping;
	}

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	public function getModelIdMapping() {
		return $this->arModelIdMapping;
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	public function getQuery() {
		return $this->query;
	}

	public function getSqlGenerator() {
		return $this->sg;
	}

	public function getTypeSorter() {
		return $this->ts;
	}

	/**
	 *   Create a prepared statement that can be executed later.
	 *
	 * @param  Dataset	   $dataset	RDF Dataset
	 * @param  Query		 $query	  Parsed SPARQL query
	 *
	 * @return SparqlEngineDb_PreparedStatement Prepared statment that can
	 *		   be execute()d later.
	 */
	/*public function prepare(Dataset $dataset, Query $query)
		{
			$this->query   = $query;
			$this->dataset = $dataset;
			$this->sg = new SparqlEngineDb_SqlGenerator   ($this->query, $this->dbConn, $this->arModelIds);
			$this->rc = new SparqlEngineDb_ResultConverter($this->query, $this->sg, $this);
			$this->ts = new SparqlEngineDb_TypeSorter     ($this->query, $this->dbConn);
			$this->pr = new SparqlEngineDb_Preparator     ($this->query, $this->dbConn);
			$this->arPrepared = $this->sg->createSql();
			$this->ts->setData($this->sg);
			if ($this->ts->willBeDataDependent()) {
				$this->bRealPrepared = false;
			} else {
				$this->bRealPrepared     = true;
				list($strSelect, $strFrom, $strWhere) = $this->arPrepared;
				$this->arPreparedQueries = $this->ts->getOrderifiedSqls(
					$strSelect,
					$strFrom,
					$strWhere
				);
				$this->arDbStatements    = $this->pr->prepareInDb(
					$this->arPreparedQueries,
					$this->sg->getPlaceholders()
				);
			}
			return new SparqlEngineDb_PreparedStatement(
				$this
			);
		}*/

	/**
	 *   Execute a prepared statement by filling it with variables
	 *
	 * @param array $arVariables   Array with (variable name => value) pairs
	 * @param string $resultform   Which form the result should have
	 *
	 * @return mixed   Result according to $resultform
	 */
	/*public function execute($arVariables, $resultform = false)
		{
			if ($this->arPrepared === null) {
				throw new Exception('You need to prepare() the query first.');
			}
			if ($this->bRealPrepared) {
				return
					SparqlEngineDb_ResultConverter::convertFromDbResults(
						$this->pr->execute(
							$this->arDbStatements,
							$arVariables
						),
						$this,
						$resultform
					);
			} else {
				list($strSelect, $strFrom, $strWhere) = $this->arPrepared;
				return SparqlEngineDb_ResultConverter::convertFromDbResults(
					$this->_queryMultiple(
						$this->ts->getOrderifiedSqls(
							$strSelect,
							$strFrom,
							$this->pr->replacePlaceholdersWithVariables(
								$strWhere,
								$this->sg->getPlaceholders(),
								$arVariables
							)
						)
					),
					$this,
					$resultform
				);
			}
		}*/

	/**
	 * Query the database with the given SPARQL query.
	 *
	 * @param Erfurt_SparqlQuery $query Parsed SPARQL query.
	 * @param string $resultform Result form. If set to 'xml' the result will be
	 * SPARQL Query Results XML Format as described in @link http://www.w3.org/TR/rdf-sparql-XMLres/.
	 *
	 * @return array/string  array of triple arrays, or XML.
	 * Format depends on $resultform parameter.
	 */
	public function queryModel(Sparql\Query $query, $resultform = 'plain') {
		$this->query = $query;
		$qsimp = $this->objectManager->create('\Erfurt\Sparql\EngineDb\QuerySimplifier');
		$qsimp->simplify($this->query);
		$queryOptimizer = $this->objectManager->create('\Erfurt\Sparql\EngineDb\QueryOptimizer', $this);
		$result = $queryOptimizer->optimize($this->query);
		if ($result instanceof \Erfurt\Sparql\Query) {
			$this->query = $result;
		}
		$resultform = strtolower($resultform);
		switch ($resultform) {
			case 'xml':
				$rc = $this->objectManager->create('\Erfurt\Sparql\EngineDb\ResultRenderer\Xml');
				break;
			//throw new Erfurt_Exception('XML result format not supported yet.');
			//$this->rc = new ResultRenderer\RapZendDb_Xml();
			//break;
			case 'extended':
				$rc = $this->objectManager->create('\Erfurt\Sparql\EngineDb\ResultRenderer\Extended');
				break;
			case 'json':
				$rc = $this->objectManager->create('\Erfurt\Sparql\EngineDb\ResultRenderer\Json');
				break;
			case 'plain':
			default:
				$rc = $this->objectManager->create('\Erfurt\Sparql\EngineDb\ResultRenderer\Plain');
		}
		if (is_array($result)) {
			$result = $rc->convertFromDbResults($result['data'], $this->query, $this, $result['vars']);
			return $result;
		}
		$this->sg = $this->objectManager->create('\Erfurt\Sparql\EngineDb\SqlGenerator\Adapter\Typo3', $this->query, $this->arModelIdMapping);
		$this->ts = $this->objectManager->create('\Erfurt\Sparql\EngineDb\TypeSorter', $this->query, $this);
		$this->_setOptions();
		$arSqls = $this->sg->createSql();
		#var_dump($arSqls);exit;
		$this->ts->setData($this->sg);
		return $rc->convertFromDbResults($this->queryMultiple($this->ts->getOrderifiedSqls($arSqls)),
										 $this->query, $this, $this->sg->arVarAssignments);
	}

	public function execSelectQuery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		return $this->databaseConnection->exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
	}

	public function sqlQuery($sql) {
		$data = array();
		$res = $this->databaseConnection->sql_query($sql);
		if ($res) {
			while($row = $this->databaseConnection->sql_fetch_assoc($res)) {
				$data[] = $row;
			}
		}
		return $data;
	}

	// ------------------------------------------------------------------------
	// --- Protected methods --------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Sends the sql to the database and returns the results.
	 *
	 * @param array $arSql Array that gets a SQL query string once imploded.
	 *
	 * @return mixed
	 */
	protected function _queryDb($arSql, $nOffset, $nLimit) {
		$strSql = EngineDb\SqlMerger::getSelect($this->query, $arSql);
		#var_dump($nLimit, $nOffset);
		#echo $strSql;
		if ($strSql === '()') {
			return array();
		}
		if ($nLimit === null && $nOffset == 0) {
			$ret = $this->databaseConnection->sql_query($strSql);
		} else {
			if ($nLimit === null) {
				$ret = $this->databaseConnection->sql_query($strSql . ' LIMIT ' . $nOffset . ', 18446744073709551615');
			} else {
				$ret = $this->databaseConnection->sql_query($strSql . ' LIMIT ' . $nOffset . ', ' . $nLimit);
			}
		}
		$result = array();
		while ($row = $this->databaseConnection->sql_fetch_assoc($ret)) {
			$result[] = $row;
		}
		return $result;
	}

	/**
	 * Executes multiple SQL queries and returns an array of results.
	 *
	 * @param array $arSqls Array of SQL queries.
	 * @return array Array of query results.
	 */
	protected function queryMultiple($arSqls) {
		$arSM = $this->query->getSolutionModifier();
		if ($arSM['limit'] === null && $arSM['offset'] === null) {
			$nOffset = 0;
			$nLimit = null;
			$nSql = 0;
		} else {
			$offsetter = new EngineDb\Offsetter($this, $this->query);
			list($nSql, $nOffset) = $offsetter->determineOffset($arSqls);
			$nLimit = $arSM['limit'];
		}
		$nCount = 0;
		$arResults = array();
		foreach ($arSqls as $nId => $arSql) {
			if ($nId < $nSql) {
				continue;
			}
			if ($nLimit != null) {
				$nCurrentLimit = $nLimit - $nCount;
			} else {
				$nCurrentLimit = null;
			}
			$dbResult = $this->_queryDb($arSql, $nOffset, $nCurrentLimit);
			$nCount += count($dbResult);
			$arResults[] = $dbResult;
			$nOffset = 0;
			if ($nLimit !== null && $nCount >= $nLimit) {
				break;
			}
		}
		return $arResults;
	}

	/**
	 * Set options to subobjects like SqlGenerator
	 */
	protected function _setOptions() {
		// allow changing the statements' table name
		//if (isset($GLOBALS['RAP']['conf']['database']['tblStatements'])) {
		//    $this->sg->setStatementsTable(
		//        $GLOBALS['RAP']['conf']['database']['tblStatements']
		//    );
		//}
	}

}

?>