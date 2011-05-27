<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Plugin;

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
 * Erfurt plug-in base class.
 * Sets up the environment for an erfurt plug-in.
 *
 * @author Norman Heino <norman.heino@gmail.com>
 * @version $Id$
 */
class Plugin {

	/**
	 * Plug-in private config
	 * @var \Zend_Config
	 */
	protected $privateConfig = null;

	/**
	 * Plug-in root directory
	 * @var string
	 */
	protected $pluginRoot = null;

	/**
	 * Constructor
	 */
	public function __construct($root, $config = null) {
		$this->pluginRoot = $root;

		if ($config instanceof \Zend_Config) {
			$this->privateConfig = $config;
		}

		$this->init();
	}

	/**
	 * Customized plug-in initialization method
	 */
	public function init() {
	}

}

?>