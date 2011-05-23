<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Syntax\RdfParser\Adapter;
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
 * @package erfurt
 * @subpackage   syntax
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