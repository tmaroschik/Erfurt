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
 * the root interface for all constraining expressions
 *
 * @package Semantic
 * @scope prototype
 */
class AdditiveExpression extends AddMultHelper implements Interfaces\AdditiveExpression {
	const operator = '+';
	const invOperator = '-';

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 *
	 * @param <type> $op + or -. operator used to connect to the remaining elements
	 * @param Interfaces\Expression $exp
	 * @return AdditiveExpression $this
	 */
	public function addElement($op, Interfaces\Expression $exp) {
		if ($op == self::operator || $op == self::invOperator) {
			//a hack to convert a expression that is added first when added with a minus as operator - would be omitted otherwise. maybe not usefull?!
			if ($op == self::invOperator && count($this->elements) == 0) {
				if ($exp instanceof RDFLiteral) {
					$exp->setValue(self::invOperator . $exp->getValue());
				} else {
					if ($exp instanceof NumericLiteral) {
						$exp->setValue((-1) * $exp->getValue());
					} else {
						$exp = new UnaryExpressionMinus($exp);
					}
				}
			}
			$this->elements[] = array('op' => $op, 'exp' => $exp);
			$exp->addParent($this);
		} else {
			throw new \RuntimeException('Argument 1 passed to UnaryExpression::__construct must be AdditiveExpression::minus or AdditiveExpression::plus');
		}
		return $this; //for chaining
	}
}

?>