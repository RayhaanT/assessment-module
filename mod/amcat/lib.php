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
 * Standard library of functions and constants for amcat
 *
 * @package mod_amcat
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

// Event types.
define('amcat_EVENT_TYPE_OPEN', 'open');
define('amcat_EVENT_TYPE_CLOSE', 'close');

/* Do not include any libraries here! */





/**
 * Return data of test schuldeing api.
 *
 * @param string $url API URL.
 * @param array $postData Post Data Array
 * @return Data | false if Data is getting from myamcat.
 */
function testschuldeAPI($url, $postData){
    $curl_handle = curl_init();
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Api-AuthToken: MjMxNDk2OGZiMDIxMGJiZGY1YzNiODU3MWQ2MWI0NWI='
    ];
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_POST, true);
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
    $buffer = curl_exec($curl_handle);
    $data = json_decode($buffer,true);
    curl_close($curl_handle);

    if(!empty($data['status'])){
        return $data;
    }
    return false;
}
/**
 * Get Based URL of Myamcat
 *
 */
function getBaseURL_myamcat() {
    return 'https://ampartnerstg.myamcat.com/';
}

/**
 * Get Auth key.
 *
 */
function getAuthKey(){
    return 'MjMxNDk2OGZiMDIxMGJiZGY1YzNiODU3MWQ2MWI0NWI=';
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @global object
 * @param object $amcat amcat post data from the form
 * @return int
 **/
function amcat_add_instance($data, $mform) {
    global $DB;

    $cmid = $data->coursemodule;
    $draftitemid = '';
    $context = context_module::instance($cmid);

    amcat_process_pre_save($data);

    unset($data->mediafile);
    $amcatid = $DB->insert_record("amcat", $data);
    $data->id = $amcatid;

    amcat_update_media_file($amcatid, $context, $draftitemid);

    amcat_process_post_save($data);

    amcat_grade_item_update($data);

    return $amcatid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $amcat amcat post data from the form
 * @return boolean
 **/
function amcat_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $cmid = $data->coursemodule;
    $draftitemid = $data->mediafile;
    $context = context_module::instance($cmid);

    amcat_process_pre_save($data);

    unset($data->mediafile);
    $DB->update_record("amcat", $data);

    amcat_update_media_file($data->id, $context, $draftitemid);

    amcat_process_post_save($data);

    // update grade item definition
    amcat_grade_item_update($data);

    // update grades - TODO: do it only when grading style changes
    amcat_update_grades($data, 0, false);

    return true;
}

/**
 * This function updates the events associated to the amcat.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @uses amcat_MAX_EVENT_LENGTH
 * @param object $amcat the amcat object.
 * @param object $override (optional) limit to a specific override
 */
function amcat_update_events($amcat, $override = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/amcat/locallib.php');
    require_once($CFG->dirroot . '/calendar/lib.php');

    // Load the old events relating to this amcat.
    $conds = array('modulename' => 'amcat',
                   'instance' => $amcat->id);
    if (!empty($override)) {
        // Only load events for this override.
        if (isset($override->userid)) {
            $conds['userid'] = $override->userid;
        } else {
            $conds['groupid'] = $override->groupid;
        }
    }
    $oldevents = $DB->get_records('event', $conds, 'id ASC');

    // Now make a to-do list of all that needs to be updated.
    if (empty($override)) {
        // We are updating the primary settings for the amcat, so we need to add all the overrides.
        $overrides = $DB->get_records('amcat_overrides', array('amcatid' => $amcat->id), 'id ASC');
        // It is necessary to add an empty stdClass to the beginning of the array as the $oldevents
        // list contains the original (non-override) event for the module. If this is not included
        // the logic below will end up updating the wrong row when we try to reconcile this $overrides
        // list against the $oldevents list.
        array_unshift($overrides, new stdClass());
    } else {
        // Just do the one override.
        $overrides = array($override);
    }

    // Get group override priorities.
    $grouppriorities = amcat_get_group_override_priorities($amcat->id);

    foreach ($overrides as $current) {
        $groupid   = isset($current->groupid) ? $current->groupid : 0;
        $userid    = isset($current->userid) ? $current->userid : 0;
        $available  = '';
        $deadline = '';

        // Only add open/close events for an override if they differ from the amcat default.
        $addopen  = empty($current->id) || !empty($current->available);
        $addclose = empty($current->id) || !empty($current->deadline);

        if (!empty($amcat->coursemodule)) {
            $cmid = $amcat->coursemodule;
        } else {
            $cmid = get_coursemodule_from_instance('amcat', $amcat->id, $amcat->course)->id;
        }

        $event = new stdClass();
        $event->type = !$deadline ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->description = format_module_intro('amcat', $amcat, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $amcat->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'amcat';
        $event->instance    = $amcat->id;
        $event->timestart   = $available;
        $event->timeduration = 0;
        $event->timesort    = $available;
        $event->visible     = instance_is_visible('amcat', $amcat);
        $event->eventtype   = amcat_EVENT_TYPE_OPEN;
        $event->priority    = null;

        // Determine the event name and priority.
        if ($groupid) {
            // Group override event.
            $params = new stdClass();
            $params->amcat = $amcat->name;
            $params->group = groups_get_group_name($groupid);
            if ($params->group === false) {
                // Group doesn't exist, just skip it.
                continue;
            }
            $eventname = get_string('overridegroupeventname', 'amcat', $params);
            // Set group override priority.
            if ($grouppriorities !== null) {
                $openpriorities = $grouppriorities['open'];
                if (isset($openpriorities[$available])) {
                    $event->priority = $openpriorities[$available];
                }
            }
        } else if ($userid) {
            // User override event.
            $params = new stdClass();
            $params->amcat = $amcat->name;
            $eventname = get_string('overrideusereventname', 'amcat', $params);
            // Set user override priority.
            $event->priority = CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
        } else {
            // The parent event.
            $eventname = $amcat->name;
        }

        if ($addopen or $addclose) {
            // Separate start and end events.
            $event->timeduration  = 0;
            if ($available && $addopen) {
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->name = get_string('amcateventopens', 'amcat', $eventname);
                // The method calendar_event::create will reuse a db record if the id field is set.
                calendar_event::create($event, false);
            }
            if ($deadline && $addclose) {
                if ($oldevent = array_shift($oldevents)) {
                    $event->id = $oldevent->id;
                } else {
                    unset($event->id);
                }
                $event->type      = CALENDAR_EVENT_TYPE_ACTION;
                $event->name      = get_string('amcateventcloses', 'amcat', $eventname);
                $event->timestart = $deadline;
                $event->timesort  = $deadline;
                $event->eventtype = amcat_EVENT_TYPE_CLOSE;
                if ($groupid && $grouppriorities !== null) {
                    $closepriorities = $grouppriorities['close'];
                    if (isset($closepriorities[$deadline])) {
                        $event->priority = $closepriorities[$deadline];
                    }
                }
                calendar_event::create($event, false);
            }
        }
    }

    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
    }
}

/**
 * Calculates the priorities of timeopen and timeclose values for group overrides for a amcat.
 *
 * @param int $amcatid The amcat ID.
 * @return array|null Array of group override priorities for open and close times. Null if there are no group overrides.
 */
function amcat_get_group_override_priorities($amcatid) {
    global $DB;

    // Fetch group overrides.
    $where = 'amcatid = :amcatid AND groupid IS NOT NULL';
    $params = ['amcatid' => $amcatid];
    $overrides = $DB->get_records_select('amcat_overrides', $where, $params, '', 'id, groupid, available, deadline');
    if (!$overrides) {
        return null;
    }

    $grouptimeopen = [];
    $grouptimeclose = [];
    foreach ($overrides as $override) {
        if ($override->available !== null && !in_array($override->available, $grouptimeopen)) {
            $grouptimeopen[] = $override->available;
        }
        if ($override->deadline !== null && !in_array($override->deadline, $grouptimeclose)) {
            $grouptimeclose[] = $override->deadline;
        }
    }

    // Sort open times in ascending manner. The earlier open time gets higher priority.
    sort($grouptimeopen);
    // Set priorities.
    $opengrouppriorities = [];
    $openpriority = 1;
    foreach ($grouptimeopen as $timeopen) {
        $opengrouppriorities[$timeopen] = $openpriority++;
    }

    // Sort close times in descending manner. The later close time gets higher priority.
    rsort($grouptimeclose);
    // Set priorities.
    $closegrouppriorities = [];
    $closepriority = 1;
    foreach ($grouptimeclose as $timeclose) {
        $closegrouppriorities[$timeclose] = $closepriority++;
    }

    return [
        'open' => $opengrouppriorities,
        'close' => $closegrouppriorities
    ];
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every amcat event in the site is checked, else
 * only amcat events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance amcat module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function amcat_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('amcat', array('id' => $instance), '*', MUST_EXIST);
        }
        amcat_update_events($instance);
        return true;
    }

    if ($courseid == 0) {
        if (!$amcats = $DB->get_records('amcat')) {
            return true;
        }
    } else {
        if (!$amcats = $DB->get_records('amcat', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($amcats as $amcat) {
        amcat_update_events($amcat);
    }

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function amcat_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/amcat/locallib.php');

    $amcat = $DB->get_record("amcat", array("id"=>$id), '*', MUST_EXIST);
    $amcat = new amcat($amcat);
    return $amcat->delete();
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $amcat
 * @return object
 */
function amcat_user_outline($course, $user, $mod, $amcat) {
    global $CFG, $DB;

    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'amcat', $amcat->id, $user->id);
    $return = new stdClass();

    if (empty($grades->items[0]->grades)) {
        $return->info = get_string("noamcatattempts", "amcat");
    } else {
        $grade = reset($grades->items[0]->grades);
        if (empty($grade->grade)) {

            // Check to see if it an ungraded / incomplete attempt.
            $sql = "SELECT *
                      FROM {amcat_timer}
                     WHERE amcatid = :amcatid
                       AND userid = :userid
                  ORDER BY starttime DESC";
            $params = array('amcatid' => $amcat->id, 'userid' => $user->id);

            if ($attempts = $DB->get_records_sql($sql, $params, 0, 1)) {
                $attempt = reset($attempts);
                if ($attempt->completed) {
                    $return->info = get_string("completed", "amcat");
                } else {
                    $return->info = get_string("notyetcompleted", "amcat");
                }
                $return->time = $attempt->amcattime;
            } else {
                $return->info = get_string("noamcatattempts", "amcat");
            }
        } else {
            if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
                $return->info = get_string('grade') . ': ' . $grade->str_long_grade;
            } else {
                $return->info = get_string('grade') . ': ' . get_string('hidden', 'grades');
            }

            $return->time = grade_get_date_for_user_grade($grade, $user);
        }
    }
    return $return;
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $amcat
 * @return bool
 */
function amcat_user_complete($course, $user, $mod, $amcat) {
    global $DB, $OUTPUT, $CFG;

    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'amcat', $amcat->id, $user->id);

    // Display the grade and feedback.
    if (empty($grades->items[0]->grades)) {
        echo $OUTPUT->container(get_string("noamcatattempts", "amcat"));
    } else {
        $grade = reset($grades->items[0]->grades);
        if (empty($grade->grade)) {
            // Check to see if it an ungraded / incomplete attempt.
            $sql = "SELECT *
                      FROM {amcat_timer}
                     WHERE amcatid = :amcatid
                       AND userid = :userid
                     ORDER by starttime desc";
            $params = array('amcatid' => $amcat->id, 'userid' => $user->id);

            if ($attempt = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE)) {
                if ($attempt->completed) {
                    $status = get_string("completed", "amcat");
                } else {
                    $status = get_string("notyetcompleted", "amcat");
                }
            } else {
                $status = get_string("noamcatattempts", "amcat");
            }
        } else {
            if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
                $status = get_string("grade") . ': ' . $grade->str_long_grade;
            } else {
                $status = get_string('grade') . ': ' . get_string('hidden', 'grades');
            }
        }

        // Display the grade or amcat status if there isn't one.
        echo $OUTPUT->container($status);

        if ($grade->str_feedback &&
            (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id)))) {
            echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
        }
    }

    // Display the amcat progress.
    // Attempt, pages viewed, questions answered, correct answers, time.
    $params = array ("amcatid" => $amcat->id, "userid" => $user->id);
    $attempts = $DB->get_records_select("amcat_attempts", "amcatid = :amcatid AND userid = :userid", $params, "retry, timeseen");
    $branches = $DB->get_records_select("amcat_branch", "amcatid = :amcatid AND userid = :userid", $params, "retry, timeseen");
    if (!empty($attempts) or !empty($branches)) {
        echo $OUTPUT->box_start();
        $table = new html_table();
        // Table Headings.
        $table->head = array (get_string("attemptheader", "amcat"),
            get_string("totalpagesviewedheader", "amcat"),
            get_string("numberofpagesviewedheader", "amcat"),
            get_string("numberofcorrectanswersheader", "amcat"),
            get_string("time"));
        $table->width = "100%";
        $table->align = array ("center", "center", "center", "center", "center");
        $table->size = array ("*", "*", "*", "*", "*");
        $table->cellpadding = 2;
        $table->cellspacing = 0;

        $retry = 0;
        $nquestions = 0;
        $npages = 0;
        $ncorrect = 0;

        // Filter question pages (from amcat_attempts).
        foreach ($attempts as $attempt) {
            if ($attempt->retry == $retry) {
                $npages++;
                $nquestions++;
                if ($attempt->correct) {
                    $ncorrect++;
                }
                $timeseen = $attempt->timeseen;
            } else {
                $table->data[] = array($retry + 1, $npages, $nquestions, $ncorrect, userdate($timeseen));
                $retry++;
                $nquestions = 1;
                $npages = 1;
                if ($attempt->correct) {
                    $ncorrect = 1;
                } else {
                    $ncorrect = 0;
                }
            }
        }

        // Filter content pages (from amcat_branch).
        foreach ($branches as $branch) {
            if ($branch->retry == $retry) {
                $npages++;

                $timeseen = $branch->timeseen;
            } else {
                $table->data[] = array($retry + 1, $npages, $nquestions, $ncorrect, userdate($timeseen));
                $retry++;
                $npages = 1;
            }
        }
        if ($npages > 0) {
                $table->data[] = array($retry + 1, $npages, $nquestions, $ncorrect, userdate($timeseen));
        }
        echo html_writer::table($table);
        echo $OUTPUT->box_end();
    }

    return true;
}

/**
 * @deprecated since Moodle 3.3, when the block_course_overview block was removed.
 */
function amcat_print_overview() {
    throw new coding_exception('amcat_print_overview() can not be used any more and is obsolete.');
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @global stdClass
 * @return bool true
 */
function amcat_cron () {
    global $CFG;

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $amcatid id of amcat
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function amcat_get_user_grades($amcat, $userid=0) {
    global $CFG, $DB;

    $params = array("amcatid" => $amcat->id,"amcatid2" => $amcat->id);

    if (!empty($userid)) {
        $params["userid"] = $userid;
        $params["userid2"] = $userid;
        $user = "AND u.id = :userid";
        $fuser = "AND uu.id = :userid2";
    }
    else {
        $user="";
        $fuser="";
    }

    if ($amcat->retake) {
        if ($amcat->usemaxgrade) {
            $sql = "SELECT u.id, u.id AS userid, MAX(g.grade) AS rawgrade
                      FROM {user} u, {amcat_grades} g
                     WHERE u.id = g.userid AND g.amcatid = :amcatid
                           $user
                  GROUP BY u.id";
        } else {
            $sql = "SELECT u.id, u.id AS userid, AVG(g.grade) AS rawgrade
                      FROM {user} u, {amcat_grades} g
                     WHERE u.id = g.userid AND g.amcatid = :amcatid
                           $user
                  GROUP BY u.id";
        }
        unset($params['amcatid2']);
        unset($params['userid2']);
    } else {
        // use only first attempts (with lowest id in amcat_grades table)
        $firstonly = "SELECT uu.id AS userid, MIN(gg.id) AS firstcompleted
                        FROM {user} uu, {amcat_grades} gg
                       WHERE uu.id = gg.userid AND gg.amcatid = :amcatid2
                             $fuser
                       GROUP BY uu.id";

        $sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
                  FROM {user} u, {amcat_grades} g, ($firstonly) f
                 WHERE u.id = g.userid AND g.amcatid = :amcatid
                       AND g.id = f.firstcompleted AND g.userid=f.userid
                       $user";
    }

    return $DB->get_records_sql($sql, $params);
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $amcat
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function amcat_update_grades($amcat, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($amcat->grade == 0 || $amcat->practice) {
        amcat_grade_item_update($amcat);

    } else if ($grades = amcat_get_user_grades($amcat, $userid)) {
        amcat_grade_item_update($amcat, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        amcat_grade_item_update($amcat, $grade);

    } else {
        amcat_grade_item_update($amcat);
    }
}

/**
 * Create grade item for given amcat
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $amcat object with extra cmidnumber
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function amcat_grade_item_update($amcat, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (property_exists($amcat, 'cmidnumber')) { //it may not be always present
        $params = array('itemname'=>$amcat->name, 'idnumber'=>$amcat->cmidnumber);
    } else {
        $params = array('itemname'=>$amcat->name);
    }

    if ($amcat->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $amcat->grade;
        $params['grademin']   = 0;
    } else if ($amcat->grade < 0) {
        $params['gradetype']  = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$amcat->grade;

        // Make sure current grade fetched correctly from $grades
        $currentgrade = null;
        if (!empty($grades)) {
            if (is_array($grades)) {
                $currentgrade = reset($grades);
            } else {
                $currentgrade = $grades;
            }
        }

        // When converting a score to a scale, use scale's grade maximum to calculate it.
        if (!empty($currentgrade) && $currentgrade->rawgrade !== null) {
            $grade = grade_get_grades($amcat->course, 'mod', 'amcat', $amcat->id, $currentgrade->userid);
            $params['grademax']   = reset($grade->items)->grademax;
        }
    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            //check raw grade isnt null otherwise we erroneously insert a grade of 0
            if ($grade['rawgrade'] !== null) {
                $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $params['grademax'] / 100);
            } else {
                //setting rawgrade to null just in case user is deleting a grade
                $grades[$key]['rawgrade'] = null;
            }
        }
    }

    return grade_update('mod/amcat', $amcat->course, 'mod', 'amcat', $amcat->id, 0, $grades, $params);
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function amcat_get_view_actions() {
    return array('view','view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function amcat_get_post_actions() {
    return array('end','start');
}

/**
 * Runs any processes that must run before
 * a amcat insert/update
 *
 * @global object
 * @param object $amcat amcat form data
 * @return void
 **/
function amcat_process_pre_save(&$amcat) {
    global $DB;

    $amcat->timemodified = time();

    if (empty($amcat->timelimit)) {
        $amcat->timelimit = 0;
    }
    if (empty($amcat->timespent) or !is_numeric($amcat->timespent) or $amcat->timespent < 0) {
        $amcat->timespent = 0;
    }
    if (!isset($amcat->completed)) {
        $amcat->completed = 0;
    }
    if (empty($amcat->gradebetterthan) or !is_numeric($amcat->gradebetterthan) or $amcat->gradebetterthan < 0) {
        $amcat->gradebetterthan = 0;
    } else if ($amcat->gradebetterthan > 100) {
        $amcat->gradebetterthan = 100;
    }

    if (empty($amcat->width)) {
        $amcat->width = 640;
    }
    if (empty($amcat->height)) {
        $amcat->height = 480;
    }
    if (empty($amcat->bgcolor)) {
        $amcat->bgcolor = '#FFFFFF';
    }

    // Conditions for dependency
    $conditions = new stdClass;
    $conditions->timespent = $amcat->timespent;
    $conditions->completed = $amcat->completed;
    $conditions->gradebetterthan = $amcat->gradebetterthan;
    $amcat->conditions = serialize($conditions);
    unset($amcat->timespent);
    unset($amcat->completed);
    unset($amcat->gradebetterthan);

    if (empty($amcat->password)) {
        unset($amcat->password);
    }
}

/**
 * Runs any processes that must be run
 * after a amcat insert/update
 *
 * @global object
 * @param object $amcat amcat form data
 * @return void
 **/
function amcat_process_post_save(&$amcat) {
    // Update the events relating to this amcat.
    amcat_update_events($amcat);
    $completionexpected = (!empty($amcat->completionexpected)) ? $amcat->completionexpected : null;
    \core_completion\api::update_completion_date_event($amcat->coursemodule, 'amcat', $amcat, $completionexpected);
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the amcat.
 *
 * @param $mform form passed by reference
 */
function amcat_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'amcatheader', get_string('modulenameplural', 'amcat'));
    $mform->addElement('advcheckbox', 'reset_amcat', get_string('deleteallattempts','amcat'));
    $mform->addElement('advcheckbox', 'reset_amcat_user_overrides',
            get_string('removealluseroverrides', 'amcat'));
    $mform->addElement('advcheckbox', 'reset_amcat_group_overrides',
            get_string('removeallgroupoverrides', 'amcat'));
}

/**
 * Course reset form defaults.
 * @param object $course
 * @return array
 */
function amcat_reset_course_form_defaults($course) {
    return array('reset_amcat' => 1,
            'reset_amcat_group_overrides' => 1,
            'reset_amcat_user_overrides' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function amcat_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
              FROM {amcat} l, {course_modules} cm, {modules} m
             WHERE m.name='amcat' AND m.id=cm.module AND cm.instance=l.id AND l.course=:course";
    $params = array ("course" => $courseid);
    if ($amcats = $DB->get_records_sql($sql,$params)) {
        foreach ($amcats as $amcat) {
            amcat_grade_item_update($amcat, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * amcat attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function amcat_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'amcat');
    $status = array();

    if (!empty($data->reset_amcat)) {
        $amcatssql = "SELECT l.id
                         FROM {amcat} l
                        WHERE l.course=:course";

        $params = array ("course" => $data->courseid);
        $amcats = $DB->get_records_sql($amcatssql, $params);

        // Get rid of attempts files.
        $fs = get_file_storage();
        if ($amcats) {
            foreach ($amcats as $amcatid => $unused) {
                if (!$cm = get_coursemodule_from_instance('amcat', $amcatid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_amcat', 'essay_responses');
                $fs->delete_area_files($context->id, 'mod_amcat', 'essay_answers');
            }
        }

        $DB->delete_records_select('amcat_timer', "amcatid IN ($amcatssql)", $params);
        $DB->delete_records_select('amcat_grades', "amcatid IN ($amcatssql)", $params);
        $DB->delete_records_select('amcat_attempts', "amcatid IN ($amcatssql)", $params);
        $DB->delete_records_select('amcat_branch', "amcatid IN ($amcatssql)", $params);

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            amcat_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', 'amcat'), 'error'=>false);
    }

    // Remove user overrides.
    if (!empty($data->reset_amcat_user_overrides)) {
        $DB->delete_records_select('amcat_overrides',
                'amcatid IN (SELECT id FROM {amcat} WHERE course = ?) AND userid IS NOT NULL', array($data->courseid));
        $status[] = array(
        'component' => $componentstr,
        'item' => get_string('useroverridesdeleted', 'amcat'),
        'error' => false);
    }
    // Remove group overrides.
    if (!empty($data->reset_amcat_group_overrides)) {
        $DB->delete_records_select('amcat_overrides',
        'amcatid IN (SELECT id FROM {amcat} WHERE course = ?) AND groupid IS NOT NULL', array($data->courseid));
        $status[] = array(
        'component' => $componentstr,
        'item' => get_string('groupoverridesdeleted', 'amcat'),
        'error' => false);
    }
    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        $DB->execute("UPDATE {amcat_overrides}
                         SET available = available + ?
                       WHERE amcatid IN (SELECT id FROM {amcat} WHERE course = ?)
                         AND available <> 0", array($data->timeshift, $data->courseid));
        $DB->execute("UPDATE {amcat_overrides}
                         SET deadline = deadline + ?
                       WHERE amcatid IN (SELECT id FROM {amcat} WHERE course = ?)
                         AND deadline <> 0", array($data->timeshift, $data->courseid));

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        shift_course_mod_dates('amcat', array('available', 'deadline'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function amcat_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Obtains the automatic completion state for this amcat based on any conditions
 * in amcat settings.
 *
 * @param object $course Course
 * @param object $cm course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function amcat_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get amcat details.
    $amcat = $DB->get_record('amcat', array('id' => $cm->instance), '*',
            MUST_EXIST);

    $result = $type; // Default return value.
    // If completion option is enabled, evaluate it and return true/false.
    if ($amcat->completionendreached) {
        $value = $DB->record_exists('amcat_timer', array(
                'amcatid' => $amcat->id, 'userid' => $userid, 'completed' => 1));
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    if ($amcat->completiontimespent != 0) {
        $duration = $DB->get_field_sql(
                        "SELECT SUM(amcattime - starttime)
                               FROM {amcat_timer}
                              WHERE amcatid = :amcatid
                                AND userid = :userid",
                        array('userid' => $userid, 'amcatid' => $amcat->id));
        if (!$duration) {
            $duration = 0;
        }
        if ($type == COMPLETION_AND) {
            $result = $result && ($amcat->completiontimespent < $duration);
        } else {
            $result = $result || ($amcat->completiontimespent < $duration);
        }
    }
    return $result;
}
/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $amcatnode
 */
function amcat_extend_settings_navigation($settings, $amcatnode) {
    global $PAGE, $DB;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $amcatnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/amcat:manageoverrides', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/amcat/overrides.php', array('cmid' => $PAGE->cm->id));
        $node = navigation_node::create(get_string('groupoverrides', 'amcat'),
                new moodle_url($url, array('mode' => 'group')),
                navigation_node::TYPE_SETTING, null, 'mod_amcat_groupoverrides');
        $amcatnode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('useroverrides', 'amcat'),
                new moodle_url($url, array('mode' => 'user')),
                navigation_node::TYPE_SETTING, null, 'mod_amcat_useroverrides');
        $amcatnode->add_node($node, $beforekey);
    }

    if (has_capability('mod/amcat:edit', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/amcat/view.php', array('id' => $PAGE->cm->id));
        $amcatnode->add(get_string('preview', 'amcat'), $url);
        $editnode = $amcatnode->add(get_string('edit', 'amcat'));
        $url = new moodle_url('/mod/amcat/edit.php', array('id' => $PAGE->cm->id, 'mode' => 'collapsed'));
        $editnode->add(get_string('collapsed', 'amcat'), $url);
        $url = new moodle_url('/mod/amcat/edit.php', array('id' => $PAGE->cm->id, 'mode' => 'full'));
        $editnode->add(get_string('full', 'amcat'), $url);
    }

    if (has_capability('mod/amcat:viewreports', $PAGE->cm->context)) {
        $reportsnode = $amcatnode->add(get_string('reports', 'amcat'));
        $url = new moodle_url('/mod/amcat/report.php', array('id'=>$PAGE->cm->id, 'action'=>'reportoverview'));
        $reportsnode->add(get_string('overview', 'amcat'), $url);
        $url = new moodle_url('/mod/amcat/report.php', array('id'=>$PAGE->cm->id, 'action'=>'reportdetail'));
        $reportsnode->add(get_string('detailedstats', 'amcat'), $url);
    }

    if (has_capability('mod/amcat:grade', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/amcat/essay.php', array('id'=>$PAGE->cm->id));
        $amcatnode->add(get_string('manualgrading', 'amcat'), $url);
    }

}

/**
 * Get list of available import or export formats
 *
 * Copied and modified from lib/questionlib.php
 *
 * @param string $type 'import' if import list, otherwise export list assumed
 * @return array sorted list of import/export formats available
 */
function amcat_get_import_export_formats($type) {
    global $CFG;
    $fileformats = core_component::get_plugin_list("qformat");

    $fileformatname=array();
    foreach ($fileformats as $fileformat=>$fdir) {
        $format_file = "$fdir/format.php";
        if (file_exists($format_file) ) {
            require_once($format_file);
        } else {
            continue;
        }
        $classname = "qformat_$fileformat";
        $format_class = new $classname();
        if ($type=='import') {
            $provided = $format_class->provide_import();
        } else {
            $provided = $format_class->provide_export();
        }
        if ($provided) {
            $fileformatnames[$fileformat] = get_string('pluginname', 'qformat_'.$fileformat);
        }
    }
    natcasesort($fileformatnames);

    return $fileformatnames;
}

/**
 * Serves the amcat attachments. Implements needed access control ;-)
 *
 * @package mod_amcat
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function amcat_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    $fileareas = amcat_get_file_areas();
    if (!array_key_exists($filearea, $fileareas)) {
        return false;
    }

    if (!$amcat = $DB->get_record('amcat', array('id'=>$cm->instance))) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'page_contents') {
        $pageid = (int)array_shift($args);
        if (!$page = $DB->get_record('amcat_pages', array('id'=>$pageid))) {
            return false;
        }
        $fullpath = "/$context->id/mod_amcat/$filearea/$pageid/".implode('/', $args);

    } else if ($filearea === 'page_answers' || $filearea === 'page_responses') {
        $itemid = (int)array_shift($args);
        if (!$pageanswers = $DB->get_record('amcat_answers', array('id' => $itemid))) {
            return false;
        }
        $fullpath = "/$context->id/mod_amcat/$filearea/$itemid/".implode('/', $args);

    } else if ($filearea === 'essay_responses' || $filearea === 'essay_answers') {
        $itemid = (int)array_shift($args);
        if (!$attempt = $DB->get_record('amcat_attempts', array('id' => $itemid))) {
            return false;
        }
        $fullpath = "/$context->id/mod_amcat/$filearea/$itemid/".implode('/', $args);

    } else if ($filearea === 'mediafile') {
        if (count($args) > 1) {
            // Remove the itemid when it appears to be part of the arguments. If there is only one argument
            // then it is surely the file name. The itemid is sometimes used to prevent browser caching.
            array_shift($args);
        }
        $fullpath = "/$context->id/mod_amcat/$filearea/0/".implode('/', $args);

    } else {
        return false;
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, $forcedownload, $options); // download MUST be forced - security!
}

/**
 * Returns an array of file areas
 *
 * @package  mod_amcat
 * @category files
 * @return array a list of available file areas
 */
function amcat_get_file_areas() {
    $areas = array();
    $areas['page_contents'] = get_string('pagecontents', 'mod_amcat');
    $areas['mediafile'] = get_string('mediafile', 'mod_amcat');
    $areas['page_answers'] = get_string('pageanswers', 'mod_amcat');
    $areas['page_responses'] = get_string('pageresponses', 'mod_amcat');
    $areas['essay_responses'] = get_string('essayresponses', 'mod_amcat');
    $areas['essay_answers'] = get_string('essayresponses', 'mod_amcat');
    return $areas;
}

/**
 * Returns a file_info_stored object for the file being requested here
 *
 * @package  mod_amcat
 * @category files
 * @global stdClass $CFG
 * @param file_browse $browser file browser instance
 * @param array $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info_stored
 */
function amcat_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // No peaking here for students!
        return null;
    }

    // Mediafile area does not have sub directories, so let's select the default itemid to prevent
    // the user from selecting a directory to access the mediafile content.
    if ($filearea == 'mediafile' && is_null($itemid)) {
        $itemid = 0;
    }

    if (is_null($itemid)) {
        return new mod_amcat_file_info($browser, $course, $cm, $context, $areas, $filearea);
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!$storedfile = $fs->get_file($context->id, 'mod_amcat', $filearea, $itemid, $filepath, $filename)) {
        return null;
    }

    $itemname = $filearea;
    if ($filearea == 'page_contents') {
        $itemname = $DB->get_field('amcat_pages', 'title', array('amcatid' => $cm->instance, 'id' => $itemid));
        $itemname = format_string($itemname, true, array('context' => $context));
    } else {
        $areas = amcat_get_file_areas();
        if (isset($areas[$filearea])) {
            $itemname = $areas[$filearea];
        }
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $itemname, $itemid, true, true, false);
}


/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function amcat_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
        'mod-amcat-*'=>get_string('page-mod-amcat-x', 'amcat'),
        'mod-amcat-view'=>get_string('page-mod-amcat-view', 'amcat'),
        'mod-amcat-edit'=>get_string('page-mod-amcat-edit', 'amcat'));
    return $module_pagetype;
}

/**
 * Update the amcat activity to include any file
 * that was uploaded, or if there is none, set the
 * mediafile field to blank.
 *
 * @param int $amcatid the amcat id
 * @param stdClass $context the context
 * @param int $draftitemid the draft item
 */
function amcat_update_media_file($amcatid, $context, $draftitemid) {
    global $DB;

    // Set the filestorage object.
    $fs = get_file_storage();
    // Save the file if it exists that is currently in the draft area.
    file_save_draft_area_files($draftitemid, $context->id, 'mod_amcat', 'mediafile', 0);
    // Get the file if it exists.
    $files = $fs->get_area_files($context->id, 'mod_amcat', 'mediafile', 0, 'itemid, filepath, filename', false);
    // Check that there is a file to process.
    if (count($files) == 1) {
        // Get the first (and only) file.
        $file = reset($files);
        // Set the mediafile column in the amcats table.
        $DB->set_field('amcat', 'mediafile', '/' . $file->get_filename(), array('id' => $amcatid));
    } else {
        // Set the mediafile column in the amcats table.
        $DB->set_field('amcat', 'mediafile', '', array('id' => $amcatid));
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_amcat_get_fontawesome_icon_map() {
    return [
        'mod_amcat:e/copy' => 'fa-clone',
    ];
}

/*
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.3
 */
function amcat_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER;

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check if there are new pages or answers in the amcat.
    $updates->pages = (object) array('updated' => false);
    $updates->answers = (object) array('updated' => false);
    $select = 'amcatid = ? AND (timecreated > ? OR timemodified > ?)';
    $params = array($cm->instance, $from, $from);

    $pages = $DB->get_records_select('amcat_pages', $select, $params, '', 'id');
    if (!empty($pages)) {
        $updates->pages->updated = true;
        $updates->pages->itemids = array_keys($pages);
    }
    $answers = $DB->get_records_select('amcat_answers', $select, $params, '', 'id');
    if (!empty($answers)) {
        $updates->answers->updated = true;
        $updates->answers->itemids = array_keys($answers);
    }

    // Check for new question attempts, grades, pages viewed and timers.
    $updates->questionattempts = (object) array('updated' => false);
    $updates->grades = (object) array('updated' => false);
    $updates->pagesviewed = (object) array('updated' => false);
    $updates->timers = (object) array('updated' => false);

    $select = 'amcatid = ? AND userid = ? AND timeseen > ?';
    $params = array($cm->instance, $USER->id, $from);

    $questionattempts = $DB->get_records_select('amcat_attempts', $select, $params, '', 'id');
    if (!empty($questionattempts)) {
        $updates->questionattempts->updated = true;
        $updates->questionattempts->itemids = array_keys($questionattempts);
    }
    $pagesviewed = $DB->get_records_select('amcat_branch', $select, $params, '', 'id');
    if (!empty($pagesviewed)) {
        $updates->pagesviewed->updated = true;
        $updates->pagesviewed->itemids = array_keys($pagesviewed);
    }

    $select = 'amcatid = ? AND userid = ? AND completed > ?';
    $grades = $DB->get_records_select('amcat_grades', $select, $params, '', 'id');
    if (!empty($grades)) {
        $updates->grades->updated = true;
        $updates->grades->itemids = array_keys($grades);
    }

    $select = 'amcatid = ? AND userid = ? AND (starttime > ? OR amcattime > ? OR timemodifiedoffline > ?)';
    $params = array($cm->instance, $USER->id, $from, $from, $from);
    $timers = $DB->get_records_select('amcat_timer', $select, $params, '', 'id');
    if (!empty($timers)) {
        $updates->timers->updated = true;
        $updates->timers->itemids = array_keys($timers);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/amcat:viewreports', $cm->context)) {
        $select = 'amcatid = ? AND timeseen > ?';
        $params = array($cm->instance, $from);

        $insql = '';
        $inparams = [];
        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->userquestionattempts = (object) array('updated' => false);
        $updates->usergrades = (object) array('updated' => false);
        $updates->userpagesviewed = (object) array('updated' => false);
        $updates->usertimers = (object) array('updated' => false);

        $questionattempts = $DB->get_records_select('amcat_attempts', $select, $params, '', 'id');
        if (!empty($questionattempts)) {
            $updates->userquestionattempts->updated = true;
            $updates->userquestionattempts->itemids = array_keys($questionattempts);
        }
        $pagesviewed = $DB->get_records_select('amcat_branch', $select, $params, '', 'id');
        if (!empty($pagesviewed)) {
            $updates->userpagesviewed->updated = true;
            $updates->userpagesviewed->itemids = array_keys($pagesviewed);
        }

        $select = 'amcatid = ? AND completed > ?';
        if (!empty($insql)) {
            $select .= ' AND userid ' . $insql;
        }
        $grades = $DB->get_records_select('amcat_grades', $select, $params, '', 'id');
        if (!empty($grades)) {
            $updates->usergrades->updated = true;
            $updates->usergrades->itemids = array_keys($grades);
        }

        $select = 'amcatid = ? AND (starttime > ? OR amcattime > ? OR timemodifiedoffline > ?)';
        $params = array($cm->instance, $from, $from, $from);
        if (!empty($insql)) {
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }
        $timers = $DB->get_records_select('amcat_timer', $select, $params, '', 'id');
        if (!empty($timers)) {
            $updates->usertimers->updated = true;
            $updates->usertimers->itemids = array_keys($timers);
        }
    }
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_amcat_core_calendar_provide_event_action(calendar_event $event,
                                                       \core_calendar\action_factory $factory,
                                                       int $userid = 0) {
    global $DB, $CFG, $USER;
    require_once($CFG->dirroot . '/mod/amcat/locallib.php');

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['amcat'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    $amcat = new amcat($DB->get_record('amcat', array('id' => $cm->instance), '*', MUST_EXIST));

    if ($amcat->count_user_retries($userid)) {
        // If the user has attempted the amcat then there is no further action for the user.
        return null;
    }

    // Apply overrides.
    $amcat->update_effective_access($userid);

    if (!$amcat->is_participant($userid)) {
        // If the user is not a participant then they have
        // no action to take. This will filter out the events for teachers.
        return null;
    }

    return $factory->create_instance(
        get_string('startamcat', 'amcat'),
        new \moodle_url('/mod/amcat/view.php', ['id' => $cm->id]),
        1,
        $amcat->is_accessible()
    );
}

/**
 * Add a get_coursemodule_info function in case any amcat type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function amcat_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionendreached, completiontimespent';
    if (!$amcat = $DB->get_record('amcat', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $amcat->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('amcat', $amcat, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionendreached'] = $amcat->completionendreached;
        $result->customdata['customcompletionrules']['completiontimespent'] = $amcat->completiontimespent;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_amcat_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionendreached':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionendreached_desc', 'amcat', $val);
                }
                break;
            case 'completiontimespent':
                if (!empty($val)) {
                    $descriptions[] = get_string('completiontimespentdesc', 'amcat', format_time($val));
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_amcat_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == amcat_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the amcat activity.
        if (!empty($instance->deadline)) {
            $maxdate = [
                $instance->deadline,
                get_string('openafterclose', 'amcat')
            ];
        }
    } else if ($event->eventtype == amcat_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the amcat activity.
        if (!empty($instance->available)) {
            $mindate = [
                $instance->available,
                get_string('closebeforeopen', 'amcat')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the amcat module according to the
 * event that has been modified.
 *
 * It will set the available or deadline value of the amcat instance
 * according to the type of event provided.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event
 * @param stdClass $amcat The module instance to get the range from
 */
function mod_amcat_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $amcat) {
    global $DB;

    if (empty($event->instance) || $event->modulename != 'amcat') {
        return;
    }

    if ($event->instance != $amcat->id) {
        return;
    }

    if (!in_array($event->eventtype, [amcat_EVENT_TYPE_OPEN, amcat_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == amcat_EVENT_TYPE_OPEN) {
        // If the event is for the amcat activity opening then we should
        // set the start time of the amcat activity to be the new start
        // time of the event.
        if ($amcat->available != $event->timestart) {
            $amcat->available = $event->timestart;
            $amcat->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == amcat_EVENT_TYPE_CLOSE) {
        // If the event is for the amcat activity closing then we should
        // set the end time of the amcat activity to be the new start
        // time of the event.
        if ($amcat->deadline != $event->timestart) {
            $amcat->deadline = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $amcat->timemodified = time();
        $DB->update_record('amcat', $amcat);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}
