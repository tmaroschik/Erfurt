<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Cache\Frontend;

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
class ObjectCache extends \Zend_Cache_Core {

	/**
	 * This method is the only addtition made to Zend_Cache_Core. It takes a class-instance, a function name,
	 * an optional arguments array and an optional prefix for the id and generated a unique id by concatenating
	 * $addtionalIdPrefix + Classname of $object + $functionName + Serialization of $args and building the md5 hash.
	 *
	 * @param Object $object An instance of a class.
	 * @param string $functionName The name of the function, which return value should be cached.
	 * @param array $args An array containing the arguments for the function call.
	 * @param string $addtionalIdPrefix An optional prefix for the id generation
	 *
	 * @return string Returns the md5 hash of the serialization of the given parameters.
	 */
	public function makeId($object, $functionName, $args = array(), $addtionalIdPrefix = '') {
		return md5($addtionalIdPrefix . get_class($object) . $functionName . serialize($args));
	}

}

?>