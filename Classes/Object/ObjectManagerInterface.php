<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Object;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Interface for the TYPO3 Object Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
interface ObjectManagerInterface {

	const INITIALIZATIONCAUSE_CREATED = 1;
	const INITIALIZATIONCAUSE_RECREATED = 2;

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 *
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Manager's create() method and Singleton objects should rather be
	 * injected by some type of Dependency Injection.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @api
	 */
	public function get($objectName);

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This factory method can only create objects of the scope prototype.
	 * Singleton objects must be either injected by some type of Dependency Injection or
	 * if that is not possible, be retrieved by the get() method of the
	 * Object Manager
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws Tx_Extbase_Object_Exception_WrongScropeException if the created object is not of scope prototype
	 * @api
	 */
	public function create($objectName);

}

?>