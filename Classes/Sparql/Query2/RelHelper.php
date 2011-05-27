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
 * @package Semantic
 * @scope prototype
 */
abstract class RelHelper extends ElementHelper implements Interfaces\Expression {

	protected $conjuction;

	/**
	 * @var Interfaces\Expression
	 */
	protected $element1;

	/**
	 * @var Interfaces\Expression
	 */
	protected $element2;

	/**
	 * create a relation
	 * @param Interfaces\Expression $e1
	 * @param Interfaces\Expression $e2
	 */
	public function __construct(Interfaces\Expression $e1, Interfaces\Expression $e2) {
		parent::__construct();
		$this->element1 = $e1;
		$this->element2 = $e2;
	}

	/**
	 * set the first element
	 * @param Interfaces\Expression $element
	 * @return RelHelper
	 */
	public function setElement1(Interfaces\Expression $element) {
		$this->element1 = $element;
		return $this; //for chaining
	}

	/**
	 * set the second element
	 * @param Interfaces\Expression $element
	 * @return RelHelper
	 */
	public function setElement2(Interfaces\Expression $element) {
		$this->element2 = $element;
		return $this; //for chaining
	}

	/**
	 * get string representation
	 * @return string
	 */
	public function getSparql() {
		return $this->element1->getSparql() . ' ' . $this->conjuction . ' ' . $this->element2->getSparql();
	}
}

?>
