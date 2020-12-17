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
  DTO,
  Entity
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
abstract class AbstractEntity implements Entity {

  /** @var string Fully qualified classname of the Entity's DTO. */
  protected const DTO_CLASSNAME = null;

  /**
   * @var ?string[]  list of enumerable property names.
   *
   * if ENUMS are not defined, then DTO properties will be enumerable.
   */
  protected const ENUMS = null;

  /** @var DTO  this instance's "literal" properties. */
  protected $DTO;

  /**
   * @type string[]  instance copy of static::ENUMERABLE.
   *
   * @internal this *must* be treated as constant; it exists only as an implementation detail
   *  (to avoid sharing an array pointer between instances when iterating).
   */
  private $ENUMS;

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
   * @method bool isValid{name}(mixed $value)
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
   * @param ?DTO $dto  data transfer object providing "literal" properties
   */
  public function __construct(?DTO $dto) {
    $this->DTO = $dto ?? $this->newDTO();
    $this->ENUMS = $this->ENUMS ?? static::ENUMS ?? $this->DTO::NAMES;
  }

  /**
   * {@inheritDoc}
   * @see Modelable::equals()
   */
  public function equals($other) : bool {
    if (! $other instanceof $this) {
      return false;
    }

    foreach ($this->DTO as $property => $value) {
      if ($other->DTO->$property !== $value) {
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
    return method_exists($this, "get{$property}") ?
      $this->{"get{$property}"}() :
      $this->DTO->get($property);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::identity()
   */
  public function identity() : array {
    return $this->DTO->identity();
  }

  /**
   * {@inheritDoc}
   * @see Modelable::set()
   */
  public function set(string $property, $value) : void {
    method_exists($this, "set{$property}") ?
      $this->{"set{$property}"}($value) :
      $this->DTO->set($property, $value);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::unset()
   */
  public function unset(string $property) : void {
    method_exists($this, "unset{$property}") ?
      $this->{"unset{$property}"}() :
      $this->set($property, null);
  }

  /**
   * {@inheritDoc}
   * @see Modelable::validate()
   *
   * validation process is as follows:
   *  - validator method, if defined
   *  - validation rules, if property is enumerable or setable
   */
  public function isValid(string $property, $value) : bool {
    return method_exists($this, "isValid{$property}") ?
      $this->{"isValid{$property}"}($value) :
      $this->DTO->isValid($property, $value);
  }


  # Traversable (Iterator) #

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.current
   */
  public function current() {
    return $this->get(current($this->ENUMS));
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.key
   */
  public function key() {
    return current($this->ENUMS);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.next
   */
  public function next() {
    next($this->ENUMS);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.rewind
   */
  public function rewind() {
    reset($this->ENUMS);
  }

  /**
   * {@inheritDoc}
   * @see http://php.net/Iterator.valid
   */
  public function valid() : bool {
    return key($this->ENUMS) !== null;
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
