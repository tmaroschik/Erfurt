<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;
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
 * Erfurt_Sparql Query2 - RDFLiteral.
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class RDFLiteral extends ElementHelper implements Interfaces\GraphTerm, Interfaces\PrimaryExpression {

	protected $value = '';
	protected $datatype;
	protected $lang;
	protected $mode = 0;
	protected $delimiter = '"';

	public static $knownShortcuts = array('int', 'boolean', 'float', 'decimal', 'string', 'time', 'date');

	/**
	 * @param string $str the literal value
	 * @param null|string|IriRef $meta if is null: untyped literal with no language tag.
	 *  If is IriRef: typed literal. if is string: when string is one of
	 *  ('int','boolean','float','decimal','string','time','date') convert to IriRef with XMLSchema#%s.
	 *  Else use as language tag.
	 * @param String $delimiter string to delimit the literal prepended and appended (in reverse order)
	 *  in Sparql ouput.
	 */
	public function __construct($str, $meta = null, $delimiter = '"') {
		$this->delimiter = $delimiter;
		if (!is_string($str)) {
			throw new \RuntimeException('Argument 1 passed to RDFLiteral::__construct must be a string, instance of ' . typeHelper($str) . ' given');
		}
		$this->value = $str;
		if ($meta != null) {
			if (is_string($meta)) {
				if (in_array($meta, self::$knownShortcuts)) {
					//is a typed literal, type given as shortcut
					$xmls = 'http://www.w3.org/2001/XMLSchema#';
					$this->datatype = new IriRef($xmls . $meta);
					$this->mode = 2;
				} else {
					// is a literal with language tag
					$this->lang = $meta;
					$this->mode = 1;
				}
			} else {
				if ($meta instanceof IriRef) {
					//is a typed literal
					$this->datatype = $meta;
					$this->mode = 2;
				} else {
					throw new \RuntimeException('Argument 2 passed to RDFLiteral::__construct must be an instance of IriRef or string, instance of ' . typeHelper($meta) . ' given');
				}
			}
		}
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like 'abc' or 'abc'^^<http://www.w3.org/2001/XMLSchema#string> or 'abc'@de
	 * @return string
	 */
	public function getSparql() {
		$sparql = $this->delimiter . $this->value . strrev($this->delimiter);
		switch ($this->mode) {
			case 0:
				break;
			case 1:
				$sparql .= '@' . $this->lang;
				break;
			case 2:
				$sparql .= '^^' . $this->datatype->getSparql();
				break;
		}
		return $sparql;
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * setValue
	 * @param string $val
	 * @return RDFLiteral $this
	 */
	public function setValue($val) {
		if (is_string($val) || is_numeric($val)) {
			$this->value = $val;
		} else {
			throw new \RuntimeException('Argument 1 passed to RDFLiteral::setValue must be string, instance of ' . typeHelper($val) . ' given');
		}
		return $this;
	}

	/**
	 * getValue
	 * @return string the value of the literal without datatype or language tag
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * setDatatype
	 * @param IriRef $type
	 * @return RDFLiteral $this
	 */
	public function setDatatype(IriRef $type) {
		$this->datatype = $type;
		return $this;
	}

	/**
	 * getDatatype
	 * @return IriRef the datatype
	 */
	public function getDatatype() {
		return $this->datatype;
	}

	/**
	 * getLanguageTag
	 * @return string the language tag
	 */
	public function getLanguageTag() {
		return $this->lang;
	}

	/**
	 * setLanguageTag
	 * @param string $lang the language tag
	 * @return RDFLiteral $this
	 */
	public function setLanguageTag($lang) {
		if (is_string($lang)) {
			$this->lang = $lang;
		}
		return $this;
	}

	public function isPlain() {
		return $this->mode == 0;
	}

	public function isLangTagged() {
		return $this->mode == 1;
	}

	public function isTyped() {
		return $this->mode == 2;
	}

}

?>