<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb;
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
 * Determines the offset in a row of sql queries.
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @subpackage sparql
 * @author Christian Weiske <cweiske@cweiske.de>
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @license http://www.gnu.org/licenses/lgpl.html LGPL
 * @version	$Id: $
 */
use \Erfurt\Sparql;
class Offsetter {

	protected $_engine;
	protected $_query;

	public function __construct($engine, Sparql\Query $query) {
		$this->_engine = $engine;
		$this->_query = $query;
	}

	/**
	 * Determines the offset in the sqls, the position to start from.
	 */
	public function determineOffset($arSqls) {
		$arSM = $this->_query->getSolutionModifier();
		if ($arSM['offset'] === null) {
			return array(0, 0);
		}
		$nCount = 0;
		foreach ($arSqls as $nId => $arSql) {
			$nCurrentCount = $this->_getCount($arSql);
			if ($nCurrentCount + $nCount > $arSM['offset']) {
				return array($nId, $arSM['offset'] - $nCount);
			}
			$nCount += $nCurrentCount;
		}
		//nothing found - no results for this offset
		return array(count($arSqls), 0);
	}

	/**
	 * Returns the number of rows that the given query will return.
	 *
	 * @param array $arSql Array with sql parts and at least keys 'from' and 'where' set.
	 * @return int Number of rows returned.
	 */
	protected function _getCount($arSql) {
		$sql = SqlMerger::getCount($this->_query, $arSql);
		$dbResult = $this->_engine->sqlQuery($sql);
		$nCount = 0;
		foreach ($dbResult as $row) {
			$nCount = intval($row['count']);
			break;
		}
		return $nCount;
	}

}

?>