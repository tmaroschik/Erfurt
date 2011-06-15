<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Object;
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Extbase Team
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Value object containing the relevant informations for a class,
 * this object is build by the classInfoFactory - or could also be restored from a cache
 *
 * @author Daniel Pötzinger
 */
class ClassInfo {

	const ARGUMENT_TYPES_STRAIGHTVALUE = 0;
	const ARGUMENT_TYPES_OBJECT = 1;
	const ARGUMENT_TYPES_SETTING = 2;

	/**
	 * The classname of the class where the infos belong to
	 * @var string
	 */
	protected $className;

	/**
	 * The constructor Dependencies for the class in the format:
	 * 	 array(
	 *     0 => array( <-- parameters for argument 1
	 *        ARGUMENT_TYPES_* => value/classname/settingspath
	 *     ),
	 *     1 => ...
	 *   )
	 *
	 * @var array
	 */
	protected $constructorArguments;

	/**
	 * The classname of the responsible factory
	 * @var string
	 */
	protected $factoryObjectName;

	/**
	 * All setter injections in the format
	 * 	array (<nameOfMethod> => <classNameToInject> )
	 *
	 * @var array
	 */
	protected $injectMethods;

	/**
	 *
	 * @param string $className
	 * @param array $constructorArguments
	 * @param array $injectMethods
	 */
	public function __construct($className, array $constructorArguments, array $injectMethods, $factoryObjectName = NULL) {
		$this->className = $className;
		$this->constructorArguments = $constructorArguments;
		$this->injectMethods = $injectMethods;
		$this->factoryObjectName = $factoryObjectName;
	}

	/**
	 * @return string $className
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * @return array $constructorArguments
	 */
	public function getConstructorArguments() {
		return $this->constructorArguments;
	}

	/**
	 * @return array $injectMethods
	 */
	public function getInjectMethods() {
		return $this->injectMethods;
	}

	/**
	 * @return array $injectMethods
	 */
	public function hasInjectMethods() {
		return (count($this->injectMethods) > 0);
	}

	/**
	 * @return null|string
	 */
	public function getFactoryObjectName() {
		return $this->factoryObjectName;
	}
}

?>