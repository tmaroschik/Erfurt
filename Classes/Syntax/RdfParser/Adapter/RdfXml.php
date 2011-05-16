<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfParser\Adapter;
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
 * @version   $Id: RdfXml.php 4283 2009-10-12 11:26:57Z c.riess.dev $
 */
class RdfXml implements AdapterInterface {

	protected $_data = null;
	protected $_offset = 0;
	protected $_currentElementIsEmpty = false;

	const BNODE_PREFIX = 'node';

	protected $_bnodeCounter = 0;

	protected $_elementStack = array();
	protected $_currentXmlLang = null;

	protected $_statements = array();

	protected $_xmlParser = null;
	protected $_baseIri = null;

	protected $_currentCharData = null;

	protected $_parseToStore = false;
	protected $_graphIri = null;
	protected $_useAc = true;
	protected $_stmtCounter = 0;

	protected $_rdfElementParsed = false;

	protected $_namespaces = array();

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
	}

	public function parseFromDataString($dataString, $baseIri = null) {
		if (null !== $baseIri) {
			$this->_baseIri = $baseIri;
		}

		$xmlParser = $this->_getXmlParser();

		$this->_data = $dataString;
		xml_parse($xmlParser, $dataString);

		if (xml_get_error_code($xmlParser) !== 0) {
			throw new \Erfurt\Syntax\RdfParserException(
				'Parsing failed: ' . xml_error_string(xml_get_error_code($xmlParser))
			);
		}

		return $this->_statements;
	}

	public function parseFromFilename($filename) {
		$this->_baseIri = $filename;

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

		$xmlParser = $this->_getXmlParser();

		// Let's parse.
		while ($data = fread($fileHandle, 4096)) {
			$this->_data = $data;
			xml_parse($xmlParser, $data, feof($fileHandle));
			$this->_offset++;
		}

		fclose($fileHandle);

		return $this->_statements;
	}

	public function parseFromFilenameToStore($filename, $graphIri, $useAc = true) {
		$this->_parseToStore = true;
		$this->_graphIri = $graphIri;
		$this->_useAc = $useAc;
		$this->parseFromFilename($filename);

		$this->_writeStatementsToStore();
		$this->_addNamespacesToStore();

		return true;
	}

	public function parseFromDataStringToStore($data, $graphIri, $useAc = true, $baseIri = null) {
		$this->_parseToStore = true;
		$this->_graphIri = $graphIri;
		$this->_useAc = $useAc;
		$this->parseFromDataString($data, $baseIri);

		$this->_writeStatementsToStore();
		$this->_addNamespacesToStore();

		return true;
	}

	public function parseFromUrl($url) {
		$this->_baseIri = $url;

		$client = $this->knowledgeBase->getHttpClient($url, array(
																	  'maxredirects' => 10,
																	  'timeout' => 30
																 ));

		$client->setHeaders('Accept', 'application/rdf+xml, text/plain');
		$response = $client->request();

		return $this->parseFromDataString($response->getBody());
	}

	public function parseFromUrlToStore($url, $graphIri, $useAc = true) {
		$this->_parseToStore = true;
		$this->_graphIri = $graphIri;
		$this->_useAc = $useAc;
		$this->parseFromUrl($url);

		$this->_writeStatementsToStore();
		$this->_addNamespacesToStore();

		return true;
	}

	public function parseNamespacesFromDataString($data) {
		$xmlParser = $this->_getXmlParserNamespacesOnly();

		xml_parse($xmlParser, $data);

		return $this->_namespaces;
	}

	public function parseNamespacesFromFilename($filename) {
		$fileHandle = fopen($filename, 'r');

		if ($fileHandle === false) {
			throw new \Erfurt\Syntax\RdfParserException("Failed to open file with filename '$filename'");
		}

		$xmlParser = $this->_getXmlParserNamespacesOnly();

		// Let's parse.
		while ($data = fread($fileHandle, 4096)) {
			$this->_data = $data;
			xml_parse($xmlParser, $data, feof($fileHandle));
			$this->_offset++;
		}

		fclose($fileHandle);

		return $this->_namespaces;
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
		return $this->_namespaces;
	}

	protected function _addNamespacesToStore() {
		$erfurtNamespaces = $this->knowledgeBase->getNamespaces();
		foreach ($this->_namespaces as $ns => $prefix) {
			try {
				$erfurtNamespaces->addNamespacePrefix($this->_graphIri, $ns, $prefix);
			}
			catch (\Erfurt\Namespaces\Exception $e) {
				// We need to catch the store exception, for the namespace component throws exceptions in case a prefix
				// already exists.

				// Do nothing... just continue with the next one...
			}
		}
	}

	protected function _startElement($parser, $name, $attrs) {
		if (strpos($name, ':') === false) {
			throw new \Erfurt\Syntax\RdfParserException('Invalid element name: ' . $name . '.');
		}

		if ($name === Erfurt\Vocabulary\Rdf::NS . 'RDF') {
			if (isset($attrs[(Erfurt\Vocabulary\Xml::NS . 'base')])) {
				$this->_baseIri = $attrs[(Erfurt\Vocabulary\Xml::NS . 'base')];
			}
			return;
		}

		$idx = xml_get_current_byte_index($parser) - $this->_offset * 4096;
		if (($idx >= 0) && ($this->_data[$idx] . $this->_data[$idx + 1]) === '/>') {
			$this->_currentElementIsEmpty = true;
		} else {
			$this->_currentElementIsEmpty = false;
		}

		if (isset($attrs['http://www.w3.org/XML/1998/namespacelang'])) {
			$this->_currentXmlLang = $attrs['http://www.w3.org/XML/1998/namespacelang'];
		}

		if ($this->_topElemIsProperty()) {
			// In this case the surrounding element is a property, so this element is a s and/or o.
			$this->_processNode($name, $attrs);
		} else {
			// This element is a property.
			$this->_processProperty($name, $attrs);
		}
	}

	protected function _topElemIsProperty() {
		if (count($this->_elementStack) === 0 ||
			$this->_peekStack(0) instanceof RdfXml\PropertyElement) {

			return true;
		} else {
			return false;
		}
	}

	protected function _endElement($parser, $name) {
		$this->_handleCharDataStatement();

		if ($this->_currentElementIsEmpty) {
			$this->_currentElementIsEmpty = false;
			return;
		}

		if ($name === Erfurt\Vocabulary\Rdf::NS . 'RDF') {
			return;
		}

		$topElement = $this->_peekStack(0);

		if ($topElement instanceof RdfXml\NodeElement) {
			if ($topElement->isVolatile()) {
				array_pop($this->_elementStack);
			}
		} else {
			if (null === $topElement) {
				return;
			}

			if ($topElement->parseAsCollection()) {
				$lastListResource = $topElement->getLastListResource();

				if (null === $lastListResource) {
					$subject = $this->_peekStack(1);

					$this->_addStatement($subject->getResource(), $topElement->getIri(), Erfurt\Vocabulary\Rdf::NIL, 'iri');
					$this->_handleReification(Erfurt\Vocabulary\Rdf::NIL);
				} else {
					$this->_addStatement($lastListResource, Erfurt\Vocabulary\Rdf::REST, Erfurt\Vocabulary\Rdf::NIL, 'iri');
				}
			}
		}

		array_pop($this->_elementStack);
		$this->_currentXmlLang = null;
	}

	protected function _characterData($parser, $data) {
		if (null !== $this->_currentCharData) {
			$this->_currentCharData .= $data;
		} else {
			$this->_currentCharData = $data;
		}
	}

	protected function _handleCharDataStatement() {
		#var_dump($this->_currentCharData);exit;
		if (null !== $this->_currentCharData) {
			if (trim($this->_currentCharData) === '') {
				$this->_currentCharData = null;
				return;
			}

			if (!$this->_topElemIsProperty()) {
				#var_dump($this->_currentCharData);exit;
				#var_dump($this->_statements);
				#var_dump($this->_elementStack);exit;
				$this->_throwException('Unexpected literal.');
			}

			$propElem = $this->_peekStack(0);
			if (null === $propElem) {
				return;
			}
			$dt = $propElem->getDatatype();

			$subjectElem = $this->_peekStack(1);
			$this->_addStatement($subjectElem->getResource(), $propElem->getIri(), trim($this->_currentCharData), 'literal',
								 $this->_currentXmlLang, $dt);

			$this->_handleReification(trim($this->_currentCharData));

			$this->_currentCharData = null;
		}
	}

	protected function _processNode($name, &$attrs) {
		$nodeResource = $this->_getNodeResource($attrs);
		#var_dump($nodeResource);
		if (null === $nodeResource) {
			return;
		}

		$nodeElem = new RdfXml\NodeElement($nodeResource);


		if (count($this->_elementStack) > 0) {
			// Node can be the object or part of an rdf:List
			$subject = $this->_peekStack(1);
			$predicate = $this->_peekStack(0);

			if ($predicate->parseAsCollection()) {
				$lastListResource = $predicate->getLastListResource();
				$newListResource = $this->_createBNode();

				if (null === $lastListResource) {
					// This is the first element in the list.
					$this->_addStatement($subject->getResource(), $predicate->getIri(), $newListResource);
					$this->_handleReification($newListResource);
				} else {
					// Not the first element in the list.
					$this->_addStatement($lastListResource, Erfurt\Vocabulary\Rdf::REST, $newListResource);
				}

				$this->_addStatement($newListResource, Erfurt\Vocabulary\Rdf::FIRST, $nodeResource);
				$predicate->setLastListResource($newListResource);
			} else {
				$this->_addStatement($subject->getResource(), $predicate->getIri(), $nodeResource);
				$this->_handleReification($nodeResource);
			}
		}

		if ($name !== Erfurt\Vocabulary\Rdf::NS . 'Description') {
			// Element name is the type of the iri.
			$this->_addStatement($nodeResource, Erfurt\Vocabulary\Rdf::TYPE, $name, 'iri');
		}

		$type = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::TYPE);
		if (null !== $type) {
			$className = $this->_resolveIri($type);
			$this->_addStatement($nodeResource, Erfurt\Vocabulary\Rdf::TYPE, $className, 'iri');
		}

		// Process all remaining attributes of this element.
		$this->_processSubjectAttributes($nodeResource, $attrs);

		if (!$this->_currentElementIsEmpty) {
			$this->_elementStack[] = $nodeElem;
		}
	}

	protected function _processProperty($name, &$attrs) {
		$propIri = $name;

		// List expansion rule
		if ($propIri === Erfurt\Vocabulary\Rdf::NS . 'li') {
			$subject = $this->_peekStack(0);
			$propIri = Erfurt\Vocabulary\Rdf::NS . '_' . $subject->getNextLiCounter();
		}

		// Push the property on the stack.
		$predicate = new RdfXml\PropertyElement($propIri);
		$this->_elementStack[] = $predicate;

		// Check, whether the prop has a reification id.
		$id = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'ID');
		if (null !== $id) {
			$iri = $this->_buildIriFromId($id);
			$predicate->setReificationIri($iri);
		}

		// Check for rdf:parseType attribute.
		$parseType = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'parseType');
		if (null !== $parseType) {
			switch ($parseType) {
				case 'Resource':
					$objectResource = $this->_createBNode();
					$subject = $this->_peekStack(1);

					$this->_addStatement($subject->getResource(), $propIri, $objectResource, 'bnode');

					if ($this->_currentElementIsEmpty) {
						$this->_handleReification($objectResource);
					} else {
						$object = new RdfXml\NodeElement($objectResource);
						$object->setIsVolatile(true);
						$this->_elementStack[] = $object;
					}

					break;
				case 'Collection':
					if ($this->_currentElementIsEmpty) {
						$subject = $this->_peekStack(1);
						$this->_addStatement($subject->getResource(), $propIri, Erfurt\Vocabulary\Rdf::NIL, 'iri');
						$this->_handleReification(Erfurt\Vocabulary\Rdf::NIL);
					} else {
						$predicate->setParseAsCollection(true);
					}

					break;

				case 'Literal':
					if ($this->_currentElementIsEmpty) {
						$subject = $this->_peekStack(1);
						$this->_addStatement($subject->getResource(), $propIri,
											 '', 'literal', null, Erfurt\Vocabulary\Rdf::NS . 'XmlLiteral');
						$this->_handleReification('');
					} else {
						$predicate->setDatatype($value);
					}

					break;
			}
		} else {
			// No parseType

			if ($this->_currentElementIsEmpty) {
				if (count($attrs) === 0 || (count($attrs) === 1 && isset($attrs[Erfurt\Vocabulary\Rdf::NS . 'datatype']))) {
					// Element has no attributes, or only the optional
					// rdf:ID and/or rdf:datatype attributes.
					$subject = $this->_peekStack(1);

					$dt = null;
					if (isset($attrs[Erfurt\Vocabulary\Rdf::NS . 'datatype'])) {
						$dt = $attrs[Erfurt\Vocabulary\Rdf::NS . 'datatype'];
					}

					$this->_addStatement($subject->getResource(), $propIri, '', 'literal', $this->_currentXmlLang, $dt);
					$this->_handleReification('');
				} else {
					$resourceRes = $this->_getPropertyResource($attrs);

					if (null === $resourceRes) {
						return;
					}

					$subject = $this->_peekStack(1);

					$this->_addStatement($subject->getResource(), $propIri, $resourceRes);
					$this->_handleReification($resourceRes);

					$type = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::TYPE);
					if (null !== $type) {
						$className = $this->_resolveIri($type);

						$this->_addStatement($resourceRes, Erfurt\Vocabulary\Rdf::TYPE, $className);
					}

					$this->_processSubjectAttributes($resourceRes, $attrs);
				}
			} else {
				// Not an empty element.

				$datatype = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'datatype');
				if (null !== $datatype) {
					$predicate->setDatatype($datatype);
				}

				// Check for about attribute
				#$about = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS.'about');
				#if (null !== $about) {
				#    $aboutIri = $this->_resolveIri($about);
				#    $this->_addStatement($aboutIri, Erfurt\Vocabulary\Rdf::TYPE, $predicate, 'iri');
				// TODO phil    #
				#    $this->_processSubjectAttributes($aboutIri, $attrs);
				#}
			}
		}
		#var_dump($this->_currentElementIsEmpty);
		if ($this->_currentElementIsEmpty) {
			array_pop($this->_elementStack);
		}
	}

	protected function _getPropertyResource(&$attrs) {
		$resource = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'resource');
		$nodeId = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'nodeID');

		if (null !== $resource) {
			return $this->_resolveIri($resource);
		} else {
			if (null !== $nodeId) {
				return $this->_createBNode($nodeId);
			} else {
				return $this->_createBNode();
			}
		}
	}

	protected function _processSubjectAttributes($subject, &$attrs) {
		foreach ($attrs as $key => $value) {
			$this->_addStatement($subject, $key, $value, 'literal', $this->_currentXmlLang, null);
		}
	}

	protected function _addStatement($s, $p, $o, $oType = null, $lang = null, $dType = null) {
		if (!isset($this->_statements["$s"])) {
			$this->_statements["$s"] = array();
		}
		if (!isset($this->_statements["$s"]["$p"])) {
			$this->_statements["$s"]["$p"] = array();
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

		$this->_statements["$s"]["$p"][] = $objectArray;
		++$this->_stmtCounter;

		if ($this->_parseToStore && $this->_stmtCounter >= 1000) {
			// Write the statements
			$this->_writeStatementsToStore();
		}
	}

	protected function _writeStatementsToStore() {
		// Check whether graph exists.
		$store = $this->knowledgeBase->getStore();

		if (!$store->isGraphAvailable($this->_graphIri, $this->_useAc)) {
			throw new \Exception('Graph with iri ' . $this->_graphIri . ' not available.');
		}

		if (count($this->_statements) > 0) {
			$store->addMultipleStatements($this->_graphIri, $this->_statements, $this->_useAc);
			$this->_statements = array();
			$this->_stmtCounter = 0;
		}
	}

	protected function _handleReification($value) {
		$predicate = $this->_peekStack(0);

		if ($predicate->isReified()) {
			$subject = $this->_peekStack(1);
			$reifRes = $predicate->getReificationIri();
			$this->_reifyStatement($reifRes, $subject->getResource(), $predicate->getIri(), $value);
		}
	}

	protected function _reifyStatement($reifNode, $s, $p, $o) {
		// TODO handle literals and bnodes the right way...

		$this->_addStatement($reifNode, Erfurt\Vocabulary\Rdf::TYPE, Erfurt\Vocabulary\Rdf::NS . 'Statement');
		$this->_addStatement($reifNode, Erfurt\Vocabulary\Rdf::NS . 'subject', $s);
		$this->_addStatement($reifNode, Erfurt\Vocabulary\Rdf::NS . 'predicate', $p);
		$this->_addStatement($reifNode, Erfurt\Vocabulary\Rdf::NS . 'object', $o);
	}

	protected function _getNodeResource(&$attrs) {
		$id = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'ID');
		$about = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'about');
		$nodeId = $this->_removeAttribute($attrs, Erfurt\Vocabulary\Rdf::NS . 'nodeID');

		// We could throw an exception if more than one of the above attributes
		// are given, but we want to be as tolerant as possible, so we use the
		// first given.

		if (null !== $id) {
			return $this->_buildIriFromId($id);
		} else {
			if (null !== $about) {
				return $this->_resolveIri($about);
			} else {
				if (null !== $nodeId) {
					return $this->_createBNode($nodeId);
				} else {
					// Nothing given, so create a new BNode.
					return $this->_createBNode();
				}
			}
		}
	}

	protected function _removeAttribute(&$attrs, $name) {
		if (isset($attrs[$name])) {
			$value = $attrs[$name];
			unset($attrs[$name]);
			return $value;
		} else {
			return null;
		}
	}

	protected function _buildIriFromId($id) {
		return $this->_resolveIri('#' . $id);
	}

	protected function _resolveIri($about) {
		if ($this->_checkSchemas($about)) {
			return $about;
		}

		// TODO Handle all relative IRIs the right way...
		if (substr($about, 0, 1) === '#' || $about === '' || strpos($about, '/') === false) {
			// Relative IRI... Resolve against the base IRI.
			if ($this->_getBaseIri()) {
				// prevent double hash (e.g. http://www.w3.org/TR/owl-guide/wine.rdf Issue 604)
				if (substr($about, 0, 1) === '#' && substr($this->_getBaseIri(), -1) === '#') {
					$about = substr($about, 1);
				}
				return $this->_getBaseIri() . $about;
			}
		}

		// Absolute IRI... Return it.
		return $about;
	}

	protected function _checkSchemas($about) {
		$schemataArray = $this->knowledgeBase->getIriConfiguration()->schemata->toArray();

		$regExp = '/^(' . implode(':|', $schemataArray) . ').*$/';
		if (preg_match($regExp, $about)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _createBNode($id = null) {

		if (null === $id) {
			while (true) {
				$id = self::BNODE_PREFIX . ++$this->_bnodeCounter;

				if (!isset($this->_usedBnodeIds[$id])) {
					break;
				}
			}
		}

		$this->_usedBnodeIds[$id] = true;
		return '_:' . $id;
	}

	protected function _getBaseIri() {
		if (null !== $this->_baseIri) {
			return $this->_baseIri;
		} else {
			return false;
		}
	}

	protected function _throwException($msg) {
		throw new \Erfurt\Syntax\RdfParserException($msg);
	}

	protected function _peekStack($distanceFromTop = 0) {
		$count = count($this->_elementStack);
		$pos = $count - 1 - $distanceFromTop;
		if ($count > 0 && $pos >= 0) {
			return $this->_elementStack[$pos];
		} else {
			return null;
		}
	}

	private function _getXmlParserNamespacesOnly() {
		$xmlParser = xml_parser_create_ns(null, '');

		xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);

		xml_set_start_namespace_decl_handler($xmlParser, array($this, '_handleNamespaceDeclaration'));

		return $xmlParser;
	}

	private function _getXmlParser() {
		if (null === $this->_xmlParser) {
			$this->_xmlParser = xml_parser_create_ns(null, '');

			// Disable case folding, for we need the iris.
			xml_parser_set_option($this->_xmlParser, XML_OPTION_CASE_FOLDING, 0);

			xml_parser_set_option($this->_xmlParser, XML_OPTION_SKIP_WHITE, 1);

			xml_set_default_handler($this->_xmlParser, array($this, '_handleDefault'));

			// Set the handler method for namespace definitions
			xml_set_start_namespace_decl_handler($this->_xmlParser, array($this, '_handleNamespaceDeclaration'));

			//$this->_setNamespaceDeclarationHandler('_handleNamespaceDeclaration');

			//xml_set_end_namespace_decl_handler($xmlParser, array(&$this, '_handleNamespaceDeclaration'));

			xml_set_character_data_handler($this->_xmlParser, array($this, '_characterData'));

			//xml_set_external_entity_ref_handler($this->_xmlParser, array($this, '_handleExternalEntityRef'));

			//xml_set_processing_instruction_handler($this->_xmlParser, array($this, '_handleProcessingInstruction'));

			//xml_set_unparsed_entity_decl_handler($this->_xmlParser, array($this, '_handleUnparsedEntityDecl'));

			xml_set_element_handler(
				$this->_xmlParser,
				array($this, '_startElement'),
				array($this, '_endElement')
			);
		}

		return $this->_xmlParser;
	}

	protected function _handleDefault($parser, $data) {
		// Handles comments
		//var_dump($data);
	}

	protected function _handleNamespaceDeclaration($parser, $prefix, $iri) {
		$prefix = (string)$prefix;
		$iri = (string)$iri;

		if (!$this->_rdfElementParsed) {
			if ($prefix != '' && $iri != '') {
				$this->_namespaces[$iri] = $prefix;
			}
		}
	}

}

?>