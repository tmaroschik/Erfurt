<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Authentication\Storage;
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
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Session implements StorageInterface {

	/**
	 * Default session namespace
	 */
	const NAMESPACE_DEFAULT = 'Semantic_Authentication';

	/**
	 * Default session object member name
	 */
	const MEMBER_DEFAULT = 'storage';

	/**
	 * Object to proxy $_SESSION storage
	 *
	 * @var object
	 */
	protected $session;

	/**
	 * Session namespace
	 *
	 * @var mixed
	 */
	protected $namespace;

	/**
	 * Session object member
	 *
	 * @var mixed
	 */
	protected $member;

	/**
	 * Sets session storage options and initializes session namespace object
	 *
	 * @param  mixed $namespace
	 * @param  mixed $member
	 * @return void
	 */
	public function __construct($namespace = self::NAMESPACE_DEFAULT, $member = self::MEMBER_DEFAULT) {
		$this->namespace = $namespace;
		$this->member = $member;
		$this->initializeSession();
	}

	/**
	 * @return void
	 */
	public function initializeSession() {
		if (TYPO3_MODE == 'FE') {
			if ($GLOBALS["TSFE"]->loginUser){
				$this->session = $GLOBALS["TSFE"]->fe_user->getKey("user", $this->namespace);
			} else {
				$this->session = $GLOBALS["TSFE"]->fe_user->getKey("ses", $this->namespace);
			}
		} elseif (TYPO3_MODE == 'BE') {
			$this->session = $GLOBALS['BE_USER']->uc[$this->namespace];
		}
		if (!is_object($this->session)) {
			$this->session = (object) null;
		}
		if (TYPO3_MODE == 'FE') {
			if ($GLOBALS["TSFE"]->loginUser){
				$GLOBALS["TSFE"]->fe_user->setKey("user", $this->namespace, $this->session);
			} else {
				$GLOBALS["TSFE"]->fe_user->setKey("ses", $this->namespace, $this->session);
			}
		} elseif (TYPO3_MODE == 'BE') {
			$GLOBALS['BE_USER']->uc[$this->namespace] = $this->session;
		}
	}

	/**
	 * Returns the session namespace
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * Returns the name of the session object member
	 *
	 * @return string
	 */
	public function getMember() {
		return $this->member;
	}

	/**
	 * Defined by StorageInterface
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return !isset($this->session->{$this->member});
	}

	/**
	 * Defined by StorageInterface
	 *
	 * @return mixed
	 */
	public function read() {
		return $this->session->{$this->member};
	}

	/**
	 * Defined by StorageInterface
	 *
	 * @param  mixed $contents
	 * @return void
	 */
	public function write($contents) {
		$this->session->{$this->member} = $contents;
	}

	/**
	 * Defined by StorageInterface
	 *
	 * @return void
	 */
	public function clear() {
		unset($this->session->{$this->member});
	}

}

?>