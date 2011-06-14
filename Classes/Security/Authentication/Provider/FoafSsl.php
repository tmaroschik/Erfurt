<?php
declare(ENCODING = 'utf-8') ;
namespace Erfurt\Security\Authentication\Adapter;

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
 * This class provides functionality to authenticate and register users based
 * on FOAF+SSL. If SSL/TLS is supported, this class checks whether a valid user exsists
 * by itself. If not, it can use a remote service, iff configured so in config. It also
 * supports a form of auth delegation, i.e. if another Erfurt application connects via
 * SSL/TLS and this application can be authenticated with FOAF+SSL and the application
 * provides a FOAF+SSL auth header (non-standard!) with a valid WebID and the dereferenced
 * FOAF file contains the IRI of the agent, then the user is authenticated...
 *
 * @scope singleton
 */
class FoafSsl implements \Zend_Auth_Adapter_Interface {
	/**
	 * The duration a timestamp is valid...
	 *
	 * @var int
	 */
	const TIMESTAMP_VALIDITY = 1000;

	/* Property IRIs */
	const PUBLIC_KEY_PROPERTY = 'http://www.w3.org/ns/auth/rsa#RSAPublicKey';
	const IDENTITY_PROP = 'http://www.w3.org/ns/auth/cert#identity';
	const EXPONENT_PROP = 'http://www.w3.org/ns/auth/rsa#public_exponent';
	const MODULUS_PROP = 'http://www.w3.org/ns/auth/rsa#modulus';
	const DECIMAL_PROP = 'http://www.w3.org/ns/auth/cert#decimal';
	const HEX_PROP = 'http://www.w3.org/ns/auth/cert#hex';

	// ------------------------------------------------------------------------
	// --- Protected properties -----------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Contains the IRI of the graph used for ac and auth.
	 *
	 * @var string
	 */
	protected $accessControlGraphIri = null;

	/**
	 * Contains the config object.
	 *
	 * @var array
	 */
	protected $configuration = null;

	/**
	 * Contains fetched FOAF data if dereferencing was succesfull.
	 *
	 * @var array
	 */
	protected $foafData = array();

	/**
	 * GET of the request if we use a remote idp.
	 *
	 * @var array|null
	 */
	protected $get = null;

	/**
	 * URL of the idp iff set.
	 *
	 * @var string|null
	 */
	protected $idpServiceUrl = null;

	/**
	 * Contains the public key of the idp iff configured.
	 *
	 * @var string|null
	 */
	protected $publicKey = null;

	/**
	 * An optional redirect URL, that is used in combination with a remote idp only.
	 * If a remote idp is used the idp will redirect to that URL.
	 *
	 * @var string|null
	 */
	protected $redirectUrl = null;

	/**
	 * Contains a reference to the store object in order to do SPARQL.
	 *
	 * @var Erfurt_Store
	 */
	protected $store = null;

	/**
	 * Contains the IRIs used for modeling users and rights in RDF.
	 * This IRIs are loaded from the config file.
	 *
	 * @var array
	 */
	protected $iris = array();

	/**
	 * Whether to verify signature of idp result.
	 *
	 * @var bool
	 */
	protected $verifySignature = true;

	/**
	 * Whether to check timestamp of idp result.
	 *
	 * @var bool
	 */
	protected $verifyTimestamp = true;

	/**
	 * If SSL/TLS is used the parameters can be left out. If a remote idp is
	 * used they need to be set.
	 *
	 * @param array $get
	 * @param string $redirectUrl
	 */
	public function __construct(array $get = null, $redirectUrl = null) {
		$this->get = $get;
		$this->redirectUrl = $redirectUrl;

		$app = Erfurt_App::getInstance();
		$this->store = $app->getStore();

		$config = $app->getConfig();
		$this->configuration = $config;
		$this->accessControlGraphIri = $config->ac->graphIri;
		if (isset($config->auth->foafssl->idp->serviceUrl)) {
			$this->idpServiceUrl = $config->auth->foafssl->idp->serviceUrl;
		}
		if (isset($config->auth->foafssl->idp->verifyTimestamp)) {
			$this->verifyTimestamp = (bool)$config->auth->foafssl->idp->verifyTimestamp;
		}
		if (isset($config->auth->foafssl->idp->verifySignature)) {
			$this->verifySignature = (bool)$config->auth->foafssl->idp->verifySignature;

			if (isset($config->auth->foafssl->idp->publicKey)) {
				$this->publicKey = $config->auth->foafssl->idp->publicKey;
			}
		}

		// load IRIs from config
		$this->iris = array(
			'user_class' => $config->ac->user->class,
			'user_username' => $config->ac->user->name,
			'user_password' => $config->ac->user->pass,
			'user_mail' => $config->ac->user->mail,
			'user_superadmin' => $config->ac->user->superAdmin,
			'user_anonymous' => $config->ac->user->anonymousUser,
			'action_deny' => $config->ac->action->deny,
			'action_login' => $config->ac->action->login,
			'group_membership' => $config->ac->group->membership
		);
	}

	// ------------------------------------------------------------------------
	// --- Static methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Whether creation of self signed certificates is supported or not.
	 *
	 * @return bool
	 */
	public static function canCreateCertificates() {
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on' && extension_loaded('openssl')) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Creates a certificate if possible. Returns cert data on success and false else.
	 *
	 * @param string $webId
	 * @param string $name
	 * @param string $mail
	 * @param string $spkac Browser generated challenge
	 *
	 * @return array|bool
	 */
	public static function createCertificate($webId, $name, $mail = '', $spkac = '') {
		if (!self::canCreateCertificates()) {
			return false;
		}

		// Set some temp filenames.
		$tmpDir = Erfurt_App::getInstance()->getTmpDir();
		if (!$tmpDir) {
			return false;
		}
		$uniqueFilename = uniqid();
		$spkacFilename = $tmpDir . '/' . $uniqueFilename . '.spkac';
		$certFilename = $tmpDir . '/' . $uniqueFilename . '.temp';

		// Configure for signing...
		$config = Erfurt_App::getInstance()->getConfig();
		$state = $config->auth->foafssl->provider->ca->state;
		$country = $config->auth->foafssl->provider->ca->country;
		$org = $config->auth->foafssl->provider->ca->org;

		// Prepate SPKAC
		$spkac = str_replace(str_split(" \t\n\r\0\x0B"), '', $spkac);
		$dn = 'SPKAC=' . $spkac;

		// Name needs to be set!
		$dn .= PHP_EOL . 'CN=' . $name;

		// Optional mail address...
		if ($mail !== '') {
			$dn .= PHP_EOL . 'emailAddress=' . $mail;
		}

		// Needs to be the same as in CA cert!
		$dn .= PHP_EOL . 'organizationName=' . $org;

		$dn .= PHP_EOL . 'stateOrProvinceName=' . $state;
		$dn .= PHP_EOL . 'countryName=' . $country;

		// Subject alternate name...
		$san = 'IRI:' . $webId;
		putenv('SAN=' . $san);

		$fhandle = fopen($spkacFilename, 'w');
		fwrite($fhandle, $dn);
		fclose($fhandle);

		$expiration = $config->auth->foafssl->provider->ca->expiration;
		$pw = $config->auth->foafssl->provider->ca->password;

		// Sign the cert...
		$null = `openssl ca -days $expiration -notext -batch -spkac $spkacFilename -out $certFilename -passin pass:$pw`;

		unlink($spkacFilename);
		putenv('SAN=""');

		if (filesize($certFilename) === 0) {
			return false;
		}

		$fhandle = fopen($certFilename, 'r');
		$certData = fread($fhandle, filesize($certFilename));
		fclose($fhandle);

		// Extract data from cert...
		$pubKey = `openssl x509 -inform DER -in $certFilename -pubkey -noout`;
		$rsaCertStruct = `echo "$pubKey" | openssl asn1parse -inform PEM -i`;
		$rsaCertFields = explode("\n", $rsaCertStruct);
		$rsaKeyOffset = explode(':', $rsaCertFields[4]);
		$rsaKeyOffset = trim($rsaKeyOffset[0]);

		$rsaKey = `echo "$pubKey" | openssl asn1parse -inform PEM -i -strparse $rsaKeyOffset`;

		$rsaKeys = explode("\n", $rsaKey);
		$modulus = explode(':', $rsaKeys[1]);
		$modulus = $modulus[3];
		$exponent = explode(':', $rsaKeys[2]);
		$exponent = $exponent[3];

		unlink($certFilename);

		return array(
			'certData' => $certData,
			'modulus' => strtolower($modulus),
			'exponent' => hexdec($exponent)
		);
	}

	/**
	 * Returns the certficate data of the given user cert if possible.
	 * Else returns false.
	 *
	 * @return array|bool
	 */
	public static function getCertificateInfo() {
		if (!self::canCreateCertificates()) {
			return false;
		}

		$instance = new self();

		$rsaPublicKey = $instance->_getCertRsaPublicKey();
		if ($rsaPublicKey === false) {
			return false;
		}
		$san = $instance->_getSubjectAlternativeNames();
		if ($san === false || !isset($san['iri'])) {
			return false;
		}

		$foafPublicKey = $instance->_getFoafRsaPublicKey($san['iri']);
		if ($foafPublicKey === false) {
			return array(
				'certPublicKey' => $rsaPublicKey,
				'webId' => $san['iri']
			);
		} else {
			return array(
				'certPublicKey' => $rsaPublicKey,
				'webId' => $san['iri'],
				'foafPublicKey' => $foafPublicKey
			);
		}
	}

	/**
	 * Returns FOAF data for a given FOAF IRI iff available.
	 * Returns false else.
	 *
	 * @param string $foafIri
	 * @return array|bool
	 *
	 */
	public static function getFoafData($foafIri) {
		$client = Erfurt_App::getInstance()->getHttpClient($foafIri, array(
																		  'maxredirects' => 3,
																		  'timeout' => 30
																	 ));

		$client->setHeaders('Accept', 'application/rdf+xml');
		$response = $client->request();
		if ($response->getStatus() === 200) {
			require_once 'Erfurt/Syntax/RdfParser.php';
			$parser = Erfurt_Syntax_RdfParser::rdfParserWithFormat('rdfxml');

			if ($idx = strrpos($foafIri, '#')) {
				$base = substr($foafIri, 0, $idx);
			} else {
				$base = $foafIri;
			}

			try {
				$result = $parser->parse($response->getBody(), Erfurt_Syntax_RdfParser::LOCATOR_DATASTRING, $base);
			}
			catch (Erfurt_Syntax_RdfParserException $e) {
				return false;
			}

			return $result;
		} else {
			return false;
		}
	}

	// ------------------------------------------------------------------------
	// --- Public methods -----------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Adds a new FOAF+SSL user...
	 *
	 * @param $webId
	 */
	public function addUser($webId) {
		$acGraphIri = $this->accessControlGraphIri;
		$store = $this->store;

		// Only add the user if allowed...
		if (!Erfurt_App::getInstance()->getAc()->isActionAllowed('RegisterNewUser')) {
			return false;
		}

		// Does user already exist?
		$users = Erfurt_App::getInstance()->getUsers();
		if (isset($users[$webId])) {
			return false;
		}

		$actionConfig = Erfurt_App::getInstance()->getActionConfig('RegisterNewUser');

		$foafData = $this->_getFoafData($webId);
		if (isset($foafData[$webId][\Erfurt\Vocabulary\Rdf::TYPE][0]['value'])) {
			if ($foafData[$webId][\Erfurt\Vocabulary\Rdf::TYPE][0]['value'] === 'http://xmlns.com/foaf/0.1/OnlineAccount' ||
				$foafData[$webId][\Erfurt\Vocabulary\Rdf::TYPE][0]['value'] === 'http://xmlns.com/foaf/0.1/Person') {

				// Look for label, email
				if (isset($foafData[$webId]['http://xmlns.com/foaf/0.1/mbox'][0]['value'])) {
					$email = $foafData[$webId]['http://xmlns.com/foaf/0.1/mbox'][0]['value'];
				}
				if (isset($foafData[$webId]['http://xmlns.com/foaf/0.1/name'][0]['value'])) {
					$label = $foafData[$webId]['http://xmlns.com/foaf/0.1/name'][0]['value'];
				} else {
					if (isset($foafData[$webId][\Erfurt\Vocabulary\Rdfs::LABEL][0]['value'])) {
						$label = $foafData[$webId]['http://xmlns.com/foaf/0.1/name'][0]['value'];
					}
				}
			}
		}

		// iri rdf:type sioc:User
		$store->addStatement(
			$acGraphIri,
			$webId,
			\Erfurt\Vocabulary\Rdf::TYPE,
			array(
				 'value' => $this->iris['user_class'],
				 'type' => 'iri'
			),
			false
		);

		if (!empty($email)) {
			// Check whether email already starts with mailto:
			if (substr($email, 0, 7) !== 'mailto:') {
				$email = 'mailto:' . $email;
			}

			// iri sioc:mailbox email
			$store->addStatement(
				$acGraphIri,
				$userIri,
				$this->configuration->ac->user->mail,
				array(
					 'value' => $email,
					 'type' => 'iri'
				),
				false
			);
		}

		if (!empty($label)) {
			// iri rdfs:label $label
			$store->addStatement(
				$acGraphIri,
				$userIri,
				\Erfurt\Vocabulary\Rdfs::LABEL,
				array(
					 'value' => $label,
					 'type' => 'literal'
				),
				false
			);
		}

		if (isset($actionConfig['defaultGroup'])) {
			$store->addStatement(
				$acGraphIri,
				$actionConfig['defaultGroup'],
				$this->iris['group_membership'],
				array(
					 'value' => $webId,
					 'type' => 'iri'
				),
				false
			);
		}

		return true;
	}

	/**
	 * @return Zend_Auth_Result
	 */
	public function authenticate() {
		if (null === $this->get) {
			// Check if we can get the webid by ourself (https+openssl)
			if ($this->_isSelfCheckPossible()) {
				$webId = $this->_getAndCheckWebId();

				if ($webId !== false) {
					// Auth...
					$userResult = $this->_checkWebId($webId);

					if ($userResult['userIri'] === false) {
						// Add the user automatically...
						$this->addUser($webId);
						$userResult = $this->_checkWebId($webId);
					}

					return $this->_getAuthResult($userResult);
				} else {
					// Corrupt result
					$msg = 'No valid WebId found.';
					$result = false;

					require_once 'Zend/Auth/Result.php';
					return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, null, array($msg));
				}
			} else {
				if (null === $this->idpServiceUrl) {
					// Currently we need an external service for that...
					$result = false;
					$msg = 'No IdP configured.';

					require_once 'Zend/Auth/Result.php';
					return new Zend_Auth_Result($result, null, array($msg));
				}

				// First we fetch the webid in a secure manner...
				$url = $this->idpServiceUrl . '?authreqissuer=' . urlencode($this->redirectUrl);
				header('Location: ' . $url);
				exit;
			}
		} else {
			// First we need to verify the idp result!
			if (!$this->verifyIdpResult($this->get)) {
				// Corrupt result
				$msg = $this->_getErrorMessage($this->get);
				$result = false;

				require_once 'Zend/Auth/Result.php';
				return new Zend_Auth_Result($result, null, array($msg));
			} else {
				// Result is OK, so we have a valid WebId now. We now know, that the user is really the user...
				// Now check against the local ac graph...
				// Auth...
				$webId = $this->get['webid'];
				$userResult = $this->_checkWebId($webId);

				if ($userResult['userIri'] === false) {
					// Add the user automatically...
					$this->addUser($webId);
					$userResult = $this->_checkWebId($webId);
				}

				return $this->_getAuthResult($userResult);
			}
		}
	}

	/**
	 * This method authenticates a user iri that is given via auth header FOAF+SSL.
	 * Therefore the requesting agent needs to be connected via SSL/TLS and the users
	 * FOAF needs to be connected to the agent...
	 *
	 * @return Zend_Auth_Result
	 */
	public function authenticateWithCredentials($credentials) {
		// Possible?
		if (!$this->_isSelfCheckPossible()) {
			// Corrupt result
			$msg = 'Not possible.';
			$result = false;

			require_once 'Zend/Auth/Result.php';
			return new Zend_Auth_Result($result, null, array($msg));
		}

		// 2. Check the WebID (AgentID) of the requesting client...
		$agentId = $this->_getAndCheckWebId();
		if (!$agentId) {
			// Corrupt result
			$msg = 'Not possible.';
			$result = false;

			require_once 'Zend/Auth/Result.php';
			return new Zend_Auth_Result($result, null, array($msg));
		}

		// Now we now that the requesting client is really the client...
		// No check, whether user from credentials delegated access to agent
		$userId = $credentials[1];
		$foafData = $this->_getFoafData($userId);
		$allows = false;
		if (isset($foafData[$userId]['http://ns.ontowiki.net/SysOnt/delegatesAccess'])) {
			foreach ($foafData['http://ns.ontowiki.net/SysOnt/delegatesAccess'] as $oArray) {
				if ($oArray['value'] === $agentId) {
					$allows = true;
					break;
				}
			}
		} else {
			// Corrupt result
			$msg = 'Not possible.';
			$result = false;

			require_once 'Zend/Auth/Result.php';
			return new Zend_Auth_Result($result, null, array($msg));
		}

		// If allows is true, the user allows the agent to authenticate as him...
		if ($allows === true) {
			$userResult = $this->_checkWebId($userId);
			return $this->_getAuthResult($userResult);
		} else {
			// Corrupt result
			$msg = 'Not possible.';
			$result = false;

			require_once 'Zend/Auth/Result.php';
			return new Zend_Auth_Result($result, null, array($msg));
		}
	}

	/**
	 * Starts a request to a idp...
	 */
	public function fetchWebId() {
		// First we fetch the webid in a secure manner...
		$url = $this->idpServiceUrl . '?authreqissuer=' . urlencode($this->redirectUrl);

		header('Location: ' . $url);
		exit;
	}

	/**
	 * Verifies a idp result...
	 *
	 * @param array $get
	 *
	 * @return bool
	 */
	public function verifyIdpResult($get) {
		if (isset($get['webid']) && isset($get['ts']) && isset($get['sig'])) {
			$webId = $get['webid'];
			$ts = strtotime($get['ts']);
			$sig = $get['sig'];

			// TODO How to verify that in the right way? (time diffs between local server and remote server)
			if ($this->verifyTimestamp) {
				if ((time() - $ts) > self::TIMESTAMP_VALIDITY) {
					return false;
				}
			}

			// TODO Does not work yet...?!
			if ($this->verifySignature) {
				if ((null === $this->publicKey) || !extension_loaded('openssl')) {
					return false;
				}
				//$this->_publicKey = str_replace(str_split(" \t\n\r\0\x0B"), '', $this->_publicKey);
				$schema = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'])) ? 'https://' : 'http://';
				$url = $schema . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_IRI'];

				$data = substr($url, 0, strpos($url, '&sig='));
				$publicKeyId = openssl_pkey_get_public($this->publicKey);
				$result = openssl_verify($data, $sig, $publicKeyId);
				openssl_free_key($publicKeyId);

				if ($result != 1) {
					return false;
				}
			}

			return true;
		} else {
			return false;
		}
	}

	// ------------------------------------------------------------------------
	// --- Protected methods --------------------------------------------------
	// ------------------------------------------------------------------------

	/**
	 * Checks the local database, whether user exists
	 */
	protected function _checkWebId($webId) {
		$retVal = array(
			'userIri' => false,
			'denyLogin' => false
		);

		// Query the store.
		require_once 'Erfurt/Sparql/SimpleQuery.php';
		$query = new Erfurt_Sparql_SimpleQuery();
		$query->setProloguePart('SELECT ?s ?p ?o');
		$query->addFrom($this->accessControlGraphIri);
		$where = 'WHERE {
                            ?s ?p ?o .
                            ?s <' . \Erfurt\Vocabulary\Rdf::TYPE . '> <' . $this->iris['user_class'] . "> .
                            FILTER (sameTerm(?s, <$webId>))
                        }";
		$query->setWherePart($where);
		$result = $this->store->sparqlQuery($query, array('use_ac' => false));

		foreach ((array)$result as $row) {
			// Set user IRI
			if (($retVal['userIri']) === false) {
				$retVal['userIri'] = $row['s'];
			}

			// Check predicates, whether needed.
			switch ($row['p']) {
				case $this->iris['action_deny']:
					// if login is disallowed
					if ($row['o'] === $this->iris['action_login']) {
						$retVal['denyLogin'] = true;
					}
					break;
				case \Erfurt\Vocabulary\Rdfs::LABEL:
					$retVal['userLabel'] = $row['o'];
					break;
				case $this->iris['user_username']:
					$retVal['username'] = $row['o'];
					break;
				case $this->iris['user_mail'];
					$retVal['email'] = $row['o'];
				default:
					// Ignore all other statements.
			}
		}

		return $retVal;
	}

	/**
	 * Checks, whether a client cert is given and valid. If cert is given, it
	 * checks the WebID... If everything wents ok the WebID is returned, else
	 * false is returned.
	 *
	 * @return string|bool
	 */
	protected function _getAndCheckWebId() {
		if (!(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on' && extension_loaded('openssl'))) {
			// Wrong configuration.
			return false;
		}

		if (!isset($_SERVER['SSL_CLIENT_CERT'])) {
			// No client certificate exists or no client certificate populated by server.
			return false;
		}

		// Extract the public key of the cert...
		$certRsaPublicKey = $this->_getCertRsaPublicKey();
		if (!$certRsaPublicKey) {
			// Certificate contains no RSA public key.
			return false;
		}

		// Extract the subject alternate name of the cert (IRI)
		$subjectAlternateNames = $this->_getSubjectAlternativeNames();
		if (!$subjectAlternateNames) {
			// Certificate contains subject alternate name.
			return false;
		}

		$foafRsaPublicKey = $this->_getFoafRsaPublicKey($subjectAlternateNames['iri']);
		if (!$foafRsaPublicKey) {
			return false;
		}

		// Now compare the two keys...
		if ((int)$certRsaPublicKey['exponent'] === (int)$foafRsaPublicKey['exponent'] &&
			$certRsaPublicKey['modulus'] === $foafRsaPublicKey['modulus']) {

			return $subjectAlternateNames['iri'];
		} else {
			return false;
		}
	}

	/**
	 * Checks the result from the SPARQL query and returns an appropriate result.
	 *
	 * @param array $userResult
	 *
	 * @return Zend_Auth_Result
	 */
	protected function _getAuthResult($userResult) {
		if ($userResult['userIri'] === false) {
			$result = false;
			$msg = 'User does not exist!';

			require_once 'Zend/Auth/Result.php';
			return new Zend_Auth_Result($result, null, array($msg));
		}
		if ($userResult['denyLogin'] === true) {
			$result = false;
			$msg = 'Login not allowed!';

			require_once 'Zend/Auth/Result.php';
			return new Zend_Auth_Result($result, null, array($msg));
		}

		// Create the identity object and return it...
		$identity = array(
			'iri' => $userResult['userIri'],
			'dbuser' => false,
			'anonymous' => false,
			'is_webid_user' => true
		);

		if (isset($userResult['userLabel'])) {
			$identity['label'] = $userResult['userLabel'];
		}

		if (isset($userResult['username'])) {
			$identity['username'] = $userResult['username'];
		}

		if (isset($userResult['email'])) {
			$identity['email'] = $userResult['email'];
		}

		require_once 'Erfurt/Auth/Identity.php';
		$identityObject = new Erfurt_Auth_Identity($identity);

		require_once 'Zend/Auth/Result.php';
		return new Zend_Auth_Result(true, $identityObject, array());
	}

	/**
	 * Returns the public key of the client cert on success.
	 *
	 * @return array|bool
	 */
	protected function _getCertRsaPublicKey() {
		if (isset($_SERVER['SSL_CLIENT_CERT']) && !empty($_SERVER['SSL_CLIENT_CERT'])) {
			$publicKey = openssl_pkey_get_public($_SERVER['SSL_CLIENT_CERT']);
			$keyDetails = openssl_pkey_get_details($publicKey);

			$rsaCert = $keyDetails['key'];
			$rsaCertStruct = `echo "$rsaCert" | openssl asn1parse -inform PEM -i`;
			$rsaCertFields = explode("\n", $rsaCertStruct);
			$rsaKeyOffset = explode(':', $rsaCertFields[4]);
			$rsaKeyOffset = trim($rsaKeyOffset[0]);

			$rsaKey = `echo "$rsaCert" | openssl asn1parse -inform PEM -i -strparse $rsaKeyOffset`;

			$rsaKeys = explode("\n", $rsaKey);
			$modulus = explode(':', $rsaKeys[1]);
			$modulus = $modulus[3];
			$exponent = explode(':', $rsaKeys[2]);
			$exponent = $exponent[3];

			return array(
				'exponent' => strtolower($exponent),
				'modulus' => strtolower($modulus)
			);
		} else {
			return false;
		}
	}

	/**
	 * Returns an approriate error message for a negative idp result.
	 *
	 * @param array $get
	 *
	 * @return string
	 */
	protected function _getErrorMessage($get) {
		if (isset($get['error'])) {
			$error = $get['error'];

			if ($error === 'nocert') {
				return 'No valid certificate was found.';
			} else {
				if ($error === 'IdPError') {
					return 'The IdP returned an unknown error.';
				}
			}
		}

		return 'Something went wrong.';
	}

	/**
	 * Returns the FOAF data for a given IRI.
	 *
	 * @return array|false
	 */
	protected function _getFoafData($foafIri) {
		if (!isset($this->foafData[$foafIri])) {
			$this->foafData[$foafIri] = self::getFoafData($foafIri);
		}

		return $this->foafData[$foafIri];
	}

	/**
	 * Returns the public key in the foaf data on success.
	 *
	 * @return array|bool
	 */
	protected function _getFoafRsaPublicKey($foafIri) {
		$foafData = $this->_getFoafData($foafIri);
		if ($foafData === false) {
			return false;
		}

		$pubKeyId = null;
		foreach ($foafData as $s => $pArray) {
			foreach ($pArray as $p => $oArray) {
				if ($p === \Erfurt\Vocabulary\Rdf::TYPE) {
					foreach ($oArray as $o) {
						if ($o['type'] === 'iri' && $o['value'] === self::PUBLIC_KEY_PROPERTY) {
							// This is a public key... Now check whether it belongs to the iri...
							if (isset($foafData[$s][self::IDENTITY_PROP])) {
								$values = $foafData[$s][self::IDENTITY_PROP];
								foreach ($values as $v) {
									if ($v['type'] === 'iri' && $v['value'] === $foafIri) {
										// Match... We can use this Key and stop searching
										$pubKeyId = $s;
										break 4;
									}
								}
							}
						}
					}
				}
			}
		}

		if (isset($foafData[$pubKeyId][self::EXPONENT_PROP][0]['value'])) {
			$exponentId = $foafData[$pubKeyId][self::EXPONENT_PROP][0]['value'];
		} else {
			return false;
		}
		if (isset($foafData[$pubKeyId][self::MODULUS_PROP][0]['value'])) {
			$modulusId = $foafData[$pubKeyId][self::MODULUS_PROP][0]['value'];
		}

		$exponent = $foafData[$exponentId][self::DECIMAL_PROP][0]['value'];
		$modulus = $foafData[$modulusId][self::HEX_PROP][0]['value'];

		return array(
			'exponent' => dechex($exponent),
			'modulus' => strtolower($modulus)
		);
	}

	/**
	 * Returns a list of subject alternative names of a given cert on success.
	 *
	 * @return array|bool
	 */
	protected function _getSubjectAlternativeNames() {
		if (isset($_SERVER['SSL_CLIENT_CERT']) && !empty($_SERVER['SSL_CLIENT_CERT'])) {
			$x509Cert = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT']);

			if (isset($x509Cert['extensions']['subjectAltName'])) {
				$iriList = explode(',', $x509Cert['extensions']['subjectAltName']);
				$retVal = array();

				foreach ($iriList as $iri) {
					$key = strtolower(trim(substr($iri, 0, strpos($iri, ':'))));
					$val = trim(substr($iri, strpos($iri, ':') + 1));
					$retVal[$key] = $val;
				}

				return $retVal;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Can we check client certs ourself? (Needs SSL/TLS)
	 *
	 * @return bool
	 */
	protected function _isSelfCheckPossible() {
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on' && extension_loaded('openssl')) {
			return true;
		} else {
			return false;
		}
	}

}

?>