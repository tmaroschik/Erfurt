<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Domain\Model\Rdf;

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
 * Represents a basic RDF literal.
 *
 * @scope prototype
 */
class Literal extends Node {

	protected $label = false;
	protected $lang = null;
	protected $datatype = null;

	protected function __construct($label) {

		$this->label = $label;
	}

	/**
	 * Returns a string representation of this resource.
	 *
	 * @return string
	 */
	public function __toString() {
		if ($this->getLabel()) {
			$ret = $this->getLabel();
			if ($this->getDatatype()) {
				$ret .= "^^" . $this->getDatatype();
			}
			else {
				if ($this->getLanguage()) {
					$ret .= "@" . $this->getLanguage();
				}
			}
			return $ret;
		}
		else {
			return "";
		}
	}

	public function setLanguage($lang) {
		$this->lang = $lang;
	}

	public function setDatatype($datatype) {
		$this->datatype = $datatype;
	}

	public function getLabel() {
		return $this->label;
	}

	public function getDatatype() {
		return $this->datatype;
	}

	public function getLanguage() {
		return $this->lang;
	}

}

?>