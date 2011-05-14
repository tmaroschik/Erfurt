<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit\Versioning;
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
class StoreStub {

	const ROLLBACK_ACTION_QUERY1 = 'SELECT action_type, payload_id, model, parent FROM tx_semantic_versioning_actions WHERE ( id = ';
	const ROLLBACK_ACTION_QUERY2 = 'SELECT statement_hash FROM tx_semantic_versioning_payloads WHERE id = ';

	public function sqlQuery($sql, $limit, $offset) {
		if (strpos($sql, 'INSERT') !== false) {
			return array();
		}

		if (strpos($sql, self::ROLLBACK_ACTION_QUERY1) === 0) {
			if (substr($sql, (strpos($sql, 'id = ') + 5)) == 1) { // Should work
				return array(
					array(
						'action_type' => 20,
						'payload_id' => 1
					)
				);
			} else {
				if (substr($sql, (strpos($sql, 'id = ') + 5)) == 2) { // Should work
					return array(
						array(
							'action_type' => 22,
							'payload_id' => 1
						)
					);
				} else {
					if (substr($sql, (strpos($sql, 'id = ') + 5)) == 3) { // Should fail
						return array(
							array(
								'action_type' => 20,
								'payload_id' => 2
							)
						);
					} else {
						if (substr($sql, (strpos($sql, 'id = ') + 5)) == 4) { // Should fail
							return array(
								array(
									'action_type' => 20,
									'payload_id' => null
								)
							);
						} else {
							return array();
						}
					}
				}
			}
		} else {
			if (strpos($sql, self::ROLLBACK_ACTION_QUERY2) === 0) {
				if (substr($sql, (strpos($sql, 'id = ') + 5)) == 1) {
					return array(
						array(
							'statements_hash' => 'a:2:{s:29:"http://example.org/resource1/";a:1:{s:29:"http://example.org/property1/";a:1:{i:0;a:2:{s:4:"type";s:7:"literal";s:5:"value";s:6:"Value1";}}}s:29:"http://example.org/resource2/";a:1:{s:29:"http://example.org/property2/";a:1:{i:0;a:2:{s:4:"type";s:3:"uri";s:5:"value";s:26:"http://example.org/object/";}}}}'
						)
					);
				} else {
					if (substr($sql, (strpos($sql, 'id = ') + 12)) == 2) {
						return array();
					}
				}
			}
		}


		$sql = substr($sql, 7); // Remove SELECT

		$variables = substr($sql, 0, strpos($sql, 'FROM'));

		$keys = explode(', ', $variables);
		$result = array();

		preg_match('/(LIMIT ){1,1}(\d{1,})/', $sql, $match);

		if ($limit !== null) {
			$match[2] = $limit;

			if ($limit === PHP_INT_MAX) {
				$match[2] = 10000;
			}

		} else {
			if (!isset($match[2])) {
				$match[2] = 100;
			}
		}

		for ($i = 0; $i < $match[2]; ++$i) {
			$result[$i] = array();

			foreach ($keys as $k) {
				$result[$i][trim($k)] = 'ttt' . $i;
			}
		}

		return $result;
	}

	public function lastInsertId() {
		return 1;
	}

	public function addMultipleStatements($statements) {
		// We assume this works while testing.
	}

	public function deleteMultipleStatements($statements) {
		// We assume this works while testing.
	}

	public function isSqlSupported() {
		return true;
	}

	public function listTables() {
		return array(
			'tx_semantic_versioning_actions',
			'tx_semantic_versioning_payloads'
		);
	}

}

?>