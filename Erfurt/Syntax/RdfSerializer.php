<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax;
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
 * @package erfurt
 * @subpackage   syntax
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: RdfSerializer.php 4016 2009-08-13 15:21:13Z pfrischmuth $
 */
class RdfSerializer {

	/**
	 * @var string
	 */
	protected $format;

	protected $serializerAdapter;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Constructor method for a rdf serializer
	 *
	 * @param string $format
	 * @return void
	 */
	public function __construct($format) {
		$this->format = $format;
	}

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Lifecycle method after all injections
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->initializeWithFormat($this->format);
	}

	public static function normalizeFormat($format) {
		$formatMapping = array(
			'application/rdf+xml' => 'rdfxml',
			'rdfxml' => 'rdfxml',
			'rdf/xml' => 'rdfxml',
			'xml' => 'rdfxml',
			'rdf' => 'rdfxml',
			'text/plain' => 'rdfxml',
			'application/x-turtle' => 'turtle',
			'text/turtle' => 'turtle',
			'rdf/turtle' => 'turtle',
			'rdfturtle' => 'turtle',
			'turtle' => 'turtle',
			'ttl' => 'turtle',
			'nt' => 'turtle',
			'ntriple' => 'turtle',
			'rdf/n3' => 'rdfn3',
			'rdfn3' => 'rdfn3',
			'n3' => 'rdfn3',
			'application/json' => 'rdfjson',
			'json' => 'rdfjson',
			'rdfjson' => 'rdfjson',
			'rdf/json' => 'rdfjson'
		);

		if (isset($formatMapping[strtolower($format)])) {
			return $formatMapping[strtolower($format)];
		} else {
			return strtolower($format);
		}
	}

	public static function getSupportedFormats() {
		return array(
			'rdfxml' => 'RDF/XML',
			'turtle' => 'Turtle',
			'rdfjson' => 'RDF/JSON (Talis)',
			'rdfn3' => 'Notation 3'
		);
	}

	public function initializeWithFormat($format) {
		$format = self::normalizeFormat($format);
		switch ($format) {
			case 'rdfxml':
				$this->serializerAdapter = $this->objectManager->create('\Erfurt\Syntax\RdfSerializer\Adapter\RdfXml');
				break;
			case 'turtle':
			case 'rdfn3':
				$this->serializerAdapter = $this->objectManager->create('\Erfurt\Syntax\RdfSerializer\Adapter\Turtle');
				break;
			case 'rdfjson':
				$this->serializerAdapter = $this->objectManager->create('\Erfurt\Syntax\RdfSerializer\Adapter\RdfJson');
				break;
			default:
				throw new RdfSerializerException("Format '$format' not supported");
		}
	}

	public function serializeGraphToString($graphUri, $pretty = false, $useAc = true) {
		return $this->serializerAdapter->serializeGraphToString($graphUri, $pretty, $useAc);
	}

	public function serializeQueryResultToString($query, $graphUri, $pretty = false, $useAc = true) {
		return $this->serializerAdapter->serializeQueryResultToString($query, $graphUri, $pretty, $useAc);
	}

	public function serializeResourceToString($resourceUri, $graphUri, $pretty = false, $useAc = true, array $additional = array()) {
		return $this->serializerAdapter->serializeResourceToString($resourceUri, $graphUri, $pretty, $useAc, $additional);
	}

}

?>