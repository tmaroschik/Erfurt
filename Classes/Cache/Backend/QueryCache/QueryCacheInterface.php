<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Cache\Backend\QueryCache;
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
 * Interface Definition
 * @author         Michael Martin <martin@informatik.uni-leipzig.de>
 * @package        erfurt
 * @subpackage     cache
 * @copyright      Copyright (c) 2009 {@link http://aksw.org aksw}
 * @license        http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 * @link           http://code.google.com/p/ontowiki/
 * @version        0.1
 * @todo           some more methods needed
 */
interface QueryCacheInterface {


	/**
	 *  saving a Query as String, its result and some more needed information
	 * @access	 public
	 * @param	  string	$queryId	  Its a hash of the QueryString
	 * @param	  string	$queryString  SPARQL Query as String
	 * @param	  array   $graphUris	  An Array of graphUris extracted from the From and FromNamed Clause of the SPARQL Query
	 * @param	  array   $triplePatterns An Array of TriplePatterns extracted from the Where Clause of the SPARQL Query
	 * @param	  string  $queryResult	the QueryResult
	 * @param	  float   $duration	   the duration of the originally executed Query in seconds, microseconds
	 * @return	 boolean $result		 returns the state of the saveprocess
	 */
	public function save($queryId, $queryString, $graphIris, $triplePatterns, $queryResult, $duration = 0);


	/**
	 *  saving a Query as String, its result and some more needed information
	 * @access	 public
	 * @param	  string		  $queryId		Its a hash of the QueryString
	 * @return	 string/boolean  $result		 If a result was found it returns the result or if not then returns false
	 */
	public function load($queryId);


	/**
	 *  increments the count of a query result (needed for logging)
	 * @access	   public
	 * @param		string	$queryId		Its a hash of the QueryString
	 */
	public function incrementHitCounter($queryId);

	/**
	 *  increments the count of a query result (needed for logging)
	 * @access	   public
	 * @param		string	$queryId		Its a hash of the QueryString
	 */
	public function incrementInvalidationCounter($queryId);


	/**
	 *  invalidating a cached Query Result
	 * @access	 public
	 * @param	  array   $statements	 an Array of statements in the form $statements[$subject][$predicate] = $object
	 * @return	 int	 $count		  count of the affected cached queries
	 */
	public function invalidate($graphIri, $statements = array());

	/**
	 *  invalidating all cached Query Results according to a given ModelIri
	 * @access	 public
	 * @param	  string  $graphIri	   A ModelIri
	 * @return	 int	 $count		  count of the affected cached queries
	 */
	public function invalidateWithModelIri($graphIri);

	/**
	 *  invalidating all cached ObjectKeys according to a query list of QueryIds
	 * @access	 public
	 * @param	  string  $objectKeys	 An array of Objectkeys
	 */
	public function invalidateObjectKeys($queryIds = array());


	/**
	 *  deleting all cachedResults
	 * @access	 public
	 * @return	 boolean		 $state		  true / false
	 */
	public function invalidateAll();


	/**
	 *  deleting the initially created cacheStructure
	 * @access	 public
	 * @return	 boolean		 $state		  true / false
	 */
	public function uninstall();


	/**
	 *  check if a QueryResult is cached yet
	 * @access	 public
	 * @param	  string		  $queryId		Its a hash of the QueryString
	 * @return	 boolean		 $state		  true / false
	 */
	public function exists($queryId);


	/**
	 *  check the existing cacheVersion
	 * @access	 public
	 * @return	 boolean		 $state		  true / false
	 */
	public function checkCacheVersion();


	/**
	 *  creating the initially needed cacheStructure
	 * @access	 public
	 * @return	 boolean		 $state		  true / false
	 */
	public function createCacheStructure();


	/**
	 *  getObjectKeys from ObjectCache
	 * @access	 public
	 * @return	 array		 $objectKeys
	 */
	public function getObjectKeys();

	/**
	 *  getmaterializedViews
	 * @access	 public
	 * @return	 array		   $array of tableNames
	 */
	public function getMaterializedViews();

}

?>