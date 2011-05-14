<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Event;
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
 * Erfurt event dispatcher.
 *
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @package    erfurt
 * @subpackage event
 * @version    $$
 * @author     Michael Haschke
 * @author     Norman Heino <norman.heino@gmail.com>
 * @author     Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
class Dispatcher implements \Erfurt\Singleton {

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	/**
	 * @var string
	 */
	const INIT_VALUE = '__init_value';

	/**
	 * Handler priority if none is given
	 * @var int
	 */
	const DEFAULT_PRIORITY = 10;

	/**
	 * @var Zend_Logger
	 */
	protected $logger = null;

	/**
	 * @var array
	 */
	protected $handlerInstances = array();

	/**
	 * @var array
	 */
	protected $registeredEvents = array();

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
		$this->logger = $this->knowledgeBase->getLog();
	}

	/**
	 * Binds an event handler (class or object) to a specified event.
	 *
	 * The handler can be an object that handles the event via a similarly
	 * called method or an array. In case of an array, the following keys must
	 * be set:
	 * - class_name: the name of the handler class
	 * - include_path: path where the class' implementation file can be found
	 * - method_name: optional. specifies the method that handles the event.
	 *				Same as the event name by default.
	 *
	 * @param string $eventName
	 * @param object|array $handler
	 */
	public function register($eventName, $handler, $priority = self::DEFAULT_PRIORITY) {
		// create event if not already handled
		if (!array_key_exists($eventName, $this->registeredEvents)) {
			$this->registeredEvents[$eventName] = array();
		}

		if (is_object($handler)) {
			// simply store handling object
			$this->registerHandler($eventName, $handler, $priority);
			$this->logger->info('Dispatcher: ' . get_class($handler) . " registered for event '$eventName'");
		} else {
			if (is_array($handler)) {
				// or check mandatory parameters
				if (!array_key_exists('class_name', $handler)) {
					throw new \Erfurt\Exception("Missing key 'class_name' for handler registration.");
				}

				if (!array_key_exists('include_path', $handler)) {
					throw new \Erfurt\Exception("Missing key 'include_path' for handler registration.");
				}

				// and add handler class info
				$this->registerHandler($eventName, $handler, $priority);
				$this->logger->info('Dispatcher: ' . $handler['class_name'] . " registered for event '$eventName'");
			}
		}
		// var_dump($this->_registeredEvents);

		return $this;
	}

	/**
	 * Triggers the specified event, thereby invoking all registered observers.
	 *
	 * @param string $eventName
	 * @param event parameters
	 */
	public function trigger(Event $event) {
		$eventName = $event->getName();
		$result = self::INIT_VALUE;

		if (array_key_exists($eventName, $this->registeredEvents)) {
			ksort($this->registeredEvents[$eventName]);
			foreach ($this->registeredEvents[$eventName] as &$handler) {
				if (is_array($handler)) {
					// handler is already instantiated
					if (isset($handler['instance']) && is_object($handler['instance'])) {
						$handlerObject = $handler['instance'];
					} else {
						// observer is an array, try to load class
						if (!class_exists($handler['class_name'], false)) {
							$pathSpec = rtrim($handler['include_path'], '/\\')
										. DIRECTORY_SEPARATOR
										. $handler['file_name'];
							include_once $pathSpec;
						}

						// instantiate handler
						$handlerObject = $this->getHandlerInstance(
							$handler['class_name'], // class name
							$handler['include_path'], // plug-in root
							$handler['config']); // private config

						//TODO check usage of this duplicated config property
						//if (isset($handler['config'])) {
						//$handlerObject->config = $handler['config'];
						//}
					}
				} else {
					if (is_object($handler)) {
						$handlerObject = $handler;
						$handler = array();
					}
				}

				if (is_object($handlerObject)) {
					// use event name as handler method if not specified otherwise
					if (array_key_exists('method_name', $handler)) {
						$handlerMethod = $handler['method_name'];
					} else {
						$handlerMethod = $eventName;
					}
					// let's see if it handles the event
					if (method_exists($handlerObject, $eventName)) {
						// invoke event method
						$reflectionMethod = new \ReflectionMethod(get_class($handlerObject), $handlerMethod);

						// get result of current handler
						$tempResult = $reflectionMethod->invoke($handlerObject, $event);

						if (null !== $tempResult) {
							$event->setValue($tempResult);

							if (is_array($tempResult)) {
								if ($result === self::INIT_VALUE) {
									$result = $tempResult;
								} else {
									if (is_array($result)) {
										// If multiple plugins return an array, we merge them.
										$result = array_merge($result, $tempResult);
									} else {
										// If another plugin returned something else, we convert to an array...
										$result = array_merge(array($result), $tempResult);
									}
								}
							} else {
								// TODO: Support for chaining multiple plugin results that are no arrays?
								$result = $tempResult;
							}
						}
					}
				} else {
					// TODO: throw exception or log error?
				}

				$handler['instance'] = $handlerObject;
			}
		}

		// check whether event has been handled
		// and set handled flag and default value
		if ($result !== self::INIT_VALUE) {
			$event->setHandled(true);
		} else {
			$result = $event->getDefault();
			$event->setHandled(false);
		}

		return $result;
	}

	protected function registerHandler($eventName, $handler, $priority) {
		while (isset($this->registeredEvents[$eventName][$priority])) {
			$priority++;
		}

		$this->registeredEvents[$eventName][$priority] = $handler;
	}

	/**
	 * Returns a previously created instance of a handler class or
	 * instantiates one if necessary.
	 *
	 * @param string $className
	 */
	protected function getHandlerInstance($className, $root, $config) {
		if (!array_key_exists($className, $this->handlerInstances)) {
			$this->handlerInstances[$className] = new $className($root, $config);
		}

		return $this->handlerInstances[$className];
	}

	/**
	 * Returns the instance of a given plugin, if such a plugin is registered and was
	 * already handled. In the case no such plugin exists or was instanciated, this
	 * method returns false.
	 *
	 * @param string $pluginName
	 * @return Erfurt_Plugin
	 */
	public function getPluginInstance($pluginName) {
//		$className = ucfirst($pluginName) . Erfurt_Plugin_Manager::PLUGIN_CLASS_POSTFIX;
//
//		if (array_key_exists($className, $this->_handlerInstances)) {
//			return $this->_handlerInstances[$className];
//		} else {
//			return false;
//		}
		// TODO reimplement
		return false;
	}

}

?>