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
 * Local clac settings.
 *
 * @package    local_clac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
$plugin = 'local_clac';
global $CFG;
require_once($CFG->dirroot.'/local/clac/lib.php');
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_clac',
                                        new lang_string('pluginname', $plugin),
                                        'moodle/site:config');
    $ADMIN->add('localplugins', $settings);

    $customfields = local_clac_get_custom_fields();
    $catlist = local_clac_get_course_categories();
    $userlist = local_clac_get_users();
    $settings->add(new admin_setting_configselect('local_clac/fieldused',
                    new lang_string('fieldused', $plugin),
                    new lang_string('fieldused:desc', $plugin), 1, $customfields));
    $settings->add(new admin_setting_configtext('local_clac/sendingemail',
                    new lang_string('sendingemail', $plugin), '', 'noreply@collegecourses.clac.ca'));

    for ($i = 1; $i <= 5; $i++) {
        $catfield = 'local_clac/catfield'.$i;
        $catname = new lang_string('categoryname', $plugin).$i;
        $settings->add(new admin_setting_configselect($catfield, $catname, '', 0, $catlist));

        $testuser = 'local_clac/testuser'.$i;
        $testname = new lang_string('testname', $plugin).$i;
        $testdesc = new lang_string('testuser:desc', $plugin). $i;
        $settings->add(new admin_setting_configselect($testuser, $testname, $testdesc, 5, $userlist));

        $testuser = 'local_clac/contactuser'.$i;
        $contactname = new lang_string('contactuser', $plugin).$i;
        $contactdesc = new lang_string('contactuser:desc', $plugin). $i;
        $settings->add(new admin_setting_configselect($testuser, $contactname, $contactdesc, 5, $userlist));

        $name = 'local_clac/studentmessage'.$i;
        $studentext = new lang_string('studentmessage', $plugin).$i;
        $studentdesc = new lang_string('studentmessage:desc', $plugin).$i;
        $default = '';
        $settings->add(new admin_setting_configtextarea($name, $studentext, $studentdesc, $default));

        $name = 'local_clac/supermessage'.$i;
        $supertext = new lang_string('supermessage', $plugin).$i;
        $supertextdesc = new lang_string('supermessage:desc', $plugin).$i;
        $settings->add(new admin_setting_configtextarea($name, $supertext, $supertextdesc, $default));
    }
}
