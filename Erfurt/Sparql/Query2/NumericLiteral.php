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
 * Erfurt Sparql Query2 - NumericLiteral
 *
 * represents numeric literals - does not do a lot because php's numeric literals are pretty much the same
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: NumericLiteral.php 4181 2009-09-22 15:46:24Z jonas.brekle@gmail.com $
 */
class NumericLiteral extends ElementHelper implements Interfaces\GraphTerm, Interfaces\PrimaryExpression {

	protected $value;

	/**
	 * @param numeric $num
	 */
	public function __construct($num) {
		if (is_numeric($num)) {
			$this->value = $num;
		} else {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_NumericLiteral::__construct must be numeric, instance of ' . typeHelper($num) . ' given');
		}
	}

	//TODO test this, looks too easy...
	/**
	 * getSparql
	 * build a valid sparql representation of this obj
	 * @return string
	 */
	public function getSparql() {
		return (string)$this->value;
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * setValue
	 * @param string $val
	 * @return Erfurt_Sparql_Query2_NumricLiteral $this
	 */
	public function setValue($val) {
		if (is_numeric($val)) {
			$this->value = $val;
		} else {
			throw new \RuntimeException('Argument 1 passed to Erfurt_Sparql_Query2_NumericLiteral::setValue must be numeric, instance of ' . typeHelper($val) . ' given');
		}
		return $this;
	}

	/**
	 * getValue
	 * @return string the value of the literal
	 */
	public function getValue() {
		return $this->value;
	}

}

?>
