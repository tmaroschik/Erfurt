<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2\Abstraction;
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
//under construction
use \Erfurt\Sparql\Query2;
use \Erfurt\Sparql\Query2\Interfaces;
class ClassNode {

	protected $shownproperties = array();
	protected $filters = array();

	protected $type;
	protected $classVar;

	protected $outgoinglinks;
	protected $query;

	public function __construct(Query2\IriRef $type, $member_predicate = EF_RDF_TYPE, Query2 $query, $varName = null, $withChilds = true) {
		$this->query = $query;
		if ($member_predicate == EF_RDF_TYPE) {
			$type = new RDFSClass($type, $withChilds);
			$member_predicate = new Query2\A();
		} else
		{
			$type = new NoClass($type, $member_predicate);
		}
		if (is_string($member_predicate)) {
			$member_predicate = new Query2\IriRef($member_predicate);
		}
		$this->type = $type;
		if ($varName == null) {
			$this->classVar = new Query2\Variable($type->getIri());
		}
		else
		{
			$this->classVar = new Query2\Variable($varName);
		}
		if (!($member_predicate instanceof Interfaces\Verb)) {
			throw new \RuntimeException('Argument 2 passed to ClassNode::__construct must be an instance of Query2\IriRef or string instance of ' . typeHelper($member_predicate) . ' given');
		}
		$subclasses = $type->getSubclasses();
		if (count($subclasses) > 1) { //the class itself is somehow included in the subclasses...
			$typeVar = new Query2\Variable($type->getIri());
			$typePart = new Query2\Triple($this->classVar, $member_predicate, $typeVar);
			$this->query->getWhere()->addElement($typePart);
			$or = new Query2\ConditionalOrExpression();
			foreach ($subclasses as $subclass) {
				$or->addElement(new Query2\SameTerm($typeVar, $subclass));
			}
			$filter = new Query2\Filter($or);
			$this->query->getWhere()->addElement($filter);
		} else {
			$typePart = new Query2\Triple($this->classVar, $member_predicate, $type->getIri());
			$this->query->getWhere()->addElement($typePart);
		}
	}

	public function __clone() {
	}

	/**
	 * addShownProperty
	 *
	 * adds a triple <classVar> $predicate ?newPropertyVar
	 * and adds ?newPropertyVar as projectionvar
	 * and keep track of that
	 *
	 * @param Query2\IriRef|string $predicate
	 * @param string|null $name
	 * @param bool $inverse
	 * @return ClassNode $this
	 */
	public function addShownProperty($predicate, $name = null, $inverse = false) {
		$ret = self::addShownPropertyHelper($this->query, $this->classVar, $predicate, $name, $inverse);
		$this->shownproperties[] = array('optional' => $ret['optional'], 'var' => $ret['var']);
		return $this; //for chaining
	}

	/**
	 * addShownPropertyHelper
	 *
	 * adds a triple <classVar> $predicate ?newPropertyVar
	 * and adds ?newPropertyVar as projectionvar
	 * and keep track of that
	 *
	 * @param Erfurt_Sparql_Query2 $query
	 * @param Variable $resVar
	 * @param Query2\IriRef|string $predicate
	 * @param string|null $name
	 * @param bool $inverse
	 * @return array array('optional' => $optionalpart, 'var' => $var);
	 */
	public static function addShownPropertyHelper(Query2 $query, Query2\Variable $resVar, $predicate, $name = null, $inverse = false) {
		if (is_string($predicate)) {
			$predicate = new Query2\IriRef($predicate);
		}
		if (!($predicate instanceof Query2\IriRef)) {
			throw new \RuntimeException('Argument 3 passed to ClassNode::addShownPropertyHelper must be an instance of Query2\IriRef, instance of ' . typeHelper($predicate) . ' given');
		}
		if (!is_string($name)) {
			throw new \RuntimeException('Argument 4 passed to ClassNode::addShownPropertyHelper must be an instance of string, instance of ' . typeHelper($name) . ' given');
		}
		if (!is_bool($inverse)) {
			throw new \RuntimeException('Argument 5 passed to ClassNode::addShownPropertyHelper must be an instance of bool, instance of ' . typeHelper($inverse) . ' given (' . $inverse . ')');
		}
		$optionalpart = new Query2\OptionalGraphPattern();
		if ($name == null) {
			$var = new Query2\Variable($predicate);
		}
		else
		{
			$var = new Query2\Variable($name);
		}
		if (!$inverse) {
			$triple = new Query2\Triple($resVar, $predicate, $var);
		}
		else
		{
			$triple = new Query2\Triple($var, $predicate, $resVar);
		}
		$optionalpart->addElement($triple);
		$query->getWhere()->addElement($optionalpart);
		/* filtered now in php
				  $filter = new Query2\Filter(
					new Query2\UnaryExpressionNot(
						new Query2\isBlank($var)
					)
				);*/
		//$optionalpart->addElement($filter);
		$query->addProjectionVar($var);
		return array('optional' => $optionalpart, 'var' => $var, 'filter' => null);
	}

	public function addLink($predicate, ClassNode $target) {
		if (is_string($predicate)) {
			$predicate = new Query2\IriRef($predicate);
		}
		if (!($predicate instanceof Query2\IriRef)) {
			throw new \RuntimeException('Argument 1 passed to ClassNode::addFilter must be an instance of Query2\IriRef instance of ' . typeHelper($predicate) . ' given');
		}
		$this->outgoinglinks[] = new Link($predicate, $target);
		$this->query->getWhere()
				->addElement(new Query2\Triple($this->classVar, $predicate, new Query2\Variable($target
				->getClass()->getIri())));
		return $this; //for chaining
	}

	public function addFilter($predicate, $type, $value) {
		$this->filters[] = self::addFilterHelper($this->_query, $this->classVar, $predicate, $type, $value);
		return $this;
	}

	public static function addFilterHelper(Query2 $query, Query2\Variable $resVar, $predicate, $type, $value) {
		if (is_string($predicate)) {
			$predicate = new Query2\IriRef($predicate);
		}
		if (!($predicate instanceof Query2\IriRef)) {
			throw new \RuntimeException('Argument 3 passed to ClassNode::addFilterHelper must be an instance of Query2\IriRef instance of ' . typeHelper($predicate) . ' given');
		}
		switch ($type) {
			case 'contains':
				$propVar = new Query2\Variable($predicate);
				$filteringTriple = new Query2\Triple($resVar, $predicate, $propVar);
				$filterExp = new Query2\Filter(new Query2\Regex($propVar, new Query2\RDFLiteral($value)));
				$query->getWhere()->addElement($filteringTriple);
				$query->getWhere()->addElement($filterExp);
				return array($filteringTriple, $filterExp);
				break;
			case 'equals':
				$filteringTriple = new Query2\Triple($resVar, $predicate, new Query2\RDFLiteral($value));
				$query->getWhere()->addElement($filteringTriple);
				return $filteringTriple;
				break;
			default:
				throw new \RuntimeException('Argument 4 passed to ClassNode::addFilterHelper must be "equals" or "contains", ' . $type . ' given');
				break;
		}
		return null;
	}

	public function clearShownProperties() {
		foreach ($this->shownproperties as $pair) {
			$this->query->removeProjectionVar($pair['var']);
			$pair['optional']->remove();
		}
		return $this;
	}

	public function clearFilter() {
		foreach ($this->filters as $filter) {
			if (is_array($filter)) {
				foreach ($filter as $tripleOrFilter) {
					$tripleOrFilter->remove();
				}
			} else {
				$filter->remove();
			}
		}
		return $this->type;
	}

	public function clearAll() {
		$this->clearShownProperties();
		$this->clearFilter();
		return $this;
	}

	public function getClass() {
		return $this->type;
	}

	public function getClassVar() {
		return $this->classVar;
	}

}

?>