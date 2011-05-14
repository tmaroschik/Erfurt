<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Uri;
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
class UriTest extends \Erfurt\Tests\Unit\BaseTestCase {

	public function testCheck() {
		$validUris = array(
			'http://example.com/Test',
			'urn:isbn:978-3-86680-192-9',
			'ftp://ftp.is.co.za/rfc/rfc1808.txt',
			'gopher://spinaltap.micro.umn.edu/00/Weather/California/Los%20Angeles',
			'http://www.math.uio.no/faq/compression-faq/part1.html',
			'mailto:mduerst@ifi.unizh.ch',
			'news:comp.infosystems.www.servers.unix',
			'telnet://melvyl.ucop.edu/',
			'http://User:pass@example.com/test/cat?foo=ba%7C'
		);

		foreach ($validUris as $uri) {
			$this->assertEquals(true, \Erfurt\Uri\Uri::check($uri));
		}

		$invalidUris = array(
			'Literal value',
			'http://example.com /Test',
			"http://example.com/T\nest",
			'http://example.[com]/{test}/cat?foo=ba%7C'
		);

		foreach ($invalidUris as $uri) {
			$this->assertEquals(false, \Erfurt\Uri\Uri::check($uri));
		}
	}

	public function testNormalize() {
		$uri = 'HTtP://User:pA:897@ExaMPLe.COM/test/cat?foo=ba%7C';
		$normalized = 'http://User:pA:897@example.com/test/cat?foo=ba%7C';

		$this->assertEquals($normalized, \Erfurt\Uri\Uri::normalize($uri));
	}

	public function testNormalizeWithNonUri() {
		$this->setExpectedException('\Erfurt\Uri\Uri_Exception');

		$nonUri = "HTtP://User:pA:89\t7@ExaMPLe.COM/test/cat?foo=ba%7C";
		\Erfurt\Uri\Uri::normalize($nonUri);
	}

}

?>