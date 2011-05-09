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
 * This class provides support for model, action (and statement) based access control.
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

	// ------------------------------------------------------------------------
	// --- Private properties -------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Instance of the ac model.
	 * @var Erfurt_Rdf_Model
	 */
	private $accessControlModel = null;

	/**
	 * Contains the action configuration from the configurations (both ini and ac model).
	 * @var array
	 */
	private $_actionConfig = null;

	/**
	 * Contains a reference to a auth object.
	 * @var Zend_Auth
	 */
	private $auth = null;

	/**
	 * Contains the configuration.
	 * @var Zend_Config
	 */
	private $config = null;

	private $isInitialized = false;

	/**
	 * Contains the configured ac concept uris.
	 * @var array
	 */
	private $uris = array(
		'acBaseUri' => 'http://ns.ontowiki.net/SysOnt/',
		'acModelUri' => 'http://localhost/OntoWiki/Config/',
		'anonymousUserUri' => 'http://ns.ontowiki.net/SysOnt/Anonymous',
		'superUserUri' => 'http://ns.ontowiki.net/SysOnt/SuperAdmin',
		'propAnyModel' => 'http://ns.ontowiki.net/SysOnt/AnyModel',
		'propGrantModelView' => 'http://ns.ontowiki.net/SysOnt/grantModelView',
		'propDenyModelView' => 'http://ns.ontowiki.net/SysOnt/denyModelView',
		'propGrantModelEdit' => 'http://ns.ontowiki.net/SysOnt/grantModelEdit',
		'propDenyModelEdit' => 'http://ns.ontowiki.net/SysOnt/denyModelEdit',
		'actionClassUri' => 'http://ns.ontowiki.net/SysOnt/Action',
		'propAnyAction' => 'http://ns.ontowiki.net/SysOnt/AnyAction',
		'propGrantAccess' => 'http://ns.ontowiki.net/SysOnt/grantAccess',
		'propDenyAccess' => 'http://ns.ontowiki.net/SysOnt/denyAccess',
		'modelClassUri' => 'http://ns.ontowiki.net/SysOnt/Model',
		'actionConfigUri' => 'http://ns.ontowiki.net/SysOnt/rawConfig'
	);

	/**
	 * Contains the user rights for all fetched users.
	 * @var array
	 */
	private $userRights = array();

	/**
	 * Contains a template for the default permissions of a user.
	 * @var array
	 */
	private $_userRightsTemplate = array(
		'userAnyModelViewAllowed' => false,
		'userAnyModelEditAllowed' => false,
		'userAnyActionAllowed' => false,
		'grantAccess' => array(),
		'denyAccess' => array(),
		'grantModelView' => array(),
		'denyModelView' => array(),
		'grantModelEdit' => array(),
		'denyModelEdit' => array()
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
	 * @param string $actionSpec The URI of the action.
	 * @return array Returns an array with the action spec.
	 */
	public function getActionConfig($actionSpec) {
		$this->init();
		if (null === $this->_actionConfig) {
			// Fetch the action config.
			$actionConfig = array();
			// First we set the default values from (ini) config. These values will then be overwritten by
			// values from the (ac model) config.
			foreach ($this->config->action->config->toArray() as $actions) {
				$actionConfig[$actions['uri']] = $actions['spec'];
			}
			// Now fetch the config from ac model and overwrite the values.
			$query = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
			$query->setProloguePart('SELECT ?s ?o')
					->setWherePart(
				'WHERE {
                          ?s <' . $this->uris['actionConfigUri'] . '> ?o .
                      }'
			);
			$result = $this->sparql($this->accessControlModel, $query);
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
					// Check whether config for uri is already set.
					if (!isset($actionConfig[$s])) {
						$actionConfig[$s] = array();
					}
					$actionConfig[$s][$o[0]] = $o[1];
				}
			}
			$this->_actionConfig = $actionConfig;
		}
		// Return the action config for the given spec if available.
		$actionUri = $this->uris['acBaseUri'] . $actionSpec;
		if (isset($this->_actionConfig[$actionUri])) {
			return $this->_actionConfig[$actionUri];
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
		$userRights = $this->getUserModelRights($user->getUri());
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
	 * Delievers a list of allowed models.
	 *
	 * @param string $type Name of the access type.
	 * @return array Returns a list of allowed models.
	 */
	public function getAllowedModels($type = 'view') {
		$this->init();
		$type = strtolower($type);
		// not supported type?
		if (!in_array($type, array('view', 'edit'))) {
			return array();
		}
		$user = $this->getUser();
		$userRights = $this->getUserModelRights($user->getUri());
		$ret = array();
		$grantModelKey = ($type === 'view') ? 'grantModelView' : 'grantModelEdit';
		$denyModelKey = ($type === 'view') ? 'denyModelView' : 'denyModelEdit';
		// filter denied models
		foreach ($userRights[$grantModelKey] as $allowed) {
			if (in_array($allowed, $userRights[$denyModelKey])) {
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
		$userRights = $this->getUserModelRights($user->getUri());
		return $userRights['denyAccess'];
	}

	/**
	 * Delievers a list of denied models.
	 *
	 * @param string $type Name of the access type.
	 * @return array Returns a list of denied models.
	 */
	public function getDeniedModels($type = 'view') {
		$this->init();
		$type = strtolower($type);
		// not supported type?
		if (!in_array($type, array('view', 'edit'))) {
			return array();
		}
		$user = $this->getUser();
		$userRights = $this->getUserModelRights($user->getUri());
		$denyModelKey = ($type === 'view') ? 'denyModelView' : 'denyModelEdit';
		return $userRights[$denyModelKey];
	}

	/**
	 * Checks whether the given action is allowed for the current user on the
	 * given model uri.
	 *
	 * @param string $type Name of the access-type (view, edit).
	 * @param string $modelUri The uri of the graph to check.
	 * @return boolean Returns whether allowed or denied.
	 */
	public function isModelAllowed($type, $modelUri) {
		$modelUri = (string)$modelUri;
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserModelRights($user->getUri());
		$type = strtolower($type);
		// type = view; check whether allowed
		if ($type === 'view') {
			// explicit forbidden
			if (in_array($modelUri, $userRights['denyModelView'])) {
				return false;
			} else {
				if (in_array($modelUri, $userRights['grantModelView'])) {
					// view explicit allowed and not denied
					return true;
				} else {
					if (in_array($modelUri, $userRights['grantModelEdit'])) {
						// view in edit allowed and not denied
						return true;
					} else {
						if ($this->isAnyModelAllowed('view')) {
							// any model
							return true;
						}
					}
				}
			}
		}
		// type = edit; check whether allowed
		if ($type === 'edit') {
			// explicit forbidden
			if (in_array($modelUri, $userRights['denyModelEdit'])) {
				return false;
			} else {
				if (in_array($modelUri, $userRights['grantModelEdit'])) {
					// edit allowed and not denied
					return true;
				} else {
					if ($this->isAnyModelAllowed('edit')) {
						// any model
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
		$userRights = $this->getUserModelRights($user->getUri());
		$actionUri = $this->uris['acBaseUri'] . $action;
		// Action not allowed (init is optimized on all actions which have an instance)
		if (in_array($actionUri, $userRights['denyAccess'])) {
			return false;
		} else {
			if (in_array($actionUri, $userRights['grantAccess'])) {
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
						$actionUri => array(
							EF_RDF_TYPE => array(
								array('type' => 'uri', 'value' => $this->uris['actionClassUri'])
							),
							EF_RDFS_LABEL => array(
								array('type' => 'literal', 'value' => $action)
							)
						)
					);
					$store = $this->knowledgeBase->getStore();
					$store->addMultipleStatements($this->uris['acModelUri'], $actionStmt, false);
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
		$userRights = $this->getUserModelRights($user->getUri());
		return $userRights['userAnyActionAllowed'];
	}

	/**
	 * Checks whether the current user has the given permission
	 * for any models.
	 *
	 * @param string $type (optional) Contains view or edit.
	 * @return boolean Returns whether allowed or denied.
	 */
	public function isAnyModelAllowed($type = 'view') {
		$this->init();
		$user = $this->getUser();
		$userRights = $this->getUserModelRights($user->getUri());
		$type = strtolower($type);
		if ($type === 'view') {
			// any model view allowed?
			if ($userRights['userAnyModelViewAllowed'] === true) {
				return true;
			} else {
				if ($userRights['userAnyModelEditAllowed'] === true) {
					// any model edit allowed? (implies view right)
					return true;
				} else {
					// not allowed!
					return false;
				}
			}
		}
		if ($type === 'edit') {
			// any model edit allowed?
			if ($userRights['userAnyModelEditAllowed'] === true) {
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
	 * Adds a right to a model for the current user.
	 *
	 * @param string $modelUri The URI of the model.
	 * @param string $type Type of access: view or edit.
	 * @param string $perm Type of permission: grant or deny.
	 * @throws Erfurt_Exception Throws an exception if wrong type was submitted or
	 * wrong perm type was submitted.
	 */
	public function setUserModelRight($modelUri, $type = 'view', $perm = 'grant') {
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
				$prop = $this->uris['propGrantModelView'];
				$right = 'grantModelView';
			} else {
				// else the permission is deny
				$prop = $this->uris['propDenyModelView'];
				$right = 'denyModelView';
			}
		} else {
			// else the type is edit
			if ($perm === 'grant') {
				$prop = $this->uris['propGrantModelEdit'];
				$right = 'grantModelEdit';
			} else {
				// else the permission is deny
				$prop = $this->uris['propDenyModelEdit'];
				$right = 'denyModelEdit';
			}
		}
		// Update the array that contains the right for the user.
		//$this->_userRights[$user->getUri()][$right][] = $modelUri;
		unset($this->userRights[$user->getUri()]);
		// TODO set the right cache tags, such that cache is invalidated!!!
		$store = $this->knowledgeBase->getStore();
		$store->addStatement(
			$this->accessControlModel->getModelUri(),
			$user->getUri(),
			$prop,
			array('type' => 'uri', 'value' => $modelUri),
			false
		);
	}

	// ------------------------------------------------------------------------
	// --- Private methods ----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Fetches the current user from the auth object.
	 *
	 * @return array Returns a user spec array on success.
	 * @throws Exception Throws an exception if no valid user is given.
	 */
	private function getUser() {
		if ($this->auth->hasIdentity()) {
			// Identity exists; get it
			return $this->auth->getIdentity();
		} else {
			throw new \Exception('No valid user was given.');
		}
	}

	/**
	 * Gets the user rights for the current user.
	 * In case the user uri was not fetched, it is fetched.
	 *
	 * @param string $userURI The URI of the user.
	 * @return array Returns an array that contains the user rights.
	 */
	private function getUserModelRights($userURI) {
		if (!isset($this->userRights[$userURI])) {
			// In this case we need to fetch the rights for the user.
			$userRights = $this->_userRightsTemplate;
			// Super admin, i.e. a user that has database rights (only for debugging purposes and only if
			// enabled in config).
			if (($userURI === $this->uris['superUserUri']) && ((boolean)$this->config->allowDbUser === true)) {
				$userRights['userAnyActionAllowed'] = true;
				$userRights['userAnyModelEditAllowed'] = true;
				$userRights['userAnyModelViewAllowed'] = true;
				$this->userRights[$userURI] = $userRights;
				return $userRights;
			}
			$sparqlQuery = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
			$sparqlQuery->setProloguePart('SELECT ?group ?p ?o')
					->setWherePart(
				'WHERE {
                                ?group ?p ?o .
                                ?group <' . $this->config->group->membership . '> <' . $userURI . '>
                            }'
			);
			if ($result = $this->sparql($this->accessControlModel, $sparqlQuery)) {
				$this->filterAccess($result, $userRights);
			}
			$sparqlQuery = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
			$sparqlQuery->setProloguePart('SELECT ?s ?p ?o')
					->setWherePart(
				'WHERE {
                                ?s ?p ?o .
                                FILTER (
                                    sameTerm(?s, <' . $userURI . '>) ||
                                    sameTerm(?o, <' . $this->config->action->class . '>)
                                )
                            }'
			);
			if ($result = $this->sparql($this->accessControlModel, $sparqlQuery)) {
				$this->filterAccess($result, $userRights);
			}
			// Now check for forbidden anyModel.
			// view
			if (in_array($this->uris['propAnyModel'], $userRights['denyModelView'])) {
				$userRights['userAnyModelViewAllowed'] = false;
				$userRights['userAnyModelEditAllowed'] = false;
				$userRights['grantModelView'] = array();
				$userRights['grantModelEdit'] = array();
			}
			// edit
			if (in_array($this->uris['propAnyModel'], $userRights['denyModelEdit'])) {
				$userRights['userAnyModelEditAllowed'] = false;
				$userRights['grantModelEdit'] = array();
			}
			$this->userRights[$userURI] = $userRights;
		}
		return $this->userRights[$userURI];
	}

	/**
	 * Filters the sparql results and saves the results in $userRights var.
	 *
	 * @param array $resultList A list of sparql results.
	 * @param array $userRights A reference to an array containing user rights.
	 */
	private function filterAccess($resultList, &$userRights) {
		$allActions = array();
		#var_dump($resultList);
		foreach ($resultList as $entry) {
			// any action allowed?
			if (($entry['o'] === $this->uris['propAnyAction'])
				&& ($entry['p'] === $this->uris['propGrantAccess'])) {
				$userRights['userAnyActionAllowed'] = true;
			} else {
				if (($entry['o'] === $this->uris['propAnyModel'])
					&& ($entry['p'] === $this->uris['propGrantModelView'])) {
					// any model view allowed?
					$userRights['userAnyModelViewAllowed'] = true;
				} else {
					if (($entry['o'] === $this->uris['propAnyModel'])
						&& ($entry['p'] === $this->uris['propGrantModelEdit'])) {
						// any model edit allowed?
						$userRights['userAnyModelEditAllowed'] = true;
					} else {
						if ($entry['p'] === $this->uris['propGrantAccess']) {
							// grant action?
							if (!in_array($entry['o'], $userRights['grantAccess'])) {
								$userRights['grantAccess'][] = $entry['o'];
							}
						} else {
							if ($entry['p'] === $this->uris['propDenyAccess']) {
								// deny action?
								if (!in_array($entry['o'], $userRights['denyAccess'])) {
									$userRights['denyAccess'][] = $entry['o'];
								}
							} else {
								if ($entry['p'] === $this->uris['propGrantModelView']) {
									// grant model view?
									if (!in_array($entry['o'], $userRights['grantModelView'])) {
										$userRights['grantModelView'][] = $entry['o'];
									}
								} else {
									if ($entry['p'] === $this->uris['propDenyModelView']) {
										// deny model view?
										if (!in_array($entry['o'], $userRights['denyModelView'])) {
											$userRights['denyModelView'][] = $entry['o'];
										}
									} else {
										if ($entry['p'] === $this->uris['propGrantModelEdit']) {
											// grant model edit?
											if (!in_array($entry['o'], $userRights['grantModelEdit'])) {
												$userRights['grantModelEdit'][] = $entry['o'];
											}
										} else {
											if ($entry['p'] === $this->uris['propDenyModelEdit']) {
												// deny model edit?
												if (!in_array($entry['o'], $userRights['denyModelEdit'])) {
													$userRights['denyModelEdit'][] = $entry['o'];
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
	 * initialisation of models, uris and rights
	 *
	 */
	private function init() {
		if ($this->isInitialized === true) {
			return;
		}
		// Reset the user rights array.
		$this->userRights = array();
		$this->config = $this->knowledgeBase->getAccessControlConfiguration();
		$this->auth = $this->knowledgeBase->getAuthentication();
		// access control informations
		$this->accessControlModel = $this->knowledgeBase->getAccessControlModel();
		// get custom uri configuration
		$this->uris['acBaseUri'] = $this->config->baseUri;
		$this->uris['acModelUri'] = $this->accessControlModel->getModelUri();
		$this->uris['anonymousUserUri'] = $this->config->user->anonymousUser;
		$this->uris['superUserUri'] = $this->config->user->superAdmin;
		$this->uris['propAnyModel'] = $this->config->models->anyModel;
		$this->uris['propGrantModelView'] = $this->config->models->grantView;
		$this->uris['propDenyModelView'] = $this->config->models->denyView;
		$this->uris['propGrantModelEdit'] = $this->config->models->grantEdit;
		$this->uris['propDenyModelEdit'] = $this->config->models->denyEdit;
		$this->uris['actionClassUri'] = $this->config->action->class;
		$this->uris['propAnyAction'] = $this->config->action->anyAction;
		$this->uris['propGrantAccess'] = $this->config->action->grant;
		$this->uris['propDenyAccess'] = $this->config->action->deny;
		$this->uris['modelClassUri'] = $this->config->models->class;
		$this->uris['actionConfigUri'] = $this->config->action->rawConfig;
		$this->isInitialized = true;
	}

	/**
	 * Executes a sparql query against the store.
	 *
	 * @param Erfurt_Rdf_Model Active model instance to query sparql.
	 * @param Erfurt_Sparql_SimpleQuery The SPARQL query.
	 * @return array Returns an array containig the result.
	 */
	private function sparql($model, $sparqlQuery) {
		$sparqlQuery->addFrom($model->getModelUri());
		$result = $model->getStore()->sparqlQuery($sparqlQuery, array(STORE_USE_AC => false));
		return $result;
	}

}

?>