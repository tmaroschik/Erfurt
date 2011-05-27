<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 2 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/copyleft/gpl.html.                      *
 *                                                                        */
/**
 * This is an alternative entry point to the erfurt library
 *
 * @package Semantic
 * @scope singleton
 * @api
 */
class KnowledgeBase implements \Erfurt\Singleton {
	/**
	 * Constant that contains the minimum required php version.
	 * @var string
	 */
	const MIN_PHP_VERSION = '5.2.0';

	/**
	 * Constant that contains the minimum required zend framework version.
	 * @var string
	 */
	const MIN_ZEND_VERSION = '1.5.0';

	// ------------------------------------------------------------------------
	// --- protected properties -------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Contains an instance of the Erfurt access control class.
	 * @var \Erfurt\AccessControl\Standard
	 */
	protected $accessControl;

	/**
	 * Contains an instanciated access control graph.
	 * @var \Erfurt\Domain\Model\Rdf\Graph
	 */
	protected $accessControlGraph;

	/**
	 * @var \Erfurt\Authentication\Authentication
	 */
	protected $authentication;

	/**
	 * Contains the cache object.
	 * @var \Zend_Cache_Core
	 */
	protected $cache;

	/**
	 * Contains the cache backend.
	 * @var \Zend_Cache_Backend
	 */
	protected $cacheBackend;

	/**
	 * Contains an instance of the configuration object.
	 * @var \Zend_Config
	 */
	protected $configuration;

	/**
	 * @var \Erfurt\Configuration\AccessControlConfiguration
	 */
	protected $accessControlConfiguration;

	/**
	 * @var \Erfurt\Configuration\AuthenticationConfiguration
	 */
	protected $authenticationConfiguration;

	/**
	 * @var \Erfurt\Configuration\CacheConfiguration
	 */
	protected $cacheConfiguration;

	/**
	 * @var \Erfurt\Configuration\GeneralConfiguration
	 */
	protected $generalConfiguration;

	/**
	 * @var \Erfurt\Configuration\NamespacesConfiguration
	 */
	protected $namespacesConfiguration;

	/**
	 * @var \Erfurt\Configuration\SessionConfiguration
	 */
	protected $sessionConfiguration;

	/**
	 * @var \Erfurt\Configuration\StoreConfiguration
	 */
	protected $storeConfiguration;

	/**
	 * @var \Erfurt\Configuration\SystemOntologyConfiguration
	 */
	protected $systemOntologyConfiguration;

	/**
	 * @var \Erfurt\Configuration\IriConfiguration
	 */
	protected $iriConfiguration;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Namespace management module
	 * @var Erfurt_Namespaces
	 */
	protected $namespaces;

	/**
	 * Contains the query cache object.
	 * @var \Erfurt\Cache\Frontend\QueryCache
	 */
	protected $queryCache;

	/**
	 * Contains the query cache backend.
	 * @var \Erfurt\Cache\Backend\QueryCache\Backend
	 */
	protected $queryCacheBackend;

	/**
	 * Contains an instance of the store.
	 * @var \Erfurt\Store\Store
	 */
	protected $store;

	/**
	 * Contains an instanciated system ontology graph.
	 * @var \Erfurt\Domain\Model\Rdf\Graph
	 */
	protected $systemOntologyGraph;

	/**
	 * Contains an instance of the Erfurt versioning class.
	 *
	 * @var Erfurt_Versioning
	 */
	protected $versioning;

	/**
	 * Override Erfurt App constructor
	 */
	public function __construct() {

	}

	/**
	 * Injector method for a \Erfurt\Authentication\Authentication
	 *
	 * @var \Erfurt\Authentication\Authentication
	 */
	public function injectAuthentication(\Erfurt\Authentication\Authentication $authentication) {
		$this->authentication = $authentication;
	}

	/**
	 * Injector method for a AccessControlConfiguration
	 *
	 * @var \Erfurt\Configuration\AccessControlConfiguration
	 */
	public function injectAccessControlConfiguration(Configuration\AccessControlConfiguration $accessControlConfiguration) {
		$this->accessControlConfiguration = $accessControlConfiguration;
	}

	/**
	 * Injector method for a AuthenticationConfiguration
	 *
	 * @var \Erfurt\Configuration\AuthenticationConfiguration
	 */
	public function injectAuthenticationConfiguration(Configuration\AuthenticationConfiguration $authenticationConfiguration) {
		$this->authenticationConfiguration = $authenticationConfiguration;
	}

	/**
	 * Injector method for a CacheConfiguration
	 *
	 * @var \Erfurt\Configuration\CacheConfiguration
	 */
	public function injectCacheConfiguration(Configuration\CacheConfiguration $cacheConfiguration) {
		$this->cacheConfiguration = $cacheConfiguration;
	}

	/**
	 * Injector method for a GeneralConfiguration
	 *
	 * @var \Erfurt\Configuration\GeneralConfiguration
	 */
	public function injectGeneralConfiguration(Configuration\GeneralConfiguration $generalConfiguration) {
		$this->generalConfiguration = $generalConfiguration;
	}

	/**
	 * Injector method for a NamespacesConfiguration
	 *
	 * @var \Erfurt\Configuration\NamespacesConfiguration
	 */
	public function injectNamespacesConfiguration(Configuration\NamespacesConfiguration $namespacesConfiguration) {
		$this->namespacesConfiguration = $namespacesConfiguration;
	}

	/**
	 * Injector method for a SessionConfiguration
	 *
	 * @var \Erfurt\Configuration\SessionConfiguration
	 */
	public function injectSessionConfiguration(Configuration\SessionConfiguration $sessionConfiguration) {
		$this->sessionConfiguration = $sessionConfiguration;
	}

	/**
	 * Injector method for a StoreConfiguration
	 *
	 * @var \Erfurt\Configuration\StoreConfiguration
	 */
	public function injectStoreConfiguration(Configuration\StoreConfiguration $storeConfiguration) {
		$this->storeConfiguration = $storeConfiguration;
	}

	/**
	 * Injector method for a SystemOntologyConfiguration
	 *
	 * @var \Erfurt\Configuration\SystemOntologyConfiguration
	 */
	public function injectSystemOntologyConfiguration(Configuration\SystemOntologyConfiguration $systemOntologyConfiguration) {
		$this->systemOntologyConfiguration = $systemOntologyConfiguration;
	}

	/**
	 * Injector method for a IriConfiguration
	 *
	 * @var \Erfurt\Configuration\IriConfiguration
	 */
	public function injectIriConfiguration(Configuration\IriConfiguration $iriConfiguration) {
		$this->iriConfiguration = $iriConfiguration;
	}

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injector method for a \Erfurt\Store\Store
	 *
	 * @var \Erfurt\Store\Store
	 */
	public function injectStore(\Erfurt\Store\Store $store) {
		$this->store = $store;
	}

	/**
	 * Starts the application, which initializes it.
	 *
	 * @param Zendconfiguration|NULL $config An optional config object that will be merged with
	 * the Erfurt config.
	 *
	 * @return \Erfurt\KnowledgeBase
	 * @throws \Erfurt\Exception( Throws an exception if the connection to the backend server fails.
	 */
	protected function initializeObject() {
		// Check for debug mode.
		$configuration = $this->getConfiguration();
		// Set the configured time zone.
		if (isset($configuration->timezone) && ((boolean)$configuration->timezone !== false)) {
			date_default_timezone_set($configuration->timezone);
		} else {
			date_default_timezone_set('Europe/Berlin');
		}
		// Starting Versioning
		try {
			$versioning = $this->getVersioning();
			if ((boolean)$configuration->versioning === false) {
				$versioning->enableVersioning(false);
			}
		}
		catch (\Erfurt\Exception $e) {
			throw new \Erfurt\Exception($e->getMessage());
		}
		return $this;
	}

	/**
	 * Adds a new OpenID user to the store.
	 *
	 * @param string $openid
	 * @param string $email
	 * @param string $label
	 * @param string|NULL $group
	 * @return boolean
	 */
	public function addOpenIdUser($openid, $email = '', $label = '', $group = '') {
		$acGraph = $this->getAccessControlGraph();
		$acGraphIri = $acGraph->getGraphIri();
		$store = $acGraph->getStore();
		$userIri = urldecode($openid);
		// iri rdf:type sioc:User
		$store->addStatement(
			$acGraphIri,
			$userIri,
			\Erfurt\Vocabulary\Rdf::TYPE,
			array(
				 'value' => $this->configuration->ac->user->class,
				 'type' => 'iri'
			),
			false
		);
		if (!empty($email)) {
			// Check whether email already starts with mailto:
			if (substr($email, 0, 7) !== 'mailto:') {
				$email = 'mailto:' . $email;
			}
			// iri sioc:mailbox email
			$store->addStatement(
				$acGraphIri,
				$userIri,
				$this->configuration->ac->user->mail,
				array(
					 'value' => $email,
					 'type' => 'iri'
				),
				false
			);
		}
		if (!empty($label)) {
			// iri rdfs:label $label
			$store->addStatement(
				$acGraphIri,
				$userIri,
				\Erfurt\Vocabulary\Rdfs::LABEL,
				array(
					 'value' => $label,
					 'type' => 'literal'
				),
				false
			);
		}
		if (!empty($group)) {
			$store->addStatement(
				$acGraphIri,
				$group,
				$this->configuration->ac->group->membership,
				array(
					 'value' => $userIri,
					 'type' => 'iri'
				),
				false
			);
		}
		return true;
	}

	/**
	 * Adds a new user to the store.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @param string|NULL $userGroupIri
	 * @return boolean
	 */
	public function addUser($username, $password, $email, $userGroupIri = '') {
		$acGraph = $this->getAccessControlGraph();
		$acGraphIri = $acGraph->getGraphIri();
		$store = $acGraph->getStore();
		$userIri = $acGraphIri . urlencode($username);
		$store->addStatement(
			$acGraphIri,
			$userIri,
			\Erfurt\Vocabulary\Rdf::TYPE,
			array(
				 'value' => $this->configuration->ac->user->class,
				 'type' => 'iri'
			),
			false
		);
		$store->addStatement(
			$acGraphIri,
			$userIri,
			$this->configuration->ac->user->name,
			array(
				 'value' => $username,
				 'type' => 'literal',
				 'datatype' => Erfurt\Vocabulary\Xsd::NS . 'string'
			),
			false
		);
		// Check whether email already starts with mailto:
		if (substr($email, 0, 7) !== 'mailto:') {
			$email = 'mailto:' . $email;
		}
		$store->addStatement(
			$acGraphIri,
			$userIri,
			$this->configuration->ac->user->mail,
			array(
				 'value' => $email,
				 'type' => 'iri'
			),
			false
		);
		$store->addStatement(
			$acGraphIri,
			$userIri,
			$this->configuration->ac->user->pass,
			array(
				 'value' => sha1($password),
				 'type' => 'literal'
			),
			false
		);
		if (!empty($userGroupIri)) {
			$store->addStatement(
				$acGraphIri,
				$userGroupIri,
				$this->configuration->ac->group->membership,
				array(
					 'value' => $userIri,
					 'type' => 'iri'
				),
				false
			);
		}
		return true;
	}

	/**
	 * Authenticates a user with a given username and password.
	 *
	 * @param string $username
	 * @param string $password
	 * @return Zendauthentication_Result
	 */
	public function authenticate($username = 'Anonymous', $password = '') {
		// Set up the authentication adapter.
		$adapter = $this->objectManager->create('Erfurt\Authentication\Adapter\Typo3', $username, $password);
		// Attempt authentication, saving the result.
		$result = $this->getAuthentication()->authenticate($adapter);
		// If the result is not valid, make sure the identity is cleared.
		if (!$result->isValid()) {
			$this->getAuthentication()->clearIdentity();
		}
		return $result;
	}

	/**
	 * @param string $get
	 * @param string $redirectUrl
	 * @return \Zend_Auth_Result
	 */
	public function authenticateWithFoafSsl($get = NULL, $redirectUrl = NULL) {
		// Set up the authentication adapter.
		$adapter = $this->objectManager->create(\Erfurt\Authentication\Adapter\FoafSsl, $get, $redirectUrl);
		// Attempt authentication, saving the result.
		$result = $this->getAuthentication()->authenticate($adapter);
		// If the result is not valid, make sure the identity is cleared.
		if (!$result->isValid()) {
			$this->getAuthentication()->clearIdentity();
		}
		return $result;
	}

	/**
	 * The second step of the OpenID authentication process.
	 * Authenticates a user with a given OpenID. On success this
	 * method will not return but instead redirect the user to the
	 * specified URL.
	 *
	 * @param string $openId
	 * @param string $redirectUrl
	 * @return \Zend_Auth_Result
	 */
	public function authenticateWithOpenId($openId, $verifyUrl, $redirectUrl) {
		$adapter = new Erfurtauthentication_Adapter_OpenId($openId, $verifyUrl, $redirectUrl);
		$result = $this->getAuthentication()->authenticate($adapter);
		// If we reach this point, something went wrong with the authentication process...
		// So we always clear the identity.
		$this->getAuthentication()->clearIdentity();
		return $result;
	}

	/**
	 * Returns an instance of the access control class.
	 *
	 * @return \Erfurt\AccessControl\Standard
	 */
	public function getAccessControl() {
		if (NULL === $this->accessControl) {
			$this->accessControl = $this->objectManager->create('\Erfurt\AccessControl\Standard');
		}
		return $this->accessControl;
	}

	public function setAccessControl($accessControl) {
		$this->accessControl = $accessControl;
	}

	/**
	 * Returns an instance of the access control graph.
	 *
	 * @return \Erfurt\Domain\Model\Rdf\Graph
	 */
	public function getAccessControlGraph() {
		if (NULL === $this->accessControlGraph) {
			$this->accessControlGraph = $this->getStore()
					->getGraph($this->getAccessControlConfiguration()->graphIri, false);
		}

		return $this->accessControlGraph;
	}

	/**
	 * Convenience shortcut for Ac_Default::getActionConfig().
	 *
	 * @param string $actionSpec The action to get the configuration for.
	 * @return array Returns the configuration for the given action.
	 */
	public function getActionConfig($actionSpec) {
		return $this->getAccessControl()->getActionConfig($actionSpec);
	}

	/**
	 * Returns a caching instance.
	 *
	 * @return \Zend_Cache_Core
	 */
	public function getCache() {
		if (NULL === $this->cache) {
			if (!isset($this->getCacheConfiguration()->lifetime) || ($this->getCacheConfiguration()->lifetime == -1)) {
				$lifetime = NULL;
			} else {
				$lifetime = $this->getCacheConfiguration()->lifetime;
			}
			$frontendOptions = array(
				'lifetime' => $lifetime,
				'automatic_serialization' => true
			);
			$this->cache = $this->objectManager->create('\Erfurt\Cache\Frontend\ObjectCache', $frontendOptions);
			$backend = $this->getCacheBackend();
			$this->cache->setBackend($backend);
		}
		return $this->cache;
	}

	/**
	 * Returns a directory, which can be used for file-based caching.
	 * If no such (writable) directory is found, false is returned.
	 *
	 * @return string|false
	 */
	public function getCacheDir() {
		if (isset($this->getCacheConfiguration()->path)) {
			$matches = array();
			if (!(preg_match('/^(\w:[\/|\\\\]|\/)/', $this->getCacheConfiguration()->path, $matches) === 1)) {
				$this->getCacheConfiguration()->path = EF_PATH_FRAMEWORK . $this->getCacheConfiguration()->path;
			}
			if (is_writable($this->getCacheConfiguration()->path)) {
				return $this->getCacheConfiguration()->path;
			} else {
				// Should throw an exception.
				return false;
				//return $this->getTmpDir();
			}
		} else {
			return false;
			//return $this->getTmpDir();
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Zend_Config
	 * @throws \Erfurt\Exception( Throws an exception if no config is loaded.
	 */
	public function getConfiguration() {
		if (NULL === $this->configuration) {
			throw new Exception\ConfigurationNotLoadedException('Configuration was not loaded.', 1302769700);
		} else {
			return $this->configuration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\AccessControlConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getAccessControlConfiguration() {
		if (NULL === $this->accessControlConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Access Control Configuration was not loaded.', 1303200116);
		} else {
			return $this->accessControlConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\AuthenticationConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getAuthenticationConfiguration() {
		if (NULL === $this->authenticationConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Authentication Configuration was not loaded.', 1303200166);
		} else {
			return $this->authenticationConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\CacheConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getCacheConfiguration() {
		if (NULL === $this->cacheConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Cache Configuration was not loaded.', 1303200192);
		} else {
			return $this->cacheConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\GeneralConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getGeneralConfiguration() {
		if (NULL === $this->generalConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Cache Configuration was not loaded.', 1304403865);
		} else {
			return $this->generalConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\NamespacesConfguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getNamespacesConfiguration() {
		if (NULL === $this->namespacesConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Namespaces Configuration was not loaded.', 1302772392);
		} else {
			return $this->namespacesConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\SessionConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getSessionConfiguration() {
		if (NULL === $this->sessionConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Session Configuration was not loaded.', 1303200235);
		} else {
			return $this->sessionConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\StoreConfguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getStoreConfiguration() {
		if (NULL === $this->storeConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Store Configuration was not loaded.', 1302772396);
		} else {
			return $this->storeConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\SystemOntologyConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getSystemOntologyConfiguration() {
		if (NULL === $this->systemOntologyConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('System Ontology Configuration was not loaded.', 1303200655);
		} else {
			return $this->systemOntologyConfiguration;
		}
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return \Erfurt\Configuration\IriConfiguration
	 * @throws \Erfurt\Exception\ConfigurationNotLoadedException Throws an exception if no config is loaded.
	 */
	public function getIriConfiguration() {
		if (NULL === $this->iriConfiguration) {
			throw new Exception\ConfigurationNotLoadedException('Iri Configuration was not loaded.', 1303203612);
		} else {
			return $this->iriConfiguration;
		}
	}

	/**
	 * Returns the event dispatcher instance.
	 *
	 * @return \Erfurt\Event\Dispatcher
	 */
	public function getEventDispatcher() {
		$eventDispatcher = $this->objectManager->get('\Erfurt\Event\Dispatcher');
		return $eventDispatcher;
	}

	/**
	 * Returns a preconfigured Http_Client
	 *
	 * @param string $iri
	 * @param array $options
	 * @return \Zend_Http_Client
	 */
	public function getHttpClient($iri, $options = array()) {
		$config = $this->getConfig();
		$defaultOptions = array();
		if (isset($config->proxy)) {
			$proxy = $config->proxy;
			if (isset($proxy->host)) {
				$defaultOptions['proxy_host'] = $proxy->host;
				$defaultOptions['adapter'] = '\Zend_Http_Client_Adapter_Proxy';
				if (isset($proxy->port)) {
					$defaultOptions['proxy_port'] = (int)$proxy->port;
				}
				if (isset($proxy->username)) {
					$defaultOptions['proxy_user'] = $proxy->username;
				}
				if (isset($proxy->password)) {
					$defaultOptions['proxy_pass'] = $proxy->password;
				}
			}
		}
		$finalOptions = array_merge($defaultOptions, $options);
		$client = new \Zend_Http_Client($iri, $finalOptions);
		return $client;
	}

	/**
	 * Returns a logging instance. If logging is disabled Zend_Log_Writer_Null is returned,
	 * so it is save to use this object without further checkings. It is possible to use
	 * different logging files for different contexts. Just use an additional identifier.
	 *
	 * @param string $logIdentifier Identifies the logfile (filename without extension).
	 * @return Zend_Log
	 */
	public function getLog($logIdentifier = 'erfurt') {
		return $this->objectManager->get('\Erfurt\Log\NullLogger');
	}

	/**
	 * Returns the namespace management module.
	 *
	 * @return \Erfurt\Namespaces\Namespaces
	 */
	public function getNamespaces() {
		if (NULL === $this->namespaces) {
			// options
			$namespacesOptions = array(
				'standard_prefixes' => ($this->getNamespacesConfiguration() !== NULL) ? $this
						->getNamespacesConfiguration()->toArray() : array(),
				'reserved_names' => isset($this->getIriConfiguration()->schemata) ? $this->getIriConfiguration()->schemata->toArray() : array()
			);
			$this->namespaces = $this->objectManager->create('\Erfurt\Namespaces\Namespaces', $namespacesOptions);
		}
		return $this->namespaces;
	}

	/**
	 * Returns a query cache instance.
	 *
	 * @return \Erfurt\Cache\Frontend\QueryCache
	 */
	public function getQueryCache() {
		if (NULL === $this->queryCache) {
			$this->queryCache = $this->objectManager->create('\Erfurt\Cache\Frontend\QueryCache');
			$backend = $this->getQueryCacheBackend();
			$this->queryCache->setBackend($backend);
		}
		return $this->queryCache;
	}

	/**
	 * Returns a instance of the store.
	 *
	 * @return \Erfurt\Store\Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @var \Erfurt\Store\Store $store
	 */
	public function setStore(\Erfurt\Store\Store $store) {
		$this->store = $store;
	}

	/**
	 * Returns an instance of the system ontology graph.
	 *
	 * @return \Erfurt\Domain\Model\Rdf\Graph
	 */
	public function getSysOntGraph() {
		if (NULL === $this->systemOntologyGraph) {
			$this->systemOntologyGraph = $this
					->getStore()
					->getGraph($this->getSystemOntologyConfiguration()->graphIri, false);
		}
		return $this->systemOntologyGraph;
	}

	/**
	 * Returns a valid tmp folder depending on the OS used.
	 *
	 * @return string
	 */
	public function getTemporaryDirectory() {
		// We use a Zend method here, for it already checks the OS.
		$temp = new \Zend_Cache_Backend();
		return $temp->getTmpDir();
	}

	/**
	 * Convenience shortcut for \Erfurt\Authentication\Adapter\Rdf::getUsers().
	 *
	 * @return array Returns a list of users.
	 */
	public function getUsers() {
		$tempAdapter = $this->objectManager->create('\Erfurt\Authentication\Adapter\Rdf');
		return $tempAdapter->getUsers();
	}

	/**
	 * Returns a versioning instance.
	 *
	 * @return \Erfurt\Versioning\Versioning
	 */
	public function getVersioning() {
		if (NULL === $this->versioning) {
			$this->versioning = $this->objectManager->create('\Erfurt\Versioning\Versioning');
		}
		return $this->versioning;
	}

	/**
	 * Convenience shortcut for Ac_Default::isActionAllowed().
	 *
	 * @param string $actionSpec The action to check.
	 * @return boolean Returns whether the given action is allowed for the current user.
	 */
	public function isActionAllowed($actionSpec) {
		return $this->getAccessControl()->isActionAllowed($actionSpec);
	}

	/**
	 * The third and last step of the OpenID authentication process.
	 * Checks whether the response is a valid OpenID result and
	 * returns the appropriate auth result.
	 *
	 * @param array $get The query part of the authentication request.
	 * @return \Zend_Auth_Result
	 */
	public function verifyOpenIdResult($get) {
		$adapter = $this->objectManager->create('\Erfurt\Authentication\Adapter\OpenId', NULL, NULL, NULL, $get);
		$result = $this->getAuthentication()->authenticate($adapter);
		if (!$result->isValid()) {
			$this->getAuthentication()->clearIdentity();
		}
		return $result;
	}

	/**
	 * Returns a cache backend as configured.
	 *
	 * @return \Zend_Cache_Backend
	 * @throws \Erfurt\Exception(
	 */
	protected function getCacheBackend() {
		if (NULL === $this->cacheBackend) {
			// TODO: fix cache, temporarily disabled
			if (!isset($this->getCacheConfiguration()->enable) || !(boolean)$this->getCacheConfiguration()->enable) {
				$this->cacheBackend = $this->objectManager->create('\Erfurt\Cache\Backend\Null');
			}
				// cache is enabled
			else {
				// check for the cache type and throw an exception if cache type is not set
				if (!isset($this->getCacheConfiguration()->type)) {
					throw new \Erfurt\Exception('Cache type is not set in config.');
				} else {
					// check the type an whether type is supported
					switch (strtolower($this->getCacheConfiguration()->type)) {
						case 'database':
							$this->cacheBackend = $this->objectManager->create('\Erfurt\Cache\Backend\Database');
							break;
						case 'sqlite':
							if (isset($this->getCacheConfiguration()->sqlite->dbname)) {
								$backendOptions = array(
									'cache_db_complete_path' => $this->getCacheDir() . $this->getCacheConfiguration()->sqlite->dbname
								);
							} else {
								throw new \Erfurt\Exception(
									'Cache database filename must be set for sqlite cache backend'
								);
							}
							$this->cacheBackend = new \Zend_Cache_Backend_Sqlite($backendOptions);
							break;
						default:
							throw new \Erfurt\Exception('Cache type is not supported.');
					}
				}
			}
		}
		return $this->cacheBackend;
	}

	/**
	 * Returns a query cache backend as configured.
	 *
	 * @return \Erfurt\Cache\Backend\QueryCache\Backend
	 * @throws \Erfurt\Exception(
	 */
	protected function getQueryCacheBackend() {
		if (NULL === $this->queryCacheBackend) {
			$backendOptions = array();
			if (!isset($this->getCacheConfiguration()->query->enable) || ((boolean)$this->getCacheConfiguration()->query->enable === false)) {
				$this->queryCacheBackend = $this->objectManager->create('\Erfurt\Cache\Backend\QueryCache\Null');
			} else {
				// cache is enabled
				// check for the cache type and throw an exception if cache type is not set
				if (!isset($this->getCacheConfiguration()->query->type)) {
					var_dump($this->getCacheConfiguration());
					throw new \Erfurt\Exception('Cache type is not set in config.');
				} else {
					// check the type an whether type is supported
					switch (strtolower($this->getCacheConfiguration()->query->type)) {
						case 'database':
							$this->queryCacheBackend = $this->objectManager->create('\Erfurt\Cache\Backend\QueryCache\Database');
							break;
						#                       case 'file':
						#                            $this->queryCacheBackend = new Erfurt_Cache_Backend_QueryCache_File();
						#                            break;
						#
						#                       case 'memory':
						#                            $this->queryCacheBackend = new Erfurt_Cache_Backend_QueryCache_Memory();
						#                            break;
						default:
							throw new \Erfurt\Exception('Cache type is not supported.');
					}
				}
			}
		}
		return $this->queryCacheBackend;
	}

}

?>