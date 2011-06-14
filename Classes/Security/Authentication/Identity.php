<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Security\Authentication;

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
 * One-sentence description of Auth.
 *
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
	protected $iri;

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
	protected $propertyIris = array();

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
		$this->propertyIris['username'] = $this->knowledgeBase->getAccessControlConfiguration()->user->name;
		$this->propertyIris['email'] = $this->knowledgeBase->getAccessControlConfiguration()->user->mail;
		$this->propertyIris['label'] = \Erfurt\Vocabulary\Rdfs::LABEL;

		$this->iri = $this->userSpec['iri'];

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
			$this->userData[$this->propertyIris['username']] = $this->userSpec['username'];
		} else {
			$this->userData[$this->propertyIris['username']] = '';
		}

		if (isset($this->userSpec['email'])) {
			if (substr($this->userSpec['email'], 0, 7) === 'mailto:') {
				$this->userData[$this->propertyIris['email']] = substr($this->userSpec['email'], 7);
			} else {
				$this->userData[$this->propertyIris['email']] = $this->userSpec['email'];
			}
		} else {
			$this->userData[$this->propertyIris['email']] = '';
		}

		if (isset($this->userSpec['label'])) {
			$this->userData[$this->propertyIris['label']] = $this->userSpec['label'];
		} else {
			$this->userData[$this->propertyIris['label']] = '';
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

	public function getIri() {
		return $this->iri;
	}

	public function getUsername() {
		return $this->userData[$this->propertyIris['username']];
	}

	public function getEmail() {
		return $this->userData[$this->propertyIris['email']];
	}

	public function getLabel() {
		return $this->userData[$this->propertyIris['label']];
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
			foreach ($this->knowledgeBase->getUsers() as $userIri => $userArray) {
				if (array_key_exists('userName', $userArray)) {
					$registeredUsernames[] = $userArray['userName'];
				}
			}

			$store = $this->knowledgeBase->getStore();

			if (in_array($newUsername, $registeredUsernames) || ($newUsername === $store->getDatabaseUser())
				|| ($newUsername === 'Anonymous')
				|| ($newUsername === 'Admin')
				|| ($newUsername === 'SuperAdmin')) {
				throw new Identity\Exception('Username already registered.', 1303220617);
			} else {
				// Set the new username.
				$sysModelIri = $this->knowledgeBase->getSystemOntologyConfiguration()->graphIri;

				$store->deleteMatchingStatements(
					$sysModelIri,
					$this->getIri(),
					$this->knowledgeBase->getAccessControlConfiguration()->user->name,
					null,
					array('use_ac' => false)
				);

				if ($newUsername !== '') {
					$store->addStatement(
						$sysModelIri,
						$this->getIri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->name,
						array(
							 'type' => 'literal',
							 'value' => $newUsername,
							 'datatype' => \Erfurt\Vocabulary\Xsd::NS . 'string'
						),
						false
					);
				} else {
					// Also delete password iff set!
					$store->deleteMatchingStatements(
						$sysModelIri,
						$this->getIri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->pass,
						null,
						array('use_ac' => false)
					);
				}
			}
		}

		$this->userData[$this->propertyIris['username']] = $newUsername;
		return true;
	}

	public function setEmail($newEmail) {
		// We save mail iris with mailto: prefix.
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
			foreach ($this->knowledgeBase->getUsers() as $userIri => $userArray) {
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
					$sysModelIri = $this->knowledgeBase->getSystemOntologyConfiguration()->graphIri;

					$store->deleteMatchingStatements(
						$sysModelIri,
						$this->getIri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->mail,
						null,
						array('use_ac' => false)
					);

					$store->addStatement(
						$sysModelIri,
						$this->getIri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->mail,
						array(
							 'type' => 'iri',
							 'value' => $newEmailWithMailto
						),
						false
					);
				}
			}
		}

		$this->userData[$this->propertyIris['email']] = $newEmail;
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
					$sysModelIri = $this->knowledgeBase->getSystemOntologyConfiguration()->graphIri;

					$store->deleteMatchingStatements(
						$sysModelIri,
						$this->getIri(),
						$this->knowledgeBase->getAccessControlConfiguration()->user->pass,
						null,
						array('use_ac' => false)
					);

					$store->addStatement(
						$sysModelIri,
						$this->getIri(),
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

	public function get($propertyIri) {
		// TODO later
	}

	public function set($propertyIri, $value) {
		// TODO later
	}

}

?>