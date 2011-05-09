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
use \Erfurt\Sparql;
class Rdf implements AdapterInterface {

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
	public function __construct($username = null, $password = null) {
		$this->username = $username;
		$this->password = $password;
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

		if ($this->isLoginDisabled() === true || $this->username === 'Anonymous') {
			$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $this->getAnonymousUser());
		} else {
			if ($this->isDatabaseUserAllowed() && $this->username === $this->getDatabaseUsername() &&
				// super admin
				$this->password === $this->getDatabasePassword()) {

				$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $this->getSuperAdmin());
			} else {
				// normal user from system ontology
				$identity = array(

					'username' => $this->username,
					'uri' => '',
					'dbuser' => false,
					'anonymous' => false
				);

				// have a look at the cache...
				$cache = $this->knowledgeBase->getCache();
				$id = $cache->makeId($this, '_fetchDataForUser', array($this->username));
				$cachedVal = $cache->load($id);
				if ($cachedVal) {
					$this->users[$this->username] = $cachedVal;
				} else {
					$this->users[$this->username] = $this->fetchDataForUser($this->username);
					$cache->save($this->users[$this->username]);
				}

				// if login is denied return failure auth result
				if ($this->users[$this->username]['denyLogin'] === true) {
					$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::FAILURE, null, array('Login not allowed!'));
				} else {
					if ($this->users[$this->username]['userUri'] === false) {
						// does user not exist?
						$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::FAILURE, null, array('Unknown user identifier.'));
					} else {
						// verify the password
						if (!$this->_verifyPassword($this->password, $this->users[$this->username]['userPassword'], 'sha1')
							&& !$this->_verifyPassword($this->password, $this->users[$this->username]['userPassword'], '')) {

							$authResult = new \Erfurt\Authentication\Result(
								\Erfurt\Authentication\Result::FAILURE, null, array('Wrong password entered!')
							);
						} else {
							$identity['uri'] = $this->users[$this->username]['userUri'];
							$identity['email'] = $this->users[$this->username]['userEmail'];

							$identityObject = $this->objectManager->create('\Erfurt\Authentication\Identity', $identity);

							$authResult = new \Erfurt\Authentication\Result(\Erfurt\Authentication\Result::SUCCESS, $identityObject);
						}
					}
				}
			}
		}

		//Erfurt_App::getInstance()->getAc()->init();
		return $authResult;
	}

	/**
	 * Fetches the data for a specific user from the RDF user store.
	 *
	 * Returns the data for the user only if $username match the data stored.
	 *
	 * @param string $username
	 *
	 * @return array
	 */
	private function fetchDataForUser($username) {

		$returnVal = array(
			'userUri' => false,
			'denyLogin' => false,
			'userPassword' => '',
			'userEmail' => ''
		);

		$uris = $this->getUris();

		$sparqlQuery = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
		$sparqlQuery->setProloguePart('SELECT ?subject ?predicate ?object');

		$wherePart = 'WHERE { ?subject ?predicate ?object . ?subject <' . EF_RDF_TYPE . '> <' .
					 $uris['user_class'] . '> . ?subject <' . $uris['user_username'] . '> "' . $username . '"^^<' .
					 EF_XSD_NS . 'string> }';
		$sparqlQuery->setWherePart($wherePart);

		if ($result = $this->_sparql($sparqlQuery)) {

			foreach ($result as $userStatement) {
				// set user URI
				if (($returnVal['userUri']) === false) {
					$returnVal['userUri'] = $userStatement['subject'];
				}

				// check other predicates
				switch ($userStatement['predicate']) {
					case $uris['action_deny']:
						// if login is disallowed
						if ($userStatement['object'] === $uris['action_login']) {
							return array('denyLogin' => true);
						}
					case $uris['user_password']:
						$returnVal['userPassword'] = $userStatement['object'];
						break;
					case $uris['user_mail']:
						$returnVal['userEmail'] = $userStatement['object'];
						break;
					default:
						// ignore other statements
				}
			}
		}

		return $returnVal;
	}

	/**
	 * Fetches the for all users from the RDF user store.
	 *
	 * Stores the user data in an internal array for alter reference.
	 *
	 * @return void
	 */
	public function fetchDataForAllUsers() {
		$uris = $this->getUris();

		$userSparql = new Sparql\SimpleQuery();
		$userSparql->setProloguePart('SELECT ?subject ?predicate ?object');

		$wherePart = 'WHERE { ?subject ?predicate ?object . ?subject <' . EF_RDF_TYPE . '> <' .
					 $uris['user_class'] . '> }';
		$userSparql->setWherePart($wherePart);

		if ($result = $this->_sparql($userSparql)) {
			foreach ($result as $statement) {
				switch ($statement['predicate']) {
					case $uris['action_deny']:
						if ($statement['object'] == $uris['action_login']) {
							$this->users[$statement['subject']]['loginForbidden'] = true;
						}
						break;
					case $uris['user_username']:
						// save username
						$this->users[$statement['subject']]['userName'] = $statement['object'];
						break;
					case $uris['user_password']:
						// save password
						$this->users[$statement['subject']]['userPassword'] = $statement['object'];
						break;
					case $uris['user_mail']:
						// save e-mail
						$this->users[$statement['subject']]['userEmail'] = $statement['object'];
						break;
					default:
						// ignore other statements
				}
			}
			$this->userDataFetched = true;
		}
	}

	/**
	 * Returns an array of users available within the container.
	 *
	 * @return array
	 */
	public function getUsers() {
		if (!$this->userDataFetched) {
			$this->fetchDataForAllUsers();
		}

		return $this->users;
	}

	/**
	 * Crypt and verfiy the entered password
	 *
	 * @param  string Entered password
	 * @param  string Password from the data container (usually this password
	 *				is already encrypted.
	 * @param  string Type of algorithm with which the password from
	 *				the container has been crypted. (md5, crypt etc.)
	 *				Defaults to "md5".
	 * @return bool   True, if the passwords match
	 */
	private function _verifyPassword($password1, $password2, $cryptType = 'md5') {
		switch ($cryptType) {
			case 'md5':
				return ((string)md5($password1) === (string)$password2);
				break;
			case 'sha1':
				return ((string)sha1($password1) === (string)$password2);
				break;
			case 'crypt':
				return ((string)crypt($password1, $password2) === (string)$password2);
				break;
			case 'none':
			case '':
				return ((string)$password1 === (string)$password2);
				break;
			default:
				if (function_exists($cryptType)) {
					return ((string)$cryptType($password1) === (string)$password2);
				} else {
					if (method_exists($this, $cryptType)) {
						return ((string)$this->$cryptType($password1) === (string)$password2);
					} else {
						return false;
					}
				}
		}
	}

	/**
	 * Queries the ac model.
	 *
	 * @return array|null
	 */
	private function _sparql($sparqlQuery) {
		try {
			$sparqlQuery->addFrom($this->accessModelUri());
			$result = $this->getStore()->sparqlQuery($sparqlQuery, array('use_ac' => false));
		}
		catch (\Exception $e) {
			var_dump($e);

			exit;
			return null;
		}

		return $result;
	}

	/**
	 * Returns the anonymous user details.
	 *
	 * @return array
	 */
	private function getAnonymousUser() {
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
	private function getSuperAdmin() {
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

	private function getDatabaseUsername() {
		if (null === $this->databaseUsername) {
			$this->databaseUsername = $this->getStore()->getDbUser();
		}

		return $this->databaseUsername;
	}

	private function getDatabasePassword() {
		if (null === $this->databasePassword) {
			$this->databasePassword = $this->getStore()->getDbPassword();
		}

		return $this->databasePassword;
	}

	private function getStore() {
		return $this->store;
	}

	private function accessModelUri() {
		if (null === $this->accessModelUri) {
			$this->accessModelUri = $this->knowledgeBase->getAccessControlConfiguration()->modelUri;
		}

		return $this->accessModelUri;
	}

	private function getUris() {
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

	private function isLoginDisabled() {
		if (null === $this->loginDisabled) {
			if (isset($this->knowledgeBase->getAccessControlConfiguration()->deactivateLogin) && ((boolean)$this->knowledgeBase->getAccessControlConfiguration()->deactivateLogin === true)) {
				$this->loginDisabled = true;
			}
		}

		return $this->loginDisabled;
	}

	private function isDatabaseUserAllowed() {
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