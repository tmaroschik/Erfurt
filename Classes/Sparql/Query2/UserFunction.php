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
 * represents a user-defined function call
 *
 * @package Semantic
 * @scope prototype
 */
class UserFunction extends ElementHelper implements Interfaces\IriRefOrFunction {

	protected $iri;
	protected $args = array();

	/**
	 *
	 * @param IriRef|string $iri the iri of the function
	 * @param array $args array of arguments (Expression) of the function.
	 */
	public function __construct($iri, $args = array()) {
		if (is_string($iri)) {
			$iri = new IriRef($iri);
		}
		if (!($iri instanceof IriRef)) {
			throw new \RuntimeException('Argument 1 passed to Function::__construct must be an instance of IriRef or string (will be converted to IriRef), instance of '.typeHelper($iri).' given');
		}
		$this->iri = $iri;
		if ($args instanceof Interfaces\Expression) {
			//only one given - pack into array
			$args = array($args);
		}
		if (!is_array($args)) {
			throw new \RuntimeException('Argument 2 passed to Function::__construct must be an array of Expression\'s, instance of '.typeHelper($args).' given');
		} else {
			foreach ($args as $arg) {
				if (!($arg instanceof Interfaces\Expression)) {
					throw new \RuntimeException('Argument 2 passed to Function::__construct must be an array of Expression\'s, instance of '.typeHelper($arg).' given');
				} else {
					$this->args[] = $arg;
				}
			}
		}
		parent::__construct();
	}

	/**
	 * get the string representation
	 * @return string
	 */
	public function getSparql() {
		return $this->iri->getSparql().'('.implode(', ', $this->args).')';
	}

}

?>