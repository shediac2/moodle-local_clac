<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * local_clac
 *
 * @license     GNU General Public License version 3
 * @package     local_clac
 */
defined('MOODLE_INTERNAL') || die();
$plugin             = new stdClass();
$plugin->version    = 2020061506;
$plugin->requires   = 2020061500;
$plugin->component  = 'local_clac';
$plugin->dependencies = array(
    'local_modcustomfields' => 2022021700,
);
