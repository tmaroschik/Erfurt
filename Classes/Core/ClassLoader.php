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
	 * An array of \Erfurt\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	public function __construct() {
		require EF_PATH_FRAMEWORK . 'Resources/PHP/Antlr/antlr.php';
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadClass($className) {
		$classNameParts = explode('\\', $className);
		if (is_array($classNameParts) && $classNameParts[0] === 'Erfurt' && isset($this->packages[$classNameParts[0]])) {
			if ($classNameParts[1] === 'Tests' && $classNameParts[2] === 'Functional') {
				$classFilePathAndName = $this->packages[$classNameParts[1]]->getFunctionalTestsPath();
				$classFilePathAndName .= implode(array_slice($classNameParts, 3, -1), '/') . '/';
				$classFilePathAndName .= end($classNameParts) . '.php';
			} else {
				$classFilePathAndName = $this->packages[$classNameParts[0]]->getClassesPath();
				$classFilePathAndName .= implode(array_slice($classNameParts, 1, -1), '/') . '/';
				$classFilePathAndName .= end($classNameParts) . '.php';
			}
		}

		if (!isset($classFilePathAndName) && $this->packages === array() && $classNameParts[0] === 'Erfurt') {
			$classFilePathAndName = EF_PATH_FRAMEWORK . 'Classes/';
			$classFilePathAndName .= implode(array_slice($classNameParts, 1, -1), '/') . '/';
			$classFilePathAndName .= end($classNameParts) . '.php';
		}

		if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
			require($classFilePathAndName);
		}
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \Erfurt\Package\Package objects
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

}

?>