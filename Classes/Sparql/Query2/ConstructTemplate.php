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
 * Erfurt Sparql Query2 - ConstructTemplate
 *
 * is like a GroupGraphPattern but you can only add triples
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: ConstructTemplate.php 4203 2009-09-28 13:56:20Z jonas.brekle@gmail.com $
 */

class ConstructTemplate extends GroupGraphPattern {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * addElement
	 * @param Interfaces\TriplesSameSubject $element
	 * @return Erfurt_Sparql_Query2_ConstructTemplate $this
	 */
	public function addElement($element) {
		if (!($element instanceof Interfaces\TriplesSameSubject)) {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_ConstructTemplate::addElement must be an instance of Erfurt_Sparql_Query2_IF_TriplesSameSubject, instance of ' . typeHelper($element) . ' given');
		}
		$this->elements[] = $element;
		$element->addParent($this);
		return $this; //for chaining
	}

	/**
	 * setElement
	 * @param int $i
	 * @param Interfaces\TriplesSameSubject $element
	 * @return Erfurt_Sparql_Query2_ConstructTemplate $this
	 */
	public function setElement($i, $element) {
		if (!is_int($i)) {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_GroupOrUnionGraphPattern::setElement must be an instance of integer, instance of ' . typeHelper($i) . ' given');
		}
		if (!($element instanceof Interfaces\TriplesSameSubject)) {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_GroupOrUnionGraphPattern::addElement must be an instance of Erfurt_Sparql_Query2_IF_TriplesSameSubject');
		}
		$this->elements[$i] = $element;
		$element->addParent($this);
		return $this; //for chaining
	}

	/**
	 * setElements
	 * @param array $elements array of Interfaces\TriplesSameSubject
	 * @return Erfurt_Sparql_Query2_ConstructTemplate $this
	 */
	public function setElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_GroupGraphPattern::setElements : must be an array');
		}
		foreach ($elements as $element) {
			if (!($element instanceof Interfaces\TriplesSameSubject)) {
				throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_GroupOrUnionGraphPattern::setElements : must be an array of instances of Erfurt_Sparql_Query2_IF_TriplesSameSubject');
			} else {
				$element->addParent($this);
			}
		}
		$this->elements = $elements;
		return $this; //for chaining
	}

}

?>
