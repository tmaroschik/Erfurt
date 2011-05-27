<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Cache\Backend\QueryCache;

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
 *	AbstractClass Erfurt_Cache_Backend_QueryCache_Backend which implements Erfurt_Cache_Backend_QueryCache_Interface
 *	@author			Michael Martin <martin@informatik.uni-leipzig.de>
 *  @package        erfurt
 *  @subpackage     cache
 *  @copyright      Copyright (c) 2009 {@link http://aksw.org aksw}
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/ontowiki/
 *	@version		0.1
 */
abstract class Backend implements QueryCacheInterface {

	/**
	 * @var \Erfurt\Store\Store
	 */
	public $store;

	/**
	 * @var \Erfurt\KnowledgeBase
	 */
	public $knowledgeBase;

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
		$this->store = $this->knowledgeBase->getStore();
	}

}

?>