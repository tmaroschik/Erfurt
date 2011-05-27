<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\EngineDb\ResultRenderer;

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
 * Result renderer that creates a PHP array containing the result of a SPARQL query as defined in
 * @link http://www.w3.org/TR/rdf-sparql-json-res/.
 *
 * @subpackage sparql
 * @author Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version	$Id: $
 */
use \Erfurt\Sparql;
use \Erfurt\Sparql\EngineDb;
class Extended implements EngineDb\ResultRenderer {

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	protected $query = null;
	protected $engine = null;
	protected $vars = null;

	protected $iriValues = array();
	protected $literalValues = array();

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
		$this->vars = $vars;
		$strResultForm = $this->query->getResultForm();
		switch ($strResultForm) {
			case 'select':
			case 'select distinct':
				$result = array();
				$result['head'] = $this->_getResultHeader();
				// incorrect format (used by erfurt for a long time so this is legacy stuff)
				$result['bindings'] = $this->_getVariableArrayFromRecordSets($arRecordSets, $strResultForm, true);
				// correct format (see http://www.w3.org/TR/rdf-sparql-json-res/)
				$result['results']['bindings'] = $this->_getVariableArrayFromRecordSets($arRecordSets, $strResultForm, true);
				return $result;
				break;
			case 'ask':
				if (count($arRecordSets) > 1) {
					throw new \Erfurt\Exception('More than one result set for a ' . $strResultForm . ' query!');
				}
				$nCount = 0;
				foreach ($arRecordSets[0] as $row) {
					$nCount += intval($row['count']);
					break;
				}
				$value = ($nCount > 0) ? true : false;
				$result = array();
				$result['head'] = $this->_getResultHeader();
				$result['boolean'] = $value;
				return $result;
				break;
			case 'construct':
			case 'count':
			case 'describe':
			default:
				throw new \Erfurt\Exception('Extended format not supported for:' . $strResultForm);
		}
	}

	// ------------------------------------------------------------------------
	// --- Protected methods --------------------------------------------------
	// ------------------------------------------------------------------------

	protected function _createBlankNode($id) {
		return array(
			'type' => 'bnode',
			'value' => ('_:' . $id)
		);
	}

	protected function _createLiteral($value, $language, $datatype) {
		$retVal = array(
			'type' => 'literal',
			'value' => $value
		);
		if ($language !== '') {
			$retVal['xml:lang'] = $language;
		} else {
			if ($datatype !== '') {
				$retVal['type'] = 'typed-literal';
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
		if ($row[$this->vars[$strVarName]['sql_value']] === null) {
			return null;
		}
		$result = null;
		switch ($row[$this->vars[$strVarName]['sql_is']]) {
			case 0:
				if ($row[$this->vars[$strVarName]['sql_ref']] === null) {
					$result = $this->_createResource($row[$this->vars[$strVarName]['sql_value']]);
				} else {
					$result = $this->_createResource(
						$this->iriValues[$row[$this->vars[$strVarName]['sql_ref']]]);
				}
				break;
			case 1:
				if ($row[$this->vars[$strVarName]['sql_ref']] === null) {
					$result = $this->_createBlankNode($row[$this->vars[$strVarName]['sql_value']]);
				} else {
					$result = $this->_createBlankNode(
						$this->iriValues[$row[$this->vars[$strVarName]['sql_ref']]]);
				}
				break;
			default:
				if ($row[$this->vars[$strVarName]['sql_ref']] === null) {
					#$result = $this->_createLiteral(
					#    $row[$this->_vars[$strVarName]['sql_value']], null, null);
					if ($row[$this->vars[$strVarName]['sql_dt_ref']] === null) {
						$result = $this->_createLiteral(
							$row[$this->vars[$strVarName]['sql_value']],
							$row[$this->vars[$strVarName]['sql_lang']],
							$row[$this->vars[$strVarName]['sql_type']]
						);
					} else {
						$result = $this->_createLiteral(
							$row[$this->vars[$strVarName]['sql_value']],
							$row[$this->vars[$strVarName]['sql_lang']],
							$this->iriValues[$row[$this->vars[$strVarName]['sql_dt_ref']]]
						);
					}
				} else {
					#$result = $this->_createLiteral(
					#$this->literalValues[$row[$this->_vars[$strVarName]['sql_ref']]], null, null);
					if ($row[$this->vars[$strVarName]['sql_dt_ref']] === null) {
						$result = $this->_createLiteral(
							$this->literalValues[$row[$this->vars[$strVarName]['sql_ref']]],
							$row[$this->vars[$strVarName]['sql_lang']],
							$row[$this->vars[$strVarName]['sql_type']]
						);
					} else {
						$result = $this->_createLiteral(
							$this->literalValues[$row[$this->vars[$strVarName]['sql_ref']]],
							$row[$this->vars[$strVarName]['sql_lang']],
							$this->iriValues[$row[$this->vars[$strVarName]['sql_dt_ref']]]
						);
					}
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
		if ($row[$this->vars[$strVarName]['sql_value']] === null) {
			return null;
		}
		$result = null;
		if ($row[$this->vars[$strVarName]['sql_ref']] === null) {
			$result = $this->_createResource($row[$this->vars[$strVarName]['sql_value']]);
		} else {
			$result = $this->_createResource($this->iriValues[$row[$this->vars[$strVarName]['sql_ref']]]);
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
		if ($row[$this->vars[$strVarName]['sql_value']] === null) {
			return null;
		}
		$result = null;
		if ($row[$this->vars[$strVarName]['sql_is']] === 0) {
			if ($row[$this->vars[$strVarName]['sql_ref']] === null) {
				$result = $this->_createResource($row[$this->vars[$strVarName]['sql_value']]);
			} else {
				$result = $this->_createResource($this->iriValues[$row[$this->vars[$strVarName]['sql_ref']]]);
			}
		} else {
			if ($row[$this->vars[$strVarName]['sql_ref']] === null) {
				$result = $this->_createBlankNode($row[$this->vars[$strVarName]['sql_value']]);
			} else {
				$result = $this->_createBlankNode(
					$this->iriValues[$row[$this->vars[$strVarName]['sql_ref']]]);
			}
		}
		if ($asArray) {
			return $result;
		} else {
			return $result['value'];
		}
	}

	protected function _getResultHeader() {
		$head = array();
		$resultForm = strtolower($this->query->getResultForm());
		if ($resultForm === 'ask') {
			return $head;
		} else {
			$head['vars'] = array();
			$arResultVars = $this->query->getResultVars();
			if (in_array('*', $arResultVars)) {
				$arResultVars = array_keys($this->vars);
			}
			foreach ($arResultVars as $var) {
				$var = (string)$var;
				if ($var[0] === '?') {
					$head['vars'][] = substr($var, 1);
				} else {
					$head['vars'][] = $var;
				}
			}
			return $head;
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
		$arResultVars = $this->query->getResultVars();
		if (in_array('*', $arResultVars)) {
			$arResultVars = array_keys($this->vars);
		}
		foreach ($dbRecordSet as $row) {
			$arResultRow = array();
			foreach ($arResultVars as $strVar) {
				$strVarName = (string)$strVar;
				$strVarId = ltrim($strVar, '?$');
				if (!isset($this->vars[$strVarName])) {
					//variable is in select, but not in result (test: q-select-2)
					//$arResultRow[$strVarId] = '';
				} else {
					$arVarSettings = $this->vars[$strVarName];
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
		foreach ($this->vars as $var) {
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