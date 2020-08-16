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

/**
 * Validate the OAuth request
 */
class Validator {

  /**
   * Data supplied from the Authorization header
   * @var array
   */
  private $_auth_data;

  /**
   * The request URI
   * @var string
   */
  private $_uri;

  /**
   * The request method
   * @var string
   */
  private $_method;

  /**
   * The request input
   * @var array
   */
  private $_input;

  /**
   * Constructor
   *
   * @param string $auth_header
   * @param string $uri
   * @param string $method
   * @param array $input
   */
  public function __construct(
    string $auth_header,
    string $uri,
    string $method,
    array $input
  ) {
    $this->_parseAuthHeader($auth_header);
    $this->_uri = $uri;
    $this->_method = $method;
    $this->_input = $input;
  }

  /**
   * Parse the Authorization header into an array of data
   *
   * @param string $header
   */
  private function _parseAuthHeader(string $header) {
    $auth = explode(' ', $header, 2);
    $data = $auth[1] ?? '';

    $this->_auth_data = [];
    foreach (explode(',', $data) as $value) {
      list ($k, $v) = explode('=', $value, 2);
      $this->_auth_data[$k] = $v;
    }
  }

  /**
   * Get the data specified
   *
   * @param string $name Key of data to return
   * @return string The value at requested key
   */
  public function getData(string $name) : string {
    return (isset($this->_auth_data[$name])) ? $this->_auth_data[$name] : '';
  }

  /**
   * Is the signature valid?
   *
   * @param Credentials $credentials
   * @return boolean
   */
  public function isValidSignature(Credentials $credentials) : bool {
    $signature = Signature::factory($this->getData('scheme'), $credentials);
    $sig = $signature->sign(
      $this->_uri,
      $this->_method,
      $this->_input,
      $this->_auth_data
    );

    $valid = $sig === $this->getData('mac');
    if ($valid || ! empty($this->_input)) {
      return $valid;
    }

    // If 1st attempt wasn't valid, try with trailing `?` if there is no input
    $sig = $signature->sign(
      "{$this->_uri}?",
      $this->_method,
      $this->_input,
      $this->_auth_data
    );
    return $sig === $this->getData('mac');
  }
}
