<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Wrapper;
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
 * This class acts as the central registry for all active wrapper extensions.
 * It provides functionality for listing all active wrapper extensions and
 * gives access to wrapper instances.
 *
 * @copyright  Copyright (c) 2009 {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @package    erfurt
 * @subpackage wrapper
 * @author     Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
class Registry {

	/**
	 * This static property contains the instance, for this class is realized
	 * following the singleton pattern.
	 *
	 * @var Erfurt_Wrapper_Registry
	 */
	private static $_instance = null;

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * This property contains all registered (active) wrapper.
	 *
	 * @var array
	 */
	protected $_wrapperRegistry = array();

	// ------------------------------------------------------------------------
	// --- Magic methods ------------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * The constructor is private, for this class is a singleton.
	 */
	private function __construct() {
		// Nothing to do here.
	}

	// ------------------------------------------------------------------------
	// --- Public static methods ----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Returns the one and only instance of this class.
	 *
	 * @return Erfurt_Wrapper_Registry
	 */
	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new Erfurt_Wrapper_Registry();
		}

		return self::$_instance;
	}

	/**
	 * Destroys the current instance. Next time getInstance is called a new
	 * instance will be created.
	 */
	public static function reset() {
		if (self::$_instance != null) {
			self::$_instance->_wrapperRegistry = array();
		}
		self::$_instance = null;
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Returns the instanciated wrapper class specified by the given wrapper
	 * name. If no such a wrapper is registered, this method throws an exception.
	 *
	 * @param string $wrapperName
	 * @throws Exception
	 */
	public function getWrapperInstance($wrapperName) {
		if ($wrapperName === 'Test') {
			return new Test();
		}

		if (!isset($this->_wrapperRegistry[$wrapperName])) {
			throw new Exception("A wrapper with name '$wrapperName' has not been registered.");
		}

		if (null === $this->_wrapperRegistry[$wrapperName]['instance']) {
			$pathSpec = rtrim($this->_wrapperRegistry[$wrapperName]['include_path'], '/\\')
						. DIRECTORY_SEPARATOR
						. ucfirst($wrapperName) . 'Wrapper.php';

			$instance = new $this->_wrapperRegistry[$wrapperName]['class_name'];
			$instance->init($this->_wrapperRegistry[$wrapperName]['config']);
			$this->_wrapperRegistry[$wrapperName]['instance'] = $instance;
		}

		return $this->_wrapperRegistry[$wrapperName]['instance'];
	}

	/**
	 * Returns a list containing all active wrapper.
	 *
	 * @return array
	 */
	public function listActiveWrapper() {
		return array_keys($this->_wrapperRegistry);
	}

	/**
	 * Registers a given wrapper within the registry. The wrapper specification
	 * contains the following keys: class_name, include_path, config, instance.
	 *
	 * @param string $wrapperName
	 * @param array $wrapperSpec
	 * @throws Exception
	 */
	public function register($wrapperName, $wrapperSpec) {
		if (isset($this->_wrapperRegistry[$wrapperName])) {
			throw new Exception("A wrapper with name '$wrapperName' has already been registered.");
		}

		$this->_wrapperRegistry[$wrapperName] = $wrapperSpec;
	}

}

?>