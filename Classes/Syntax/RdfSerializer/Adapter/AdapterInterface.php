<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfSerializer\Adapter;

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
 * Interface for Serializer implementations.
 *
 * @copyright  Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @author     Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
interface AdapterInterface {

	/**
	 * Serializes a given graph.
	 *
	 * @param string $graphUri
	 * @param bool $pretty
	 * @param bool $useAc
	 *
	 * @return string
	 */
	public function serializeGraphToString($graphUri, $pretty = false, $useAc = true);

	/**
	 * Serializes a given resource in a given graph.
	 *
	 * @param string $resourceUri
	 * @param string $graphUri
	 * @param bool $pretty
	 * @param bool $useAc
	 *
	 * @return string
	 */
	public function serializeResourceToString($resourceUri, $graphUri, $pretty = false, $useAc = true);

}

?>