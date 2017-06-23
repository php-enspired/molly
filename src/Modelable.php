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
 * implementing classes can define both "literal" (defined) and "virtual" (calculated) properties.
 *
 * literal properties are actual values stored on the model.
 *
 * virtual properties can be accessed as if they are literal properties,
 * but their values are calculated from one or more literal properties.
 * this calculation must be _deterministic_
 * (i.e., given the same literal properties, the same virtual property values will be returned).
 *
 * whether properties are literal or virtual should be of little importance to outside code,
 * though may be important to for implementation details (e.g., deserializing, database storage).
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
 * @method Jsonable Jsonable::fromArray(array $data)
 * @method array    Jsonable::jsonSerialize(void)
 * @method array    Jsonable::toArray(void)
 * @method string   Jsonable::toJson(void)
 *
 * @method string Serializable::serialize(void)
 * @method void   Serializable::serialize(string $serialized)
 */
interface Modelable extends ArrayAccess, Iterator, Jsonable, Serializable {

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
  public function compare(Modelable $other) : bool;

  /**
   * lists enumerable properties.
   * this method *must* return the same property names, in the same order,
   * that would be provided when the instance is iterated over.
   *
   * @return string[]  list of enumerable property names
   */
  public function getEnumerableProperties() : array;

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
   * @return string[]  list of identifiable property names
   */
  public function getIdentifiableProperties() : array;

  /**
   * lists readable properties.
   *
   * at a minimum, all enumberable properties must be readable.
   * other literal or virtual properties may also be readable, depending on the implementation.
   *
   * @return string[]  list of readable property names
   */
  // @todo  is this needed? complicates implementation, and i'm not sure of any benefit.
  //public function getReadableProperties() : array;

  /**
   * lists writable properties.
   *
   * writable properties, literal or virtual, can accept new values at runtime
   * (subject to validation, of course).
   * properties which can be "unset" (set to null/reset to a default value) are also considered writable.
   *
   * @return string[]  list of writable property names
   */
  // @todo  is this needed? complicates implementation, and i'm not sure of any benefit.
  //public function getWritableProperties() : array;

  /**
   * validates a value for a given offset (property).
   * this method is intended to compliment the ArrayAccess methods.
   *
   * @param string $offset
   * @param mixed  $value
   */
  public function offsetValid(string $offset, $value) : bool;
}
