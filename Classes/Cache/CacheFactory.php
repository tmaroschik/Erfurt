<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Cache;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class CacheFactory implements \Erfurt\Singleton {

	/**
	 * The current FLOW3 context ("production", "development" etc.)
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * A reference to the cache manager
	 *
	 * @var \Erfurt\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \Erfurt\Utility\Environment
	 */
	protected $environment;

	/**
	 * Constructs this cache factory
	 *
	 * @param string $context The current FLOW3 context
	 * @param \Erfurt\Cache\CacheManager $cacheManager
	 * @param \Erfurt\Utility\Environment $environment
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context, \Erfurt\Cache\CacheManager $cacheManager, \Erfurt\Utility\Environment $environment) {
		$this->context = $context;
		$this->cacheManager = $cacheManager;
		$this->cacheManager->injectCacheFactory($this);
		$this->environment = $environment;
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheObjectName Object name of the cache frontend
	 * @param string $backendObjectName Object name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return \Erfurt\Cache\Frontend\FrontendInterface The created cache frontend
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = array()) {
		/** @var \Erfurt\Cache\Backend\BackendInterface $backend */
		$backend = new $backendObjectName($this->context, $backendOptions);
		if (!$backend instanceof \Erfurt\Cache\Backend\BackendInterface) {
			throw new \Erfurt\Cache\Exception\InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304301);
		}
		$backend->injectEnvironment($this->environment);
		if (is_callable(array($backend, 'initializeObject'))) {
			$backend->initializeObject(\Erfurt\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
		}

		$cache = new $cacheObjectName($cacheIdentifier, $backend);
		if (!$cache instanceof \Erfurt\Cache\Frontend\FrontendInterface) {
			throw new \Erfurt\Cache\Exception\InvalidCacheException('"' . $cacheObjectName . '" is not a valid cache frontend object.', 1216304300);
		}
		if (is_callable(array($cache, 'initializeObject'))) {
			$cache->initializeObject(\Erfurt\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
		}

		$this->cacheManager->registerCache($cache);
		return $cache;
	}

}
?>