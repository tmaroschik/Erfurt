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
 * Erfurt Sparql Query - A
 *
 * an object that does nothing else then behaving like a "Verb" (see sparql grammar) and printing an "a"
 *
 * @package Semantic
 * @scope prototype
 */
class A implements Interfaces\Verb {
	/**
	 * getSparql
	 * build a valid sparql representation of this obj - is "a"
	 * @return string
	 */
	public function getSparql() {
		return 'a';
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>