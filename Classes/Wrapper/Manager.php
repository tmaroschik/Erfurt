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
 * This class provides functionality in order to scan directories for wrapper
 * extensions.
 *
 * @copyright  Copyright (c) 2009 {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @package    erfurt
 * @subpackage wrapper
 * @author     Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
class Manager {

	// ------------------------------------------------------------------------
	// --- Public constants ---------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Name of the wrapper config file.
	 *
	 * @var string
	 */
	const CONFIG_FILENAME = 'wrapper.ini';
	const CONFIG_LOCAL_FILENAME = 'local.ini';

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * This property defines the section of the configuration file, which is
	 * used for wrapper-internal options.
	 *
	 * @var string
	 */
	protected $_configPrivateSection = 'private';

	/**
	 * This property contains directories, that were already scanned.
	 *
	 * @var array
	 */
	protected $_wrapperPaths = array();

	// ------------------------------------------------------------------------
	// --- Magic methods ------------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * The constructor of this class, which initializes new objects.
	 */
	public function __construct() {

	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Scans a given path and adds the wrapper plugins found in that path.
	 *
	 * @param string $pathSpec
	 */
	public function addWrapperPath($pathSpec) {
		$path = rtrim($pathSpec, '/\\') . DIRECTORY_SEPARATOR;

		if (is_readable($path) && !isset($this->_wrapperPaths[$path])) {
			$this->_wrapperPaths[$path] = true;
			$this->_scanWrapperPath($path);
		}
	}

	// ------------------------------------------------------------------------
	// --- Protected methods --------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Checks a given wrapper name and path pair, whether the specified plugin
	 * is active (a key "enabled" needs to be set to "true").
	 * If a wrapper plugin is not active, the method just returns.
	 * If a wrapper plugin is activated, the method parses the config file and
	 * registers the wrapper within the registry.
	 *
	 * @param string $wrapperName
	 * @param string $wrapperPath
	 */
	protected function _addWrapper($wrapperName, $wrapperPath) {
		$wrapperConfig = parse_ini_file(($wrapperPath . self::CONFIG_FILENAME), true);
		$wrapperPrivateConfigPath = $wrapperPath . self::CONFIG_LOCAL_FILENAME;
		if (is_readable($wrapperPrivateConfigPath)) {
			$wrapperConfig = array_merge($wrapperConfig, parse_ini_file($wrapperPrivateConfigPath, true));
		}

		if (!array_key_exists('enabled', $wrapperConfig) || !(boolean)$wrapperConfig['enabled']) {
			// Wrapper is disabled.
			return;
		}

		if (isset($wrapperConfig[$this->_configPrivateSection])) {
			$privateConfig = new \Zend_Config_Ini(
				$wrapperPath . self::CONFIG_FILENAME,
				$this->_configPrivateSection,
				true
			);
		} else {
			$privateConfig = false;
		}
		if (is_readable($wrapperPrivateConfigPath)) {
			try {
				if (!($privateConfig instanceof \Zend_Config_Ini)) {
					$privateConfig = new \Zend_Config_Ini($wrapperPrivateConfigPath, 'private', true);
				} else {
					$privateConfig = $privateConfig->merge(new \Zend_Config_Ini($wrapperPrivateConfigPath, 'private', true));
				}
			}
			catch (\Zend_Config_Exception $e) {
				// no private config
			}
		}
		$this->addWrapperExternally($wrapperName, $wrapperPath, $privateConfig);
	}

	public function addWrapperExternally($wrapperName, $wrapperPath, $privateConfig) {
		//        if($privateConfig instanceof \Zend_Config){
		//            $privateConfig = $privateConfig->toArray();
		//        }

		$wrapperSpec = array(
			'class_name' => ucfirst($wrapperName) . 'Wrapper',
			'include_path' => $wrapperPath,
			'config' => $privateConfig,
			'instance' => null
		);

		// Finally register the wrapper.
		$registry = Registry::getInstance();
		$registry->register($wrapperName, $wrapperSpec);
	}

	/**
	 * This method iterates through a given directory.
	 *
	 * @var string $pathSpec
	 */
	protected function _scanWrapperPath($pathSpec) {
		$iterator = new \DirectoryIterator($pathSpec);

		foreach ($iterator as $file) {
			if (!$file->isDot() && $file->isDir()) {
				$fileName = $file->getFileName();
				$innerPath = $pathSpec . $fileName . DIRECTORY_SEPARATOR;

				// Iff a config file exists add the wrapper
				if (is_readable(($innerPath . self::CONFIG_FILENAME))) {
					$this->_addWrapper($fileName, $innerPath);
				}
			}
		}
	}
}

?>