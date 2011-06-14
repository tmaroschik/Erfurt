<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfSerializer\Adapter;

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
class RdfXml implements AdapterInterface {

	protected $currentSubject = null;
	protected $currentSubjectType = null;
	protected $pArray = array();

	protected $store;
	protected $graphIri = null;

	protected $renderedTypes = array();

	protected $rdfWriter = null;

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
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
	}

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	public function serializeGraphToString($graphIri, $pretty = false, $useAc = true) {

		$xmlStringWriter = new RdfXml\StringWriterXml();
		$this->rdfWriter = new RdfXml\RdfWriter($xmlStringWriter, $useAc);

		$this->store = $this->knowledgeBase->getStore();
		$this->graphIri = $graphIri;
		$graph = $this->store->getGraph($graphIri, $useAc);

		$this->rdfWriter->setGraphIri($graphIri);

		$base = $graph->getBaseIri();
		$this->rdfWriter->setBase($base);

		$namespaces = $this->knowledgeBase->getNamespaces();

		foreach ($namespaces->getNamespacePrefixes($graphIri) as $prefix => $ns) {
			$this->rdfWriter->addNamespacePrefix($prefix, $ns);
		}

		$config = $this->knowledgeBase->getGeneralConfiguration();
		if (isset($config->serializer->ad)) {
			$this->rdfWriter->startDocument($config->serializer->ad);
		} else {
			$this->rdfWriter->startDocument();
		}

		$this->rdfWriter->setMaxLevel(10);

		$this->_serializeType('Ontology specific informations', \Erfurt\Vocabulary\Owl::ONTOLOGY);
		$this->rdfWriter->setMaxLevel(1);

		$this->_serializeType('Classes', \Erfurt\Vocabulary\Owl::OWL_CLASS);

		$this->_serializeType('Datatypes', \Erfurt\Vocabulary\Rdfs::DATATYPE);
		$this->_serializeType('Annotation properties', \Erfurt\Vocabulary\Owl::ANNOTATION_PROPERTY);
		$this->_serializeType('Datatype properties', \Erfurt\Vocabulary\Owl::DATATYPE_PROPERTY);
		$this->_serializeType('Object properties', \Erfurt\Vocabulary\Owl::OBJECT_PROPERTY);

		$this->serializeRest('Instances and untyped data');

		$this->rdfWriter->endDocument();

		return $this->rdfWriter->getContentString();
	}

	public function serializeResourceToString($resource, $graphIri, $pretty = false, $useAc = true, array $additional = array()) {

		$xmlStringWriter = new RdfXml\StringWriterXml();
		$this->rdfWriter = new RdfXml\RdfWriter($xmlStringWriter, $useAc);

		$this->store = $this->knowledgeBase->getStore();
		$this->graphIri = $graphIri;
		$graph = $this->store->getGraph($graphIri, $useAc);

		$this->rdfWriter->setGraphIri($graphIri);

		$base = $graph->getBaseIri();
		$this->rdfWriter->setBase($base);

		$namespaces = $this->knowledgeBase->getNamespaces();

		foreach ($namespaces->getNamespacePrefixes($graphIri) as $prefix => $ns) {
			$this->rdfWriter->addNamespacePrefix($prefix, $ns);
		}

		$config = $this->knowledgeBase->getGeneralConfiguration();
		if (isset($config->serializer->ad)) {
			$this->rdfWriter->startDocument($config->serializer->ad);
		} else {
			$this->rdfWriter->startDocument();
		}

		$this->rdfWriter->setMaxLevel(1);

		foreach ($additional as $s => $pArray) {
			foreach ($pArray as $p => $oArray) {
				foreach ($oArray as $o) {
					$sType = (substr($s, 0, 2) === '_:') ? 'bnode' : 'iri';
					$lang = isset($o['lang']) ? $o['lang'] : null;
					$dType = isset($o['datatype']) ? $o['datatype'] : null;

					$this->_handleStatement($s, $p, $o['value'], $sType, $o['type'], $lang, $dType);
				}
			}
		}
		$this->rdfWriter->resetState();

		$this->_serializeResource($resource, $useAc);

		$this->rdfWriter->endDocument();

		return $this->rdfWriter->getContentString();
	}

	protected function _handleStatement($s, $p, $o, $sType, $oType, $lang = null, $dType = null) {
		if (null === $this->currentSubject) {
			$this->currentSubject = $s;
			$this->currentSubjectType = $sType;
		}

		if ($s === $this->currentSubject && $sType === $this->currentSubjectType) {
			// Put the statement on the list.
			if (!isset($this->pArray[$p])) {
				$this->pArray[$p] = array();
			}

			if ($oType === 'typed-literal') {
				$oType = 'literal';
			}

			$oArray = array(
				'value' => $o,
				'type' => $oType
			);

			if (null !== $lang) {
				$oArray['lang'] = $lang;
			} else {
				if (null !== $dType) {
					$oArray['datatype'] = $dType;
				}
			}

			$this->pArray[$p][] = $oArray;
		} else {
			$this->_forceWrite();

			$this->currentSubject = $s;
			$this->currentSubjectType = $sType;
			$this->pArray = array($p => array());

			if ($oType === 'typed-literal') {
				$oType = 'literal';
			}

			$oArray = array(
				'value' => $o,
				'type' => $oType
			);

			if (null !== $lang) {
				$oArray['lang'] = $lang;
			} else {
				if (null !== $dType) {
					$oArray['datatype'] = $dType;
				}
			}

			$this->pArray[$p][] = $oArray;
		}
	}

	protected function _forceWrite() {
		if (null === $this->currentSubject) {
			return;
		}

		// Write the statements
		$this->rdfWriter->serializeSubject($this->currentSubject, $this->currentSubjectType, $this->pArray);

		$this->currentSubject = null;
		$this->currentSubjectType = null;
		$this->pArray = array();
	}

	/**
	 * Internal function, which takes a type and a description and serializes all statements of this type in a section.
	 *
	 * @param string $description A description for the given class of statements (e.g. owl:Class).
	 * @param string $class The type which to serialize (e.g. owl:Class).
	 */
	protected function _serializeType($description, $class) {
		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT DISTINCT ?s ?p ?o');
		$query->addFrom($this->graphIri);
		$query->setWherePart('WHERE { ?s ?p ?o . ?s <' . \Erfurt\Vocabulary\Rdf::TYPE . '> <' . $class . '> }');
		$query->setOrderClause('?s ?p ?o');
		$query->setLimit(1000);

		$offset = 0;
		while (true) {
			$query->setOffset($offset);

			$result = $this->store->sparqlQuery($query, array(
															  'result_format' => 'extended',
															  'use_owl_imports' => false,
															  'use_additional_imports' => false
														 ));

			if ($offset === 0 && count($result['bindings']) > 0) {
				$this->rdfWriter->addComment($description);
			}

			foreach ($result['bindings'] as $row) {
				$s = $row['s']['value'];
				$p = $row['p']['value'];
				$o = $row['o']['value'];
				$sType = $row['s']['type'];
				$oType = $row['o']['type'];
				$lang = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
				$dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

				$this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
			}

			if (count($result['bindings']) < 1000) {
				break;
			}

			$offset += 1000;
		}

		$this->_forceWrite();

		$this->renderedTypes[] = $class;
	}

	protected function serializeRest($description) {
		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT DISTINCT ?s ?p ?o');
		$query->addFrom($this->graphIri);

		$where = 'WHERE
		          { ?s ?p ?o .
		          OPTIONAL { ?s <' . \Erfurt\Vocabulary\Rdf::TYPE . '> ?o2  } .
	              FILTER (!bound(?o2) || (';

		$count = count($this->renderedTypes);
		for ($i = 0; $i < $count; ++$i) {
			$where .= '!sameTerm(?o2, <' . $this->renderedTypes[$i] . '>)';

			if ($i < $count - 1) {
				$where .= ' && ';
			}
		}

		$where .= '))}';

		$query->setWherePart($where);
		$query->setOrderClause('?s ?p ?o');
		$query->setLimit(1000);

		$offset = 0;
		while (true) {
			$query->setOffset($offset);

			$result = $this->store->sparqlQuery($query, array(
															  'result_format' => 'extended',
															  'use_owl_imports' => false,
															  'use_additional_imports' => false
														 ));

			if ($offset === 0 && count($result['bindings']) > 0) {
				$this->rdfWriter->addComment($description);
			}

			foreach ($result['bindings'] as $row) {
				$s = $row['s']['value'];
				$p = $row['p']['value'];
				$o = $row['o']['value'];
				$sType = $row['s']['type'];
				$oType = $row['o']['type'];
				$lang = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
				$dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

				$this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
			}

			if (count($result['bindings']) < 1000) {
				break;
			}

			$offset += 1000;
		}

		$this->_forceWrite();
	}

	protected function _serializeResource($resource, $useAc = true, $level = 0) {

		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT ?s ?p ?o');
		$query->addFrom($this->graphIri);
		$query->setWherePart('WHERE { ?s ?p ?o . FILTER (sameTerm(?s, <' . $resource . '>))}');
		$query->setOrderClause('?s');
		$query->setLimit(1000);

		$offset = 0;
		$bnObjects = array();

		while (true) {
			$query->setOffset($offset);

			$result = $this->store->sparqlQuery($query, array(
															  'result_format' => 'extended',
															  'use_owl_imports' => false,
															  'use_additional_imports' => false,
															  'use_ac' => $useAc
														 ));

			foreach ($result['bindings'] as $row) {
				$s = $row['s']['value'];
				$p = $row['p']['value'];
				$o = $row['o']['value'];
				$sType = $row['s']['type'];
				$oType = $row['o']['type'];
				$lang = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
				$dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

				if ($oType === 'bnode') {
					$bnObjects[] = substr($o, 2);
				}

				$this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
			}

			if (count($result['bindings']) < 1000) {
				break;
			}

			$offset += 1000;
		}
		$this->_forceWrite();

		// SCBD -> Write Bnodes, too
		if ($level <= 10) {
			foreach ($bnObjects as $bn) {
				$this->_serializeResource($bn, $useAc, $level + 1);
			}
		}

		// We only return SCBD of the TOP resource...
		if ($level > 0) {
			return;
		}

		// SCBD: Do the same for all Resources, that have the resource as object

		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT ?s ?p ?o');
		$query->addFrom($this->graphIri);
		$query->setWherePart('WHERE { ?s ?p ?o . ?s ?p2 ?o2 . FILTER (sameTerm(?o2, <' . $resource . '>)) }');
		$query->setOrderClause('?s');
		$query->setLimit(1000);

		$offset = 0;
		$bnObjects = array();

		while (true) {
			$query->setOffset($offset);

			$result = $this->store->sparqlQuery($query, array(
															  'result_format' => 'extended',
															  'use_owl_imports' => false,
															  'use_additional_imports' => false,
															  'use_ac' => $useAc
														 ));

			foreach ($result['bindings'] as $row) {
				$s = $row['s']['value'];
				$p = $row['p']['value'];
				$o = $row['o']['value'];
				$sType = $row['s']['type'];
				$oType = $row['o']['type'];
				$lang = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
				$dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

				if ($oType === 'bnode') {
					$bnObjects[] = substr($o, 2);
				}

				$this->_handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
			}

			if (count($result['bindings']) < 1000) {
				break;
			}

			$offset += 1000;
		}
		$this->_forceWrite();

		// SCBD -> Write Bnodes, too
		if ($level <= 10) {
			foreach ($bnObjects as $bn) {
				$this->_serializeResource($bn, $useAc, $level + 1);
			}
		}
	}

}

?>