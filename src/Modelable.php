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

use ArrayAccess;
use Iterator;
use Serializable;

use at\util\Jsonable;

/**
 * interface for domain models (data objects).
 * the key characteristic here is that the data has a _schema_ and can be _validated_.
 *
 * the goal is to provide a uniform api for access, validation, iteration, and serialization.
 *
 * implementing classes can define both "literal" (defined) and "virtual" (computed) properties.
 *
 * literal properties are actual values stored on the model.
 *
 * virtual properties can be accessed as if they are literal properties,
 * but their values are computed from literal properties.
 * this allows implementations to expose different "views" on the literal properties.
 * @example <code>
 *  <?php
 *
 *  class ExampleModel implements Modelable {
 *
 *    // a literal property.
 *    private $number = 5;
 *
 *    // a virtual property.
 *    public function square() : int {
 *      return $this->literal ** 2;
 *    }
 *
 *    // simple example of a get() implementation.
 *    public function get($property) {
 *      switch ($property) {
 *        case 'number': return $this->number;
 *        case 'square': return $this->square();
 *        // . . .
 *      }
 *    }
 *  }
 * </code>
 *
 * both literal and virtual properties may be enumerable, readable, and/or writable.
 *
 * whether properties are literal or virtual should be of little importance to outside code,
 * though may be important for implementation reasons (e.g., serializing, database storage).
 *
 * @method bool  ArrayAccess::offsetExists(string $offset)
 * @method mixed ArrayAccess::offsetGet(string $offset)
 * @method void  ArrayAccess::offsetSet(string $offset, mixed $value)
 * @method void  ArrayAccess::offsetUnset(string $offset)
 *
 * @method mixed  Iterator::current(void)
 * @method string Iterator::key(void)
 * @method void   Iterator::next(void)
 * @method void   Iterator::rewind(void)
 * @method bool   Iterator::valid(void)
 *
 * @method array  Jsonable::jsonSerialize(void)
 * @method array  Jsonable::toArray(void)
 * @method string Jsonable::toJson(void)
 *
 * @method string Serializable::serialize(void)
 * @method void   Serializable::serialize(string $serialized)
 */
interface Modelable extends ArrayAccess, Iterator, Jsonable, Serializable {

  /**
   * indicates the internal state of a Modelable instance.
   *
   * @type int STATE_INCOMPLETE  the data is invalid; some property values are missing
   * @type int STATE_INVALID     the data is invalid; some property values are invalid
   * @type int STATE_VALID       the data is valid
   */
  const STATE_INCOMPLETE = 1;
  const STATE_INVALID = (1<<1);
  const STATE_VALID = (1<<2);

  /**
   * checks whether another modelable instance represents the same unique model.
   *
   * this determiniation *must* be made based on the modelable's identifiable properties;
   * value(s) of other properties are not relevant.
   *
   * if the modelable has no identifiable properties,
   * then all instances must be considered distinct.
   *
   * @return bool  true if given instance represents the same unique model; false otherwise
   */
  public function equals(Modelable $other) : bool;

  /**
   * lists enumerable properties.
   * this method *must* return the same property names, in the same order,
   * that would be provided when the instance is iterated over.
   *
   * @return string[]  list of enumerable property names
   */
  public function enumerableProperties() : array;

  /**
   * gets the value of a readable property, i.e., one that is enumerable or has a getter method.
   *
   * @param string $property     name of the property to get
   * @throws ModelableException  if the property is not readable or does not exist
   * @return mixed               the property value
   */
  public function get(string $property);

  /**
   * lists identifiable properties.
   *
   * identifiable properties are those which uniquely identify a model.
   * in RDBMS terms, they are the "primary keys."
   * identifiable properties should be "natural,"
   * i.e., things like serial ids or uuids should be avoided.
   *
   * some models may not have such properties; an empty array must be returned in this case.
   *
   * identifiable properties *must* be enumerable.
   *
   * @return string[]  list of identifiable property names
   */
  public function identifiableProperties() : array;

  /**
   * validates a value for a given offset (property).
   *
   * @param string $property     offset (property) name
   * @param mixed  $value        value to validate
   * @throws ModelableException  if offset does not exist
   * @return bool                true if value is valid; false otherwise
   */
  public function isValid(string $property, $value) : bool;

  /**
   * sets the value of a writable property, i.e., one that has a setter or validator method.
   *
   * @param string $property     name of the property
   * @param mixed  $value        the value to set
   * @throws ModelableException  if the property is not writable or does not exist,
   *                             or if the given value is not valid
   */
  public function set(string $property, $value);

  /**
   * gets the state of the modelable instance.
   *
   * @return int  bitmask of Modelable::STATE_* constants
   */
  public function state() : int;

  /**
   * unsets the value of an unsetable property,
   * i.e., one that has an unsetter method or accepts null as a valid value.
   *
   * @param string $property     name of the property to unset
   * @throws ModelableException  if the property is not unsetable or does not exist
   */
  public function unset(string $property);
}
