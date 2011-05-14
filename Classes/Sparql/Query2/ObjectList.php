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
 * Erfurt Sparql Query2 - ObjectList
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: ObjectList.php 4181 2009-09-22 15:46:24Z jonas.brekle@gmail.com $
 */
class ObjectList extends ContainerHelper implements Interfaces\ObjectList {

	/**
	 * @param array array of GraphNode
	 */
	public function __construct($objects) {
		$this->setElements($objects);
		parent::__construct();
	}

	/**
	 * addElement
	 * @param Interfaces\GraphNode $element
	 * @return Collection $this
	 */
	public function addElement(Interfaces\GraphNode $element) {
		$this->elements[] = $element;
		return $this;
	}

	/**
	 * setElement
	 * @param int $i
	 * @param Interfaces\GraphNode $element
	 * @return Collection $this
	 */
	public function setElement($i, Interfaces\GraphNode $element) {
		$this->elements[$i] = $element;
		return $this;
	}

	public function setElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to ObjectList::setElements must be an array of GraphNode\'s, instance of ' . typeHelper($elements) . ' given');
		} else {
			foreach ($elements as $object) {
				if (!($object instanceof Interfaces\GraphNode)) {
					throw new \RuntimeException('Argument 1 passed to ObjectList::setElements must be an array of GraphNode\'s, instance of ' . typeHelper($object) . ' given');
				} else {
					$this->addElement($object);
				}
			}
		}
	}

	public function getVars() {
		$ret = array();
		foreach ($this->elements as $element) {
			if ($element instanceof Variable) {
				$ret[] = $element;
			}
		}
		return $ret;
	}

	//merge?
	public function getNumVars() {
		$ret = 0;
		foreach ($this->elements as $element) {
			if ($element instanceof Variable) {
				$ret++;
			}
		}
		return $ret;
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "obj1, obj2, obj3"
	 * @return string
	 */
	public function getSparql() {
		return implode(', ', $this->elements);
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>