<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Syntax;
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
class RdfSerializerTest extends \Erfurt\Tests\Unit\BaseTestCase {

	/**
	 * @var \Erfurt\Syntax\RdfSerializer
	 * @access protected
	 */
	protected $_object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp() {
		$this->_object = new \Erfurt\Syntax\RdfSerializer();
	}

	public function testRdfSerializerWithFormat() {
		$positiveFormats = array('rdfxml', 'xml', 'rdf', 'turtle', 'ttl', 'nt', 'ntriple',
								 'json', 'rdfjson', 'RDFXML', 'rdfXML', 'RdF', 'TuRTle');

		$negativeFormats = array('noVALIDformat', '123456789', 'rdf-xml', 'n 3', 'andsoon');

		foreach ($positiveFormats as $format) {
			try {
				$object = \Erfurt\Syntax\RdfSerializer::rdfSerializerWithFormat($format);

				if (!($object instanceof \Erfurt\Syntax\RdfSerializer)) {
					throw new \Exception ('Object initialization with ' . $format . ' failed where it should not fail.');
				}
			}
			catch (\Exception $e) {
				$this->fail($e->getMessage());
			}
		}

		foreach ($negativeFormats as $format) {
			try {
				$object = \Erfurt\Syntax\RdfSerializer::rdfSerializerWithFormat($format);

				// We should not reach this point.
				$this->fail('Object initialization should fail.');
			}
			catch (\Exception $e) {

			}
		}
	}

	public function testInitializeWithFormat() {
		$positiveFormats = array('rdfxml', 'xml', 'rdf', 'turtle', 'ttl', 'nt', 'ntriple',
								 'json', 'rdfjson', 'RDFXML', 'rdfXML', 'RdF', 'TuRTle');

		$negativeFormats = array('noVALIDformat', '123456789', 'rdf-xml', 'n 3', 'andsoon');

		foreach ($positiveFormats as $format) {
			try {
				$object = new \Erfurt\Syntax\RdfSerializer();
				$object->initializeWithFormat($format);

				if (!($object instanceof \Erfurt\Syntax\RdfSerializer)) {
					throw new \Exception ('Object initialization with format ' . $format . ' failed where it should not fail.');
				}
			}
			catch (\Exception $e) {
				$this->fail($e->getMessage());
			}
		}

		foreach ($negativeFormats as $format) {
			try {
				$object = new \Erfurt\Syntax\RdfSerializer();
				$object->initializeWithFormat($format);

				// We should not reach this point.
				$this->fail('Initialization with format should fail.');
			}
			catch (\Exception $e) {

			}
		}
	}

	public function testSerializeGraphToStringWithRdfXml() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();
		$g = 'http://localhost/OntoWiki/Config/';

		$this->_object->initializeWithFormat('rdfxml');
		$result1 = $this->_object->serializeGraphToString($g);

		$adapter = new \Erfurt\Syntax\RdfSerializer\Adapter\RdfXml();
		$result2 = $adapter->serializeGraphToString($g);

		$this->assertEquals($result1, $result2);
	}

	public function testSerializeResourceToStringWithRdfXml() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();
		$g = 'http://localhost/OntoWiki/Config/';

		$this->_object->initializeWithFormat('rdfxml');
		$result1 = $this->_object->serializeResourceToString($g, $g);

		$adapter = new \Erfurt\Syntax\RdfSerializer\Adapter\RdfXml();
		$result2 = $adapter->serializeResourceToString($g, $g);

		$this->assertEquals($result1, $result2);
	}

	public function testSerializeGraphToStringWithRdfJson() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();
		$g = 'http://localhost/OntoWiki/Config/';

		$this->_object->initializeWithFormat('rdfjson');
		$result1 = $this->_object->serializeGraphToString($g);

		$adapter = new \Erfurt\Syntax\RdfSerializer\Adapter\RdfJson();
		$result2 = $adapter->serializeGraphToString($g);

		$this->assertEquals($result1, $result2);
	}

	public function testSerializeResourceToStringWithRdfJson() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();
		$g = 'http://localhost/OntoWiki/Config/';

		$this->_object->initializeWithFormat('rdfjson');
		$result1 = $this->_object->serializeResourceToString($g, $g);

		$adapter = new \Erfurt\Syntax\RdfSerializer\Adapter\RdfJson();
		$result2 = $adapter->serializeResourceToString($g, $g);

		$this->assertEquals($result1, $result2);
	}

	public function testSerializeGraphToStringWithN3() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();
		$g = 'http://localhost/OntoWiki/Config/';

		$this->_object->initializeWithFormat('ttl');
		$result1 = $this->_object->serializeGraphToString($g);

		$adapter = new \Erfurt\Syntax\RdfSerializer\Adapter\Turtle();
		$result2 = $adapter->serializeGraphToString($g);

		$this->assertEquals($result1, $result2);
	}

	public function testSerializeResourceToStringWithN3() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();
		$g = 'http://localhost/OntoWiki/Config/';

		$this->_object->initializeWithFormat('ttl');
		$result1 = $this->_object->serializeResourceToString($g, $g);

		$adapter = new \Erfurt\Syntax\RdfSerializer\Adapter\Turtle();
		$result2 = $adapter->serializeResourceToString($g, $g);

		$this->assertEquals($result1, $result2);
	}

}
?>