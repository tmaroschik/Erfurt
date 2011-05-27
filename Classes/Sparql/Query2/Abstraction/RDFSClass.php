<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2\Abstraction;

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
 * OntoWiki
 *
 * @package	erfurt
 * @subpackage query2
 * @author	 Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license	http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version	$Id: RDFSClass.php 4131 2009-09-06 10:08:24Z jonas.brekle@gmail.com $
 */
use \Erfurt\Sparql\Query2;
class RDFSClass {

	public $iri;
	protected $subclasses = array();
	protected $labels;

	public function __construct(Query2\IriRef $iri, $withChilds = false) {
		$this->iri = $iri;
		if ($withChilds) {
			// TODO elaborate relevance
			$owApp = OntoWiki_Application::getInstance();
			$store = $owApp->erfurt->getStore();
			$graph = $owApp->selectedModel;
			$types = array_keys($store->getTransitiveClosure($graph->getModelIri(), Erfurt\Vocabulary\Rdfs::SUBCLASSOF, array($iri->getIri()), true));
			foreach ($types as $type) {
				$this->subclasses[] = new Query2\IriRef($type);
			}
		}
	}

	public function getLabel($lang) {
		if (isset($this->labels[$lang])) {
			return $this->labels[$lang];
		} else {
			return $this->iri->getIri();
		}
	}

	public function getIri() {
		return $this->iri;
	}

	public function getSubclasses() {
		return $this->subclasses;
	}

}

?>