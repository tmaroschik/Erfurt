<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Http\Adapter;

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
 * @author    Mike Davies <isofarro2@gmail.com>
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
interface AdapterInterface {

	/**
	 * @abstract
	 * @param string $feature
	 * @return void
	 */
	public function can($feature);

	/**
	 * @abstract
	 * @param \Erfurt\Http\Request $request
	 * @return void
	 */
	public function doRequest(\Erfurt\Http\Request $request);
}

?>