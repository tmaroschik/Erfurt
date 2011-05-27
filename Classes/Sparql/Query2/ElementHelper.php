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
 * OntoWiki Sparql Query ElementHelper
 *
 * a abstract helper class for objects that are elements of groups. i.e.: Triples but also GroupGraphPatterns
 *
 * @package Semantic
 * @scope prototype
 */
abstract class ElementHelper {

	protected $id;
	protected $parents = array();

	public function __construct() {
		$this->id = \Erfurt\Sparql\Query2::getNextID();
	}

	//abstract public function getSparql();

	/**
	 * addParent
	 * when a ElementHelper-object is added to a ContainerHelper-object this method is called. lets the child know of the new parent
	 * @param ContainerHelper $parent
	 * @return ElementHelper $this
	 */
	public function addParent(ContainerHelper $parent) {
		//        if (!in_array($parent, $this->parents))
		//                $this->parents[] = $parent;
		return $this;
	}

	/**
	 * remove
	 * removes this object from a query
	 * @param $query
	 * @return ElementHelper $this
	 */
	public function remove($query) {
		//remove from this query
		foreach ($query->getParentContainer($this) as $parent) {
			$parent->removeElement($this);
		}
		//        foreach ($this->parents as $parent) {
		//                $parent->removeElement($this);
		//        }
		return $this;
	}

	/**
	 * removeParent
	 * removes a parent
	 * @param ContainerHelper $parent
	 * @return ElementHelper $this
	 */
	public function removeParent(ContainerHelper $parent) {
		//        $new = array();
		//        foreach ($this->parents as $compare) {
		//                if ($compare->equals($parent)) {
		//                        $new[] = $compare;
		//                }
		//        }
		//
		//        $this->parents = $new;
		return $this;
	}

	/**
	 * getID
	 * @return int the id of this object
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * getParents
	 * @return array an array of ContainerHelper
	 */
	public function getParents() {
		return $this->parents;
	}

	/**
	 * equals
	 * @param mixed $obj the object to compare with
	 * @return bool true if equal, false otherwise
	 */
	public function equals($obj) {
		//trivial cases
		if ($this === $obj) {
			return true;
		}
		if (!method_exists($obj, 'getID')) {
			return false;
		}
		if ($this->getID() == $obj->getID()) {
			return true;
		}
		if (get_class($this) !== get_class($obj)) {
			return false;
		}
		return $this->getSparql() === $obj->getSparql();
	}

	abstract public function getSparql();

	public function  __toString() {
		return $this->getSparql();
	}

}

?>