<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql;

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
 * Object representation of a SPARQL variable.
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @package Semantic
 * @scope prototype
 */
class Variable {

	protected $_name;

	public function __construct($name) {
		$this->_name = $name;
	}

	public function __toString() {
		return $this->_name;
	}

	/**
	 *   Checks if the given subject/predicate/object
	 *   is a variable name.
	 *
	 * @return boolean
	 */
	public static function isVariable($bject) {
		return (is_string($bject) && (strlen($bject) >= 2) && (($bject[0] == '?') || ($bject[0] == '$')));
	}

}

?>