<?php
/**
 * @package    at.molly
 * @author     Adrian <adrian@enspi.red>
 * @copyright  2014 - 2016
 * @license    GPL-3.0 (only)
 *
 *  This program is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License, version 3.
 *  The right to apply the terms of later versions of the GPL is RESERVED.
 *
 *  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *  See the GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along with this program.
 *  If not, see <http://www.gnu.org/licenses/gpl-3.0.txt>.
 */
declare(strict_types = 1);

namespace at\molly;

use ArrayAccess,
    Iterator;
use at\molly\{
      Modelable,
      ModelableException
    };
use at\PRO\Regex;
use at\util\{
      Jsonable,
      Json,
      Vars
    };

/**
 * base class for domain models (data objects).
 *
 * most characteristics of the model are defined via four class constants:
 *  - VARS: names of literal properties.
 *  - KEYS: names of primary (identifiable) properties.
 *    objects with the same key values will be considered identical.
 *    if omitted, instances will be considered distinct, even if all property values are the same.
 *  - ENUM: names of literal and/or virtual properties which are _enumerable_
 *    (e.g., will be included when iterated over).
 *  - DEFS: rules for validating properties against.  @see Modelable::_validate().
 */
abstract class Model implements Modelable {

  /**
   * @type mixed[]  map of validation rules for properties (literal or virtual).
   *
   * each property may have one or more rules to be validated by.  each rule may be:
   *  - a callable: property value is valid if callable(value) is truthy.
   *    also accepts arrays in the form [callable, ...args],
   *    which are invoked like callable(value, ...args).
   *  - an array: valid if value is found in the array.
   *    comparison is strict; Modelables are compared using compare().
   *  - a fully qualified classname: valid if value is an instance of the named class.
   *  - a (psuedo)datatype: valid if value matches type. @see Vars::typeCheck().
   *  - a regular expression: valid if the value is a string, and matches the pattern.
   *    also accepts at\PRO\Regex objects.
   *  - a boolean: valid if boolean is true.
   *
   * if a value passes all of its tests, it will be considered valid.
   * note that these rules are only applied if no validator ("valid{property}" method) exists.
   *
   * if no validator or rules are defined, a property value is automatically considered valid.
   */
  const DEFS = [];

  /** @type string[]  list of enumerable properties (literal or virtual). */
  const ENUM = [];

  /** @type string[]  list of primary (identifiable) property keys. */
  const KEYS = [];

  /** @type string[]  list of literal property keys. */
  const VARS = [];

  /** @type string[]  instance copy of list of enumerable keys. */
  private $_ENUM = [];

  /**
   * @property mixed $...
   *
   * implementing classes should declare each of static::VARS as a private instance property.
   */

  /**
   * @method mixed get...(void)
   *
   * getters compute virtual property values from one or more literal properties.
   * this process *must* be deterministic.
   *
   * @return mixed  the property value
   */

  /**
   * @method void set...(mixed $value)
   *
   * setters store values for both literal and virtual properties.
   * note, setters may modify values depending on how they are stored/represented internally.
   * in this sense, getters and setters exist independently.
   *
   * setters are also responsible for validating values before storing them,
   * though this should not be done by the setter directly (use validator methods instead).
   *
   * @param mixed $value         the value to set
   * @throws ModelableException  if the value fails validation
   */

  /**
   * @method bool valid...(mixed $value)
   *
   * validators apply custom validation logic when rules in DEFS would not be sufficient.
   *
   * if a validator exists for a property, its DEFS are not applied automatically;
   * the validator must do so explicitly if desired.
   *
   * @param mixed $value  the value to validate
   * @return bool         true if value is valid; false otherwise
   */

  /**
   * checks a value against a one or more assertions.
   *
   * if the given rules are not an array, it is assumed to be a single rule.
   * @see Model::DEFS for details on how rules are applied.
   *
   * @param mixed         $value  subject value
   * @param mixed|mixed[] $rules  one or more rules to apply
   * @return bool                 true if all rules are satisfied; false otherwise
   */
  private static function _validate($value, $rules) : bool {
    if (! is_array($rules)) {
      $rules = [$rules];
    }

    foreach ($rules as $rule) {

      if (is_callable($rule)) {
        if ($rule($value)) { continue; }
        else { return false; }
      }

      if (is_array($rule)) {

        if(is_callable($rule[0])) {
          $args = $rule;
          $rule = array_shift($args);
          if ($rule($value, ...$args)) { continue; }
          else { return false; }
        }

        if (in_array($value, $rule)) { continue; }
        else { return false; }
      }

      if ($rule instanceof Regex) {
        if ($rule->matches($value)) { continue; }
        else { return false; }
      }

      if (is_string($rule)) {
        if (
          Vars::typeCheck($value, $rule) ||
          (Regex::isValid($rule) && (new Regex($rule))->matches($value))
        ) { continue; }
        else { return false; }
      }

      if (is_bool($rule)) {
        return $rule;
      }

      return false;
    }
    return true;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::compare()
   */
  public function compare(Modelable $other) : bool {
    if (empty(static::KEYS) || ! $other instanceof $this) {
      return false;
    }

    foreach (static::KEYS as $key) {
      if ($this->_get($key) !== $other->_get($key)) {
        return false;
      }
    }
    return true;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::getEnumerableProperties()
   */
  public function getEnumerableProperties() : array {
    return static::ENUM;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::getIdentifiableProperties()
   */
  public function getIdentifiableProperties() : array {
    return static::KEYS;
  }

  /**
   * gets a literal property value.
   *
   * this method is intended for internal use by the implementing class.
   * it performs no validation and throws no errors.
   *
   * @param string $offset  name of property
   * @return mixed          property value if exists; null otherwise
   */
  protected function _get(string $offset) {
    return $this->$property ?? null;
  }

  /**
   * sets a literal property value.
   *
   * this method is intended for internal use by the implementing class.
   * it performs no validation and throws no errors.
   *
   * @param string $offset  name of property
   * @param mixed  $value   value to set
   */
  protected function _set(string $offset, $value) {
    $this->$property = $value;
  }


  # ArrayAccess #

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetExists
   */
  public function offsetExists($offset) : bool {
    return in_array($offset, static::ENUM) ||
      method_exists($this, "get{$offset}");
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetGet
   */
  public function offsetGet($offset) {
    if (method_exists($this, "get{$offset}")) {
      return $this->{"get{$offset}"}();
    }

    if (in_array($offset, static::ENUM)) {
      return $this->_get($offset);
    }

    throw new ModelableException(ModelableException::NO_SUCH_PROPERTY, ['offset' => $offset]);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetSet
   */
  public function offsetSet($offset, $value) {
    if (method_exists($this, "set{$offset}")) {
      $this->{"set{$offset}"}($value);
      return;
    }

    if (! $this->offsetValid($offset, $value)) {
      throw new ModelableException(
        ModelableException::INVALID_PROPERTY_VALUE,
        ['offset' => $offset, 'value' => $value]
      );
    }
    $this->_set($offset, $value);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetUnset
   */
  public function offsetUnset($offset) {
    $this->offsetSet($offset, null);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::offsetValid()
   *
   * validation process is as follows:
   *  - uses declared validation method (valid{property}())
   *  - uses declared validation rules (static::DEFS[property])
   *  - assumes value is valid if:
   *    - a setter is declared (set{property}())
   *    - offsetExists(property) returns true
   */
  public function offsetValid(string $offset, $value) : bool {
    if (method_exists($this, "valid{$offset}")) {
      return $this->{"valid{$offset}"}($value, $flags);
    }

    if (isset(static::DEFS[$offset])) {
      return self::_validate($value, static::DEFS[$offset], $flag);
    }

    if (method_exists($this, "set{$offset}") || $this->offsetExists($offset)) {
      return true;
    }

    throw new ModelableException(ModelableException::NO_SUCH_PROPERTY, ['offset' => $offset]);
  }


  # Traversable (Iterator) #

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.current
   */
  public function current() {
    return $this->offsetGet(current($this->_ENUM));
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.key
   */
  public function key() {
    return current($this->_ENUM);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.next
   */
  public function next() {
    next($this->_ENUM);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.rewind
   */
  public function rewind() {
    $this->_ENUM = static::ENUM;
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.valid
   */
  public function valid() : bool {
    return key($this->_ENUM) !== null;
  }


  # Jsonable

  /**
   * {@inheritDoc}
   * @see Jsonable::jsonSerialize()
   */
  public function jsonSerialize() {
    return $this->toArray();
  }

  /**
   * {@inheritDoc}
   * @see Jsonable::toArray()
   */
  public function toArray() : array {
    return iterator_to_array($this);
  }

  /**
   * {@inheritDoc}
   * @see Jsonable::toJson()
   */
  public function toJson() : string {
    return Json::encode($this);
  }


  # Serializable

  /**
   * {@inheritDoc}
   * @see http://php.net/Serializable.serialize
   */
  public function serialize() {
    return serialize(array_map([$this, '_get'], static::VARS));
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Serializable.unserialize
   *
   * @throws ModelException  if serialized data is invalid
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);
    if (array_keys($data) !== static::VARS) {
      throw new ModelableException(
        ModelableException::INVALID_SERIALIZATION,
        ['serialized' => $serialized]
      );
    }

    foreach ($data as $property => $value) {
      $this->_set($property, $value);
    }
  }
}
