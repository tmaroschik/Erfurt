<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Rdf;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This class is a port of the corresponding class of the
 *  {@link http://aksw.org/Projects/Erfurt Erfurt} project.
 *  All credits go to the Erfurt team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Represents a basic RDF literal factory.
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
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

	public function createFromLabel($label) {
		return $this->objectManager->create('\Erfurt\Rdf\Literal', $label);
	}

	public function createFromLabelAndLanguage($label, $lang) {
		$literal = $this->objectManager->create('\Erfurt\Rdf\Literal', $label);
		$literal->setLanguage($lang);
		return $literal;
	}

	public function createFromLabelAndDatatype($label, $datatype) {
		$literal = $this->objectManager->create('\Erfurt\Rdf\Literal', $label);
		$literal->setDatatype($datatype);
		return $literal;
	}

}

?>