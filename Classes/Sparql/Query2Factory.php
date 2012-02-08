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
 * A Query2 Factory
 *
 * @package Semantic
 * @scope prototype
 */
class Query2Factory implements \Erfurt\Singleton {

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injector method for a \Erfurt\Object|ObjectManager
	 *
	 * @var \Erfurt\Object|ObjectManager
	 */
	public function injectObjectManager(\Erfurt\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	public function create($queryString, $parsePartial = null) {
		return $this->createFromQueryString($queryString, $parsePartial);
	}

	public function createFromQueryString($queryString, $parsePartial = null) {
		// $parser = new Erfurt_Sparql_Parser_Sparql10();
		// $fromParser = $parser->initFromString($queryString, array());
		// if($fromParser['retval'] instanceof Erfurt_Sparql_Query2){
		//     return $fromParser['retval'];
		// } else {
		//     throw new Exception("Error in parser: ". print_r($fromParser['errors'], true));
		//     return null;
		// }
		$parser = $this->objectManager->create('Erfurt\Sparql\Parser\Sparql10Query');
		try {
			$q = $parser->initFromString($queryString, $parsePartial);
			if ($q['errors']) {
				$e = new Exception('Parse Error: ' . implode(',', $q['errors']));
				throw $e;
			}
			// var_dump($q);
			return $q['retval'];
		}
		catch (\Exception $e) {
			// if ($querySpec['type'] === 'positive') {
			//     $this->fail($this->_createErrorMsg($querySpec, $e));
			// }
			return $e;
		}
	}

}

?>