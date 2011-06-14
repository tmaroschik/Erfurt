<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Tests\Unit;
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
class BaseTestCase extends \PHPUnit_Framework_TestCase {

	private $databaseWasUsed = false;
	private $testConfig = null;

	/**
	 * \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	public function __construct($name = null, $data = array(), $dataName = '') {
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		if (!class_exists('Erfurt\Resource\ClassLoader', FALSE)) {
			require_once(__DIR__ . '../../Classes/Resource/ClassLoader.php');
			spl_autoload_register(array(new \Erfurt\Resource\ClassLoader(), 'loadClass'));
		}
		if (!class_exists('Erfurt\Object\ObjectManager', FALSE)) {
			require_once(__DIR__ . '../../Classes/Object/ObjectManager.php');
		}
		$objectManager = new \Erfurt\Object\ObjectManager;
		$this->objectManager = clone $objectManager;
		parent::__construct($name, $data, $dataName);
	}

	/**
	 * Injects an untainted clone of the object manager and all its referencing
	 * objects for every test.
	 *
	 * @return void
	 */
	public function runBare() {
		parent::runBare();
	}

	protected function tearDown() {
		// If test case used the database, we delete all graphs in order to clean up th environment
		if ($this->databaseWasUsed) {
			$this->authenticateDatabaseUser();
			/** @var \Erfurt\Store\Store $store */
			$store = $this->objectManager->get('\Erfurt\Store\Store');
			/** @var \Erfurt\Configuration\SystemOntologyConfiguration $systemOnlogyConfiguration */
			$systemOnlogyConfiguration = $this->objectManager->get('\Erfurt\Configuration\SystemOntologyConfiguration');

			foreach ($store->getAvailableGraphs(true) as $graphUri => $true) {
				if ($graphUri !== $systemOnlogyConfiguration->schemaUri && $graphUri !== $systemOnlogyConfiguration->graphUri) {
					$store->deleteGraph($graphUri);
				}
			}

			// Delete system graphs after all other graphs are deleted.
			$store->deleteGraph($systemOnlogyConfiguration->graphUri);
			$store->deleteGraph($systemOnlogyConfiguration->schemaUri);

			$this->databaseWasUsed = false;
		}
	}

	public function authenticateAnonymous() {
		/** @var \Erfurt\KnowledgeBase $knowledgeBase */
		$knowledgeBase = $this->objectManager->get('\Erfurt\KnowledgeBase');
		$knowledgeBase->authenticate();
	}

	public function authenticateDatabaseUser() {
		/** @var \Erfurt\Store\Store $store */
		$store = $this->objectManager->get('\Erfurt\Store\Store');
		$dbUser = $store->getDatabaseUser();
		$dbPass = $store->getDatabasePassword();
		/** @var \Erfurt\KnowledgeBase $knowledgeBase */
		$knowledgeBase = $this->objectManager->get('\Erfurt\KnowledgeBase');
		$knowledgeBase->authenticate($dbUser, $dbPass);
	}

	public function getDatabaseUser() {
		/** @var \Erfurt\Store\Store $store */
		$store = $this->objectManager->get('\Erfurt\Store\Store');
		return $store->getDatabaseUser();
	}

	public function getDatabasePassword() {
		/** @var \Erfurt\Store\Store $store */
		$store = $this->objectManager->get('\Erfurt\Store\Store');
		return $store->getDatabasePassword();
	}

	public function markTestNeedsDatabase() {
		if (EF_TEST_CONFIG_SKIP_DB_TESTS) {
			$this->markTestSkipped();
		}

		$this->loadTestConfig();

		if ($this->testConfig === false) {
			$this->markTestSkipped();
		}

		$this->authenticateAnonymous();

		try {
			/** @var \Erfurt\Store\Store $store */
			$store = $this->objectManager->get('\Erfurt\Store\Store');
			$store->checkSetup();
			$this->databaseWasUsed = true;
		}
		catch (\Erfurt\Store\Exception\StoreException $e) {
			if ($e->getCode() === 20) {
				// Setup successfull
				$this->databaseWasUsed = true;
			} else {
				$this->markTestSkipped();
			}
		}
		catch (\Erfurt\Exception $e2) {
			$this->markTestSkipped();
		}
	}

	public function markTestNeedsCleanZendDbDatabase() {
		$this->markTestNeedsZendDb();

		/** @var \Erfurt\Store\Store $store */
		$store = $this->objectManager->get('\Erfurt\Store\Store');
		$sql = 'DROP TABLE IF EXISTS ' . implode(',', $store->listTables()) . ';';
		$store->sqlQuery($sql);

		// We do not clean up the db on tear down, for it is empty now.
		$this->databaseWasUsed = false;

		$this->loadTestConfig();
	}

	public function markTestUsesDb() {
		$this->databaseWasUsed = true;
	}

	public function markTestNeedsTestConfig() {
		$this->loadTestConfig();

		if ($this->testConfig === false) {
			$this->markTestSkipped();
		}
	}

	public function getTestConfig() {
		return $this->testConfig;
	}

	public function markTestNeedsVirtuoso() {
		$this->markTestNeedsTestConfig();
		$this->testConfig->store->backend = 'virtuoso';
		$this->markTestNeedsDatabase();
	}

	public function markTestNeedsZendDb() {
		$this->markTestNeedsDatabase();

		if ($this->testConfig->store->backend !== 'zenddb') {
			$this->markTestSkipped();
		}
	}

	private function loadTestConfig() {
		if (null === $this->testConfig) {
			if (is_readable(_TESTROOT . 'config.ini')) {
				$this->testConfig = new \Zend_Config_Ini((_TESTROOT . 'config.ini'), 'private', true);
			} else {
				$this->testConfig = false;
			}
		}

		// We always reload the config in Erfurt, for a test may have changed values
		// and we need a clean environment.
//		if ($this->testConfig !== false) {
//			Erfurt_App::getInstance()->loadConfig($this->testConfig);
//		} else {
//			Erfurt_App::getInstance()->loadConfig();
//		}
	}



	/**
	 * Returns a mock object which allows for calling protected methods and access
	 * of protected properties.
	 *
	 * @param string $className Full qualified name of the original class
	 * @param array $methods
	 * @param array $arguments
	 * @param string $mockClassName
	 * @param boolean $callOriginalConstructor
	 * @param boolean $callOriginalClone
	 * @param boolean $callAutoload
	 * @return object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function getAccessibleMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE) {
		return $this->getMock($this->buildAccessibleProxy($originalClassName), $methods, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload);
	}


	/**
	 * Creates a proxy class of the specified class which allows
	 * for calling even protected methods and access of protected properties.
	 *
	 * @param protected $className Full qualified name of the original class
	 * @return string Full qualified name of the built class
	 */
	protected function buildAccessibleProxy($className) {
		$accessibleClassName = uniqid('AccessibleTestProxy');
		$class = new \ReflectionClass($className);
		$abstractModifier = $class->isAbstract() ? 'abstract ' : '';
		eval('
			' . $abstractModifier . 'class ' . $accessibleClassName . ' extends ' . $className . ' {
				public function _call($methodName) {
					$args = func_get_args();
					return call_user_func_array(array($this, $methodName), array_slice($args, 1));
				}
				public function _callRef($methodName, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL, &$arg5= NULL, &$arg6 = NULL, &$arg7 = NULL, &$arg8 = NULL, &$arg9 = NULL) {
					switch (func_num_args()) {
						case 0 : return $this->$methodName();
						case 1 : return $this->$methodName($arg1);
						case 2 : return $this->$methodName($arg1, $arg2);
						case 3 : return $this->$methodName($arg1, $arg2, $arg3);
						case 4 : return $this->$methodName($arg1, $arg2, $arg3, $arg4);
						case 5 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5);
						case 6 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
						case 7 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7);
						case 8 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8);
						case 9 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8, $arg9);
					}
				}
				public function _set($propertyName, $value) {
					$this->$propertyName = $value;
				}
				public function _setRef($propertyName, &$value) {
					$this->$propertyName = $value;
				}
				public function _get($propertyName) {
					return $this->$propertyName;
				}
			}
		');
		$this->objectManager->create($accessibleClassName);
		return $accessibleClassName;
	}

}

?>