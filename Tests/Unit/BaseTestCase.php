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
class BaseTestCase extends \Tx_Extbase_Tests_Unit_BaseTestCase {

	private $databaseWasUsed = false;
	private $testConfig = null;

	/**
	 * \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	public function __construct($name = null, $data = array(), $dataName = '') {
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		if (!class_exists('Erfurt\Resource\ClassLoader', FALSE)) {
			require(\t3lib_extmgm::extPath('semantic') . 'Classes/Resource/ClassLoader.php');
			spl_autoload_register(array(new \Erfurt\Resource\ClassLoader(), 'loadClass'));
		}
		if (!class_exists('Erfurt\Object\ObjectManager', FALSE)) {
			require_once(\t3lib_extmgm::extPath('semantic') . 'Classes/Object/ObjectManager.php');
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
		// If test case used the database, we delete all models in order to clean up th environment
		if ($this->databaseWasUsed) {
			$this->authenticateDatabaseUser();
			$store = $this->objectManager->get('\Erfurt\Store\Store');
			$systemOnlogyConfiguration = $this->objectManager->get('\Erfurt\Configuration\SystemOntologyConfiguration');

			foreach ($store->getAvailableModels(true) as $graphUri => $true) {
				if ($graphUri !== $systemOnlogyConfiguration->schemaUri && $graphUri !== $systemOnlogyConfiguration->modelUri) {
					$store->deleteModel($graphUri);
				}
			}

			// Delete system models after all other models are deleted.
			$store->deleteModel($systemOnlogyConfiguration->modelUri);
			$store->deleteModel($systemOnlogyConfiguration->schemaUri);

			$this->databaseWasUsed = false;
		}
	}

	public function authenticateAnonymous() {
		$knowledgeBase = $this->objectManager->get('\Erfurt\KnowledgeBase');
		$knowledgeBase->authenticate();
	}

	public function authenticateDatabaseUser() {
		$store = $this->objectManager->get('\Erfurt\Store\Store');
		$dbUser = $store->getDatabaseUser();
		$dbPass = $store->getDatabasePassword();
		$knowledgeBase = $this->objectManager->get('\Erfurt\KnowledgeBase');
		$knowledgeBase->authenticate($dbUser, $dbPass);
	}

	public function getDatabaseUser() {
		$store = $this->objectManager->get('\Erfurt\Store\Store');
		return $store->getDbUser();
	}

	public function getDatabasePassword() {
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

}

?>