<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
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
 * This is the bootstrap for the framework. It defines basic environmental conditions.
 *
 * @scope singleton
 * @api
 */
class Bootstrap {

	/**
	 * Required PHP version
	 */
	const MINIMUM_PHP_VERSION = '5.3.2';
	const MAXIMUM_PHP_VERSION = '5.99.9';

	/**
	 * @var \Erfurt\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($context) {
		$this->defineConstants();
		$this->ensureRequiredEnvironment();
	}

	/**
	 * Initializes the framework
	 *
	 * @return void
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 * @api
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializeConfiguration();
		$this->initializeObjectManager();
	}

	/**
	 * Initializes basic framework constants
	 * @return void
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 */
	protected function defineConstants() {
			if (!defined('EF_PATH_FRAMEWORK')) {
			define('EF_PATH_FRAMEWORK', \realpath(__DIR__ . '../../../') . '/');
		}
		if (!defined('ZEND_BASE')) {
			define('ZEND_BASE', \realpath(__DIR__ . '../../../Zend') . '/');
		}
		if (!defined('EF_PATH_CONFIGURATION')) {
			define('EF_PATH_CONFIGURATION', EF_PATH_FRAMEWORK  . 'Configuration/');
		}
	}

	/**
	 * Initializes the class autoloading mechanism
	 *
	 * @return void
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 */
	protected function initializeClassLoader() {
		require(EF_PATH_FRAMEWORK . 'Classes/Core/ClassLoader.php');
		spl_autoload_register(array(new Erfurt\Core\ClassLoader(), 'loadClass'));
	}

	/**
	 * Initializes the Signals and Slots mechanism
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see run()
	 */
	protected function initializeSignalsSlots() {
		$this->signalSlotDispatcher = new \Erfurt\SignalSlot\Dispatcher();
		$signalsSlotsConfiguration = $this->configurationManager->getConfiguration(\Erfurt\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS);
		foreach ($signalsSlotsConfiguration as $signalClassName => $signalSubConfiguration) {
			if (is_array($signalSubConfiguration)) {
				foreach ($signalSubConfiguration as $signalMethodName => $slotConfigurations) {
					$signalMethodName = 'emit' . ucfirst($signalMethodName);
					if (is_array($slotConfigurations)) {
						foreach ($slotConfigurations as $slotConfiguration) {
							if (is_array($slotConfiguration)) {
								if (isset($slotConfiguration[0]) && isset($slotConfiguration[1])) {
									$omitSignalInformation = (isset($slotConfiguration[2])) ? TRUE : FALSE;
									$this->signalSlotDispatcher->connect($signalClassName, $signalMethodName, $slotConfiguration[0], $slotConfiguration[1], $omitSignalInformation);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Checks PHP version and other parameters of the environment
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function ensureRequiredEnvironment() {
		if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
			exit('Erfurt requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
		}
		if (version_compare(PHP_VERSION, self::MAXIMUM_PHP_VERSION, '>')) {
			exit('Erfurt requires PHP version ' . self::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . PHP_VERSION . '. (Error #1172215790)');
		}
		if (version_compare(PHP_VERSION, '6.0.0', '<') && !extension_loaded('mbstring')) {
			exit('Erfurt requires the PHP extension "mbstring" for PHP versions below 6.0.0 (Error #1207148809)');
		}

		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');
		#locale_set_default('en_UK');
		if (ini_get('date.timezone') === '') {
			date_default_timezone_set('Europe/Copenhagen');
		}
		if (ini_get('magic_quotes_gpc') === '1' || ini_get('magic_quotes_gpc') === 'On') {
			exit('Erfurt requires the PHP setting "magic_quotes_gpc" set to Off. (Error #1224003190)');
		}

//		if (!is_dir(ERFURT_PATH_DATA)) {
//			mkdir(ERFURT_PATH_DATA);
//		}
//		if (!is_dir(ERFURT_PATH_DATA . 'Persistent')) {
//			mkdir(ERFURT_PATH_DATA . 'Persistent');
//		}
	}

}

?>