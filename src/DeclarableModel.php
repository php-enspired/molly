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

use at\molly\ {
  Modelable,
  ModelableException
};
use at\util\ {
  Json,
  Validator
};

/**
 * declarative domain models (data objects).
 *
 * implementations declare the Model's schema and validation rules as class constants,
 * allowing most of the modelable logic to be handled by the base class.
 * in the simplest case, a child class can define a list of property names, and be done.
 */
abstract class DeclarableModel implements Modelable {

  /**
   * @type string[]  list of enumerable properties.
   *
   * enumerable properties may be literal or virtual.
   * if ENUMS are not defined, NAMES will be enumerable.
   */
  const ENUMS = null;

  /** @type string[]  list of identifiable literal property keys. */
  const KEYS = [];

  /**
   * @type array[]  map of validation rules for literal properties.
   *
   * each literal property may have one or more rules to be validated by.
   * rules may be defined as follows:
   *  - a callable, or [callable, ...args] array. passes if callable(value, ...args) is truthy.
   *  - an array of values. passes if the value is present in the array.
   *  - a fully qualified classname. passes if the value is an instance of that class.
   *  - a data type or pseudotype. passes if the value is of that type.
   *  - @todo a regex.
   *  - @todo a FILTER_* constant.
   *  - a boolean. true always passes; false always fails.
   *
   * if all rules return truthy, the value will be considered valid.
   * if no validator or rules are defined, a property value is automatically considered valid.
   *
   * note that these rules are only applied if no validator ("valid{property}" method) exists.
   */
  const RULES = [];

  /**
   * @type string[]  list of literal property keys.
   *
   * the implementing class *must* define names. it will break without them.
   */
  const NAMES = [];

  /**
   * @type string[]  instance copy of enumberable property keys.
   *
   * @internal this *must* be treated as constant; it exists only as an implementation detail
   * (to avoid sharing an array pointer between instances when iterating).
   */
  private $_ENUMS;

  /**
   * @property mixed ${name}
   *
   * implementing classes should declare each of static::NAMES as a nonpublic instance property.
   */

  /**
   * @method mixed get{name}(void)
   *
   * getters compute virtual property values from one or more literal properties.
   * this process *must* be deterministic.
   *
   * @return mixed  the property value
   */

  /**
   * @method void set{name}(mixed $value)
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
   * @method void unset{name}(void)
   *
   * unsetters restore values for literal or virtual properties to an "empty"/"unset" state.
   *
   * @throws ModelableException  if the property cannot be unset
   */

  /**
   * @method bool validate{name}(mixed $value)
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
  public function equals($other) : bool {
    if (empty(static::KEYS) || ! $other instanceof $this) {
      return false;
    }

    foreach (static::NAMES as $property) {
      if ($this->_get($property) !== $other->_get($property)) {
        return false;
      }
    }
    return true;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::get()
   */
  public function get(string $property) {
    if (method_exists($this, "get{$property}")) {
      return $this->{"get{$property}"}();
    }

   if (in_array($property, static::ENUMS ?? static::NAMES)) {
      return $this->_get($property);
    }

    throw new ModelableException(
      ModelableException::PROPERTY_NOT_READABLE,
      ['property' => $property]
    );
  }

  /**
   * {@inheritDoc}
   * @see Modelable::identity()
   */
  public function identity() : array {
    $identity = [];
    foreach (static::KEYS as $key) {
      $identity[$key] = $this->_get($key);
    }
    return $identity;
  }

  /**
   * convenience method for checking Modelable state.
   *
   * @return bool  true if Modelable is in an incomplete state; false otherwise
   */
  public function isStateIncomplete() : bool {
    return $this->state() & Modelable::STATE_INCOMPLETE === Modelable::STATE_INCOMPLETE;
  }

  /**
   * convenience method for checking Modelable state.
   *
   * @return bool  true if Modelable is in an invalid state; false otherwise
   */
  public function isStateInvalid() : bool {
    return $this->state() & Modelable::STATE_INVALID === Modelable::STATE_INVALID;
  }

  /**
   * convenience method for checking Modelable state.
   *
   * @return bool  true if Modelable is in a valid state; false otherwise
   */
  public function isStateValid() : bool {
    return $this->state() & Modelable::STATE_VALID === Modelable::STATE_VALID;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::set()
   */
  public function set(string $property, $value) {
    if (method_exists($this, "set{$property}")) {
      $this->{"set{$property}"}($value);
      return;
    }

    if (! $this->validate($property, $value)) {
      throw new ModelableException(
        ModelableException::INVALID_PROPERTY_VALUE,
        ['property' => $property, 'value' => $value]
      );
    }

    $this->_set($property, $value);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::state()
   */
  public function state() : int {
    $state = 0;
    foreach (static::NAMES as $property) {
      $value = $this->_get($property);
      if (! $this->_valid($property, $value)) {
        $state |= ($value === null) ?
          self::STATE_INCOMPLETE :
          self::STATE_INVALID;
      }
    }
    return $state ?: self::STATE_VALID;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::unset()
   */
  public function unset(string $property) {
    if (method_exists($this, "unset{$property}")) {
      $this->{"unset{$property}"}();
      return;
    }

    $this->offsetSet($property, null);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::validate()
   *
   * validation process is as follows:
   *  - validator method, if defined
   *  - validation rules, if property is enumerable or setable
   */
  public function validate(string $property, $value) : bool {
    if (method_exists($this, "isValid{$property}")) {
      return $this->{"isValid{$property}"}($value, $flags);
    }

    if (
      in_array($property, static::ENUMS ?? static::NAMES) ||
      method_exists($this, "set{$property}")
    ) {
      return $this->_validate($property, $value);
    }

    throw new ModelableException(
      ModelableException::PROPERTY_NOT_WRITABLE,
      ['property' => $property]
    );
  }

  /**
   * gets a literal property value.
   *
   * this method is intended for internal use by the implementing class.
   * it performs no validation and throws no errors.
   *
   * @param string $property  name of property
   * @return mixed          property value if exists; null otherwise
   */
  protected function _get(string $property) {
    return $this->$property ?? null;
  }

  /**
   * sets a literal property value.
   *
   * this method is intended for internal use by the implementing class.
   * it performs no validation and throws no errors.
   *
   * @param string $property  name of property
   * @param mixed  $value   value to set
   */
  protected function _set(string $property, $value) {
    $this->$property = $value;
  }

  /**
   * validates a literal property value using rules found in static::DEFS array.
   *
   * this method is intended for internal use by the implementing class.
   * all rules must pass for the value to be considered valid.
   * if no rules are declared for a given property, the value is considered valid.
   *
   * @see Model::RULES for details on how rules are defined.
   *
   * @param string $property  name of property
   * @param mixed  $value   value to validate
   * @return bool           true if value is valid; false otherwise
   */
  protected function _validate(string $property, $value) : bool {
    $rules = [];
    foreach (static::RULES ?? [] as $definition) {

      // shorthand: callable with only value arg
      if (is_callable($definition)) {
        $rules[] = [$definition, $value];
        continue;
      }

      // shorthand: instanceof / type / pseudotype hint
      if (is_string($definition)) {
        $rules[] = [Validator::TYPE, $value, $definition];
        continue;
      }

      if (is_array($definition)) {
        // shorthand: callable with additional args
        if (is_callable(reset($definition))) {
          $rules[] = ArrayTools::splice($definition, 1, 0, [$value]);
          continue;
        }

        // shorthand: whitelist
        $rules[] = [Validator::ONE_OF, $value, $definition];
        continue;
      }

      // shorthand: always / never
      if (is_bool($definition)) {
        $rules[] = [$definition ? Validator::ALWAYS : Validator::NEVER];
        continue;
      }
    }

    // apply all rules
    return empty($rules) ? true : Validator::all(...$rules);
  }


  # ArrayAccess #

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetExists
   */
  public function offsetExists($offset) : bool {
    return in_array($offset, static::ENUMS ?? static::NAMES) ||
      method_exists($this, "get{$offset}");
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetGet
   */
  public function offsetGet($offset) {
    return $this->get($offset);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetSet
   */
  public function offsetSet($offset, $value) {
    $this->set($offset, $value);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/ArrayAccess.offsetUnset
   */
  public function offsetUnset($offset) {
    $this->unset($offset);
  }


  # Traversable (Iterator) #

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.current
   */
  public function current() {
    return $this->get(current($this->_ENUMS));
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.key
   */
  public function key() {
    return current($this->_ENUMS);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.next
   */
  public function next() {
    next($this->_ENUMS);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.rewind
   */
  public function rewind() {
    $this->_ENUMS = static::ENUMS ?? static::NAMES;
    reset($this->_ENUMS);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.valid
   */
  public function valid() : bool {
    return key($this->_ENUMS) !== null;
  }


  # JsonSerializable

  /**
   * {@inheritDoc}
   * @see JsonSerializable::jsonSerialize()
   */
  public function jsonSerialize() {
    return iterator_to_array($this);
  }


  # Serializable

  /**
   * {@inheritDoc}
   * @see http://php.net/Serializable.serialize
   */
  public function serialize() {
    $data = [];
    foreach (static::NAMES as $property) {
      $data[$property] = $this->_get($property);
    }
    return serialize($data);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Serializable.unserialize
   *
   * @throws ModelException  if serialized data is invalid
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);
    if (array_keys($data) !== static::NAMES) {
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
