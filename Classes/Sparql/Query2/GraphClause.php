<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;

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
 * Erfurt Sparql Query2 - GraphClause
 *
 * represents a FROM
 *
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class GraphClause extends ElementHelper {

	protected $graphIri;
	protected $named = false;

	/**
	 * @param IriRef $iri
	 */
	public function __construct($iri, $named = false) {
		if (is_string($iri)) {
			$iri = new IriRef($iri);
		}
		if (!($iri instanceof IriRef)) {
			throw new \RuntimeException("Argument 1 passed to GraphClause::__construct must be instance of IriRef or string", E_USER_ERROR);
		}
		$this->graphIri = $iri;
		if (is_bool($named)) {
			$this->named = $named;
		}
		parent::__construct();
	}

	/**
	 * isNamed
	 * @return bool true if this FROM is a "FROM NAMED"
	 */
	public function isNamed() {
		return $this->named;
	}

	/**
	 * setNamed
	 * @param bool $bool
	 * @return GraphClause $this
	 */
	public function setNamed($bool = true) {
		if (is_bool($bool)) {
			$this->named = $bool;
		}
		return $this; // for chaining
	}

	/**
	 * getGraphIri
	 * @return IriRef the iri
	 */
	public function getGraphIri() {
		return $this->graphIri;
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "FROM <http://example.com>" or "FROM NAMED <http://example.com>"
	 * @return string
	 */
	public function getSparql() {
		return ($this->named ? 'NAMED ' : '') . $this->graphIri->getSparql();
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>
