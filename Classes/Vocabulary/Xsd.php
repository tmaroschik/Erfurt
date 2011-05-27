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
interface Xsd extends VocabularyInterface {

	const NS		=	'http://www.w3.org/2001/XMLSchema#';
	const STRING	=	'http://www.w3.org/2001/XMLSchema#string';
	const INTEGER	=	'http://www.w3.org/2001/XMLSchema#integer';
	const DECIMAL	=	'http://www.w3.org/2001/XMLSchema#decimal';
	const DOUBLE	=	'http://www.w3.org/2001/XMLSchema#double';
	const BOOLEAN	=	'http://www.w3.org/2001/XMLSchema#boolean';
	const DATETIME	=	'http://www.w3.org/2001/XMLSchema#dateTime';

}

?>