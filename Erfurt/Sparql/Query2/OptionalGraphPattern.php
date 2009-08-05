<?php
require_once "GroupGraphPattern.php";

/**
 * Erfurt_Sparql Query - OptionalGraphPattern.
 * 
 * @package    query
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Erfurt_Sparql_Query2_OptionalGraphPattern extends Erfurt_Sparql_Query2_GroupGraphPattern 
{
 	public function getSparql(){
		return "OPTIONAL ".parent::getSparql();
	}
}
?>
