<?php
declare(ENCODING = 'utf-8') ;
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
 * Internal TYPO3 Dependency Injection container
 *
 * @author Daniel Pötzinger
 * @author Sebastian Kurfürst
 */
class ObjectManager implements ObjectManagerInterface, \Erfurt\Singleton {

	/**
	 * @var array
	 */
	protected $allSettings = array();

	/**
	 * internal cache for classinfos
	 *
	 * @var ClassInfoCache
	 */
	protected $classInfoCache;

	/**
	 * @var string
	 */
	protected $context;

	/**
	 * registered alternative implementations of a class
	 * e.g. used to know the class for a AbstractClass or a Dependency
	 *
	 * @var array
	 */
	protected $alternativeImplementation;

	/**
	 * reference to the classinfofactory, that analyses dependencys
	 *
	 * @var ClassInfoFactory
	 */
	protected $classInfoFactory;

	/**
	 * holds references of singletons
	 *
	 * @var array
	 */
	protected $singletonInstances = array();

	/**
	 * Array of prototype objects currently being built, to prevent recursion.
	 *
	 * @var array
	 */
	protected $prototypeObjectsWhichAreCurrentlyInstanciated;

	/**
	 * Constructor is protected since container should
	 * be a singleton.
	 *
	 * @see getContainer()
	 * @param void
	 * @return void
	 */
	public function __construct($context) {
		$this->context = $context;
		$this->classInfoFactory = new ClassInfoFactory();
	}

	/**
	 * Injector method for a array
	 *
	 * @var array
	 */
	public function injectAllSettings(array $allSettings) {
		$this->allSettings = $allSettings;
	}

	/**
	 * Injector method for a ClassInfoCache
	 *
	 * @var ClassInfoCache
	 */
	public function injectClassInfoCache(ClassInfoCache $classInfoCache) {
		$this->classInfoCache = $classInfoCache;
	}

	/**
	 * Main method which should be used to get an instance of the wished class
	 * specified by $className.
	 *
	 * @param string $className
	 * @param array $givenConstructorArguments the list of constructor arguments as array
	 * @return object the built object
	 */
	public function getInstance($className, $givenConstructorArguments = array()) {
		$this->prototypeObjectsWhichAreCurrentlyInstanciated = array();
		return $this->getInstanceInternal($className, $givenConstructorArguments);
	}

	/**
	 * Internal implementation for getting a class.
	 *
	 * @param string $className
	 * @param array $givenConstructorArguments the list of constructor arguments as array
	 * @return object the built object
	 */
	protected function getInstanceInternal($className, $givenConstructorArguments = array()) {
		if ($className == __CLASS__) {
			return $this;
		}
		$className = $this->getImplementationClassName($className);

		if ($className === 'Container') {
			return $this;
		}

		if (isset($this->singletonInstances[$className])) {
			if (count($givenConstructorArguments) > 0) {
				throw new Exception('Object "' . $className . '" fetched from singleton cache, thus, explicit constructor arguments are not allowed.', 1292857934);
			}
			return $this->singletonInstances[$className];
		}

		$classIsSingleton = $this->isSingleton($className);
		if (!$classIsSingleton) {
			if (array_search($className, $this->prototypeObjectsWhichAreCurrentlyInstanciated) !== FALSE) {
				throw new Exception\CannotBuildObject('Cyclic dependency in prototype object, for class "' . $className . '".', 1295611406);
			}
			$this->prototypeObjectsWhichAreCurrentlyInstanciated[] = $className;
		}

		$classInfo = $this->getClassInfo($className);

		$instance = $this->instanciateObject($className, $classInfo, $givenConstructorArguments);
		$this->injectDependencies($instance, $classInfo);

		if (method_exists($instance, 'initializeObject') && is_callable(array($instance, 'initializeObject'))) {
			$instance->initializeObject();
		}

		if (!$classIsSingleton) {
			array_pop($this->prototypeObjectsWhichAreCurrentlyInstanciated);
		}

		return $instance;
	}

	/**
	 * Instanciates an object, possibly setting the constructor dependencies.
	 * Additionally, directly registers all singletons in the singleton registry,
	 * such that circular references of singletons are correctly instanciated.
	 *
	 * @param string $className
	 * @param ClassInfo $classInfo
	 * @param array $givenConstructorArguments
	 * @return object the new instance
	 */
	protected function instanciateObject($className, ClassInfo $classInfo, array $givenConstructorArguments) {
		$classIsSingleton = $this->isSingleton($className);

		if ($classIsSingleton && count($givenConstructorArguments) > 0) {
			throw new Exception('Object "' . $className . '" has explicit constructor arguments but is a singleton; this is not allowed.', 1292858051);
		}

		$constructorArguments = $this->getConstructorArguments($className, $classInfo->getConstructorArguments(), $givenConstructorArguments);
		array_unshift($constructorArguments, $className);
		$instance = call_user_func_array(array('t3lib_div', 'makeInstance'), $constructorArguments);

		if ($classIsSingleton) {
			$this->singletonInstances[$className] = $instance;
		}
		return $instance;
	}

	/**
	 * Inject setter-dependencies into $instance
	 *
	 * @param object $instance
	 * @param ClassInfo $classInfo
	 * @return void
	 */
	protected function injectDependencies($instance, ClassInfo $classInfo) {
		if (!$classInfo->hasInjectMethods()) {
			return;
		}
		foreach ($classInfo->getInjectMethods() as $injectMethodName => $classNameToInject) {
			if ($injectMethodName == 'injectSettings' && $classNameToInject == 'array') {
				$instance->$injectMethodName($this->allSettings);
			} else {
				$instanceToInject = $this->getInstanceInternal($classNameToInject);
				if ($this->isSingleton($instance) && !($instanceToInject instanceof \Erfurt\Singleton)) {
					$this->log('The singleton "' . $classInfo->getClassName() . '" needs a prototype in "' . $injectMethodName . '". This is often a bad code smell; often you rather want to inject a singleton.', 1);
				}
				$instance->$injectMethodName($instanceToInject);
			}
		}
	}

	/**
	 * Wrapper for dev log, in order to ease testing
	 *
	 * @param	string		Message (in english).
	 * @param	integer		Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
	 * @return	void
	 */
	protected function log($message, $severity) {
		// TODO reenable log
	}

	/**
	 * register a classname that should be used if a dependency is required.
	 * e.g. used to define default class for a interface
	 *
	 * @param string $className
	 * @param string $alternativeClassName
	 */
	public function registerImplementation($className, $alternativeClassName) {
		$this->alternativeImplementation[$className] = $alternativeClassName;
	}

	/**
	 * gets array of parameter that can be used to call a constructor
	 *
	 * @param string $className
	 * @param array $constructorArgumentInformation
	 * @param array $givenConstructorArguments
	 * @return array
	 */
	private function getConstructorArguments($className, array $constructorArgumentInformation, array $givenConstructorArguments) {
		$parameters = array();
		foreach ($constructorArgumentInformation as $argumentInformation) {
			$argumentName = $argumentInformation['name'];
			// We have a dependency we can automatically wire,
			// AND the class has NOT been explicitely passed in
			if (isset($argumentInformation['dependency']) && !(count($givenConstructorArguments) && is_a($givenConstructorArguments[0], $argumentInformation['dependency']))) {
				// Inject parameter
				$parameter = $this->getInstanceInternal($argumentInformation['dependency']);
				if ($this->isSingleton($className) && !($parameter instanceof \Erfurt\Singleton)) {
					$this->log('The singleton "' . $className . '" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.', 1);
				}
			} elseif (count($givenConstructorArguments)) {
				// EITHER:
				// No dependency injectable anymore, but we still have
				// an explicit constructor argument
				// OR:
				// the passed constructor argument matches the type for the dependency
				// injection, and thus the passed constructor takes precendence over
				// autowiring.
				$parameter = array_shift($givenConstructorArguments);
			} elseif (array_key_exists('defaultValue', $argumentInformation)) {
				// no value to set anymore, we take default value
				$parameter = $argumentInformation['defaultValue'];
			} else {
				throw new \InvalidArgumentException('not a correct info array of constructor dependencies was passed!');
			}
			$parameters[] = $parameter;
		}
		return $parameters;
	}

	/**
	 * @param string/object $object
	 * @return boolean TRUE if the object is a singleton, FALSE if it is a prototype.
	 */
	protected function isSingleton($object) {
		return in_array('Erfurt\Singleton', class_implements($object));
	}

	/**
	 * Returns the class name for a new instance, taking into account the
	 * class-extension API.
	 *
	 * @param	string		Base class name to evaluate
	 * @return	string		Final class name to instantiate with "new [classname]"
	 */
	protected function getImplementationClassName($className) {
		if (isset($this->alternativeImplementation[$className])) {
			$className = $this->alternativeImplementation[$className];
		}

		if (substr($className, -9) === 'Interface') {
			$className = substr($className, 0, -9);
		}

		return $className;
	}

	/**
	 * Gets Classinfos for the className - using the cache and the factory
	 *
	 * @param string $className
	 * @return ClassInfo
	 */
	private function getClassInfo($className) {
		// we also need to make sure that the cache is returning a vaild object
		// in case something went wrong with unserialization etc..
		$cachedClassName = str_replace('\\', '-', $className);
		if (!$this->classInfoCache->has($cachedClassName) || !is_object($this->classInfoCache->get($cachedClassName))) {
			$this->classInfoCache->set($cachedClassName, $this->classInfoFactory->buildClassInfoFromClassName($className));
		}
		return $this->classInfoCache->get($cachedClassName);
	}

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
	public function create($objectName) {
		$arguments = func_get_args();
		array_shift($arguments);
		$instance = $this->getInstance($objectName, $arguments);

		if ($instance instanceof \Erfurt\Singleton) {
			throw new Exception\WrongScope('Object "' . $objectName . '" is of not of scope prototype, but only prototype is supported by create()', 1265203124);
		}

		return $instance;
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
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @api
	 */
	public function get($objectName) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->getInstance($objectName, $arguments);
	}
}