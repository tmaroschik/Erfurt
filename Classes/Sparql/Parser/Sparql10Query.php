<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Sparql\Parser;

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
 * @category Erfurt
 * @package Sparql_Parser_Sparql
 * @author Rolland Brunec <rollxx@gmail.com>
 * @copyright Copyright (c) 2010 {@link http://aksw.org aksw}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class Sparql10Query implements SparqlInterface {

	public static function initFromString($queryString, $parsePartial = null) {
		$retval = null;
		$input = new Util\CaseInsensitiveStream($queryString);
		$lexer = new Sparql10\QueryLexer($input);
		//		if (!count($lexer->getErrors())) {
		$tokens = new \CommonTokenStream($lexer);
		$parser = new Sparql10\QueryParser($tokens);
		if ($parsePartial != null && is_string($parsePartial) && method_exists($parser, $parsePartial)) {
			$retval = call_user_func(array($parser, $parsePartial));
		} else {
			$retval = $parser->parse();
		}
		//		}
		return array('retval' => $retval, 'errors' =>
			//array_merge($lexer->getErrors(),
			//$parser?
		$parser->getErrors()
			//:array())
		);
	}

}

?>