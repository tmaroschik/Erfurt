<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfParser\Adapter\RdfXml;

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
class PropertyElement {

	protected $iri = null;
	protected $reificationIri = null;
	protected $datatype = null;
	protected $parseAsCollection = false;
	protected $lastListResource = null;

	public function __construct($iri) {
		$this->iri = $iri;
	}

	public function getIri() {
		return $this->iri;
	}

	public function isReified() {
		if (null !== $this->reificationIri) {
			return true;
		} else {
			return false;
		}
	}

	public function setReificationIri($reifIri) {
		$this->reificationIri = $reifIri;
	}

	public function getReificationIri() {
		return $this->reificationIri;
	}

	public function setDatatype($datatype) {
		$this->datatype = $datatype;
	}

	public function getDatatype() {
		return $this->datatype;
	}

	public function parseAsCollection() {
		return $this->parseAsCollection;
	}

	public function setParseAsCollection($parseAsCollection) {
		$this->parseAsCollection = $parseAsCollection;
	}

	public function getLastListResource() {
		return $this->lastListResource;
	}

	public function setLastListResource($lastListResource) {
		$this->lastListResource = $lastListResource;
	}
}

?>