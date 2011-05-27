<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 2 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/copyleft/gpl.html.                      *
 *                                                                        */
/**
 * wrapps an expression in brackets
 *
 * @package Semantic
 * @scope prototype
 */
class BrackettedExpression extends ElementHelper implements Interfaces\PrimaryExpression {

	protected $expression;

	/**
	 *
	 * @param Interfaces\Expression $expression
	 */
	public function __construct(Interfaces\Expression $expression) {
		$this->expression = $expression;
		parent::__construct();
	}

	/**
	 *
	 * @param Interfaces\Expression $expression
	 * @return BrackettedExpression
	 */
	public function setExpression(Interfaces\Expression $expression) {
		$this->expression = $expression;
		return $this; //for chaining
	}

	/**
	 * get the string representation
	 * @return string
	 */
	public function getSparql() {
		return '(' . $this->expression->getSparql() . ')';
	}
}

?>
