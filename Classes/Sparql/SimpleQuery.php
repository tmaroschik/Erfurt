<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql;
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
 * This class models a SPARQL query that can be used within an application in order to make
 * it easier e.g. to set different parts of a query independently.
 *
 * @package Semantic
 * @scope prototype
 */
class SimpleQuery {

	/**
	 * @var int
	 */
	protected $prologuePart = null;

	/**
	 * @var int
	 */
	protected $from = array();

	/**
	 * @var int
	 */
	protected $fromNamed = array();

	/**
	 * @var int
	 */
	protected $wherePart = null;

	/**
	 * @var int
	 */
	protected $orderClause = null;

	/**
	 * @var int
	 */
	protected $limit = null;

	/**
	 * @var int
	 */
	protected $offset = null;

	public function __toString() {
		$queryString = $this->prologuePart . PHP_EOL;
		foreach (array_unique($this->from) as $from) {
			$queryString .= 'FROM <' . $from . '>' . PHP_EOL;
		}
		foreach (array_unique($this->fromNamed) as $fromNamed) {
			$queryString .= 'FROM NAMED <' . $fromNamed . '>' . PHP_EOL;
		}
		$queryString .= $this->wherePart . ' ';
		if ($this->orderClause !== null) {
			$queryString .= 'ORDER BY ' . $this->orderClause . PHP_EOL;
		}
		if ($this->limit !== null) {
			$queryString .= 'LIMIT ' . $this->limit . PHP_EOL;
		}
		if ($this->offset !== null) {
			$queryString .= 'OFFSET ' . $this->offset . PHP_EOL;
		}
		return $queryString;
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	public function addFrom($iri) {
		$this->from[] = $iri;
		return $this;
	}

	public function addFromNamed($iri) {
		$this->fromNamed[] = $iri;
		return $this;
	}

	public function getFrom() {
		return $this->from;
	}

	public function getFromNamed() {
		return $this->fromNamed;
	}

	public function getLimit() {
		return $this->limit;
	}

	public function getOffset() {
		return $this->offset;
	}

	public function getProloguePart() {
		return $this->prologuePart;
	}

	public function resetInstance() {
		$this->prologuePart = null;
		$this->from = array();
		$this->fromNamed = array();
		$this->wherePart = null;
		$this->orderClause = null;
		$this->limit = null;
		$this->offset = null;
		return $this;
	}

	public function setFrom(array $newFromArray) {
		$this->from = $newFromArray;
		return $this;
	}

	public function setFromNamed(array $newFromNamedArray) {
		$this->fromNamed = $newFromNamedArray;
		return $this;
	}

	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}

	public function setOffset($offset) {
		$this->offset = $offset;
		return $this;
	}

	public function setOrderClause($orderString) {
		$this->orderClause = $orderString;
		return $this;
	}

	public function setProloguePart($prologueString) {
		$this->prologuePart = $prologueString;
		return $this;
	}

	public function setWherePart($whereString) {
		if (stripos($whereString, 'where') !== false) {
			$this->wherePart = $whereString;
		} else {
			$this->wherePart = 'WHERE' . $whereString;
		}
		return $this;
	}

}

?>