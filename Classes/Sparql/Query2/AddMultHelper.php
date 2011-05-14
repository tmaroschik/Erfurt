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
abstract class AddMultHelper extends \Erfurt\Sparql\Query2\ContainerHelper {

	const operator = null;
	const invOperator = null;

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
	}

	abstract public function addElement($op, Interfaces\Expression $element);

	/**
	 * get string representation
	 * @return string
	 */
	public function getSparql() {
		$sparql = '';
		$countElements = count($this->elements);
		for ($i = 0; $i < $countElements; ++$i) {
			if ($i != 0 || $this->elements[$i]['op'] == self::invOperator) {
				$sparql .= ' ' . $this->elements[$i]['op'] . ' ';
			}
			$sparql .= $this->elements[$i]['exp']->getSparql();
		}
		if (count($this->elements) > 1) {
			$sparql = '(' . $sparql . ')';
		}
		return $sparql;
	}

	/**
	 *
	 * @param array $elements array of Expression
	 * @return Erfurt_Sparql_Query2_AddMultHelper $this
	 */
	public function setElements($elements) {
		if (!is_array($elements)) {
			throw new \RuntimeException('Argument 1 passed to ' . __CLASS__ . '::setElements : must be an array');
		}
		foreach ($elements as $element) {
			if (!($element['exp'] instanceof Interfaces\Expression) || !isset($element['op'])) {
				throw new \RuntimeException('Argument 1 passed to ' . __CLASS__ . '::setElements : must be an array of arrays consisting of a field "exp" with type Expression and a field "op" containing the operator (+,-,*,/) as string');
			} else {
				$this->addElement($element['op'], $element['exp']);
			}
		}
		return $this; //for chaining
	}
}

?>
