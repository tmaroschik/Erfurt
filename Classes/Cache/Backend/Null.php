<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Cache\Backend;

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
class Null extends \Zend_Cache_Backend implements \Zend_Cache_Backend_Interface {

	public function __construct() {
		parent::__construct();
	}

	public function load($id, $doNotTestCacheValidity = false) {
		return false;
	}

	public function test($id) {
		return false;
	}

	public function save($data, $id, $tags = array(), $specificLifetime = false) {
		return true;
	}

	public function remove($id) {
		return true;
	}

	public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
		return true;
	}

}

?>