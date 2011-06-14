<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Syntax\RdfParser\Adapter;

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
 * @author	Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: RdfXml.php 4283 2009-10-12 11:26:57Z c.riess.dev $
 */
class RdfXml implements AdapterInterface {

	const BLANK_NODE_PREFIX = 'node';

	/**
	 * @var string
	 */
	protected $data = null;

	/**
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * @var bool
	 */
	protected $currentElementIsEmpty = false;

	/**
	 * @var int
	 */
	protected $blankNodeCounter = 0;

	/**
	 * @var array
	 */
	protected $elementStack = array();

	/**
	 * @var string
	 */
	protected $currentXmlLang = null;

	/**
	 * @var array
	 */
	protected $statements = array();

	/**
	 * @var resource
	 */
	protected $xmlParser = null;

	/**
	 * @var string
	 */
	protected $baseIri = null;

	/**
	 * @var string
	 */
	protected $currentCharData = null;

	/**
	 * @var bool
	 */
	protected $parseToStore = false;

	/**
	 * @var string
	 */
	protected $graphIri = null;

	/**
	 * @var bool
	 */
	protected $needsAuthentication = true;

	/**
	 * @var bool
	 */
	protected $statementCounter = 0;

	/**
	 * @var bool
	 */
	protected $rdfElementParsed = false;

	/**
	 * @var array
	 */
	protected $namespaces = array();

	/**
	 * @var array
	 */
	protected $prefixes;

	/**
	 * @var array
	 */
	protected $schemata;

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Contains store
	 *
	 * @var \Erfurt\Store\Store
	 */
	protected $store;

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injector method for a \Erfurt\Store\Store
	 *
	 * @var \Erfurt\Store\Store
	 */
	public function injectStore(\Erfurt\Store\Store $store) {
		$this->store = $store;
	}

	/**
	 * Injector method for a array
	 *
	 * @var array
	 */
	public function injectPrefixes(array $prefixes) {
		$this->prefixes = $prefixes;
	}

	/**
	 * Injector method for a array
	 *
	 * @var array
	 */
	public function injectSettings(array $settings) {
		$this->schemata = $settings['Erfurt']['iri']['schemata'];
	}

	/**
	 * @throws \Erfurt\Syntax\RdfParserException
	 * @param string $dataString
	 * @param string $baseIri
	 * @return array
	 */
	public function parseFromDataString($dataString, $baseIri = null) {
		if (null !== $baseIri) {
			$this->baseIri = $baseIri;
		}
		$xmlParser = $this->getXmlParser();
		$this->data = $dataString;
		xml_parse($xmlParser, $dataString);
		if (xml_get_error_code($xmlParser) !== 0) {
			throw new \Erfurt\Syntax\RdfParserException(
				'Parsing failed: ' . xml_error_string(xml_get_error_code($xmlParser))
			);
		}
		return $this->statements;
	}

	/**
	 * @throws \Erfurt\Syntax\RdfParserException
	 * @param string $filename
	 * @return array
	 */
	public function parseFromFilename($filename) {
		$this->baseIri = $filename;
		stream_context_get_default(array(
										'http' => array(
											'header' => "Accept: application/rdf+xml"
										)));
		$fileHandle = fopen($filename, 'r');
		stream_context_get_default(array(
										'http' => array(
											'header' => ""
										)));
		if ($fileHandle === false) {
			throw new \Erfurt\Syntax\RdfParserException("Failed to open file with filename '$filename'");
		}
		$xmlParser = $this->getXmlParser();
		// Let's parse.
		while ($data = fread($fileHandle, 4096)) {
			$this->data = $data;
			xml_parse($xmlParser, $data, feof($fileHandle));
			$this->offset++;
		}
		fclose($fileHandle);
		return $this->statements;
	}

	/**
	 * @param string $filename
	 * @param string $graphIri
	 * @param bool $needsAuthentication
	 * @return bool
	 */
	public function parseFromFilenameToStore($filename, $graphIri, $needsAuthentication = true) {
		$this->parseToStore = true;
		$this->graphIri = $graphIri;
		$this->needsAuthentication = $needsAuthentication;
		$this->parseFromFilename($filename);
		$this->writeStatementsToStore();
		$this->addNamespacesToStore();
		return TRUE;
	}

	/**
	 * @param string $data
	 * @param string $graphIri
	 * @param bool $needsAuthentication
	 * @param null $baseIri
	 * @return bool
	 */
	public function parseFromDataStringToStore($data, $graphIri, $needsAuthentication = true, $baseIri = null) {
		$this->parseToStore = true;
		$this->graphIri = $graphIri;
		$this->needsAuthentication = $needsAuthentication;
		$this->parseFromDataString($data, $baseIri);
		$this->writeStatementsToStore();
		$this->addNamespacesToStore();
		return TRUE;
	}

	/**
	 * @param string $url
	 * @return array
	 */
	public function parseFromUrl($url) {
		$this->baseIri = $url;
		/** @var \Erfurt\Http\Request $request */
		$request = $this->objectManager->create('Erfurt\Http\Request');
		$request->addHeader('Accept', 'application/rdf+xml, text/plain');
		/** @var \Erfurt\Http\Client $client */
		$client = $this->objectManager->create('Erfurt\Http\Client');
		$response = $client->doRequest($request);
		return $this->parseFromDataString($response->getBody());
	}

	public function parseFromUrlToStore($url, $graphIri, $useAc = true) {
		$this->parseToStore = true;
		$this->graphIri = $graphIri;
		$this->needsAuthentication = $useAc;
		$this->parseFromUrl($url);

		$this->writeStatementsToStore();
		$this->addNamespacesToStore();

		return true;
	}

	public function parseNamespacesFromDataString($data) {
		$xmlParser = $this->getXmlParserNamespacesOnly();

		xml_parse($xmlParser, $data);

		return $this->namespaces;
	}

	public function parseNamespacesFromFilename($filename) {
		$fileHandle = fopen($filename, 'r');

		if ($fileHandle === false) {
			throw new \Erfurt\Syntax\RdfParserException("Failed to open file with filename '$filename'");
		}

		$xmlParser = $this->getXmlParserNamespacesOnly();

		// Let's parse.
		while ($data = fread($fileHandle, 4096)) {
			$this->data = $data;
			xml_parse($xmlParser, $data, feof($fileHandle));
			$this->offset++;
		}

		fclose($fileHandle);

		return $this->namespaces;
	}

	public function parseNamespacesFromUrl($url) {
		return $this->parseNamespacesFromFilename($url);
	}

	/**
	 * Call this method after parsing only. The function parseToStore will add namespaces automatically.
	 * This method is just for situations, where the namespaces are needed to after a in-memory parsing.
	 *
	 * @return array
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	protected function addNamespacesToStore() {
		/** @var \Erfurt\Namespaces\Namespaces $erfurtNamespaces */
		$erfurtNamespaces = $this->objectManager->get('Erfurt\Namespaces\Namespaces');
		foreach ($this->namespaces as $ns => $prefix) {
			try {
				$erfurtNamespaces->addNamespacePrefix($this->graphIri, $ns, $prefix);
			}
			catch (\Erfurt\Namespaces\Exception $e) {
				// We need to catch the store exception, for the namespace component throws exceptions in case a prefix
				// already exists.

				// Do nothing... just continue with the next one...
			}
		}
	}

	protected function startElement($parser, $name, $attrs) {
		if (strpos($name, ':') === false) {
			throw new \Erfurt\Syntax\RdfParserException('Invalid element name: ' . $name . '.');
		}

		if ($name === \Erfurt\Vocabulary\Rdf::NS . 'RDF') {
			if (isset($attrs[(\Erfurt\Vocabulary\Xml::NS . 'base')])) {
				$this->baseIri = $attrs[(\Erfurt\Vocabulary\Xml::NS . 'base')];
			}
			return;
		}

		$idx = xml_get_current_byte_index($parser) - $this->offset * 4096;
		if (($idx >= 0) && ($this->data[$idx] . $this->data[$idx + 1]) === '/>') {
			$this->currentElementIsEmpty = true;
		} else {
			$this->currentElementIsEmpty = false;
		}

		if (isset($attrs['http://www.w3.org/XML/1998/namespacelang'])) {
			$this->currentXmlLang = $attrs['http://www.w3.org/XML/1998/namespacelang'];
		}

		if ($this->topElementIsProperty()) {
			// In this case the surrounding element is a property, so this element is a s and/or o.
			$this->processNode($name, $attrs);
		} else {
			// This element is a property.
			$this->processProperty($name, $attrs);
		}
	}

	protected function topElementIsProperty() {
		if (count($this->elementStack) === 0 ||
			$this->peekStack(0) instanceof RdfXml\PropertyElement) {

			return true;
		} else {
			return false;
		}
	}

	protected function endElement($parser, $name) {
		$this->handleCharacterDataStatement();

		if ($this->currentElementIsEmpty) {
			$this->currentElementIsEmpty = false;
			return;
		}

		if ($name === \Erfurt\Vocabulary\Rdf::NS . 'RDF') {
			return;
		}

		$topElement = $this->peekStack(0);

		if ($topElement instanceof RdfXml\NodeElement) {
			if ($topElement->isVolatile()) {
				array_pop($this->elementStack);
			}
		} else {
			if (null === $topElement) {
				return;
			}

			if ($topElement->parseAsCollection()) {
				$lastListResource = $topElement->getLastListResource();

				if (null === $lastListResource) {
					$subject = $this->peekStack(1);

					$this->addStatement($subject->getResource(), $topElement->getIri(), \Erfurt\Vocabulary\Rdf::NIL, 'iri');
					$this->handleReification(\Erfurt\Vocabulary\Rdf::NIL);
				} else {
					$this->addStatement($lastListResource, \Erfurt\Vocabulary\Rdf::REST, \Erfurt\Vocabulary\Rdf::NIL, 'iri');
				}
			}
		}

		array_pop($this->elementStack);
		$this->currentXmlLang = null;
	}

	protected function characterData($parser, $data) {
		if (null !== $this->currentCharData) {
			$this->currentCharData .= $data;
		} else {
			$this->currentCharData = $data;
		}
	}

	protected function handleCharacterDataStatement() {
		#var_dump($this->_currentCharData);exit;
		if (null !== $this->currentCharData) {
			if (trim($this->currentCharData) === '') {
				$this->currentCharData = null;
				return;
			}

			if (!$this->topElementIsProperty()) {
				#var_dump($this->_currentCharData);exit;
				#var_dump($this->_statements);
				#var_dump($this->_elementStack);exit;
				$this->throwException('Unexpected literal.');
			}

			$propElem = $this->peekStack(0);
			if (null === $propElem) {
				return;
			}
			$dt = $propElem->getDatatype();

			$subjectElem = $this->peekStack(1);
			$this->addStatement($subjectElem->getResource(), $propElem->getIri(), trim($this->currentCharData), 'literal',
				$this->currentXmlLang, $dt);

			$this->handleReification(trim($this->currentCharData));

			$this->currentCharData = null;
		}
	}

	protected function processNode($name, &$attrs) {
		$nodeResource = $this->getNodeResource($attrs);
		#var_dump($nodeResource);
		if (null === $nodeResource) {
			return;
		}

		$nodeElem = new RdfXml\NodeElement($nodeResource);


		if (count($this->elementStack) > 0) {
			// Node can be the object or part of an rdf:List
			$subject = $this->peekStack(1);
			$predicate = $this->peekStack(0);

			if ($predicate->parseAsCollection()) {
				$lastListResource = $predicate->getLastListResource();
				$newListResource = $this->createBlankNode();

				if (null === $lastListResource) {
					// This is the first element in the list.
					$this->addStatement($subject->getResource(), $predicate->getIri(), $newListResource);
					$this->handleReification($newListResource);
				} else {
					// Not the first element in the list.
					$this->addStatement($lastListResource, \Erfurt\Vocabulary\Rdf::REST, $newListResource);
				}

				$this->addStatement($newListResource, \Erfurt\Vocabulary\Rdf::FIRST, $nodeResource);
				$predicate->setLastListResource($newListResource);
			} else {
				$this->addStatement($subject->getResource(), $predicate->getIri(), $nodeResource);
				$this->handleReification($nodeResource);
			}
		}

		if ($name !== \Erfurt\Vocabulary\Rdf::NS . 'Description') {
			// Element name is the type of the iri.
			$this->addStatement($nodeResource, \Erfurt\Vocabulary\Rdf::TYPE, $name, 'iri');
		}

		$type = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::TYPE);
		if (null !== $type) {
			$className = $this->resolveIri($type);
			$this->addStatement($nodeResource, \Erfurt\Vocabulary\Rdf::TYPE, $className, 'iri');
		}

		// Process all remaining attributes of this element.
		$this->processSubjectAttributes($nodeResource, $attrs);

		if (!$this->currentElementIsEmpty) {
			$this->elementStack[] = $nodeElem;
		}
	}

	protected function processProperty($name, &$attrs) {
		$propIri = $name;

		// List expansion rule
		if ($propIri === \Erfurt\Vocabulary\Rdf::NS . 'li') {
			$subject = $this->peekStack(0);
			$propIri = \Erfurt\Vocabulary\Rdf::NS . '_' . $subject->getNextLiCounter();
		}

		// Push the property on the stack.
		$predicate = new RdfXml\PropertyElement($propIri);
		$this->elementStack[] = $predicate;

		// Check, whether the prop has a reification id.
		$id = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'ID');
		if (null !== $id) {
			$iri = $this->buildIriFromId($id);
			$predicate->setReificationIri($iri);
		}

		// Check for rdf:parseType attribute.
		$parseType = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'parseType');
		if (null !== $parseType) {
			switch ($parseType) {
				case 'Resource':
					$objectResource = $this->createBlankNode();
					$subject = $this->peekStack(1);

					$this->addStatement($subject->getResource(), $propIri, $objectResource, 'bnode');

					if ($this->currentElementIsEmpty) {
						$this->handleReification($objectResource);
					} else {
						$object = new RdfXml\NodeElement($objectResource);
						$object->setIsVolatile(true);
						$this->elementStack[] = $object;
					}

					break;
				case 'Collection':
					if ($this->currentElementIsEmpty) {
						$subject = $this->peekStack(1);
						$this->addStatement($subject->getResource(), $propIri, \Erfurt\Vocabulary\Rdf::NIL, 'iri');
						$this->handleReification(\Erfurt\Vocabulary\Rdf::NIL);
					} else {
						$predicate->setParseAsCollection(true);
					}

					break;

				case 'Literal':
					if ($this->currentElementIsEmpty) {
						$subject = $this->peekStack(1);
						$this->addStatement($subject->getResource(), $propIri,
							'', 'literal', null, \Erfurt\Vocabulary\Rdf::NS . 'XmlLiteral');
						$this->handleReification('');
					} else {
						$predicate->setDatatype($value);
					}

					break;
			}
		} else {
			// No parseType

			if ($this->currentElementIsEmpty) {
				if (count($attrs) === 0 || (count($attrs) === 1 && isset($attrs[\Erfurt\Vocabulary\Rdf::NS . 'datatype']))) {
					// Element has no attributes, or only the optional
					// rdf:ID and/or rdf:datatype attributes.
					$subject = $this->peekStack(1);

					$dt = null;
					if (isset($attrs[\Erfurt\Vocabulary\Rdf::NS . 'datatype'])) {
						$dt = $attrs[\Erfurt\Vocabulary\Rdf::NS . 'datatype'];
					}

					$this->addStatement($subject->getResource(), $propIri, '', 'literal', $this->currentXmlLang, $dt);
					$this->handleReification('');
				} else {
					$resourceRes = $this->getPropertyResource($attrs);

					if (null === $resourceRes) {
						return;
					}

					$subject = $this->peekStack(1);

					$this->addStatement($subject->getResource(), $propIri, $resourceRes);
					$this->handleReification($resourceRes);

					$type = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::TYPE);
					if (null !== $type) {
						$className = $this->resolveIri($type);

						$this->addStatement($resourceRes, \Erfurt\Vocabulary\Rdf::TYPE, $className);
					}

					$this->processSubjectAttributes($resourceRes, $attrs);
				}
			} else {
				// Not an empty element.

				$datatype = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'datatype');
				if (null !== $datatype) {
					$predicate->setDatatype($datatype);
				}

				// Check for about attribute
				#$about = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS.'about');
				#if (null !== $about) {
				#    $aboutIri = $this->resolveIri($about);
				#    $this->addStatement($aboutIri, \Erfurt\Vocabulary\Rdf::TYPE, $predicate, 'iri');
				// TODO phil    #
				#    $this->processSubjectAttributes($aboutIri, $attrs);
				#}
			}
		}
		#var_dump($this->_currentElementIsEmpty);
		if ($this->currentElementIsEmpty) {
			array_pop($this->elementStack);
		}
	}

	protected function getPropertyResource(&$attrs) {
		$resource = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'resource');
		$nodeId = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'nodeID');

		if (null !== $resource) {
			return $this->resolveIri($resource);
		} else {
			if (null !== $nodeId) {
				return $this->createBlankNode($nodeId);
			} else {
				return $this->createBlankNode();
			}
		}
	}

	protected function processSubjectAttributes($subject, &$attrs) {
		foreach ($attrs as $key => $value) {
			$this->addStatement($subject, $key, $value, 'literal', $this->currentXmlLang, null);
		}
	}

	protected function addStatement($s, $p, $o, $oType = null, $lang = null, $dType = null) {
		if (!isset($this->statements["$s"])) {
			$this->statements["$s"] = array();
		}
		if (!isset($this->statements["$s"]["$p"])) {
			$this->statements["$s"]["$p"] = array();
		}

		if (null === $oType) {
			if (substr($o, 0, 2) === '_:') {
				$oType = 'bnode';
			} else {
				$oType = 'iri';
			}
		}

		$objectArray = array(
			'type' => $oType,
			'value' => $o
		);

		// If we have a language we use that language and datatype is string implicit.
		if ($oType === 'literal' && null !== $lang) {
			$objectArray['lang'] = $lang;
		} else {
			if ($oType === 'literal' && null !== $dType) {
				$objectArray['datatype'] = $dType;
			}
		}

		$this->statements["$s"]["$p"][] = $objectArray;
		++$this->statementCounter;

		if ($this->parseToStore && $this->statementCounter >= 1000) {
			// Write the statements
			$this->writeStatementsToStore();
		}
	}

	protected function writeStatementsToStore() {
		// Check whether graph exists.
		if (!$this->store->isGraphAvailable($this->graphIri, $this->needsAuthentication)) {
			throw new \Exception('Graph with iri ' . $this->graphIri . ' not available.');
		}

		if (count($this->statements) > 0) {
			$this->store->addMultipleStatements($this->graphIri, $this->statements, $this->needsAuthentication);
			$this->statements = array();
			$this->statementCounter = 0;
		}
	}

	protected function handleReification($value) {
		$predicate = $this->peekStack(0);

		if ($predicate->isReified()) {
			$subject = $this->peekStack(1);
			$reifRes = $predicate->getReificationIri();
			$this->reifyStatement($reifRes, $subject->getResource(), $predicate->getIri(), $value);
		}
	}

	protected function reifyStatement($reifNode, $s, $p, $o) {
		// TODO handle literals and bnodes the right way...

		$this->addStatement($reifNode, \Erfurt\Vocabulary\Rdf::TYPE, \Erfurt\Vocabulary\Rdf::NS . 'Statement');
		$this->addStatement($reifNode, \Erfurt\Vocabulary\Rdf::NS . 'subject', $s);
		$this->addStatement($reifNode, \Erfurt\Vocabulary\Rdf::NS . 'predicate', $p);
		$this->addStatement($reifNode, \Erfurt\Vocabulary\Rdf::NS . 'object', $o);
	}

	protected function getNodeResource(&$attrs) {
		$id = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'ID');
		$about = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'about');
		$nodeId = $this->removeAttribute($attrs, \Erfurt\Vocabulary\Rdf::NS . 'nodeID');

		// We could throw an exception if more than one of the above attributes
		// are given, but we want to be as tolerant as possible, so we use the
		// first given.

		if (null !== $id) {
			return $this->buildIriFromId($id);
		} else {
			if (null !== $about) {
				return $this->resolveIri($about);
			} else {
				if (null !== $nodeId) {
					return $this->createBlankNode($nodeId);
				} else {
					// Nothing given, so create a new BNode.
					return $this->createBlankNode();
				}
			}
		}
	}

	protected function removeAttribute(&$attrs, $name) {
		if (isset($attrs[$name])) {
			$value = $attrs[$name];
			unset($attrs[$name]);
			return $value;
		} else {
			return null;
		}
	}

	protected function buildIriFromId($id) {
		return $this->resolveIri('#' . $id);
	}

	protected function resolveIri($about) {
		if ($this->checkSchemas($about)) {
			return $about;
		}

		// TODO Handle all relative IRIs the right way...
		if (substr($about, 0, 1) === '#' || $about === '' || strpos($about, '/') === false) {
			// Relative IRI... Resolve against the base IRI.
			if ($this->getBaseIri()) {
				// prevent double hash (e.g. http://www.w3.org/TR/owl-guide/wine.rdf Issue 604)
				if (substr($about, 0, 1) === '#' && substr($this->getBaseIri(), -1) === '#') {
					$about = substr($about, 1);
				}
				return $this->getBaseIri() . $about;
			}
		}

		// Absolute IRI... Return it.
		return $about;
	}

	protected function checkSchemas($about) {
		$regExp = '/^(' . implode(':|', $this->schemata) . ').*$/';
		if (preg_match($regExp, $about)) {
			return true;
		} else {
			return false;
		}
	}

	protected function createBlankNode($id = null) {
		if (null === $id) {
			while (true) {
				$id = self::BLANK_NODE_PREFIX . ++$this->blankNodeCounter;
				if (!isset($this->_usedBnodeIds[$id])) {
					break;
				}
			}
		}
		$this->_usedBnodeIds[$id] = true;
		return '_:' . $id;
	}

	protected function getBaseIri() {
		if (null !== $this->baseIri) {
			return $this->baseIri;
		} else {
			return false;
		}
	}

	protected function throwException($msg) {
		throw new \Erfurt\Syntax\RdfParserException($msg);
	}

	protected function peekStack($distanceFromTop = 0) {
		$count = count($this->elementStack);
		$pos = $count - 1 - $distanceFromTop;
		if ($count > 0 && $pos >= 0) {
			return $this->elementStack[$pos];
		} else {
			return null;
		}
	}

	private function getXmlParserNamespacesOnly() {
		$xmlParser = xml_parser_create_ns(null, '');
		xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);
		xml_set_start_namespace_decl_handler($xmlParser, array($this, 'handleNamespaceDeclaration'));

		return $xmlParser;
	}

	/**
	 * @return resource
	 */
	private function getXmlParser() {
		if (null === $this->xmlParser) {
			$this->xmlParser = xml_parser_create_ns(null, '');
			// Disable case folding, for we need the iris.
			xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 1);
			xml_set_default_handler($this->xmlParser, array($this, 'handleDefault'));
			// Set the handler method for namespace definitions
			xml_set_start_namespace_decl_handler($this->xmlParser, array($this, 'handleNamespaceDeclaration'));
			//$this->_setNamespaceDeclarationHandler('handleNamespaceDeclaration');
			//xml_set_end_namespace_decl_handler($xmlParser, array(&$this, 'handleNamespaceDeclaration'));
			xml_set_character_data_handler($this->xmlParser, array($this, 'characterData'));
			//xml_set_external_entity_ref_handler($this->_xmlParser, array($this, '_handleExternalEntityRef'));
			//xml_set_processing_instruction_handler($this->_xmlParser, array($this, '_handleProcessingInstruction'));
			//xml_set_unparsed_entity_decl_handler($this->_xmlParser, array($this, '_handleUnparsedEntityDecl'));
			xml_set_element_handler(
				$this->xmlParser,
				array($this, 'startElement'),
				array($this, 'endElement')
			);
		}
		return $this->xmlParser;
	}

	protected function handleDefault($parser, $data) {
		// Handles comments
		//var_dump($data);
	}

	protected function handleNamespaceDeclaration($parser, $prefix, $iri) {
		$prefix = (string) $prefix;
		$iri = (string) $iri;
		if (!$this->rdfElementParsed) {
			if ($prefix != '' && $iri != '') {
				$this->namespaces[$iri] = $prefix;
			}
		}
	}

}

?>