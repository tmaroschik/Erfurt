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
class CurlAdapter implements AdapterInterface {

	/**
	 * @param string $feature
	 * @return bool
	 */
	public function can($feature) {
		switch ($feature) {
			case \Erfurt\Http\Request::METHOD_GET:
			case \Erfurt\Http\Request::METHOD_POST:
				if (function_exists('curl_init')) {
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
		switch ($request->getMethod()) {
			case \Erfurt\Http\Request::METHOD_GET:
				$response = $this->doGet($request);
				break;
			case \Erfurt\Http\Request::METHOD_POST:
				$response = $this->doPost($request);
				break;
			case \Erfurt\Http\Request::METHOD_PUT:
				$response = $this->doPut($request);
				break;
			case \Erfurt\Http\Request::METHOD_DELETE:
				$response = $this->doDelete($request);
				break;
			default:
				break;
		}
		return $response;
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return Response|null
	 */
	protected function doGet(\Erfurt\Http\Request $request) {
		$ch = curl_init();

		curl_setopt_array($ch, array(
									CURLOPT_URL => $request->getUrl(),
									CURLOPT_HTTPGET => true,
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_HEADER => true
							   ));

		// Convert to raw CURL headers and add to request
		$headers = $this->processRequestHeaders($request->getHeaders());
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$httpOutput = curl_exec($ch);
		$response = $this->parseResponse($httpOutput, $ch);

		curl_close($ch);
		return $response;
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return \Erfurt\Http\Response|null
	 */
	protected function doPost(\Erfurt\Http\Request $request) {
		$response = NULL;
		$ch = curl_init();

		$data = $request->getBody();

		if ($data) {
			curl_setopt_array($ch, array(
										CURLOPT_URL => $request->getUrl(),
										CURLOPT_POST => true,
										CURLOPT_POSTFIELDS => $data,
										CURLOPT_RETURNTRANSFER => true,
										CURLOPT_HEADER => true
								   ));

			// Convert to raw CURL headers and add to request
			$headers = $this->processRequestHeaders($request->getHeaders());
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			$httpOutput = curl_exec($ch);
			$response = $this->parseResponse($httpOutput, $ch);
		} else {
			echo "ERROR: No body to send.\n";
		}

		curl_close($ch);
		return $response;
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return \Erfurt\Http\Response|null
	 */
	protected function doPut(\Erfurt\Http\Request $request) {
		$response = NULL;
		$ch = curl_init();

		$data = $request->getBody();

		if ($data) {
			curl_setopt_array($ch, array(
										CURLOPT_URL => $request->getUrl(),
										CURLOPT_CUSTOMREQUEST => 'PUT',
										CURLOPT_POSTFIELDS => $data,
										CURLOPT_RETURNTRANSFER => true,
										CURLOPT_HEADER => true
								   ));

			// Convert to raw CURL headers and add to request
			$headers = $this->processRequestHeaders($request->getHeaders());
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}

			$httpOutput = curl_exec($ch);
			$response = $this->parseResponse($httpOutput, $ch);
		} else {
			echo "ERROR: No body to send.\n";
		}

		curl_close($ch);
		return $response;
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return \Erfurt\Http\Response
	 */
	protected function doDelete(\Erfurt\Http\Request $request) {
		$ch = curl_init();

		curl_setopt_array($ch, array(
									CURLOPT_URL => $request->getUrl(),
									CURLOPT_CUSTOMREQUEST => 'DELETE',
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_HEADER => true
							   ));

		// Convert to raw CURL headers and add to request
		$headers = $this->processRequestHeaders($request->getHeaders());
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$httpOutput = curl_exec($ch);
		$response = $this->parseResponse($httpOutput, $ch);

		curl_close($ch);
		return $response;
	}

	/**
	 *	Parses the raw HTTP response and returns a response object
	 **/
	protected function parseResponse($output, $ch) {
		$response = new \Erfurt\Http\Response();

		if ($output) {
			$lines = explode("\n", $output);
			$isHeader = true;
			$buffer = array();

			foreach ($lines as $line) {
				if ($isHeader) {
					if (preg_match('/^\s*$/', $line)) {
						// Header/body separator
						$isHeader = false;
					} else {
						// This is a real HTTP header
						if (preg_match('/^([^:]+)\:(.*)$/', $line, $matches)) {
							//echo "HEADER: [", $matches[1], ']: [', $matches[2], "]\n";
							$name = trim($matches[1]);
							$value = trim($matches[2]);
							$response->addHeader($name, $value);
						} else {
							// This is the status response
							//echo "HEADER: ", trim($line), "\n";
							if (preg_match(
								'/^(HTTP\/\d\.\d) (\d*) (.*)$/',
								trim($line), $matches)
							) {
								$response->setStatus($matches[2]);
								$response->setStatusMessage($matches[3]);
								$response->setVersion($matches[1]);
							}
						}
					}
				} else {
					$buffer[] = $line;
				}
			}
			// The buffer is the HTTP Entity Body
			$response->setBody(implode("\n", $buffer));
		} else {
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($statusCode == 0) {
				$response->setStatus(502);
				$response->setStatusMessage('CURL Error');
			} else {
				$response->setStatus($statusCode);
				$response->setStatusMessage('CURL Response');
			}
		}

		return $response;
	}

	/**
	 * @param array $headers
	 * @return array
	 */
	protected function processRequestHeaders($headers) {
		$rawHeaders = array();
		if (is_array($headers)) {
			foreach ($headers as $key => $value) {
				$rawHeaders[] = $key . ': ' . $value;
			}
		}
		return $rawHeaders;
	}

}

?>