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
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @package Semantic
 * @scope prototype
 */
class QueryResultVariable {
	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	protected $_variable = null;
	protected $_datatype = null;
	protected $_language = null;
	protected $_alias = null;
	protected $_func = null;

	// ------------------------------------------------------------------------
	// --- Magic methods ------------------------------------------------------
	// ------------------------------------------------------------------------

	public function __construct($variable) {
		$this->_variable = $variable;
		$this->_language = Query::getLanguageTag($variable);
	}

	public function __toString() {
		return $this->getName();
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	public function getDatatype() {
		return $this->_datatype;
	}

	public function getFunc() {
		return $this->_func;
	}

	public function getId() {
		return $this->_variable;
	}

	public function getLanguage() {
		return $this->_language;
	}

	public function getName() {
		if (null !== $this->_alias) {
			return $this->_alias;
		}
		return $this->_variable;
	}

	public function getVariable() {
		return $this->_variable;
	}

	public function setAlias($alias) {
		$this->_alias = $alias;
	}

	public function setDatatype($datatype) {
		$this->_datatype = $datatype;
	}

	public function setFunc($func) {
		$this->_func = $func;
	}

}

?>