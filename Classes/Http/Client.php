<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Http;

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
class Client {

	/**
	 * @var \Erfurt\Http\Request
	 */
	protected $request;

	/**
	 * @var \Erfurt\Http\Response
	 */
	protected $response;

	/**
	 * The actual transfer adapter
	 *
	 * @var \Erfurt\Http\Adapter\AdapterInterface
	 */
	protected $client;

	/**
	 * Constructor method for the http client
	 */
	public function __construct() {
		$this->initHttpMechanism();
	}

	/**
	 * @param string $url
	 * @return void
	 */
	public function get($url) {
		$request = new Request();
		$request->setMethod('GET');
		$request->setUrl($url);
		$this->doRequest($request);
	}

	/**
	 * @param string $url
	 * @param string $body
	 * @return void
	 */
	public function post($url, $body) {
		$request = new Request();
		$request->setMethod('POST');
		$request->setUrl($url);
		$request->setBody($body);
		$this->doRequest($request);
	}

	/**
	 * @param string $url
	 * @param string $body
	 * @return void
	 */
	public function put($url, $body) {
		$request = new Request();
		$request->setMethod('PUT');
		$request->setUrl($url);
		$request->setBody($body);
		$this->doRequest($request);
	}

	/**
	 * @param string $url
	 * @return void
	 */
	public function delete($url) {
		$request = new Request();
		$request->setMethod('DELETE');
		$request->setUrl($url);
		$this->doRequest($request);
	}

	/**
	 * @param string $url
	 * @return void
	 */
	public function head($url) {
		$request = new Request();
		$request->setMethod('HEAD');
		$request->setUrl($url);
		$this->doRequest($request);
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return Response
	 */
	public function doRequest($request) {
		$this->response = NULL;
		if ($this->isClientCapable($request) && $this->isRequestReady($request)) {
			$this->response = $this->client->doRequest($request);
		}
		return $this->response;
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return bool
	 */
	protected function isRequestReady($request) {
		if (!$request->getUrl()) {
			throw new Exception\UrlNotSpecifiedException('The current request has no url specified.', 1308057327);
		}
		switch ($request->getMethod()) {
			case \Erfurt\Http\Request::METHOD_GET:
			case \Erfurt\Http\Request::METHOD_DELETE:
				break;
			case \Erfurt\Http\Request::METHOD_POST:
			case \Erfurt\Http\Request::METHOD_PUT:
				if (!$request->getBody()) {
					throw new Exception\EmptyBodyException('The body of the request may not be empty when used under request method POST or PUT.', 1308057961);
				}
				break;
			default:
				throw new Exception\UnsupportedRequestMethodException('The request method "' . $request->getMethod() . '" is currently unsupported. Please use one the defined methods in \Erfurt\Http\Request.', 1308057838);
				break;
		}
		return TRUE;
	}

	/**
	 * @param \Erfurt\Http\Request $request
	 * @return
	 */
	protected function isClientCapable($request) {
		if (!$this->client->can($request->getMethod())) {
			throw new Exception\AdapterNotCapableException('HTTP Client adapter "' . get_class($this->client) . '" isn\'t capable of performing this request.', 1308057327);
		}
		return TRUE;
	}

	/**
	 * Initializes the client adapter
	 * @return void
	 */
	protected function initHttpMechanism() {
		// Find the best available HTTP mechanism
		if (function_exists('curl_init')) {
			$this->client = new Adapter\CurlAdapter();
		} else {
			$this->client = new Adapter\FileContentsAdapter();
		}
	}

}


?>