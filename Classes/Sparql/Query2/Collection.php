<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;

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
 * Erfurt Sparql Query2 - Collection
 *
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: Collection.php 4181 2009-09-22 15:46:24Z jonas.brekle@gmail.com $
 */
class Collection extends ObjectList  implements Interfaces\TriplesNode {

	/**
	 * @param array array of Erfurt_Sparql_Query2_GraphNode
	 */
	public function __construct($objects) {
		parent::__construct($objects);
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "(obj1, obj2, obj3)"
	 * @return string
	 */
	public function getSparql() {
		return '(' . parent::getSparql() . ')';
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>
