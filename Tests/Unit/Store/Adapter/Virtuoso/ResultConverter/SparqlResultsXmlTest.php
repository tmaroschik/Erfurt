<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Store\Adapter\Virtuoso\ResultConverter;
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
class SparqlResultsXmlTest extends \Erfurt\Tests\Unit\BaseTestCase {

	protected $_fixture = null;

	public function setUp() {
		require_once 'Erfurt/Store/Adapter/Virtuoso/ResultConverter/SparqlResultsXml.php';
		$this->_fixture = new \Erfurt\Store\Adapter\Virtuoso\ResultConverter\SparqlResultsXml();
	}

	/**
	 * @expectedException \Erfurt\Store\Adapter\Virtuoso\ResultConverter\Exception
	 */
	public function testInvalidFormatHead() {
		$invalid1 = array('head' => null, 'results' => array('bindings' => array()));
		$results1 = $this->_fixture->convert($invalid1);
	}

	/**
	 * @expectedException \Erfurt\Store\Adapter\Virtuoso\ResultConverter\Exception
	 */
	public function testInvalidFormatResults() {
		$invalid2 = array('head' => array('vars' => array()), 'results' => null);
		$results2 = $this->_fixture->convert($invalid2);
	}

	public function testSimple() {
		$extended = array(
			'head' => array('vars' => array('1', '2', '3')),
			'results' => array('bindings' => array(
				array(
					'1' => array('type' => 'uri', 'value' => 'http://example.com/1'),
					'2' => array('type' => 'literal', 'value' => 'ttt'),
					'3' => array('type' => 'literal', 'value' => 'ttt', 'datatype' => 'http://www.w3.org/2001/XMLSchema#string')
				),
				array(
					'1' => array('type' => 'uri', 'value' => 'http://example.com/1'),
					'2' => array('type' => 'literal', 'value' => 'ttt'),
					'3' => array('type' => 'literal', 'value' => 'äää', 'xml:lang' => 'de')
				)
			))
		);

		$expected = <<<EOT
<?xml version="1.0"?>
<sparql xmlns="http://www.w3.org/2005/sparql-results#">
  <head>
    <variable name="1"/>
    <variable name="2"/>
    <variable name="3"/>
  </head>
  <results>
    <result>
      <binding name="1"><uri>http://example.com/1</uri></binding>
      <binding name="2"><literal>ttt</literal></binding>
      <binding name="3"><literal datatype="http://www.w3.org/2001/XMLSchema#string">ttt</literal></binding>
    </result>
    <result>
      <binding name="1"><uri>http://example.com/1</uri></binding>
      <binding name="2"><literal>ttt</literal></binding>
      <binding name="3"><literal xml:lang="de">äää</literal></binding>
    </result>
  </results>
</sparql>
EOT;
		$actual = $this->_fixture->convert($extended);

		$this->assertSame(
			preg_replace('/\s/', '', $expected),
			preg_replace('/\s/', '', $actual));
	}

}

?>