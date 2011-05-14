<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Rdf;
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
 * Represents a basic RDF literal.
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 */
class Literal extends Node {

	protected $_label = false;
	protected $_lang = null;
	protected $_datatype = null;

	protected function __construct($label) {

		$this->_label = $label;
	}

    /**
     * Returns a string representation of this resource.
     *
     * @return string
     */
    public function __toString() {
        if ( $this->getLabel() ) {
            $ret = $this->getLabel();
            if ( $this->getDatatype() ) {
                $ret .= "^^" . $this->getDatatype() ;
            }
            else if ( $this->getLanguage() ) {
                $ret .= "@" . $this->getLanguage() ;
            }
            return $ret;
        }
        else {
            return "";
        }
    }

	public function setLanguage($lang) {

		$this->_lang = $lang;
	}

	public function setDatatype($datatype) {

		$this->_datatype = $datatype;
	}

	public function getLabel() {

		return $this->_label;
	}

	public function getDatatype() {

	    return $this->_datatype;
	}

	public function getLanguage() {

	    return $this->_lang;
	}

}

?>