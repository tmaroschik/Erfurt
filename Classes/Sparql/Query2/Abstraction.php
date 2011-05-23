<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;
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
 * Erfurt_Sparql Query - Abstraction.
 *
 * an Abstraction for Sparql-Queries
 *
 * under construction
 *
 * @package Semantic
 * @scope prototype
 */
use \Erfurt\Sparql\Query2;
class Abstraction {

	protected $query;
	protected $startNode;

	/**
	 * @param IriRef|string|null $class
	 * @param bool $withChilds wether to include subclasses of $class
	 * @param string|null $varName the var-name to be used for instances of this class
	 * @param string $member_predicate the predicate that stands between the class und its instances (mostly rdf:type)
	 */
	public function __construct($class = null, $withChilds = true, $varName = null, $member_predicate = Erfurt\Vocabulary\Rdf::TYPE) {
		$this->query = new Query2();
		if ($class != null && !($class instanceof IriRef)) {
			if (is_string($class)) {
				$class = new IriRef($class);
			}
			if (!($class instanceof IriRef)) {
				throw new \RuntimeException("Argument 3 passed to Abstraction::addNode must be an instance of IriRef or string, instance of " . typeHelper($class) . " given");
			}
		}
		//add startnode
		$this->startNode = new Abstraction\ClassNode($class, $member_predicate, $this->query, $varName, $withChilds);
	}

	public function __clone() {
	}

	/**
	 * redirect method calls to the query object
	 * @param string $name
	 * @param array $params
	 */
	public function __call($name, $params) {
		if ($name != "getWhere" && $name != "addTriple") { //you shall not mess with the abstraction concept
			if (method_exists($this->query, $name)) {
				$ret = call_user_func_array(array($this->query, $name), $params);
			} elseif (method_exists($this->startNode, $name)) {
				$ret = call_user_func_array(array($this->startNode, $name), $params);
			} else {
				throw new \RuntimeException("Query2_Abstraction: method $name does not exists");
			}
			if ($this->query->equals($ret) || $this->startNode->equals($ret)) {
				return $this;
			}
			else
			{
				return $ret;
			}
		} else {
			throw new \RuntimeException("Query2_Abstraction: method $name not allowed");
		}
	}

	/**
	 * addNode
	 *
	 * add a node to the tree of class-nodes
	 *
	 * @param Abstraction\ClassNode $sourceNode where in the tree of nodes should the new node be added
	 * @param IriRef|string $LinkPredicate over which predicate you want to link
	 * @param IriRef|string|null $targetClass can be used to link to a subset of all possible
	 * @param bool $withChilds wether to include subclasses of $class
	 * @param string|null $varName the var-name to be used for instances of this class
	 * @param string $member_predicate the predicate that stands between the class und its instances (mostly rdf:type)
	 * @return Abstraction\ClassNode the new node
	 */
	public function addNode(Abstraction\ClassNode $sourceNode, $LinkPredicate, $targetClass = null, $withChilds = true, $varName = null, $member_predicate = Erfurt\Vocabulary\Rdf::TYPE) {
		// hack for overloaded functioncalls
		if (!($LinkPredicate instanceof IriRef)) {
			if (is_string($LinkPredicate)) {
				$LinkPredicate = new IriRef($LinkPredicate);
			} else {
				throw new \RuntimeException("Argument 2 passed to Abstraction::addNode must be an instance of IriRef or string, instance of " . typeHelper($LinkPredicate) . " given");
			}
		}
		if ($targetClass == null) {
			//TODO: find type of referenced objects
		}
		//add link from source node to new node
		$newnode = new Abstraction\ClassNode($targetClass, $member_predicate, $this->query, $varName, $withChilds);
		$sourceNode->addLink($LinkPredicate, $newnode);
		return $newnode; //for chaining

	}

	/**
	 * getSparql
	 * build a query string
	 * @return	string	query
	 */
	public function getSparql() {
		return $this->query->getSparql();
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * getStartNode
	 * @return Abstraction\ClassNode the root-node
	 */
	public function getStartNode() {
		return $this->startNode;
	}

	/**
	 * getRealQuery
	 * @return Erfurt_Sparql_Query2 the query that is handled inside - but only a clone to prevent external manipulation that wont fit in the scheme
	 */
	public function getRealQuery() {
		return clone $this->query;
	}

}

?>
