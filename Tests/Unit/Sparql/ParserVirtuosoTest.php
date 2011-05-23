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
set_include_path(get_include_path() . PATH_SEPARATOR . '../../ontowiki/src/libraries/');
class ParserVirtuosoTest extends \Erfurt\Tests\Unit\BaseTestCase {

	const RAP_TEST_DIR = 'resources/sparql/rap/';
	const OW_TEST_DIR = 'resources/sparql/ontowiki/';
	const EF_TEST_DIR = 'resources/sparql/erfurt/';
	const DAWG_DATA_DIR = 'resources/sparql/w3c-dawg2/data-r2/';

	protected function tearDown() {
		gc_collect_cycles();
	}

	/**
	 * @dataProvider providerTestParse
	 */
	public function testParse($querySpec) {
		$parser = new \Erfurt\Sparql\Parser\Virtuoso();
		try {
			$q = $parser->initFromString($querySpec["query"]);
			// If query type is negative, we should not reach this code...
			if ($querySpec['type'] === 'negative') {
				echo $querySpec['type'] . "\n";
				$e = new \Exception('Query parsing should fail.');
				$this->fail($this->_createErrorMsg($querySpec, $e));
			}
			$this->assertTrue($q instanceof \Erfurt\Sparql\Query2);
		}
		catch (\Exception $e) {
			if ($querySpec['type'] === 'positive') {
				$this->fail($this->_createErrorMsg($querySpec, $e));
			}
		}
	}


	public function providerTestParse() {
		$queryArray = array();

		// // 1. ow tests
		// $this->_importFromManifest(self::OW_TEST_DIR . 'manifest.ttl', $queryArray);
		//
		// // 2. erfurt tests
		// $this->_importFromManifest(self::EF_TEST_DIR . 'manifest.ttl', $queryArray);
		// //
		// // 3. rap tests
		// $this->_importFromManifest(self::RAP_TEST_DIR . 'manifest.ttl', $queryArray);

		// 4. dawg2
		$parser = new \Erfurt\Syntax\RdfParser();
		$parser->initializeWithFormat('turtle');

		$result = $parser->parse(self::DAWG_DATA_DIR . 'manifest-syntax.ttl', \Erfurt\Syntax\RdfParser::LOCATOR_FILE);
		$keys = array_keys($result);
		$subject = $keys[0];
		$base = $subject;
		$predicate = 'http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#include';
		$object = $result["$subject"]["$predicate"][0]['value'];

		while (true) {
			$p = EF_RDF_NS . 'first';
			$filename = $result["$object"]["$p"][0]['value'];

			$filename = self::DAWG_DATA_DIR . substr($filename, strlen($base));

			$this->_importFromManifest($filename, $queryArray);

			$p = EF_RDF_NS . 'rest';
			$nil = EF_RDF_NS . 'nil';
			if ($result["$object"]["$p"][0]['value'] === $nil) {
				break;
			} else {
				$object = $result["$object"]["$p"][0]['value'];
			}
		}

		return $queryArray;
	}


	protected function _importFromManifest($filename, &$queryResultArray) {
		$parser = new \Erfurt\Syntax\RdfParser();
		$parser->initializeWithFormat('turtle');

		$manifestResult = $parser->parse($filename, \Erfurt\Syntax\RdfParser::LOCATOR_FILE);
		$mfAction = 'http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#action';

		// file auslesen...
		foreach ($manifestResult as $s => $pArray) {
			if (isset($pArray[EF_RDF_TYPE]) &&
				$pArray[EF_RDF_TYPE][0]['value'] ===
				'http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#PositiveSyntaxTest') {

				$queryFileName = substr($filename, 0, strrpos($filename, '/') + 1) .
								 substr($pArray["$mfAction"][0]['value'],
										strrpos($pArray["$mfAction"][0]['value'], '/'));


				$queryArray = array();
				$queryArray['name'] = $s;
				$queryArray['file_name'] = $queryFileName;
				$queryArray['group'] = 'Positive syntax tests';
				$queryArray['type'] = 'positive';

				$handle = fopen($queryFileName, "r");
				$queryArray['query'] = fread($handle, filesize($queryFileName));
				fclose($handle);
				$queryResultArray[] = array($queryArray);
			} else {
				if (isset($pArray[EF_RDF_TYPE]) &&
					$pArray[EF_RDF_TYPE][0]['value'] ===
					'http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#NegativeSyntaxTest') {

					$queryFileName = substr($filename, 0, strrpos($filename, '/') + 1) .
									 substr($pArray["$mfAction"][0]['value'],
											strrpos($pArray["$mfAction"][0]['value'], '/'));


					$queryArray = array();
					$queryArray['name'] = $s;
					$queryArray['file_name'] = $queryFileName;
					$queryArray['group'] = 'Negative syntax tests';
					$queryArray['type'] = 'negative';

					$handle = fopen($queryFileName, "r");
					$queryArray['query'] = fread($handle, filesize($queryFileName));
					fclose($handle);
					$queryResultArray[] = array($queryArray);
				} else {
					continue;
				}
			}
		}
	}

	protected function _createErrorMsg($query, $e) {
		$msg = 'Group: ' . $query['group'] . PHP_EOL .
			   'Filename: ' . $query['file_name'] . PHP_EOL .
			   'Name: ' . $query['name'] . PHP_EOL;
		#'Query: ' . $query['query'] . PHP_EOL;

		$msg .= 'Error: ' . $e->getMessage();

		return $msg;
	}

}

?>