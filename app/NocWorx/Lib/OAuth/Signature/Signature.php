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

namespace App\NocWorx\Lib\OAuth\Signature;

use App\NocWorx\Lib\OAuth\Credentials;

/**
 * OAuth signature
 */
abstract class Signature {

  /**
   * @var Credentials
   */
  private $_credentials;

  /**
   * Constructor
   *
   * @param Credentials $credentials
   */
  public function __construct(Credentials $credentials) {
    $this->_credentials = $credentials;
  }

  /**
   * Create the signature
   *
   * @param string $uri
   * @param string $method The request method
   * @param array $data Data of the request
   * @param array $auth_data Additional data used for auth
   *
   * @return string
   */
  abstract public function sign(
    string $uri,
    string $method,
    array $data,
    array $auth_data = []
  ) : string;

  /**
   * Generate the requested Signature type
   *
   * @param string $method
   * @param Credentials $credentials
   *
   * @return Signature
   */
  public static function factory(
    string $method,
    Credentials $credentials
  ) : Signature {
    switch ($method) {
      default:
        return new HmacSha1($credentials);
    }
  }

  /**
   * Get the private key for signing
   *
   * @return string
   */
  protected function _getKey() : string {
    return $this->_credentials->getSecret();
  }

  /**
   * Encode the given value acording to OAuth specs
   *
   * @param string $value
   * @return string
   */
  protected function _encodeValue(string $value) : string {
    $value = strval($value);
    if (0 == strlen($value)) {
      return '';
    }
    if (0 === $value || '0' == $value) {
      return $value;
    }

    $value = urlencode($value);
    // fix urlencode of ~ and '+'
    return str_replace(['%7E', '+'], ['~', '%20'], $value);
  }
}
