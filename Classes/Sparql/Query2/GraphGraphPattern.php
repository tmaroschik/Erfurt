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
 * Erfurt_Sparql Query2 - GraphGraphPattern.
 *
 * representation of named graphs
 *
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */

class GraphGraphPattern extends GroupGraphPattern {

	protected $varOrIri;

	/**
	 * @param Interfaces\VarOrIriRef $nvarOrIri
	 */
	public function __construct(Interfaces\VarOrIriRef $nvarOrIri) {
		$this->varOrIri = $nvarOrIri;
		parent::__construct();
	}

	/**
	 * setVarOrIri
	 * @param Interfaces\VarOrIriRef $nvarOrIri
	 * @return GraphGraphPattern $this
	 */
	public function setVarOrIri(Interfaces\VarOrIriRef $nvarOrIri) {
		$this->varOrIri = $nvarOrIri;
		return $this; //for chaining
	}

	/**
	 * getVarOrIri
	 * @return VarOrIriRef the name of this graph
	 */
	public function getVarOrIri() {
		return $this->varOrIri;
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "GRAPH <http://example.com> {[Triple...]}" or "GRAPH ?graphName {[Triple...]}"
	 * @return string
	 */
	public function getSparql() {
		return 'GRAPH ' . $this->varOrIri->getSparql() . ' ' . substr(parent::getSparql(), 0, -1); //subtr is cosmetic for stripping off the last linebreak
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * getVars
	 * get all vars used in this pattern (recursive)
	 * @return array array of Var
	 */
	public function getVars() {
		$vars = parent::getVars();
		if ($this->varOrIri instanceof Variable) {
			$vars[] = $this->varOrIri;
		}
		return $vars;
	}

}

?>
