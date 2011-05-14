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
 * @package Semantic
 * @scope prototype
 */
class AndOrHelper extends ContainerHelper implements Interfaces\ConditionalOrExpression {

	protected $conjunction;

	/**
	 *
	 * @param array $elements array of Interfaces\Expression
	 */
	public function __construct($elements = array()) {
		parent::__construct();
		if (!empty($elements)) {
			$this->setElements($elements);
		}
	}

	/**
	 * @param Expression
	 * @return AndOrHelper
	 */
	public function addElement($element) {
		if (is_string($element)) {
			$element = new RDFLiteral($element);
		}
		if (!($element instanceof Interfaces\Expression)) {
			throw new \RuntimeException('Argument 2 passed to RDFLiteral::__construct must be an instance of Expression or string, instance of ' . typeHelper($element) . ' given');
		}
		$element->addParent($this);
		$this->elements[] = $element;
		return $this; //for chaining
	}

	/**
	 * get the string-representation of this expression
	 * @return string
	 */
	public function getSparql() {
		$sparql = '';
		$countElements = count($this->elements);
		for ($i = 0; $i < $countElements; ++$i) {
			$sparql .= $this->elements[$i]->getSparql();
			if (isset($this->elements[$i + 1])) {
				$sparql .= ' ' . $this->conjunction . ' ';
			}
		}
		if (count($this->elements) > 1) {
			$sparql = '(' . $sparql . ')';
		}
		return $sparql;
	}

	/**
	 * set an array of alements at once
	 * @param array $elements of Interfaces\Expression
	 * @return AndOrHelper
	 */
	public function setElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to ' . __CLASS__ . '::setElements : must be an array');
		}
		foreach ($elements as $element) {
			if (!($element instanceof Interfaces\Expression)) {
				throw new \RuntimeException('Argument 1 passed to ' . __CLASS__ . '::setElements : must be an array of Expression');
			}
		}
		$this->elements = $elements;
		return $this; //for chaining
	}
}

?>
