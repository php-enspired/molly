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

  /** @type int  invalid property value. */
  const INVALID_PROPERTY_VALUE = 1;

  /** @type int  invalid serialization. */
  const INVALID_SERIALIZATION = 2;

  /** @type int  no such property. */
  const NO_SUCH_PROPERTY = 3;

  /** {@inheritDoc} @see Exceptable::INFO */
  const INFO = [
    self::INVALID_PROPERTY_VALUE => [
      'message' => 'invalid property value',
      'tr_message' => 'invalid value for "{offset}": {value}',
      'severity' => Exceptable::NOTICE
    ],
    self::INVALID_SERIALIZATION => [
      'message' => 'invalid modelable serialization',
      'severity' => Exceptable::ERROR
    ],
    self::NO_SUCH_PROPERTY => [
      'message' => 'no such property',
      'tr_message' => 'no modelable property "{offset}" exists',
      'severity' => Exceptable::ERROR
    ]
  ];
}
