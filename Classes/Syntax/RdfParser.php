<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax;

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
/**
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: RdfParser.php 3757 2009-07-23 05:47:35Z pfrischmuth $
 */
class RdfParser {

	const LOCATOR_URL = 10;
	const LOCATOR_FILE = 20;
	const LOCATOR_DATASTRING = 30;

	/**
	 * @var string
	 */
	protected $format;

	protected $parserAdapter;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Constructor method for a rdf parser
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

	public function initializeWithFormat($format) {
		$format = strtolower($format);

		switch ($format) {
			case 'rdfxml':
			case 'xml':
			case 'rdf':
				$this->parserAdapter = $this->objectManager->create('Erfurt\Syntax\RdfParser\Adapter\RdfXml');
				break;
			case 'turtle':
			case 'ttl':
			case 'nt':
			case 'ntriple':
			case 'n3':
			case 'rdfn3':
				$this->parserAdapter = $this->objectManager->create('Erfurt\Syntax\RdfParser\Adapter\Turtle');
				break;
			case 'json':
			case 'rdfjson':
				$this->parserAdapter = $this->objectManager->create('Erfurt\Syntax\RdfParser\Adapter\RdfJson');
				break;
			default:
				throw new RdfParserException("Format '$format' not supported");
		}
	}

	public function reset() {
		$this->parserAdapter->reset();
	}

	/**
	 * @param string E.g. a filename, a url or the data to parse itself.
	 * @param int One of the supported pointer types.
	 * @return array Returns an RDF/PHP array.
	 */
	public function parse($dataPointer, $pointerType, $baseIri = null) {
		if ($pointerType === self::LOCATOR_URL) {
			$result = $this->parserAdapter->parseFromUrl($dataPointer);
		} else if ($pointerType === self::LOCATOR_FILE) {
			$result = $this->parserAdapter->parseFromFilename($dataPointer);
		} else if ($pointerType === self::LOCATOR_DATASTRING) {
			$result = $this->parserAdapter->parseFromDataString($dataPointer, $baseIri);
		} else {
			throw new RdfParserException('Type of data pointer not valid.');
		}

		return $result;
	}

	/**
	 * Call this method after parsing only. The function parseToStore will add namespaces automatically.
	 * This method is just for situations, where the namespaces are needed to after a in-memory parsing.
	 *
	 * @return array
	 */
	public function getNamespaces() {
		if (method_exists($this->parserAdapter, 'getNamespaces')) {
			return $this->parserAdapter->getNamespaces();
		} else {
			return array();
		}
	}

	public function parseNamespaces($dataPointer, $pointerType) {
		if ($pointerType === self::LOCATOR_URL) {
			$result = $this->parserAdapter->parseNamespacesFromUrl($dataPointer);
		} else if ($pointerType === self::LOCATOR_FILE) {
			$result = $this->parserAdapter->parseNamespacesFromFilename($dataPointer);
		} else if ($pointerType === self::LOCATOR_DATASTRING) {
			$result = $this->parserAdapter->parseNamespacesFromDataString($dataPointer);
		} else {
			throw new RdfParserException('Type of data pointer not valid.');
		}

		return $result;
	}

	public function parseToStore($dataPointer, $pointerType, $graphIri, $useAc = true, $baseIri = null) {
		if ($pointerType === self::LOCATOR_URL) {
			$result = $this->parserAdapter->parseFromUrlToStore($dataPointer, $graphIri, $useAc);
		} else if ($pointerType === self::LOCATOR_FILE) {
			$result = $this->parserAdapter->parseFromFilenameToStore($dataPointer, $graphIri, $useAc);
		} else if ($pointerType === self::LOCATOR_DATASTRING) {
			$result = $this->parserAdapter->parseFromDataStringToStore($dataPointer, $graphIri, $useAc, $baseIri);
		} else {
			throw new RdfParserException('Type of data pointer not valid.');
		}

		return $result;
	}

}

?>