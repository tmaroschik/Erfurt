<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Rdf;
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
class LiteralTest extends \Erfurt\Tests\Unit\BaseTestCase {

	public function testInitWithLabel() {
		$literal1 = \Erfurt\Rdf\Literal::initWithLabel("Testliteral1");
		$literal2 = \Erfurt\Rdf\Literal::initWithLabel("Testliteral2");

		$this->assertSame("Testliteral1", $literal1->getLabel());
		$this->assertSame("Testliteral2", $literal2->getLabel());
	}

	public function testInitWithLabelAndLanguage() {
		$literal1 = \Erfurt\Rdf\Literal::initWithLabelAndLanguage("Testliteral1", "de");
		$literal2 = \Erfurt\Rdf\Literal::initWithLabelAndLanguage("Testliteral2", "en");

		$this->assertSame("Testliteral1", $literal1->getLabel());
		$this->assertSame("Testliteral2", $literal2->getLabel());

		$this->assertSame("de", $literal1->getLanguage());
		$this->assertSame("en", $literal2->getLanguage());

	}

	public function testInitWithLabelAndDatatype() {
		$literal1 = \Erfurt\Rdf\Literal::initWithLabelAndDatatype("true", "http://www.w3.org/2001/XMLSchema#boolean");
		$literal2 = \Erfurt\Rdf\Literal::initWithLabelAndDatatype("Testliteral2", "http://www.w3.org/2001/XMLSchema#string");

		$this->assertSame("true", $literal1->getLabel());
		$this->assertSame("Testliteral2", $literal2->getLabel());

		$this->assertSame("http://www.w3.org/2001/XMLSchema#boolean", $literal1->getDatatype());
		$this->assertSame("http://www.w3.org/2001/XMLSchema#string", $literal2->getDatatype());

	}

}

?>