<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Configuration;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It has been ported from the corresponding class of the FLOW3           *
 * framework. All credits go to the responsible contributors.             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        */

/**
 * A general purpose configuration manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class ConfigurationManager implements \t3lib_Singleton {

	const CONFIGURATION_TYPE_CACHES = 'Caches';
	const CONFIGURATION_TYPE_POLICY = 'Policy';
	const CONFIGURATION_TYPE_PREFIXES = 'Prefixes';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_SIGNALSSLOTS = 'SignalsSlots';

	/**
	 * The application context of the configuration to manage
	 * @var string
	 */
	protected $context;

	/**
	 * @var \Erfurt\Configuration\Source\YamlSource
	 */
	protected $configurationSource;

	/**
	 * @var \Erfurt\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var string
	 */
	protected $includeCachedConfigurationsPathAndFilename;

	/**
	 * Storage of the raw special configurations
	 * @var array
	 */
	protected $configurations = array(
		self::CONFIGURATION_TYPE_SETTINGS => array(),
	);

	/**
	 * Active packages to load the configuration for
	 * @var array<Erfurt\Package\PackageInterface>
	 */
	protected $packages = array();

	/**
	 * @var boolean
	 */
	protected $cacheNeedsUpdate = FALSE;

	/**
	 * Constructs the configuration manager
	 */
	public function __construct() {
		$this->context = EF_CONTEXT;
		if (!is_dir(EF_PATH_CONFIGURATION . EF_CONTEXT)) {
			\Erfurt\Utility\Files::createDirectoryRecursively(EF_PATH_CONFIGURATION . EF_CONTEXT);
		}
		$this->includeCachedConfigurationsPathAndFilename = EF_PATH_CONFIGURATION . EF_CONTEXT . '/IncludeCachedConfigurations.php';
		$this->loadConfigurationCache();
	}

	/**
	 * Injects the configuration source
	 *
	 * @param \Erfurt\Configuration\Source\YamlSource $configurationSource
	 * @return void
	 */
	public function injectConfigurationSource(\Erfurt\Configuration\Source\YamlSource $configurationSource) {
		$this->configurationSource = $configurationSource;
	}

	/**
	 * Injects the environment
	 *
	 * @param \Erfurt\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\Erfurt\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the active packages to load the configuration for
	 *
	 * @param array<Erfurt\Package\PackageInterface> $packages
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

	/**
	 * Returns the specified raw configuration.
	 * The actual configuration will be merged from different sources in a defined order.
	 *
	 * Note that this is a low level method and only makes sense to be used by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $packageKey Key of the package to return the configuration for
	 * @param array $configurationPath The path of the configuration to extract (e.g. 'FLOW3', 'aop')
	 * @return array The configuration
	 * @throws \Erfurt\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfiguration($configurationType, $packageKey = NULL, array $configurationPath = NULL) {
		$configuration = array();
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
			case self::CONFIGURATION_TYPE_PREFIXES :
				if (!isset($this->configurations[$configurationType])) {
					$this->loadConfiguration($configurationType, $this->packages);
				}
				if (isset($this->configurations[$configurationType])) {
					$configuration = &$this->configurations[$configurationType];
				}
			break;

			case self::CONFIGURATION_TYPE_SETTINGS :
				if ($packageKey === NULL) {
					foreach ($this->packages as $package) {
						if (!isset($this->configurations[self::CONFIGURATION_TYPE_SETTINGS][$package->getPackageKey()])) {
							$this->loadConfiguration($configurationType, $this->packages);
						}
					}
					$configuration = &$this->configurations[self::CONFIGURATION_TYPE_SETTINGS];
					break;
				} else {
					$configuration = &$this->configurations[self::CONFIGURATION_TYPE_SETTINGS][$packageKey];
					break;
				}

			default :
				throw new \Erfurt\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1307365066);
		}
		if ($configurationPath === NULL) {
			return $configuration;
		} else {
			return \Erfurt\Utility\Arrays::getValueByPath($configuration, $configurationPath);
		}
	}

	/**
	 * Sets the specified raw configuration.
	 * Note that this is a low level method and only makes sense to be used by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $configuration The new configuration
	 * @return void
	 * @throws \Erfurt\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConfiguration($configurationType, array $configuration) {
		switch ($configurationType) {
			default :
				throw new \Erfurt\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1251127738);
		}
	}

	/**
	 * Saves configuration of the given configuration type back to the configuration file
	 * (if supported)
	 *
	 * @param string $configurationType The kind of configuration to save - must be one of the supported CONFIGURATION_TYPE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfiguration($configurationType) {
		switch ($configurationType) {
			default :
				throw new \Erfurt\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" does not support saving.', 1251127425);
		}
	}

	/**
	 * Shuts down the configuration manager.
	 * This method writes the current configuration into a cache file if FLOW3 was configured to do so.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdown() {
		if ($this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] === TRUE && $this->cacheNeedsUpdate === TRUE) {
			$this->saveConfigurationCache();
		}
	}

	/**
	 * Loads special configuration defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders. The result is stored
	 * in the configuration manager's configuration registry and can be retrieved with the
	 * getConfiguration() method.
	 *
	 * @param string $configurationType The kind of configuration to load - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $packages An array of Package objects to consider
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function loadConfiguration($configurationType, array $packages) {
		$this->cacheNeedsUpdate = TRUE;

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_SETTINGS :
				if (isset($packages['Erfurt'])) {
					$erfurtPackage = $packages['Erfurt'];
					unset($packages['Erfurt']);
					array_unshift($packages, $erfurtPackage);
				}
				$settings = array();

				foreach ($packages as $package) {
					if (!isset($settings[$package->getPackageKey()])) {
						$settings[$package->getPackageKey()] = array();
					}
					$settings = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . self::CONFIGURATION_TYPE_SETTINGS));
				}
				$settings = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(EF_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_SETTINGS));
				foreach ($packages as $package) {
					$settings = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $this->context . '/' . self::CONFIGURATION_TYPE_SETTINGS));
				}
				$settings = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(EF_PATH_CONFIGURATION . $this->context . '/' . self::CONFIGURATION_TYPE_SETTINGS));

				if (isset($this->configurations[self::CONFIGURATION_TYPE_SETTINGS])) {
					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS] = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[self::CONFIGURATION_TYPE_SETTINGS], $settings);
				} else {
					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS] = $settings;
				}

				if (!isset($this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['Erfurt']['core']['context'])) {
					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['Erfurt']['core']['context'] = $this->context;
				}
			break;
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
			case self::CONFIGURATION_TYPE_PREFIXES :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
				$this->configurations[$configurationType] = array();
				foreach ($packages as $package) {
					$this->configurations[$configurationType] = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
				foreach ($packages as $package) {
					$this->configurations[$configurationType] = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $this->context . '/' . $configurationType));
				}
			break;
			default:
				throw new \Erfurt\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" cannot be loaded with loadConfiguration().', 1251450613);
		}

			// merge in global configuration
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
				$this->configurations[$configurationType] = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(EF_PATH_CONFIGURATION . $configurationType));
				$this->configurations[$configurationType] = \Erfurt\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(EF_PATH_CONFIGURATION . $this->context . '/' . $configurationType));
		}

		$this->postProcessConfiguration($this->configurations[$configurationType]);
	}

	/**
	 * If a cache file with previously saved configuration exists, it is loaded.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function loadConfigurationCache() {
		if (file_exists($this->includeCachedConfigurationsPathAndFilename)) {
			$this->configurations = require($this->includeCachedConfigurationsPathAndFilename);
		}
	}

	/**
	 * Saves the current configuration into a cache file and creates a cache inclusion script
	 * in the context's Configuration directory.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function saveConfigurationCache() {
		$configurationCachePath = $this->environment->getPathToTemporaryDirectory() . 'Configuration/';
		if (!file_exists($configurationCachePath)) {
			\Erfurt\Utility\Files::createDirectoryRecursively($configurationCachePath);
		}
		$cachePathAndFilename = $configurationCachePath  . $this->context . 'Configurations.php';
		$includeCachedConfigurationsCode = <<< "EOD"
<?php
if (file_exists('$cachePathAndFilename')) {
	return require '$cachePathAndFilename';
} else {
	unlink(__FILE__);
	return array();
}
?>
EOD;
		file_put_contents($cachePathAndFilename, '<?php return ' . var_export($this->configurations, TRUE) . '?>');
		file_put_contents($this->includeCachedConfigurationsPathAndFilename, $includeCachedConfigurationsCode);
	}

	/**
	 * Post processes the given configuration array by replacing constants with their
	 * actual value.
	 *
	 * @param array &$configurations The configuration to post process. The results are stored directly in the given array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function postProcessConfiguration(array &$configurations) {
		foreach ($configurations as $key => $configuration) {
			if (is_array($configuration)) {
				$this->postProcessConfiguration($configurations[$key]);
			} elseif (is_string($configuration)) {
				$matches = array();
				preg_match_all('/(?:%)([A-Z_0-9]+)(?:%)/', $configuration, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) $configurations[$key] = str_replace('%' . $match . '%', constant($match), $configurations[$key]);
					}
				}
			}
		}
	}

}

?>