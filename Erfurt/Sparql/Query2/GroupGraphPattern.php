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
 * Erfurt_Sparql Query - GroupGraphPattern.
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class GroupGraphPattern extends ContainerHelper {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * addElement
	 * @param GroupGraphPattern|Interfaces\TriplesSameSubject|Filter $member
	 * @return GroupGraphPattern $this
	 */
	public function addElement($member) {
		if (!($member instanceof GroupGraphPattern)
			&& !($member instanceof Interfaces\TriplesSameSubject)
			&& !($member instanceof Filter)) {
			throw new \RuntimeException('Argument 1 passed to GroupGraphPattern::addElement must be an instance of GroupGraphPattern or Triple or Filter, instance of ' . typeHelper($member) . ' given');
		}
		$this->elements[] = $member;
		$member->addParent($this);
		return $this; //for chaining
	}

	/**
	 * getElement
	 * @param int $i
	 * @return GroupGraphPattern|Interfaces\TriplesSameSubject|Filter the choosen element
	 */
	public function getElement($i) {
		return $this->elements[$i];
	}

	public function getElements() {
		return $this->elements;
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj -
	 * should be like "{[Triple] . [Triple] [GroupGraphPattern]}"
	 * @return string
	 */
	public function getSparql() {
		//sort filters to the end - usefull?
		$filters = array();
		$new = array();
		$countElements = count($this->elements);
		for ($i = 0; $i < $countElements; ++$i) {
			if ($this->elements[$i] instanceof Filter) {
				$filters[] = $this->elements[$i];
			} else {
				$new[] = $this->elements[$i];
			}
		}
		$countFilters = count($filters);
		for ($i = 0; $i < $countFilters; ++$i) {
			$new[] = $filters[$i];
		}
		$this->elements = $new;
		//build sparql-string
		$sparql = "{ \n";
		for ($i = 0; $i < $countElements; ++$i) {
			$sparql .= $this->elements[$i]->getSparql();
			//realisation of TriplesBlock
			if ($this->elements[$i] instanceof Interfaces\TriplesSameSubject
				&& isset($this->elements[$i + 1])
				&& $this->elements[$i + 1] instanceof Interfaces\TriplesSameSubject) {
				$sparql .= ' .';
			}
			$sparql .= " \n";
		}
		return $sparql . "} \n";
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * getVars
	 * get all vars used in this pattern (recursive)
	 * @return array array of Var
	 */
	public function getVars() {
		$vars = array();
		foreach ($this->elements as $element) {
			$new = $element->getVars();
			$vars = array_merge($vars, $new);
		}
		return $vars;
	}

	/**
	 * setElement
	 * overwrite a element
	 * @param int $i index of element to overwrite
	 * @param GroupGraphPattern|Interfaces\TriplesSameSubject|Filter $member what to overwrite with
	 * @return GroupGraphPattern $this
	 */
	public function setElement($i, $member) {
		if (!($member instanceof GroupGraphPattern)
			&& !($member instanceof Interfaces\TriplesSameSubject)
			&& !($member instanceof Filter)) {
			throw new \RuntimeException(
				'Argument 2 passed to GroupGraphPattern' .
				'::setElement must be an instance of ' .
				'GroupGraphPattern or ' .
				'IF_TriplesSameSubject or ' .
				'Filter, instance of ' .
				typeHelper($member) . ' given');
		}
		if (!is_int($i)) {
			throw new \RuntimeException('Argument 1 passed to ' .
									   'GroupGraphPattern::setElement must be ' .
									   'an instance of integer, instance of ' . typeHelper($i) . ' given');
		}
		$this->elements[$i] = $member;
		$member->addParent($this);
		return $this; //for chaining
	}

	/**
	 * setElements
	 * overwrite all elements at once with a array of new ones
	 * @param array $elements array of GroupGraphPattern|Interfaces\TriplesSameSubject|Filter
	 * @return GroupGraphPattern $this
	 */
	public function setElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to ' .
									   'GroupGraphPattern::setElements : ' .
									   'must be an array');
		}
		foreach ($elements as $element) {
			if (!($element instanceof GroupGraphPattern)
				&& !($element instanceof Interfaces\TriplesSameSubject)
				&& !($element instanceof Filter)) {
				throw new \RuntimeException('Argument 1 passed to ' .
										   'GroupGraphPattern::setElements : ' .
										   'must be an array of instances of ' .
										   'GroupGraphPattern or ' .
										   'IF_TriplesSameSubject or ' .
										   'Filter');
				return $this; //for chaining
			} else {
				$element->addParent($this);
			}
		}
		$this->elements = $elements;
		return $this; //for chaining
	}

	/**
	 * addElements
	 * add multiple elements at once
	 * @param array $elements array of GroupGraphPattern|Interfaces\TriplesSameSubject|Filter
	 * @return GroupGraphPattern $this
	 */
	public function addElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to ' .
									   'GroupGraphPattern::setElements : ' .
									   'must be an array');
		}
		foreach ($elements as $element) {
			if (!($element instanceof GroupGraphPattern)
				&& !($element instanceof Interfaces\TriplesSameSubject)
				&& !($element instanceof Filter)) {
				throw new \RuntimeException('Argument 1 passed to ' .
										   'GroupGraphPattern::setElements : ' .
										   'must be an array of instances of ' .
										   'GroupGraphPattern or ' .
										   'IF_TriplesSameSubject or ' .
										   'Filter');
			} else {
				//ok
				$element->addParent($this);
			}
		}
		$this->elements = array_merge($this->elements, $elements);
		return $this; //for chaining
	}

	/**
	 * optimize
	 * little demo of optimization:
	 * - delete duplicate elements
	 * - sort by weight (number of vars used)
	 * @return GroupGraphPattern $this
	 */
	public function optimize() {
		//delete duplicates
		$to_remove = array();
		$countElements = count($this->elements);
		for ($i = 0; $i < $countElements; ++$i) {
			for ($j = 0; $j < $countElements; ++$j) {
				if ($i != $j) {
					//compare
					if ($this->elements[$i] === $this->elements[$j]) {
						//identical same object
						$to_remove[] = $this->elements[$i];
						//cant delete one without deleting both - need to copy first
						if ($this->elements[$j] instanceof ContainerHelper) {
							$copy = $this->elements[$j];
							$classname = get_class($this->elements[$j]);
							$this->elements[$j] = new $classname;
							$this->elements[$j]->setElements($copy->getElements());
						} else {
							if ($this->elements[$j] instanceof Triple) {
								$this->elements[$j] =
										new Triple(
											$this->elements[$j]->getS(),
											$this->elements[$j]->getP(),
											$this->elements[$j]->getO()
										);
							} else {
								if (
									$this->elements[$j] instanceof TriplesSameSubject
								) {
									$this->elements[$j] =
											new TriplesSameSubject(
												$this->elements[$j]->getSubject(),
												$this->elements[$j]->getPropList()
											);
								}
							}
						}
						continue;
						//TODO cover all cases - cant be generic?!
					} else {
						if ($this->elements[$i]->equals($this->elements[$j])
							&& $this->elements[$i] != $this->elements[$j]) {
							//if the j of this i-j-pair is already
							//marked for deletion: skip i
							if (!in_array($this->elements[$j], $to_remove)) {
								$to_remove[] = $this->elements[$i];
							}
						}
					}
				}
			}
		}
		foreach ($to_remove as $obj) {
			$this->removeElement($obj);
		}
		//sort triples by weight
		usort($this->elements, array("TriplesSameSubject", "compareWeight"));
		//optimization is done on this level - proceed on deeper level
		foreach ($this->elements as $element) {
			if ($element instanceof GroupGraphPattern) {
				$element->optimize();
			}
		}
		return $this;
	}

	/**
	 * remove all elements that are an OptionalGraphPattern (not recursive)
	 * @return GroupGraphPattern $this
	 */
	public function removeAllOptionals() {
		foreach ($this->elements as $element) {
			if ($element instanceof OptionalGraphPattern) {
				$this->removeElement($element);
			}
		}
		return $this;
	}

	/**
	 * shortcut to instantiate a new triple and add it to the pattern
	 * @param Interfaces\VarOrTerm $s
	 * @param Interfaces\Verb $p
	 * @param Interfaces\ObjectList $o
	 * @return Triple
	 */
	public function addTriple($s, $p, $o) {
		if (is_string($p)) {
			$p = new IriRef($p);
		}
		$triple = new Triple($s, $p, $o);
		$this->addElement($triple);
		return $triple;
	}

	/**
	 * shortcut to instantiate a new filter with a given expressio nand add it to the pattern
	 * @param Constraint $exp
	 * @return Filter
	 */
	public function addFilter($exp) {
		$filter = new Filter($exp);
		$this->addElement($filter);
		return $filter;
	}

}

?>
