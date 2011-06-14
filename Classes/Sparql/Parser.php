<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql;

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
 * Parses a SPARQL Query string and returns a Query Object.
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @package Semantic
 * @scope prototype
 */
class Parser {

	// ------------------------------------------------------------------------
	// --- Protected static properties ----------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Which order operators are to be treated (11.3 Operator Mapping).
	 *
	 * @var array
	 */
	protected static $operatorPrecedence = array(
		'||' => 0,
		'&&' => 1,
		'=' => 2,
		'!=' => 3,
		'<' => 4,
		'>' => 5,
		'<=' => 6,
		'>=' => 7,
		'*' => 0,
		'/' => 0,
		'+' => 0,
		'-' => 0,
	);

	/**
	 * Operators introduced by sparql.
	 *
	 * @var array
	 */
	protected static $sparqlOperators = array('regex', 'bound', 'isuri', 'isblank',
											  'isliteral', 'str', 'lang', 'datatype', 'langmatches'
	);

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * The query object.
	 *
	 * @var Query
	 */
	protected $query;

	/**
	 * Last parsed graphPattern.
	 *
	 * @var int
	 */
	protected $tmp;

	/**
	 * The tokenized query.
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Contains blank node ids as key and an boolean value as value.
	 * This array is used in order to invalidate used blank node ids in some
	 * cases.
	 */
	protected $usedBlankNodes = array();


	/**
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injector method for a \Erfurt\Object\ObjectManager
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	// ------------------------------------------------------------------------
	// --- Public static methods ----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Tokenizes the query string into $tokens.
	 * The query may not contain any comments.
	 *
	 * @param  string $queryString Query to split into tokens
	 * @return array Tokens
	 */
	public static function tokenize($queryString) {
		$inTelUri = false;
		$inUri = false;
		$queryString = trim($queryString);
		$removeableSpecialChars = array(' ', "\t", "\r", "\n");
		$specialChars = array(',', '\\', '(', ')', '{', '}', '"', "'", ';', '[', ']');
		$len = strlen($queryString);
		$tokens = array();
		$n = 0;
		$inLiteral = false;
		$longLiteral = false;
		for ($i = 0; $i < $len; ++$i) {
			if (in_array($queryString[$i], $removeableSpecialChars) && !$inLiteral && !$inTelUri) {
				if (isset($tokens[$n]) && $tokens[$n] !== '') {
					if ((strlen($tokens[$n]) >= 2)) {
						if (($tokens[$n][strlen($tokens[$n]) - 1] === '.') &&
							!is_numeric(substr($tokens[$n], 0, strlen($tokens[$n]) - 1))) {
							$tokens[$n] = substr($tokens[$n], 0, strlen($tokens[$n]) - 1);
							$tokens[++$n] = '.';
						} else {
							if (($tokens[$n][0] === '.')) {
								$dummy = substr($tokens[$n], 1);
								$tokens[$n] = '.';
								$tokens[++$n] = $dummy;
							}
						}
					}
					$n++;
				}
				continue;
			} else {
				if (in_array($queryString[$i], $specialChars) && !$inTelUri && !$inUri) {
					if ($queryString[$i] === '"' || $queryString[$i] === "'") {
						$foundChar = $queryString[$i];
						if (!$inLiteral) {
							// Check for long literal
							if (($queryString[($i + 1)] === $foundChar) && ($queryString[($i + 2)] === $foundChar)) {
								$longLiteral = true;
							}
							$inLiteral = true;
						} else {
							// We are inside a literal... Check whether this is the end of the literal.
							if ($longLiteral) {
								if (($queryString[($i + 1)] === $foundChar) && ($queryString[($i + 2)] === $foundChar)) {
									$inLiteral = false;
									$longLiteral = false;
								}
							} else {
								$inLiteral = false;
							}
						}
					}
					if (isset($tokens[$n]) && ($tokens[$n] !== '')) {
						// Check whether trailing char is a dot.
						if ((strlen($tokens[$n]) >= 2)) {
							if (($tokens[$n][strlen($tokens[$n]) - 1] === '.') &&
								!is_numeric(substr($tokens[$n], 0, strlen($tokens[$n]) - 1))) {
								$tokens[$n] = substr($tokens[$n], 0, strlen($tokens[$n]) - 1);
								$tokens[++$n] = '.';
							} else {
								if (($tokens[$n][0] === '.')) {
									$dummy = substr($tokens[$n], 1);
									$tokens[$n] = '.';
									$tokens[++$n] = $dummy;
								}
							}
						}
						$tokens[++$n] = '';
					}
					// In case we have a \ in the string we add the following char to the current token.
					// In that case it doesn't matter what type of char the following one is!
					if ($queryString[$i] === '\\') {
						// Escaped chars do not need a new token.
						$n--;
						$tokens[$n] .= $queryString[$i] . $queryString[++$i];
						// In case we have added \u we will also add the next 4 digits.
						if ($queryString[$i] === 'u') {
							$tokens[$n] .= $queryString[++$i] . $queryString[++$i] . $queryString[++$i] . $queryString[++$i];
						}
						$n++;
					}
						// Sparql supports literals that are written as """...""" in order to support quotation inside
						// the literal.
					else {
						if (($queryString[$i] === '"') && ($i < ($len - 2)) && ($queryString[($i + 1)] === '"') &&
							($queryString[($i + 2)] === '"')) {
							$tokens[$n++] = $queryString[$i] . $queryString[++$i] . $queryString[++$i];
						}
							// Sparql supports literals that are written as '''...''' in order to support quotation inside
							// the literal.
						else {
							if (($queryString[$i] === "'") && ($i < ($len - 2)) && ($queryString[($i + 1)] === "'") &&
								($queryString[($i + 2)] === "'")) {
								$tokens[$n++] = $queryString[$i] . $queryString[++$i] . $queryString[++$i];
							} else {
								$tokens[$n++] = $queryString[$i];
							}
						}
					}
				} else {
					// Special care for tel URIs
					if (substr($queryString, $i, 5) === '<tel:') {
						$inTelUri = true;
					}
					if ($inTelUri && $queryString[$i] === '>') {
						$inTelUri = false;
					}
					if (!isset($tokens[$n])) {
						$tokens[$n] = '';
					}
					// Iris written as <><><> can be written without whitespace, so we need to test for this.
					// If yes, we need to start a new token.
					if ((substr($tokens[$n], 0, 1) === '<') && ($queryString[$i] === '>')) {
						$tokens[$n++] .= $queryString[$i];
						$inUri = false;
						continue;
					} else {
						if ($queryString[$i] === '<') {
							$inUri = true;
							if ($tokens[$n] === '') {
								$tokens[$n] = '<';
								continue;
							} else {
								$tokens[++$n] = '<';
								continue;
							}
						}
					}
					$tokens[$n] .= $queryString{$i};
				}
			}
		}
		return $tokens;
	}

	/**
	 * Removes comments in the query string. Comments are
	 * indicated by '#'.
	 *
	 * @param  string $queryString
	 * @return string The uncommented query string
	 */
	public static function uncomment($queryString) {
		$regex = "/((\"[^\"]*\")|(\'[^\']*\')|(\<[^\>]*\>))|(#.*)/";
		return preg_replace($regex, '\1', $queryString);
	}

	// ------------------------------------------------------------------------
	// --- Protected static methods -------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 *   "Balances" the filter tree in the way that operators on the same
	 *   level are nested according to their precedence defined in
	 *   $operatorPrecedence array.
	 *
	 * @param array $tree  Tree to be modified
	 */
	public static function balanceTree(&$tree) {
		if (isset($tree['type']) && $tree['type'] === 'equation' && isset($tree['operand1']['type']) &&
			$tree['operand1']['type'] === 'equation' && $tree['level'] === $tree['operand1']['level'] &&
			self::$operatorPrecedence[$tree['operator']] > self::$operatorPrecedence[$tree['operand1']['operator']]) {
			$op2 = array(
				'type' => 'equation',
				'level' => $tree['level'],
				'operator' => $tree['operator'],
				'operand1' => $tree['operand1']['operand2'],
				'operand2' => $tree['operand2']
			);
			$tree['operator'] = $tree['operand1']['operator'];
			$tree['operand1'] = $tree['operand1']['operand1'];
			$tree['operand2'] = $op2;
		}
	}

	public static function fixNegationInFuncName(&$tree) {
		if ($tree['type'] === 'function' && $tree['name'][0] === '!') {
			$tree['name'] = substr($tree['name'], 1);
			if (!isset($tree['negated'])) {
				$tree['negated'] = true;
			} else {
				unset($tree['negated']);
			}
			//perhaps more !!
			self::fixNegationInFuncName($tree);
		}
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Main function of Erfurt_Sparql_Parser.
	 * Parses a query string.
	 *
	 * @param string $queryString The SPARQL query
	 * @return Query The query object
	 * @throws ParserException
	 */
	public function parse($queryString = false) {
		if ($queryString === false) {
			throw new ParserException('Querystring is empty.');
		}
		//echo "Parser is called on:\n".$queryString."\n\n";
		$this->_prepare();
		$this->query->setQueryString($queryString);
		$uncommented = self::uncomment($queryString);
		$this->tokens = self::tokenize($uncommented);
		$this->_parseQuery();
		if (!$this->query->isComplete()) {
			throw new ParserException('Query is incomplete.');
		}
		return $this->query;
	}

	// ------------------------------------------------------------------------
	// --- Protected methods --------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Checks if $token is a Blanknode.
	 *
	 * @param  string  $token The token
	 * @return boolean true if the token is BNode false if not
	 */
	protected function isBlankNode($token) {
		if (substr($token, 0, 2) === '_:') {
			return true;
		}
		return false;
	}

	/**
	 * Checks if there is a datatype given and appends it to the node.
	 *
	 * @param string $node Node to check
	 */
	protected function _checkDtypeLang(&$node, $nSubstrLength = 1) {
		$this->_fastForward();
		switch (substr(current($this->tokens), 0, 1)) {
			case '^':
				if (substr(current($this->tokens), 0, 2) === '^^') {
					if (strlen(current($this->tokens)) === 2) {
						next($this->tokens);
					}
					$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\LiteralFactory');
					$node = $literalFactory->buildFromLabel(substr($node, 1, -1));
					$node->setDatatype(
						$this->query->getFullUri(
							substr(current($this->tokens), 2)
						)
					);
				}
				break;
			case '@':
				$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\Literal');
				$node = $literalFactory->buildFromLabelAndLanguage(
					substr($node, $nSubstrLength, -$nSubstrLength),
					substr(current($this->tokens), $nSubstrLength)
				);
				break;
			default:
				prev($this->tokens);
				$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\LiteralFactory');
				$node = $literalFactory->buildFromLabel(substr($node, $nSubstrLength, -$nSubstrLength));
				break;
		}
	}

	protected function _dissallowBlankNodes() {
		foreach ($this->usedBlankNodes as $key => &$value) {
			$value = false;
		}
	}

	/**
	 * Checks if the Node is a typed Literal.
	 *
	 * @param String $node
	 * @return boolean true if typed, false if not.
	 */
	protected function _dtypeCheck(&$node) {
		$patternInt = "/^-?[0-9]+$/";
		$match = preg_match($patternInt, $node, $hits);
		if ($match > 0) {
			$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\LiteralFactory');
			$node = $literalFactory->buildFromLabel($hits[0]);
			$node->setDatatype(\Erfurt\Vocabulary\Xsd::NS . 'integer');
			return true;
		}
		$patternBool = "/^(true|false)$/";
		$match = preg_match($patternBool, $node, $hits);
		if ($match > 0) {
			$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\LiteralFactory');
			$node = $literalFactory->buildFromLabel($hits[0]);
			$node->setDatatype(\Erfurt\Vocabulary\Xsd::NS . 'boolean');
			return true;
		}
		$patternType = "/^a$/";
		$match = preg_match($patternType, $node, $hits);
		if ($match > 0) {
			$ressourceFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\ResourceFactory');
			$node = $ressourceFactory->buildFromNamespaceAndLocalName(\Erfurt\Vocabulary\Rdf::NS, 'type');
			return true;
		}
		$patternDouble = "/^-?[0-9]+.[0-9]+[e|E]?-?[0-9]*/";
		$match = preg_match($patternDouble, $node, $hits);
		if ($match > 0) {
			$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\LiteralFactory');
			$node = $literalFactory->buildFromLabel($hits[0]);
			$node->setDatatype(\Erfurt\Vocabulary\Xsd::NS . 'double');
			return true;
		}
		return false;
	}

	/** FastForward until next token which is not blank. */
	protected function _fastForward() {
		#next($this->_tokens);
		#return;
		$tok = next($this->tokens);
		while ($tok === ' ') {
			$tok = next($this->tokens);
		}
	}

	/**
	 * Checks if $token is an IRI.
	 *
	 * @param  string  $token The token
	 * @return boolean true if the token is an IRI false if not
	 */
	protected function _iriCheck($token) {
		$pattern = "/^<[^>]*>\.?$/";
		if (preg_match($pattern, $token) > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if $token is a Literal.
	 *
	 * @param string $token The token
	 * @return boolean true if the token is a Literal false if not
	 */
	protected function _literalCheck($token) {
		$pattern = "/^[\"\'].*$/";
		if (preg_match($pattern, $token) > 0) {
			return true;
		} else {
			if (is_numeric($token)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sets result form to 'ASK' and 'COUNT'.
	 *
	 * @param string $form  if it's an ASK or COUNT query
	 */
	protected function _parseAsk($form) {
		$this->query->setResultForm($form);
		$this->_fastForward();
		if (current($this->tokens) === '{' || strtolower(current($this->tokens)) === 'from') {
			prev($this->tokens);
		}
	}

	/**
	 * Parses the BASE part of the query.
	 *
	 * @throws ParserException
	 */
	protected function _parseBase() {
		$this->_fastForward();
		if ($this->_iriCheck(current($this->tokens))) {
			$this->query->setBase(current($this->tokens));
		} else {
			throw new ParserException('IRI expected', -1, key($this->tokens));
		}
	}

	/**
	 * Parses an RDF collection.
	 *
	 * @param  Erfurt_Sparql_TriplePattern $trp
	 * @return Erfurt_Rdf_Node The first parsed label.
	 */
	protected function _parseCollection(&$trp) {
		if (prev($this->tokens) === '{') {
			$prevStart = true;
		} else {
			$prevStart = false;
		}
		next($this->tokens);
		$tmpLabel = $this->query->getBlanknodeLabel();
		$firstLabel = $this->_parseNode($tmpLabel);
		$this->_fastForward();
		$i = 0;
		$emptyList = true;
		$rdfRest = \Erfurt\Domain\Resource::initWithNamespaceAndLocalName(\Erfurt\Vocabulary\Rdf::NS, 'rest');
		$rdfFirst = \Erfurt\Domain\Resource::initWithNamespaceAndLocalName(\Erfurt\Vocabulary\Rdf::NS, 'first');
		$rdfNil = \Erfurt\Domain\Resource::initWithNamespaceAndLocalName(\Erfurt\Vocabulary\Rdf::NS, 'nil');
		while (current($this->tokens) !== ')') {
			if ($i > 0) {
				$trp[] = new QueryTriple($this->_parseNode($tmpLabel), $rdfRest,
					$this->_parseNode($tmpLabel = $this->query->getBlanknodeLabel()));
			}
			if (current($this->tokens) === '(') {
				$listNode = $this->_parseCollection($trp);
				$trp[] = new QueryTriple($this->_parseNode($tmpLabel), $rdfFirst, $listNode);
			} else {
				if (current($this->tokens) === '[') {
					$this->_fastForward();
					if (current($this->tokens) === ']') {
						$this->_rewind();
						$trp[] = new QueryTriple($this->_parseNode($tmpLabel), $rdfFirst, $this->_parseNode());
					} else {
						$this->_rewind();
						$sNode = $this->_parseNode();
						$trp[] = new QueryTriple($this->_parseNode($tmpLabel), $rdfFirst, $sNode);
						$this->_fastForward();
						$p = $this->_parseNode();
						$this->_fastForward();
						$o = $this->_parseNode();
						$trp[] = new QueryTriple($sNode, $p, $o);
						$this->_fastForward();
					}
				} else {
					$trp[] = new QueryTriple($this->_parseNode($tmpLabel), $rdfFirst, $this->_parseNode());
				}
			}
			$this->_fastForward();
			$emptyList = false;
			$i++;
		}
		if ($prevStart && $emptyList) {
			if (next($this->tokens) === '}') {
				// list may not occure standalone in a pattern.
				throw new ParserException(
					'A list may not occur standalone in a pattern.', -1, key($this->tokens));
			}
			prev($this->tokens);
		}
		$trp[] = new QueryTriple($this->_parseNode($tmpLabel), $rdfRest, $rdfNil);
		return $firstLabel;
	}

	/**
	 * Parses a value constraint.
	 *
	 * @param GraphPattern $pattern
	 * @param boolean $outer If the constraint is an outer one.
	 */
	protected function _parseConstraint(&$pattern, $outer) {
		$constraint = new Constraint();
		$constraint->setOuterFilter($outer);
		$constraint->setTree($this->_parseConstraintTree());
		if (current($this->tokens) === '}') {
			prev($this->tokens);
		}
		$pattern->addConstraint($constraint);
	}

	/**
	 *   Parses a constraint string recursively.
	 *
	 *   The result array is one "element" which may contain subelements.
	 *   All elements have one key "type" that determines which other
	 *   array keys the element array has. Valid types are:
	 *   - "value":
	 *	   Just a plain value with a value key, nothing else
	 *   - "function"
	 *	   A function has a name and an array of parameter(s). Each parameter
	 *	   is an element.
	 *   - "equation"
	 *	   An equation has an operator, and operand1 and operand2 which
	 *	   are elements themselves
	 *   Any element may have the "negated" value set to true, which means
	 *   that is is - negated (!).
	 *
	 * @internal The functionality of this method is being unit-tested
	 *   in testSparqlParserTests::testParseFilter()
	 *   "equation'-elements have another key "level" which is to be used
	 *   internally only.
	 *
	 * @return array Nested tree array representing the filter
	 */
	protected function _parseConstraintTree($nLevel = 0, $bParameter = false) {
		$tree = array();
		$part = array();
		$chQuotes = null;
		$litQuotes = null;
		$strQuoted = '';
		$parens = false;
		while ($tok = next($this->tokens)) {
			if ($chQuotes !== null && $tok != $chQuotes) {
				$strQuoted .= $tok;
				continue;
			} else {
				if ($litQuotes !== null) {
					$strQuoted .= $tok;
					if ($tok[strlen($tok) - 1] === '>') {
						$tok = '>';
					} else {
						continue;
					}
				} else {
					if ($tok === ')' || $tok === '}' || $tok === '.') {
						break;
					} else {
						if (strtolower($tok) === 'filter' || strtolower($tok) === 'optional') {
							break;
						}
					}
				}
			}
			switch ($tok) {
				case '"':
				case '\'':
					if ($chQuotes === null) {
						$chQuotes = $tok;
						$strQuoted = '';
					} else {
						$chQuotes = null;
						$part[] = array(
							'type' => 'value',
							'value' => $strQuoted,
							'quoted' => true
						);
					}
					continue 2;
					break;
				#                case '>':
				#                   $litQuotes = null;
				#                    $part[] = array(
				#                        'type'  => 'value',
				#                        'value' => $strQuoted,
				#                        'quoted'=> false
				#                    );
				#                    continue 2;
				#                    break;
				case '(':
					$parens = true;
					$bFunc1 = isset($part[0]['type']) && $part[0]['type'] === 'value';
					$bFunc2 = isset($tree['type']) && $tree['type'] === 'equation'
							  && isset($tree['operand2']) && isset($tree['operand2']['value']);
					$part[] = $this->_parseConstraintTree(
						$nLevel + 1,
						$bFunc1 || $bFunc2
					);
					if ($bFunc1) {
						$tree['type'] = 'function';
						$tree['name'] = $part[0]['value'];
						self::fixNegationInFuncName($tree);
						if (isset($part[1]['type'])) {
							$part[1] = array($part[1]);
						}
						$tree['parameter'] = $part[1];
						$part = array();
					} else {
						if ($bFunc2) {
							$tree['operand2']['type'] = 'function';
							$tree['operand2']['name'] = $tree['operand2']['value'];
							self::fixNegationInFuncName($tree['operand2']);
							$tree['operand2']['parameter'] = $part[0];
							unset($tree['operand2']['value']);
							unset($tree['operand2']['quoted']);
							$part = array();
						}
					}
					if (current($this->tokens) === ')') {
						if (substr(next($this->tokens), 0, 2) === '_:') {
							// filter ends here
							prev($this->tokens);
							break 2;
						} else {
							prev($this->tokens);
						}
					}
					continue 2;
					break;
				case ' ':
				case "\t":
					continue 2;
				case '=':
				case '>':
				case '<':
				case '<=':
				case '>=':
				case '-' : //TODO: check correctness
				case '+' : //TODO: check correctness
				case '!=':
				case '&&':
				case '||':
					if (isset($tree['type']) && $tree['type'] === 'equation' && isset($tree['operand2'])) {
						//previous equation open
						$part = array($tree);
					} else {
						if (isset($tree['type']) && $tree['type'] !== 'equation') {
							$part = array($tree);
							$tree = array();
						}
					}
					$tree['type'] = 'equation';
					$tree['level'] = $nLevel;
					$tree['operator'] = $tok;
					//TODO: remove the if when parse contraint is fixed (issue 601)
					if (isset($part[0])) {
						$tree['operand1'] = $part[0];
					} else {
						$tree['operand1'] = null;
					}
					unset($tree['operand2']);
					$part = array();
					continue 2;
					break;
				case '!':
					if ($tree != array()) {
						throw new ParserException(
							'Unexpected "!" negation in constraint.', -1, current($this->tokens));
					}
					$tree['negated'] = true;
					continue 2;
				case ',':
					//parameter separator
					if (count($part) == 0 && !isset($tree['type'])) {
						throw new ParserException(
							'Unexpected comma'
						);
					}
					$bParameter = true;
					if (count($part) === 0) {
						$part[] = $tree;
						$tree = array();
					}
					continue 2;
				default:
					break;
			}
			if ($this->_varCheck($tok)) {
				if (!$parens && $nLevel === 0) {
					// Variables need parenthesizes first
					throw new ParserException('FILTER expressions that start with a variable need parenthesizes.', -1, current($this->tokens));
				}
				$part[] = array(
					'type' => 'value',
					'value' => $tok,
					'quoted' => false
				);
			} else {
				if (substr($tok, 0, 2) === '_:') {
					// syntactic blank nodes not allowed in filter
					throw new ParserException('Syntactic Blanknodes not allowed in FILTER.', -1,
						current($this->tokens));
				} else {
					if (substr($tok, 0, 2) === '^^') {
						$part[count($part) - 1]['datatype'] = $this->query->getFullUri(substr($tok, 2));
					} else {
						if ($tok[0] === '@') {
							$part[count($part) - 1]['language'] = substr($tok, 1);
						} else {
							if ($tok[0] === '<') {
								if ($tok[strlen($tok) - 1] === '>') {
									//single-tokenized <> uris
									$part[] = array(
										'type' => 'value',
										'value' => $tok,
										'quoted' => false
									);
								} else {
									//iris split over several tokens
									$strQuoted = $tok;
									$litQuotes = true;
								}
							} else {
								if ($tok === 'true' || $tok === 'false') {
									$part[] = array(
										'type' => 'value',
										'value' => $tok,
										'quoted' => false,
										'datatype' => 'http://www.w3.org/2001/XMLSchema#boolean'
									);
								} else {
									$part[] = array(
										'type' => 'value',
										'value' => $tok,
										'quoted' => false
									);
								}
							}
						}
					}
				}
			}
			if (isset($tree['type']) && $tree['type'] === 'equation' && isset($part[0])) {
				$tree['operand2'] = $part[0];
				self::balanceTree($tree);
				$part = array();
			}
		}
		if (!isset($tree['type']) && $bParameter) {
			return $part;
		} else {
			if (isset($tree['type']) && $tree['type'] === 'equation'
				&& isset($tree['operand1']) && !isset($tree['operand2'])
				&& isset($part[0])) {
				$tree['operand2'] = $part[0];
				self::balanceTree($tree);
			}
		}
		if ((count($tree) === 0) && (count($part) > 1)) {
			//TODO: uncomment when issue 601 is fixed
			//throw new ParserException('Failed to parse constraint.', -1, current($this->_tokens));
		}
		if (!isset($tree['type']) && isset($part[0])) {
			if (isset($tree['negated'])) {
				$part[0]['negated'] = true;
			}
			return $part[0];
		}
		return $tree;
	}

	/**
	 * Parses the CONSTRUCT clause.
	 *
	 * @throws ParserException
	 */
	protected function _parseConstruct() {
		$this->_fastForward();
		$this->query->setResultForm('construct');
		if (current($this->tokens) === '{') {
			$this->_parseGraphPattern(false, false, false, true);
		} else {
			throw new ParserException('Unable to parse CONSTRUCT part. "{" expected.', -1,
				key($this->tokens));
		}
		while (true) {
			if (strtolower(current($this->tokens)) === 'from') {
				$this->_parseFrom();
			} else {
				break;
			}
		}
		$this->_parseWhere();
		$this->_parseModifier();
	}

	/** Adds a new variable to the query and sets result form to 'DESCRIBE'. */
	protected function _parseDescribe() {
		while (strtolower(current($this->tokens)) != 'from' && strtolower(current($this->tokens)) != 'where') {
			$this->_fastForward();
			if ($this->_varCheck(current($this->tokens)) || $this->_iriCheck(current($this->tokens))) {
				$var = new QueryResultVariable(current($this->tokens));
				$this->query->addResultVar($var);
				if (!$this->query->getResultForm()) {
					$this->query->setResultForm('describe');
				}
			}
			if (!current($this->tokens)) {
				break;
			}
		}
		prev($this->tokens);
	}

	/**
	 * Parses the FROM clause.
	 *
	 * @throws ParserException
	 */
	protected function _parseFrom() {
		$this->_fastForward();
		if (strtolower(current($this->tokens)) !== 'named') {
			if ($this->_iriCheck(current($this->tokens)) || $this->_qnameCheck(current($this->tokens))) {
				$this->query->addFrom(substr(current($this->tokens), 1, -1));
			} else {
				if ($this->_varCheck(current($this->tokens))) {
					$this->query->addFrom(current($this->tokens));
				} else {
					throw new ParserException('Variable, iri or qname expected in FROM', -1,
						key($this->tokens));
				}
			}
		} else {
			$this->_fastForward();
			if ($this->_iriCheck(current($this->tokens)) || $this->_qnameCheck(current($this->tokens))) {
				$this->query->addFromNamed(substr(current($this->tokens), 1, -1));
			} else {
				if ($this->_varCheck(current($this->tokens))) {
					$this->query->addFromNamed(current($this->tokens));
				} else {
					throw new ParserException('Variable, Iri or qname expected in FROM NAMED', -1,
						key($this->tokens));
				}
			}
		}
	}

	/**
	 * Parses a GRAPH clause.
	 *
	 * @param  Erfurt_Sparql_GraphPattern $pattern
	 * @throws ParserException
	 */
	protected function _parseGraph() {
		$this->_fastForward();
		$name = current($this->tokens);
		if (!$this->_varCheck($name) && !$this->_iriCheck($name) && !$this->_qnameCheck($name)) {
			$msg = $name;
			$msg = preg_replace('/</', '&lt;', $msg);
			throw new ParserException('IRI or Var expected.', -1, key($this->tokens));
		}
		$this->_fastForward();
		if ($this->_iriCheck($name)) {
			$name = \Erfurt\Domain\Resource::initWithIri(substr($name, 1, -1));
		} else {
			if ($this->_qnameCheck($name)) {
				$name = \Erfurt\Domain\Resource::initWithIri($this->query->getFullUri($name));
			}
		}
		$this->_parseGraphPattern(false, false, $name);
		if (current($this->tokens) === '.') {
			$this->_fastForward();
		}
	}

	/**
	 * Parses a graph pattern.
	 *
	 * @param  int	 $optional Optional graph pattern
	 * @param  int	 $union	Union graph pattern
	 * @param  string  $graph	Graphname
	 * @param  boolean $constr   TRUE if the pattern is a construct pattern
	 * @param  boolean $external If the parsed pattern shall be returned
	 * @param  int	 $subpattern If the new pattern is subpattern of the pattern with the given id
	 */
	protected function _parseGraphPattern($optional = false, $union = false, $graph = false, $constr = false,
										  $external = false, $subpattern = false) {
		$pattern = $this->query->getNewPattern($constr);
		if (current($this->tokens) !== '{') {
			throw new ParserException(
				'A graph pattern needs to start with "{".', -1, key($this->tokens));
		}
		// A new graph pattern invalidates the use of all previous blank nodes.
		$this->_dissallowBlankNodes();
		if (is_int($optional)) {
			$pattern->setOptional($optional);
		} else {
			$this->tmp = $pattern->getId();
		}
		if (is_int($union)) {
			$pattern->setUnion($union);
		}
		if (is_int($subpattern)) {
			$pattern->setSubpatternOf($subpattern);
		}
		if ($graph != false) {
			$pattern->setGraphname($graph);
		}
		$this->_fastForward();
		do {
			switch (strtolower(current($this->tokens))) {
				case 'graph':
					$this->_parseGraph();
					$this->_dissallowBlankNodes();
					break;
				case 'union':
					$this->_fastForward();
					$this->_parseGraphPattern(false, $this->tmp, false, false, false, $subpattern);
					break;
				case 'optional':
					$this->_fastForward();
					$this->_parseGraphPattern($pattern->patternId, false, false, false, false, null);
					break;
				case 'filter':
					$this->_parseConstraint($pattern, true);
					if (current($this->tokens) === ')') {
						$this->_fastForward();
					}
					$needsDot = false;
					break;
				case '.':
				case ';':
					// Check whether the previous token is {, for this is not allowed.
					$this->_rewind();
					if (current($this->tokens) === '{') {
						throw new ParserException('A dot/semicolon must not follow a "{" directly.', -1,
							key($this->tokens));
					}
					$this->_fastForward();
					$this->_fastForward();
					break;
				case '{':
					$subpattern = $pattern->getId();
					$this->_parseGraphPattern(false, false, false, false, false, $subpattern);
					break;
				case '}':
					$pattern->open = false;
					break;
				default:
					$this->_parseTriplePattern($pattern);
					break;
			}
		} while ($pattern->open);
		if ($external) {
			return $pattern;
		}
		$this->_fastForward();
	}

	/**
	 * Parses a literal.
	 *
	 * @param string $node
	 * @param string $sep used separator " or '
	 */
	protected function _parseLiteral(&$node, $sep) {
		if ($sep !== null) {
			do {
				next($this->tokens);
				$node = $node . current($this->tokens);
			} while ((current($this->tokens) != $sep));
			$this->_checkDtypeLang($node, strlen($sep));
		} else {
			$datatype = '';
			if (is_string($node) && strpos($node, '.') !== false) {
				$datatype = \Erfurt\Vocabulary\Xsd::NS . 'integer';
			} else {
				$datatype = \Erfurt\Vocabulary\Xsd::NS . 'decimal';
			}
			$literalFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\LiteralFactory');
			$node = $literalFactory->buildFromLabel($node);
			$node->setDatatype($datatype);
		}
	}

	/**
	 * Parses the solution modifiers of a query.
	 *
	 * @throws ParserException
	 */
	protected function _parseModifier() {
		do {
			switch (strtolower(current($this->tokens))) {
				case 'order':
					$this->_fastForward();
					if (strtolower(current($this->tokens)) === 'by') {
						$this->_fastForward();
						$this->_parseOrderCondition();
					} else {
						throw new ParserException('"BY" expected.', -1, key($this->tokens));
					}
					break;
				case 'limit':
					$this->_fastForward();
					$val = current($this->tokens);
					$this->query->setSolutionModifier('limit', $val);
					break;
				case 'offset':
					$this->_fastForward();
					$val = current($this->tokens);
					$this->query->setSolutionModifier('offset', $val);
					break;
				default:
					break;
			}
		} while (next($this->tokens));
	}

	/**
	 * Parses a String to an RDF node.
	 *
	 * @param  string $node
	 * @return Erfurt_Rdf_Node The parsed RDF node
	 * @throws ParserException
	 */
	protected function _parseNode($node = false) {
		if ($node) {
			$node = $node;
		} else {
			$node = current($this->tokens);
		}
		if ($node{strlen($node) - 1} === '.') {
			$node = substr($node, 0, -1);
		}
		if ($this->_dtypeCheck($node)) {
			return $node;
		}
		if ($this->isBlankNode($node)) {
			$node = '?' . $node;
			if (isset($this->usedBlankNodes[$node]) && $this->usedBlankNodes[$node] === false) {
				throw new ParserException('Reuse of blank node id not allowed here.' - 1,
					key($this->tokens));
			}
			$this->query->addUsedVar($node);
			$this->usedBlankNodes[$node] = true;
			return $node;
		}
		if ($node === '[') {
			$node = '?' . substr($this->query->getBlanknodeLabel(), 1);
			$this->query->addUsedVar($node);
			$this->_fastForward();
			if (current($this->tokens) !== ']') {
				prev($this->tokens);
			}
			return $node;
		}
		if ($this->_iriCheck($node)) {
			$base = $this->query->getBase();
			if ($base != null) {
				$ressourceFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\ResourceFactory');
				$node = $ressourceFactory->buildFromNamespaceAndLocalName(substr($base, 1, -1), substr($node, 1, -1));
			} else {
				$ressourceFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\ResourceFactory');
				$node = $ressourceFactory->buildFromIri(substr($node, 1, -1));
			}
			return $node;
		} else {
			if ($this->_qnameCheck($node)) {
				$node = $this->query->getFullUri($node);
				$ressourceFactory = $this->objectManager->get('Erfurt\Domain\Model\Rdf\ResourceFactory');
				$node = $ressourceFactory->buildFromIri($node);
				return $node;
			} else {
				if ($this->_literalCheck($node)) {
					if ((substr($node, 0, 1) === '"') || (substr($node, 0, 1) === "'")) {
						$ch = substr($node, 0, 1);
						$chLong = str_repeat($ch, 3);
						if (substr($node, 0, 3) == $chLong) {
							$ch = $chLong;
						}
						$this->_parseLiteral($node, $ch);
					} else {
						$this->_parseLiteral($node, null);
					}
				} else {
					if ($this->_varCheck($node)) {
						$pos = is_string($node) ? strpos($node, '.') : false;
						if ($pos) {
							return substr($node, 0, $pos);
						} else {
							return $node;
						}
					} else {
						if ($node[0] === '<') {
							//partial IRI? loop tokens until we find a closing >
							while (next($this->tokens)) {
								$node .= current($this->tokens);
								if (substr($node, -1) === '>') {
									break;
								}
							}
							if (substr($node, -1) != '>') {
								var_dump($this->tokens);
								exit;
								throw new ParserException('Unclosed IRI: ' . $node, -1, key($this->tokens));
							}
							return $this->_parseNode($node);
						} else {
							throw new ParserException(
								'"' . $node . '" is neither a valid rdf- node nor a variable.',
								-1,
								key($this->tokens)
							);
						}
					}
				}
			}
		}
		return $node;
	}

	/**
	 * Parses order conditions of a query.
	 *
	 * @throws ParserException
	 */
	protected function _parseOrderCondition() {
		$valList = array();
		$val = array();
		while (strtolower(current($this->tokens)) !== 'limit' && strtolower(current($this->tokens)) != false
			   && strtolower(current($this->tokens)) !== 'offset') {
			switch (strtolower(current($this->tokens))) {
				case 'desc':
					$this->_fastForward();
					$this->_fastForward();
					if ($this->_varCheck(current($this->tokens))) {
						$val['val'] = current($this->tokens);
					} else {
						if ($this->_iriCheck(current($this->tokens)) || $this->_qnameCheck(current($this->tokens)) ||
							in_array(current($this->tokens), $this->_sops)) {
							$fName = current($this->tokens);
							do {
								$this->_fastForward();
								$fName .= current($this->tokens);
							} while (current($this->tokens) !== ')');
							$val['val'] = $fName;
						} else {
							throw new ParserException('Variable expected in ORDER BY clause.', -1,
								key($this->tokens));
						}
					}
					$this->_fastForward();
					if (current($this->tokens) != ')') {
						throw new ParserException('missing ")" in ORDER BY clause.', -1,
							key($this->tokens));
					}
					$val['type'] = 'desc';
					$this->_fastForward();
					break;
				case 'asc':
					$this->_fastForward();
					$this->_fastForward();
					if ($this->_varCheck(current($this->tokens))) {
						$val['val'] = current($this->tokens);
					} else {
						if ($this->_iriCheck(current($this->tokens)) || $this->_qnameCheck(current($this->tokens)) ||
							in_array(current($this->tokens), $this->_sops)) {
							$fName = current($this->tokens);
							do {
								$this->_fastForward();
								$fName .= current($this->tokens);
							} while (current($this->tokens) !== ')');
							$val['val'] = $fName;
						} else {
							throw new ParserException('Variable expected in ORDER BY clause. ', -1,
								key($this->tokens));
						}
					}
					$this->_fastForward();
					if (current($this->tokens) !== ')') {
						throw new ParserException('missing ")" in ORDER BY clause.', -1,
							key($this->tokens));
					}
					$val['type'] = 'asc';
					$this->_fastForward();
					break;
				case ')':
					$this->_fastForward();
					break;
				case '(':
					$this->_fastForward();
				default:
					if ($this->_varCheck(current($this->tokens))) {
						$val['val'] = current($this->tokens);
						$val['type'] = 'asc';
					} else {
						if ($this->_iriCheck(current($this->tokens)) || $this->_qnameCheck(current($this->tokens)) ||
							in_array(current($this->tokens), self::$sparqlOperators)) {
							$fName = current($this->tokens);
							do {
								$this->_fastForward();
								$fName .= current($this->tokens);
							} while (current($this->tokens) !== ')');
							$val['val'] = $fName;
						} else {
							//TODO: fix recognition of "ORDER BY ASC(?x)"
							//throw new ParserException('Variable expected in ORDER BY clause.', -1,
							//              key($this->_tokens));
						}
					}
					$this->_fastForward();
					break;
			}
			$valList[] = $val;
		}
		prev($this->tokens);
		$this->query->setSolutionModifier('order by', $valList);
	}

	/**
	 * Adds a new namespace prefix to the query object.
	 *
	 * @throws ParserException
	 */
	protected function _parsePrefix() {
		$this->_fastForward();
		$prefix = substr(current($this->tokens), 0, -1);
		$this->_fastForward();
		if ($this->_iriCheck(current($this->tokens))) {
			$uri = substr(current($this->tokens), 1, -1);
			$this->query->addPrefix($prefix, $uri);
		} else {
			throw new ParserException('IRI expected', -1, key($this->tokens));
		}
	}

	/** Starts parsing the tokenized SPARQL Query. */
	protected function _parseQuery() {
		do {
			switch (strtolower(current($this->tokens))) {
				case 'base':
					$this->_parseBase();
					break;
				case 'prefix':
					$this->_parsePrefix();
					break;
				case 'select':
					$this->_parseSelect();
					break;
				case 'describe':
					$this->_parseDescribe();
					break;
				case 'ask':
					$this->_parseAsk('ask');
					break;
				case 'count':
					$this->_parseAsk('count');
					break;
				case 'count-distinct':
					$this->_parseAsk('count-distinct');
					break;
				case 'from':
					$this->_parseFrom();
					break;
				case 'construct':
					$this->_parseConstruct();
					break;
				case 'where':
					$this->_parseWhere();
					$this->_parseModifier();
					break;
				case '{':
					prev($this->tokens);
					$this->_parseWhere();
					$this->_parseModifier();
					break;
			}
		} while (next($this->tokens));
	}

	/**
	 * Parses the SELECT part of a query.
	 *
	 * @throws ParserException
	 */
	protected function _parseSelect() {
		$this->_fastForward();
		$curLow = strtolower(current($this->tokens));
		prev($this->tokens);
		if ($curLow === 'distinct') {
			$this->query->setResultForm('select distinct');
		} else {
			$this->query->setResultForm('select');
		}
		$currentVar = null;
		$currentFunc = null;
		$bWaitForRenaming = false;
		while ($curLow != 'from' && $curLow != 'where' && $curLow != "{") {
			$this->_fastForward();
			$curTok = current($this->tokens);
			$curLow = strtolower($curTok);
			if ($this->_varCheck($curTok) || $curLow == '*') {
				if ($bWaitForRenaming) {
					$bWaitForRenaming = false;
					$currentVar->setAlias($curTok);
					if ($currentFunc != null) {
						$currentVar->setFunc($currentFunc);
					}
					$this->query->addResultVar($currentVar);
					$currentVar = null;
				} else {
					if ($currentVar != null) {
						$this->query->addResultVar($currentVar);
						$currentVar = null;
					}
					$currentVar = new QueryResultVariable($curTok);
					if ($currentFunc != null) {
						$currentVar->setFunc($currentFunc);
					}
				}
				$currentFunc = null;
			} else {
				if ($curLow == 'as') {
					if ($currentVar === null) {
						throw new ParserException('AS requires a variable left and right', -1,
							key($this->tokens));
					}
					$bWaitForRenaming = true;
				} else {
					if (in_array($curLow, self::$sparqlOperators)) {
						$currentFunc = $curLow;
					}
				}
			}
			if (!current($this->tokens)) {
				throw new ParserException(
					'Unexpected end of query.', -1, key($this->tokens));
			}
		}
		if ($currentVar != null) {
			$this->query->addResultVar($currentVar);
		}
		prev($this->tokens);
		if (count($this->query->getResultVars()) == 0) {
			throw new ParserException('Variable or "*" expected.', -1, key($this->tokens));
		}
	}

	/**
	 * Parses a triple pattern.
	 *
	 * @param  Sparql_GraphPattern $pattern
	 */
	protected function _parseTriplePattern(&$pattern) {
		$trp = array();
		$prev = false;
		$prevPred = false;
		$cont = true;
		$needsDot = false;
		$dotAllowed = true;
		$sub = '';
		$pre = '';
		$tmp = '';
		$tmpPred = '';
		$obj = '';
		do {
			switch (strtolower(current($this->tokens))) {
				case false:
					$cont = false;
					$pattern->open = false;
					break;
				case 'filter':
					$this->_parseConstraint($pattern, false);
					if (strtolower(current($this->tokens)) !== 'filter' &&
						strtolower(current($this->tokens)) !== 'optional') {
						$this->_fastForward();
					}
					$needsDot = false;
					break;
				case 'optional':
					$needsDot = false;
					$this->_fastForward();
					$this->_parseGraphPattern($pattern->getId(), false);
					break;
				case 'union':
					$this->_fastForward();
					$this->_parseGraphPattern(false, $this->tmp, false, false, false, $pattern->getId());
					break;
				case ';':
					// Check whether the previous token is a dot too, for this is not allowed.
					$this->_rewind();
					if (current($this->tokens) === '.') {
						throw new ParserException('A semicolon must not follow a dot directly.', -1,
							key($this->tokens));
					}
					$this->_fastForward();
					$prev = true;
					$needsDot = false;
					$this->_fastForward();
					break;
				case '.':
					if ($dotAllowed === false) {
						throw new ParserException('A dot is not allowed here.', -1, key($this->tokens));
					}
					// Check whether the previous token is a dot too, for this is not allowed.
					$this->_rewind();
					if (current($this->tokens) === '.') {
						throw new ParserException('A dot may not follow a dot directly.', -1,
							key($this->tokens));
					}
					$this->_fastForward();
					$prev = false;
					$needsDot = false;
					$this->_fastForward();
					break;
				case 'graph':
					$this->_parseGraph();
					break;
				case ',':
					throw new ParserException('A comma is not allowed directly after a triple.', -1,
						key($this->tokens));
					$prev = true;
					$prevPred = true;
					$this->_fastForward();
					break;
				case '}':
					$prev = false;
					$pattern->open = false;
					$cont = false;
					$this->_dissallowBlankNodes();
					break;
				case '{':
					//subpatterns opens
					$this->_parseGraphPattern(false, false, false, false, false, $pattern->getId());
					$needsDot = false;
					break;
				case "[":
					$needsDot = false;
					$prev = true;
					$tmp = $this->_parseNode($this->query->getBlanknodeLabel());
					$this->_fastForward();
					break;
				case "]":
					$needsDot = false;
					$dotAllowed = false;
					$prev = true;
					$this->_fastForward();
					break;
				case "(":
					$prev = true;
					$tmp = $this->_parseCollection($trp);
					$this->_fastForward();
					break;
				case false:
					$cont = false;
					$pattern->open = false;
					break;
				default:
					if ($needsDot === true) {
						throw new ParserException('Two triple pattern need to be seperated by a dot. In Query: ' . htmlentities($this->query), -1,
							key($this->tokens));
					}
					$dotAllowed = false;
					if ($prev) {
						$sub = $tmp;
					} else {
						$sub = $this->_parseNode();
						$this->_fastForward();
						$tmp = $sub;
					}
					if ($prevPred) {
						$pre = $tmpPred;
					} else {
						// Predicates may not be blank nodes.
						if ((current($this->tokens) === '[') || (substr(current($this->tokens), 0, 2) === '_:')) {
							throw new ParserException('Predicates may not be blank nodes.', -1,
								key($this->tokens));
						}
						$pre = $this->_parseNode();
						$this->_fastForward();
						$tmpPred = $pre;
					}
					if (current($this->tokens) === '[') {
						$tmp = $this->_parseNode($this->query->getBlanknodeLabel());
						$prev = true;
						$obj = $tmp;
						$trp[] = new QueryTriple($sub, $pre, $obj);
						$dotAllowed = true;
						$this->_fastForward();
						continue;
					} else {
						if (current($this->tokens) === '(') {
							$obj = $this->_parseCollection($trp);
						} else {
							$obj = $this->_parseNode();
						}
					}
					$trp[] = new QueryTriple($sub, $pre, $obj);
					$dotAllowed = true;
					$needsDot = true;
					$this->_fastForward();
					break;
			}
		} while ($cont);
		if (count($trp) > 0) {
			$pattern->addTriplePatterns($trp);
		}
	}

	/**
	 * Parses the WHERE clause.
	 *
	 * @throws ParserException
	 */
	protected function _parseWhere() {
		$this->_fastForward();
		if (current($this->tokens) === '{') {
			$this->_parseGraphPattern();
		} else {
			throw new ParserException('Unable to parse WHERE part. "{" expected in Query. ', -1,
				key($this->tokens));
		}
	}

	/**
	 *   Set all internal variables to a clear state
	 *   before we start parsing.
	 */
	protected function _prepare() {
		$this->query = new Query();
		$this->tokens = array();
		$this->tmp = null;
	}

	/**
	 * Checks if $token is a qname.
	 *
	 * @param  string  $token The token
	 * @return boolean true if the token is a qname false if not
	 * @throws ParserException
	 */
	protected function _qnameCheck($token) {
		$pattern = "/^([^:^\<]*):([^:]*)$/";
		if (preg_match($pattern, $token, $hits) > 0) {
			$prefs = $this->query->getPrefixes();
			if (isset($prefs[$hits{1}])) {
				return true;
			}
			if ($hits{1} === '_') {
				return true;
			}
			throw new ParserException('Unbound Prefix: <i>' . $hits{1} . '</i>', -1, key($this->tokens));
		} else {
			return false;
		}
	}

	/** Rewind until next token which is not blank. */
	protected function _rewind() {
		prev($this->tokens);
	}

	/**
	 * Checks if $token is a variable.
	 *
	 * @param  string  $token The token
	 * @return boolean true if the token is a variable false if not
	 */
	protected function _varCheck($token) {
		if (isset($token[0]) && ($token{0} == '$' || $token{0} == '?')) {
			$this->query->addUsedVar($token);
			return true;
		}
		return false;
	}

}

?>