<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Query2;

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
 * Erfurt_Sparql Query - IriRef.
 *
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class IriRef extends ElementHelper implements Interfaces\VarOrIriRef, Interfaces\GraphTerm, Interfaces\IriRefOrFunction {

	protected $iri;
	protected $prefix = null;
	protected $unexpandablePrefix = null;

	/**
	 * @param string $nresource
	 * @param Prefix $prefix
	 * @param string $unexpandablePrefix
	 */
	public function __construct($nresource, Prefix $prefix = null, $unexpandablePrefix = null) {
		if (!is_string($nresource)) {
			throw new \RuntimeException('wrong argument 1 passed to IriRef::__construct. string expected. ' . typeHelper($nresource) . ' found.');
		}
		$this->iri = $nresource;
		if ($prefix != null) {
			$this->prefix = $prefix;
		}
		if ($unexpandablePrefix !== null && is_string($unexpandablePrefix)) {
			$this->unexpandablePrefix = $unexpandablePrefix;
		}
		parent::__construct();
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like "<http://example.com>" or "ns:local"
	 * @return string
	 */
	public function getSparql() {
		if ($this->isPrefixed()) {
			if ($this->prefix != null) {
				return $this->prefix->getPrefixName() . ':' . $this->iri;
			} else {
				return $this->unexpandablePrefix . ':' . $this->iri;
			}
		} else {
			return '<' . $this->iri . '>';
		}
	}

	public function __toString() {
		return $this->getSparql();
	}

	/**
	 * isPrefixed
	 * check if this IriRef uses a prefix
	 */
	public function isPrefixed() {
		return $this->prefix != null || $this->unexpandablePrefix !== null;
	}

	/**
	 * getIri
	 * get the iri - may be only the local part if prefixed
	 * @return string
	 * @see getExpanded
	 */
	public function getIri() {
		return $this->iri;
	}

	/**
	 * getExpanded
	 * expand the prefix
	 * @return string
	 */
	public function getExpanded() {
		if ($this->isPrefixed()) {
			if ($this->prefix != null) {
				return '<' . $this->prefix->getPrefixIri()->iri . $this->iri . '>';
			} else {
				return $this->unexpandablePrefix . ':' . $this->iri;
			}
		} else {
			return '<' . $this->iri . '>';
		}
	}

}

?>