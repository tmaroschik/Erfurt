<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Syntax\RdfSerializer\Adapter;
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
class RdfJsonTest extends \Erfurt\Tests\Unit\BaseTestCase {

	/**
	 * @var \Erfurt\Syntax\RdfSerializer\Adapter\RdfJson()
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
		$this->_object = new \Erfurt\Syntax\RdfSerializer\Adapter\RdfJson();
	}

	public function testSerializeGraphToString() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();

		$result = $this->_object->serializeGraphToString('http://localhost/OntoWiki/Config/');
		$this->assertTrue(is_string($result));
	}

	public function testSerializeResourceToString() {
		$this->markTestNeedsDatabase();
		$this->authenticateDbUser();

		$r = 'http://localhost/OntoWiki/Config/';
		$result = $this->_object->serializeResourceToString($r, $r);
		$this->assertTrue(is_string($result));
	}

}

?>