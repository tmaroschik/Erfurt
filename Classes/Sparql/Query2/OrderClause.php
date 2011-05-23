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
 * Erfurt_Sparql Query2 - OrderClause.
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class OrderClause {

	protected $exps = array();

	/**
	 * add
	 * add an expression to this order clause - expresssion will mostly be a Var... but by the grammar this can be almost anything
	 * @param Interfaces\Expression $exp
	 * @return int index of added element
	 */
	public function add(Interfaces\Expression $exp, $order = 'ASC') {
		if ($order != 'ASC' && $order != 'DESC') {
			throw new \RuntimeException('Argument 2 passed to OrderClause::add must be \'ASC\' or \'DESC\', ' . $order . ' (instance of ' . typeHelper($order) . ') given');
		}
		$this->exps[] = array('exp' => $exp, 'dir' => $order);
		return count($this->exps) - 1; //last index = index of added element
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like 'ORDER BY ASC(?var)'
	 * @return string
	 */
	public function getSparql() {
		$sparql = 'ORDER BY';
		$countExps = count($this->exps);
		for ($i = 0; $i < $countExps; ++$i) {
			$sparql .= ' ' . $this->exps[$i]['dir'] . '(' . $this->exps[$i]['exp']->getSparql() . ')';
			if ($i < (count($this->exps) - 1)) {
				$sparql .= ' ';
			}
		}
		$sparql .= '';
		return $sparql;
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * toggleDirection
	 * @param int $i index of element which direction should be toggled
	 * @return OrderClause $this
	 */
	public function toggleDirection($i) {
		$this->exps[$i]['dir'] = $this->exps[$i]['dir'] == 'ASC' ? 'DESC' : 'ASC';
		return $this; //for chaining
	}

	/**
	 * setAsc
	 * @param int $i index of element which direction should be set to ASC
	 * @return OrderClause $this
	 */
	public function setAsc($i) {
		$this->exps[$i]['dir'] = 'ASC';
		return $this; //for chaining
	}

	/**
	 * setDesc
	 * @param int $i index of element which direction should be set to DESC
	 * @return OrderClause $this
	 */
	public function setDesc($i) {
		$this->exps[$i]['dir'] = 'DESC';
		return $this; //for chaining
	}

	/**
	 * used
	 * @return bool true if any expressions are added
	 */
	public function used() {
		return !empty($this->exps);
	}

	public function clear() {
		$this->exps = array();
		return $this; //for chaining
	}

	public function getExpressions() {
		return $this->exps;
	}

}

?>