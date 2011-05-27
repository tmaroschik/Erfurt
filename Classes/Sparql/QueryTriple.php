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
 * Represents a query triple with subject, predicate and object.
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @package Semantic
 * @scope prototype
 */
class QueryTriple {
	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * The QueryTriples Subject. Can be a BlankNode or Resource, string in
	 * case of a variable
	 *
	 * @var Node/string
	 */
	protected $_subject;

	/**
	 * The QueryTriples Predicate. Normally only a Resource, string in case of a variable
	 *
	 * @var Node/string
	 */
	protected $_predicate;

	/**
	 * The QueryTriples Object. Can be BlankNode, Resource or Literal, string in case of a variable.
	 *
	 * @var Node/string
	 */
	protected $_object;

	// ------------------------------------------------------------------------
	// --- Magic methods ------------------------------------------------------
	// ------------------------------------------------------------------------

	public function __clone() {
		if (is_object($this->_subject)) {
			$this->_subject = clone $this->_subject;
		}
		if (is_object($this->_predicate)) {
			$this->_predicate = clone $this->_predicate;
		}
		if (is_object($this->_object)) {
			$this->_object = clone $this->_object;
		}
	}

	/**
	 * Constructor
	 *
	 * @param Erfurt_Rdf_Resource/string $subject Subject
	 * @param Erfurt_Rdf_Resource/string $predicate Predicate
	 * @param Erfurt_Rdf_Node/string $object Object
	 */
	public function __construct($subject, $predicate, $object) {
		$this->_subject = $subject;
		$this->_predicate = $predicate;
		$this->_object = $object;
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Returns the Triples Object.
	 *
	 * @return Erfurt_Rdf_Node/string
	 */
	public function getObject() {
		return $this->_object;
	}

	/**
	 * Returns the Triples Predicate.
	 *
	 * @return Erfurt_Rdf_Resource/string
	 */
	public function getPredicate() {
		return $this->_predicate;
	}

	/**
	 * Returns the Triples Subject.
	 *
	 * @return Erfurt_Rdf_Resource/string
	 */
	public function getSubject() {
		return $this->_subject;
	}

	/**
	 *   Returns an array of all variables in this triple.
	 *
	 * @return array Array of variable names.
	 */
	public function getVariables() {
		$arVars = array();
		if (Variable::isVariable($this->_subject)) {
			$arVars[] = $this->_subject;
		}
		if (Variable::isVariable($this->_predicate)) {
			$arVars[] = $this->_predicate;
		}
		if (Variable::isVariable($this->_object)) {
			$arVars[] = $this->_object;
		}
		return $arVars;
	}
}
