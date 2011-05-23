<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Authentication\Adapter;
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
class RdfTest extends \Erfurt\Tests\Unit\BaseTestCase {

	public function testObjectCreation() {
		$instance = new \Erfurt\Authentication\Adapter\Rdf();

		$this->assertTrue($instance instanceof \Erfurt\Authentication\Adapter\Rdf);
	}

	public function testAuthenticateAnonymous() {
		$instance = new \Erfurt\Authentication\Adapter\Rdf('Anonymous');
		$result = $instance->authenticate();
		$id = $result->getIdentity();

		$this->assertTrue($result->isValid());
		$this->assertEquals('Anonymous', $id->getUsername());
		$this->assertTrue($id->isAnonymousUser());
	}

	public function testAuthenticateSuperAdmin() {
		$this->markTestNeedsTestConfig();
		$dbUser = $this->getDbUser();
		$dbPassword = $this->getDbPassword();

		$instance = new \Erfurt\Authentication\Adapter\Rdf($dbUser, $dbPassword);
		$result = $instance->authenticate();
		$id = $result->getIdentity();

		$this->assertTrue($result->isValid());
		$this->assertEquals('SuperAdmin', $id->getUsername());
		$this->assertTrue($id->isDbUser());
	}

	public function testAuthenticateSuperAdminWithWrongPassword() {
		$this->markTestNeedsTestConfig();
		$dbUser = $this->getDbUser();

		$instance = new \Erfurt\Authentication\Adapter\Rdf($dbUser, 'wrongPass');
		$result = $instance->authenticate();

		$this->assertFalse($result->isValid());
	}

	public function testAuthenticateAdmin() {
		$this->markTestNeedsDatabase();

		$instance = new \Erfurt\Authentication\Adapter\Rdf('Admin');
		$result = $instance->authenticate();
		$id = $result->getIdentity();

		$this->assertTrue($result->isValid());
		$this->assertEquals('Admin', $id->getUsername());
	}

	public function testAuthenticateUserWithWrongPassword() {
		$this->markTestNeedsDatabase();

		$instance = new \Erfurt\Authentication\Adapter\Rdf('Admin', 'wrongPass');
		$result = $instance->authenticate();

		$this->assertFalse($result->isValid());
	}

	public function testAuthenticateWithNotExistingUser() {
		$this->markTestNeedsDatabase();

		$instance = new \Erfurt\Authentication\Adapter\Rdf('UserDoesNotExist', 'wrongPass');
		$result = $instance->authenticate();

		$this->assertFalse($result->isValid());
	}

	public function testFetchDataForAllUsers() {
		$this->markTestNeedsDatabase();

		$instance = new \Erfurt\Authentication\Adapter\Rdf();
		$instance->fetchDataForAllUsers();
	}

	public function testGetUsers() {
		$this->markTestNeedsDatabase();

		$instance = new \Erfurt\Authentication\Adapter\Rdf();
		$users = $instance->getUsers();

		$this->assertTrue(is_array($users));
	}

}

?>