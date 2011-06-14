<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Core;
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

	// Those are needed before the autoloader is active
require_once(__DIR__ . '/../Utility/Files.php');
require_once(__DIR__ . '/../Package/PackageInterface.php');
require_once(__DIR__ . '/../Package/Package.php');
require_once(__DIR__ . '/../Package/PackageManagerInterface.php');
require_once(__DIR__ . '/../Package/PackageManager.php');
require_once(__DIR__ . '/../Cache/CacheManager.php');

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
	 * Contains cacheFactory
	 *
	 * @var \Erfurt\Cache\CacheFactory
	 */
	protected $cacheFactory;

	/**
	 * Contains cacheManager
	 *
	 * @var \Erfurt\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \Erfurt\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * The application context
	 * @var string
	 */
	protected $context;

	/**
	 * @var \Erfurt\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \Erfurt\Utility\Environment
	 */
	protected $environment;

	/**
	 * Contains objectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * The same instance like $objectManager, but static, for use in the proxy classes.
	 *
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 * @see initializeObjectManager(), getObjectManager()
	 */
	static public $staticObjectManager;

	/**
	 * Contains packageManager
	 *
	 * @var \Erfurt\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * The settings for Erfurt
	 * @var array
	 */
	protected $settings;

	/**
	 * Contains signalSlotDispatcher
	 *
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

		$this->context = $context;
		if ($this->context !== 'Production' && $this->context !== 'Development' && $this->context !== 'Testing') {
			exit('Erfurt: Unknown context "' . $this->context . '" provided, currently only "Production", "Development" and "Testing" are supported. (Error #1254216868)' . PHP_EOL);
		} else {
			define('EF_CONTEXT', $context);
		}

		if ($this->context === 'Testing') {
			require_once('PHPUnit/Autoload.php');
			require_once(EF_PATH_FRAMEWORK . 'Tests/BaseTestCase.php');
//			require_once(EF_PATH_FRAMEWORK . 'Tests/FunctionalTestCase.php');
		}
	}

	/**
	 * Initializes the framework
	 *
	 * @return void
	 * @author Thomas Maroschik <tmroschik@dfau.de>
	 * @api
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializePackageManagement();
		$this->initializeConfiguration();
		$this->initializeSignalsSlots();
		$this->initializeCacheManagement();

		$classInfoCache = new \Erfurt\Object\ClassInfoCache();
		$classInfoCache->injectCache($this->cacheManager->getCache('Erfurt_Object_ClassInfoCache'));
		$this->objectManager = new \Erfurt\Object\ObjectManager($this->context);
		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\Erfurt\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$this->objectManager->injectClassInfoCache($classInfoCache);
		self::$staticObjectManager = $this->objectManager;
	}

	/**
	 * Initializes basic framework constants
	 * @return void
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 */
	protected function defineConstants() {
		if (!defined('EF_PATH_ROOT')) {
			define('EF_PATH_ROOT', \Erfurt\Utility\Files::getUnixStylePath(realpath(__DIR__ . '/../../../../..') . '/'));
		}
		if (!defined('EF_PATH_FRAMEWORK')) {
			define('EF_PATH_FRAMEWORK', \Erfurt\Utility\Files::getUnixStylePath(realpath(__DIR__ . '/../../') . '/'));
		}
		if (!defined('EF_PATH_CONFIGURATION')) {
			define('EF_PATH_CONFIGURATION', EF_PATH_ROOT  . 'Configuration/');
		}
		if (!defined('EF_PATH_DATA')) {
			define('EF_PATH_DATA', EF_PATH_ROOT  . 'Data/');
		}
		if (!defined('EF_PATH_PACKAGES')) {
			define('EF_PATH_PACKAGES', EF_PATH_ROOT  . 'Packages/');
		}
	}

	/**
	 * Initializes the class autoloading mechanism
	 *
	 * @return void
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 */
	protected function initializeClassLoader() {
		/** @define "EF_PATH_FRAMEWORK" ".." */
		require(EF_PATH_FRAMEWORK . 'Classes/Core/ClassLoader.php');
		$this->classLoader = new \Erfurt\Core\ClassLoader();
		spl_autoload_register(array($this->classLoader, 'loadClass'), TRUE, TRUE);
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializePackageManagement() {
		$this->packageManager =  new \Erfurt\Package\PackageManager();
		$this->packageManager->initialize($this);

		$activePackages = $this->packageManager->getActivePackages();

		$this->classLoader->setPackages($activePackages);
	}

	/**
	 * Initializes the configuration manager and the Erfurt settings
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeConfiguration() {
		$this->configurationManager = new \Erfurt\Configuration\ConfigurationManager($this->context);
		$this->configurationManager->injectConfigurationSource(new \Erfurt\Configuration\Source\YamlSource());
		$this->configurationManager->setPackages($this->packageManager->getActivePackages());

		// TODO elaborate why this is needed
		$this->configurationManager->getConfiguration(\Erfurt\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
		$this->settings = $this->configurationManager->getConfiguration(\Erfurt\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Erfurt');

		$this->environment = new \Erfurt\Utility\Environment($this->context);
		$this->environment->setTemporaryDirectoryBase($this->settings['utility']['environment']['temporaryDirectoryBase']);

		$this->configurationManager->injectEnvironment($this->environment);
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
	 * Initializes the cache framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeCacheManagement() {
		$this->cacheManager = new \Erfurt\Cache\CacheManager();
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getConfiguration(\Erfurt\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));

		$this->cacheFactory = new \Erfurt\Cache\CacheFactory($this->context, $this->cacheManager, $this->environment);
	}

	/**
	 * Returns the object manager
	 *
	 * @return \Erfurt\Object\ObjectManager
	 */
	public function getObjectManager() {
		return $this->objectManager;
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

		if (!is_dir(EF_PATH_DATA)) {
			mkdir(EF_PATH_DATA);
		}
		if (!is_dir(EF_PATH_DATA . 'Persistent')) {
			mkdir(EF_PATH_DATA . 'Persistent');
		}
	}

}

?>