<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Resource;
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
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassLoader {

	/**
	 * Define some global constants
	 */
	public function __construct() {
		if (!defined('EF_BASE')) {
			define('EF_BASE', \t3lib_extmgm::extPath('semantic') . 'Resources/PHP/Erfurt/');
		}
		if (!defined('ZEND_BASE')) {
			define('ZEND_BASE', \t3lib_extmgm::extPath('semantic') . 'Resources/PHP/Zend/');
		}
	}

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
		if (\t3lib_div::isFirstPartOfStr($classNameParts[0], 'Erfurt')) {
			if ($classNameParts[1] !== 'Tests') {
				$classFilePathAndName = \t3lib_extmgm::extPath('semantic') . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 1, -1), '/') . '/';
			} else {
				$classFilePathAndName = \t3lib_extmgm::extPath('semantic') . 'Tests/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
			}
			$classFilePathAndName .= end($classNameParts) . '.php';
		} elseif (\t3lib_div::isFirstPartOfStr($classNameParts[0], 'Zend')) {
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