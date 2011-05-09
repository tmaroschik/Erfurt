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
 * @category   Zend
 * @package    Zend_Auth
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Result {

	/**
	 * General Failure
	 */
	const FAILURE = 0;

	/**
	 * Failure due to identity not being found.
	 */
	const FAILURE_IDENTITY_NOT_FOUND = -1;

	/**
	 * Failure due to identity being ambiguous.
	 */
	const FAILURE_IDENTITY_AMBIGUOUS = -2;

	/**
	 * Failure due to invalid credential being supplied.
	 */
	const FAILURE_CREDENTIAL_INVALID = -3;

	/**
	 * Failure due to uncategorized reasons.
	 */
	const FAILURE_UNCATEGORIZED = -4;

	/**
	 * Authentication success.
	 */
	const SUCCESS = 1;

	/**
	 * Authentication result code
	 *
	 * @var int
	 */
	protected $code;

	/**
	 * The identity used in the authentication attempt
	 *
	 * @var mixed
	 */
	protected $identity;

	/**
	 * An array of string reasons why the authentication attempt was unsuccessful
	 *
	 * If authentication was successful, this should be an empty array.
	 *
	 * @var array
	 */
	protected $messages;

	/**
	 * Sets the result code, identity, and failure messages
	 *
	 * @param  int	 $code
	 * @param  mixed   $identity
	 * @param  array   $messages
	 * @return void
	 */
	public function __construct($code, $identity, array $messages = array()) {
		$code = (int)$code;

		if ($code < self::FAILURE_UNCATEGORIZED) {
			$code = self::FAILURE;
		} elseif ($code > self::SUCCESS) {
			$code = 1;
		}

		$this->code = $code;
		$this->identity = $identity;
		$this->messages = $messages;
	}

	/**
	 * Returns whether the result represents a successful authentication attempt
	 *
	 * @return boolean
	 */
	public function isValid() {
		return ($this->code > 0) ? true : false;
	}

	/**
	 * getCode() - Get the result code for this authentication attempt
	 *
	 * @return int
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Returns the identity used in the authentication attempt
	 *
	 * @return mixed
	 */
	public function getIdentity() {
		return $this->identity;
	}

	/**
	 * Returns an array of string reasons why the authentication attempt was unsuccessful
	 *
	 * If authentication was successful, this method returns an empty array.
	 *
	 * @return array
	 */
	public function getMessages() {
		return $this->messages;
	}

}

?>