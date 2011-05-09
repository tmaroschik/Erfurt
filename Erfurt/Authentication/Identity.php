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
 * One-sentence description of Auth.
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 */
class Identity {

	/**
	 * @var array
	 */
	protected $userSpec;

	/**
	 * @var string
	 */
	protected $uri;

	/**
	 * @var bool
	 */
	protected $isOpenId = false;

	/**
	 * @var bool
	 */
	protected $isWebId = false;

	/**
	 * @var bool
	 */
	protected $isAnonymous = false;

	/**
	 * @var bool
	 */
	protected $isDatabaseUser = false;

	/**
	 * @var array
	 */
	protected $propertyUris = array();

	/**
	 * @var array
	 */
	protected $userData = array();

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	public function __construct(array $userSpec) {
		$this->userSpec = $userSpec;
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
	 * Lifecycle method, to initialize the objet after all dependencies have been injected
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->propertyUris['username'] = $this->knowledgeBase->getAccessControlConfiguration()->user->name;
		$this->propertyUris['email'] = $this->knowledgeBase->getAccessControlConfiguration()->user->mail;
		$this->propertyUris['label'] = EF_RDFS_LABEL;

		$this->uri = $this->userSpec['uri'];

		if (isset($this->userSpec['dbuser'])) {
			$this->isDatabaseUser = $this->userSpec['dbuser'];
		} else {
			$this->isDatabaseUser = false;
		}

		if (isset($this->userSpec['anonymous'])) {
			$this->isAnonymous = $this->userSpec['anonymous'];
		} else {
			$this->isAnonymous = false;
		}


		if (isset($this->userSpec['username'])) {
			$this->userData[$this->propertyUris['username']] = $this->userSpec['username'];
		} else {
			$this->userData[$this->propertyUris['username']] = '';
		}

		if (isset($this->userSpec['email'])) {
			if (substr($this->userSpec['email'], 0, 7) === 'mailto:') {
				$this->userData[$this->propertyUris['email']] = substr($this->userSpec['email'], 7);
			} else {
				$this->userData[$this->propertyUris['email']] = $this->userSpec['email'];
			}
		} else {
			$this->userData[$this->propertyUris['email']] = '';
		}

		if (isset($this->userSpec['label'])) {
			$this->userData[$this->propertyUris['label']] = $this->userSpec['label'];
		} else {
			$this->userData[$this->propertyUris['label']] = '';
		}

		if (isset($this->userSpec['is_openid_user'])) {
			$this->isOpenId = true;
		} else {
			$this->isOpenId = false;
		}

		if (isset($this->userSpec['is_webid_user'])) {
			$this->isWebId = true;
		} else {
			$this->isWebId = false;
		}
	}

	public function getUri() {
		return $this->uri;
	}

	public function getUsername() {
		return $this->userData[$this->propertyUris['username']];
	}

	public function getEmail() {
		return $this->userData[$this->propertyUris['email']];
	}

	public function getLabel() {
		return $this->userData[$this->propertyUris['label']];
	}

	public function isOpenId() {
		return $this->isOpenId;
	}

	public function isWebId() {
		return $this->isWebId;
	}

	public function isDatabaseUser() {
		return $this->isDatabaseUser;
	}

	public function isAnonymousUser() {
		return $this->isAnonymous;
	}

	public function setUsername($newUsername) {
		// Non-OpenID users are not allowed to change their username!
		if (!($this->isOpenId() || $this->isWebId())) {
			throw new Identity\Exception('Username change is not allowed!', 1303220565);
		}

		$oldUsername = $this->getUsername();

		if ($oldUsername !== $newUsername) {
			// Username has changed

			$registeredUsernames = array();
			foreach ($this->knowledgeBase->getUsers() as $userUri => $userArray) {
				if (array_key_exists('userName', $userArray)) {
					$registeredUsernames[] = $userArray['userName'];
				}
			}

			$store = $this->knowledgeBase->getStore();

			if (in_array($newUsername, $registeredUsernames) || ($newUsername === $store->getDbUser())
				|| ($newUsername === 'Anonymous')
				|| ($newUsername === 'Admin')
				|| ($newUsername === 'SuperAdmin')) {
				throw new Identity\Exception('Username already registered.', 1303220617);
			} else {
				// Set the new username.
				$sysModelUri = $this->knowledgeBase->getSystemOntologyConfiguration()->modelUri;

				$store->deleteMatchingStatements(
					$sysModelUri,
					$this->getUri(),
					$this->knowledgeBase->getAccessControlConfiguration()->user->name,
					null,
					array('use_ac' => false)
				);

				if ($newUsername !== '') {
					$store->addStatement(
						$sysModelUri,
						$this->getUri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->name,
						array(
							 'type' => 'literal',
							 'value' => $newUsername,
							 'datatype' => EF_XSD_NS . 'string'
						),
						false
					);
				} else {
					// Also delete password iff set!
					$store->deleteMatchingStatements(
						$sysModelUri,
						$this->getUri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->pass,
						null,
						array('use_ac' => false)
					);
				}
			}
		}

		$this->userData[$this->propertyUris['username']] = $newUsername;
		return true;
	}

	public function setEmail($newEmail) {
		// We save mail uris with mailto: prefix.
		if (substr($newEmail, 0, 7) !== 'mailto:') {
			$newEmailWithMailto = 'mailto:' . $newEmail;
		} else {
			$newEmailWithMailto = $newEmail;
		}

		$oldEmail = $this->getEmail();

		if ($oldEmail !== $newEmail) {
			// Email has changed

			if ($newEmail === '') {
				// This case is not allowed, for every user needs a valid email address.
				throw new Identity\Exception('Email must not be empty.', 1303220712);
			}

			$registeredEmailAddresses = array();
			foreach ($this->knowledgeBase->getUsers() as $userUri => $userArray) {
				if (array_key_exists('userEmail', $userArray)) {
					$registeredEmailAddresses[] = $userArray['userEmail'];
				}
			}

			$emailValidator = new Zend_Validate_EmailAddress();
			$actionConfig = $this->knowledgeBase->getActionConfig('RegisterNewUser');

			if (in_array($newEmailWithMailto, $registeredEmailAddresses)) {
				throw new Identity\Exception('Email address is already registered.', 1303220745);
			} else {
				if (isset($actionConfig['mailvalidation']) && $actionConfig['mailvalidation'] == 'yes'
					&& !$emailValidator->isValid($newEmail)) {
					throw new Identity\Exception('Email address validation failed.', 1303220757);
				} else {
					// Set new mail address
					$store = $this->knowledgeBase->getStore();
					$sysModelUri = $this->knowledgeBase->getSystemOntologyConfiguration()->modelUri;

					$store->deleteMatchingStatements(
						$sysModelUri,
						$this->getUri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->mail,
						null,
						array('use_ac' => false)
					);

					$store->addStatement(
						$sysModelUri,
						$this->getUri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->mail,
						array(
							 'type' => 'uri',
							 'value' => $newEmailWithMailto
						),
						false
					);
				}
			}
		}

		$this->userData[$this->propertyUris['email']] = $newEmail;
		return true;
	}

	public function setLabel($newLabel) {
		// TODO later
	}

	public function setPassword($newPassword) {
		$username = $this->getUsername();
		$actionConfig = $this->knowledgeBase->getActionConfig('RegisterNewUser');

		if ($username !== '') {
			if (strlen($newPassword) < 5) {
				throw new Identity\Exception('Password needs at least 5 characters.', 1303220820);
			} else {
				if (isset($actionConfig['passregexp']) && $actionConfig['passregexp'] != ''
					&& !@preg_match($actionConfig['passregexp'], $newPassword)) {
					throw new Identity\Exception('Password does not match regular expression set in system configuration', 1303220834);
				} else {
					// Set new password.
					$store = $this->knowledgeBase->getStore();
					$sysModelUri = $this->knowledgeBase->getSystemOntologyConfiguration()->modelUri;

					$store->deleteMatchingStatements(
						$sysModelUri,
						$this->getUri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->pass,
						null,
						array('use_ac' => false)
					);

					$store->addStatement(
						$sysModelUri,
						$this->getUri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->pass,
						array(
							 'value' => sha1($newPassword),
							 'type' => 'literal'
						),
						false
					);
				}
			}
		} else {
			// If we have no username, we need no password.
			throw new Identity\Exception('Passwords are only allowed if a Username is set.', 1303220872);
		}
		return true;
	}

	public function get($propertyUri) {
		// TODO later
	}

	public function set($propertyUri, $value) {
		// TODO later
	}

}

?>