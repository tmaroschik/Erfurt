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
 * Erfurt event class
 *
 * @package erfurt
 * @subpackage    event
 * @author     Norman Heino <norman.heino@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Event {

	/**
	 * @var \Erfurt\Event\Dispatcher
	 */
	protected $eventDispatcher = null;

	/**
	 * @var bool
	 */
	protected $handled = false;

	/**
	 * @var string
	 */
	protected $name = null;

	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * The event's current value;
	 * @var mixed
	 */
	protected $value = null;

	/**
	 * Constructor
	 */
	public function __construct($eventName) {
		$this->name = (string)$eventName;
	}

	/**
	 * Injector method for a \Erfurt\Event\Dispatcher
	 *
	 * @var \Erfurt\Event\Dispatcher
	 */
	public function injectEventDispatcher(\Erfurt\Event\Dispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * Returns a property value
	 *
	 * @param string $propertyName
	 */
	public function __get($propertyName) {
		if (isset($this->$propertyName)) {
			return $this->parameters[$propertyName];
		}
	}

	/**
	 * Sets a property
	 *
	 * @param string $propertyName
	 * @param mixed $propertyValue
	 */
	public function __set($propertyName, $propertyValue) {
		$this->parameters[$propertyName] = $propertyValue;

		return $this;
	}

	/**
	 * Returns whether a property with name $propertyName is set.
	 *
	 * @param string $propertyName The property's name
	 *
	 * @return boolean True if the property is set, false otherwise.
	 */
	public function __isset($propertyName) {
		return array_key_exists($propertyName, $this->parameters);
	}

	/**
	 * Returns a default value for the event if one has been set or null.
	 * A default value is used if the event is not handled by any handler.
	 *
	 * @return mixed A default value.
	 */
	public function getDefault() {
		if (null !== $this->default) {
			return $this->default;
		}
	}

	/**
	 * Returns the event name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns this event's parameters all at once.
	 *
	 * @return array An array of parameters.
	 */
	public function getParams() {
		return $this->parameters;
	}

	/**
	 * Returns the current event value, as handled by previous
	 * handlers or null.
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns whether this event has been handled or not.
	 *
	 * @return boolean
	 */
	public function handled() {
		return $this->handled;
	}

	/**
	 * Sets the event's default value.
	 * A default value is used if the event is not handled by any handler.
	 *
	 * @param mixed $default
	 */
	public function setDefault($default) {
		$this->default = $default;

		return $this;
	}

	/**
	 * Sets this event's handled state.
	 *
	 * @param boolean $handlet True if the event has been handled, false otherwise.
	 */
	public function setHandled($handled) {
		$this->handled = (bool)$handled;

		return $this;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * Triggers this event.
	 *
	 * @return mixed Event handler return value
	 */
	public function trigger() {
		return $this->eventDispatcher->trigger($this);
	}
}

?>