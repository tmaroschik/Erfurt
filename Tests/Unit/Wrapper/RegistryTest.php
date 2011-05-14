<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Wrapper;
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
class RegistryTest extends \Erfurt\Tests\Unit\BaseTestCase {

	protected $_registry = null;

	protected function setUp() {
		$this->_registry = \Erfurt\Wrapper\Registry::getInstance();
	}

	protected function tearDown() {
		\Erfurt\Wrapper\Registry::reset();

		parent::tearDown();
	}

	public function testGetInstance() {
		$instance = \Erfurt\Wrapper\Registry::getInstance();

		$this->assertTrue($instance instanceof \Erfurt\Wrapper\Registry);
	}

	public function testGetWrapperInstanceWillFail() {
		try {
			$this->_registry->getWrapperInstance('doesnotexist');

			$this->fail();
		}
		catch (\Erfurt\Wrapper\Exception $e) {

		}
	}

	public function testGetWrapperInstanceWillSucceed() {
		$manager = new \Erfurt\Wrapper\Manager();
		$manager->addWrapperPath('resources/wrapper');

		try {
			$this->_registry->getWrapperInstance('enabled');
		}
		catch (\Erfurt\Wrapper\Exception $e) {
			$this->fail();
		}
	}

	public function testListActiveWrapperEmpty() {
		$result = $this->_registry->listActiveWrapper();

		$this->assertTrue(empty($result));
	}

	public function testListActiveWrapper() {
		$manager = new \Erfurt\Wrapper\Manager();
		$manager->addWrapperPath('resources/wrapper');

		$result = $this->_registry->listActiveWrapper();

		$this->assertEquals(1, count($result));
		$this->assertEquals('enabled', $result[0]);
	}

	public function testRegister() {
		$this->_registry->register('dummy', array());

		$result = $this->_registry->listActiveWrapper();

		$this->assertEquals(1, count($result));
		$this->assertEquals('dummy', $result[0]);
	}

	public function testRegisterAlreadyRegistered() {
		$this->_registry->register('dummy', array());

		try {
			$this->_registry->register('dummy', array());

			$this->fail();
		}
		catch (\Erfurt\Wrapper\Exception $e) {

		}
	}
}

?>