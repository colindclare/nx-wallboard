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

namespace App\NocWorx\Lib;

/**
 * Helper functions for strings
 *
 * @package    NocWorx
 * @subpackage Lib
 */
final class Str {

  const CASE_UPPER = 'upper';
  const CASE_LOWER = 'lower';
  const CASE_TITLE = 'title';

  const FORMAT_SPEC_START    = '%';
  const FORMAT_SPEC_RX       = "%.*?(?<!')[\\?bcdeEufFgGosxX]";
  const FORMAT_SPEC_NAMED_RX = '(?<=%)\\([a-z_]\\w*?\\)';
  const FORMAT_ESCAPE        = '%%';
  const FORMAT_ESCAPE_TEMP   = '@@__FORMAT_ESCAPE_TEMP__@@';
  const FORMAT_TYPE_STRING   = 's';
  const FORMAT_TYPE_INT      = 'd';
  const FORMAT_TYPE_FLOAT    = 'f';
  const FORMAT_TYPE_DBIDENT  = '?';

  /**
   * @var string A "blank" UUID
   */
  const NIL_UUID = '00000000-0000-0000-0000-000000000000';

  /**
   * Determines if a string contains only ASCII characters
   *
   * @param string $str
   * @return boolean
   */
  private static function _isAscii($str) {
    return 'ASCII' == mb_detect_encoding($str);
  }

  /**
   * Determines the number of characters in a string
   *
   * @param string $str
   *
   * @return integer
   */
  public static function strlen(string $str) : int {
    if (self::_isAscii($str)) {
      $len = strlen($str);
    } elseif (false === ($len = mb_strlen($str, 'UTF-8'))) {
      throw new Exception();
    }

    return $len;
  }

  /**
   * Get the byte length of the given string
   *
   * @param string $str
   * @return integer
   */
  public static function bytelen(string $str) : int {
    if (false === ($len = mb_strlen($str, '8bit'))) {
      throw new Exception();
    }

    return $len;
  }

  /**
   * Performs case folding on a string
   *
   * @param string $str
   * @param string $mode
   *
   * @return string
   */
  private static function _convertCase(string $str, string $mode) : string {
    if (self::_isAscii($str)) {
      switch ($mode) {
        case self::CASE_UPPER:
          return strtoupper($str);
        case self::CASE_LOWER:
          return strtolower($str);
        case self::CASE_TITLE:
          return ucwords(strtolower($str));
        default:
          return $str;
      }
    }

    switch ($mode) {
      case self::CASE_UPPER:
        $mb_mode = MB_CASE_UPPER;
        break;
      case self::CASE_LOWER:
        $mb_mode = MB_CASE_LOWER;
        break;
      case self::CASE_TITLE:
        $mb_mode = MB_CASE_TITLE;
        break;
      default:
        return $str;
    }
    return mb_convert_case($str, $mb_mode, 'UTF-8');
  }

  /**
   * Convert a string to lower case
   *
   * @param string $str
   * @return string
   */
  public static function lowerCase(string $str) : string {
    return self::_convertCase($str, self::CASE_LOWER);
  }

  /**
   * Convert a string to upper case
   *
   * @param string $str
   * @return string
   */
  public static function upperCase(string $str) : string {
    return self::_convertCase($str, self::CASE_UPPER);
  }

  /**
   * Convert a string to title case
   *
   * @param string $str
   * @return string
   */
  public static function titleCase(string $str) : string {
    return self::_convertCase($str, self::CASE_TITLE);
  }

  /**
   * Sub string
   *
   * @param string  $str
   * @param integer $start
   * @param integer|null $length
   *
   * @return string
   */
  public static function substr(
    string $str,
    int $start,
    int $length = null
  ) : string {
    $length = is_null($length) ? self::strlen($str) : $length;

    if (self::_isAscii($str)) {
      return substr($str, $start, $length);
    }

    return mb_substr($str, $start, $length, 'UTF-8');
  }

  /**
   * Wraps a string to a given number of characters
   *
   * @param string  $str
   * @param integer $width
   * @param string  $break
   * @param boolean $cut
   *
   * @return string
   */
  public static function wordwrap(
    string $str,
    int $width = 75,
    string $break = "\n",
    bool $cut = false
  ) : string {

    if (self::_isAscii($str)) {
      return wordwrap($str, $width, $break, $cut);
    }

    $new = $str;
    $out = '';

    while ('' != $new) {
      if (self::strlen($new) <= $width) {
        $out .= $new;
        break;
      }

      // Find last whitespace in the first $width characters
      preg_match(
        '(^(.{1,' . $width . '})(' . Validate::RX_C_WHITESPACE . '))u',
        $new,
        $matches
      );

      // If found, put $break there
      if (isset($matches[2])) {
        $out .= $matches[1] . $break;
        $len  = self::strlen($matches[1]) + 1;
      } else {
        if ($cut) {
          // We're to cut the word anyway
          // Put $break at $width character
          $out .= self::substr($new, 0, $width) . $break;
          $len  = $width;
        } else {
          // Put $break at first whitespace if any
          preg_match(
            '(^(.+)(' . Validate::RX_C_WHITESPACE . '))u',
            $new,
            $matches
          );
          if (isset($matches[2])) {
            $out .= $matches[1] . $break;
            $len  = self::strlen($matches[1]) + 1;
          } else {
            $out .= $new;
            $len  = self::strlen($new);
          }
        }
      }

      // Remove the portion we've added to $out and loop
      $new = self::substr($new, $len);
    }

    return $out;
  }

  /**
   * Converts an_underscored_string to a CamelCaseString.
   *
   * Example: this_is_the_string => ThisIsTheString
   * Example: This_Is_The_String => ThisIsTheString
   *
   * @param string $input The underscored string
   * @return string The camel-cased output
   */
  public static function underscoreToCamel(string $input) : string {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
  }

  /**
   * Converts a-dashed-string to a CamelCaseString.
   *
   * Example: this-is-the-string => ThisIsTheString
   * Example: This-Is-The-String => ThisIsTheString
   *
   * @param string $input The dashed string
   * @return string The camel-cased output
   */
  public static function dashToCamel(string $input) : string {
    return str_replace(' ', '', ucwords(str_replace('-', ' ', $input)));
  }

  /**
   * Converts a CamelCasedString to lower_case_underscored.
   *
   * Example: ThisIsTheString => this_is_the_string
   *
   * @param string $input The camel-cased string
   * @return string The underscored output
   */
  public static function camelToUnderscore(string $input) : string {
    return strtolower(self::delimitCamel($input, '_'));
  }

  /**
   * Converts a CamelCasedString to lower-case-dashed.
   *
   * Example: ThisIsTheString => this-is-the-string
   *
   * @param string $input The camel-cased string
   * @return string The dashed output
   */
  public static function camelToDash(string $input) : string {
    return strtolower(self::delimitCamel($input, '-'));
  }

  /**
   * Converts an_underscored_string to a-dashed-string.
   *
   * @param string $input
   * @return string
   */
  public static function underscoreToDash(string $input) : string {
    return str_replace('_', '-', $input);
  }

  /**
   * Converts an_underscored_string to Camel_Case_Underscored.
   *
   * Example: this_is_the_string => This_Is_The_String
   *
   * @param string $input The underscored string
   * @return string The camel-case underscored output
   */
  public static function underscoreToCamelUnderscore(string $input) : string {
    return str_replace(' ', '_', ucwords(str_replace('_', ' ', $input)));
  }

  /**
   * Converts a-dashed-string to Camel_Case_Underscored.
   *
   * Example: this-is-the-string => This_Is_The_String
   *
   * @param string $input The dashed string
   * @return string The camel-case underscored output
   */
  public static function dashToCamelUnderscore(string $input) : string {
    return str_replace(' ', '_', ucwords(str_replace('-', ' ', $input)));
  }

  /**
   * Converts a CamelCaseString or a string separated by ' ', '-', or '_' to a
   * Camel_Case_Underscore_String.
   *
   * Each of the following would become 'The_Quick_Brown_Fox':
   * - the-quick-brown-fox
   * - the_quick_brown_fox
   * - TheQuickBrownFox
   * - The quick brown Fox
   * - the+quick*brown@fox
   * - The-quick BrownFox
   * - The_QuickBrown_Fox
   * - theQuick_brown-Fox
   *
   * @param string $input
   * @return string The camel-case underscored output
   */
  public static function toCamelUnderscore(string $input) : string {
    $input = self::delimitCamel($input); // delimit already camel-cased parts
    $input = preg_replace('/[_-]/', ' ', $input);
    return str_replace(' ', '_', ucwords(trim($input)));
  }

  /**
   * Converts a string separated by non-alphanumeric characters to a
   * CamelCaseString.
   *
   * Each of the following would become 'TheQuickBrownFox':
   * - the-quick-brown-fox
   * - the_quick_brown_fox
   * - TheQuickBrownFox
   * - the quick brown fox
   * - the+quick*brown@fox
   * - The-quick BrownFox
   * - The_QuickBrown_Fox
   * - theQuick_brown-Fox
   *
   * @param string $input
   * @return string The camel-cased output
   */
  public static function toCamel(string $input) : string {
    $input = self::delimitCamel($input); // delimit already camel-cased parts
    $input = strtolower(trim($input));
    return str_replace(' ', '', ucwords(preg_replace(
      '/[^a-z0-9]/i',
      ' ',
      $input
    )));
  }

  /**
   * Separates a CamelCaseString using the given delimiter. Case is unaffected.
   *
   * Examples (input => delim => output):
   * - TheQuickBrownFox   => ' '  => The Quick Brown Fox
   * - theQuick_BrownFox  => '-'  => the-Quick_Brown-Fox
   * - theQuick_BRownFox  => '-'  => the-Quick_B_Rown-Fox
   * - The_QuickBrown_Fox => '@'  => The_Quick@Brown_Fox
   * - The-quick BrownFox => '##' => The-quick Brown##Fox
   *
   * @param string $input
   * @param string $delim
   * @return string The delimited output
   */
  public static function delimitCamel(
    string $input,
    string $delim = ' '
  ) : string {
    $input = preg_replace('~([A-Z])([A-Z])~', "\$1{$delim}\$2", $input);
    $input = preg_replace('~([a-z0-9])([A-Z])~', "\$1{$delim}\$2", $input);

    return $input;
  }

  /**
   * Converts a string separated by non-alphanumeric characters to a
   * studlyCapsString.
   *
   * Each of the following would become 'theQuickBrownFox':
   * - the-quick-brown-fox
   * - the_quick_brown_fox
   * - the quick brown fox
   * - the+quick*brown@fox
   * - The_QuickBrown_Fox
   *
   * @param string $input
   * @return string The camel-cased output
   */
  public static function toStudly(string $input) : string {
    return lcfirst(self::toCamel($input));
  }

  /**
   * Converts a string separated by non-alphanumeric characters to a
   * mixedCaseString.
   *
   * Each of the following would become 'theQuickBrownFox':
   * - the-quick-brown-fox
   * - the_quick_brown_fox
   * - the quick brown fox
   * - the+quick*brown@fox
   * - The_QuickBrown_Fox
   *
   * @param string $input
   * @return string The camel-cased output
   */
  public static function toMixed(string $input) : string {
    return lcfirst(self::toCamel($input));
  }

  /**
   * Converts a string separated by non-alphanumeric characters to an
   * underscore_string. Case is unaffected.
   *
   * Each of the following would become 'The_quick_brown_Fox':
   * - The-quick-brown-Fox
   * - The_quick_brown_Fox (no change)
   * - The quick brown Fox
   * - The+quick*brown@Fox
   *
   * @param string $input
   * @return string The underscore separated output
   */
  public static function toUnderscore(string $input) : string {
    return str_replace(
      ' ',
      '_',
      preg_replace(
        '/[^a-z0-9]/i',
        ' ',
        trim($input)
      )
    );
  }

  /**
   * Converts a string separated by non-alphanumeric characters to a
   * dashed-string. Case is unaffected.
   *
   * Each of the following would become 'The-quick-brown-Fox':
   * - The-quick-brown-Fox (no change)
   * - The_quick_brown_Fox
   * - The quick brown Fox
   * - The+quick*brown@Fox
   *
   * @param string $input
   * @return string
   */
  public static function toDash(string $input) : string {
    return str_replace(
      ' ',
      '-',
      preg_replace(
        '/[^a-z0-9]/i',
        ' ',
        trim($input)
      )
    );
  }

  /**
   * Encodes an ISO-8859-1 string to UTF-8. If passed an arr, will recursively
   * encode any values (and optionally keys) that are strings. If input is not
   * a string or an array, it is returned unchanged.
   *
   * @param string|array $input
   * @param boolean      $encode_keys Default: false
   * @return array|string
   *
   * @see PHP_MANUAL#utf8_encode
   */
  public static function utf8Encode($input, bool $encode_keys = false) {
    if (is_array($input)) {
      $result = [];
      foreach ($input as $key => $value) {
        if ($encode_keys) {
          $key = self::utf8Encode($key);
        }
        $result[$key] = self::utf8Encode($value, $encode_keys);
      }
      return $result;
    }

    if (is_string($input) &&
        false === ( 'UTF-8' == mb_detect_encoding($input) &&
                    mb_check_encoding($input, 'UTF-8') ) ) {
      return utf8_encode($input);
    }

    return $input;
  }

  /**
   * Escape specific characters within a string with a given escape char.
   *
   * @param string $str
   * @param string $char_list
   * @param string $esc_char
   * @return string
   */
  public static function escape(
    string $str,
    string $char_list,
    string $esc_char = '\\'
  ) : string {
    foreach (str_split($char_list) as $c) {
      $str = str_replace($c, $esc_char . $c, $str);
    }
    return $str;
  }

  /**
   * sprintf
   *
   * @param string $format
   *               Accepts variable list of arguments
   * @return string
   */
  public static function sprintf(string $format) : string {
    return vsprintf($format, array_slice(func_get_args(), 1));
  }

  /**
   * Implementation of vsprintf that allows named arguments using the python
   * style of %(foo)s. In this case, the passed in array of arguments must have
   * a 'foo' key defined.
   *
   * See the comments for sprintf() at {@link PHP_MANUAL#sprintf}.
   *
   * @param string $format
   * @param array  $args
   * @return string
   */
  public static function vsprintf(string $format, array $args) : string {
    if (empty($args) || '' === trim($format)) {
      return $format;
    }

    // Temporarily remove escaped percent signs.
    $replace_pp = ( false !== strpos($format, self::FORMAT_ESCAPE) );
    if ($replace_pp) {
      $format = str_replace(
        self::FORMAT_ESCAPE,
        self::FORMAT_ESCAPE_TEMP,
        $format
      );
    }

    // Map each named argument to an ID number that will be used by the actual
    // vsprintf() function using the standard %1$s format.
    $spec_ids = [];
    $spec_id  = 0;
    foreach ($args as $key => $val) {
      ++$spec_id;
      if (is_string($key)) {
        // Example: '(foo)', if the first argument, will be replaced with '1$'
        // so that '%(foo)s' will become '%1$s'.
        $spec_ids["({$key})"] = "{$spec_id}$";
      }
    }

    // For each match, replace the named argument with the numeric ID.
    $pos = 0;
    while (1 === preg_match(
      '~' . self::FORMAT_SPEC_NAMED_RX . '~',
      $format,
      $m,
      PREG_OFFSET_CAPTURE,
      $pos
    ) ) {
      $spec_key = $m[0][0];

      if (false === isset($spec_ids[$spec_key])) {
        $msg = sprintf("Missing argument '%s'", trim($spec_key, '()'));
        throw new Exception($msg);
      }

      $key_pos = $m[0][1];
      $key_len = self::strlen($spec_key);
      $spec_id = $spec_ids[$spec_key];

      $format = substr_replace($format, $spec_id, $key_pos, $key_len);
      $pos = ( $key_pos + self::strlen($spec_id) );
    }

    // Restore any escaped percent signs.
    if ($replace_pp) {
      $format = str_replace(
        self::FORMAT_ESCAPE_TEMP,
        self::FORMAT_ESCAPE,
        $format
      );
    }

    return vsprintf($format, array_values($args));
  }

  /**
   * Explode a string by a given delimiter, then apply a callback to the
   * individual chunks, then implode back again with the same delimiter.
   *
   * @param callable $callback
   * @param string   $str
   * @param string   $delim
   *
   * @return string
   */
  public static function map(
    callable $callback,
    string $str,
    string $delim = ' '
  ) : string {
    return implode($delim, array_map($callback, explode($delim, $str)));
  }

  /**
   * Convert digits in a string to spelled out version
   * ( 1 => one, 15 => onefive )
   *
   * @param string $str
   * @return string
   */
  public static function toSpelledDigits(string $str) : string {
    return self::_regexSpelledNumber($str, '~([^0-9])|([0-9])~');
  }

  /**
   * Convert numeric values in a string to spelled out version
   * ( 1 => one, 15 => fifteen )
   *
   * @param string $str
   * @return string
   */
  public static function toSpelledNumeric(string $str) : string {
    return self::_regexSpelledNumber($str, '~([^0-9]+)|([0-9]+)~');
  }

  /**
   * Convert to spelled numerics from the given regex
   *
   * @param string $str
   * @param string $regex
   * @return string
   */
  private static function _regexSpelledNumber(
    string $str,
    string $regex
  ) : string {
    preg_match_all($regex, $str, $data);
    $res = [];
    foreach ($data[2] as $k => $v) {
      if ('' === $v) {
        $res[] = $data[1][$k];
      } else {
        $res[] = self::_numberToSpelled($v);
      }
    }
    return implode('', $res);
  }

  /**
   * Converts a numeric to spelled version
   *
   * @param integer $num
   * @param integer $mult
   * @return string
   */
  private static function _numberToSpelled(int $num, int $mult = 0) : string {
    static $first = [
      'zero',
      'one',
      'two',
      'three',
      'four',
      'five',
      'six',
      'seven',
      'eight',
      'nine',
      'ten',
      'eleven',
      'twelve',
      'thirteen',
      'fourteen',
      'fifteen',
      'sixteen',
      'seventeen',
      'eightteen',
      'nineteen'
    ];

    static $tens = [
      'twenty',
      'thirty',
      'fourty',
      'fifty',
      'sixty',
      'seventy',
      'eighty',
      'ninety'
    ];

    static $multipliers = [
      'thousand',
      'million',
      'billion',
      'trillion',
      'quadrillion',
      'quintillion',
      'sextillion',
      'septillion',
      'octillion',
      'nonillion'
    ];

    $out = [];

    if (0 == $num) {
      return $first[0];
    }
    if ($mult > count($multipliers)) {
      throw new Exception('Number too large');
    }

    preg_match(
      '~(.*)(\d)(\d\d)$~',
      str_pad(Cast::toString($num), 4, '0', STR_PAD_LEFT),
      $m
    );
    $r = $m[1];
    $h = intval($m[2]);
    $t = intval($m[3]);

    // Remaining
    if ($r > 0) {
      $arr = [self::_numberToSpelled($r, $mult + 1)];
      if ('000' != substr($r, -3)) {
        $arr[] = $multipliers[$mult];
      }
      $out[] = implode(' ', $arr);
    }

    // Hundreds
    if ($h > 0) {
      $out[] = "{$first[$h]} hundred";
    }

    // Tens
    if ($t > 0) {
      if ($t < 20) {
        $out[] = $first[$t];
      } else {
        $m      = str_split(Cast::toString($t));
        $k_tens = intval($m[0]);
        $k_ones = intval($m[1]);
        $arr    = [$tens[( $k_tens - 2 )]];
        if ($k_ones > 0) {
          $arr[] = $first[$k_ones];
        }
        $out[] = implode('-', $arr);
      }
    }

    return implode(' ', $out);
  }

  /**
   * Compare 2 strings for strict equality
   *
   * @param string $s1
   * @param string $s2
   * @return boolean
   */
  public static function equals(string $s1, string $s2) : bool {
    // @todo - make better but can't find the article atm
    return 0 === strcmp($s1, $s2);
  }
}
