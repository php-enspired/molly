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

namespace at\molly\tests;

use PHPUnit\Framework\TestCase;

use at\molly\ {
  Modelable,
  ModelableException
};

/**
 * base test cases for Modelable interface methods.
 *
 * implementations should extend this test and write data providers (see @return definitions),
 * along with other tests as needed.
 *
 * tests are included for every interface method.
 * however,
 * because property characteristics are flexible and the interface does not define behavior,
 * it is not possible to write generic tests for every possible implementation.
 *
 * for example, testSet()/testUnset() can handle invocation and checking for exceptions,
 * but cannot fully cover properties which are not also readable.
 *
 * other tests may have similar "blind spots" depending on the implementation,
 * so make sure to review thoroughly and add tests as needed.
 */
abstract class ModelableTest extends TestCase {

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type Modelable $1  a modelable to compare to
   *    @type bool      $2  should the modelables compare as equal?
   *  }
   */
  abstract public function _comparisonProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type Modelable $1  a modelable to compare to
   *    @type bool      $2  should the modelables compare as equal?
   *  }
   */
  abstract public function _identityProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type array     $1  the property name=>value pairs expected on iteration
   *  }
   */
  abstract public function _iterationProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type string    $1  property name
   *    @type bool      $2  true if property exists on modelable; false otherwise
   *  }
   */
  abstract public function _nameProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type string    $1  property name to read
   *    @type mixed     $2  the value expected to be read;
   *                        or a ModelableException if reading should throw
   *  }
   */
  abstract public function _readableProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  the modelable instance to test
   *    @type int       $1  expected Modelable::STATE_*
   *  }
   */
  abstract public function _stateProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type string    $1  property name to unset
   *    @type mixed     $3  the expected default "unset" value if property is readable;
   *                        a ModelableException if writing should throw;
   *                        null otherwise
   *  }
   */
  abstract public function _unsetableProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type string    $1  property name to validate
   *    @type mixed     $2  the value to validate
   *    @type mixed     $3  expected validation result (boolean);
   *                        or a ModelableException if validation should throw
   *  }
   */
  abstract public function _validationProvider() : array;

  /**
   * @return array[] {
   *    @type Modelable $0  modelable instance
   *    @type string    $1  property name to write
   *    @type mixed     $2  the value to set
   *    @type mixed     $3  the property's expected value if property is readable;
   *                        a ModelableException if writing should throw;
   *                        null otherwise
   *  }
   */
  abstract public function _writableProvider() : array;


  /**
   * @covers Modelable::equals()
   * @dataProvider _equalityProvider
   */
  public function testEquals(Modelable $modelable, Modelable $other, bool $expected) {
    $this->assertEquals($expected, $modelable->equals($other));
  }

  /**
   * @covers Modelable::get()
   * @dataProvider _readableProvider
   */
  public function testGet(Modelable $modelable, string $property, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $this->assertEquals($expected, $modelable->get($property));
  }

  /**
   * @covers Modelable::identity()
   * @dataProvider _identityProvider
   */
  public function testIdentity(Modelable $modelable, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $actual = $modelable->identity();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers Modelable::set()
   * @dataProvider _writableProvider
   */
  public function testSet(Modelable $modelable, string $property, $value, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $modelable->set($property, $value);

    if ($expected !== null) {
      $this->assertEquals($expected, $modelable->get($property));
    }
  }

  /**
   * @covers Modelable::state()
   * @dataProvider _stateProvider
   */
  public function testState(Modelable $modelable, int $expected) {
    $this->assertEquals($expected, $modelable->state());
  }

  /**
   * @covers Modelable::unset()
   * @dataProvider _unsetableProvider
   */
  public function testUnset(Modelable $modelable, string $property, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $modelable->unset($property);

    if ($expected !== null) {
      $this->expectEquals($expected, $modelable->get($property));
    }
  }

  /**
   * @covers Modelable::validate()
   * @dataProvider _validationProvider
   */
  public function testValidate(Modelable $modelable, string $property, $value, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $actual = $modelable->validate($property, $value);
    $this->assertEquals($expected, $actual);
  }


  # ArrayAccess #

  /**
   * @covers Modelable::offsetExists()
   * @dataProvider _nameProvider
   */
  public function testOffsetExists(Modelable $modelable, string $property, bool $expected) {
    $this->assertEquals($expected, isset($modelable[$property]));
  }

  /**
   * @covers Modelable::offsetGet()
   * @dataProvider _readableProvider
   */
  public function testOffsetGet(Modelable $modelable, string $property, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $this->assertEquals($expected, $modelable[$property]);
  }

  /**
   * @covers Modelable::offsetSet()
   * @dataProvider _writableProvider
   */
  public function testOffsetSet(Modelable $modelable, string $property, $value, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    $modelable[$property] = $value;

    if ($expected !== null) {
      $this->assertEquals($expected, $modelable[$property]);
    }
  }

  /**
   * @covers Modelable::offsetUnset()
   * @dataProvider _unsetableProvider
   */
  public function testOffsetUnset(Modelable $modelable, string $property, $expected) {
    if ($expected instanceof ModelableException) {
      $this->expectException($expected);
    }

    unset($modelable[$property]);

    if ($expected !== null) {
      $this->expectEquals($expected, $modelable[$property]);
    }
  }


  # Iterator #

  /**
   * @covers Modelable::current()
   * @covers Modelable::key()
   * @covers Modelable::next()
   * @covers Modelable::rewind()
   * @covers Modelable::valid()
   * @dataProvider _iterationProvider
   */
  public function testIteration(Modelable $modelable, array $expected) {
    foreach ($modelable as $property => $value) {
      $this->assertNotEmpty($expected);
      $expectedName = key($expected);
      $expectedValue = array_shift($expected);

      $this->assertEquals($expectedName, $property);
      $this->assertEquals($expectedValue, $value);
      $this->assertEquals($value, $modelable->get($property));
    }

    $this->assertEmpty($expected);
  }


  # JsonSerializable #

  /**
   * @covers Modelable::JsonSerialize()
   * @dataProvider _jsonProvider
   */
  public function testJsonSerialize(Modelable $modelable, string $expected) {
    $this->assertEquals(json_encode($modelable), $expected);
    $this->assertTrue(json_last_error() === JSON_ERROR_NONE);
  }


  # Serializable #

  /**
   * @covers Modelable::serialize()
   * @covers Modelable::unserialize()
   * @dataProvider _iterationProvider
   */
  public function testSerialization(Modelable $modelable) {
    $serialized = $modelable->serialize();
    $other = (new $modelableFQCN)->unserialize($serialized);

    if (! empty($modelable->identity())) {
      $this->assertTrue($modelable->equals($other));
    } else {
      $this->assertEquals(iterator_to_array($modelable), iterator_to_array($other));
    }
  }

  /**
   * sets exception expectations based on provided Exception instance.
   *
   * @param string|Throwable $exception  the exception to expect
   */
  public function expectException($exception) {
    if (is_string($exception)) {
      parent::expectException($exception);
      return;
    }

    parent::expectException(get_class($exception));
    $this->expectExceptionCode($exception->getCode());
  }
}
