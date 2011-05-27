<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfParser\Adapter;

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
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: Interface.php 2929 2009-04-22 14:56:30Z pfrischmuth $
 */
interface AdapterInterface {

	public function parseFromDataString($dataString);

	public function parseFromFilename($filename);

	public function parseFromUrl($url);

	public function parseFromDataStringToStore($dataString, $graphUri, $useAc = true);

	public function parseFromFilenameToStore($filename, $graphUri, $useAc = true);

	public function parseFromUrlToStore($filename, $graphUri, $useAc = true);

	public function parseNamespacesFromDataString($dataString);

	public function parseNamespacesFromFilename($filename);

	public function parseNamespacesFromUrl($url);

}

?>