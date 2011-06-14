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
 * Represents a basic RDF literal factory.
 *
 * @scope prototype
 */
class LiteralFactory implements \Erfurt\Singleton {

	/**
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	public function buildFromLabel($label) {
		return $this->objectManager->create('Erfurt\Domain\Model\Rdf\Literal', $label);
	}

	public function buildFromLabelAndLanguage($label, $lang) {
		$literal = $this->objectManager->create('Erfurt\Domain\Model\Rdf\Literal', $label);
		$literal->setLanguage($lang);
		return $literal;
	}

	public function buildFromLabelAndDatatype($label, $datatype) {
		$literal = $this->objectManager->create('Erfurt\Domain\Model\Rdf\Literal', $label);
		$literal->setDatatype($datatype);
		return $literal;
	}

}

?>