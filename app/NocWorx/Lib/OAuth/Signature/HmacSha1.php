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

use App\NocWorx\Lib\Arr;
use App\NocWorx\Lib\Str;

/**
 * HMAC-SHA1 OAuth signature
 */
class HmacSha1 extends Signature {

  /**
   * Create the signature
   *
   * @param string $uri
   * @param string $method The request method
   * @param array $data
   * @param array $auth_data
   *
   * @return string
   */
  public function sign(
    string $uri,
    string $method,
    array $data,
    array $auth_data = []
  ) : string {
    $values = [
      $auth_data['ts'],
      $auth_data['nonce'],
      Str::upperCase($method),
      $this->_getRequestUri($uri, $data)
    ];

    $base = implode("\n", $values);
    return base64_encode($this->_hash($base));
  }

  /**
   * Get the full, encoded URI
   *
   * @param string $uri
   * @param array $data
   *
   * @return string
   */
  protected function _getRequestUri(string $uri, array $data) : string {
    $params = $this->_getParameters($data);
    return (empty($params)) ?
      $uri :
      "{$uri}?{$params}";
  }

  /**
   * Get the parameters for making the signature
   *
   * @param array $data
   * @return string
   */
  protected function _getParameters(array $data) : string {
    $out = [];
    foreach (Arr::keyFlatten($data) as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          $new_key = "{$key}[{$k}]";
          $out[$new_key] = $this->_encodeParameterPairs($new_key, $v);
        }
      } else {
        $out[$key] = $this->_encodeParameterPairs($key, $value);
      }
    }

    ksort($out);
    return implode('&', $out);
  }

  /**
   * Encode the key value pair
   *
   * @param string $key
   * @param mixed $value scalar value
   * @return string
   */
  protected function _encodeParameterPairs(string $key, $value) : string {
    return "{$this->_encodeValue($key)}={$this->_encodeValue($value)}";
  }

  /**
   * Hash the given value
   *
   * @param string $value
   * @return string
   */
  protected function _hash(string $value) : string {
    return hash_hmac('sha1', $value, $this->_getKey(), true);
  }
}
