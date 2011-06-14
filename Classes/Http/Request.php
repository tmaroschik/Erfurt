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
class Request {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $version = 'HTTP/1.1';

	/**
	 * @var array
	 */
	protected $headers;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $rawUrl;

	/**
	 * @param string $url
	 */
	public function __construct($url=NULL) {
		$this->headers = array();

		if (!is_null($url)) {
			$this->setUrl($url);
		}
	}

	/**
	 * @param string $url
	 * @return void
	 */
	public function setUrl($url) {
		$this->rawUrl = $url;
		$this->url    = $this->segmentUrl($url);
		$this->setPath($this->url['path']);
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->rawUrl;
	}

	/**
	 * @param string $path
	 * @return void
	 */
	public function setPath($path) {
		// TODO: Check that it starts with a /
		$this->path = $path;
	}

	/**
	 * @param string $method
	 * @return void
	 */
	public function setMethod($method) {
		$method = strtoupper($method);
		if ($this->isValidMethod($method)) {
			$this->method = $method;
		}
	}

	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @param string $version
	 * @return void
	 */
	public function setVersion($version) {
		$version = $this->normaliseVersion($version);
		if (!is_null($version)) {
			$this->version = 'HTTP/' . $version;
		}
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	public function getHeader($name) {
		if (!empty($this->headers[$name])) {
			return $this->headers[$name];
		}
		return NULL;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function addHeader($name, $value) {
		$name = $this->normaliseHeader($name);
		$this->headers[$name] = $value;
	}

	/**
	 * @param array $headers
	 * @return void
	 */
	public function setHeaders($headers) {
		foreach($headers as $key=>$value) {
			$key = $this->normaliseHeader($key);
			$this->headers[$key] = $value;
		}
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @param array|string $body
	 * @return void
	 */
	public function setBody($body) {
		if (is_array($body)) {
			$tmp = array();
			foreach($body as $name=>$val) {
				$tmp[] = $name . '=' . $val;
			}
			$this->body = implode('&', $tmp);
		} else {
			$this->body = $body;
		}
	}

	/**
	 * @param string $header
	 * @return mixed|string
	 */
	protected function normaliseHeader($header) {
		$name = str_replace('-', ' ', $header);
		$name = ucwords(strtolower($name));
		$name = str_replace(' ', '-', $name);
		return $name;
	}

	/**
	 * @param string $url
	 * @return array
	 */
	protected function segmentUrl($url) {
		$segments = array();
		if (preg_match('/^(\w+):\/\/([^\/]+)(.+)$/', $url, $matches)) {
			$segments['protocol'] = $matches[1];
			$segments['path']     = $matches[3];

			$domain = $matches[2];
			// TODO: Check for username/passwords in the URL
			if (preg_match('/^([^:]+):?(\d*)/', $domain, $matches)) {
				$segments['domain'] = $matches[1];
				if (!empty($matches[2])) {
					$segments['port'] = $matches[2];
				}
			}
		}
		return $segments;
	}

	/**
	 * @param string $version
	 * @return null|string
	 */
	protected function normaliseVersion($version) {
		if ($version=='1.1' || $version=1.1) {
			return '1.1';
		} elseif ($version=='1.0' || $version==1.0 || $version==1) {
			return '1.0';
		}
		return NULL;
	}

	/**
	 * @param string $method
	 * @return bool
	 */
	protected function isValidMethod($method) {
		switch($method) {
			case self::METHOD_GET:
			case self::METHOD_POST:
			case self::METHOD_PUT:
			case self::METHOD_DELETE:
				return TRUE;
				break;
			default:
				return FALSE;
				break;
		}
	}

}

?>