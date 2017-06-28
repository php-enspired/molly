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
   * @type mixed[]  map of validation rules for literal properties.
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

  /** @type string[]  list of identifiable literal property keys. */
  const KEYS = [];

  /** @type string[]  list of literal property keys. */
  const VARS = [];

  /** @type string[]  instance copy of static::ENUM. */
  private $_ENUM;

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
   * @method void unset...(void)
   *
   * unsetters restore values for both literal and virtual properties to an "empty"/"unset" state.
   *
   * @throws ModelableException  if the property cannot be unset
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
   * {@inheritDoc}
   * @see Modelable::equals()
   */
  public function equals(Modelable $other) : bool {
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
   * {@inheritDoc}
   * @see Modelable::getState()
   */
  public function getState() : int {
    foreach (static::VARS as $property) {
      $value = $this->_get($property);
      if (! $this->_valid($property, $value)) {
        return ($value === null) ?
          self::STATE_INCOMPLETE :
          self::STATE_INVALID;
      }
    }
    return self::STATE_VALID;
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

  /**
   * validates a literal property value using rules found in static::DEFS array.
   *
   * this method is intended for internal use by the implementing class.
   * if the rules for a given property is not an array, it is assumed to be a single rule.
   * if no rules are declared for a given property, the value is considered valid.
   *
   * @see Model::DEFS for details on how rules are applied.
   *
   * @param string $offset  name of property
   * @param mixed  $value   value to validate
   * @return bool           true if value is valid; false otherwise
   */
  protected function _valid(string $offset, $value) : bool {
    $rules = static::DEFS[$offset] ?? [];
    if (! is_array($rules)) {
      $rules = [$rules];
    }

    foreach ($rules as $rule) {

      if (is_callable($rule)) {
        if ($rule($value)) { continue; }
      }

      if (is_array($rule)) {
        if(is_callable($rule[0])) {
          $args = $rule;
          $rule = array_shift($args);
          if ($rule($value, ...$args)) { continue; }
        }

        if (in_array($value, $rule)) { continue; }
      }

      if (is_bool($rule)) {
        return $rule;
      }

      if (is_string($rule)) {
        if (Vars::typeCheck($value, $rule)) { continue; }
        elseif (is_string($value)) {
          try {
            // @todo should we support $modifiers somehow?
            //  `u` is implicit; can't imagine others would see much use.
            if ((new Regex($rule))->matches($value)) { continue; }
          } catch (\Throwable $e) {
            // "not a regex" isn't a failure
          }
        }
      }

      return false;
    }
    return true;
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
    if (method_exists($this, "unset{$offset}")) {
      $this->{"unset{$offset}"}();
      return;
    }

    $this->offsetSet($offset, null);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::offsetValid()
   *
   * validation process is as follows:
   *  - uses declared validator (valid{property}())
   *  - uses declared validation rules (static::DEFS[property]) if property is enumerable or settable
   */
  public function offsetValid(string $offset, $value) : bool {
    if (method_exists($this, "valid{$offset}")) {
      return $this->{"valid{$offset}"}($value, $flags);
    }

    if (in_array($offset, static::ENUM) || method_exists($this, "set{$offset}")) {
      return $this->_valid($offset, $value);
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
    if (! $this->_ENUM) {
      $this->_ENUM = static::ENUM;
    }
    reset($this->_ENUM);
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
