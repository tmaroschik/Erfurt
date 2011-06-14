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
 * @author	Mike Davies <isofarro2@gmail.com>
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class Response {

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @var int
	 */
	protected $status;

	/**
	 * @var string
	 */
	protected $statusMessage;

	/**
	 * @var array
	 */
	protected $headers;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * Constructor method for a http response
	 */
	public function __construct() {

	}

	/**
	 * @param string $version
	 * @return void
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * @param int $status
	 * @return void
	 */
	public function setStatus($status) {
		$this->status = (int) $status;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $statusMsg
	 * @return void
	 */
	public function setStatusMessage($statusMsg) {
		$this->statusMessage = $statusMsg;
	}

	/**
	 * @return string
	 */
	public function getStatusMessage() {
		return $this->statusMessage;
	}

	/**
	 * @param string $body
	 * @return void
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @param array $headers
	 * @return void
	 */
	public function setHeaders($headers) {
		foreach ($headers as $name => $value) {
			$this->headers[$name] = $value;
		}
	}

	/**
	 * @param string $header
	 * @param string $value
	 * @return void
	 */
	public function addHeader($header, $value) {
		if (empty($this->headers)) {
			$this->headers = array();
		}
		$this->headers[$header] = $value;
	}
}

?>