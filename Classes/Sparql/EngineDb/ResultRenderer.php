<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb;

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
 *   Result renderer interface that any result renderer needs to implement.
 *   A result renderer converts the raw database results into a
 *   - for the user - usable result format, e.g. php arrays, xml, json and
 *   so on.
 *
 *   @author Christian Weiske <cweiske@cweiske.de>
 *   @license http://www.gnu.org/licenses/lgpl.html LGPL
 *
 *   @subpackage sparql
 */
use \Erfurt\Sparql;
interface ResultRenderer {

	/**
	 *   Converts the database results into the desired output format
	 *   and returns the result.
	 *
	 * @param array $arRecordSets  Array of (possibly several) SQL query results.
	 * @param Query $query	 SPARQL query object
	 * @param SparqlEngineDb $engine   Sparql Engine to query the database
	 * @return mixed   The result as rendered by the result renderers.
	 */
	public function convertFromDbResults($arRecordSets, Sparql\Query $query, $engine, $vars);
}
?>