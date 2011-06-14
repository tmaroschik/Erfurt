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
 * TYPO3 Dependency Injection container
 *
 * @author Daniel Pötzinger
 */
class ClassInfoFactory {


	/**
	 * Contains objectConfiguration
	 *
	 * @var array
	 */
	protected $objectConfiguration;

	/**
	 * Injector method for a \Erfurt\Configuration\ConfigurationManager
	 *
	 * @var \Erfurt\Configuration\ConfigurationManager
	 */
	public function injectConfigurationManager(\Erfurt\Configuration\ConfigurationManager $configurationManager) {
		$this->objectConfiguration = $configurationManager->getConfiguration(\Erfurt\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS);
	}

	/**
	 * Factory metod that builds a ClassInfo Object for the given classname - using reflection
	 *
	 * @param string $className The class name to build the class info for
	 * @return ClassInfo the class info
	 */
	public function buildClassInfoFromClassName($className) {
		try {
			$reflectedClass = new \ReflectionClass($className);
		} catch (Exception $e) {
			throw new Exception\UnknownObjectException('Could not analyse class:' . $className . ' maybe not loaded or no autoloader?', 1289386765);
		}
		$objectConfiguration = $this->getObjectConfiguration($className);
		$constructorArguments = $this->getConstructorArguments($reflectedClass);
		$injectMethods = $this->getInjectMethods($reflectedClass);
		if (!empty($objectConfiguration)) {
			die();
		} else {
			return new ClassInfo($className, $constructorArguments, $injectMethods);
		}
	}

	protected function getObjectConfiguration($objectClassName) {
		$objectConfiguration = Array();
		// Merge configuration of all defined parent objects and interfaces
		$classNames = array_merge(array($objectClassName), class_parents($objectClassName), class_implements($objectClassName));
		$classNames = array_reverse($classNames);
		foreach ($classNames as $className) {
			if (isset($this->objectConfiguration[$className])) {
				$objectConfiguration = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($objectConfiguration, $this->objectConfiguration[$className]);
			}
		}
		return $objectConfiguration;
	}

	/**
	 * Build a list of constructor arguments
	 *
	 * @param \ReflectionClass $reflectedClass
	 * @return array of parameter infos for constructor
	 */
	private function getConstructorArguments(\ReflectionClass $reflectedClass) {
		$reflectionMethod = $reflectedClass->getConstructor();
		if (!is_object($reflectionMethod)) {
			return array();
		}
		$result = array();
		foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
			/* @var $reflectionParameter ReflectionParameter */
			$info = array();

			$info['name'] = $reflectionParameter->getName();

			if ($reflectionParameter->getClass()) {
				$info['dependency'] = $reflectionParameter->getClass()->getName();
			}
			if ($reflectionParameter->isOptional()) {
				$info['defaultValue'] = $reflectionParameter->getDefaultValue();
			}

			$result[] = $info;
		}
		return $result;
	}

	/**
	 * Build a list of inject methods for the given class.

	 * @param \ReflectionClass $reflectedClass
	 * @return array (nameOfInjectMethod => nameOfClassToBeInjected)
	 */
	private function getInjectMethods(\ReflectionClass $reflectedClass) {
		$result = array();
		$reflectionMethods = $reflectedClass->getMethods();

		if (is_array($reflectionMethods)) {
			foreach ($reflectionMethods as $reflectionMethod) {
				if ($reflectionMethod->isPublic()
					&& substr($reflectionMethod->getName(), 0, 6) === 'inject'
					&& $reflectionMethod->getName() !== 'injectSettings'
					&& $reflectionMethod->getName() !== 'injectPrefixes') {

					$reflectionParameter = $reflectionMethod->getParameters();
					if (isset($reflectionParameter[0])) {
						if (!$reflectionParameter[0]->getClass()) {
							throw new Exception('Method "' . $reflectionMethod->getName(). '" of class "' . $reflectedClass->getName() . '" is marked as setter for Dependency Injection, but does not have a type annotation');
						}
						$result[$reflectionMethod->getName()] = $reflectionParameter[0]->getClass()->getName();
					}
				} else if (
					$reflectionMethod->getName() == 'injectSettings'
					|| $reflectionMethod->getName() == 'injectPrefixes') {
					$result[$reflectionMethod->getName()] = 'array';
				}
			}
		}
		return $result;
	}
}