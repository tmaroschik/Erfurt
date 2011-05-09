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
 *
 * @package erfurt
 * @subpackage   syntax
 * @author    Philipp Frischmuth <pfrischmuth@googlemail.com>
 * @copyright Copyright (c) 2008 {@link http://aksw.org aksw}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @version   $Id: RdfJson.php 2929 2009-04-22 14:56:30Z pfrischmuth $
 */
class RdfJson implements AdapterInterface {

	/**
	 * The injected knowledge base
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	protected $knowledgeBase;

	/**
	 * Injector method for a \Erfurt\KnowledgeBase
	 *
	 * @var \Erfurt\KnowledgeBase
	 */
	public function injectKnowledgeBase(\Erfurt\KnowledgeBase $knowledgeBase) {
		$this->knowledgeBase = $knowledgeBase;
	}

	public function parseFromDataString($dataString) {
		$result = json_decode($dataString, true);

		if ($result === null) {
			throw new \Erfurt\Syntax\RdfParserException('Decoding of JSON failed.');
		}

		return $result;
	}

	public function parseFromFilename($filename) {
		$handle = fopen($filename, 'r');

		if ($handle === false) {
			throw new \Erfurt\Syntax\RdfParserException("Failed to open file with filename '$filename'");
		}

		$dataString = fread($handle, filesize($filename));
		fclose($handle);

		return $this->parseFromDataString($dataString);
	}

	public function parseFromUrl($url) {
		// replace all whitespaces (prevent possible CRLF Injection attacks)
		// http://www.acunetix.com/websitesecurity/crlf-injection.htm
		$url = preg_replace('/\\s+/', '', $url);

		$handle = fopen($url, 'r');

		if ($handle === false) {
			throw new \Erfurt\Syntax\RdfParserException("Failed to open file at url '$url'");
		}

		$dataString = '';

		while (!feof($handle)) {
			$dataString .= fread($handle, 1024);
		}

		fclose($handle);

		return $this->parseFromDataString($dataString);
	}

	public function parseFromDataStringToStore($dataString, $graphUri, $useAc = true) {
		$triples = $this->parseFromDataString($dataString);

		$store = $this->knowledgeBase->getStore();

		$store->addMultipleStatements($graphUri, $triples, $useAc);

		return true;
	}

	public function parseFromFilenameToStore($filename, $graphUri, $useAc = true) {
		$triples = $this->parseFromFilename($filename);

		$store = $this->knowledgeBase->getStore();

		$store->addMultipleStatements($graphUri, $triples, $useAc);

		return true;
	}

	public function parseFromUrlToStore($url, $graphUri, $useAc = true) {
		$triples = $this->parseFromUrl($url);

		$store = $this->knowledgeBase->getStore();

		$store->addMultipleStatements($graphUri, $triples, $useAc);

		return true;
	}

	public function parseNamespacesFromDataString($dataString) {
		return array();
	}

	public function parseNamespacesFromFilename($filename) {
		return array();
	}

	public function parseNamespacesFromUrl($url) {
		return array();
	}

}

?>