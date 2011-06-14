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
/**
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: Turtle.php 4016 2009-08-13 15:21:13Z pfrischmuth $
 */
class Turtle implements AdapterInterface {

	protected $baseIri;
	protected $writingStarted = false;
	protected $namespaces = array();

	protected $resultString = '';

	protected $lastWrittenSubject;
	protected $lastWrittenSubjectLength = 0;
	protected $lastWrittenPredicate;
	protected $lastWrittenIriLength = 0;

	protected $newLine = false;

	protected $graphIri;

	/**
	 * @var \Erfurt\Store\Store
	 */
	protected $store;

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

	public function serializeQueryResultToString($query, $graphIri, $pretty = false, $useAc = true) {
		$this->handleGraph($graphIri, $useAc);

		$query->setLimit(1000); //needed?
		$s = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'resourceIri');
		$p = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'p');
		$o = $this->objectManager->create('Erfurt\Sparql\Query2\Variable', 'o');
		if (strstr((string)$query, '?resourceIri ?p ?o') === false) {
			if ($query instanceof \Erfurt\Sparql\Query2) {
				$query->addTriple($s, $p, $o);
			} else {
				//should not happen
				throw new \Exception('serializeQueryResultToString expects a \Erfurt\Sparql\Query2 object');
			}
		}

		if ($query instanceof \Erfurt\Sparql\Query2) {
			$query->removeAllProjectionVars();
			$query->addProjectionVar($s);
			$query->addProjectionVar($p);
			$query->addProjectionVar($o);
		} else {
			if ($query instanceof \Erfurt\Sparql\SimpleQuery) {
				$query->setProloguePart('SELECT ?resourceIri ?p ?o');
			}
		}

		$config = $this->knowledgeBase->getGeneralConfiguration();
		if (isset($config->serializer->ad)) {
			$this->startRdf($config->serializer->ad);
		} else {
			$this->startRdf();
		}

		$offset = 0;
		while (true) {
			$query->setOffset($offset);
			$result = $this->store->sparqlQuery($query, array(
															  'result_format' => 'extended',
															  'use_owl_imports' => false,
															  'use_additional_imports' => false,
															  'use_ac' => $useAc
														 ));
			foreach ($result['bindings'] as $row) {
				$s = $row['resourceIri']['value'];
				$p = $row['p']['value'];
				$o = $row['o']['value'];
				$sType = $row['resourceIri']['type'];
				$oType = $row['o']['type'];
				$lang = isset($row['o']['xml:lang']) ? $row['o']['xml:lang'] : null;
				$dType = isset($row['o']['datatype']) ? $row['o']['datatype'] : null;

				$this->handleStatement($s, $p, $o, $sType, $oType, $lang, $dType);
			}

			if (count($result['bindings']) < 1000) {
				break;
			}

			$offset += 1000;
		}

		return $this->endRdf();
	}

	public function serializeGraphToString($graphIri, $pretty = false, $useAc = true) {
		//construct query
		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT ?resourceIri ?p ?o');
		$query->addFrom($graphIri);
		$query->setWherePart('WHERE { ?resourceIri ?p ?o . }');
		$query->setOrderClause('?resourceIri ?p ?o');

		return $this->serializeQueryResultToString($query, $graphIri, $pretty, $useAc);
	}

	public function serializeResourceToString($resource, $graphIri, $pretty = false, $useAc = true, array $additional = array()) {
		$query = $this->objectManager->create('Erfurt\Sparql\SimpleQuery');
		$query->setProloguePart('SELECT ?resourceIri ?p ?o');
		$query->addFrom($graphIri);
		$query->setWherePart('WHERE { ?resourceIri ?p ?o . FILTER (sameTerm(?resourceIri, <' . $resource . '>))}'); //why not as subject
		$query->setOrderClause('?resourceIri ?p ?o');

		return $this->serializeQueryResultToString($query, $graphIri, $pretty, $useAc);
	}

	public function handleGraph($graphIri, $useAc) {
		$this->graphIri = $graphIri;
		$namespaces = $this->knowledgeBase->getNamespaces();
		foreach ($namespaces->getNamespacePrefixes($graphIri) as $prefix => $ns) {
			$this->handleNamespace($prefix, $ns);
		}
		$this->baseIri = $this->store->getGraph($graphIri, $useAc)->getBaseIri();
	}

	public function startRdf($ad = null) {
		if ($this->writingStarted) {
			$this->throwException('Document was already started.');
		}

		$this->resultString = '';
		$this->writingStarted = true;

		if (null != $ad) {
			$this->resultString .= '# ' . $ad . PHP_EOL . PHP_EOL;
		}

		if (null !== $this->baseIri) {
			$this->resultString .= '@base <' . $this->baseIri . '> .' . PHP_EOL;
		}

		foreach ($this->namespaces as $ns => $prefix) {
			$this->resultString .= '@prefix ' . $prefix . ': <' . $ns . '> .' . PHP_EOL;
		}

		$this->resultString .= PHP_EOL;

	}

	public function endRdf() {
		if (!$this->writingStarted) {
			$this->throwException('Document has not been started yet.');
		}

		if (null !== $this->lastWrittenSubject) {
			$this->write(' .' . PHP_EOL);
		}

		$this->writingStarted = false;
		return $this->resultString;
	}

	public function handleNamespace($prefix, $ns) {
		$this->addNamespace($ns, $prefix);
	}

	protected function addNamespace($ns, $prefix = null) {
		if (isset($this->namespaces[$ns])) {
			// Namespace already exists.
			return;
		}

		$counter = 0;
		$genPrefix = 'ns';

		if (null == $prefix) {
			$prefix = $genPrefix;
			$testPrefix = $prefix . $counter++;
		} else {
			$testPrefix = $prefix;
		}

		while (true) {
			if (in_array($testPrefix, array_values($this->namespaces))) {
				$testPrefix = $prefix . $counter++;
			} else {
				$this->namespaces[$ns] = $testPrefix;
				break;
			}
		}
	}

	public function handleStatement($s, $p, $o, $sType, $oType, $lang = null, $dType = null) {
		if (!$this->writingStarted) {
			$this->throwException('Document has not been started yet.');
		}

		if ($s === $this->lastWrittenSubject) {
			if ($p === $this->lastWrittenPredicate) {
				$this->write(', ');
			} else {
				$this->write(' ;');
				$this->_writeNewline();

				$this->_writePredicate($p);
				$this->lastWrittenPredicate = $p;
			}
		} else {
			if (null !== $this->lastWrittenSubject) {
				$this->write(' .');
				$this->_writeNewline(2);
			}

			$this->_writeSubject($s, $sType);
			$this->_writePredicate($p);
		}

		$this->_writeObject($o, $oType, $lang, $dType);

	}

	protected function _writeSubject($s, $sType) {
		if ($sType === 'iri') {
			$this->_writeIri($s);
			$this->lastWrittenSubjectLength = $this->lastWrittenIriLength;
		} else {
			$this->write($s);
			$this->lastWrittenSubjectLength = strlen($s);
		}

		$this->resultString .= ' ';
		$this->lastWrittenSubject = $s;
	}

	protected function _writeObject($o, $oType, $lang = null, $dType = null) {
		if ($oType === 'iri') {
			$this->_writeIri($o);
		} else {
			if ($oType === 'bnode') {
				$this->write($o);
			} else {
				if (strpos($o, "\n") !== false || strpos($o, "\r") !== false || strpos($o, "\t") !== false) {
					$this->write('"""');
					$this->write(\Erfurt\Syntax\Utils\Turtle::encodeLongString($o));
					$this->write('"""');
				} else {
					$this->write('"');
					$this->write(\Erfurt\Syntax\Utils\Turtle::encodeString($o));
					$this->write('"');
				}

				if (null !== $lang) {
					$this->write('@' . $lang);
				} else {
					if (null !== $dType) {
						$this->write('^^');
						$this->_writeIri($dType);
					}
				}
			}
		}
	}

	protected function _writePredicate($p) {
		if ($this->newLine) {
			$this->resultString .= str_repeat(' ', $this->lastWrittenSubjectLength + 1);
		}

		if ($p === \Erfurt\Vocabulary\Rdf::TYPE) {
			$this->write('a');
		} else {
			$this->_writeIri($p);
		}

		$this->resultString .= ' ';
		$this->lastWrittenPredicate = $p;
	}


	protected function _writeIri($iri) {
		$prefix = null;

		$splitIdx = \Erfurt\Syntax\Utils\Turtle::findIriSplitIndex($iri);
		if ($splitIdx !== false) {
			$ns = substr($iri, 0, $splitIdx);

			if (isset($this->namespaces[$ns])) {
				$prefix = $this->namespaces[$ns];
			} else {
				if (null !== $this->baseIri && substr($iri, 0, $splitIdx) === $this->baseIri) {
					$prefix = null;
					$iri = substr($iri, $splitIdx);
				} else {
					// We need to support large exports so we add namespaces once and write iris that do not match as
					// full iris.
					//$this->_addNamespace($ns);
					//$prefix = $this->_namespaces[$ns];
				}
			}

			if (null !== $prefix) {
				$this->write($prefix . ':' . substr($iri, $splitIdx));
				$this->lastWrittenIriLength = strlen($prefix . ':' . substr($iri, $splitIdx));
			} else {
				$this->write('<' . $iri . '>');
				$this->lastWrittenIriLength = strlen('<' . $iri . '>');
			}
		} else {
			if (null !== $this->baseIri && $iri === $this->baseIri) {
				$this->write('<>');
				$this->lastWrittenIriLength = strlen('<>');
			} else {
				$this->write('<' . $iri . '>');
				$this->lastWrittenIriLength = strlen('<' . $iri . '>');
			}

		}
	}

	protected function _writeNewline($count = 1) {
		for ($i = 0; $i < $count; ++$i) {
			$this->resultString .= PHP_EOL;
		}

		$this->newLine = true;
	}

	protected function write($value) {
		$this->resultString .= $value;
		$this->newLine = false;
	}

	public function handleComment($comment) {
		$this->_writeNewline();
		$this->write('# ' . $comment);
		$this->_writeNewline();
	}

	protected function throwException($msg) {
		throw new \Erfurt\Syntax\RdfSerializerException($msg);
	}

}

?>