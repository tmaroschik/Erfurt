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
class FileContentsAdapter implements AdapterInterface {

	/**
	 * @param string $feature
	 * @return bool
	 */
	public function can($feature) {
		switch ($feature) {
			case \Erfurt\Http\Request::METHOD_GET:
				// TODO: need to check that file_get_contents
				// can accept URLs.
				if (ini_get('allow_url_fopen')) {
					//echo "INFO: file_get_contents allows URLs\n";
					return TRUE;
				}
				return FALSE;
				break;
			default:
				return FALSE;
				break;
		}
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return Response|null
	 */
	public function doRequest(\Erfurt\Http\Request $request) {
		$response = NULL;
		if ($request->getMethod() == \Erfurt\Http\Request::METHOD_GET) {
			$url = $request->getUrl();
			$body = @file_get_contents($url);

			// Check whether file_get_contents threw an error
			$error = error_get_last();

			if (strpos($error['message'], $url) !== false) {
				$msg = $error['message'];

				//echo "file get contents returned an error.\n";
				$httpPattern = '/HTTP\/(\d\.\d) (\d*) (.*)$/';
				if (preg_match($httpPattern, $msg, $matches)) {
					//echo 'HTTP/', $matches[1], ' ', $matches[2], ' ', $matches[3], "\n";

					$response = new \Erfurt\Http\Response();
					$response->setStatus($matches[2]);
					$response->setStatusMessage(trim($matches[3]));
					$response->setVersion($matches[1]);
				} elseif (preg_match('/stream: (.*)$/', $msg, $matches)) {
					$response = new \Erfurt\Http\Response();
					$response->setStatus(502);
					$response->setStatusMessage($matches[1]);
				} else {
					echo "WARN: No valid HTTP reply from file_get_contents:\n",
					$msg, "\n";
				}
			} else {
				// Valid response
				$response = new \Erfurt\Http\Response();
				if (!empty($body)) {
					$response->setStatus(200);
					$response->setStatusMessage('Ok');
					$response->setBody($body);
				}
			}
		}
		return $response;
	}

}

?>