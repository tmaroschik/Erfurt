<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Authentication;
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
 * Extends the Zend_Auth class in order to provide some additional functionality.
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope singleton
 */
class Authentication implements \Erfurt\Singleton {

	/**
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \Erfurt\Configuration\SessionConfiguraion
	 */
	protected $sessionConfiguration;

	/**
	 * @var Storage/StorageInterface
	 */
	protected $storage;

	/**
	 * Make the new operator accessible again
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injector method for a \Erfurt\Configuration\SessionConfiguraion
	 *
	 * @var \Erfurt\Configuration\SessionConfiguraion
	 */
	public function injectSessionConfiguration(\Erfurt\Configuration\SessionConfiguraion $sessionConfiguration) {
		$this->sessionConfiguration = $sessionConfiguration;
	}

	/**
	 * Lifecycle method after all dependencies have been injected
	 */
	public function initializeObject() {
		if (isset($this->sessionConfiguration->identifier)) {
			$sessionNamespace = 'Semantic_Authentication' . $this->sessionConfiguration->identifier;
			$this->setStorage($this->objectManager->create('\Erfurt\Authentication\Storage\Session', $sessionNamespace));
		}
	}

	/**
	 * Authenticates against the supplied adapter
	 *
	 * @param  Adapter\AdapterInterface $adapter
	 * @return Result
	 */
	public function authenticate(Adapter\AdapterInterface $adapter) {
		$result = $adapter->authenticate();

		/**
		 * ZF-7546 - prevent multiple succesive calls from storing inconsistent results
		 * Ensure storage has clean state
		 */
		if ($this->hasIdentity()) {
			$this->clearIdentity();
		}

		if ($result->isValid()) {
			$this->getStorage()->write($result->getIdentity());
		}

		return $result;
	}

	public function setIdentity(Result $authResult) {
		if ($authResult->isValid()) {
			$this->getStorage()->write($authResult->getIdentity());
		}
	}

	public function setUsername($newUsername) {
		$storage = $this->getStorage();
		if ($storage->isEmpty()) {
			return;
		}
		$identity = $storage->read();
		$identity->setUsername($newUsername);
		$storage->write($identity);
	}

	public function setEmail($newEmail) {
		$storage = $this->getStorage();
		if ($storage->isEmpty()) {
			return;
		}
		$identity = $storage->read();
		$identity->setEmail($newEmail);
		$storage->write($identity);
	}

	/**
	 * Returns the persistent storage handler
	 *
	 * Session storage is used by default unless a different storage adapter has been set.
	 *
	 * @return Storage\StorageInterface
	 */
	public function getStorage() {
		if (null === $this->storage) {
			$this->setStorage($this->objectManager->create('\Erfurt\Storage\Session'));
		}
		return $this->storage;
	}

	/**
	 * Sets the persistent storage handler
	 *
	 * @param  Storage\StorageInterface $storage
	 * @return Zend_Auth Provides a fluent interface
	 */
	public function setStorage(Storage\StorageInterface $storage) {
		$this->storage = $storage;
		return $this;
	}

	/**
	 * Returns true if and only if an identity is available from storage
	 *
	 * @return boolean
	 */
	public function hasIdentity() {
		return !$this->getStorage()->isEmpty();
	}

	/**
	 * Returns the identity from storage or null if no identity is available
	 *
	 * @return mixed|null
	 */
	public function getIdentity() {
		$storage = $this->getStorage();

		if ($storage->isEmpty()) {
			return null;
		}

		return $storage->read();
	}

	/**
	 * Clears the identity from persistent storage
	 *
	 * @return void
	 */
	public function clearIdentity() {
		$this->getStorage()->clear();
	}

}

?>