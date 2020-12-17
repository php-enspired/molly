<?php
/**
 * @package    at.molly
 * @author     Adrian <adrian@enspi.red>
 * @copyright  2014 - 2019
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
namespace at\molly;

use Iterator,
  JsonSerializable,
  Serializable;

/**
 * Interface for entities (domain model objects).
 *
 * The goal is to provide a uniform, storage-agnostic api for entities,
 * including tools to access, validate, iterate over, and serialize.
 *
 * inherited methods:
 *
 * - mixed  Iterator::current(void)
 * - string Iterator::key(void)
 * - void   Iterator::next(void)
 * - void   Iterator::rewind(void)
 * - bool   Iterator::valid(void)
 *
 * - array  JsonSerializable::jsonSerialize(void)
 *
 * - string Serializable::serialize(void)
 * - void   Serializable::unserialize(string $serialized)
 */
interface Entity extends Iterator, JsonSerializable, Serializable {

  /**
   * Checks whether another instance has the same value as this entity.
   *
   * Making this determiniation must consider enumerable/gettable properties.
   * If artificial (e.g., a serial id), the entity's identity must not be considered.
   *
   * @param mixed $other  the value to compare to this entity
   * @return bool         true if value ; false otherwise
   */
  public function equals($other) : bool;

  /**
   * Gets the value of a readable property, i.e., one that is enumerable or has a getter method.
   *
   * @param string $property     name of the property to get
   * @throws ModelableException  if the property is not readable or does not exist
   * @return mixed               the property value
   */
  public function get(string $property);

  /**
   * Gets the model's identity.
   *
   * Identifiable properties are those which uniquely identify a model.
   * In RDBMS terms, they are the "primary keys."
   *
   * @return array  map of identifiable property=>values
   */
  public function identity() : array;

  /**
   * Checks whether another instance represents the same entity.
   *
   * This determination checks the entities' identities;
   * two instances comparing as "the same" does not imply they have equal values.
   *
   * @param mixed $other  the value to compare to this entity
   * @return bool         true if value represents the same entity; false otherwise
   */
  public function same($other) : bool;

  /**
   * Sets the value of a writable property, i.e., one that has a setter or validator method.
   *
   * @param string $property     name of the property
   * @param mixed  $value        the value to set
   * @throws ModelableException  if the property is not writable or does not exist,
   *                             or if the given value is not valid
   */
  public function set(string $property, $value);

  /**
   * Gets the state of the modelable instance.
   *
   * @return int  bitmask of Modelable::STATE_* constants
   */
  public function state() : int;

  /**
   * Unsets the value of an unsetable property,
   * i.e., one that has an unsetter method or accepts null as a valid value.
   *
   * @param string $property     name of the property to unset
   * @throws ModelableException  if the property is not unsetable or does not exist
   */
  public function unset(string $property);

  /**
   * Validates a value for a given offset (property).
   *
   * @param string $property     offset (property) name
   * @param mixed  $value        value to validate
   * @throws ModelableException  if offset does not exist
   * @return bool                true if value is valid; false otherwise
   */
  public function isValid(string $property, $value) : bool;
}
