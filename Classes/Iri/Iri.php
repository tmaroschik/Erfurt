<?php
declare(ENCODING = 'utf-8');
namespace Erfurt\Iri;

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
 * Represents a Unique Resource Identifier according to STD 66 / RFC 3986
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Iri {

	const PATTERN_MATCH_SCHEME = '/^[a-zA-Z][a-zA-Z0-9\+\-\.]*$/';
	const PATTERN_MATCH_USERNAME = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
	const PATTERN_MATCH_PASSWORD = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
	const PATTERN_MATCH_HOST = '/^[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
	const PATTERN_MATCH_PORT = '/^[0-9]*$/';
	const PATTERN_MATCH_PATH = '/^.*$/';
	const PATTERN_MATCH_FRAGMENT = '/^(?:[a-zA-Z0-9_~!&\',;=:@\/?\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';

	/**
	 * @var string The scheme / protocol of the locator, eg. http
	 */
	protected $scheme;

	/**
	 * @var string User name of a login, if any
	 */
	protected $username;

	/**
	 * @var string Password of a login, if any
	 */
	protected $password;

	/**
	 * @var string Host of the locator, eg. some.subdomain.example.com
	 */
	protected $host;

	/**
	 * @var integer Port of the locator, if any was specified. Eg. 80
	 */
	protected $port;

	/**
	 * @var string The hierarchical part of the IRI, eg. /products/acme_soap
	 */
	protected $path;

	/**
	 * @var string Query string of the locator, if any. Eg. color=red&size=large
	 */
	protected $query;

	/**
	 * @var array Array representation of the IRI query
	 */
	protected $arguments = array();

	/**
	 * @var string Fragment / anchor, if one was specified.
	 */
	protected $fragment;

	/**
	 * Constructs the IRI object from a string
	 *
	 * @param string $iriString String representation of the IRI
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($iriString) {
		if (!is_string($iriString)) throw new \InvalidArgumentException('The IRI must be a valid string.', 1176550571);

		$iriParts = parse_url($iriString);
		if (is_array($iriParts)) {
			$this->scheme = isset($iriParts['scheme']) ? $iriParts['scheme'] : NULL;
			$this->username = isset($iriParts['user']) ? $iriParts['user'] : NULL;
			$this->password = isset($iriParts['pass']) ? $iriParts['pass'] : NULL;
			$this->host = isset($iriParts['host']) ? $iriParts['host'] : NULL;
			$this->port = isset($iriParts['port']) ? $iriParts['port'] : NULL;
			$this->path = isset($iriParts['path']) ? $iriParts['path'] : NULL;
			if (isset($iriParts['query'])) {
				$this->setQuery ($iriParts['query']);
			}
			$this->fragment = isset($iriParts['fragment']) ? $iriParts['fragment'] : NULL;
		}
	}

	/**
	 * Returns the IRI's scheme / protocol
	 *
	 * @return string IRI scheme / protocol
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * Sets the IRI's scheme / protocol
	 *
	 * @param  string $scheme The scheme. Allowed values are "http" and "https"
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setScheme($scheme) {
		if (preg_match(self::PATTERN_MATCH_SCHEME, $scheme) === 1) {
			$this->scheme = strtolower($scheme);
		} else {
			throw new \InvalidArgumentException('"' . $scheme . '" is not a valid scheme.', 1184071237);
		}
	}

	/**
	 * Returns the username of a login
	 *
	 * @return string User name of the login
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Sets the IRI's username
	 *
	 * @param string $username User name of the login
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setUsername($username) {
		if (preg_match(self::PATTERN_MATCH_USERNAME, $username) === 1) {
			$this->username = $username;
		} else {
			throw new \InvalidArgumentException('"' . $username . '" is not a valid username.', 1184071238);
		}
	}

	/**
	 * Returns the password of a login
	 *
	 * @return string Password of the login
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Sets the IRI's password
	 *
	 * @param string $password Password of the login
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setPassword($password) {
		if (preg_match(self::PATTERN_MATCH_PASSWORD, $password) === 1) {
			$this->password = $password;
		} else {
			throw new \InvalidArgumentException('The specified password is not valid as part of a IRI.', 1184071239);
		}
	}

	/**
	 * Returns the host(s) of the IRI
	 *
	 * @return string The hostname(s)
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * Sets the host(s) of the IRI
	 *
	 * @param string $host The hostname(s)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setHost($host) {
		if (preg_match(self::PATTERN_MATCH_HOST, $host) === 1) {
			$this->host = $host;
		} else {
			throw new \InvalidArgumentException('"' . $host . '" is not valid host as part of a IRI.', 1184071240);
		}
	}

	/**
	 * Returns the port of the IRI
	 *
	 * @return integer Port
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * Sets the port in the IRI
	 *
	 * @param string $port The port number
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setPort($port) {
		if (preg_match(self::PATTERN_MATCH_PORT, $port) === 1) {
			$this->port = $port;
		} else {
			throw new \InvalidArgumentException('"' . $port . '" is not valid port number as part of a IRI.', 1184071241);
		}
	}

	/**
	 * Returns the IRI path
	 *
	 * @return string IRI path
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Sets the path of the IRI
	 *
	 * @param string $path The path
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setPath($path) {
		if (preg_match(self::PATTERN_MATCH_PATH, $path) === 1) {
			$this->path = $path;
		} else {
			throw new \InvalidArgumentException('"' . $path . '" is not valid path as part of a IRI.', 1184071242);
		}
	}

	/**
	 * Returns the IRI's query part
	 *
	 * @return string The query part
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Sets the IRI's query part. Updates (= overwrites) the arguments accordingly!
	 *
	 * @param string $query The query string.
	 * @return void
	 * @api
	 */
	public function setQuery($query) {
		$this->query = $query;
		parse_str($query, $this->arguments);
	}

	/**
	 * Returns the arguments from the IRI's query part
	 *
	 * @return array Associative array of arguments and values of the IRI's query part
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the fragment / anchor, if any
	 *
	 * @return string The fragment
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * Sets the fragment in the IRI
	 *
	 * @param string $fragment The fragment (aka "anchor")
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setFragment($fragment) {
		if (preg_match(self::PATTERN_MATCH_FRAGMENT, $fragment) === 1) {
			$this->fragment = $fragment;
		} else {
			throw new \InvalidArgumentException('"' . $fragment . '" is not valid fragment as part of a IRI.', 1184071252);
		}
	}

	/**
	 * Returns a string representation of this IRI
	 *
	 * @return string This IRI as a string
	 * @author Robert Lemke	<robert@typo3.org>
	 * @api
	 */
	public function __toString() {
		$iriString = '';

		$iriString .= isset($this->scheme) ? $this->scheme . '://' : '';
		if (isset($this->username)) {
			if (isset($this->password)) {
				$iriString .= $this->username . ':' . $this->password . '@';
			} else {
				$iriString .= $this->username . '@';
			}
		}
		$iriString .= $this->host;
		$iriString .= isset($this->port) ? ':' . $this->port : '';
		if (isset($this->path)) {
			$iriString .= $this->path;
			$iriString .= isset($this->query) ? '?' . $this->query : '';
			$iriString .= isset($this->fragment) ? '#' . $this->fragment : '';
		}
		return $iriString;
	}
}

?>