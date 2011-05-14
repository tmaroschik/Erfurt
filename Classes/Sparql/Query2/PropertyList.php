<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This class is a port of the corresponding class of the
 *  {@link http://aksw.org/Projects/Erfurt Erfurt} project.
 *  All credits go to the Erfurt team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class PropertyList {

	protected $properties = array();

	function __construct(array $props = null) {
		if ($props !== null) {
			foreach ($props as $prop) {
				if (!is_array($prop) || !isset($prop['verb']) || !isset($prop['objList']) || !($prop['verb'] instanceof Interfaces\Verb) || !($prop['objList'] instanceof ObjectList)) {
					throw new \RuntimeException('Argument 1 passed to PropertyList::__construct must be an array of arrays containing the fields "verb"(instance of Verb) and "objList"(instance of ObjectList)', E_USER_ERROR);
				}
				$this->addProperty($prop['verb'], $prop['objList']);
			}
		}
	}

	public function addProperty(Interfaces\Verb $verb, ObjectList $objList) {
		$this->properties[] = array('verb' => $verb, 'objList' => $objList);
	}

	public function getSparql() {
		$ret = '';
		$countProperties = count($this->properties);
		for ($i = 0; $i < $countProperties; ++$i) {
			$ret .= "\t" . $this->properties[$i]['verb'] . " " . $this->properties[$i]['objList'];
			if (isset($this->properties[$i + 1])) {
				$ret .= " ; \n";
			}
		}
		return $ret;
	}

	public function isEmpty() {
		return (count($this->properties) == 0);
	}

	public function getVars() {
		$ret = array();
		$countProperties = count($this->properties);
		for ($i = 0; $i < $countProperties; ++$i) {
			if ($this->properties[$i]['verb'] instanceof Variable) {
				$ret[] = $this->properties[$i]['verb'];
			}
			$ret = array_merge($ret, $this->properties[$i]['objList']->getVars());
		}
		return $ret;
	}

	public function __toString() {
		return $this->getSparql();
	}

}

?>