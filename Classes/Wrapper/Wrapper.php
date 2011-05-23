<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Wrapper;
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
 * This abstract class provides the basis for dedicated data wrapper
 * implementation classes, that provide RDF data for a given IRI. Developers
 * are encouraged to utilize the built-in config and cache objects in order
 * to make wrappers customizable by the user and to avoid expensive requests
 * to be done to frequent. The default cache lifetime is one hour.
 *
 * @copyright  Copyright (c) 2009 {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @package    erfurt
 * @subpackage wrapper
 * @author     Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
abstract class Wrapper {

	// ------------------------------------------------------------------------
	// --- Constants ----------------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * States, whether statements have been added by the wrapper.
	 *
	 * @var int
	 */
	const STATEMENTS_ADDED = 10;

	/**
	 * States, whether statements have been removed by the wrapper.
	 *
	 * @var int
	 */
	const STATEMENTS_REMOVE = 20;

	/**
	 * States, whether there have not been any modifications by the wrapper.
	 *
	 * @var int
	 */
	const NO_MODIFICATIONS = 30;

	/**
	 * States, whether the result contains a key 'add', which contains data
	 * to be added.
	 *
	 * @var int
	 */
	const RESULT_HAS_ADD = 40;

	/**
	 * States, whether the result contains a key 'ns', which contains
	 * namespaces to be added.
	 *
	 * @var int
	 */
	const RESULT_HAS_NS = 45;

	/**
	 * States, whether the result contains a key 'remove', which can be used
	 * to match statements.
	 *
	 * @var int
	 */
	const RESULT_HAS_REMOVE = 50;

	/**
	 * States, whether the result contains a key 'added_count', which contains
	 * the number of triples added.
	 *
	 * @var int
	 */
	const RESULT_HAS_ADDED_COUNT = 60;

	/**
	 * States, whether the result contains a key 'removed_count', which
	 * contains the number of triples removed.
	 *
	 * @var int
	 */
	const RESULT_HAS_REMOVED_COUNT = 70;

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Contains a caching class instance.
	 *
	 * @var Erfurt_Cache_Frontend_AutoId
	 */
	protected $_cache = null;

	/**
	 * Contains the parsed configuration, iff existsing.
	 * Otherwise this property is set to false.
	 *
	 * @var Zend_Config_Ini
	 */
	protected $_config = false;

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Initializes the base wrapper class. It provides derived classes with
	 * a reference to the config object and a reference to the cache, which
	 * should be used by all implemenataions. If a derived class needs to override
	 * this method, it should call this method as the first operation.
	 *
	 * @param Zend_Config_Ini
	 */
	public function init($config) {
		$frontendOptions = array(
			'automatic_serialization' => true
		);

		$frontendAdapter = new \Erfurt\Cache\Frontend\ObjectCache($frontendOptions);

		$tmpDir = \Erfurt\App::getInstance()->getCacheDir();
		if ($tmpDir !== false) {
			$backendOptions = array(
				'cache_dir' => $tmpDir);

			$backendAdapter = new \Zend_Cache_Backend_File($backendOptions);
		} else {
			$backendAdapter = new \Erfurt\Cache\Backend\Null();
		}

		$frontendAdapter->setBackend($backendAdapter);

		$this->_cache = $frontendAdapter;
		$this->_config = $config;
	}

	// ------------------------------------------------------------------------
	// --- Abstract methods ---------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * This method returns a human-readable string that describes the wrapper.
	 *
	 * @return string A string representing a description of the wrapper.
	 */
	abstract public function getDescription();

	/**
	 * This method returns a human-readable string that identifies the wrapper.
	 * It is intended that this method is used by an application in order to
	 * present the user with a name for the specific wrapper. It will also serve
	 * as basis for further translations.
	 *
	 * @return string A string representation of the wrapper name.
	 */
	abstract public function getName();

	/**
	 * This method forms the second step in the data fetching process.
	 * If a given IRI is handled by a wrapper, this method tests whether there
	 * is data available for the IRI. In many situations this will imply,
	 * that the data is actually fetched. It is a appropriate solution to cache
	 * requested data in order to do a request only once.
	 *
	 * @param string $iri The IRI to test for available data.
	 * @param string $graphIri The IRI fro the graph to use. Some wrapper implementations
	 * may need it, e.g. to do SPARQL queries against the graph.
	 * @return boolean Returns whether there is data available for the given IRI or not.
	 * @throws Erfurt_Wrapper_Exception
	 */
	abstract public function isAvailable($iri, $graphIri);

	/**
	 * This method will be called first in most cases. It therefore should
	 * not yet fetch any data. This method is intended to match a given IRI
	 * against a certain IRI-schema and return whether the wrapper will handle
	 * such IRIs.
	 *
	 * @param string $iri The IRI to be tested.
	 * @param string $graphIri The IRI fro the graph to use. Some wrapper implementations
	 * may need it, e.g. to do SPARQL queries against the graph.
	 * @return boolean Returns whether the wrapper will handle the given IRI.
	 * @throws Erfurt_Wrapper_Exception
	 */
	abstract public function isHandled($iri, $graphIri);

	/**
	 * This method actually executes the wrapper. Whatever the internal
	 * realization is like, this method actually does the heavy lifting.
	 * This method returns an array containing the following keys:
	 *
	 *	  'status_codes': An array containing status code constants.
	 *
	 *	  'status_desc': A human readable description of the status.
	 *
	 *	  'add': (optional) A resource-centric array containing triples
	 *	  to add to the graph.
	 *
	 *	  'remove': (optional) An array, which can be used to match
	 *	  statements that will be deleted. E.g. array('s' => 'http://...',
	 *	  'p' => null, 'o' => null) would match all statements with a
	 *	  given subject.
	 *
	 *	  'added_count': (optional) Contains the number of statements
	 *	  the wrapper has already added internally.
	 *
	 *	  'removed_count': (optional) Contains the number of statements
	 *	  the wrapper has already removed internally.
	 *
	 * If the result contains a 'add' key, the value for this key is a
	 * resource-centric array of triples as proposed in [1].
	 *
	 * [1] @link http://n2.talis.com/wiki/RDF_PHP_Specification
	 *
	 * @param string $iri This is the IRI for which data should be wrapped.
	 * @param string $graphIri The IRI fro the graph to use. Some wrapper implementations
	 * may need it, e.g. to do SPARQL queries against the graph.
	 * @return array|false
	 * @throws Erfurt_Wrapper_Exception
	 */
	abstract public function run($iri, $graphIri);

}

?>