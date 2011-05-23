<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Parser\Util;
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
/**
* Extends the ANTLRStringStream to use case insensitive grammar for tokens.
* in the grammar the strings are compared in lowercase
*/
class CaseInsensitiveStream extends \ANTLRStringStream {

	public function LA($i) {
		if ($i == 0) {
			return 0; // undefined
		}
		if ($i < 0) {
			$i++; // e.g., translate LA(-1) to use offset i=0; then data[p+0-1]
			if (($this->p + $i - 1) < 0) {
				return \CharStreamConst::$EOF; // invalid; no char before first char
			}
		}
		if (($this->p + $i - 1) >= $this->n) {
			//System.out.println("char LA("+i+")=EOF; p="+p);
			return \CharStreamConst::$EOF;
		}
		// echo ord(strtolower(chr($this->data[$this->p+$i-1]))) . "\n";
		//System.out.println("char LA("+i+")="+(char)data[p+i-1]+"; p="+p);
		//System.out.println("LA("+i+"); p="+p+" n="+n+" data.length="+data.length);
		return ord(strtolower(chr($this->data[$this->p + $i - 1])));

	}

}

?>