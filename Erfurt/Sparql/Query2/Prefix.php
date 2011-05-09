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
 * Erfurt Sparql Query2 - GraphTerm.
 *
 * @package    erfurt
 * @subpackage query2
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Prefix extends ElementHelper //TODO must be unique in Query - factory?
{

	protected $name;
	protected $iri;

	/**
	 * @param string $nname
	 * @param IriRef $iri
	 */
	public function __construct($nname, $iri) {
		if (!is_string($nname)) {
			throw new \RuntimeException('Argument 1 passed to Prefix::__construct must be an instance of string, instance of ' . typeHelper($iri) . ' given');
		}
		$this->name = $nname;
		if (is_string($iri)) {
			$iri = new IriRef($iri);
		}
		if (!($iri instanceof IriRef)) {
			throw new \RuntimeException("Argument 2 passed to Prefix::__construct must be instance of IriRef or string", E_USER_ERROR);
		}
		$this->iri = $iri;
		parent::__construct();
	}

	/**
	 * getSparql
	 * build a valid sparql representation of this obj - should be like 'PREFIX ns : <http://example.com>'
	 * @return string
	 */
	public function getSparql() {
		return 'PREFIX ' . $this->name . ':' . $this->iri->getSparql();
	}

	/**
	 * getPrefixName
	 * @return string the name of the prefix (everything before the ':')
	 */
	public function getPrefixName() {
		return $this->name;
	}

	/**
	 * getPrefixIri
	 * @return IriRef the iri which this prefix stands for
	 */
	public function getPrefixIri() {
		return $this->iri;
	}

}

?>