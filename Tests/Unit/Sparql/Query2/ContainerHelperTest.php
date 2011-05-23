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
class ContainerHelperTest  extends \Erfurt\Tests\Unit\BaseTestCase {

	protected $container;

	public function setUp() {
		$this->container = new \Erfurt\Tests\Unit\Sparql\Query2\ContainerStub();
	}

	public function testElements() {
		$element = new \Erfurt\Tests\Unit\Sparql\Query2\ElementStub();
		$this->container->addElement($element);
		$elements = $this->container->getElements();
		$this->assertEquals($element, $elements[0]);
		$this->assertEquals($this->container->getElement(0), $elements[0]);
		$this->assertTrue($this->container->size() == 1);
		$this->container->removeAllElements();
		$elements = $this->container->getElements();
		$this->assertTrue(empty($elements));
		$this->assertTrue($this->container->size() == 0);
	}

	public function testSetProperties() {
		$element1 = new \Erfurt\Tests\Unit\Sparql\Query2\ElementStub();
		$element2 = new \Erfurt\Tests\Unit\Sparql\Query2\ElementStub();
		$this->container->addElement($element1);
		$this->container->addElement($element2);

		$container2 = new \Erfurt\Tests\Unit\Sparql\Query2\ContainerStub();
		$container2->addElement($element2);
		$container2->addElement($element1);

		$this->assertTrue($this->container->equals($container2));

		//test contains-function in recursive and non-recursive mode
		$this->container->removeAllElements();
		$this->container->addElement($element1);
		$this->container->addElement($container2);
		$this->assertFalse($this->container->contains($element2, false));
		$this->assertTrue($this->container->contains($element2, true));

		//clean
		$this->container->removeAllElements();
	}

	public function testVars() {
		$var = new \Erfurt\Sparql\Query2\Variable("x");
		$var2 = new \Erfurt\Sparql\Query2\Variable("y");
		$this->container->addElement($var);
		$container2 = new \Erfurt\Tests\Unit\Sparql\Query2\ContainerStub();
		$container2->addElement($var2);
		$this->container->addElement($container2);

		$this->assertEquals(array($var, $var2), $this->container->getVars());
	}

}

?>