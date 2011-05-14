<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Sparql\Query2;
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
class IriRefTest extends \Erfurt\Tests\Unit\BaseTestCase {

	protected $iri;

	public function setUp() {
		$this->iri = new \Erfurt\Sparql\Query2\IriRef("http://example.com/");
	}

	public function testSimple() {
		$this->assertTrue($this->iri->getSparql() == "<http://example.com/>");
	}

	public function testPrefixedUri() {
		$prefix = new \Erfurt\Sparql\Query2\Prefix("ns", new \Erfurt\Sparql\Query2\IriRef("http://example.com/"));
		$this->iri = new \Erfurt\Sparql\Query2\IriRef("local", $prefix);
		$this->assertTrue($this->iri->getSparql() == "ns:local");
		$this->assertTrue($this->iri->getExpanded() == "<http://example.com/local>");
		$this->assertTrue($this->iri->isPrefixed());
	}

	public function testUnexpandablePrefixedUri() {
		$this->iri = new \Erfurt\Sparql\Query2\IriRef("local", null, "ns");
		$this->assertTrue($this->iri->getSparql() == "ns:local");
		$this->assertTrue($this->iri->getExpanded() == "ns:local");
		$this->assertTrue($this->iri->isPrefixed());
	}

}

?>