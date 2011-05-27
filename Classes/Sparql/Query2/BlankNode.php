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
 * Erfurt_Sparql Query - BlankNode.
 *
 * @package Semantic
 * @scope prototype
 */
class BlankNode implements Interfaces\GraphTerm { //TODO 1. may not appear in two different graphs. 2. an anon bn should only be used once (fix in ...GraphPattern)
	protected $name = '';

	/**
	 * @param string $nname
	 */
	public function __construct($nname) {
		if (is_string($nname)) {
			$this->name = $nname;
		}
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "[]" or "_:localname"
	 * @return string
	 */
	public function getSparql() {
		return $this->isAnon() ? '[]' : '_:' . $this->name;
	}

	/**
	 * getName
	 * @return string the name of this blanknode
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * isAnon
	 * @return bool true if no name is set
	 */
	public function isAnon() {
		return empty($this->name);
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>
