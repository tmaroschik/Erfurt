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
 * Erfurt Sparql Query2 - TriplesSameSubject
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: TriplesSameSubject.php 4181 2009-09-22 15:46:24Z jonas.brekle@gmail.com $
 */
class TriplesSameSubject extends ElementHelper implements Interfaces\TriplesSameSubject {

	protected $subject;
	protected $propertyList;

	/**
	 * @param Interfaces\VarOrTerm $subject
	 * @param array $propList array of (Interfaces\Verb, Interfaces\ObjectList)-pairs
	 */
	public function __construct($subject, PropertyList $propList) {
		if (!($subject instanceof Interfaces\VarOrTerm) && !($subject instanceof Interfaces\TriplesNode)) {
			throw new \RuntimeException('Argument 1 passed to Interfaces\TriplesSameSubject::__construct must be instance of Interfaces\VarOrTerm or Interfaces\TriplesNode', E_USER_ERROR);
		}
		$this->subject = $subject;
		$this->propertyList = $propList;
		parent::__construct();
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj
	 * @return string
	 */
	public function getSparql() {
		$propList = '';
		return $this->subject->getSparql() . ' ' . $this->propertyList->getSparql();
	}

	/**
	 * getVars
	 * get all vars used in this pattern (recursive)
	 * @return array array of Interfaces\Var
	 */
	public function getVars() {
		$ret = array();
		if ($this->subject instanceof Variable) {
			$ret[] = $this->subject;
		}
		$ret = array_merge($ret, $this->propertyList->getVars());
		return $ret;
	}

	/**
	 * getPropList
	 * @return array array of (Interfaces\Verb, Interfaces\ObjectList)-pairs
	 */
	public function getPropList() {
		return $this->propertyList;
	}

	/**
	 * getSubject
	 * @return Interfaces\VarOrTerm the subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	public function getWeight($part = null) {
		if ($part == null) {
			$i = 0;
			foreach ($this->propertyList as $prop) {
				$i += ($prop['pred'] instanceof Variable ? 1 : 0) + ($prop['obj'] instanceof ObjectList ? $prop['obj']->getNumVars() : 0);
			}
			return ($this->subject instanceof Variable ? 1 : 0) + $i;
		} else {
			switch ($part) {
				case 0:
					return ($this->subject instanceof Variable ? 1 : 0);
					break;
				case 1:
					$i = 0;
					foreach ($this->propertyList as $prop) {
						$i += $prop['pred'] instanceof Variable ? 1 : 0;
					}
					return $i;
				case 2:
					$i = 0;
					foreach ($this->propertyList as $prop) {
						if ($prop['obj'] instanceof ObjectList) {
							$i += $prop['obj']->getNumVars();
						} else {
							if ($prop['obj'] instanceof Variable) {
								$i++;
							}
						}
					}
					return $i;
					break;
			}
		}
	}

	public static function compareWeight($c1, $c2) {
		if (!($c1 instanceof Interfaces\TriplesSameSubject && $c2 instanceof Interfaces\TriplesSameSubject)) {
			return 0;
		}
		$res = $c1->getWeight() - $c2->getWeight();
		switch ($res) {
			case $res == 0:
				// go deeper
				break;
			case $res < 0:
				$ret = -1;
				break;
			case $res > 0:
				$ret = 1;
				break;
		}
		return $ret;
	}

}

?>