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
 * Erfurt Sparql Query2 - GroupOrUnionGraphPattern.
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class GroupOrUnionGraphPattern extends GroupGraphPattern {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "{[Triples...]} UNION {[Triples...]}"
	 * @return string
	 */
	public function getSparql() {
		$sparql = '';
		$countElements = count($this->elements);
		for ($i = 0; $i < $countElements; ++$i) {
			if ($this->elements[$i] instanceof OptionalGraphPattern) {
				$sparql .= ' { ';
			}
			$sparql .= $this->elements[$i]->getSparql();
			if ($this->elements[$i] instanceof OptionalGraphPattern) {
				$sparql .= ' } ';
			}
			if ($i < (count($this->elements) - 1)) {
				$sparql .= ' UNION ';
			}
		}
		return $sparql;
	}

	/**
	 * addElement
	 * @param GroupGraphPattern $element
	 * @return GroupOrUnionGraphPattern $this
	 */
	public function addElement($element) {
		if (!($element instanceof GroupGraphPattern)) {
			throw new \RuntimeException('Argument 1 passed to GroupOrUnionGraphPattern::addElement must be an instance of GroupGraphPattern, instance of ' . typeHelper($element) . ' given');
		}
		$this->elements[] = $element;
		$element->addParent($this);
		return $this; //for chaining
	}

	/**
	 * setElement
	 * @param int $i
	 * @param GroupGraphPattern $element
	 * @return GroupOrUnionGraphPattern $this
	 */
	public function setElement($i, $element) {
		if (!($element instanceof GroupGraphPattern)) {
			throw new \RuntimeException('Argument 2 passed to GroupOrUnionGraphPattern::setElement must be an instance of GroupGraphPattern, instance of ' . typeHelper($element) . ' given');
		}
		$this->elements[$i] = $element;
		$element->addParent($this);
		return $this; //for chaining
	}

	/**
	 * setElements
	 * overwrite all elements at once with a array of new ones
	 * @param array $elements array of GroupGraphPattern
	 * @return GroupGraphPattern $this
	 */
	public function setElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to GroupGraphPattern::setElements : must be an array');
		}
		foreach ($elements as $element) {
			if (!($element instanceof GroupGraphPattern)) {
				throw new \RuntimeException('Argument 1 passed to GroupOrUnionGraphPattern::setElements : must be an array of instances of GroupGraphPattern');
			} else {
				$element->addParent($this);
			}
		}
		$this->elements = $elements;
		return $this; //for chaining
	}

}

?>
