<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb\ResultRenderer;
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
/**
 * Result renderer that creates a JSON object string containing the result of a SPARQL query as defined in
 * @link http://www.w3.org/TR/rdf-sparql-json-res/.
 *
 * @subpackage sparql
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version	$Id: $
 */
use \Erfurt\Sparql;
class Xml extends Extended {

	/**
	 * Converts the database results into the desired output format
	 * and returns the result.
	 *
	 * @param array $arRecordSets Array of (possibly several) SQL query results.
	 * @param Sparql\Query $query SPARQL query object
	 * @param $engine Sparql Engine to query the database
	 * @return array
	 */
	public function convertFromDbResults($arRecordSets, Sparql\Query $query, $engine, $vars) {
		$result = parent::convertFromDbResults($arRecordSets, $query, $engine, $vars);
		$xmlString = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
					 '<sparql xmlns="http://www.w3.org/2005/sparql-results#">' . PHP_EOL .
					 '<head>' . PHP_EOL;
		if (isset($result['head']['vars'])) {
			foreach ($result['head']['vars'] as $var) {
				$xmlString .= '<variable name="' . $var . '" />' . PHP_EOL;
			}
		}
		$xmlString .= '</head>' . PHP_EOL;
		if (isset($result['boolean'])) {
			$xmlString .= '<boolean>' . $result['boolean'] . '</boolean>' . PHP_EOL;
		} else {
			$xmlString .= '<results>' . PHP_EOL;
			foreach ($result['bindings'] as $row) {
				$xmlString .= '<result>' . PHP_EOL;
				foreach ($row as $key => $value) {
					$xmlString .= '<binding name="' . $key . '">' . PHP_EOL;
					if ($value['type'] === 'bnode') {
						$xmlString .= '<bnode>' . $value['value'] . '</bnode>' . PHP_EOL;
					} else {
						if ($value['type'] === 'uri') {
							$xmlString .= '<uri>' . $value['value'] . '</uri>' . PHP_EOL;
						} else {
							if ($value['type'] === 'typed-literal') {
								$xmlString .= '<literal datatype="' . $value['datatype'] . '">' .
											  $value['value'] . '</literal>' . PHP_EOL;
							} else {
								if (isset($value['xml:lang'])) {
									$xmlString .= '<literal xml_lang="' . $value['xml:lang'] . '">' .
												  $value['value'] . '</literal>' . PHP_EOL;
								} else {
									$xmlString .= '<literal>' . $value['value'] . '</literal>' . PHP_EOL;
								}
							}
						}
					}
					$xmlString .= '</binding>' . PHP_EOL;
				}
				$xmlString .= '</result>' . PHP_EOL;
			}
			$xmlString .= '</results>' . PHP_EOL;
		}
		$xmlString .= '</sparql>' . PHP_EOL;
		return $xmlString;
	}

}

?>