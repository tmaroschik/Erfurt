<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Cache\Backend;
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