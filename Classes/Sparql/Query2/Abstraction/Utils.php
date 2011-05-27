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
 * OntoWiki QUery Abstraction Utils
 *
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id: Utils.php 3977 2009-08-09 14:38:37Z jonas.brekle@gmail.com $
 */
class Utils {

	static function getAllProperties(RDFSClass $class) {
		$query = "PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>
			PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>
			SELECT DISTINCT ?property ?label ?order ?range
			WHERE {
				?property a <http://www.w3.org/2002/07/owl#DatatypeProperty> .
				?property rdfs:domain ?type .
				?property rdfs:label ?label .
				?property rdfs:range ?range .
				OPTIONAL {
					?property <http://ns.ontowiki.net/SysOnt/order> ?order
				}
				FILTER(sameTerm(?type, <" . $class->iri . ">))";
		//TODO ...
		;
	}

}

?>