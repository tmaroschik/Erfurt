<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Sparql;
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
class EngineDbTest extends \Erfurt\Tests\Unit\BaseTestCase {

	const RDF_TEST_DIR = 'resources/rdf/';

	public function testOdFmiLimitQueryWithZendDbIssue782() {
		$this->markTestNeedsZendDb();
		$this->authenticateDbUser();

		$store = \Erfurt\App::getInstance()->getStore();

		$store->getNewModel('http://od.fmi.uni-leipzig.de/model/');
		$store->importRdf('http://od.fmi.uni-leipzig.de/model/', self::RDF_TEST_DIR . 'fmi.rdf', 'rdf');

		$store->getNewModel('http://od.fmi.uni-leipzig.de/s10/');
		$store->importRdf('http://od.fmi.uni-leipzig.de/s10/', self::RDF_TEST_DIR . 'fmi-s10.rdf', 'rdf');

		$sparql = 'PREFIX od: <http://od.fmi.uni-leipzig.de/model/>
        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        SELECT DISTINCT ?lv ?titel ?typ ?wtyp ?von ?bis ?tag ?raum
        FROM <http://od.fmi.uni-leipzig.de/s10/>
        WHERE {{
         ?lv a od:LV . ?lv rdf:type ?typ .
         ?lv rdfs:label ?titel .
         ?lv od:dayOfWeek ?tag .} OPTIONAL {
         ?lv od:locatedAt ?raum .
         ?lv od:startTime ?von .
         ?lv od:endTime ?bis .
         ?lv od:typeOfWeek ?wtyp .
        }} LIMIT 20';

		$result = $store->sparqlQuery($sparql, array('result_format' => 'xml'));
		var_dump($result);
		exit;
		$this->assertEquals(680, count($result));

		foreach ($result as $row) {
			$this->assertTrue(array_key_exists('lv', $row));
			$this->assertTrue(array_key_exists('titel', $row));
			$this->assertTrue(array_key_exists('typ', $row));
			$this->assertTrue(array_key_exists('wtyp', $row));
			$this->assertTrue(array_key_exists('von', $row));
			$this->assertTrue(array_key_exists('bis', $row));
			$this->assertTrue(array_key_exists('tag', $row));
			$this->assertTrue(array_key_exists('raum', $row));
		}
	}

}

?>