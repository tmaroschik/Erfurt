<?php
declare(ENCODING = 'utf-8');
namespace Erfurt;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \Erfurt\Package\Package as BasePackage;

/**
 * The FLOW3 Package
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Package extends BasePackage {

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \Erfurt\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\Erfurt\Core\Bootstrap $bootstrap) {
//		require_once(__DIR__ . '/../Resources/PHP/AutoLoader.php');
//
//		$bootstrap->registerCompiletimeCommandController('flow3:object');
//		$bootstrap->registerCompiletimeCommandController('flow3:core');
//		$bootstrap->registerCompiletimeCommandController('flow3:cache');
//
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
//		$dispatcher->connect('Erfurt\Core\Bootstrap', 'finishedRuntimeRun', 'Erfurt\Persistence\PersistenceManagerInterface', 'persistAll');
//		$dispatcher->connect('Erfurt\Core\Bootstrap', 'dispatchedCommandLineSlaveRequest', 'Erfurt\Persistence\PersistenceManagerInterface', 'persistAll');
		$dispatcher->connect('Erfurt\Core\Bootstrap', 'bootstrapShuttingDown', 'Erfurt\Configuration\ConfigurationManager', 'shutdown');
//		$dispatcher->connect('Erfurt\Core\Bootstrap', 'bootstrapShuttingDown', 'Erfurt\Object\ObjectManagerInterface', 'shutdown');
//		$dispatcher->connect('Erfurt\Core\Bootstrap', 'bootstrapShuttingDown', 'Erfurt\Reflection\ReflectionService', 'saveToCache');
//
//		$dispatcher->connect('Erfurt\Command\CoreCommandController', 'finishedCompilationRun', 'Erfurt\Security\Policy\PolicyService', 'savePolicyCache');
	}
}

?>
