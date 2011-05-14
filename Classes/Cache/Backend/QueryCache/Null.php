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
class Null extends Backend {

	public function __construct() {
		// Nothing to do here... It is not neccessary to call the super constructor here!
	}

	// saving a Query Result according to his Query and QueryHash
	public function save($queryId, $queryString, $graphUris, $triplePatterns, $queryResult, $duration = 0, $transactions = array()) {
		return false;
	}

	// loading a Query Result according to his QueryHash
	public function load($queryId) {
		return false;
	}

	public function incrementHitCounter($queryId) {
		return false;
	}

	public function incrementInvalidationCounter($queryId) {
		return false;
	}

	// invalidating a cached Query Result
	public function invalidate($graphUri, $statements = array()) {
		return false;
	}

	public function invalidateWithModelIri($modelIri) {
		return false;
	}

	public function invalidateObjectKeys($queryIds = array()) {
		return false;
	}

	public function invalidateAll() {

		return true;
	}

	public function uninstall() {
		return true;
	}

	// check if a QueryResult is cached yet
	public function exists($queryId) {
		return false;
	}

	// check the existing cacheVersion
	public function checkCacheVersion() {
		return true;
	}

	// creating the inital cacheStructure
	public function createCacheStructure() {
		return true;
	}

	public function getObjectKeys($qids = array()) {
		return array();
	}

	public function getMaterializedViews() {
		return array();
	}

}

?>