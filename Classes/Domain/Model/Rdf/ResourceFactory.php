<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Domain\Model\Rdf;

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
 * Represents a basic RDF resource.
 *
 * @scope prototype
 */
class ResourceFactory implements \Erfurt\Singleton {

	/**
	 * The injected knowledge base
	 *
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

	/**
	 * @param  $iri
	 * @return Resource
	 */
	public function buildFromIri($iri) {
		/** @var \Erfurt\Domain\Model\Rdf\Resource $resource  */
		$resource = $this->objectManager->create('\Erfurt\Domain\Model\Rdf\Resource', $iri);
		return $resource;
	}

	public function buildFromNamespaceAndLocalName($namespace, $local) {
		/** @var \Erfurt\Domain\Model\Rdf\Resource $resource  */
		$resource = $this->objectManager->create('\Erfurt\Domain\Model\Rdf\Resource', $namespace . $local);
		return $resource;
	}

	/**
	 * @param  $id
	 * @return Resource
	 */
	public function buildBlankNode($id) {
		/** @var \Erfurt\Domain\Model\Rdf\Resource $resource  */
		$resource = $this->objectManager->create('\Erfurt\Domain\Model\Rdf\Resource', $id);
		$resource->setIsBlankNode(true);
		return $resource;
	}

}

?>