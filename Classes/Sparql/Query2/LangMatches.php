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
 * represents a built-in LangMatches function call
 *
 * @package Semantic
 * @scope prototype
 */
class LangMatches extends ElementHelper implements Interfaces\BuiltInCall {
	protected $element1;
	protected $element2;

	/**
	 *
	 * @param Interfaces\Expression $element1
	 * @param Interfaces\Expression $element2
	 */
	public function __construct(Interfaces\Expression $element1, Interfaces\Expression $element2) {
		$this->element1 = $element1;
		$this->element2 = $element2;
		parent::__construct();
	}

	/**
	 * get the string representation
	 * @return string
	 */
	public function getSparql() {
		return 'LANGMATCHES(' . $this->element1->getSparql() . ', ' . $this->element2->getSparql() . ')';
	}

}

?>
