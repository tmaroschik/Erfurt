<?php

require_once "VarOrIriRef.php";

/**
 * Erfurt_Sparql Query - IriRef.
 * 
 * @package    query
 * @author     Jonas Brekle <jonas.brekle@gmail.com>
 * @copyright  Copyright (c) 2008, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version    $Id$
 */
class Erfurt_Sparql_Query2_IriRef implements Erfurt_Sparql_Query2_VarOrIriRef{
	protected $iriRef;
	protected $prefix;
	
	public function __construct($nresource){
		if(!is_string($nresource)){throw new RuntimeException("wrong parameter for contructing Erfurt_Sparql_Query2_Variable. string expected. "+gettype($nresource)+" found.");}
		$this->iriRef = $nresource;
		if(func_num_args()>1){
			$prefix = func_get_arg(1);
			if(is_a($prefix, "Erfurt_Sparql_Query2_Prefix")){
				$this->prefix = $prefix;
			} else {
				throw new RuntimeException("Argument 2 passed to Erfurt_Sparql_Query2_IriRef::__construct must be an instance of Erfurt_Sparql_Query2_Prefix, instance of ".gettype($prefix)." given");
			}
		}
	}
	
	public function getSparql(){
		return $this->isPrefixed() ? ($this->prefix->getPrefixName().":".$this->iriRef) : ("<".$this->iriRef.">");
	}
	
	public function isPrefixed(){
		return !empty($this->prefix);
	}
}
?>