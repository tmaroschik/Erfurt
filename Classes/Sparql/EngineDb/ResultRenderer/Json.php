<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb\ResultRenderer;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 2 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/copyleft/gpl.html.                      *
 *                                                                        */
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
