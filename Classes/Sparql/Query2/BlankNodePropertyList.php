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
 * Description of BlankNodePropertyList
 *
 * @package Semantic
 * @scope prototype
 */
class BlankNodePropertyList implements Interfaces\TriplesNode {
	protected $propertyList;

	function __construct(PropertyList $propertyList) {
		if ($propertyList->isEmpty()) {
			throw new \RuntimeException('Argument 1 passed to BlankNodePropertyList::__construct must not be an empty PropertyList', E_USER_ERROR);
		}
		$this->propertyList = $propertyList;
	}

	public function getSparql() {
		return '[' . $this->propertyList . ']';
	}

	public function getPropertyList() {
		return $this->propertyList;
	}

	public function setPropertyList(PropertyList $propertyList) {
		if ($propertyList->isEmpty()) {
			throw new \RuntimeException('Argument 1 passed to BlankNodePropertyList::setPropertyList must not be an empty PropertyList', E_USER_ERROR);
		}
		$this->propertyList = $propertyList;
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>
