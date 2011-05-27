<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Configuration\Source;

/*                                                                        *
 * This script belongs to the Erfurt framework.                           *
 *                                                                        *
 * It has been ported from the corresponding class of the FLOW3           *
 * framework. All credits go to the responsible contributors.             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        */

/**
 * Configuration source based on YAML files
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class YamlSource implements \Erfurt\Configuration\Source\SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load, excluding the file extension (ie. ".yaml")
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function load($pathAndFilename) {
		if (file_exists($pathAndFilename . '.yaml')) {
			try {
				$configuration = \Erfurt\Configuration\Source\YamlParser::loadFile($pathAndFilename . '.yaml');
			} catch (\Erfurt\Exception $exception) {
				throw new \Erfurt\Configuration\Exception\ParseErrorException('A parse error occurred while parsing file "' . $pathAndFilename . '.yaml". Error message: ' . $exception->getMessage(), 1232014321);
			}
		} else {
			$configuration = array();
		}
		return $configuration;
	}

	/**
	 * Save the specified configuration array to the given file in YAML format.
	 *
	 * @param string $pathAndFilename Full path and file name of the file to write to, excluding the dot and file extension (i.e. ".yaml")
	 * @param array $configuration The configuration to save
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function save($pathAndFilename, array $configuration) {
		$header = '';
		if (file_exists($pathAndFilename . '.yaml')) {
			$header = $this->getHeaderFromFile($pathAndFilename . '.yaml');
		}
		$yaml = \Erfurt\Configuration\Source\YamlParser::dump($configuration);
		file_put_contents($pathAndFilename . '.yaml', $header . PHP_EOL . $yaml);
	}

	/**
	 * Read the header part from the given file. That means, every line
	 * until the first non comment line is found.
	 *
	 * @param string $pathAndFilename
	 * @return string The header of the given YAML file
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	protected function getHeaderFromFile($pathAndFilename) {
		$header = '';
		$line = '';
		$fileHandle = fopen($pathAndFilename, 'r');
		while ($line = fgets($fileHandle)) {
			if (preg_match('/^#/', $line)) {
				$header .= $line;
			} else {
				break;
			}
		}
		fclose($fileHandle);
		return $header;
	}
}
?>