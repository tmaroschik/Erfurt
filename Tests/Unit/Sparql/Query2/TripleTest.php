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
class TripleTest extends \Erfurt\Tests\Unit\BaseTestCase {

	protected $triple;

	public function setUp() {

	}

	public function testInterfaces() {
		$iri = new \Erfurt\Sparql\Query2\IriRef("http://example.com");
		$var = new \Erfurt\Sparql\Query2\Variable("x");
		$literal = new \Erfurt\Sparql\Query2\RDFLiteral("abc");

		//some negative tests first
		try {
			//literal as predicate
			$this->triple = new \Erfurt\Sparql\Query2\Triple($var, $literal, $iri);

			//this should not be reached
			$this->fail();
		}
		catch (\Exception $e) {
			//good
		}

		//positive tests
		try {
			$this->triple = new \Erfurt\Sparql\Query2\Triple($var, $iri, $literal);
		}
		catch (\Exception $e) {
			$this->fail();
		}
		try {
			$this->triple = new \Erfurt\Sparql\Query2\Triple($iri, $var, $literal);
		}
		catch (\Exception $e) {
			$this->fail();
		}
		try {
			$this->triple = new \Erfurt\Sparql\Query2\Triple($literal, $var, $iri); //literal subject is ok :)
		}
		catch (\Exception $e) {
			$this->fail();
		}
		try {
			$this->triple = new \Erfurt\Sparql\Query2\Triple($literal, $iri, $var); //literal subject is ok :)
		}
		catch (\Exception $e) {
			$this->fail();
		}
	}

}

?>