<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Utility;

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
 * Simple static class for performing regular expression-based IRI checking and normalizing.
 *
 * @category Erfurt
 * @package Iri
 * @copyright Copyright (c) 2009 {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @author Norman Heino <norman.heino@gmail.com>
 */
class Iri {

	/**
	 * Regular expression to split the schema-specific part of HTTP IRIs
	 * @var string
	 */
	protected static $httpSplit = '/^\/\/(.+@)?(.+?)(\/.*)?$/';

	/**
	 * Regular expression to match the whole IRI
	 * @var string
	 */
	protected static $regExp = '/^([a-zA-Z][a-zA-Z0-9+.-]+):([^\x00-\x0f\x20\x7f<>{}|\[\]`"^\\\\])+$/';

	/**
	 * Checks the general syntax of a given IRI. Protocol-specific syntaxes are not checked.
	 * Instead, only characters disallowed an all IRIs lead to a rejection of the check.
	 *
	 * @param string $iri
	 * @return string
	 */
	public static function check($iri) {
		return (preg_match(self::$regExp, (string)$iri) === 1);
	}

	/**
	 * Normalizes the given IRI according to {@link http://www.ietf.org/rfc/rfc2396.txt}.
	 * In particular, protocol and -- for HTTP IRIs -- the server part are
	 * normalized to lower case.
	 *
	 * @param string $iri The IRI to be normalized
	 * @return string
	 */
	public static function normalize($iri) {
		if (!self::check($iri)) {
			throw new Exception('The supplied string is not a valid IRI. ');
		}

		// split into schema and schema-specific part
		$parts = explode(':', $iri, 2);
		$schema = strtolower($parts[0]);
		$schemaSpecific = isset($parts[1]) === true ? $parts[1] : '';

		// schema-only normalization
		$normalized = $schema
					  . ':'
					  . $schemaSpecific;

		// check for HTTP(S) IRIs
		if (strpos('http', $schema) !== false) {
			// here we can do more ...
			$matches = array();
			preg_match(self::$httpSplit, $schemaSpecific, $matches);

			$authority = $matches[1];
			$server = strtolower($matches[2]);
			$path = isset($matches[3]) ? $matches[3] : '';

			// server-part normalization
			$normalized = $schema
						  . '://'
						  . $authority
						  . $server
						  . $path;
		}

		return $normalized;
	}

}

?>