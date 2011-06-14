<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Domain\Model\Rdfs;

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
 * RDFS Model class
 *
 * @scope prototype
 */
class Graph extends \Erfurt\Domain\Model\Rdf\Graph {

	/**
	 * Resource factory method
	 *
	 * @return Erfurt\Domain\Model\Rdfs\Resource
	 */
	public function getResource($resourceIri) {
		return $this->objectManager->create('Erfurt\Domain\Model\Rdfs\Resource', $resourceIri, $this);
	}

}

?>