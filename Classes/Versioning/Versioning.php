<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Versioning;

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
/*
 * database tables:
 * CREATE TABLE tx_semantic_versioning_actions(
 *   id             INT NOT NULL AUTO_INCREMENT,
 *   graph          VARCHAR(255) NOT NULL,
 *   useriri        VARCHAR(255) NOT NULL,
 *   resource       VARCHAR(255),
 *   tstamp         INT NOT NULL,
 *   action_type    INT NOT NULL,
 *   parent         INT DEFAULT NULL,
 *   payload_id     INT DEFAULT NULL
 * );
 *
 * CREATE TABLE tx_semantic_versioning_payloads(
 *   id                 INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *   statement_hash     LONGTEXT
 * );
 *
 */

/**
 * Erfurt versioning component
 *
 * @author     Philipp Frischmuth <prischmuth@googlemail.com>
 * @author     Norman Heino <norman.heino@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Versioning implements \Erfurt\Singleton {

	// standard constants for given actions
	const GRAPH_IMPORTED = 10;
	const STATEMENT_ADDED = 20;
	const STATEMENT_CHANGED = 21;
	const STATEMENT_REMOVED = 22;
	const STATEMENTS_ROLLBACK = 23;

	protected $currentAction = null;

	protected $currentActionParent = null;

	protected $versioningEnabled = true;

	protected $limit = 10;

	/**
	 * The injected store
	 *
	 * @var \Erfurt\Store\Store
	 */
	protected $store = null;

	protected $user = null;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injector method for a \Erfurt\Store\Store
	 *
	 * @var \Erfurt\Store\Store
	 */
	public function injectStore(\Erfurt\Store\Store $store) {
		$this->store = $store;
	}

	/**
	 * Constructor registers with \Erfurt\Event\Dispatcher
	 * and adds triggers for operations on statements (add/del)
	 */
	public function initializeObject() {
		// register for events
		$eventDispatcher = $this->objectManager->get('\Erfurt\Event\Dispatcher');
		$eventDispatcher->register('onAddStatement', $this);
		$eventDispatcher->register('onAddMultipleStatements', $this);
		$eventDispatcher->register('onDeleteMatchingStatements', $this);
		$eventDispatcher->register('onDeleteMultipleStatements', $this);
	}

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Enables or disables versioning.
	 *
	 * @param bool $versioningEnabled True, if versioning is enabled, false otherwise
	 */
	public function enableVersioning($versioningEnabled = true) {
		$this->versioningEnabled = (bool)$versioningEnabled;
	}

	/**
	 * Stopping current action if possible throws Exception else
	 */
	public function endAction() {
		if (!$this->isVersioningEnabled()) {
			return;
		}

		// no action to end?
		if (null === $this->currentAction) {
			throw new \Exception('Action not started');
		} else {
			$this->currentAction = null;
			$this->currentActionParent = null;
		}
	}

	/**
	 *  Aborting current action and removing action entry from Database.
	 *  For use on Exceptions ...
	 */
	private function _abortAction() {
		if ($this->isActionStarted()) {
			$this->sqlQuery(
				'DELETE FROM tx_semantic_versioning_actions
                 WHERE id = ' . $this->currentActionParent
			);
			$this->endAction();
		} else {
			// do nothing
		}
	}

	/**
	 * Probably shortcut?
	 */
	public function getLastModifiedForResource($resourceIri, $graphIri) {
		$this->checkSetup();

		$history = $this->getHistoryForResource($resourceIri, $graphIri);

		if (is_array($history) && count($history) > 0) {
			return $history[0];
		} else {
			return null;
		}
	}

	/**
	 * get the versioning actions for a specific graph
	 *
	 * @param string $graphIri the IRI of the knowledge base
	 * @param page
	 */
	public function getHistoryForGraph($graphIri, $page = 1) {
		$this->checkSetup();

		$sql = 'SELECT id, useriri, resource, tstamp, action_type ' .
			   'FROM tx_semantic_versioning_actions WHERE
                graph = \'' . $graphIri . '\'
                ORDER BY tstamp DESC';

		$result = $this->sqlQuery(
			$sql,
			$this->getLimit() + 1,
			$page * $this->getLimit() - $this->getLimit()
		);

		return $result;
	}

	/**
	 * In difference to getHistoryForGraph, this method do result history
	 * actions but the last changed resources
	 * TODO: Make this query more useful (count, with timestamp)
	 *
	 * @param string $graphIri the IRI of the knowledge base
	 */
	public function getConciseHistoryForGraph($graphIri, $page = 1) {
		$this->checkSetup();

		$sql = 'SELECT useriri, resource, MAX(tstamp) FROM tx_semantic_versioning_actions WHERE
                graph = \'' . $graphIri . '\'
                GROUP BY useriri, resource
                ORDER BY 3 DESC';

		$result = $this->sqlQuery(
			$sql,
			$this->getLimit() + 1,
			$page * $this->getLimit() - $this->getLimit()
		);

		return $result;
	}

	/**
	 * This method returns a distinct query result array of resource IRIs which
	 * are modified since a certain timestamp on a given Knowledge Base
	 *
	 * @param string $graphIri the knowledge base (a IRI string)
	 * @param integer $ts the Timestamp (as int!)
	 */
	public function getModifiedResources($graphIri, $timestamp = 0) {
		$this->checkSetup();

		$sql = 'SELECT DISTINCT resource ' .
			   'FROM tx_semantic_versioning_actions WHERE
                graph = \'' . $graphIri . '\' AND
                tstamp >= \'' . $timestamp . '\'
                ORDER BY tstamp DESC';

		$result = $this->sqlQuery($sql);

		return $result;
	}


	/**
	 * get the versioning actions for a specific resource of a graph
	 *
	 * @param string $resourceIri the IRI of the resource
	 * @param string $graphIri the IRI of the knowledge base
	 * @param page
	 */
	public function getHistoryForResource($resourceIri, $graphIri, $page = 1) {
		$this->checkSetup();

		$sql = 'SELECT v2.id,  v2.useriri, v2.tstamp, v2.action_type
                FROM tx_semantic_versioning_actions AS v1, tx_semantic_versioning_actions AS v2
                WHERE
                v1.graph = \'' . $graphIri . '\' AND
                v1.resource = \'' . $resourceIri . '\' AND
                v2.id = v1.parent
                UNION
                SELECT id, useriri, tstamp, action_type
                FROM tx_semantic_versioning_actions
                WHERE
                graph = \'' . $graphIri . '\' AND
                resource = \'' . $resourceIri . '\' AND
                parent IS NULL
                ORDER BY tstamp DESC';

		$result = $this->sqlQuery(
			$sql,
			$this->getLimit() + 1,
			$page * $this->getLimit() - $this->getLimit()
		);

		return $result;
	}

	public function getHistoryForResourceList($resources, $graphIri, $page = 1) {
		$this->checkSetup();

		$sql = 'SELECT id, resource, useriri, tstamp, action_type ' .
			   'FROM tx_semantic_versioning_actions WHERE
                graph = \'' . $graphIri . '\' AND ( resource = \'' . implode('\' OR resource = \'', $resources) . '\' )
                AND parent IS NULL
                ORDER BY tstamp DESC';

		$result = $this->sqlQuery(
			$sql,
			$this->getLimit() + 1,
			($page - 1) * $this->getLimit()
		);

		return $result;
	}

	public function getHistoryForUser($userIri, $page = 1) {
		$this->checkSetup();

		$sql = 'SELECT id, resource, tstamp, action_type ' .
			   'FROM tx_semantic_versioning_actions WHERE
                useriri = \'' . $userIri . '\'
                ORDER BY tstamp DESC';

		$result = $this->sqlQuery(
			$sql,
			$this->getLimit() + 1,
			$page * $this->getLimit() - $this->getLimit()
		);

		return $result;
	}

	/*
	 * Gets latest changes for user on all resources for dashboard
	 */
	public function getHistoryForUserDash($userIri) {
		$this->checkSetup();

		$sql = 'SELECT DISTINCT resource ' .
			   'FROM tx_semantic_versioning_actions WHERE
                useriri = \'' . $userIri . '\'
                ORDER BY tstamp DESC';

		$result = $this->sqlQuery(
			$sql,
			$this->getLimit() + 1,
			$this->getLimit() - $this->getLimit()
		);

		return $result;
	}

	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Returns whether an action is currently running or not.
	 *
	 * @return bool Returns true iff an action is currently started, else false.
	 */
	public function isActionStarted() {
		return (null !== $this->currentAction);
	}

	/**
	 * Returns whether versioning is currently enabled or not.
	 *
	 * @return bool Returns true iff versioning is enabled, false else.
	 */
	public function isVersioningEnabled() {
		$this->checkSetup();

		return (bool)$this->versioningEnabled;
	}

	public function setLimit($limit) {
		if ($limit <= 0) {
			throw new \Exception('Invalid value for limit. Must be postive integer.');
		}

		$this->limit = (int)$limit;
	}

	public function onAddStatement(\Erfurt\Event\Event $event) {
		$this->checkSetup();

		if ($this->isVersioningEnabled() && is_array($event->statement)) {

			$payload = array(
				$event->statement['subject'] => array(
					$event->statement['predicate'] => array(
						$event->statement['object']
					)
				)
			);

			$payloadId = $this->_execAddPayload($payload);
			$resource = $event->statement['subject'];
			$this->execAddAction($event->graphIri, $resource, self::STATEMENT_ADDED, $payloadId);
		} else {
			// do nothing
		}
	}

	public function onAddMultipleStatements(\Erfurt\Event\Event $event) {
		$this->checkSetup();

		if ($this->isVersioningEnabled() && is_array($event->statements)) {
			$graphIri = $event->graphIri;

			$this->execAddPayloadsAndActions($graphIri, self::STATEMENT_ADDED, $event->statements);
		} else {
			// do nothing
		}
	}

	public function onDeleteMatchingStatements(\Erfurt\Event\Event $event) {
		$this->checkSetup();

		if ($this->isversioningEnabled()) {
			$graphIri = $event->graphIri;

			if (isset($event->statements)) {
				$this->execAddPayloadsAndActions($graphIri, self::STATEMENT_REMOVED, $event->statements);
			} else {
				// In this case, we have no payload. Just add a action without a payload (no rollback possible).
				$this->execAddAction($graphIri, $event->resource, self::STATEMENT_REMOVED);
			}
		} else {
			// do nothing
		}
	}

	public function onDeleteMultipleStatements(\Erfurt\Event\Event $event) {
		$this->checkSetup();

		if ($this->isVersioningEnabled()) {
			$graphIri = $event->graphIri;

			$this->execAddPayloadsAndActions($graphIri, self::STATEMENT_REMOVED, $event->statements);
		} else {
			// do nothing
		}
	}


	/**
	 *  Restores a change made to the store directly identified by an actionid inside
	 *  'tx_semantic_versioning_actions'. Action-IDs could be aquired via methods
	 * @see getHistoryForGraph()
	 * @see getHistoryForResource()
	 * @see getHistoryForUser()
	 *
	 * @param integer $actionid identifies the action to restore
	 * @return boolean true if everythings goes fine false otherwise
	 */
	public function rollbackAction($actionId) {
		$this->checkSetup();

		$actionsSql = 'SELECT action_type, payload_id, graph, parent FROM tx_semantic_versioning_actions WHERE ' .
					  '( id = ' . ((int)$actionId) . ' OR parent = ' . ((int)$actionId) . ' ) ' .
					  'AND payload_id IS NOT NULL';

		$result = $this->sqlQuery($actionsSql);

		if (count($result) == 0 || $result[0]['payload_id'] === null) {
			$this->_abortAction();
			$dedicatedException = 'No valid entry in tx_semantic_versioning_actions for action ID';
			throw new \Exception('No rollback possible (' . $dedicatedException . ')');

			return false;

		} else {

			foreach ($result as $i) {

				$type = (int)$i['action_type'];
				$graphIri = isset($i['graph']) ? $i['graph'] : null;
				$payloadID = (int)$i['payload_id'];

				$payloadsSql = 'SELECT statement_hash FROM tx_semantic_versioning_payloads WHERE id = ' .
							   $payloadID;

				$payloadResult = $this->sqlQuery($payloadsSql);

				if (count($payloadResult) !== 1) {

					$dedicatedException = 'No valid entry in tx_semantic_versioning_payloads for payload ID';
					throw new \Exception('No rollback possible (' . $dedicatedException . ')');

					return false;

				} else {

					if (isset($payloadResult[0]['statement_hash'])) {
						$payload = unserialize($payloadResult[0]['statement_hash']);
					} else {
						$payload = null;
					}


					if ($type === self::STATEMENT_ADDED) {
						$this->getStore()->deleteMultipleStatements($graphIri, $payload);
					} else {
						if ($type === self::STATEMENT_REMOVED) {
							$this->getStore()->addMultipleStatements($graphIri, $payload);
						} else {
							// do nothing
						}
					}

				}

			}

			return true;

		}
	}

	/**
	 * Starts a log action to which subsequent statement modifications are added.
	 *
	 * @param $actionSpec array with keys type, graphiri, resourceiri
	 * @return
	 */
	public function startAction($actionSpec) {
		$this->checkSetup();

		// action already running?
		if (null !== $this->currentAction) {
			throw new \Exception('Action already started');
		} elseif ($this->isVersioningEnabled()) {
			$actionType = $actionSpec['type'];
			$graphIri = $actionSpec['graphiri'];
			$resource = $actionSpec['resourceiri'];
			$this->currentAction = $actionSpec;
			$this->currentActionParent = $this->execAddAction($graphIri, $resource, $actionType);
		} else {
			// do nothing
		}
	}

	public function setUserIri($iri) {
		$this->user = $iri;
	}

	/**
	 * Loading Details for a specified ActionId and returns it as array.
	 *
	 * @param $id int
	 * @return array containg columns action_type and statement_hash
	 */
	public function getDetailsForAction($id) {
		$this->checkSetup();

		$detailsSql = 'SELECT actions.action_type, payloads.statement_hash ' .
					  '  FROM tx_semantic_versioning_actions AS actions, ' .
					  '       tx_semantic_versioning_payloads AS payloads ' .
					  'WHERE ' .
					  '( actions.id = ' . $id . ' OR actions.parent = ' . $id . ' ) ' .
					  'AND actions.payload_id IS NOT NULL ' .
					  'AND actions.payload_id = payloads.id ';

		$resultArray = $this->sqlQuery($detailsSql);

		return $resultArray;
	}

	/**
	 * Deletes all history information on a specific graph
	 * use with caution
	 */
	public function deleteHistoryForGraph($graphIri) {
		$this->checkSetup();

		$sql = 'SELECT DISTINCT ac.payload_id
                FROM tx_semantic_versioning_actions AS ac
                WHERE
                ( ac.graph      = \'' . $graphIri . '\' OR  ac.resource   = \'' . $graphIri . '\' )
                AND   ac.payload_id IS NOT NULL';

		$result = $this->sqlQuery($sql);

		// deleting explicitely by id described payloads
		// we need to do so as JOIN isn't compatible with DELETE on Virtuoso
		if (!empty($result)) {

			$idArray = array();

			foreach ($result as $r) {
				$idArray[] = $r['payload_id'];
			}

			sort($idArray, SORT_NUMERIC);

			// finding out ranges of ids to pack them together via id >= xxx AND id <= yyy
			$last = 0;
			$started = 0;
			$ranges = array();
			foreach ($idArray as $nr) {
				if (!$started) {
					$started = $nr;
					$last = $nr;
				} else {
					if ($nr == $last + 1) {
						$last++;
					} else {
						$ranges[] = ' ( id >= ' . $started . ' AND id <= ' . $last . ' ) ';
						$started = $nr;
						$last = $nr;
					}
				}
			}

			$ranges[] = ' ( id >= ' . $started . ' AND id <= ' . $last . ' ) ';

			$sizeOfRanges = sizeof($ranges);

			// iterate over id ranges in groups of 100 per query
			// (this optimizes exec. time for large consecutive changes)
			for ($i = 0; $i < $sizeOfRanges; $i += 100) {

				$sqldeletePayload = 'DELETE FROM tx_semantic_versioning_payloads WHERE ';

				if (($i + 100) < $sizeOfRanges) {
					$sqldeletePayload .= implode('OR', array_slice($ranges, $i, 100));
				} else {
					$length = ($sizeOfRanges) % 100;
					$sqldeletePayload .= implode('OR', array_slice($ranges, $i, $length));
				}
				$resultPayload = $this->sqlQuery($sqldeletePayload);
			}
		}


		// finally delete actions
		$sqldeleteAction = 'DELETE FROM tx_semantic_versioning_actions WHERE
                            graph = \'' . $graphIri . '\' OR resource = \'' . $graphIri . '\'';

		$resultAction = $this->sqlQuery($sqldeleteAction);


	}

	private function execAddAction($graphIri, $resource, $actionType, $payloadId = null) {
		if ($this->user === null) {
			$this->user = $this->getAuthentication()->getIdentity()->getIri();
		}
		$userIri = $this->user;

		$actionsSql = 'INSERT INTO tx_semantic_versioning_actions (graph, useriri, resource, tstamp, action_type, parent';

		if (null !== $payloadId) {
			$actionsSql .= ', payload_id)';
		} else {
			$actionsSql .= ')';
		}

		if (null !== $this->currentActionParent) {
			$actionParent = $this->currentActionParent;
		} else {
			$actionParent = 'NULL';
		}

		$actionsSql .= ' VALUES (\'' .
					   addslashes($graphIri) . '\', \'' .
					   addslashes($userIri) . '\', \'' .
					   addslashes($resource) . '\', \'' . time() . '\', ' .
					   addslashes($actionType) . ', ' . $actionParent;

		if (null !== $payloadId) {
			$actionsSql .= ', ' . $payloadId . ')';
		} else {
			$actionsSql .= ')';
		}

		$this->sqlQuery($actionsSql);

		if (null !== $this->currentAction) {
			$parentActionId = $this->getStore()->lastInsertId();
			return $parentActionId;
		}
	}

	private function _execAddPayload($payload) {
		$payloadsSql = 'INSERT INTO tx_semantic_versioning_payloads (statement_hash) VALUES (\'' .
					   addslashes(serialize($payload)) . '\')';

		$this->sqlQuery($payloadsSql);
		$payloadId = $this->getStore()->lastInsertId();

		return $payloadId;
	}

	private function execAddPayloadsAndActions($graphIri, $actionType, $statements) {
		foreach ($statements as $s => $poArray) {
			foreach ($poArray as $p => $oArray) {
				foreach ($oArray as $i => $oSpec) {
					$statement = array($s => array($p => array($oSpec)));

					$payloadId = $this->_execAddPayload($statement);

					$this->execAddAction($graphIri, $s, $actionType, $payloadId);
				}
			}
		}
	}

	protected function getStore() {
		return $this->store;
	}

	protected function getAuthentication() {
		return $this->knowledgeBase->getAuthentication();
	}

	/**
	 * late setup function for time saving and mocking in test cases
	 */
	private function checkSetup() {
		$this->initialize();
	}

	private function initialize() {

		if (!$this->getStore()->isSqlSupported()) {
			throw new \Exception('For versioning support store adapter needs to implement the SQL interface.');
		}

		$existingTableNames = $this->getStore()->listTables();

		if (!in_array('tx_semantic_versioning_actions', $existingTableNames)) {
			$columnSpec = array(
				'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
				'graph' => 'VARCHAR(255) NOT NULL',
				'useriri' => 'VARCHAR(255) NOT NULL',
				'resource' => 'VARCHAR(255)',
				'tstamp' => 'INT NOT NULL',
				'action_type' => 'INT NOT NULL',
				'parent' => 'INT DEFAULT NULL',
				'payload_id' => 'INT DEFAULT NULL'
			);

			$this->getStore()->createTable('tx_semantic_versioning_actions', $columnSpec);
		}

		if (!in_array('tx_semantic_versioning_payloads', $existingTableNames)) {
			$columnSpec = array(
				'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
				'statement_hash' => 'LONGTEXT'
			);

			$this->getStore()->createTable('tx_semantic_versioning_payloads', $columnSpec);
		}
	}

	protected function sqlQuery($sql, $limit = PHP_INT_MAX, $offset = 0) {
		try {
			$result = $this->getStore()->sqlQuery($sql, $limit, $offset);
		}
		catch (\Erfurt\Exception $e) {
			$this->checkSetup();

			try {
				$result = $this->getStore()->sqlQuery($sql, $limit, $offset);
			}
			catch (\Erfurt\Exception $e2) {
				throw new \Erfurt\Exception('Erfurt_Versioning _sqlQuery failed: ' . $e2->getMessage() . $sql);
			}
		}

		return $result;
	}

}

?>