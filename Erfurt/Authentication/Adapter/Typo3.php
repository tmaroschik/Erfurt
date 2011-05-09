<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Authentication\Adapter;
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
 * RDF authentication adapter.
 *
 * Authenticates a subject via an RDF store using a provided model.
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope singleton
 */
class Typo3 implements AdapterInterface {

	/** @var string */
	protected $username = null;

	/** @var string */
	protected $password = null;

	/** @var string */
	protected $accessModelUri = null;

	/** @var array */
	protected $users = array();

	/** @var boolean */
	protected $userDataFetched = false;

	/** @var string */
	protected $databaseUsername = null;

	/** @var string */
	protected $databasePassword = null;

	/** @var array */
	protected $uris = null;

	protected $loginDisabled = null;

	protected $databaseUserAllowed = null;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \Erfurt\Store\Store
	 */
	protected $store;

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
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
	 * Injector method for a \Erfurt\Store\Store
	 *
	 * @var \Erfurt\Store\Store
	 */
	public function injectStore(\Erfurt\Store\Store $store) {
		$this->store = $store;
	}

	/**
	 * Performs an authentication attempt
	 *
	 * @throws \
	 *
	 *
	 *  If authentication cannot be performed
	 * @return \Erfurt\Authentication\Result
	 */
	public function authenticate() {
		if ($this->isLoginDisabled() === true || $this->username === 'Anonymous' || (TYPO3_MODE == 'FE' && !$GLOBALS["TSFE"]->loginUser && !isset($GLOBALS['BE_USER']))) {
			$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $this->getAnonymousUser());
		} elseif (TYPO3_MODE == 'FE' && $GLOBALS["TSFE"]->loginUser){
			$this->username = $GLOBALS["TSFE"]->fe_user->user['username'];
			$this->password = $GLOBALS["TSFE"]->fe_user->user['password'];
			$identity = $GLOBALS["TSFE"]->fe_user->user;
			$identity['uri']		= '';
			$identity['dbuser']		= false;
			$identity['anonymous']	= false;
			$identityObject = $this->objectManager->create('\Erfurt\Authentication\Identity', $identity);
			$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $identityObject);
		} elseif (isset($GLOBALS['BE_USER'])) {
			if ($GLOBALS['BE_USER']->isAdmin()) {
				$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $this->getSuperAdmin());
			} else {
				$this->username = $GLOBALS['BE_USER']->user['username'];
				$this->password = $GLOBALS['BE_USER']->user['password'];
				$identity = $GLOBALS['BE_USER']->user;
				$identity['uri']		= '';
				$identity['dbuser']		= false;
				$identity['anonymous']	= false;
				$identityObject = $this->objectManager->create('\Erfurt\Authentication\Identity', $identity);
				$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $identityObject);
			}
		} else {
			$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::FAILURE, null, array('No user either Frontend or Backend given.'));
		}

		//Erfurt_App::getInstance()->getAc()->init();
		return $authResult;
	}

	/**
	 * Returns the anonymous user details.
	 *
	 * @return array
	 */
	protected function getAnonymousUser() {
		$uris = $this->getUris();

		$user = array(
			'username' => 'Anonymous',
			'uri' => $uris['user_anonymous'],
			'dbuser' => false,
			'email' => '',
			'anonymous' => true
		);

		$identityObject = $this->objectManager->create('\Erfurt\Authentication\Identity', $user);

		return $identityObject;
	}

	/**
	 * Returns the super admin (db user) details
	 *
	 * @return array
	 */
	protected function getSuperAdmin() {
		$uris = $this->getUris();

		$user = array(
			'username' => 'SuperAdmin',
			'uri' => $uris['user_superadmin'],
			'dbuser' => true,
			'email' => '',
			'anonymous' => false
		);

		$identityObject = $this->objectManager->create('\Erfurt\Authentication\Identity', $user);

		return $identityObject;
	}

	protected function getStore() {
		return $this->store;
	}

	protected function accessModelUri() {
		if (null === $this->accessModelUri) {
			$this->accessModelUri = $this->knowledgeBase->getAccessControlConfiguration()->modelUri;
		}

		return $this->accessModelUri;
	}

	protected function getUris() {
		if (null === $this->uris) {
			$accessControlConfiguration = $this->knowledgeBase->getAccessControlConfiguration();
			$this->uris = array(
				'user_class' => $accessControlConfiguration->user->class,
				'user_username' => $accessControlConfiguration->user->name,
				'user_password' => $accessControlConfiguration->user->pass,
				'user_mail' => $accessControlConfiguration->user->mail,
				'user_superadmin' => $accessControlConfiguration->user->superAdmin,
				'user_anonymous' => $accessControlConfiguration->user->anonymousUser,
				'action_deny' => $accessControlConfiguration->action->deny,
				'action_login' => $accessControlConfiguration->action->login
			);
		}

		return $this->uris;
	}

	protected function isLoginDisabled() {
		if (null === $this->loginDisabled) {
			if (isset($this->knowledgeBase->getAccessControlConfiguration()->deactivateLogin) && ((boolean)$this->knowledgeBase->getAccessControlConfiguration()->deactivateLogin === true)) {
				$this->loginDisabled = true;
			}
		}

		return $this->loginDisabled;
	}

	protected function isDatabaseUserAllowed() {
		if (null === $this->databaseUserAllowed) {
			if (isset($this->knowledgeBase->getAccessControlConfiguration()->allowDbUser) && ((boolean)$this->knowledgeBase->getAccessControlConfiguration()->allowDbUser === true)) {
				$this->databaseUserAllowed = true;
			} else {
				$this->databaseUserAllowed = false;
			}
		}

		return $this->databaseUserAllowed;
	}

}

?>