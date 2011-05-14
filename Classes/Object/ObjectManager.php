<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Object;
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
 * This is a wrapper around a object mananger.
 * It is needed for the standalone library.
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope singleton
 * @entity
 * @api
 */
class ObjectManager {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManagerInstance;

	/**
	 * Constructor method for a object manager wrapper
	 */
	public function __construct() {
		$this->objectManagerInstance = \t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
	}

	/**
	 * This magic function forwards all calls to the object manager instance instance
	 * @param  $name
	 * @param  $arguments
	 * @return void
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->objectManagerInstance, $name), $arguments);
	}
}
