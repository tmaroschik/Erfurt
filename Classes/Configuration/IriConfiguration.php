<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Configuration;

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
 * Enter descriptions here
 *
 * @package Semantic
 * @scope singleton
 * @api
 */
class IriConfiguration extends AbstractConfiguration implements \Erfurt\Singleton {

	/**
	 * This is the key for this configuration inside the extension configuration
	 */
	protected $extensionConfigurationKey = 'iri';

	/**
	 * Abstract_Configuration provides a property based interface to
	 * an array. The data are read-only unless $allowModifications
	 * is set to true on construction.
	 *
	 * Abstract_Configuration also implements Countable and Iterator to
	 * facilitate easy access to the data.
	 *
	 * @param array $configuration
	 * @param boolean $allowModifications
	 * @return void
	 */
	public function __construct(array $configuration = array(), $allowModifications = false) {
		if (empty($configuration)) {
			$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['semantic']);
			if (isset($extConfig[$this->extensionConfigurationKey . '.']) && is_array($extConfig[$this->extensionConfigurationKey . '.'])) {
				$configurationArray = $extConfig[$this->extensionConfigurationKey . '.'];
				$configuration = \Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($configurationArray);
			}
		}
		$this->allowedModifications = (boolean) $allowModifications;
		$this->loadedSection = null;
		$this->index = 0;
		$this->data = array();
		foreach ($configuration as $key => $value) {
			if (is_array($value)) {
				$this->data[$key] = new self($value, $this->allowedModifications);
			} else {
				if ($key === 'schemata') {
					$this->data[$key] = new self(explode(',', $value), $this->allowedModifications);
				} else {
					$this->data[$key] = $value;
				}
			}
		}
		$this->count = count($this->data);
	}

}

?>