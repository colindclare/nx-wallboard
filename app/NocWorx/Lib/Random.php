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

declare(strict_types = 1);

namespace App\NocWorx\Lib;

/**
 * Class to handle all random # generation
 *
 * @package    NocWorx
 * @subpackage Lib
 */
final class Random {

  /**
   * Numbers
   * @var string
   */
  const NUMBERS = '0123456789';

  /**
   * Lowercase chars
   * @var string
   */
  const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';

  /**
   * Uppercase chars
   * @var string
   */
  const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

  /**
   * Special chars
   * @var string
   */
  const SPECIAL = '!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';

  /**
   * Alphanum chars
   * @var string
   */
  const ALPHANUM = self::NUMBERS . self::LOWERCASE . self::UPPERCASE;

  /**
   * The default set of password chars
   * @var string
   */
  const CHARS = self::ALPHANUM . self::SPECIAL;

  /**
   * Get bytes from the random number generator.
   *
   * @param integer $l
   * @return string
   * @throws \NocWorx\Lib\Exception
   */
  public static function getBytes(int $l) : string {
    try {
      return \random_bytes($l);
    } catch (\Throwable $e) {
      throw Exception::from($e);
    }
  }

  /**
   * Gen a random integer between min and max
   *
   * @param integer $min
   * @param integer $max
   * @return integer
   * @throws \NocWorx\Lib\Exception
   */
  public static function getInt(
    int $min = PHP_INT_MIN,
    int $max = PHP_INT_MAX
  ) : int {
    try {
      return \random_int($min, $max);
    } catch (\Throwable $e) {
      throw Exception::from($e);
    }
  }

  /**
   * Get a random list of digits given the length
   *
   * @param integer $l
   * @return string
   */
  public static function getDigits(int $l) : string {
    return self::getString($l, self::NUMBERS);
  }

  /**
   * Get a random string
   *
   * @param integer $l
   * @param string  $clist
   * @return string
   * @throws \NocWorx\Lib\Exception
   */
  public static function getString(
    int $l,
    string $clist = self::CHARS
  ) : string {
    if (! is_string($clist) || Str::strlen($clist) == 0) {
      throw new Exception('##LG_VALID_CLIST_REQUIRED##');
    }

    try {
      $str = '';
      $max = Str::strlen($clist) - 1;

      for ($i = 0; $i < $l; $i++) {
        $str .= $clist[self::getInt(0, $max)];
      }

      return $str;
    } catch (\Throwable $e) {
      throw Exception::from($e);
    }
  }

  /**
   * Generates a RFC 4122 universally unique identifier (UUID)
   *
   * @return string
   */
  public static function generateUuid() : string {
    return Uuid::getRfc4122();
  }

  /**
   * Get a random array value out of the given array
   *
   * @param array
   * @return mixed
   */
  public static function getArrayValue(array $array) {
    return $array[array_rand($array)];
  }
}
