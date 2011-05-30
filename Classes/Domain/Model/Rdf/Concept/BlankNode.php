<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Domain\Model\Rdf\Concept;

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
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class BlankNode extends RdfNode {

	/**
	 * @var string
	 */
	protected $nominalValue;

	/**
	 * @test
	 */
	public function __construct() {
		$this->nominalValue = uniqid('b', TRUE);
	}

	/**
	 * Return the NTriples notation for this Node
	 *
	 * @return string
	 */
	public function toNT() {
		return '_:' . $this->nominalValue;
	}

	/**
	 * Return a string repesentation of this RDF Node.
	 */
	public function __toString() {
		return $this->toNT();
	}

	/**
	 * Comparator.
	 *
	 * @param RdfNode $otherNode the oher node to test Equality with.
	 * @return boolean TRUE if $otherNode equals $this, FALSE otherwise.
	 */
	public function equals(RdfNode $otherNode) {
		if (!$otherNode instanceof BlankNode) {
			return FALSE;
		}
		return $otherNode->valueOf() === $this->nominalValue;
	}

	/**
	 * Return the internal value of this Node.
	 *
	 * @return mixed
	 */
	public function valueOf() {
		return $this->nominalValue;
	}
}
?>