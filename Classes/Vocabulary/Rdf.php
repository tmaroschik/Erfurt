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
interface Rdf extends VocabularyInterface {

	const NS 			=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	const TYPE			=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
	const DESCRIPTION	=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#Description';
	const ABOUT			=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#about';
	const NIL			=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil';
	const FIRST			=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#first';
	const REST			=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest';
	const PROPERTY		=	'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property';

}

?>