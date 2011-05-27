<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Wrapper;

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
 * This wrapper extension provides functionality for gathering linked data.
 *
 * @category  OntoWiki
 * @package   OntoWiki_extensions_wrapper
 * @copyright Copyright (c) 2009 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 */
class Test extends Wrapper {

	static $isAvailableResult = false;
	static $isHandledResult = false;
	static $runResult = false;

	public function getDescription() {
		return 'This wrapper is a wrapper for testing purposes only.';
	}

	public function getName() {
		return 'Test Wrapper';
	}

	public function isAvailable($r, $graphUri) {
		return self::$isAvailableResult;
	}

	public function isHandled($r, $graphUri) {
		return self::$isHandledResult;
	}

	public function run($r, $graphUri) {
		return self::$runResult;
	}

}

?>