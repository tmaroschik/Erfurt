<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfSerializer\Adapter\RdfXml;

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
 * An implemenation of a RDF string writer.
 *
 * @author Philipp Frischmuth <philipp@frischmuth24.de>
 * @copyright Copyright (c) 2007
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: RdfWriter.php 4016 2009-08-13 15:21:13Z pfrischmuth $
 */
class RdfWriter {

	/**
	 * @var string
	 */
	private $base;


	/**
	 * @var Erfurt_Syntax_StringWriterInterface
	 */
	private $stringWriter;

	/**
	 * An associative array where the key is the label of the blank node.
	 *
	 * @var string[]
	 */
	private $bNodes;

	/**
	 * An associative array where the key is the label of the blank node.
	 *
	 * @var int[]
	 */
	private $bNodeCount;

	/**
	 * This string array is handled like a set of namespaces.
	 *
	 * @var string[]
	 */
	private $namespaces;

	/**
	 * This Node array is an associative array of Nodes where the key is the iri of the subject
	 *
	 * @var Node[]
	 */
	private $rendered;

	/**
	 * This string array is an associative array where the key is the namespace and the value is the prefix.
	 *
	 * @var string[]
	 */
	private $qnames;

	/**
	 * This is an associative array where the key is the iri of the subject and the value is an associative array
	 * itself containing all properties for the specific subject. Each property is an indexed array again containing
	 * all objects for the property. So it looks something like this: Node[<Subject-IRI][<Predicate-IRI][0-N]
	 * @var Node[][][]
	 */
	private $subjects;

	/**
	 * @var int
	 */
	private $level;

	/**
	 * @var int
	 */
	private $maxLevel;

	/**
	 * @var int
	 */
	private static $bNodeNumber = 0;

	protected $store = null;
	protected $graphIri = null;

	protected $listArray = null;

	protected $useAc = true;

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
	 * @param Erfurt_Syntax_StringWriterInterface $stringWriter
	 * @param MemModel $model
	 */
	public function __construct($stringWriter, $useAc = true) {
		$this->resetState();
		$this->useAc = $useAc;
		$this->stringWriter = $stringWriter;
		$this->stringWriter->setDoctype(Erfurt\Vocabulary\Rdf::NS, 'RDF');
	}

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
		$this->store = $this->knowledgeBase->getStore();
	}

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	public function setGraphIri($graphIri) {
		$this->graphIri = $graphIri;
	}

	/**
	 * @see Erfurt_Syntax_RDFStringWriterInterface
	 */
	public function addNamespacePrefix($prefix, $ns) {
		$this->namespaces[] = $ns;
		$this->qnames[$ns] = $prefix;
	}

	/**
	 * @see Erfurt_Syntax_RDFStringWriterInterface
	 */
	public function serializeSubject($s, $sType, $pArray) {
		if ($sType === 'bnode' && isset($this->_renderedBNodes[$s])) {
			return;
		}

		if (isset($this->rendered[$s])) {
			return;
		}

		$this->level++;
		$propertyMap = $pArray;

		if (isset($propertyMap[Erfurt\Vocabulary\Rdf::TYPE]) && count($propertyMap[Erfurt\Vocabulary\Rdf::TYPE]) > 0) {
			if ($propertyMap[Erfurt\Vocabulary\Rdf::TYPE][0]['type'] === 'iri') {
				$this->startElement($propertyMap[Erfurt\Vocabulary\Rdf::TYPE][0]['value']);
				unset($propertyMap[Erfurt\Vocabulary\Rdf::TYPE][0]);
				$propertyMap[Erfurt\Vocabulary\Rdf::TYPE] = array_values($propertyMap[Erfurt\Vocabulary\Rdf::TYPE]);
			}
		} else {
			$this->stringWriter->startElement(Erfurt\Vocabulary\Rdf::NS, 'Description');
		}

		// add identifier
		if ($sType === 'bnode') {
			$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'nodeID', 'b' . substr($s, 2));
		} else {
			$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'about', $s);
		}

		// write short literal properties
		foreach ($propertyMap as $key => $values) {
			if (count($values) !== 1) {
				continue;
			}

			if ($values[0]['type'] === 'literal') {
				if ((!isset($values[0]['datatype']) && (!isset($values[0]['lang'])))
					&& (strlen($values[0]['value']) < 40)) {

					$prop = $key;
					$this->stringWriter->addAttribute($prop, null, $values[0]['value']);
					unset($propertyMap[$key]);
				}
			}
		}


		// write all other properties
		foreach ($propertyMap as $key => $values) {
			foreach ($values as $v) {
				$this->serializeProperty($key, $v);
			}
		}

		$this->rendered[$s] = true;

		$this->stringWriter->endElement();
		$this->level--;
	}

	/**
	 * @see Erfurt_Syntax_RDFStringWriterInterface
	 */
	public function setMaxLevel($level) {
		$this->maxLevel = $level;
	}

	/**
	 * @see Erfurt_Syntax_RDFStringWriterInterface
	 */
	public function startDocument($ad = null) {
		$this->addNamespaces();

		if (null !== $ad) {
			$this->stringWriter->setAd($ad);
		}

		$this->stringWriter->addEntity('xsd', Erfurt\Vocabulary\Xsd::NS);
		$this->stringWriter->startDocument();
		$this->stringWriter->startElement(Erfurt\Vocabulary\Rdf::NS, 'RDF');
	}

	public function linefeed($count = 1) {
		$this->stringWriter->linefeed($count);
	}

	/**
	 * @see Erfurt_Syntax_RDFStringWriterInterface
	 */
	public function endDocument() {
		$this->stringWriter->endDocument();
	}

	/**
	 * @see Erfurt_Syntax_RDFStringWriterInterface
	 */
	public function setBase($base) {
		$this->base = $base;
		$this->stringWriter->setBase($base);
	}

	public function addComment($comment) {
		$this->stringWriter->writeComment($comment);
	}

	private function addNamespaces() {

		foreach (array_unique($this->namespaces) as $ns) {

			if (isset($this->qnames[$ns])) {
				$prefix = $this->qnames[$ns];
			} else {
				continue;
			}

			$this->stringWriter->addNamespace($prefix, $ns);
			$this->stringWriter->addEntity($prefix, $ns);
		}
	}

	/**
	 * @param Node $node
	 * @return boolean
	 */
	private function isList($node) {

		if ($node['type'] === 'literal') {
			return false;
		}

		// Node is either anonymous or rdf:Nil
		if ($node['type'] === 'iri') {
			if ($node['value'] === Erfurt\Vocabulary\Rdf::NIL) {
				return true;
			}
			return false;
		}

		$listArray = $this->_getListArray();
		if (isset($listArray[$node['value']])) {
			$propertyMap = $listArray[$node['value']];
		} else {
			return false;
		}


		$child = $propertyMap['rest'];

		// child should not be rendered allready
		if (isset($this->rendered[$child])) {
			return false;
		}

		return $this->isList($child);
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function propertyBNode($value) {
		if ($value['type'] === 'bnode') {
			$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'nodeID', 'b' . substr($value['value'], 2));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function propertyList($value) {
		if (!$this->isList($value)) {
			return false;
		}

		$elements = array();
		$current = $value['value'];

		$listArray = $this->_getListArray();

		while ($current !== Erfurt\Vocabulary\Rdf::NIL) {
			$this->rendered[$current] = true;
			$propertyMap = $listArray[$current];
			$first = $propertyMap['first'];
			$elements[] = $first;
			$rest = $propertyMap['rest'];
			$current = $rest;
		}

		// write list
		$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'parseType', 'Collection');
		foreach ($elements as $e) {
			if ($this->shouldNest($e)) {
				$this->serializeSubject($e);
			} else {
				$this->stringWriter->startElement(Erfurt\Vocabulary\Rdf::NS, 'Description');
				if ($e['type'] === 'bnode') {
					$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'nodeID', 'b' . $e['value']);
				} else {
					$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'about', $e['value']);
				}
				$this->stringWriter->endElement();
			}
		}

		return true;
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function propertyLiteral($value) {
		if ($value['type'] === 'literal') {
			if (isset($value['lang'])) {
				$language = $value['lang'];
				$this->stringWriter->addAttribute('xml:lang', null, $language);
			} else {
				if (isset($value['datatype'])) {
					$datatype = $value['datatype'];
					$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'datatype', $datatype);
				}
			}

			$this->stringWriter->writeData($value['value']);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function propertyNested($value) {
		if (($value['type'] !== 'iri') || !$this->shouldNest($value)) {
			return false;
		}

		return false;
		// TODO
		#$this->serializeSubject($value);
		#return true;
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function propertyReference($value) {
		if ($value['type'] === 'iri') {
			$this->stringWriter->addAttribute(Erfurt\Vocabulary\Rdf::NS, 'resource', $value['value']);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function propertyXMLLiteral($value) {
		// TODO Implement this function!
		return false;
	}

	public function resetState() {

		$this->bNodes = array();
		$this->bNodeCount = array();
		$this->namespaces = array();
		$this->rendered = array();
		$this->level = 0;
		$this->maxLevel = 1;
	}

	/**
	 * @param Resource $value
	 * @param Node $value
	 * @throws Exception
	 */
	private function serializeProperty($property, $value) {
		$this->startElement($property);

		if (!$this->propertyList($value)
			&& !$this->propertyNested($value)
			&& !$this->propertyBNode($value)
			&& !$this->propertyReference($value)
			&& !$this->propertyXMLLiteral($value)
			&& !$this->propertyLiteral($value)) {

			#var_dump($value);exit;
			throw new \Exception('Could not serialize property ' . $property . ' with value ' . $value);
		}

		$this->stringWriter->endElement();
	}

	/**
	 * @param Node $value
	 * @return boolean
	 */
	private function shouldNest($node) {
		if ($node['type'] === 'iri') {
			if (isset($this->rendered[$node['value']])) {
				return false;
			}

			if ($this->level >= $this->maxLevel) {
				return false;
			}

			return true;
		} else {
			if ($node['type'] === 'bnode') {
				return false;
				#return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * @param string $iri
	 */
	private function startElement($iri) {
		$this->stringWriter->startElement($iri);
	}

	protected function _getListArray() {
		if (null === $this->listArray) {
			$this->sparqlForListResources();
		}

		return $this->listArray;
	}

	protected function sparqlForListResources() {

		$query = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT ?s ?first ?rest');
		$query->addFrom($this->graphIri);
		$query->setWherePart('WHERE { ?s <' . Erfurt\Vocabulary\Rdf::FIRST . '> ?first . ?s <' . Erfurt\Vocabulary\Rdf::REST . '> ?rest }');

		$result = $this->store->sparqlQuery($query, array(
														  'result_format' => 'extended',
														  'use_owl_imports' => false,
														  'use_additional_imports' => false
													 ));

		$listArray = array();
		if ($result) {
			foreach ($result['bindings'] as $row) {
				$listArray[$row['s']['value']] = array(
					'first' => $row['first']['value'],
					'rest' => $row['rest']['value']
				);
			}
		}

		$this->listArray = $listArray;
	}

	public function getContentString() {
		return $this->stringWriter->getContentString();
	}

}

?>