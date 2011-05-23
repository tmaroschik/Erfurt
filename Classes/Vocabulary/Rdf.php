<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Vocabulary;
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