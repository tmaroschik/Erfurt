<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Namespaces;
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
 **************************************************************
/**
 * Erfurt namespace and prefix management.
 *
 * @category Erfurt
 * @package Namespaces
 * @copyright Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @author Nathanael Arndt <arndtn@gmail.com>
 * @author Norman Heino <norman.heino@gmail.com>
 */
class Namespaces implements \Erfurt\Singleton {

	/**
	 * The predicate IRI used to store prefixes.
	 * @var string
	 */
	const PREFIX_PREDICATE = 'http://ns.ontowiki.net/SysOnt/prefix';

	/**
	 * Whether to allow multiple prefixes for the same namespace IRI.
	 * @var boolean
	 */
	protected $allowMultiplePrefixes = true;

	/**
	 * Hash table for namespace storage per graph.
	 * @var array
	 */
	protected $namespaces = array();

	/**
	 * Hash table of names not to be used as prefixes.
	 * @var array
	 */
	protected $reservedNames = array();

	/**
	 * Hash table of prefixes for standard vocabularies that
	 * should always be the same.
	 * @var array
	 */
	protected $standardPrefixes = array();

	/**
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injector method for a array
	 *
	 * @var array
	 */
	public function injectPrefixes(array $prefixes) {
		$this->standardPrefixes = $prefixes;
	}

	/**
	 * Injector method for a array
	 *
	 * @var array
	 */
	public function injectSettings(array $settings) {
		if (is_array($settings['Erfurt']['iri']['schemata'])) {
			$this->reservedNames = array_flip($settings['Erfurt']['iri']['schemata']);
		}
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
	 * Adds a prefix for the namespace IRI in a given graph.
	 *
	 * @param \Erfurt\Domain\Model\Rdf\Graph|string $graph
	 * @param string $namespace
	 * @param string $prefix
	 * @throws Exception
	 */
	public function addNamespacePrefix($graph, $namespace, $prefix) {
		// safety
		$prefix = (string) $prefix;
		$namespace = (string) $namespace;
		//lowercase prefix always (for best compatibility)
		$prefix = strtolower($prefix);
		$graphPrefixes = $this->getNamespacesForGraph($graph);
		// check if namespace is a valid IRI
		if (!\Erfurt\Utility\Iri::check($namespace)) {
			throw new Exception("Given namespace '$namespace' is not a valid IRI.");
		}
		// check if prefix is a valid XML name
		if (!\Erfurt\Utils\Utils::isXmlPrefix($prefix)) {
			throw new Exception("Given prefix '$prefix' is not a valid XML name.");
		}
		// check if prefix matches a IRI scheme (http://www.iana.org/assignments/iri-schemes.html)
		if (array_key_exists($prefix, $this->reservedNames)) {
			throw new Exception("Reserved name '$prefix' cannot be used as a namespace prefix.");
		}
		// check for existence of prefixes
		if (array_key_exists($prefix, $graphPrefixes)) {
			throw new Exception("Prefix '$prefix' already exists.");
		}
		// check for multiple prefixes
		if (!$this->allowMultiplePrefixes and array_key_exists($namespace, array_flip($graphPrefixes))) {
			throw new Exception("Multiple prefixes for namespace '$namespace' not allowed.");
		}
		// add new prefix
		$graphPrefixes[$prefix] = $namespace;
		// save new set of prefixes
		$this->setNamespacesForGraph($graph, $graphPrefixes);
	}

	/**
	 * Removes the prefix for the namespaces IRI in a given graph.
	 *
	 * @param string $graph
	 * @param string $prefix
	 */
	public function deleteNamespacePrefix($graph, $prefix) {
		$graphPrefixes = $this->getNamespacesForGraph($graph);
		if (array_key_exists($prefix, $graphPrefixes)) {
			unset($graphPrefixes[$prefix]);
		}
		$this->setNamespacesForGraph($graph, $graphPrefixes);
	}

	/**
	 * @param mixed $graph
	 * @return \Erfurt\Domain\Model\Rdf\Graph
	 */
	public function getGraph($graph) {
		if (!is_object($graph)) {
			/** @var \Erfurt\Store\Store $store */
			$store = $this->objectManager->get('Erfurt\Store\Store');
			// we need to read from the system config even though the user
			// may have no direct access to it
			$graph = $store->getGraph($graph, false);
		}
		return $graph;
	}

	/**
	 * Returns the prefix for the namespace IRI in a given graph.
	 *
	 * If more than one prefixes are stored for the given namespace,
	 * the first one in alphabetical order is returned.
	 *
	 * @param string $graph
	 * @param string $namespace
	 * @return string|null
	 */
	public function getNamespacePrefix($graph, $namespace) {
		$graphPrefixes = $this->getNamespacesForGraph($graph);
		// sort reverse alphabetical
		krsort($graphPrefixes);
		// invert keys <=> values
		$prefixesByNs = array_flip($graphPrefixes);
		// stored prefix
		if (array_key_exists($namespace, $prefixesByNs)) {
			$prefix = $prefixesByNs[$namespace];
		} else {
			// try standard prefix
			if (array_key_exists($namespace, $this->standardPrefixes)) {
				$prefix = $this->standardPrefixes[$namespace];
			} else {
				// synthesize prefix
				do {
					$k = isset($k) ? $k + 1 : 0;
					$prefix = 'ns' . $k;
				} while (array_key_exists($prefix, $graphPrefixes));
			}
			// store standard or synthetic prefix
			$this->addNamespacePrefix($graph, $namespace, $prefix);
		}
		return $prefix;
	}

	/**
	 * Returns all namespace prefixes for a given graph.
	 *
	 * @param string $graph
	 * @return array
	 */
	public function getNamespacePrefixes($graph) {
		return $this->getNamespacesForGraph($graph);
	}

	/**
	 * Returns the stored namespaces indexed by their prefixes for a
	 * given graph IRI.
	 *
	 * @param string $graph The graph IRI
	 * @return array
	 */
	public function getNamespacesForGraph($graph) {
		$graph = $this->getGraph($graph);
		$graphIri = (string)$graph;

		if (!array_key_exists($graphIri, $this->namespaces)) {
			$graphNamespaces = array();

			// load graph configuration froms store
			$prefixOptions = (array)$graph->getOption(self::PREFIX_PREDICATE);

			foreach ($prefixOptions as $entry) {
				// split raw config string
				$parts = isset($entry['value']) ? explode('=', $entry['value']) : array();
				$prefix = isset($parts[0]) ? $parts[0] : '';
				$namespace = isset($parts[1]) ? $parts[1] : null;

				// store only if namespace is valid
				if (null !== $namespace) {
					$graphNamespaces[$prefix] = $namespace;
				}
			}

			// add to global store
			$this->namespaces[$graphIri] = $graphNamespaces;
		}

		return $this->namespaces[$graphIri];
	}

	/**
	 * Sets the namespace configuration for a given graph.
	 *
	 * @param string $graph
	 * @param array|null $namespaces
	 */
	public function setNamespacesForGraph($graph, $namespaces = null) {
		$graph = $this->getGraph($graph);
		$graphIri = (string)$graph;
		$namespaces = (array)$namespaces;
		$newValues = array();
		foreach ($namespaces as $prefix => $namespace) {
			$rawValue = $prefix
						. '='
						. $namespace;

			array_push($newValues, array('value' => $rawValue, 'type' => 'literal'));
		}
		try {
			// will fail if graph is not writable
			$graph->setOption(self::PREFIX_PREDICATE, $newValues);
			// update locally
			$this->namespaces[$graphIri] = $namespaces;
		}
		catch (\Erfurt\Exception $e) {
			throw new Exception(
				"Insufficient privileges to edit namespace prefixes for graph '$graphIri'.");
		}
	}

}

?>