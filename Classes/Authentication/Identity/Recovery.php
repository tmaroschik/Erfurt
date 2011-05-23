<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Authentication\Identity;
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
 * Class to recover/reset lost user credentials like password, username
 * Templates are simple strings where %HASH% will be replaced with the recovery session id
 *
 * default options array:
 * ----------------------
 * key              value
 * ----------------------
 * method       =>  'mail'
 * template     =>  false
 * templateHtml =>  false
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 */

class Recovery {
	/**
	 * @var options array
	 */
	private $options = array(
		'method' => 'mail',
	);

	/**
	 * @var template array
	 */
	private $template = array('subject' => 'Erfurt_Auth_Identity_Recovery');

	/**
	 * Constructor; accepts options array to overwrite default options
	 */
	public function __construct(array $options = array()) {
		$this->options = array_merge($this->options, $options);
	}

	/**
	 *
	 */
	public function validateUser($identity) {
		$config = Erfurt_App::getInstance()->getConfig();
		$store = Erfurt_App::getInstance()->getStore();

		$query = new Erfurt_Sparql_SimpleQuery();
		$query->addFrom($config->ac->graphIri);
		$query->setProloguePart('SELECT *');
		$query->setWherePart(
			'{ ?user <' . $config->ac->user->name . '> "' . $identity . '" .
             OPTIONAL { ?user <' . $config->ac->user->mail . '> ?mail . } }'
		);

		$resultUser = $store->sparqlQuery($query, array('use_ac' => false));

		$query = new Erfurt_Sparql_SimpleQuery();
		$query->addFrom($config->ac->graphIri);
		$query->setProloguePart('SELECT *');
		$query->setWherePart(
			'{ ?user <' . $config->ac->user->mail . '> <mailto:' . $identity . '> .
             OPTIONAL { ?user <' . $config->ac->user->name . '> ?name . } }'
		);

		$resultMail = $store->sparqlQuery($query, array('use_ac' => false));

		if (!empty($resultUser)) {
			$userIri = $resultUser[0]['user'];
			$username = $identity;
			$mailAddr = substr($resultUser[0]['mail'], 7);
		} elseif (!empty($resultMail)) {
			$userIri = $resultMail[0]['user'];
			$username = $resultMail[0]['name'];
			$mailAddr = $identity;
		} else {
			require_once 'Erfurt/Auth/Identity/Exception.php';
			throw new Erfurt_Auth_Identity_Exception('Unknown user identifier.');
		}

		$hash = $this->generateHash($userIri);

		$this->template = array(
			'userIri' => $userIri,
			'hash' => $hash,
			$config->ac->user->name => $username,
			$config->ac->user->mail => $mailAddr
		);

		return $this->template;
	}

	/**
	 *
	 */
	public function recoverWithIdentity($identity) {

		switch ($this->options['method']) {
			case 'mail':
				$config = Erfurt_App::getInstance()->getConfig();
				$this->options['mail']['localname'] = $config->mail->localname->recovery;
				$this->options['mail']['hostname'] = $config->mail->hostname;
				$ret = $this->recoverWithMail($identity);
				break;
			default:
				$ret = false;
				break;
		}

		return $ret;
	}

	/**
	 *
	 */
	private function recoverWithMail($identity) {
		$config = Erfurt_App::getInstance()->getConfig();
		$mail = new Zend_Mail();

		if (array_key_exists('contentText', $this->template)) {
			$mail->setBodyText($this->template['contentText']);
		} else {
			$contentText = '';
			foreach ($this->template as $k => $v) {
				$contentText .= $k . ' : ' . $v . PHP_EOL;
			}
			$mail->setBodyText($contentText);
		}

		if ($this->template['contentHtml']) {
			$mail->setBodyHtml($this->template['contentHtml']);
		}

		$mail->addTo($this->template['mailTo'], $this->template['mailUser']);
		$mail->setFrom($this->options['mail']['localname'] . '@' . $this->options['mail']['hostname']);
		$mail->setSubject($this->template['mailSubject']);
		$mail->send();

		return true;

	}

	/**
	 *
	 */
	private function generateHash($userIri = '') {
		$config = Erfurt_App::getInstance()->getConfig();
		$store = Erfurt_App::getInstance()->getStore();

		$hash = md5($userIri . time() . rand());
		$acGraph = Erfurt_App::getInstance()->getAcGraph();
		// delete previous hash(es)
		$store->deleteMatchingStatements(
			$config->ac->graphIri,
			$userIri,
			$config->ac->user->recoveryHash,
			null,
			array('useAc' => false)
		);
		// create new hash statement
		$store->addStatement(
			$config->ac->graphIri,
			$userIri,
			$config->ac->user->recoveryHash,
			array('value' => $hash, 'type' => 'literal'),
			false
		);
		//var_dump($hash);
		return $hash;
	}

	/**
	 *
	 */
	public function validateHash($hash) {
		$config = Erfurt_App::getInstance()->getConfig();
		$store = Erfurt_App::getInstance()->getStore();

		$query = new Erfurt_Sparql_SimpleQuery();
		$query->addFrom($config->ac->graphIri);
		$query->setProloguePart('SELECT ?user');
		$query->setWherePart('{ ?user <' . $config->ac->user->recoveryHash . '> "' . $hash . '" . }');

		$resultUser = $store->sparqlQuery($query, array('use_ac' => false));

		if (!empty($resultUser)) {
			return $resultUser[0]['user'];
		} else {
			require_once 'Erfurt/Auth/Identity/Exception.php';
			throw new Erfurt_Auth_Identity_Exception('Invalid recovery session identifier.');
		}

	}

	/**
	 *
	 */
	public function resetPassword($hash, $password1, $password2) {

		$config = Erfurt_App::getInstance()->getConfig();
		$store = Erfurt_App::getInstance()->getStore();
		$actionConfig = Erfurt_App::getInstance()->getActionConfig('RegisterNewUser');

		$userIri = $this->validateHash($hash);

		$ret = false;

		if ($password1 !== $password2) {
			require_once 'Erfurt/Auth/Identity/Exception.php';
			throw new Erfurt_Auth_Identity_Exception('Passwords do not match.');
		} else {
			if (strlen($password1) < 5) {
				require_once 'Erfurt/Auth/Identity/Exception.php';
				throw new Erfurt_Auth_Identity_Exception('Password needs at least 5 characters.');
			} else {
				if (
					isset($actionConfig['passregexp']) &&
					$actionConfig['passregexp'] != '' &&
					!@preg_match($actionConfig['passregexp'], $password1)
				) {
					require_once 'Erfurt/Auth/Identity/Exception.php';
					throw new Erfurt_Auth_Identity_Exception('Password does not match regular expression set in system configuration');
				} else {
					// Set new password.

					$store->deleteMatchingStatements(
						$config->ac->graphIri,
						$userIri,
						$config->ac->user->pass,
						null,
						array('use_ac' => false)
					);

					$store->addStatement(
						$config->ac->graphIri,
						$userIri,
						$config->ac->user->pass,
						array(
							 'value' => sha1($password1),
							 'type' => 'literal'
						),
						false
					);

					// delete hash(es)
					$store->deleteMatchingStatements(
						$config->ac->graphIri,
						$userIri,
						$config->ac->user->recoveryHash,
						null,
						array('useAc' => false)
					);

					$ret = true;
				}
			}
		}

		return $ret;
	}

	/**
	 *
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}

}

?>