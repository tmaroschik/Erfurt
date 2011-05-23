<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb\ResultRenderer;
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
 * Result renderer that creates a text array
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @subpackage sparql
 * @author Christian Weiske <cweiske@cweiske.de>
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @license http://www.gnu.org/licenses/lgpl.html LGPL
 * @version	$Id: $
 */
use \Erfurt\Sparql;
use \Erfurt\Sparql\EngineDb;
class Plain implements EngineDb\ResultRenderer {

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	protected $iriValues = array();
	protected $literalValues = array();

	protected $_vars = null;

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Converts the database results into the desired output format
	 * and returns the result.
	 *
	 * @param array $arRecordSets Array of (possibly several) SQL query results.
	 * @param Sparql\Query $query SPARQL query object
	 * @param $engine Sparql Engine to query the database
	 * @return array
	 */
	public function convertFromDbResults($arRecordSets, Sparql\Query $query, $engine, $vars) {
		$this->query = $query;
		$this->engine = $engine;
		$this->_vars = $vars;
		$strResultForm = $this->query->getResultForm();
		switch ($strResultForm) {
			case 'construct':
			case 'select':
			case 'select distinct':
				switch ($strResultForm) {
					case 'construct':
						$arResult = $this->_getVariableArrayFromRecordSets($arRecordSets, $strResultForm, true);
						break;
					default:
						$arResult = $this->_getVariableArrayFromRecordSets($arRecordSets, $strResultForm, false);
						if (count($this->iriValues) > 0 || count($this->literalValues) > 0) {
							// If the query contains a ORDER BY wen need to reorder the result
							$sm = $query->getSolutionModifier();
							if (null !== $sm['order by']) {
								foreach ($sm['order by'] as $order) {
									$n = count($arResult);
									$id = ltrim($order['val'], '?$');
									while (true) {
										$hasChanged = false;
										for ($i = 0; $i < $n - 1; ++$i) {
											switch ($order['type']) {
												case 'desc':
													if ($arResult[$i][$id] < $arResult[($i + 1)][$id]) {
														$dummy = $arResult[$i][$id];
														$arResult[$i][$id] = $arResult[($i + 1)][$id];
														$arResult[($i + 1)][$id] = $dummy;
														$hasChanged = true;
													}
													break;
												case 'asc':
												default:
													if ($arResult[$i][$id] > $arResult[($i + 1)][$id]) {
														$dummy = $arResult[$i][$id];
														$arResult[$i][$id] = $arResult[($i + 1)][$id];
														$arResult[($i + 1)][$id] = $dummy;
														$hasChanged = true;
													}
													break;
											}
										}
										$n--;
										if (!$hasChanged && ($n === 0)) {
											break;
										}
									}
								}
							}
						}
				}
				//some result forms need more modification
				switch ($strResultForm) {
					case 'construct';
						$arResult = $this->_constructGraph(
							$arResult,
							$this->query->getConstructPattern()
						);
						break;
					case 'describe';
						$arResult = $this->describeGraph($arResult);
						break;
				}
				return $arResult;
				break;
			case 'count':
			case 'count-distinct':
			case 'ask':
				if (count($arRecordSets) > 1) {
					throw new \Erfurt\Exception('More than one result set for a ' . $strResultForm . ' query!');
				}
				$nCount = 0;
				foreach ($arRecordSets[0] as $row) {
					$nCount += intval($row['count']);
					break;
				}
				if ($strResultForm == 'ask') {
					return ($nCount > 0) ? true : false;
				} else {
					return $nCount;
				}
				break;
			case 'describe':
			default:
				throw new \Exception('Yet not supported: ' . $strResultForm);
		}

	}

	// ------------------------------------------------------------------------
	// --- Protected methods --------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Constructs a result graph.
	 *
	 * @param  array $arVartable A table containing the result vars and their bindings.
	 * @param  Erfurt_Sparql_GraphPattern  $constructPattern The CONSTRUCT pattern.
	 * @return array
	 */
	protected function _constructGraph($arVartable, $constructPattern) {
		$resultGraph = array();
		if (!$arVartable) {
			return $resultGraph;
		}
		$tp = $constructPattern->getTriplePatterns();
		$bnode = 0;
		foreach ($arVartable as $value) {
			foreach ($tp as $triple) {
				$subVar = substr($triple->getSubject(), 1);
				$predVar = substr($triple->getPredicate(), 1);
				$objVar = substr($triple->getObject(), 1);
				$sub = $value["$subVar"]['value'];
				$pred = $value["$predVar"]['value'];
				$obj = $value["$objVar"];
				if (!isset($resultGraph["$sub"])) {
					$resultGraph["$sub"] = array();
				}
				if (!isset($resultGraph["$sub"]["$pred"])) {
					$resultGraph["$sub"]["$pred"] = array();
				}
				$resultGraph["$sub"]["$pred"][] = $obj;
			}
		}
		return $resultGraph;
	}

	protected function _createBlankNode($id) {
		return array(
			'type' => 'bnode',
			'value' => $id
		);
	}

	protected function _createLiteral($value, $language, $datatype) {
		$retVal = array(
			'type' => 'literal',
			'value' => $value
		);
		if ((null !== $language)) {
			$retVal['lang'] = $language;
		} else {
			if ((null !== $datatype)) {
				$retVal['datatype'] = $datatype;
			}
		}
		return $retVal;
	}

	/**
	 * Creates an RDF object object contained in the given $dbRecordSet object.
	 *
	 * @see convertFromDbResult() to understand $strVarBase necessity
	 *
	 * @param array $dbRecordSet
	 * @param string $strVarBase Prefix of the columns the recordset fields have.
	 * @return string RDF triple object resource object.
	 */
	protected function _createObjectFromDbRecordSetPart($row, $strVarBase, $strVar, $asArray = false) {
		$strVarName = (string)$strVar;
		if ($row[$this->_vars[$strVarName]['sql_value']] === null) {
			return '';
		}
		$result = null;
		switch ($row[$this->_vars[$strVarName]['sql_is']]) {
			case 0:
				if ($row[$this->_vars[$strVarName]['sql_ref']] === null) {
					$result = $this->_createResource($row[$this->_vars[$strVarName]['sql_value']]);
				} else {
					$result = $this->_createResource(
						$this->iriValues[$row[$this->_vars[$strVarName]['sql_ref']]]);
				}
				break;
			case 1:
				if ($row[$this->_vars[$strVarName]['sql_ref']] === null) {
					$result = $this->_createBlankNode($row[$this->_vars[$strVarName]['sql_value']]);
				} else {
					$result = $this->_createBlankNode(
						$this->iriValues[$row[$this->_vars[$strVarName]['sql_ref']]]);
				}
				break;
			default:
				if ($row[$this->_vars[$strVarName]['sql_ref']] === null) {
					$result = $this->_createLiteral(
						$row[$this->_vars[$strVarName]['sql_value']], null, null);
					#if ($row[$this->_vars[$strVarName]['sql_dt_ref']] === null) {
					#    $result = $this->_createLiteral(
					#        $row[$this->_vars[$strVarName]['sql_value']],
					#        $row[$this->_vars[$strVarName]['sql_lang']],
					#        $row[$this->_vars[$strVarName]['sql_type']]
					#    );
					#} else {
					#    $result = $this->_createLiteral(
					#        $row[$this->_vars[$strVarName]['sql_value']],
					#        $row[$this->_vars[$strVarName]['sql_lang']],
					#        $this->iriValues[$row[$this->_vars[$strVarName]['sql_dt_ref']]]
					#    );
					#}
				} else {
					$result = $this->_createLiteral(
						$this->literalValues[$row[$this->_vars[$strVarName]['sql_ref']]], null, null);
					#if ($row[$this->_vars[$strVarName]['sql_dt_ref']] === null) {
					#    $result = $this->_createLiteral(
					#        $this->literalValues[$row[$this->_vars[$strVarName]['sql_ref']]],
					#        $row[$this->_vars[$strVarName]['sql_lang']],
					#        $row[$this->_vars[$strVarName]['sql_type']]
					#    );
					#} else {
					#    $result = $this->_createLiteral(
					#        $this->literalValues[$row[$this->_vars[$strVarName]['sql_ref']]],
					#        $row[$this->_vars[$strVarName]['sql_lang']],
					#        $this->iriValues[$row[$this->_vars[$strVarName]['sql_dt_ref']]]
					#    );
					#}
				}
		}
		if ($asArray) {
			return $result;
		} else {
			return $result['value'];
		}
	}

	/**
	 * Creates an RDF predicate object contained in the given $dbRecordSet object.
	 *
	 * @see convertFromDbResult() to understand $strVarBase necessity
	 *
	 * @param array $dbRecordSet
	 * @param string $strVarBase Prefix of the columns the recordset fields have.
	 * @return string RDF triple predicate resource object.
	 */
	protected function _createPredicateFromDbRecordSetPart($row, $strVarBase, $strVar, $asArray = false) {
		$strVarName = (string)$strVar;
		if ($row[$this->_vars[$strVarName]['sql_value']] === null) {
			return '';
		}
		$result = null;
		if ($row[$this->_vars[$strVarName]['sql_ref']] === null) {
			$result = $this->_createResource($row[$this->_vars[$strVarName]['sql_value']]);
		} else {
			$result = $this->_createResource($this->iriValues[$row[$this->_vars[$strVarName]['sql_ref']]]);
		}
		if ($asArray) {
			return $result;
		} else {
			return $result['value'];
		}
	}

	protected function _createResource($iri) {
		return array(
			'type' => 'iri',
			'value' => $iri
		);
	}

	/**
	 * Creates an RDF subject object contained in the given $dbRecordSet object.
	 *
	 * @see convertFromDbResult() to understand $strVarBase necessity
	 *
	 * @param array $dbRecordSet
	 * @param string $strVarBase Prefix of the columns the recordset fields have.
	 * @return string RDF triple subject resource object.
	 */
	protected function _createSubjectFromDbRecordSetPart($row, $strVarBase, $strVar, $asArray = false) {
		$strVarName = (string)$strVar;
		if ($row[$this->_vars[$strVarName]['sql_value']] === null) {
			return '';
		}
		$result = null;
		if ($row[$this->_vars[$strVarName]['sql_is']] === 0) {
			if ($row[$this->_vars[$strVarName]['sql_ref']] === null) {
				$result = $this->_createResource($row[$this->_vars[$strVarName]['sql_value']]);
			} else {
				$result = $this->_createResource($this->iriValues[$row[$this->_vars[$strVarName]['sql_ref']]]);
			}
		} else {
			if ($row[$this->_vars[$strVarName]['sql_ref']] === null) {
				$result = $this->_createBlankNode($row[$this->_vars[$strVarName]['sql_value']]);
			} else {
				$result = $this->_createBlankNode(
					$this->iriValues[$row[$this->_vars[$strVarName]['sql_ref']]]);
			}
		}
		if ($asArray) {
			return $result;
		} else {
			return $result['value'];
		}
	}

	/**
	 * Converts a result set array into an array of "rows" that
	 * are subarrays of variable => value pairs.
	 *
	 * @param $dbRecordSet
	 * @param $strResultForm
	 * @return array
	 */
	protected function _getVariableArrayFromRecordSet($dbRecordSet, $strResultForm, $asArray = false) {
		$arResult = array();
		switch ($strResultForm) {
			case 'construct':
				$arResultVars = $this->query->getConstructPatternVariables();
				break;
			default:
				$arResultVars = $this->query->getResultVars();
				break;
		}
		if (in_array('*', $arResultVars)) {
			$arResultVars = array_keys($this->_vars);
		}
		foreach ($dbRecordSet as $row) {
			$arResultRow = array();
			foreach ($arResultVars as $strVar) {
				$strVarName = (string)$strVar;
				$strVarId = ltrim($strVar, '?$');
				if (!isset($this->_vars[$strVarName])) {
					//variable is in select, but not in result (test: q-select-2)
					$arResultRow[$strVarId] = '';
				} else {
					$arVarSettings = $this->_vars[$strVarName];
					// Contains whether variable is s, p or o.
					switch ($arVarSettings[1]) {
						case 's':
							$arResultRow[$strVarId] = $this->_createSubjectFromDbRecordSetPart(
								$row, $arVarSettings[0], $strVar, $asArray);
							break;
						case 'p':
							$arResultRow[$strVarId] = $this->_createPredicateFromDbRecordSetPart(
								$row, $arVarSettings[0], $strVar, $asArray);
							break;
						case 'o':
							$arResultRow[$strVarId] = $this->_createObjectFromDbRecordSetPart(
								$row, $arVarSettings[0], $strVar, $asArray);
							break;
						default:
							throw new \Erfurt\Exception('Variable has to be s, p or o.');
					}
				}
			}
			$arResult[] = $arResultRow;
		}
		return $arResult;
	}

	protected function _getVariableArrayFromRecordSets($arRecordSets, $strResultForm, $asArray = false) {
		// First, we need to check, whether there is a need to dereference some values
		$refVariableNamesIri = array();
		$refVariableNamesLit = array();
		foreach ($this->_vars as $var) {
			if ($var[1] === 'o') {
				if (isset($var['sql_ref'])) {
					$refVariableNamesLit[] = $var['sql_ref'];
					$refVariableNamesIri[] = $var['sql_ref'];
				}
				if (isset($var['sql_dt_ref'])) {
					$refVariableNamesIri[] = $var['sql_dt_ref'];
				}
			} else {
				if (isset($var['sql_ref'])) {
					$refVariableNamesIri[] = $var['sql_ref'];
				}
			}
		}
		;
		$refVariableNamesIri = array_unique($refVariableNamesIri);
		$refVariableNamesLit = array_unique($refVariableNamesLit);
		$refIdsIri = array();
		$refIdsLit = array();
		foreach ($arRecordSets as $dbRecordSet) {
			foreach ($dbRecordSet as $row) {
				foreach ($refVariableNamesIri as $name) {
					if ($row["$name"] !== null) {
						$refIdsIri[] = $row["$name"];
					}
				}
				foreach ($refVariableNamesLit as $name) {
					if ($row["$name"] !== null) {
						$refIdsLit[] = $row["$name"];
					}
				}
			}
		}
		if (count($refIdsIri) > 0) {
			$sql = 'SELECT id, v FROM tx_semantic_iri WHERE id IN (' . implode(',', $refIdsIri) . ')';
			$result = $this->engine->sqlQuery($sql);
			foreach ($result as $row) {
				$this->iriValues[$row['id']] = $row['v'];
			}
		}
		if (count($refIdsLit) > 0) {
			$sql = 'SELECT id, v FROM tx_semantic_literal WHERE id IN (' . implode(',', $refIdsLit) . ')';
			$result = $this->engine->sqlQuery($sql);
			foreach ($result as $row) {
				$this->literalValues[$row['id']] = $row['v'];
			}
		}
		$arResult = array();
		foreach ($arRecordSets as $dbRecordSet) {
			$arResult = array_merge(
				$arResult,
				$this->_getVariableArrayFromRecordSet($dbRecordSet, $strResultForm, $asArray)
			);
		}
		return $arResult;
	}
}

?>