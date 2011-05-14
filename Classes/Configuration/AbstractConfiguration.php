<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Configuration;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This class is a port of the Zend_Config class.
 *  All credits go to the zend team.
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
 * Enter descriptions here
 *
 * @package Semantic
 * @scope prototype
 * @api
 */
class AbstractConfiguration implements \Countable, \Iterator {

	/**
	 * This is the key for this configuration inside the extension configuration
	 */
	protected $extensionConfigurationKey = 'general';

	/**
	 * Whether in-memory modifications to configuration data are allowed
	 *
	 * @var boolean
	 */
	protected $allowedModifications;

	/**
	 * Iteration index
	 *
	 * @var integer
	 */
	protected $index;

	/**
	 * Number of elements in configuration data
	 *
	 * @var integer
	 */
	protected $count;

	/**
	 * Contains array of configuration data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Used when unsetting values during iteration to ensure we do not skip
	 * the next element
	 *
	 * @var boolean
	 */
	protected $skipNextIteration;

	/**
	 * Contains which config file sections were loaded. This is null
	 * if all sections were loaded, a string name if one section is loaded
	 * and an array of string names if multiple sections were loaded.
	 *
	 * @var mixed
	 */
	protected $loadedSection;

	/**
	 * This is used to track section inheritance. The keys are names of sections that
	 * extend other sections, and the values are the extended sections.
	 *
	 * @var array
	 */
	protected $extends = array();

	/**
	 * Load file error string.
	 *
	 * Is null if there was no error while file loading
	 *
	 * @var string
	 */
	protected $loadFileErrorString = null;

	/**
	 * Abstract_Configuration provides a property based interface to
	 * an array. The data are read-only unless $allowModifications
	 * is set to true on construction.
	 *
	 * Abstract_Configuration also implements Countable and Iterator to
	 * facilitate easy access to the data.
	 *
	 * @param array $configuration
	 * @param boolean $allowModifications
	 * @return void
	 */
	public function __construct(array $configuration = array(), $allowModifications = false) {
		if (empty($configuration)) {
			$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['semantic']);
			if (isset($extConfig[$this->extensionConfigurationKey . '.']) && is_array($extConfig[$this->extensionConfigurationKey . '.'])) {
				$configurationArray = $extConfig[$this->extensionConfigurationKey . '.'];
				$configuration = \Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($configurationArray);
			}
		}
		$this->allowedModifications = (boolean) $allowModifications;
		$this->loadedSection = null;
		$this->index = 0;
		$this->data = array();
		foreach ($configuration as $key => $value) {
			if (is_array($value)) {
				$this->data[$key] = new self($value, $this->allowedModifications);
			} else {
				$this->data[$key] = $value;
			}
		}
		$this->count = count($this->data);
	}

	/**
	 * Retrieve a value and return $default if there is no element set.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($name, $default = null) {
		$result = $default;
		if (array_key_exists($name, $this->data)) {
			$result = $this->data[$name];
		}
		return $result;
	}

	/**
	 * Magic function so that $obj->value will work.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Only allow setting of a property if $allowModifications
	 * was set to true on construction. Otherwise, throw an exception.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @throws Exception\ReadOnlyException
	 * @return void
	 */
	public function __set($name, $value) {
		if ($this->allowedModifications) {
			if (is_array($value)) {
				$this->data[$name] = new self($value, true);
			} else {
				$this->data[$name] = $value;
			}
			$this->count = count($this->data);
		} else {
			throw new Exception\ReadOnlyException('Configuration is read only,', 1302766356);
		}
	}

	/**
	 * Deep clone of this instance to ensure that nested Abstract_Configurations
	 * are also cloned.
	 *
	 * @return void
	 */
	public function __clone() {
	  $array = array();
	  foreach ($this->data as $key => $value) {
		  if ($value instanceof Abstract_Configuration) {
			  $array[$key] = clone $value;
		  } else {
			  $array[$key] = $value;
		  }
	  }
	  $this->data = $array;
	}

	/**
	 * Return an associative array of the stored data.
	 *
	 * @return array
	 */
	public function toArray() {
		$array = array();
		$data = $this->data;
		foreach ($data as $key => $value) {
			if ($value instanceof Abstract_Configuration) {
				$array[$key] = $value->toArray();
			} else {
				$array[$key] = $value;
			}
		}
		return $array;
	}

	/**
	 * Support isset() overloading on PHP 5.1
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name) {
		return isset($this->data[$name]);
	}

	/**
	 * Support unset()
	 *
	 * @param  string $name
	 * @throws Exception\ReadOnlyException
	 * @return void
	 */
	public function __unset($name) {
		if ($this->allowedModifications) {
			unset($this->data[$name]);
			$this->count = count($this->data);
			$this->skipNextIteration = true;
		} else {
			throw new Exception\ReadOnlyException('Configuration is read only.', 1302766381);
		}

	}

	/**
	 * Defined by Countable interface
	 *
	 * @return int
	 */
	public function count() {
		return $this->count;
	}

	/**
	 * Defined by Iterator interface
	 *
	 * @return mixed
	 */
	public function current() {
		$this->skipNextIteration = false;
		return current($this->data);
	}

	/**
	 * Defined by Iterator interface
	 *
	 * @return mixed
	 */
	public function key() {
		return key($this->data);
	}

	/**
	 * Defined by Iterator interface
	 *
	 */
	public function next() {
		if ($this->skipNextIteration) {
			$this->skipNextIteration = false;
			return;
		}
		next($this->data);
		$this->index++;
	}

	/**
	 * Defined by Iterator interface
	 *
	 */
	public function rewind() {
		$this->skipNextIteration = false;
		reset($this->data);
		$this->index = 0;
	}

	/**
	 * Defined by Iterator interface
	 *
	 * @return boolean
	 */
	public function valid() {
		return $this->index < $this->count;
	}

	/**
	 * Returns the section name(s) loaded.
	 *
	 * @return mixed
	 */
	public function getSectionName() {
		if(is_array($this->loadedSection) && count($this->loadedSection) == 1) {
			$this->loadedSection = $this->loadedSection[0];
		}
		return $this->loadedSection;
	}

	/**
	 * Returns true if all sections were loaded
	 *
	 * @return boolean
	 */
	public function areAllSectionsLoaded() {
		return $this->loadedSection === null;
	}


	/**
	 * Merge another Abstract_Configuration with this one. The items
	 * in $merge will override the same named items in
	 * the current config.
	 *
	 * @param Abstract_Configuration $merge
	 * @return Abstract_Configuration
	 */
	public function merge(Abstract_Configuration $merge) {
		foreach($merge as $key => $item) {
			if(array_key_exists($key, $this->data)) {
				if($item instanceof Abstract_Configuration && $this->$key instanceof Abstract_Configuration) {
					$this->$key = $this->$key->merge(new Abstract_Configuration($item->toArray(), !$this->readOnly()));
				} else {
					$this->$key = $item;
				}
			} else {
				if($item instanceof Abstract_Configuration) {
					$this->$key = new Abstract_Configuration($item->toArray(), !$this->readOnly());
				} else {
					$this->$key = $item;
				}
			}
		}

		return $this;
	}

	/**
	 * Prevent any more modifications being made to this instance. Useful
	 * after merge() has been used to merge multiple Abstract_Configuration objects
	 * into one object which should then not be modified again.
	 *
	 */
	public function setReadOnly() {
		$this->allowedModifications = false;
		foreach ($this->data as $key => $value) {
			if ($value instanceof Abstract_Configuration) {
				$value->setReadOnly();
			}
		}
	}

	/**
	 * Returns if this Abstract_Configuration object is read only or not.
	 *
	 * @return boolean
	 */
	public function readOnly() {
		return !$this->allowedModifications;
	}

	/**
	 * Get the current extends
	 *
	 * @return array
	 */
	public function getExtends() {
		return $this->extends;
	}

	/**
	 * Set an extend for Abstract_Configuration_Writer
	 *
	 * @param  string $extendingSection
	 * @param  string $extendedSection
	 * @return void
	 */
	public function setExtend($extendingSection, $extendedSection = null) {
		if ($extendedSection === null && isset($this->extends[$extendingSection])) {
			unset($this->extends[$extendingSection]);
		} else if ($extendedSection !== null) {
			$this->extends[$extendingSection] = $extendedSection;
		}
	}

	/**
	 * Throws an exception if $extendingSection may not extend $extendedSection,
	 * and tracks the section extension if it is valid.
	 *
	 * @param  string $extendingSection
	 * @param  string $extendedSection
	 * @throws Exception\IllegalCircularInheritanceException
	 * @return void
	 */
	protected function assertValidExtend($extendingSection, $extendedSection) {
		// detect circular section inheritance
		$extendedSectionCurrent = $extendedSection;
		while (array_key_exists($extendedSectionCurrent, $this->extends)) {
			if ($this->extends[$extendedSectionCurrent] == $extendingSection) {
				throw new Exception\IllegalCircularInheritanceException('Illegal circular inheritance detected', 1302766341);
			}
			$extendedSectionCurrent = $this->extends[$extendedSectionCurrent];
		}
		// remember that this section extends another section
		$this->extends[$extendingSection] = $extendedSection;
	}

	/**
	 * Handle any errors from simplexml_load_file or parse_ini_file
	 *
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 */
	protected function loadFileErrorHandler($errno, $errstr, $errfile, $errline) {
		if ($this->loadFileErrorString === null) {
			$this->loadFileErrorString = $errstr;
		} else {
			$this->loadFileErrorString .= (PHP_EOL . $errstr);
		}
	}

	/**
	 * Merge two arrays recursively, overwriting keys of the same name
	 * in $firstArray with the value in $secondArray.
	 *
	 * @param  mixed $firstArray  First array
	 * @param  mixed $secondArray Second array to merge into first array
	 * @return array
	 */
	protected function arrayMergeRecursive($firstArray, $secondArray) {
		if (is_array($firstArray) && is_array($secondArray)) {
			foreach ($secondArray as $key => $value) {
				if (isset($firstArray[$key])) {
					$firstArray[$key] = $this->arrayMergeRecursive($firstArray[$key], $value);
				} else {
					if($key === 0) {
						$firstArray= array(0=>$this->arrayMergeRecursive($firstArray, $value));
					} else {
						$firstArray[$key] = $value;
					}
				}
			}
		} else {
			$firstArray = $secondArray;
		}

		return $firstArray;
	}
}