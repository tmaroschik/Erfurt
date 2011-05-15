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
 * A general purpose configuration manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class ConfigurationManager {

	const CONFIGURATION_TYPE_POLICY = 'Policy';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_SIGNALSSLOTS = 'SignalsSlots';

	/**
	 * The application context of the configuration to manage
	 * @var string
	 */
	protected $context;

	/**
	 * @var \Erfurt\Configuration\Source\SourceInterface
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
	 *
	 * @param string $context The application context to fetch configuration for
	 */
	public function __construct($context) {
		$this->context = $context;
		if (!is_dir(FLOW3_PATH_CONFIGURATION . $context)) {
			\Erfurt\Utility\Files::createDirectoryRecursively(FLOW3_PATH_CONFIGURATION . $context);
		}
		$this->includeCachedConfigurationsPathAndFilename = FLOW3_PATH_CONFIGURATION . $context . '/IncludeCachedConfigurations.php';
		$this->loadConfigurationCache();
	}

	/**
	 * Injects the configuration source
	 *
	 * @param \Erfurt\Configuration\Source\SourceInterface $configurationSource
	 * @return void
	 */
	public function injectConfigurationSource(\Erfurt\Configuration\Source\SourceInterface $configurationSource) {
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