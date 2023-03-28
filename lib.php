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
 *  local_clac
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to return the activity custom fields.
 * @return array $customfields custom profile fields with a datatype of textarea.
 */
function local_clac_get_custom_fields() {
    global $DB;
    $customfields = array();
    $proffields = $DB->get_records('customfield_field', array('categoryid' => '2', 'type' => 'text'));
    foreach ($proffields as $proffield) {
        $customfields[$proffield->id] = $proffield->name.' ('.$proffield->shortname.')';
    }
    return $customfields;
}

/**
 * Function to return a list of course categories.
 * @return array $customfields of course categories.
 */
function local_clac_get_course_categories() {
    global $DB;
    $customfields = array();
    $proffields = $DB->get_records('course_categories', array('visible' => '1'));
    $customfields[0] = get_string('na', 'local_clac');

    foreach ($proffields as $proffield) {
        $customfields[$proffield->id] = $proffield->name;
    }
    return $customfields;
}


/**
 * Function to return a list of course categories.
 * @return array $customfields of users.
 */
function local_clac_get_users() {
    global $DB;
    $customfields = array();
    $proffields = $DB->get_records('user', array('deleted' => '0'));
    foreach ($proffields as $proffield) {
        $customfields[$proffield->id] = $proffield->firstname. ' ' .$proffield->lastname. '( '.$proffield->email .' )';
    }
    return $customfields;
}

/**
 * Function to determine the total time in a course.
 * @param int $courseid The id of the course.
 * @return string - total time in the course.
 */
function local_clac_get_course_time($courseid) {
    global $DB;
    $timefield = get_config('local_clac', 'fieldused');
    // Get the total of all activities in the course.
    $seclist = $DB->get_records('course_sections', array('course' => $courseid));
    $tottm = 0;

    foreach ($seclist as $seq) {
        $seqlist = $seq->sequence;
        $seq = explode(",", $seqlist);
        foreach ($seq as $seqdetail) {
            $ldet = strlen($seqdetail);
            if ($ldet > 0) {
                $instance = $DB->get_field('course_modules',  'instance', array('visible' => '1', 'completion' => '1',
                                           'id' => $seqdetail));
                if ($instance) {
                    $tm = $DB->get_field('customfield_data', 'value', array('instanceid' => $seqdetail, 'fieldid' => $timefield));
                    if ($tm) {
                        $tottm = $tottm + $tm;
                    }
                }
            }
        }
    }
    return $tottm;
}

/**
 * Function to determine the total time in a course.
 * @param int $courseid The id of the course.
 * @param int $userid - the id of the user
 * @return string - total time for completed activities.
 */
function local_clac_get_course_completed_time($courseid, $userid) {
    global $DB;
    $timefield = get_config('local_clac', 'fieldused');
    // Get the total of all activities in the course.
    $seclist = $DB->get_records('course_sections', array('course' => $courseid));
    $tottm = 0;
    foreach ($seclist as $seq) {
        $seqlist = $seq->sequence;
        $seq = explode(",", $seqlist);
        foreach ($seq as $seqdetail) {
            $ldet = strlen($seqdetail);
            if ($ldet > 0) {
                $instance = $DB->get_field('course_modules',  'instance', array('visible' => '1', 'completion' => '1',
                                           'id' => $seqdetail));
                if ($instance) {
                    $tm = $DB->get_field('customfield_data', 'value', array('instanceid' => $seqdetail, 'fieldid' => $timefield));
                    if ($tm) {
                        // See if the module is complete.
                        $comp = $DB->get_record('course_modules_completion', array('coursemoduleid' => $seqdetail,
                                                'userid' => $userid, 'completionstate' => '1'));
                        if ($comp) {
                            $tottm = $tottm + $tm;
                        }
                    }
                }
            }
        }
    }
    return $tottm;
}

/**
 * Function to generate the report.
 * @param int $userid - the id of the user
 * @param int $categoryid - the category used
 * @param int $mode - the mode used - 1 - display to user, 2 - send to user, 3 - test report, 4 regular report;
 * @return string - the report.
 */
function local_clac_generate_report($userid, $categoryid, $mode) {
    global $DB, $CFG;
    $plugin = 'local_clac';
    $elpt = 0;
    $timefield = get_config('local_clac', 'fieldused');
    $fdlist = '';
    $fdcnt = 0;
    $validcat = false;
    // Find the matching category information.
    for ($i = 1; $i <= 5; $i++) {
        $catfield = 'catfield'.$i;
        $catvalue = get_config($plugin, $catfield);
        if ($catvalue == $categoryid) {
            $validcat = true;
            $sm = 'studentmessage'.$i;
            $superm = 'supermessage'.$i;
            $testu = 'testuser'.$i;
            $contactu = 'contactuser'.$i;
            $messagestudent = get_config($plugin, $sm);
            $messagesuper = get_config($plugin, $superm);
            $testuser = get_config($plugin, $testu);
            $contactuser = get_config($plugin, $contactu);
            break;
        }
    }
    if (!$validcat) {
        return;
    }
    $ln = html_writer::start_tag('table', array('width' => '700', 'border' => '1', 'id' => 'registration_form2',
                                  'style' => 'margin-left:20px;'));
    $smln = html_writer::start_tag('table', array('width' => '700', 'border' => '1', 'id' => 'registration_form2',
                                  'style' => 'margin-left:20px;'));
    $dline = html_writer::start_tag('table', array('width' => '800', 'border' => '1', 'id' => 'form2',
                                  'style' => 'margin-left:20px;'));
    $today = date("F j Y");
    $userrec = $DB->get_record('user', array('id' => $userid));
    $lname = $userrec->lastname;
    $fname = $userrec->firstname;
    $emuser = $userrec->email;
    $extra = html_writer::start_tag('tr', array ('BGCOLOR' => '#F5E550')).html_writer::start_tag('td', array( 'colspan' => '3')).
                                    html_writer::start_tag('b').get_string('daterun', $plugin).': '.
                                    html_writer::end_tag('b').$today.html_writer::end_tag('td').
                                    html_writer::end_tag('td').html_writer::end_tag('tr').
                                    html_writer::start_tag('tr').html_writer::end_tag('tr');
    $smln = $smln.$extra.html_writer::start_tag('tr').html_writer::start_tag('td', array('colspan' => '3')).
                         html_writer::start_tag('b').get_string('summary', $plugin).html_writer::end_tag('b').
                         html_writer::end_tag('td').html_writer::end_tag('tr').html_writer::start_tag('tr').
                         html_writer::start_tag('td').html_writer::start_tag('b').$lname.html_writer::end_tag('b').
                         html_writer::end_tag('td').html_writer::start_tag('td').html_writer::start_tag('b').$fname.
                         html_writer::end_tag('b').html_writer::end_tag('td').
                         html_writer::start_tag('td').'&nbsp;'.html_writer::end_tag('td').html_writer::end_tag('tr');
    $dline = $dline.$extra.html_writer::start_tag('tr').html_writer::start_tag('td',  array('colspan' => '3')).
                           html_writer::start_tag('b').get_string('detailed', $plugin).
                           html_writer::end_tag('b').html_writer::end_tag('td').html_writer::end_tag('tr').
                           html_writer::start_tag('tr').html_writer::start_tag('td').html_writer::start_tag('b').
                           $lname.html_writer::end_tag('b').html_writer::end_tag('td').
                           html_writer::start_tag('td').html_writer::start_tag('b').$fname.
                           html_writer::end_tag('b').html_writer::end_tag('td').
                           html_writer::start_tag('td').'&nbsp;'.html_writer::end_tag('td').
                           html_writer::end_tag('tr').html_writer::end_tag('table');

    $nm = html_writer::start_tag('tr', array('BGCOLOR' => '#F5E550')).html_writer::start_tag('td',  array('colspan' => '3')).
          html_writer::start_tag('b').get_string('daterun', $plugin).html_writer::end_tag('b').$today.
          html_writer::end_tag('td').html_writer::end_tag('tr').html_writer::start_tag('tr').html_writer::end_tag('tr');
    $nm = $nm.html_writer::start_tag('tr').html_writer::start_tag('td', array ('colspan' => '3')).
              html_writer::start_tag('b').get_string('name', $plugin).': '.html_writer::end_tag('b').
              $lname .", ".$fname.html_writer::end_tag('td').html_writer::end_tag('tr');
    $ln = $ln .$nm;
    $csvstring = $lname.",".$fname."\n";
    $tottime = 0;
    $courselists = $DB->get_records('course', array ('category' => $categoryid));
    $cnt = 0;
    $progtime = 0;
    $timetaken = 0;
    $maxdatediff = 0;
    foreach ($courselists as $courselist) {
        $cused = 0;
        $cid = $courselist->id;
        $tottime = local_clac_get_course_time($cid);
        $progtime = $progtime + $tottime;
        $csvfile = '';
        $courserec = $DB->get_record('course', array('id' => $cid));
        $cname = $courserec->fullname;
        $cline = get_string('course', $plugin).$cname.",";
        $ql = '';
        $dsum = '<table width="800" border="1" id="form2" style="margin-left:20px;"><tr BGCOLOR="#A9F5BC"><td><b>'.
                  $cname.'</b></td><td>&nbsp;</td>';
        $cnt = $cnt + 1;
        $smln = $smln.'<tr BGCOLOR="#A9F5BC"><td>'.get_string('course').'</td><td>'.$cname.'</td>';
        $enrolm = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $cid));
        $enid = $enrolm->id;
        $enrolq = $DB->get_record('user_enrolments', array('userid' => $userid, 'enrolid' => $enid));
        if ($enrolq) {
            $ts = $enrolq->timestart;
            $now = time();
            $tf = $now - $ts;
            $datediff2 = floor($tf / (60 * 60 * 24));
            if ($datediff2 > $maxdatediff) {
                $maxdatediff = $datediff2;
            }
        }

        $nm = "<tr><td colspan='3'><b>".get_string('course', $plugin).": </b>".$cname ." </td></tr>";
        $cname = str_replace(", ", " ", $cname);
        $ln = $ln. "<tr><td>Resource/Activity:</td><td>Time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>".
               get_string('completed', $plugin)."</td></tr>";
        $ln2 = "Resources,Time,Completed\n";
        $cused = 0;
        $cmax = 0;
        $ln = "Resources,Time,Completed\n";
        $dcourse = '<tr><td>'.get_string('resources', $plugin).'</td><td>'.get_string('time', $plugin).
                   '</td><td>'.get_string('completed', $plugin).'</td></tr>';
        $mods = get_course_mods($cid);
        foreach ($mods as $cm) {
            if (\core_availability\info_module::is_user_visible($cm, $userid)) {
                $modrec = $DB->get_record($cm->modname, array('id' => $cm->instance));
                $modid = $cm->id;
                $compdisp = get_string('activitynotcomplete', $plugin);
                $sqlcomp = $DB->get_record('course_modules_completion', array('userid' => $userid, 'coursemoduleid' => $modid));
                if ($sqlcomp) {
                    $compst = $sqlcomp->completionstate;
                } else {
                    $compst = 0;
                }
                $compdisp = get_string('activitynotcomplete', $plugin);
                $tm = $DB->get_field('customfield_data', 'value', array('instanceid' => $modid, 'fieldid' => $timefield));
                if (!$tm) {
                    $tm = 0;
                }
                if ($compst == 1 ) {
                    $compdisp = get_string('activitycomplete', $plugin);
                    $elpt = $elpt + $tm;
                    $cused = $cused + $tm;
                    $timetaken = $timetaken + $tm;
                }
                $nm = $modrec->name;
                $nm1 = "<tr><td>".$nm."</td><td>".$tm."</td><td>".$compdisp."</td></tr>";
                $ln = $ln.$nm1;
                $nm = str_replace(", ", " ", $nm);
                $ln2 = $ln2.$nm.",".$tm.",".$compdisp."\n";
                $dcourse = $dcourse.'<tr><td>'.$nm.'</td><td>'.$tm.'</td><td>'.$compdisp.'</td></tr>';
                if ($cm->modname == 'quiz') {
                    $quizinstance = $cm->instance;
                    $quizrec = $DB->count_records('quiz_attempts', array('userid' => $userid, 'quiz' => $quizinstance));
                    $gradeitem = $DB->get_field('grade_items', 'id', array('iteminstance' => $quizinstance, 'itemmodule' => 'quiz'));
                    $grade = 0;
                    if ($DB->get_field('grade_grades', 'finalgrade', array('userid' => $userid, 'itemid' => $gradeitem ))) {
                        $graderec = $DB->get_record('grade_grades', array('userid' => $userid, 'itemid' => $gradeitem));
                        $fg = $graderec->finalgrade;
                        $maxgrade = $graderec->rawgrademax;
                        $grade = $fg / $maxgrade;
                        $grade = $grade * 100;
                        $grade = round($grade, 2);
                    }
                    $ql = $ql.'<tr><td><i>'.get_string('quiz', $plugin).':'.$nm.'</i></td><td><i>'.
                              get_string('numberofattempts', $plugin).'</i>'.$quizrec.'</td><td><i>'.
                              get_string('currentgrade', $plugin).': </i>'.$grade.'</td></tr>';
                }
                if ($cm->modname == 'assign' && $mode > 2) {
                    $inst = $cm->instance;
                    $assigns = $DB->get_records('assign_submission', array('userid' => $userid, 'assignment' => $inst,
                                               'status' => 'submitted'));
                    foreach ($assigns as $assign) {
                        $itemid = $assign->id;
                        $files = $DB->get_records('files', array('userid' => $userid, 'itemid' => $itemid));
                        foreach ($files as $file) {
                            $fs = $file->filesize;
                            if ($fs > 0) {
                                $fid = $file->id;
                                $fdcnt = $fdcnt + 1;
                                if ($fdcnt == 1) {
                                    $fdlist = $fid;
                                } else {
                                    $fdlist = $fdlist.",".$fid;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($tottime == '0') {
            $ccomp = 0;
        } else {
            $ccomp = $cused / $tottime;
        }
        $ccomp = round($ccomp, 2);
        $ccomp = $ccomp * 100;
        $cline = $cline.$ccomp."%\n";
        $cline = $cline.$ln2;
        $smln = $smln.html_writer::start_tag('td').$ccomp.'%'.html_writer::end_tag('td').html_writer::start_tag('tr').$ql;
        $dsum = $dsum.html_writer::start_tag('td').$ccomp.'%'.html_writer::end_tag('td').html_writer::end_tag('tr');
        $dline = $dline.$dsum.$dcourse.html_writer::end_tag('table')."<br><br>";

        $csvstring = $csvstring.$cline;
        $csvstring = $csvstring.get_string('daysenrolled', $plugin) ." ,".$maxdatediff."\n";
        if ($tottime == 0) {
            $pcomp = 0;
        } else {
            if ($elpt > $tottime) {
                $pcomp = 1;
            } else {
                $pcomp = $elpt / $tottime;
            }
        }
        $pcomp = round($pcomp, 2);
        $pcomp = $pcomp * 100;
        $pcomp = trim($pcomp);
        $ln = $ln."<tr><td><b>".get_string('coursetime', $plugin)." :</b></td><td>".$tottime."</td>
                <td colspan='2'> ".get_string('percentcomplete', $plugin)." : ".$pcomp."</td></tr>";
        $csvstring = $csvstring.get_string('coursetime', $plugin).$tottime .get_string('percentcomplete', $plugin).$pcomp."\n";

        $ln = $ln. "</table>";
    }
    if ($progtime == 0) {
        $pcomp = 0;
    } else {
        $pcomp = $timetaken / $progtime;
        $pcomp = round($pcomp, 2);
        $pcomp = $pcomp * 100;
    }
    $smln = $smln.html_writer::start_tag('tr').html_writer::start_tag('td', array('colspan' => '3'));
    $smln = $smln.'&nbsp; &nbsp; &nbsp';
    $smln = $smln.html_writer::end_tag('td').html_writer::end_tag('tr');
    $smln = $smln.html_writer::start_tag('tr').html_writer::start_tag('td', array('colspan' => '2'));
    $smln = $smln.get_string('percentcomplete', $plugin).html_writer::end_tag('td');
    $smln = $smln.html_writer::start_tag('td').$pcomp.'%'.html_writer::end_tag('td').html_writer::end_tag('tr');
    $smln = $smln.html_writer::start_tag('tr').html_writer::start_tag('td', array('colspan' => '2'));
    $smln = $smln.get_string('daysenrolled', $plugin).':'.html_writer::end_tag('td');
    $smln = $smln.html_writer::start_tag('td').$maxdatediff.html_writer::end_tag('td').html_writer::end_tag('tr');
    $smln = $smln.html_writer::end_tag('table');
    $dline = $dline.html_writer::end_tag('table');

    $smln = '<html>'.$smln.'<br><br>'.$dline.'</html>';
    if ($mode == 1) {
        return $smln;
    }
    $fullname = $fname ." ".$lname;
    $xlname = $fname."_".$lname;
    $emailsubject = get_string('subject', $plugin). $fullname;
    $email = get_config($plugin, 'sendingemail');
    $headers = "From: $email";
    // Boundary.
    $semirand = md5(time());
    $mimeboundary = "==Multipart_Boundary_x{$semirand}x";
    $uid = md5(uniqid(time()));
    $from = get_config($plugin, 'sendingemail');
    $headers = "From: ".$from." <".$from.">\n";
    $headers .= "Reply-To: ".$from."\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\n\n";
    $fn = $xlname.".html";
    $data = $smln;
    $tf = $CFG->dataroot.'//temp//'.$userid;
    $cfile = fopen($tf, "w") or die ("unable to open file");
    $fs = fwrite($cfile, $data);
    fclose($cfile);
    $filetoattach = $data;
    $emailuser = $DB->get_record('user', array ('id' => $userid));
    $from = new stdClass();
    $from = get_config('local_clac', 'sendingemail');
    $attachfiles = array();
    $attachfilename = array();
    $attachname[] = $fn;
    $attachfiles[] = $tf;
    if ($mode > 2 ) {
        if ($fdcnt > 0 ) {
            $flist = explode(",", $fdlist);
            foreach ($flist as $fd) {
                $fd = trim($fd);
                $sqlfile = $DB->get_record('files', array('id' => $fd));
                if ($sqlfile) {
                    $fa = $sqlfile->filearea;
                    $item = $sqlfile->itemid;
                    $fp = $sqlfile->filepath;
                    $fn = $sqlfile->filename;
                    $mtype = $sqlfile->mimetype;
                    $phash = $sqlfile->contenthash;
                    $f0 = substr($phash, 0, 2);
                    $f1 = substr($phash, 2, 2);
                    $pname = $CFG->dataroot."/filedir/";
                    $pname = $pname."/".$f0."/".$f1."/".$phash;
                    $file = fopen($pname, "rb");
                    $data = fread($file, filesize($pname));
                    fclose($file);
                    $filetoattach = $pname;
                    $attachname[] = $fn;
                    $attachfiles[] = $pname;
                }
            }
        }
    }

    $fromaddress = \core_user::get_noreply_user();
    $fromaddress->email = $from;
    if ($pcomp < '100') {
        $emailuser->email = $emuser;
        if ($mode == 3) {
            $emusercontact = $DB->get_field('user', 'email', array('id' => $contactuser));
            if ($emusercontact) {
                $emailuser->email = $emusercontact;
            }
        }
        $emailuser->email = $emuser;
        if ($mode > 2) {
            mtrace ('emailing report to '.$emailuser->email);
        }
        local_clac_email_to_user($emailuser, $fromaddress, $emailsubject,
               $messagestudent, $messagestudent, $attachfiles, $attachname );
        // Check for supervisors.
        if ($mode == 5) {
            $contextid = $DB->get_field('context', 'id', array('contextlevel' => '30', 'instanceid' => $userid));
            if ($contextid) {
                $sqlsup = "SELECT ra.id AS raid,u.id,u.firstname,u.lastname,u.email,ra.contextid,ra.component
                          FROM {role_assignments} ra
                          JOIN {user} u ON u.id = ra.userid
                          JOIN {context} ctx ON ra.contextid = ctx.id
                         WHERE u.id <> 1
                           AND u.deleted = 0
                           AND u.confirmed = 1
                           AND ctx.id IN (".$contextid.",1)
                           AND ra.roleid = 9
                      ORDER BY ctx.depth DESC, ra.component, u.lastname, u.firstname, u.id";

                $supervisors = $DB->get_records_sql($sqlsup, array());
                if ($supervisors) {
                    foreach ($supervisors as $supervisor) {
                        $ctname = $supervisor->firstname.' '.$supervisor->lastname;
                        $ctemail = $supervisor->email;
                        $emailuser->email = $ctemail;
                        local_clac_email_to_user($emailuser, $fromaddress, $emailsubject,
                        $messagesuper, $messagesuper, $attachfiles, $attachname );
                    }
                }
            }
        }
    }
    if ($mode == 2 ) {
        return $smln;
    } else {
        return;
    }
}

/**
 * Run the monthly report. Called from the cron.
 *
 */
function local_clac_reporttask_cron() {
    global $DB;
    mtrace ('Starting monthly report');
    $plugin = 'local_clac';
    for ($i = 1; $i <= 5; $i++) {
        $catfield = 'catfield'.$i;
        $catvalue = get_config($plugin, $catfield);
        if ($catvalue > 0) {
            // Get all students in all courses in the category.
            $userarray  = array();
            $courselists = $DB->get_records('course', array ('category' => $catvalue, 'visible' => '1'));
            foreach ($courselists as $courselist) {
                $cid = $courselist->id;
                $role = $DB->get_record("role", array("shortname" => 'student'));
                $course = $DB->get_record("course", array("id" => $cid));
                $context = context_course::instance($cid);
                $userlist = get_role_users($role->id, $context);
                foreach ($userlist as $user) {
                    $userid = $user->id;
                    if (!in_array($userid, $userarray)) {
                        $userarray[] = $userid;
                    }
                }
            }
            foreach ($userarray as $users) {
                mtrace ('Generating monthly report for '.$users);
                local_clac_generate_report($users, $catvalue, 5);
            }
        }
    }
    mtrace('Ending monthly report');
}


/**
 * Run the test of the monthly report. Called from the cron.
 *
 */
function local_clac_testreporttask_cron() {
    mtrace('Starting test report');
    $plugin = 'local_clac';
    for ($i = 1; $i <= 5; $i++) {
        $catfield = 'catfield'.$i;
        $catvalue = get_config($plugin, $catfield);
        if ($catvalue > 0) {
            $testu = 'testuser'.$i;
            $contactu = 'contactuser'.$i;
            $testuser = get_config($plugin, $testu);
            $contactuser = get_config($plugin, $contactu);
            if ($testuser > 0 && $contactuser > 0) {
                mtrace('Generating test report for category '.$catvalue);
                local_clac_generate_report($testuser, $catvalue, 3);
            }
        }
    }
    mtrace ('Ending test report');
}
/**
 * Send an email to a specified user
 *
 * Note - this is more or less a copy of core email_to_user but allows multiple attachments.
 * @param stdClass $user  A {@link $USER} object
 * @param stdClass $from A {@link $USER} object
 * @param string $subject plain text subject line of the email
 * @param string $messagetext plain text version of the message
 * @param string $messagehtml complete html version of the message (optional)
 * @param string $attachment a file on the filesystem, either relative to $CFG->dataroot or a full path to a file in one of
 *          the following directories: $CFG->cachedir, $CFG->dataroot, $CFG->dirroot, $CFG->localcachedir, $CFG->tempdir
 * @param string $attachname the name of the file (extension indicates MIME)
 * @param bool $usetrueaddress determines whether $from email address should
 *          be sent out. Will be overruled by user profile setting for maildisplay
 * @param string $replyto Email address to reply to
 * @param string $replytoname Name of reply to recipient
 * @param int $wordwrapwidth custom word wrap width, default 79
 * @return bool Returns true if mail was sent OK and false if there was an error.
 */
function local_clac_email_to_user($user, $from, $subject, $messagetext, $messagehtml = '', $attachment = '', $attachname = '',
                       $usetrueaddress = true, $replyto = '', $replytoname = '', $wordwrapwidth = 79) {

    global $CFG, $PAGE, $SITE;

    if (empty($user) or empty($user->id)) {
        debugging('Can not send email to null user', DEBUG_DEVELOPER);
        return false;
    }

    if (empty($user->email)) {
        debugging('Can not send email to user without email: '.$user->id, DEBUG_DEVELOPER);
        return false;
    }

    if (!empty($user->deleted)) {
        debugging('Can not send email to deleted user: '.$user->id, DEBUG_DEVELOPER);
        return false;
    }

    if (defined('BEHAT_SITE_RUNNING')) {
        // Fake email sending in behat.
        return true;
    }

    if (!empty($CFG->noemailever)) {
        // Hidden setting for development sites, set in config.php if needed.
        debugging('Not sending email due to $CFG->noemailever config setting', DEBUG_NORMAL);
        return true;
    }

    if (email_should_be_diverted($user->email)) {
        $subject = "[DIVERTED {$user->email}] $subject";
        $user = clone($user);
        $user->email = $CFG->divertallemailsto;
    }

    // Skip mail to suspended users.
    if ((isset($user->auth) && $user->auth == 'nologin') or (isset($user->suspended) && $user->suspended)) {
        return true;
    }

    if (!validate_email($user->email)) {
        // We can not send emails to invalid addresses - it might create security issue or confuse the mailer.
        debugging("email_to_user: User $user->id (".fullname($user).") email ($user->email) is invalid! Not sending.");
        return false;
    }

    if (over_bounce_threshold($user)) {
        debugging("email_to_user: User $user->id (".fullname($user).") is over bounce threshold! Not sending.");
        return false;
    }

    // TLD .invalid  is specifically reserved for invalid domain names.
    // For More information, see {@link http://tools.ietf.org/html/rfc2606#section-2}.
    if (substr($user->email, -8) == '.invalid') {
        debugging("email_to_user: User $user->id (".fullname($user).") email domain ($user->email) is invalid! Not sending.");
        return true; // This is not an error.
    }

    // If the user is a remote mnet user, parse the email text for URL to the
    // wwwroot and modify the url to direct the user's browser to login at their
    // home site (identity provider - idp) before hitting the link itself.
    if (is_mnet_remote_user($user)) {
        require_once($CFG->dirroot.'/mnet/lib.php');

        $jumpurl = mnet_get_idp_jump_url($user);
        $callback = partial('mnet_sso_apply_indirection', $jumpurl);

        $messagetext = preg_replace_callback("%($CFG->wwwroot[^[:space:]]*)%",
                $callback,
                $messagetext);
        $messagehtml = preg_replace_callback("%href=[\"'`]($CFG->wwwroot[\w_:\?=#&@/;.~-]*)[\"'`]%",
                $callback,
                $messagehtml);
    }
    $mail = get_mailer();

    if (!empty($mail->SMTPDebug)) {
        echo '<pre>' . "\n";
    }

    $temprecipients = array();
    $tempreplyto = array();

    // Make sure that we fall back onto some reasonable no-reply address.
    $noreplyaddressdefault = 'noreply@' . get_host_from_url($CFG->wwwroot);
    $noreplyaddress = empty($CFG->noreplyaddress) ? $noreplyaddressdefault : $CFG->noreplyaddress;

    if (!validate_email($noreplyaddress)) {
        debugging('email_to_user: Invalid noreply-email '.s($noreplyaddress));
        $noreplyaddress = $noreplyaddressdefault;
    }

    // Make up an email address for handling bounces.
    if (!empty($CFG->handlebounces)) {
        $modargs = 'B'.base64_encode(pack('V', $user->id)).substr(md5($user->email), 0, 16);
        $mail->Sender = generate_email_processing_address(0, $modargs);
    } else {
        $mail->Sender = $noreplyaddress;
    }

    // Make sure that the explicit replyto is valid, fall back to the implicit one.
    if (!empty($replyto) && !validate_email($replyto)) {
        debugging('email_to_user: Invalid replyto-email '.s($replyto));
        $replyto = $noreplyaddress;
    }

    if (is_string($from)) { // So we can pass whatever we want if there is need.
        $mail->From     = $noreplyaddress;
        $mail->FromName = $from;
        // Check if using the true address is true, and the email is in the list of allowed domains for sending email,
        // and that the senders email setting is either displayed to everyone, or display to only other users that are enrolled
        // in a course with the sender.
    } else if ($usetrueaddress && can_send_from_real_email_address($from, $user)) {
        if (!validate_email($from->email)) {
            debugging('email_to_user: Invalid from-email '.s($from->email).' - not sending');
            // Better not to use $noreplyaddress in this case.
            return false;
        }
        $mail->From = $from->email;
        $fromdetails = new stdClass();
        $fromdetails->name = fullname($from);
        $fromdetails->url = preg_replace('#^https?://#', '', $CFG->wwwroot);
        $fromdetails->siteshortname = format_string($SITE->shortname);
        $fromstring = $fromdetails->name;
        if ($CFG->emailfromvia == EMAIL_VIA_ALWAYS) {
            $fromstring = get_string('emailvia', 'core', $fromdetails);
        }
        $mail->FromName = $fromstring;
        if (empty($replyto)) {
            $tempreplyto[] = array($from->email, fullname($from));
        }
    } else {
        $mail->From = $noreplyaddress;
        $fromdetails = new stdClass();
        $fromdetails->name = fullname($from);
        $fromdetails->url = preg_replace('#^https?://#', '', $CFG->wwwroot);
        $fromdetails->siteshortname = format_string($SITE->shortname);
        $fromstring = $fromdetails->name;
        if ($CFG->emailfromvia != EMAIL_VIA_NEVER) {
            $fromstring = get_string('emailvia', 'core', $fromdetails);
        }
        $mail->FromName = $fromstring;
        if (empty($replyto)) {
            $tempreplyto[] = array($noreplyaddress, get_string('noreplyname'));
        }
    }

    if (!empty($replyto)) {
        $tempreplyto[] = array($replyto, $replytoname);
    }

    $temprecipients[] = array($user->email, fullname($user));

    // Set word wrap.
    $mail->WordWrap = $wordwrapwidth;

    if (!empty($from->customheaders)) {
        // Add custom headers.
        if (is_array($from->customheaders)) {
            foreach ($from->customheaders as $customheader) {
                $mail->addCustomHeader($customheader);
            }
        } else {
            $mail->addCustomHeader($from->customheaders);
        }
    }

    // If the X-PHP-Originating-Script email header is on then also add an additional
    // header with details of where exactly in moodle the email was triggered from,
    // either a call to message_send() or to email_to_user().
    if (ini_get('mail.add_x_header')) {

        $stack = debug_backtrace(false);
        $origin = $stack[0];

        foreach ($stack as $depth => $call) {
            if ($call['function'] == 'message_send') {
                $origin = $call;
            }
        }

        $originheader = $CFG->wwwroot . ' => ' . gethostname() . ':'
             . str_replace($CFG->dirroot . '/', '', $origin['file']) . ':' . $origin['line'];
        $mail->addCustomHeader('X-Moodle-Originating-Script: ' . $originheader);
    }

    if (!empty($from->priority)) {
        $mail->Priority = $from->priority;
    }

    $renderer = $PAGE->get_renderer('core');
    $context = array(
        'sitefullname' => $SITE->fullname,
        'siteshortname' => $SITE->shortname,
        'sitewwwroot' => $CFG->wwwroot,
        'subject' => $subject,
        'prefix' => $CFG->emailsubjectprefix,
        'to' => $user->email,
        'toname' => fullname($user),
        'from' => $mail->From,
        'fromname' => $mail->FromName,
    );
    if (!empty($tempreplyto[0])) {
        $context['replyto'] = $tempreplyto[0][0];
        $context['replytoname'] = $tempreplyto[0][1];
    }
    if ($user->id > 0) {
        $context['touserid'] = $user->id;
        $context['tousername'] = $user->username;
    }

    if (!empty($user->mailformat) && $user->mailformat == 1) {
        // Only process html templates if the user preferences allow html email.

        if (!$messagehtml) {
            // If no html has been given, BUT there is an html wrapping template then
            // auto convert the text to html and then wrap it.
            $messagehtml = trim(text_to_html($messagetext));
        }
        $context['body'] = $messagehtml;
        $messagehtml = $renderer->render_from_template('core/email_html', $context);
    }

    $context['body'] = html_to_text(nl2br($messagetext));
    $mail->Subject = $renderer->render_from_template('core/email_subject', $context);
    $mail->FromName = $renderer->render_from_template('core/email_fromname', $context);
    $messagetext = $renderer->render_from_template('core/email_text', $context);

    // Autogenerate a MessageID if it's missing.
    if (empty($mail->MessageID)) {
        $mail->MessageID = generate_email_messageid();
    }

    if ($messagehtml && !empty($user->mailformat) && $user->mailformat == 1) {
        // Don't ever send HTML to users who don't want it.
        $mail->isHTML(true);
        $mail->Encoding = 'quoted-printable';
        $mail->Body = $messagehtml;
        $mail->AltBody = "\n$messagetext\n";
    } else {
        $mail->IsHTML(false);
        $mail->Body = "\n$messagetext\n";
    }

    // Handle multiple attachments.
    if (!is_array($attachment) && ($attachment && $attachname)) {
         // Cast a single attachment as an array.
         $attachment[$attachname] = $attachment;
    }
    if (is_array($attachment)) {
        foreach ($attachment as $attachname => $attachlocation) {
            if (preg_match( "~\\.\\.~" , $attachlocation)) {
                $temprecipients[] = array($supportuser->email, fullname($supportuser, true));
                $mail->AddStringAttachment('Error in attachment.  User attempted to attach a filename with a unsafe name.
                                            ', 'error.txt', '8bit', 'text/plain');
            } else {
                require_once($CFG->libdir.'/filelib.php');
                $mimetype = mimeinfo('type', $attachname);
                $mail->AddAttachment($CFG->dataroot .'/'. $attachlocation, $attachname, 'base64', $mimetype);
            }
        }
    }

    // Check if the email should be sent in an other charset then the default UTF-8.
    if ((!empty($CFG->sitemailcharset) || !empty($CFG->allowusermailcharset))) {

        // Use the defined site mail charset or eventually the one preferred by the recipient.
        $charset = $CFG->sitemailcharset;
        if (!empty($CFG->allowusermailcharset)) {
            if ($useremailcharset = get_user_preferences('mailcharset', '0', $user->id)) {
                $charset = $useremailcharset;
            }
        }

        // Convert all the necessary strings if the charset is supported.
        $charsets = get_list_of_charsets();
        unset($charsets['UTF-8']);
        if (in_array($charset, $charsets)) {
            $mail->CharSet  = $charset;
            $mail->FromName = core_text::convert($mail->FromName, 'utf-8', strtolower($charset));
            $mail->Subject  = core_text::convert($mail->Subject, 'utf-8', strtolower($charset));
            $mail->Body     = core_text::convert($mail->Body, 'utf-8', strtolower($charset));
            $mail->AltBody  = core_text::convert($mail->AltBody, 'utf-8', strtolower($charset));

            foreach ($temprecipients as $key => $values) {
                $temprecipients[$key][1] = core_text::convert($values[1], 'utf-8', strtolower($charset));
            }
            foreach ($tempreplyto as $key => $values) {
                $tempreplyto[$key][1] = core_text::convert($values[1], 'utf-8', strtolower($charset));
            }
        }
    }

    foreach ($temprecipients as $values) {
        $mail->addAddress($values[0], $values[1]);
    }
    foreach ($tempreplyto as $values) {
        $mail->addReplyTo($values[0], $values[1]);
    }

    if ($mail->send()) {
        set_send_count($user);
        if (!empty($mail->SMTPDebug)) {
            echo '</pre>';
        }
        return true;
    } else {
        // Trigger event for failing to send email.
        $event = \core\event\email_failed::create(array(
            'context' => context_system::instance(),
            'userid' => $from->id,
            'relateduserid' => $user->id,
            'other' => array(
                'subject' => $subject,
                'message' => $messagetext,
                'errorinfo' => $mail->ErrorInfo
            )
        ));
        $event->trigger();
        if (CLI_SCRIPT) {
            mtrace('Error: lib/moodlelib.php email_to_user(): '.$mail->ErrorInfo);
        }
        if (!empty($mail->SMTPDebug)) {
            echo '</pre>';
        }
        return false;
    }
}

