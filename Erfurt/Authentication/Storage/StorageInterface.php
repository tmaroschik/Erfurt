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
interface StorageInterface {

	/**
	 * Returns true if and only if storage is empty
	 *
	 * @throws Exception If it is impossible to determine whether storage is empty
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * Returns the contents of storage
	 *
	 * Behavior is undefined when storage is empty.
	 *
	 * @throws Exception If reading contents from storage is impossible
	 * @return mixed
	 */
	public function read();

	/**
	 * Writes $contents to storage
	 *
	 * @param  mixed $contents
	 * @throws Exception If writing $contents to storage is impossible
	 * @return void
	 */
	public function write($contents);

	/**
	 * Clears contents from storage
	 *
	 * @throws Exception If clearing contents from storage is impossible
	 * @return void
	 */
	public function clear();

}
