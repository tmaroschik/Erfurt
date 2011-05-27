<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Core;

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
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassLoader {

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and and erfurt classes
	 *
	 * @param string $className Name of the class/interface to load
	 * @return void
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 */
	public function loadClass($className) {
		$classNameParts = explode('\\', $className);
		if (substr($classNameParts[0], 0, 6) == 'Erfurt') {
			if ($classNameParts[1] == 'Tests') {
				$classFilePathAndName = EF_PATH_FRAMEWORK . 'Tests/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
			} else {
				$classFilePathAndName = EF_PATH_FRAMEWORK . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 1, -1), '/') . '/';
			}
			$classFilePathAndName .= end($classNameParts) . '.php';
		} elseif (substr($classNameParts[0], 0, 4) == 'Zend') {
			$classNameParts = explode('_', $classNameParts[0]);
			$classFilePathAndName = ZEND_BASE;
			$classFilePathAndName .= implode(array_slice($classNameParts, 1, -1), '/') . '/';
			$classFilePathAndName .= end($classNameParts) . '.php';
		}
		if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
			require_once($classFilePathAndName);
		}
	}

}

?>