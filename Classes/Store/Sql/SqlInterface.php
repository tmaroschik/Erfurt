<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Store\Sql;

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
 * Erfurt SQL interface
 *
 * @category Erfurt
 * @package Store_Sql
 * @author Norman Heino <norman.heino@gmail.com>
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
interface SqlInterface {

	/**
	 * Creates the table specified by $tableSpec according to backend-specific
	 * create table statement.
	 *
	 * @param string $tableName
	 * @param array $tableSpec An associative array of SQL column names and columnd specs.
	 */
	public function createTable($tableName, array $columns);

	/**
	 * Returns the ID for the last insert statement.
	 *
	 * @return int
	 */
	public function lastInsertId();

	/**
	 * Returns an array of SQL tables available in the store.
	 *
	 * @param string $prefix An optional table prefix to filter table names.
	 * @return array
	 */
	public function listTables($prefix = '');

	/**
	 * Executes a SQL query with a SQL-capable backend.
	 *
	 * @param string $sqlQuery A string containing the SQL query to be executed.
	 * @param int $limit Maximum number of results to return
	 * @param int $offset The number of results to skip from the beginning
	 * @return array
	 */
	public function sqlQuery($sqlQuery, $limit = PHP_INT_MAX, $offset = 0);

}

?>