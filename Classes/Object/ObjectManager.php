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
	 * @var \Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManagerInstance;

	/**
	 * Constructor method for a object manager wrapper
	 */
	public function __construct() {
		$this->objectManagerInstance = \t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 *
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Manager's create() method and Singleton objects should rather be
	 * injected by some type of Dependency Injection.
	 *
	 * Note: Additional arguments to this function are only passed to the object
	 * container's get method for when the object is a prototype. Any argument
	 * besides $objectName is ignored if the target object is in singleton or session
	 * scope.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function get($objectName) {
		return call_user_func_array(array($this->objectManagerInstance, 'get'), func_get_args());
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This factory method can only create objects of the scope prototype.
	 * Singleton objects must be either injected by some type of Dependency Injection or
	 * if that is not possible, be retrieved by the get() method of the
	 * Object Manager
	 *
	 * You must use either Dependency Injection or this factory method for instantiation
	 * of your objects if you need FLOW3's object management capabilities (including
	 * AOP, Security and Persistence). It is absolutely okay and often advisable to
	 * use the "new" operator for instantiation in your automated tests.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @since 1.0.0 alpha 8
	 * @api
	 */
	public function create($objectName) {
		return call_user_func_array(array($this->objectManagerInstance, 'create'), func_get_args());
	}

}

?>