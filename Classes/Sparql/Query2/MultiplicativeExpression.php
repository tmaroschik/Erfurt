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
class MultiplicativeExpression extends AddMultHelper implements Interfaces\MultiplicativeExpression {
	const operator = '*';
	const invOperator = '/';

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 *
	 * @param string $op * or / operator used to connect to the remaining elements
	 * @param Interfaces\Expression $exp
	 * @return MultiplicativeExpression $this
	 */
	public function addElement($op, Interfaces\Expression $exp) {
		if ($op == self::operator || $op == self::invOperator) {
			$this->elements[] = array('op' => $op, 'exp' => $exp);
		} else {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_UnaryExpression::__construct must be Erfurt_Sparql_Query2_AdditiveExpression::times or Erfurt_Sparql_Query2_AdditiveExpression::divided');
		}
		$exp->addParent($this);
		return $this; //for chaining
	}

	/**
	 * get string representation
	 * @return string
	 */
	public function getSparql() {
		$sparql = '';
		$countElements = count($this->elements);
		for ($i = 0; $i < $countElements; ++$i) {
			if ($i == 0) {
				if ($this->elements[$i]['op'] == self::invOperator) {
					$sparql .= ' 1' . $this->elements[$i]['op'] . ' '; // => 1/x
				}
			} else {
				$sparql .= ' ' . $this->elements[$i]['op'] . ' ';
			}
			$sparql .= $this->elements[$i]['exp']->getSparql();
		}
		if ($countElements > 1) {
			$sparql = '(' . $sparql . ')';
		}
		return $sparql;
	}
}

?>