<?php
/**
 * NOCWorx Datacenter Management Suite
 *
 * <pre>
 * +----------------------------------------------------------------------+
 * | NOCWorx                                                              |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 2006-2015 NOCWorx L.L.C., All Rights Reserved.         |
 * +----------------------------------------------------------------------+
 * | Redistribution and use in source form, with or without modification  |
 * | is NOT permitted without consent from the copyright holder.          |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND |
 * | ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,    |
 * | THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A          |
 * | PARTICULAR PURPOSE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,    |
 * | EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,  |
 * | PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR   |
 * | PROFITS; OF BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY  |
 * | OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT         |
 * | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE    |
 * | USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH     |
 * | DAMAGE.                                                              |
 * +----------------------------------------------------------------------+
 * </pre>
 */

declare(strict_types = 0);

namespace App\NocWorx\Lib\OAuth;

use App\NocWorx\Lib\OAuth\Signature\Signature;
use App\NocWorx\Lib\Str;
use App\NocWorx\Lib\Random;
use App\NocWorx\Lib\Exception;

/**
 * Make an API request using OAuth
 */
class Request {

  /**
   * MAC token type
   */
  const TOKEN_TYPE_MAC = 'MAC';

  /**
   * The host
   * e.g. http://example.com
   *
   * @var string
   */
  protected $_host;

  /**
   * The user's credentials
   *
   * @var Credentials
   */
  protected $_credentials;

  /**
   * Toekn type
   *
   * @var string
   */
  protected $_token_type = self::TOKEN_TYPE_MAC;

  /**
   * The hash algorithm for generating the OAuth signature
   *
   * @var string
   */
  protected $_signature_method = 'hmac-sha1';

  /**
   * Constructor
   *
   * @param string $host
   * @param Credentials $credentials
   */
  public function __construct(string $host, Credentials $credentials) {
    $this->_host = strval($host);
    $this->_credentials = $credentials;
  }

  /**
   * Send a GET request
   *
   * @param string $resource
   * @param array $data
   * @param array $headers
   *
   * @return string
   */
  public function get(
    string $resource,
    array $data = [],
    array $headers = []
  ) : string {
    return $this->sendRequest('GET', $resource, $data, $headers);
  }

  /**
   * Send a POST request
   *
   * @param string $resource
   * @param array $data
   * @param array $headers
   *
   * @return string
   */
  public function post(
    string $resource,
    array $data = [],
    array $headers = []
  ) : string {
    return $this->sendRequest('POST', $resource, $data, $headers);
  }

  /**
   * Send a PATCH request
   *
   * @param string $resource
   * @param array $data
   * @param array $headers
   *
   * @return string
   */
  public function patch(
    string $resource,
    array $data = [],
    array $headers = []
  ) : string {
    return $this->sendRequest('PATCH', $resource, $data, $headers);
  }

  /**
   * Send a DELETE request
   *
   * @param string $resource
   * @param array $data
   * @param array $headers
   *
   * @return string
   */
  public function delete(
    string $resource,
    array $data = [],
    array $headers = []
  ) : string {
    return $this->sendRequest('DELETE', $resource, $data, $headers);
  }

  /**
   * Send the request
   *
   * @param string $method
   * @param string $resource
   * @param array $data
   * @param array $headers
   * @param array $options
   *
   * @return string
   */
  public function sendRequest(
    string $method,
    string $resource,
    array $data = [],
    array $headers = [],
    array $options = []
  ) : string {
    $default_options = ['verify' => true];
    $options += $default_options;

    $method = Str::upperCase($method);
    $uri = $this->_getUri($resource);
    $query = http_build_query($data);
    $headers = $this->_prepareHeaders($headers, $method, $resource, $query);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if (false === $options['verify']) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }

    switch ($method) {
      case 'GET':
        if (! empty($query)) {
          $uri .= "?{$query}";
        }
        break;

      case 'POST':
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        break;

      default:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        break;
    }

    curl_setopt($curl, CURLOPT_URL, $uri);
    $result = curl_exec($curl);
    if (false === $result) {
      throw new Exception("##LG_CONNECTION_FAILURE##:\n" . curl_error($curl));
    }

    return $result;
  }

  /**
   * Prepare the headers for sending the request
   *
   * @param array $headers
   * @param string $method
   * @param string $resource
   * @param string $query
   *
   * @return array
   */
  protected function _prepareHeaders(
    array $headers,
    string $method,
    string $resource,
    string $query
  ) : array {
    if ($this->_token_type === 'MAC') {
      // convert the query string to an array of data
      parse_str($query, $data);

      $auth = $this->_getAuthorizationHeader($method, $resource, $data);
      $headers['Authorization'] = $auth;
      array_walk($headers, function (&$v, $k) {
        $v = "{$k}:{$v}";
      });
    }

    return $headers;
  }

  /**
   * Get the Authorization header value
   *
   * @param string $method
   * @param string $resource
   * @param array $data
   *
   * @return string
   */
  protected function _getAuthorizationHeader(
    string $method,
    string $resource,
    array $data
  ) : string {
    $id = $this->_credentials->getIdentifier();
    $ts = time();

    $nonce = Random::getString(32, Random::ALPHANUM);

    $auth = [
      'id' => $id,
      'ts' => $ts,
      'nonce' => $nonce,
      'scheme' => $this->_signature_method
    ];
    $auth['mac'] = $this->_getSignature($method, $resource, $data, $auth);

    array_walk($auth, function (&$v, $k) {
      $v = "{$k}={$v}";
    });

    return 'MAC ' . implode(',', $auth);
  }

  /**
   * Sign the request
   *
   * @param string $method
   * @param string $resource
   * @param array $data
   * @param array $auth_data
   *
   * @return string
   */
  protected function _getSignature(
    string $method,
    string $resource,
    array $data,
    array $auth_data
  ) : string {
    $sig = Signature::factory(
      $this->_signature_method,
      $this->_credentials
    );
    return $sig->sign($this->_getUri($resource), $method, $data, $auth_data);
  }

  /**
   * Get the request URI
   *
   * @param string $resource
   * @return string
   */
  protected function _getUri(string $resource) : string {
    $host = rtrim($this->_host, '/');
    $resource = ltrim($resource, '/');
    return implode('/', [$host, $resource]);
  }
}
