<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\AccessControl;
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
 * A class providing support for access control.
 *
 * This class provides support for graph, action (and statement) based access control.
 * The access control informations are stored in a triple store.
 *
 * @copyright Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @package erfurt
 * @subpackage ac
 * @author Stefan Berger <berger@intersolut.de>
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
class Standard {

	/**
	 * Instance of the ac graph.
	 * @var \Erfurt\Rdf\Graph
	 */
	protected $accessControlGraph = null;

	/**
	 * Contains the action configuration from the configurations (both ini and ac graph).
	 * @var array
	 */
	protected $_actionConfig = null;

	/**
	 * Contains a reference to a auth object.
	 * @var Zend_Auth
	 */
	protected $auth = null;

	/**
	 * Contains the configuration.
	 * @var Zend_Config
	 */
	protected $config = null;

	protected $isInitialized = false;

	/**
	 * Contains the configured ac concept iris.
	 * @var array
	 */
	protected $iris = array(
		'acBaseIri' => 'http://ns.ontowiki.net/SysOnt/',
		'acGraphIri' => 'http://localhost/OntoWiki/Config/',
		'anonymousUserIri' => 'http://ns.ontowiki.net/SysOnt/Anonymous',
		'superUserIri' => 'http://ns.ontowiki.net/SysOnt/SuperAdmin',
		'propAnyGraph' => 'http://ns.ontowiki.net/SysOnt/AnyGraph',
		'propGrantGraphView' => 'http://ns.ontowiki.net/SysOnt/grantGraphView',
		'propDenyGraphView' => 'http://ns.ontowiki.net/SysOnt/denyGraphView',
		'propGrantGraphEdit' => 'http://ns.ontowiki.net/SysOnt/grantGraphEdit',
		'propDenyGraphEdit' => 'http://ns.ontowiki.net/SysOnt/denyGraphEdit',
		'actionClassIri' => 'http://ns.ontowiki.net/SysOnt/Action',
		'propAnyAction' => 'http://ns.ontowiki.net/SysOnt/AnyAction',
		'propGrantAccess' => 'http://ns.ontowiki.net/SysOnt/grantAccess',
		'propDenyAccess' => 'http://ns.ontowiki.net/SysOnt/denyAccess',
		'graphClassIri' => 'http://ns.ontowiki.net/SysOnt/Graph',
		'actionConfigIri' => 'http://ns.ontowiki.net/SysOnt/rawConfig'
	);

	/**
	 * Contains the user rights for all fetched users.
	 * @var array
	 */
	protected $userRights = array();

	/**
	 * Contains a template for the default permissions of a user.
	 * @var array
	 */
	protected $_userRightsTemplate = array(
		'userAnyGraphViewAllowed' => false,
		'userAnyGraphEditAllowed' => false,
		'userAnyActionAllowed' => false,
		'grantAccess' => array(),
		'denyAccess' => array(),
		'grantGraphView' => array(),
		'denyGraphView' => array(),
		'grantGraphEdit' => array(),
		'denyGraphEdit' => array()
	);

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

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
	}

	/**
	 * Delivers the action configuration for a given action
	 *
	 * @param string $actionSpec The IRI of the action.
	 * @return array Returns an array with the action spec.
	 */
	public function getActionConfig($actionSpec) {
		$this->init();
		if (null === $this->_actionConfig) {
			// Fetch the action config.
			$actionConfig = array();
			// First we set the default values from (ini) config. These values will then be overwritten by
			// values from the (ac graph) config.
			foreach ($this->config->action->config->toArray() as $actions) {
				$actionConfig[$actions['iri']] = $actions['spec'];
			}
			// Now fetch the config from ac graph and overwrite the values.
			$query = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
			$query->setProloguePart('SELECT ?s ?o')
					->setWherePart(
				'WHERE {
                          ?s <' . $this->iris['actionConfigIri'] . '> ?o .
                      }'
			);
			$result = $this->sparql($this->accessControlGraph, $query);
			if ($result) {
				foreach ($result as $row) {
					$s = $row['s'];
					$o = explode('=', $row['o']);
					// remove quotas
					if (substr($o[1], 0, 1) === '"') {
						$o[1] = substr($o[1], 1);
					}
					if (substr($o[1], -1) === '"') {
						$o[1] = substr($o[1], 0, -1);
					}
					// Check whether config for iri is already set.
					if (!isset($actionConfig[$s])) {
						$actionConfig[$s] = array();
					}
					$actionConfig[$s][$o[0]] = $o[1];
				}
			}
			$this->_actionConfig = $actionConfig;
		}
		// Return the action config for the given spec if available.
		$actionIri = $this->iris['acBaseIri'] . $actionSpec;
		if (isset($this->_actionConfig[$actionIri])) {
			return $this->_actionConfig[$actionIri];
		} else {
			return array();
		}
	}

	/**
	 * Delievers a  list of allowed actions for the current user.
	 *
	 * @return array Returns a list of allowed actions.
	 */
	public function getAllowedActions() {
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		// filter denied actions
		$ret = array();
		foreach ($userRights['grantAccess'] as $allowed) {
			if (in_array($allowed, $userRights['denyAccess'])) {
				continue;
			}
			$ret[] = $allowed;
		}
		return $ret;
	}

	/**
	 * Delievers a list of allowed graphs.
	 *
	 * @param string $type Name of the access type.
	 * @return array Returns a list of allowed graphs.
	 */
	public function getAllowedGraphs($type = 'view') {
		$this->init();
		$type = strtolower($type);
		// not supported type?
		if (!in_array($type, array('view', 'edit'))) {
			return array();
		}
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		$ret = array();
		$grantGraphKey = ($type === 'view') ? 'grantGraphView' : 'grantGraphEdit';
		$denyGraphKey = ($type === 'view') ? 'denyGraphView' : 'denyGraphEdit';
		// filter denied graphs
		foreach ($userRights[$grantGraphKey] as $allowed) {
			if (in_array($allowed, $userRights[$denyGraphKey])) {
				continue;
			}
			$ret[] = $allowed;
		}
		return $ret;
	}

	/**
	 * Delievers a list of denied actions for the current user.
	 *
	 * @return array Returns a list of denied actions.
	 */
	public function getDeniedActions() {
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		return $userRights['denyAccess'];
	}

	/**
	 * Delievers a list of denied graphs.
	 *
	 * @param string $type Name of the access type.
	 * @return array Returns a list of denied graphs.
	 */
	public function getDeniedGraphs($type = 'view') {
		$this->init();
		$type = strtolower($type);
		// not supported type?
		if (!in_array($type, array('view', 'edit'))) {
			return array();
		}
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		$denyGraphKey = ($type === 'view') ? 'denyGraphView' : 'denyGraphEdit';
		return $userRights[$denyGraphKey];
	}

	/**
	 * Checks whether the given action is allowed for the current user on the
	 * given graph iri.
	 *
	 * @param string $type Name of the access-type (view, edit).
	 * @param string $graphIri The iri of the graph to check.
	 * @return boolean Returns whether allowed or denied.
	 */
	public function isGraphAllowed($type, $graphIri) {
		$graphIri = (string)$graphIri;
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		$type = strtolower($type);
		// type = view; check whether allowed
		if ($type === 'view') {
			// explicit forbidden
			if (in_array($graphIri, $userRights['denyGraphView'])) {
				return false;
			} else {
				if (in_array($graphIri, $userRights['grantGraphView'])) {
					// view explicit allowed and not denied
					return true;
				} else {
					if (in_array($graphIri, $userRights['grantGraphEdit'])) {
						// view in edit allowed and not denied
						return true;
					} else {
						if ($this->isAnyGraphAllowed('view')) {
							// any graph
							return true;
						}
					}
				}
			}
		}
		// type = edit; check whether allowed
		if ($type === 'edit') {
			// explicit forbidden
			if (in_array($graphIri, $userRights['denyGraphEdit'])) {
				return false;
			} else {
				if (in_array($graphIri, $userRights['grantGraphEdit'])) {
					// edit allowed and not denied
					return true;
				} else {
					if ($this->isAnyGraphAllowed('edit')) {
						// any graph
						return true;
					}
				}
			}
		}
		// deny everything else => false
		return false;
	}

	/**
	 * Checks whether the given action is allowed for the current user.
	 *
	 * @param string $action The name of the action.
	 * @return boolean Returns whether action is allowed or not.
	 */
	public function isActionAllowed($action) {
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		$actionIri = $this->iris['acBaseIri'] . $action;
		// Action not allowed (init is optimized on all actions which have an instance)
		if (in_array($actionIri, $userRights['denyAccess'])) {
			return false;
		} else {
			if (in_array($actionIri, $userRights['grantAccess'])) {
				// Action explicitly allowed
				return true;
			} else {
				if ($this->isAnyActionAllowed()) {
					// Every Action allowed
					return true;
				} else {
					// create action instance
					// array for new statements (an action instance pus label)
					$actionStmt = array(
						$actionIri => array(
							EF_RDF_TYPE => array(
								array('type' => 'iri', 'value' => $this->iris['actionClassIri'])
							),
							EF_RDFS_LABEL => array(
								array('type' => 'literal', 'value' => $action)
							)
						)
					);
					$store = $this->knowledgeBase->getStore();
					$store->addMultipleStatements($this->iris['acGraphIri'], $actionStmt, false);
					return false;
				}
			}
		}
	}

	/**
	 * Checks whether any action is allowed for the current user.
	 *
	 * @return boolean Returns whether an action is allowed or not.
	 */
	public function isAnyActionAllowed() {
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		return $userRights['userAnyActionAllowed'];
	}

	/**
	 * Checks whether the current user has the given permission
	 * for any graphs.
	 *
	 * @param string $type (optional) Contains view or edit.
	 * @return boolean Returns whether allowed or denied.
	 */
	public function isAnyGraphAllowed($type = 'view') {
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserGraphRights($user->getIri());
		$type = strtolower($type);
		if ($type === 'view') {
			// any graph view allowed?
			if ($userRights['userAnyGraphViewAllowed'] === true) {
				return true;
			} else {
				if ($userRights['userAnyGraphEditAllowed'] === true) {
					// any graph edit allowed? (implies view right)
					return true;
				} else {
					// not allowed!
					return false;
				}
			}
		}
		if ($type === 'edit') {
			// any graph edit allowed?
			if ($userRights['userAnyGraphEditAllowed'] === true) {
				return true;
			} else {
				// not allowed!
				return false;
			}
		}
		// deny everything else => false
		return false;
	}

	/**
	 * Adds a right to a graph for the current user.
	 *
	 * @param string $graphIri The IRI of the graph.
	 * @param string $type Type of access: view or edit.
	 * @param string $perm Type of permission: grant or deny.
	 * @throws Erfurt_Exception Throws an exception if wrong type was submitted or
	 * wrong perm type was submitted.
	 */
	public function setUserGraphRight($graphIri, $type = 'view', $perm = 'grant') {
		$this->init();
		$user = $this->getUser();
		$type = strtolower($type);
		// is type supported?
		if (!in_array($type, array('view', 'edit'))) {
			throw new Exception('Wrong access type submitted');
		}
		// is permission supported?
		if (!in_array($perm, array('grant', 'deny'))) {
			throw new Exception('Wrong permission type submitted');
		}
		// set the property for the right to add...
		if ($type === 'view') {
			if ($perm === 'grant') {
				$prop = $this->iris['propGrantGraphView'];
				$right = 'grantGraphView';
			} else {
				// else the permission is deny
				$prop = $this->iris['propDenyGraphView'];
				$right = 'denyGraphView';
			}
		} else {
			// else the type is edit
			if ($perm === 'grant') {
				$prop = $this->iris['propGrantGraphEdit'];
				$right = 'grantGraphEdit';
			} else {
				// else the permission is deny
				$prop = $this->iris['propDenyGraphEdit'];
				$right = 'denyGraphEdit';
			}
		}
		// Update the array that contains the right for the user.
		//$this->_userRights[$user->getIri()][$right][] = $graphIri;
		unset($this->userRights[$user->getIri()]);
		// TODO set the right cache tags, such that cache is invalidated!!!
		$store = $this->knowledgeBase->getStore();
		$store->addStatement(
			$this->accessControlGraph->getGraphIri(),
			$user->getIri(),
			$prop,
			array('type' => 'iri', 'value' => $graphIri),
			false
		);
	}

	// ------------------------------------------------------------------------
	// --- Protected methods ----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Fetches the current user from the auth object.
	 *
	 * @return array Returns a user spec array on success.
	 * @throws Exception Throws an exception if no valid user is given.
	 */
	protected function getUser() {
		if ($this->auth->hasIdentity()) {
			// Identity exists; get it
			return $this->auth->getIdentity();
		} else {
			throw new \Exception('No valid user was given.');
		}
	}

	/**
	 * Gets the user rights for the current user.
	 * In case the user iri was not fetched, it is fetched.
	 *
	 * @param string $userIRI The IRI of the user.
	 * @return array Returns an array that contains the user rights.
	 */
	protected function getUserGraphRights($userIRI) {
		if (!isset($this->userRights[$userIRI])) {
			// In this case we need to fetch the rights for the user.
			$userRights = $this->_userRightsTemplate;
			// Super admin, i.e. a user that has database rights (only for debugging purposes and only if
			// enabled in config).
			if (($userIRI === $this->iris['superUserIri']) && ((boolean)$this->config->allowDbUser === true)) {
				$userRights['userAnyActionAllowed'] = true;
				$userRights['userAnyGraphEditAllowed'] = true;
				$userRights['userAnyGraphViewAllowed'] = true;
				$this->userRights[$userIRI] = $userRights;
				return $userRights;
			}
			$sparqlQuery = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
			$sparqlQuery->setProloguePart('SELECT ?group ?p ?o')
					->setWherePart(
				'WHERE {
                                ?group ?p ?o .
                                ?group <' . $this->config->group->membership . '> <' . $userIRI . '>
                            }'
			);
			if ($result = $this->sparql($this->accessControlGraph, $sparqlQuery)) {
				$this->filterAccess($result, $userRights);
			}
			$sparqlQuery = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
			$sparqlQuery->setProloguePart('SELECT ?s ?p ?o')
					->setWherePart(
				'WHERE {
                                ?s ?p ?o .
                                FILTER (
                                    sameTerm(?s, <' . $userIRI . '>) ||
                                    sameTerm(?o, <' . $this->config->action->class . '>)
                                )
                            }'
			);
			if ($result = $this->sparql($this->accessControlGraph, $sparqlQuery)) {
				$this->filterAccess($result, $userRights);
			}
			// Now check for forbidden anyGraph.
			// view
			if (in_array($this->iris['propAnyGraph'], $userRights['denyGraphView'])) {
				$userRights['userAnyGraphViewAllowed'] = false;
				$userRights['userAnyGraphEditAllowed'] = false;
				$userRights['grantGraphView'] = array();
				$userRights['grantGraphEdit'] = array();
			}
			// edit
			if (in_array($this->iris['propAnyGraph'], $userRights['denyGraphEdit'])) {
				$userRights['userAnyGraphEditAllowed'] = false;
				$userRights['grantGraphEdit'] = array();
			}
			$this->userRights[$userIRI] = $userRights;
		}
		return $this->userRights[$userIRI];
	}

	/**
	 * Filters the sparql results and saves the results in $userRights var.
	 *
	 * @param array $resultList A list of sparql results.
	 * @param array $userRights A reference to an array containing user rights.
	 */
	protected function filterAccess($resultList, &$userRights) {
		$allActions = array();
		#var_dump($resultList);
		foreach ($resultList as $entry) {
			// any action allowed?
			if (($entry['o'] === $this->iris['propAnyAction'])
				&& ($entry['p'] === $this->iris['propGrantAccess'])) {
				$userRights['userAnyActionAllowed'] = true;
			} else {
				if (($entry['o'] === $this->iris['propAnyGraph'])
					&& ($entry['p'] === $this->iris['propGrantGraphView'])) {
					// any graph view allowed?
					$userRights['userAnyGraphViewAllowed'] = true;
				} else {
					if (($entry['o'] === $this->iris['propAnyGraph'])
						&& ($entry['p'] === $this->iris['propGrantGraphEdit'])) {
						// any graph edit allowed?
						$userRights['userAnyGraphEditAllowed'] = true;
					} else {
						if ($entry['p'] === $this->iris['propGrantAccess']) {
							// grant action?
							if (!in_array($entry['o'], $userRights['grantAccess'])) {
								$userRights['grantAccess'][] = $entry['o'];
							}
						} else {
							if ($entry['p'] === $this->iris['propDenyAccess']) {
								// deny action?
								if (!in_array($entry['o'], $userRights['denyAccess'])) {
									$userRights['denyAccess'][] = $entry['o'];
								}
							} else {
								if ($entry['p'] === $this->iris['propGrantGraphView']) {
									// grant graph view?
									if (!in_array($entry['o'], $userRights['grantGraphView'])) {
										$userRights['grantGraphView'][] = $entry['o'];
									}
								} else {
									if ($entry['p'] === $this->iris['propDenyGraphView']) {
										// deny graph view?
										if (!in_array($entry['o'], $userRights['denyGraphView'])) {
											$userRights['denyGraphView'][] = $entry['o'];
										}
									} else {
										if ($entry['p'] === $this->iris['propGrantGraphEdit']) {
											// grant graph edit?
											if (!in_array($entry['o'], $userRights['grantGraphEdit'])) {
												$userRights['grantGraphEdit'][] = $entry['o'];
											}
										} else {
											if ($entry['p'] === $this->iris['propDenyGraphEdit']) {
												// deny graph edit?
												if (!in_array($entry['o'], $userRights['denyGraphEdit'])) {
													$userRights['denyGraphEdit'][] = $entry['o'];
												}
											} else {
												if ($entry['p'] === EF_RDF_TYPE && $entry['o'] === $this->config->action->class &&
													$entry['s'] !== $this->config->action->anyAction) {
													// load all actions into array (handle afterwards)
													$allActions[] = $entry['s'];
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		// optimize denyAccess for not anyAction allowed users only
		if (!$userRights['userAnyActionAllowed']) {
			// get existing actions which are not defined (and disallowed)
			$undefinedActions = array_unique(
				array_diff($allActions, $userRights['grantAccess'], $userRights['denyAccess'])
			);
			$userRights['denyAccess'] = array_merge($userRights['denyAccess'], $undefinedActions);
		}
	}

	/**
	 * initialisation of graphs, iris and rights
	 *
	 */
	protected function init() {
		if ($this->isInitialized === true) {
			return;
		}
		// Reset the user rights array.
		$this->userRights = array();
		$this->config = $this->knowledgeBase->getAccessControlConfiguration();
		$this->auth = $this->knowledgeBase->getAuthentication();
		// access control informations
		$this->accessControlGraph = $this->knowledgeBase->getAccessControlGraph();
		// get custom iri configuration
		$this->iris['acBaseIri'] = $this->config->baseIri;
		$this->iris['acGraphIri'] = $this->accessControlGraph->getGraphIri();
		$this->iris['anonymousUserIri'] = $this->config->user->anonymousUser;
		$this->iris['superUserIri'] = $this->config->user->superAdmin;
		$this->iris['propAnyGraph'] = $this->config->graphs->anyGraph;
		$this->iris['propGrantGraphView'] = $this->config->graphs->grantView;
		$this->iris['propDenyGraphView'] = $this->config->graphs->denyView;
		$this->iris['propGrantGraphEdit'] = $this->config->graphs->grantEdit;
		$this->iris['propDenyGraphEdit'] = $this->config->graphs->denyEdit;
		$this->iris['actionClassIri'] = $this->config->action->class;
		$this->iris['propAnyAction'] = $this->config->action->anyAction;
		$this->iris['propGrantAccess'] = $this->config->action->grant;
		$this->iris['propDenyAccess'] = $this->config->action->deny;
		$this->iris['graphClassIri'] = $this->config->graphs->class;
		$this->iris['actionConfigIri'] = $this->config->action->rawConfig;
		$this->isInitialized = true;
	}

	/**
	 * Executes a sparql query against the store.
	 *
	 * @param \Erfurt\Rdf\Graph Active graph instance to query sparql.
	 * @param Erfurt_Sparql_SimpleQuery The SPARQL query.
	 * @return array Returns an array containig the result.
	 */
	protected function sparql($graph, $sparqlQuery) {
		$sparqlQuery->addFrom($graph->getGraphIri());
		$result = $graph->getStore()->sparqlQuery($sparqlQuery, array(STORE_USE_AC => false));
		return $result;
	}

}

?>