<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql;
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
 * A SPARQL Parser Execption for better errorhandling.
 *
 * This class was originally adopted from rdfapi-php (@link http://sourceforge.net/projects/rdfapi-php/).
 * It was modified and extended in order to fit into Erfurt.
 *
 * @package Semantic
 * @scope prototype
 */
class ParserException extends \Erfurt\Exception {

	protected $_tokenPointer;

	public function __construct($message, $code = -1, $pointer = -1) {
		$this->_tokenPointer = $pointer;
		parent::__construct($message, $code);
	}

	/**
	 * Returns a pointer to the token which caused the exception.
	 *
	 * @return int
	 */
	public function getPointer() {
		return $this->tokenPointer;
	}

	function display($pre = true) {
		if ($pre) {
			print '<pre>';
		}
		echo "ParserException: code $this->code ($this->message) " .
			 "in line $this->line of $this->file\n";
		echo $this->getTraceAsString(), "\n";
		echo "at token: " . $this->tokenPointer;
		if ($pre) {
			print '</pre>';
		}
	}

	public function __toString() {
		return $this->display(false);
	}

}

?>