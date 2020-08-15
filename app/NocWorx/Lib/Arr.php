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
 * Array helper class.
 *
 * @package    NocWorx
 * @subpackage Lib
 */
final class Arr {

  /**
   * Grab the 'column' from the array. What this means is look at
   * the 1st dimension of the array and accumulate the values of
   * the dimension with name = $col into a new array as its values
   * and return.
   *
   * @param array  $array
   * @param string $col
   * @return array
   */
  public static function getColumn(array $array, string $col) : array {
    // @todo - I think this is just 'array_column' (native)

    $ret = [];

    foreach ($array as $entry) {
      if (isset($entry[ $col ])) {
        $ret[] = $entry[ $col ];
      }
    }

    return $ret;
  }

  /**
   * Rekey the given array so it has consecutive numeric keys starting at zero
   *
   * @param array $array
   * @return array
   */
  public static function reKey(array $array) : array {
    return array_values($array);
  }

  /**
   * Flatten a multidimensional array
   *
   * @param array $array
   * @return array
   */
  public static function flatten(array $array) : array {
    $flat_array = [];

    array_walk_recursive(
      $array,
      function ($el) use (&$flat_array) {
        array_push($flat_array, $el);
      }
    );

    return $flat_array;
  }

  /**
   * Flatten a multidimensional array by keys
   *
   * Meant to construct how you might see HTML array input names
   * In: ['a' => ['b' => 1, 'c' => ['d' => 2]]]
   * Out: ['a[b]' => 1, 'a[c][d]' => 2]
   *
   * @param array $array
   * @param string|null $container
   * @return array
   */
  public static function keyFlatten(
    array $array,
    string $container = null
  ) : array {
    $flat_array = [];

    foreach ($array as $k => $v) {
      if ( ! is_null($container)) {
        $k = "{$container}[{$k}]";
      }

      if (! is_array($v) || ! self::isAssoc($v)) {
        $flat_array[$k] = $v;
      } else {
        $flat_array = array_merge($flat_array, self::keyFlatten($v, $k));
      }
    }

    return $flat_array;
  }

  /**
   * Removes all empty string or null values recursively
   * Also removes any values which are an empty array
   * Preserves keys
   *
   * @param array $array
   * @return array
   */
  public static function removeBlanks(array $array) : array {

    foreach ($array as $k => &$v) {
      if (is_array($v)) {
        $v = self::removeBlanks($v);
      }
      if ([] === $v ||
          '' === $v ||
          null === $v ) {
        unset($array[ $k ]);
      }
    }

    return $array;
  }

  /**
   * Trim each element of an array recursively
   *
   * @param mixed $val The array or element (as this is called recursively)
   * @return string|array
   */
  public static function trim($val) {

    if (! is_array($val)) {
      return trim($val);
    }

    return array_map([ 'self', 'trim' ], $val);
  }

  /**
   * Wrapper for array_map. This allows easier unit-testing. Without this,
   * the actual hard-coded callback will be called each item.
   *
   * @see \array_map
   * @param array $callback
   * @param array $arrays Can be given multiple arrays
   * @return array
   */
  public static function map(callable $callback, array ...$arrays) : array {
    return array_map($callback, ...$arrays);
  }

  /**
   * Is the given array associative?
   *
   * @param array $arr
   * @return boolean
   */
  public static function isAssoc(array $arr) : bool {
    return count(array_filter(array_keys($arr), 'is_string')) > 0;
  }

  /**
   * Return an array containing only the whitelisted keys
   *
   * @param array $arr
   * @param array $whitelist
   * @return array
   */
  public static function whitelist(array $arr, array $whitelist) : array {
    return array_intersect_key($arr, array_flip($whitelist));
  }

  /**
   * Delete all occurances of value from the array
   *
   * @param array $arr
   * @param mixed $v
   * @return array
   */
  public static function deleteValue(array $arr, $v) : array {
    while (($key = array_search($v, $arr)) !== false) {
      unset($arr[ $key ]);
    }

    return $arr;
  }

  /**
   * Return a random value from the array (works with assoc arrays too).
   * @todo remove null return here
   *
   * @param array $arr
   * @return mixed|null
   */
  public static function randValue(array $arr) {
    if (count($arr) === 0) {
      return null;
    }

    $keys = array_keys($arr);

    // Array keys are zero-based.
    $count = count($keys) - 1;

    return $arr[$keys[Random::getInt(0, $count)]];
  }

  /**
   * Extends an array by recursively merging array values,
   * and replacing non-array values.
   * @see <https://gist.github.com/adrian-enspired/e766b37334130ea04eaf>
   *
   * @param array $arr The subject array
   * @param array ...$others One or more arrays to extend the subject array
   * @return array An extended array
   */
  public static function extendRecursive(
    array $arr,
    array ...$others
  ) : array {
    foreach ($others as $other) {
      foreach ($other as $k => $v) {
        if (is_int($k)) {
          $arr[] = $v;
        } elseif (isset($arr[$k]) && is_array($arr[$k]) && is_array($v)) {
          $arr[$k] = self::extendRecursive($arr[$k], $v);
        } else {
          $arr[$k] = $v;
        }
      }
    }
    return $arr;
  }

  /**
   * Is the array 'a' a subset of the array 'b'?
   *
   * @param array $a - array to test
   * @param array $b - array to check subset of
   * @return bool
   */
  public static function isSubset(array $a, array $b) : bool {
    if (empty($a)) {
      return false;
    }

    foreach ($a as $test) {
      if (! in_array($test, $b)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Looks up a value at given path in an array subject
   *
   * @param array $subject The subject
   * @param string $path Delimited path of keys to follow
   * @param string $delimiter
   * @return mixed The value at the given path if it exists; null otherwise
   */
  public static function dig(
    array $subject,
    string $path,
    string $delimiter = '.'
  ) {
    foreach (explode($delimiter, $path) as $key) {
      if (! isset($subject[$key])) {
        return null;
      }
      $subject = $subject[$key];
    }

    return $subject;
  }

  /**
   * Set a value at a given path in an array subject
   *
   * @param array $subject The subject
   * @param string $path Delimited path of keys to follow
   * @param mixed $value
   * @param string $delimiter
   *
   * @return array A new array with the set value
   */
  public static function bury(
    array $subject,
    string $path,
    $value,
    string $delimiter = '.'
  ) : array {
    $keys = explode($delimiter, $path);
    $key = array_shift($keys);

    if (! empty($keys)) {
      if (! isset($subject[$key])) {
        $subject[$key] = [];
      }
      if (! is_array($subject[$key])) {
        throw new Exception('##LG_INVALID_PATH##');
      }

      $value = self::bury($subject[$key], implode('.', $keys), $value);
    }

    $subject[$key] = $value;
    return $subject;
  }
}
