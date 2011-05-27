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
 * OntoWiki Sparql Query - BooleanLiteral
 *
 * represent "true" and "false"
 *
 * @package Semantic
 * @scope prototype
 */
class BooleanLiteral extends ElementHelper implements Interfaces\GraphTerm, Interfaces\PrimaryExpression {

	protected $value;

	/**
	 * @param bool $bool
	 */
	public function __construct($bool) {
		if (is_bool($bool)) {
			$this->value = $bool;
		} else {
			throw new \RuntimeException("Argument 1 passed to Erfurt_Sparql_Query2_BooleanLiteral::__construct must be boolean, instance of '.typeHelper($bool).' given");
		}
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be 'true' or 'false'
	 * @return string
	 */
	public function getSparql() {
		return $this->value ? 'true' : 'false';
	}

	public function __toString() {
		return $this->getSparql();
	}
}
?>
