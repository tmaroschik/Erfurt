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

		$constructorArguments = $this->overrideConstructorArgumentsWithConfiguration($this->getConstructorArguments($reflectedClass), $objectConfiguration);

		$injectMethods = $this->getInjectMethods($reflectedClass);

		if (isset($objectConfiguration['factoryObjectName']) && class_exists($objectConfiguration['factoryObjectName'])) {
			return new ClassInfo($className, $constructorArguments, $injectMethods, $objectConfiguration['factoryObjectName']);
		} else {
			return new ClassInfo($className, $constructorArguments, $injectMethods);
		}
	}

	/**
	 * @param array $constructorArguments
	 * @param array $objectConfiguration
	 * @return array
	 */
	protected function overrideConstructorArgumentsWithConfiguration($constructorArguments, $objectConfiguration) {
		if (isset($objectConfiguration['arguments'])) {
			$constructorArguments = $this->overrideArgumentsWithArgumentsConfiguration($constructorArguments, $objectConfiguration['arguments']);
		}
		return $constructorArguments;
	}

	/**
	 * @param array $arguments
	 * @param array $argumentsConfiguration
	 * @return array
	 */
	protected function overrideArgumentsWithArgumentsConfiguration($arguments, $argumentsConfiguration) {
		foreach ($argumentsConfiguration as $position=>$argumentConfiguration) {
			$index = $position - 1;
			reset($argumentConfiguration);
			$argumentType = key($argumentConfiguration);
			$argumentValue = current($argumentConfiguration);
			switch ($argumentType) {
				case 'setting':
					$arguments[$index] = array(ClassInfo::ARGUMENT_TYPES_SETTING => $argumentValue);
					break;
				case 'value':
					$arguments[$index] = array(ClassInfo::ARGUMENT_TYPES_STRAIGHTVALUE => $argumentValue);
					break;
				case 'object':
					$arguments[$index] = array(ClassInfo::ARGUMENT_TYPES_OBJECT => $argumentValue);
					break;
			}
		}
		return $arguments;
	}

	/**
	 * @param string $objectClassName
	 * @return array
	 */
	protected function getObjectConfiguration($objectClassName) {
		$objectConfiguration = array();
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
	 * Build a list of factory arguments
	 *
	 * @param \ReflectionClass $reflectedClass
	 * @return array of parameter infos for factory
	 */
	protected function getFactoryArguments(\ReflectionClass $reflectedClass) {
		$reflectionMethod = $reflectedClass->getMethod('create');
		if (!is_object($reflectionMethod)) {
			return array();
		}
		return $this->getMethodArguments($reflectionMethod);
	}

	/**
	 * Build a list of constructor arguments
	 *
	 * @param \ReflectionClass $reflectedClass
	 * @return array of parameter infos for constructor
	 */
	protected function getConstructorArguments(\ReflectionClass $reflectedClass) {
		$reflectionMethod = $reflectedClass->getConstructor();
		if (!is_object($reflectionMethod)) {
			return array();
		}
		return $this->getMethodArguments($reflectionMethod);
	}

	/**
	 * Build a list of method arguments
	 *
	 * @param \ReflectionMethod $reflectionMethod
	 * @return array of parameter infos for method
	 */
	protected function getMethodArguments(\ReflectionMethod $reflectionMethod) {
		$result = array();
		foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
			/* @var \ReflectionParameter $reflectionParameter */
			$info = array();

			if ($reflectionParameter->getClass()) {
				$info[ClassInfo::ARGUMENT_TYPES_OBJECT] = $reflectionParameter->getClass()->getName();
			}
			if ($reflectionParameter->isOptional()) {
				$info[ClassInfo::ARGUMENT_TYPES_STRAIGHTVALUE] = $reflectionParameter->getDefaultValue();
			}

			$result[$reflectionParameter->getPosition()] = $info;
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