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

/**
 * This class represents the user's credentials (the API keys)
 */
class Credentials {

  /**
   * Public
   * @var string
   */
  protected $_identifier;

  /**
   * Private
   * @var string
   */
  protected $_secret;

  /**
   * Constructor
   *
   * @param string $identifier
   * @param string $secret
   */
  public function __construct(string $identifier, string $secret = '') {
    $this->_identifier = $identifier;
    $this->_secret = $secret;
  }

  /**
   * Get the identifier
   *
   * @return string
   */
  public function getIdentifier() : string {
    return $this->_identifier;
  }

  /**
   * Get the secret key
   *
   * @return string
   */
  public function getSecret() : string {
    return $this->_secret;
  }
}
