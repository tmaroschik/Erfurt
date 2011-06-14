<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\SignalSlot;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It has been ported from the corresponding class of the FLOW3           *
 * framework. All credits go to the responsible contributors.             *
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
 *                                                                        */

/**
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class Dispatcher {

	/**
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Information about all slots connected a certain signal.
	 * Indexed by [$signalClassName][$signalMethodName] and then numeric with an
	 * array of information about the slot
	 * @var array
	 */
	protected $slots = array();

	/**
	 * Injects the object manager
	 *
	 * @param \Erfurt\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Connects a signal with a slot.
	 * One slot can be connected with multiple signals by calling this method multiple times.
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
	 * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
	 * @param boolean $omitSignalInformation If set to TRUE, the first argument passed to the slot will be the first argument of the signal instead of some information about the signal.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function connect($signalClassName, $signalName, $slotClassNameOrObject, $slotMethodName = '', $omitSignalInformation = FALSE) {
		$class = NULL;
		$object = NULL;

		if (is_object($slotClassNameOrObject)) {
			$object = $slotClassNameOrObject;
			$method = ($slotClassNameOrObject instanceof \Closure) ? '__invoke' : $slotMethodName;
		} else {
			if ($slotMethodName === '') throw new \InvalidArgumentException('The slot method name must not be empty (except for closures).', 1229531659);
			$class = $slotClassNameOrObject;
			$method = $slotMethodName;
		}

		$this->slots[$signalClassName][$signalName][] = array(
			'class' => $class,
			'method' => $method,
			'object' => $object,
			'omitSignalInformation' => ($omitSignalInformation === TRUE)
		);
	}

	/**
	 * Dispatches a signal by calling the registered Slot methods
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @param array $signalArguments arguments passed to the signal method
	 * @return void
	 * @throws \Erfurt\SignalSlot\Exception\InvalidSlotException if the slot is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function dispatch($signalClassName, $signalName, array $signalArguments) {
	if (!isset($this->slots[$signalClassName][$signalName])) return;
		foreach ($this->slots[$signalClassName][$signalName] as $slotInformation) {
			if (isset($slotInformation['object'])) {
				$object = $slotInformation['object'];
			} else {
				if (!isset($this->objectManager)) {
					throw new \Erfurt\SignalSlot\Exception\InvalidSlotException(sprintf('Cannot dispatch %s::%s to class %s. The object manager is not yet available in the Signal Slot Dispatcher and therefore it cannot dispatch classes.', $signalClassName, $signalName, $slotInformation['class']), 1298113624);
				}
				$object = $this->objectManager->get($slotInformation['class']);
			}
			$slotArguments = $signalArguments;
			if ($slotInformation['omitSignalInformation'] !== TRUE) {
				$slotArguments[] = $signalClassName . '::' . $signalName;
			}
			if (!method_exists($object, $slotInformation['method'])) {
				throw new \Erfurt\SignalSlot\Exception\InvalidSlotException('The slot method ' . get_class($object) . '->' . $slotInformation['method'] . '() does not exist.', 1245673368);
			}
			call_user_func_array(array($object, $slotInformation['method']), $slotArguments);
		}
	}

	/**
	 * Returns all slots which are connected with the given signal
	 *
	 * @param string $signalClassName Name of the class containing the signal
	 * @param string $signalName Name of the signal
	 * @return array An array of arrays with slot information
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getSlots($signalClassName, $signalName) {
		return (isset($this->slots[$signalClassName][$signalName])) ? $this->slots[$signalClassName][$signalName] : array();
	}

}
?>