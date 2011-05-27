<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Vocabulary;

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
interface Rdfs extends VocabularyInterface {

	const NS			= 'http://www.w3.org/2000/01/rdf-schema#';
	const COMMENT		=	'http://www.w3.org/2000/01/rdf-schema#comment';
	const LABEL			=		'http://www.w3.org/2000/01/rdf-schema#label';
	const SUBCLASSOF	=	'http://www.w3.org/2000/01/rdf-schema#subClassOf';
	const SUBPROPERTYOF	=	'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
	const DATATYPE		=	'http://www.w3.org/2000/01/rdf-schema#Datatype';
	const RDFS_CLASS	=	'http://www.w3.org/2000/01/rdf-schema#Class';
	const DOMAIN		=	'http://www.w3.org/2000/01/rdf-schema#domain';
	const RANGE			=	'http://www.w3.org/2000/01/rdf-schema#range';
	const RESOURCE		=	'http://www.w3.org/2000/01/rdf-schema#Resource';

}

?>