<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
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
 * This is a factory for simple queries
 *
 * @package Semantic
 * @scope prototype
 */
class SimpleQueryFactory implements \Erfurt\Singleton {

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 *
	 * @param string $queryString
	 */
	public function buildFromQueryString($queryString) {
		$parts = array(
			'prologue' => array(),
			'from' => array(),
			'from_named' => array(),
			'where' => array(),
			'order' => array(),
			'limit' => array(),
			'offset' => array()
		);
		$tokens = array(
			'prologue' => '/(BASE.*\s)?(PREFIX.*\s)*(\s*ASK|\s*COUNT|(\s*SELECT\s+(DISTINCT\s+)?)(\?\w+\s+|\*)+)/si',
			'from' => '/FROM\s+<(.+?)>/i',
			'from_named' => '/FROM\s+NAMED\s+<(.+?)>/i',
			'where' => '/(WHERE\s+)?\{.*\}/si',
			'order' => '/ORDER\s+BY\s+(.+\))+/i',
			'limit' => '/LIMIT\s+(\d+)/i',
			'offset' => '/OFFSET\s+(\d+)/i'
		);
		foreach ($tokens as $key => $pattern) {
			preg_match_all($pattern, $queryString, $parts[$key]);
		}
		$queryObject = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		if (isset($parts['prologue'][0][0])) {
			$queryObject->setProloguePart($parts['prologue'][0][0]); // whole match
		}
		if (isset($parts['from'][1][0])) {
			$queryObject->setFrom($parts['from'][1]);
		}
		if (isset($parts['from_named'][1][0])) {
			$queryObject->setFromNamed($parts['from_named'][1]);
		}
		if (isset($parts['where'][0][0])) {
			$queryObject->setWherePart($parts['where'][0][0]);
		}
		if (isset($parts['order'][1][0])) {
			$queryObject->setOrderClause($parts['order'][1][0]);
		}
		if (isset($parts['limit'][1][0])) {
			$queryObject->setLimit($parts['limit'][1][0]);
		}
		if (isset($parts['offset'][1][0])) {
			$queryObject->setOffset($parts['offset'][1][0]);
		}
		return $queryObject;
	}

}

?>
