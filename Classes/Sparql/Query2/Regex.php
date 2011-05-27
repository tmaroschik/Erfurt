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
 * represents a built-in "regex" function call
 *
 * @package Semantic
 * @scope prototype
 */
class Regex extends ElementHelper implements Interfaces\BuiltInCall {

	protected $element1;
	protected $element2;
	protected $element3;

	/**
	 *
	 * @param Interfaces\Expression $element1
	 * @param Interfaces\Expression $element2
	 * @param <type> $element3
	 */
	public function __construct(Interfaces\Expression $element1, Interfaces\Expression $element2, $element3 = null) {
		$this->element1 = $element1;
		$this->element2 = $element2;
		if ($element3 != null) {
			if ($element3 instanceof Interfaces\Expression) {
				$this->element3 = $element3;
			} else {
				throw new \RuntimeException('Argument 3 passed to Regex::__construct must be an instance of Expression or null, instance of ' . typeHelper($element3) . ' given');
			}
		}
		parent::__construct();
	}

	/**
	 * get the string representation
	 * @return string
	 */
	public function getSparql() {
		return 'REGEX(' . $this->element1->getSparql() .
			   ', ' . $this->element2->getSparql() .
			   (gettype($this->element3) == 'object' ? (', ' . $this->element3->getSparql()) : '') . ')';
	}

}

?>
