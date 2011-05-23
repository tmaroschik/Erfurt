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
 * Erfurt Sparql Query2 - Filter.
 *
 * holds a constraint. no action here
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Filter extends ElementHelper {

	protected $element;

	/**
	 * @param Constraint $element
	 */
	public function __construct($element) {
		if (!($element instanceof Interfaces\Constraint || is_bool($element))) {
			throw new \Exception('Argument 1 passed to Filter::__construct must be Instance of Constraint', 1);
		}
		if (is_bool($element)) {
			$element = new BooleanLiteral($element);
		}
		$this->element = $element;
		parent::__construct();
	}

	/**
	 * getConstraint
	 * @return Interfaces\Constraint
	 */
	public function getConstraint() {
		return $this->element;
	}

	/**
	 * setConstraint
	 * @param Interfaces\Constraint $element
	 * @return Filter $this
	 */
	public function setConstraint($element) {
		if (!($element instanceof Interfaces\Constraint || is_bool($element))) {
			throw new \Exception('Argument 1 passed to Filter::__construct must be Instance of Constraint', 1);
		}
		if (is_bool($element)) {
			$element = new BooleanLiteral($element);
		}
		$this->element = $element;
		return $this;
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "FILTER([constraint])"
	 * @return string
	 */
	public function getSparql() {
		$constraint_str = trim($this->element->getSparql());
		//grammar says: brackets are not needed , sparql engines say: error...
		if (substr($constraint_str, 0, 1) != '(') {
			$constraint_str = '(' . $constraint_str . ')';
		}
		return 'FILTER ' . $constraint_str;
	}

	public function __toString() {
		return $this->getSparql();
	}

	//TODO not implemented yet
	/**
	 * getVars
	 * get all vars used in this filter (recursive)
	 * @return array array of Var
	 */
	public function getVars() {
		return array();
	}

}

?>
