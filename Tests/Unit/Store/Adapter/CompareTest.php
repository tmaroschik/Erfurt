<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Store\Adapter;
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
class CompareTest extends \Erfurt\Tests\Unit\BaseTestCase {

	public function setUp() {

	}

	private function getAdapter() {
		$optionsRef = array(
			'dsn' => 'VOS',
			'username' => 'dba',
			'password' => 'dba');
		$optionsCand = array();
		$optionsComp = array(
			'reference' => new \Erfurt\Store\Adapter\Virtuoso($optionsRef),
			'candidate' => new \Erfurt\Store\Adapter\EfZendDb($optionsCand)
		);

		$adapter = new \Erfurt\Store\Adapter\Sparql($options);
		return $adapter;
	}

	public function testInstantiation() {
		$adapter = $this->getAdapter();
		$this->assertTrue($adapter instanceof \Erfurt\Store\Adapter\Sparql);
	}

	public function testIsModelAvailable() {
		$graphUri1 = "http://idontexist.com/";
		$graphUri2 = "http://localhost/OntoWiki/Config/";
		$adapter = $this->getAdapter();
		//return values dont need to be checked
		//because not the result itself is relevant, but the equality of the 2
		//this is checked inside the method and a exception will be thrown in case
		$adapter->isModelAvailable($graphUri1);
		$adapter->isModelAvailable($graphUri2);
	}

}

?>