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
class Json extends Extended {

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
		return json_encode($result);
	}

}

?>
