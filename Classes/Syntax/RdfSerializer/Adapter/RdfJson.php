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
 * This class acts as an intermediate implementation for some important formats.
 * It uses the ARC library unitl we have own implementations.
 *
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: RdfJson.php 4016 2009-08-13 15:21:13Z pfrischmuth $
 */
class RdfJson implements AdapterInterface {

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

	public function serializeResourceToString($resourceUri, $graphUri, $pretty = false, $useAc = true) {
		$triples = array();
		$store = $this->knowledgeBase->getStore();

		$sparql = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
		$sparql->setProloguePart('SELECT ?s ?p ?o');
		$sparql->addFrom($graphUri);
		$sparql->setWherePart('WHERE { ?s ?p ?o . FILTER (sameTerm(?s, <' . $resourceUri . '>)) }');
		$sparql->setOrderClause('?s ?p ?o');
		$sparql->setLimit(1000);

		$offset = 0;
		while (true) {
			$sparql->setOffset($offset);

			$result = $store->sparqlQuery($sparql, array(
														'result_format' => 'extended',
														'use_owl_imports' => false,
														'use_additional_imports' => false,
														'use_ac' => $useAc
												   ));

			$counter = 0;
			foreach ($result['bindings'] as $stm) {
				$s = $stm['s']['value'];
				$p = $stm['p']['value'];
				$o = $stm['o'];

				if (!isset($triples["$s"])) {
					$triples["$s"] = array();
				}

				if (!isset($triples["$s"]["$p"])) {
					$triples["$s"]["$p"] = array();
				}

				if ($o['type'] === 'typed-literal') {
					$triples["$s"]["$p"][] = array(
						'type' => 'literal',
						'value' => $o['value'],
						'datatype' => $o['datatype']
					);
				} else {
					if ($o['type'] === 'typed-literal') {
						$oArray = array(
							'type' => 'literal',
							'value' => $o['value']
						);

						if (isset($o['xml:lang'])) {
							$oArray['lang'] = $o['xml:lang'];
						}

						$triples["$s"]["$p"][] = $oArray;
					} else {
						$triples["$s"]["$p"][] = array(
							'type' => $o['type'],
							'value' => $o['value']
						);
					}
				}
				$counter++;
			}

			if ($counter < 1000) {
				break;
			}

			$offset += 1000;
		}

		return json_encode($triples);
	}

	public function serializeGraphToString($graphUri, $pretty = false, $useAc = true) {
		$triples = array();
		$store = $this->knowledgeBase->getStore();

		$sparql = $this->objectManager->create('\Erfurt\Sparql\SimpleQuery');
		$sparql->setProloguePart('SELECT ?s ?p ?o');
		$sparql->addFrom($graphUri);
		$sparql->setWherePart('WHERE { ?s ?p ?o }');
		$sparql->setOrderClause('?s ?p ?o');
		$sparql->setLimit(1000);

		$offset = 0;
		while (true) {
			$sparql->setOffset($offset);

			$result = $store->sparqlQuery($sparql, array(
														'result_format' => 'extended',
														'use_owl_imports' => false,
														'use_additional_imports' => false,
														'use_ac' => $useAc
												   ));

			$counter = 0;
			foreach ($result['bindings'] as $stm) {
				$s = $stm['s']['value'];
				$p = $stm['p']['value'];
				$o = $stm['o'];

				if (!isset($triples["$s"])) {
					$triples["$s"] = array();
				}

				if (!isset($triples["$s"]["$p"])) {
					$triples["$s"]["$p"] = array();
				}

				if ($o['type'] === 'typed-literal') {
					$triples["$s"]["$p"][] = array(
						'type' => 'literal',
						'value' => $o['value'],
						'datatype' => $o['datatype']
					);
				} else {
					if ($o['type'] === 'typed-literal') {
						$oArray = array(
							'type' => 'literal',
							'value' => $o['value']
						);

						if (isset($o['xml:lang'])) {
							$oArray['lang'] = $o['xml:lang'];
						}

						$triples["$s"]["$p"][] = $oArray;
					} else {
						$triples["$s"]["$p"][] = array(
							'type' => $o['type'],
							'value' => $o['value']
						);
					}
				}
				$counter++;
			}

			if ($counter < 1000) {
				break;
			}

			$offset += 1000;
		}

		return json_encode($triples);
	}

}

?>