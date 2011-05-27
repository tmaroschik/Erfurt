<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Domain\Model\Owl;

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
 * Owl Graph class
 *
 * @scope prototype
 */
class Graph extends \Erfurt\Domain\Model\Rdfs\Graph {
	/**
	 * Imported graph IRIs
	 * @var array
	 */
	protected $imports = null;

	/**
	 * Constructor
	 *
	 * @param string $graphIri
	 * @param string $baseIri
	 * @param array $imports
	 */
	public function __construct($graphIri, $baseIri = null, array $imports = array()) {
		parent::__construct($graphIri, $baseIri);
		$this->imports = $imports;
	}

	/**
	 * Returns an array of graph IRIs this model owl:imports.
	 *
	 * @return array
	 */
	public function getImports() {
		if (!$this->imports) {
			$store = $this->getStore();
			$this->imports = array_values($store->getImportsClosure($this->getModelUri()));
		}
		return $this->imports;
	}

	/**
	 * Resource factory method
	 *
	 * @return \Erfurt\Domain\Model\Owl\Resource
	 */
	public function getResource($resourceIri) {
		return parent::getResource($resourceIri);
	}

}

?>