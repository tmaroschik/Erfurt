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
 * Erfurt_Sparql Query - Triple.
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Triple extends ElementHelper implements Interfaces\TriplesSameSubject {

	protected $s;
	protected $p;
	protected $o;

	/**
	 * @param Interfaces\VarOrTerm $s
	 * @param Interfaces\Verb $p
	 * @param Interfaces\IF_ObjectList $o
	 */
	public function __construct(Interfaces\VarOrTerm $s, Interfaces\Verb $p, Interfaces\ObjectList $o) {
		$this->s = $s;
		$this->p = $p;
		$this->o = $o;
		parent::__construct();
	}

	/**
	 * setS
	 * set the subject (the first element of this triple)
	 * @param Interfaces\VarOrTerm $s
	 * @return Interfaces\Triple $this
	 */
	public function setS(Interfaces\VarOrTerm $s) {
		$this->s = $s;
		return $this; //for chaining
	}

	/**
	 * setP
	 * set the predicate (the second element of this triple)
	 * @param Interfaces\Verb $p
	 * @return Interfaces\Triple $this
	 */
	public function setP(Interfaces\Verb $p) {
		$this->p = $p;
		return $this; //for chaining
	}

	/**
	 * setO
	 * set the object (the third element of this triple)
	 * @param Interfaces\IF_ObjectList $o
	 * @return Interfaces\Triple $this
	 */
	public function setO(Interfaces\ObjectList $o) {
		$this->o = $o;
		return $this; //for chaining
	}

	/**
	 * getS
	 * get the subject (the first element of this triple)
	 * @return Interfaces\VarOrTerm
	 */
	public function getS() {
		return $this->s;
	}

	/**
	 * getP
	 * get the predicate (the second element of this triple)
	 * @return Interfaces\Verb
	 */
	public function getP() {
		return $this->p;
	}

	/**
	 * getO
	 * get the object (the third element of this triple)
	 * @return Interfaces\IF_ObjectList
	 */
	public function getO() {
		return $this->o;
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "?s ?p ?o"
	 * @return string
	 */
	public function getSparql() {
		return $this->s->getSparql() . ' ' . $this->p->getSparql() . ' ' . $this->o->getSparql();
	}

	/**
	 * getVars
	 * get all vars used in this pattern (recursive)
	 * @return array array of Interfaces\Var
	 */
	public function getVars() {
		$vars = array();
		if ($this->s instanceof Variable) {
			$vars[] = $this->s;
		}
		if ($this->p instanceof Variable) {
			$vars[] = $this->p;
		}
		if ($this->o instanceof Variable) {
			$vars[] = $this->o;
		}
		return $vars;
	}

	public function getWeight($part = null) {
		if ($part == null) {
			return ($this->s instanceof Variable ? 1 : 0) +
				   ($this->p instanceof Variable ? 1 : 0) +
				   ($this->o instanceof ObjectList ?
						   $this->o->getNumVars() :
						   ($this->o instanceof Variable ?
								   1 :
								   0
						   )
				   );
		} else {
			switch ($part) {
				case 0:
					return ($this->s instanceof Variable ? 1 : 0);
					break;
				case 1:
					return ($this->p instanceof Variable ? 1 : 0);
					break;
				case 2:
					if ($this->o instanceof ObjectList) {
						return $this->o->getNumVars();
					} else {
						if ($this->o instanceof Variable) {
							return 1;
						} else {
							return 0;
						}
					}
					break;
			}
		}
	}

}

?>