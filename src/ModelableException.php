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

use at\exceptable\Exception as Exceptable;

class ModelableException extends Exceptable {

  /**
    * @type int INVALID_PROPERTY_VALUE
    * @type int INVALID_SERIALIZATION
    * @type int PROPERTY_NOT_READABLE
    * @type int PROPERTY_NOT_WRITABLE
    */
  const INVALID_PROPERTY_VALUE = (1<<1);
  const INVALID_SERIALIZATION = (1<<2);
  const PROPERTY_NOT_READABLE = (1<<3);
  const PROPERTY_NOT_WRITABLE = (1<<4);


  /** {@inheritDoc} @see Exceptable::INFO */
  const INFO = [
    self::INVALID_PROPERTY_VALUE => [
      'message' => 'invalid property value',
      'tr_message' => 'invalid value for "{property}": {value}',
      'severity' => Exceptable::NOTICE
    ],
    self::INVALID_SERIALIZATION => [
      'message' => 'invalid modelable serialization'
    ],
    self::PROPERTY_NOT_READABLE => [
      'messgae' => 'property not readable',
      'tr_message' => 'no readable property "{property}" exists'
    ],
    self::PROPERTY_NOT_WRITABLE => [
      'messgae' => 'property not writable',
      'tr_message' => 'no writable property "{property}" exists'
    ]
  ];
}
