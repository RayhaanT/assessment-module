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
 * amcat external API
 *
 * @package    mod_amcat
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/amcat/locallib.php');

use mod_amcat\external\amcat_summary_exporter;

/**
 * amcat external functions
 *
 * @package    mod_amcat
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class mod_amcat_external extends external_api {

    /**
     * Return a amcat record ready for being exported.
     *
     * @param  stdClass $amcatrecord amcat record
     * @param  string $password       amcat password
     * @return stdClass the amcat record ready for exporting.
     */
    protected static function get_amcat_summary_for_exporter($amcatrecord, $password = '') {
        global $USER;

        $amcat = new amcat($amcatrecord);
        $amcat->update_effective_access($USER->id);
        $amcatavailable = $amcat->get_time_restriction_status() === false;
        $amcatavailable = $amcatavailable && $amcat->get_password_restriction_status($password) === false;
        $amcatavailable = $amcatavailable && $amcat->get_dependencies_restriction_status() === false;
        $canmanage = $amcat->can_manage();

        if (!$canmanage && !$amcatavailable) {
            $fields = array('intro', 'introfiles', 'mediafiles', 'practice', 'modattempts', 'usepassword',
                'grade', 'custom', 'ongoing', 'usemaxgrade',
                'maxanswers', 'maxattempts', 'review', 'nextpagedefault', 'feedback', 'minquestions',
                'maxpages', 'timelimit', 'retake', 'mediafile', 'mediaheight', 'mediawidth',
                'mediaclose', 'slideshow', 'width', 'height', 'bgcolor', 'displayleft', 'displayleftif',
                'progressbar');

            foreach ($fields as $field) {
                unset($amcatrecord->{$field});
            }
        }

        // Fields only for managers.
        if (!$canmanage) {
            $fields = array('password', 'dependency', 'conditions', 'activitylink', 'available', 'deadline',
                            'timemodified', 'completionendreached', 'completiontimespent');

            foreach ($fields as $field) {
                unset($amcatrecord->{$field});
            }
        }
        return $amcatrecord;
    }

    /**
     * Describes the parameters for get_amcats_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_amcats_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of amcats in a provided list of courses,
     * if no list is provided all amcats that the user can view will be returned.
     *
     * @param array $courseids Array of course ids
     * @return array of amcats details
     * @since Moodle 3.3
     */
    public static function get_amcats_by_courses($courseids = array()) {
        global $PAGE;

        $warnings = array();
        $returnedamcats = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_amcats_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the amcats in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $amcats = get_all_instances_in_courses("amcat", $courses);
            foreach ($amcats as $amcatrecord) {
                $context = context_module::instance($amcatrecord->coursemodule);

                // Remove fields added by get_all_instances_in_courses.
                unset($amcatrecord->coursemodule, $amcatrecord->section, $amcatrecord->visible, $amcatrecord->groupmode,
                    $amcatrecord->groupingid);

                $amcatrecord = self::get_amcat_summary_for_exporter($amcatrecord);

                $exporter = new amcat_summary_exporter($amcatrecord, array('context' => $context));
                $returnedamcats[] = $exporter->export($PAGE->get_renderer('core'));
            }
        }
        $result = array();
        $result['amcats'] = $returnedamcats;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_amcats_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_amcats_by_courses_returns() {
        return new external_single_structure(
            array(
                'amcats' => new external_multiple_structure(
                    amcat_summary_exporter::get_read_structure()
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Utility function for validating a amcat.
     *
     * @param int $amcatid amcat instance id
     * @return array array containing the amcat, course, context and course module objects
     * @since  Moodle 3.3
     */
    protected static function validate_amcat($amcatid) {
        global $DB, $USER;

        // Request and permission validation.
        $amcatrecord = $DB->get_record('amcat', array('id' => $amcatid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($amcatrecord, 'amcat');

        $amcat = new amcat($amcatrecord, $cm, $course);
        $amcat->update_effective_access($USER->id);

        $context = $amcat->context;
        self::validate_context($context);

        return array($amcat, $course, $cm, $context, $amcatrecord);
    }

    /**
     * Validates a new attempt.
     *
     * @param  amcat  $amcat amcat instance
     * @param  array   $params request parameters
     * @param  boolean $return whether to return the errors or throw exceptions
     * @return array          the errors (if return set to true)
     * @since  Moodle 3.3
     */
    protected static function validate_attempt(amcat $amcat, $params, $return = false) {
        global $USER, $CFG;

        $errors = array();

        // Avoid checkings for managers.
        if ($amcat->can_manage()) {
            return [];
        }

        // Dead line.
        if ($timerestriction = $amcat->get_time_restriction_status()) {
            $error = ["$timerestriction->reason" => userdate($timerestriction->time)];
            if (!$return) {
                throw new moodle_exception(key($error), 'amcat', '', current($error));
            }
            $errors[key($error)] = current($error);
        }

        // Password protected amcat code.
        if ($passwordrestriction = $amcat->get_password_restriction_status($params['password'])) {
            $error = ["passwordprotectedamcat" => external_format_string($amcat->name, $amcat->context->id)];
            if (!$return) {
                throw new moodle_exception(key($error), 'amcat', '', current($error));
            }
            $errors[key($error)] = current($error);
        }

        // Check for dependencies.
        if ($dependenciesrestriction = $amcat->get_dependencies_restriction_status()) {
            $errorhtmllist = implode(get_string('and', 'amcat') . ', ', $dependenciesrestriction->errors);
            $error = ["completethefollowingconditions" => $dependenciesrestriction->dependentamcat->name . $errorhtmllist];
            if (!$return) {
                throw new moodle_exception(key($error), 'amcat', '', current($error));
            }
            $errors[key($error)] = current($error);
        }

        // To check only when no page is set (starting or continuing a amcat).
        if (empty($params['pageid'])) {
            // To avoid multiple calls, store the magic property firstpage.
            $amcatfirstpage = $amcat->firstpage;
            $amcatfirstpageid = $amcatfirstpage ? $amcatfirstpage->id : false;

            // Check if the amcat does not have pages.
            if (!$amcatfirstpageid) {
                $error = ["amcatnotready2" => null];
                if (!$return) {
                    throw new moodle_exception(key($error), 'amcat');
                }
                $errors[key($error)] = current($error);
            }

            // Get the number of retries (also referenced as attempts), and the last page seen.
            $attemptscount = $amcat->count_user_retries($USER->id);
            $lastpageseen = $amcat->get_last_page_seen($attemptscount);

            // Check if the user left a timed session with no retakes.
            if ($lastpageseen !== false && $lastpageseen != amcat_EOL) {
                if ($amcat->left_during_timed_session($attemptscount) && $amcat->timelimit && !$amcat->retake) {
                    $error = ["leftduringtimednoretake" => null];
                    if (!$return) {
                        throw new moodle_exception(key($error), 'amcat');
                    }
                    $errors[key($error)] = current($error);
                }
            } else if ($attemptscount > 0 && !$amcat->retake) {
                // The user finished the amcat and no retakes are allowed.
                $error = ["noretake" => null];
                if (!$return) {
                    throw new moodle_exception(key($error), 'amcat');
                }
                $errors[key($error)] = current($error);
            }
        } else {
            if (!$timers = $amcat->get_user_timers($USER->id, 'starttime DESC', '*', 0, 1)) {
                $error = ["cannotfindtimer" => null];
                if (!$return) {
                    throw new moodle_exception(key($error), 'amcat');
                }
                $errors[key($error)] = current($error);
            } else {
                $timer = current($timers);
                if (!$amcat->check_time($timer)) {
                    $error = ["eolstudentoutoftime" => null];
                    if (!$return) {
                        throw new moodle_exception(key($error), 'amcat');
                    }
                    $errors[key($error)] = current($error);
                }

                // Check if the user want to review an attempt he just finished.
                if (!empty($params['review'])) {
                    // Allow review only for attempts during active session time.
                    if ($timer->amcattime + $CFG->sessiontimeout > time()) {
                        $ntries = $amcat->count_user_retries($USER->id);
                        $ntries--;  // Need to look at the old attempts.
                        if ($params['pageid'] == amcat_EOL) {
                            if ($attempts = $amcat->get_attempts($ntries)) {
                                $lastattempt = end($attempts);
                                $USER->modattempts[$amcat->id] = $lastattempt->pageid;
                            }
                        } else {
                            if ($attempts = $amcat->get_attempts($ntries, false, $params['pageid'])) {
                                $lastattempt = end($attempts);
                                $USER->modattempts[$amcat->id] = $lastattempt;
                            }
                        }
                    }

                    if (!isset($USER->modattempts[$amcat->id])) {
                        $error = ["studentoutoftimeforreview" => null];
                        if (!$return) {
                            throw new moodle_exception(key($error), 'amcat');
                        }
                        $errors[key($error)] = current($error);
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Describes the parameters for get_amcat_access_information.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_amcat_access_information_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id')
            )
        );
    }

    /**
     * Return access information for a given amcat.
     *
     * @param int $amcatid amcat instance id
     * @return array of warnings and the access information
     * @since Moodle 3.3
     * @throws  moodle_exception
     */
    public static function get_amcat_access_information($amcatid) {
        global $DB, $USER;

        $warnings = array();

        $params = array(
            'amcatid' => $amcatid
        );
        $params = self::validate_parameters(self::get_amcat_access_information_parameters(), $params);

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        $result = array();
        // Capabilities first.
        $result['canmanage'] = $amcat->can_manage();
        $result['cangrade'] = has_capability('mod/amcat:grade', $context);
        $result['canviewreports'] = has_capability('mod/amcat:viewreports', $context);

        // Status information.
        $result['reviewmode'] = $amcat->is_in_review_mode();
        $result['attemptscount'] = $amcat->count_user_retries($USER->id);
        $lastpageseen = $amcat->get_last_page_seen($result['attemptscount']);
        $result['lastpageseen'] = ($lastpageseen !== false) ? $lastpageseen : 0;
        $result['leftduringtimedsession'] = $amcat->left_during_timed_session($result['attemptscount']);
        // To avoid multiple calls, store the magic property firstpage.
        $amcatfirstpage = $amcat->firstpage;
        $result['firstpageid'] = $amcatfirstpage ? $amcatfirstpage->id : 0;

        // Access restrictions now, we emulate a new attempt access to get the possible warnings.
        $result['preventaccessreasons'] = [];
        $validationerrors = self::validate_attempt($amcat, ['password' => ''], true);
        foreach ($validationerrors as $reason => $data) {
            $result['preventaccessreasons'][] = [
                'reason' => $reason,
                'data' => $data,
                'message' => get_string($reason, 'amcat', $data),
            ];
        }
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_amcat_access_information return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_amcat_access_information_returns() {
        return new external_single_structure(
            array(
                'canmanage' => new external_value(PARAM_BOOL, 'Whether the user can manage the amcat or not.'),
                'cangrade' => new external_value(PARAM_BOOL, 'Whether the user can grade the amcat or not.'),
                'canviewreports' => new external_value(PARAM_BOOL, 'Whether the user can view the amcat reports or not.'),
                'reviewmode' => new external_value(PARAM_BOOL, 'Whether the amcat is in review mode for the current user.'),
                'attemptscount' => new external_value(PARAM_INT, 'The number of attempts done by the user.'),
                'lastpageseen' => new external_value(PARAM_INT, 'The last page seen id.'),
                'leftduringtimedsession' => new external_value(PARAM_BOOL, 'Whether the user left during a timed session.'),
                'firstpageid' => new external_value(PARAM_INT, 'The amcat first page id.'),
                'preventaccessreasons' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'reason' => new external_value(PARAM_ALPHANUMEXT, 'Reason lang string code'),
                            'data' => new external_value(PARAM_RAW, 'Additional data'),
                            'message' => new external_value(PARAM_RAW, 'Complete html message'),
                        ),
                        'The reasons why the user cannot attempt the amcat'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for view_amcat.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function view_amcat_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'password' => new external_value(PARAM_RAW, 'amcat password', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $amcatid amcat instance id
     * @param string $password optional password (the amcat may be protected)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function view_amcat($amcatid, $password = '') {
        global $DB;

        $params = array('amcatid' => $amcatid, 'password' => $password);
        $params = self::validate_parameters(self::view_amcat_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);
        self::validate_attempt($amcat, $params);

        $amcat->set_module_viewed();

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the view_amcat return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function view_amcat_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Check if the current user can retrieve amcat information (grades, attempts) about the given user.
     *
     * @param int $userid the user to check
     * @param stdClass $course course object
     * @param stdClass $cm cm object
     * @param stdClass $context context object
     * @throws moodle_exception
     * @since Moodle 3.3
     */
    protected static function check_can_view_user_data($userid, $course, $cm, $context) {
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);
        // Check permissions and that if users share group (if groups enabled).
        require_capability('mod/amcat:viewreports', $context);
        if (!groups_user_groups_visible($course, $user->id, $cm)) {
            throw new moodle_exception('notingroup');
        }
    }

    /**
     * Describes the parameters for get_questions_attempts.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_questions_attempts_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'attempt' => new external_value(PARAM_INT, 'amcat attempt number'),
                'correct' => new external_value(PARAM_BOOL, 'only fetch correct attempts', VALUE_DEFAULT, false),
                'pageid' => new external_value(PARAM_INT, 'only fetch attempts at the given page', VALUE_DEFAULT, null),
                'userid' => new external_value(PARAM_INT, 'only fetch attempts of the given user', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Return the list of page question attempts in a given amcat.
     *
     * @param int $amcatid amcat instance id
     * @param int $attempt the amcat attempt number
     * @param bool $correct only fetch correct attempts
     * @param int $pageid only fetch attempts at the given page
     * @param int $userid only fetch attempts of the given user
     * @return array of warnings and page attempts
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_questions_attempts($amcatid, $attempt, $correct = false, $pageid = null, $userid = null) {
        global $DB, $USER;

        $params = array(
            'amcatid' => $amcatid,
            'attempt' => $attempt,
            'correct' => $correct,
            'pageid' => $pageid,
            'userid' => $userid,
        );
        $params = self::validate_parameters(self::get_questions_attempts_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Extra checks so only users with permissions can view other users attempts.
        if ($USER->id != $params['userid']) {
            self::check_can_view_user_data($params['userid'], $course, $cm, $context);
        }

        $result = array();
        $result['attempts'] = $amcat->get_attempts($params['attempt'], $params['correct'], $params['pageid'], $params['userid']);
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_questions_attempts return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_questions_attempts_returns() {
        return new external_single_structure(
            array(
                'attempts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The attempt id'),
                            'amcatid' => new external_value(PARAM_INT, 'The attempt amcatid'),
                            'pageid' => new external_value(PARAM_INT, 'The attempt pageid'),
                            'userid' => new external_value(PARAM_INT, 'The user who did the attempt'),
                            'answerid' => new external_value(PARAM_INT, 'The attempt answerid'),
                            'retry' => new external_value(PARAM_INT, 'The amcat attempt number'),
                            'correct' => new external_value(PARAM_INT, 'If it was the correct answer'),
                            'useranswer' => new external_value(PARAM_RAW, 'The complete user answer'),
                            'timeseen' => new external_value(PARAM_INT, 'The time the question was seen'),
                        ),
                        'The question page attempts'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_user_grade.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_user_grade_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'userid' => new external_value(PARAM_INT, 'the user id (empty for current user)', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Return the final grade in the amcat for the given user.
     *
     * @param int $amcatid amcat instance id
     * @param int $userid only fetch grades of this user
     * @return array of warnings and page attempts
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_user_grade($amcatid, $userid = null) {
        global $CFG, $USER;
        require_once($CFG->libdir . '/gradelib.php');

        $params = array(
            'amcatid' => $amcatid,
            'userid' => $userid,
        );
        $params = self::validate_parameters(self::get_user_grade_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Extra checks so only users with permissions can view other users attempts.
        if ($USER->id != $params['userid']) {
            self::check_can_view_user_data($params['userid'], $course, $cm, $context);
        }

        $grade = null;
        $formattedgrade = null;
        $grades = amcat_get_user_grades($amcat, $params['userid']);
        if (!empty($grades)) {
            $grade = $grades[$params['userid']]->rawgrade;
            $params = array(
                'itemtype' => 'mod',
                'itemmodule' => 'amcat',
                'iteminstance' => $amcat->id,
                'courseid' => $course->id,
                'itemnumber' => 0
            );
            $gradeitem = grade_item::fetch($params);
            $formattedgrade = grade_format_gradevalue($grade, $gradeitem);
        }

        $result = array();
        $result['grade'] = $grade;
        $result['formattedgrade'] = $formattedgrade;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_user_grade return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_user_grade_returns() {
        return new external_single_structure(
            array(
                'grade' => new external_value(PARAM_FLOAT, 'The amcat final raw grade'),
                'formattedgrade' => new external_value(PARAM_RAW, 'The amcat final grade formatted'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes an attempt grade structure.
     *
     * @param  int $required if the structure is required or optional
     * @return external_single_structure the structure
     * @since  Moodle 3.3
     */
    protected static function get_user_attempt_grade_structure($required = VALUE_REQUIRED) {
        $data = array(
            'nquestions' => new external_value(PARAM_INT, 'Number of questions answered'),
            'attempts' => new external_value(PARAM_INT, 'Number of question attempts'),
            'total' => new external_value(PARAM_FLOAT, 'Max points possible'),
            'earned' => new external_value(PARAM_FLOAT, 'Points earned by student'),
            'grade' => new external_value(PARAM_FLOAT, 'Calculated percentage grade'),
            'nmanual' => new external_value(PARAM_INT, 'Number of manually graded questions'),
            'manualpoints' => new external_value(PARAM_FLOAT, 'Point value for manually graded questions'),
        );
        return new external_single_structure(
            $data, 'Attempt grade', $required
        );
    }

    /**
     * Describes the parameters for get_user_attempt_grade.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_user_attempt_grade_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'amcatattempt' => new external_value(PARAM_INT, 'amcat attempt number'),
                'userid' => new external_value(PARAM_INT, 'the user id (empty for current user)', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Return grade information in the attempt for a given user.
     *
     * @param int $amcatid amcat instance id
     * @param int $amcatattempt amcat attempt number
     * @param int $userid only fetch attempts of the given user
     * @return array of warnings and page attempts
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_user_attempt_grade($amcatid, $amcatattempt, $userid = null) {
        global $CFG, $USER;
        require_once($CFG->libdir . '/gradelib.php');

        $params = array(
            'amcatid' => $amcatid,
            'amcatattempt' => $amcatattempt,
            'userid' => $userid,
        );
        $params = self::validate_parameters(self::get_user_attempt_grade_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Extra checks so only users with permissions can view other users attempts.
        if ($USER->id != $params['userid']) {
            self::check_can_view_user_data($params['userid'], $course, $cm, $context);
        }

        $result = array();
        $result['grade'] = (array) amcat_grade($amcat, $params['amcatattempt'], $params['userid']);
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_user_attempt_grade return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_user_attempt_grade_returns() {
        return new external_single_structure(
            array(
                'grade' => self::get_user_attempt_grade_structure(),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_content_pages_viewed.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_content_pages_viewed_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'amcatattempt' => new external_value(PARAM_INT, 'amcat attempt number'),
                'userid' => new external_value(PARAM_INT, 'the user id (empty for current user)', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Return the list of content pages viewed by a user during a amcat attempt.
     *
     * @param int $amcatid amcat instance id
     * @param int $amcatattempt amcat attempt number
     * @param int $userid only fetch attempts of the given user
     * @return array of warnings and page attempts
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_content_pages_viewed($amcatid, $amcatattempt, $userid = null) {
        global $USER;

        $params = array(
            'amcatid' => $amcatid,
            'amcatattempt' => $amcatattempt,
            'userid' => $userid,
        );
        $params = self::validate_parameters(self::get_content_pages_viewed_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Extra checks so only users with permissions can view other users attempts.
        if ($USER->id != $params['userid']) {
            self::check_can_view_user_data($params['userid'], $course, $cm, $context);
        }

        $pages = $amcat->get_content_pages_viewed($params['amcatattempt'], $params['userid']);

        $result = array();
        $result['pages'] = $pages;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_content_pages_viewed return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_content_pages_viewed_returns() {
        return new external_single_structure(
            array(
                'pages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The attempt id.'),
                            'amcatid' => new external_value(PARAM_INT, 'The amcat id.'),
                            'pageid' => new external_value(PARAM_INT, 'The page id.'),
                            'userid' => new external_value(PARAM_INT, 'The user who viewed the page.'),
                            'retry' => new external_value(PARAM_INT, 'The amcat attempt number.'),
                            'flag' => new external_value(PARAM_INT, '1 if the next page was calculated randomly.'),
                            'timeseen' => new external_value(PARAM_INT, 'The time the page was seen.'),
                            'nextpageid' => new external_value(PARAM_INT, 'The next page chosen id.'),
                        ),
                        'The content pages viewed.'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_user_timers.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_user_timers_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'userid' => new external_value(PARAM_INT, 'the user id (empty for current user)', VALUE_DEFAULT, null),
            )
        );
    }

    /**
     * Return the timers in the current amcat for the given user.
     *
     * @param int $amcatid amcat instance id
     * @param int $userid only fetch timers of the given user
     * @return array of warnings and timers
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_user_timers($amcatid, $userid = null) {
        global $USER;

        $params = array(
            'amcatid' => $amcatid,
            'userid' => $userid,
        );
        $params = self::validate_parameters(self::get_user_timers_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Extra checks so only users with permissions can view other users attempts.
        if ($USER->id != $params['userid']) {
            self::check_can_view_user_data($params['userid'], $course, $cm, $context);
        }

        $timers = $amcat->get_user_timers($params['userid']);

        $result = array();
        $result['timers'] = $timers;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_user_timers return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_user_timers_returns() {
        return new external_single_structure(
            array(
                'timers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The attempt id'),
                            'amcatid' => new external_value(PARAM_INT, 'The amcat id'),
                            'userid' => new external_value(PARAM_INT, 'The user id'),
                            'starttime' => new external_value(PARAM_INT, 'First access time for a new timer session'),
                            'amcattime' => new external_value(PARAM_INT, 'Last access time to the amcat during the timer session'),
                            'completed' => new external_value(PARAM_INT, 'If the amcat for this timer was completed'),
                            'timemodifiedoffline' => new external_value(PARAM_INT, 'Last modified time via webservices.'),
                        ),
                        'The timers'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the external structure for a amcat page.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    protected static function get_page_structure($required = VALUE_REQUIRED) {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'The id of this amcat page'),
                'amcatid' => new external_value(PARAM_INT, 'The id of the amcat this page belongs to'),
                'prevpageid' => new external_value(PARAM_INT, 'The id of the page before this one'),
                'nextpageid' => new external_value(PARAM_INT, 'The id of the next page in the page sequence'),
                'qtype' => new external_value(PARAM_INT, 'Identifies the page type of this page'),
                'qoption' => new external_value(PARAM_INT, 'Used to record page type specific options'),
                'layout' => new external_value(PARAM_INT, 'Used to record page specific layout selections'),
                'display' => new external_value(PARAM_INT, 'Used to record page specific display selections'),
                'timecreated' => new external_value(PARAM_INT, 'Timestamp for when the page was created'),
                'timemodified' => new external_value(PARAM_INT, 'Timestamp for when the page was last modified'),
                'title' => new external_value(PARAM_RAW, 'The title of this page', VALUE_OPTIONAL),
                'contents' => new external_value(PARAM_RAW, 'The contents of this page', VALUE_OPTIONAL),
                'contentsformat' => new external_format_value('contents', VALUE_OPTIONAL),
                'displayinmenublock' => new external_value(PARAM_BOOL, 'Toggles display in the left menu block'),
                'type' => new external_value(PARAM_INT, 'The type of the page [question | structure]'),
                'typeid' => new external_value(PARAM_INT, 'The unique identifier for the page type'),
                'typestring' => new external_value(PARAM_RAW, 'The string that describes this page type'),
            ),
            'Page fields', $required
        );
    }

    /**
     * Returns the fields of a page object
     * @param amcat_page $page the amcat page
     * @param bool $returncontents whether to return the page title and contents
     * @return stdClass          the fields matching the external page structure
     * @since Moodle 3.3
     */
    protected static function get_page_fields(amcat_page $page, $returncontents = false) {
        $amcat = $page->amcat;
        $context = $amcat->context;

        $pagedata = new stdClass; // Contains the data that will be returned by the WS.

        // Return the visible data.
        $visibleproperties = array('id', 'amcatid', 'prevpageid', 'nextpageid', 'qtype', 'qoption', 'layout', 'display',
                                    'displayinmenublock', 'type', 'typeid', 'typestring', 'timecreated', 'timemodified');
        foreach ($visibleproperties as $prop) {
            $pagedata->{$prop} = $page->{$prop};
        }

        // Check if we can see title (contents required custom rendering, we won't returning it here @see get_page_data).
        $canmanage = $amcat->can_manage();
        // If we are managers or the menu block is enabled and is a content page visible always return contents.
        if ($returncontents || $canmanage || (amcat_displayleftif($amcat) && $page->displayinmenublock && $page->display)) {
            $pagedata->title = external_format_string($page->title, $context->id);

            $options = array('noclean' => true);
            list($pagedata->contents, $pagedata->contentsformat) =
                external_format_text($page->contents, $page->contentsformat, $context->id, 'mod_amcat', 'page_contents', $page->id,
                    $options);

        }
        return $pagedata;
    }

    /**
     * Describes the parameters for get_pages.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_pages_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'password' => new external_value(PARAM_RAW, 'optional password (the amcat may be protected)', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Return the list of pages in a amcat (based on the user permissions).
     *
     * @param int $amcatid amcat instance id
     * @param string $password optional password (the amcat may be protected)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_pages($amcatid, $password = '') {

        $params = array('amcatid' => $amcatid, 'password' => $password);
        $params = self::validate_parameters(self::get_pages_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);
        self::validate_attempt($amcat, $params);

        $amcatpages = $amcat->load_all_pages();
        $pages = array();

        foreach ($amcatpages as $page) {
            $pagedata = new stdClass();

            // Get the page object fields.
            $pagedata->page = self::get_page_fields($page);

            // Now, calculate the file area files (maybe we need to download a amcat for offline usage).
            $pagedata->filescount = 0;
            $pagedata->filessizetotal = 0;
            $files = $page->get_files(false);   // Get files excluding directories.
            foreach ($files as $file) {
                $pagedata->filescount++;
                $pagedata->filessizetotal += $file->get_filesize();
            }

            // Now the possible answers and page jumps ids.
            $pagedata->answerids = array();
            $pagedata->jumps = array();
            $answers = $page->get_answers();
            foreach ($answers as $answer) {
                $pagedata->answerids[] = $answer->id;
                $pagedata->jumps[] = $answer->jumpto;
                $files = $answer->get_files(false);   // Get files excluding directories.
                foreach ($files as $file) {
                    $pagedata->filescount++;
                    $pagedata->filessizetotal += $file->get_filesize();
                }
            }
            $pages[] = $pagedata;
        }

        $result = array();
        $result['pages'] = $pages;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_pages return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_pages_returns() {
        return new external_single_structure(
            array(
                'pages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'page' => self::get_page_structure(),
                            'answerids' => new external_multiple_structure(
                                new external_value(PARAM_INT, 'Answer id'), 'List of answers ids (empty for content pages in  Moodle 1.9)'
                            ),
                            'jumps' => new external_multiple_structure(
                                new external_value(PARAM_INT, 'Page to jump id'), 'List of possible page jumps'
                            ),
                            'filescount' => new external_value(PARAM_INT, 'The total number of files attached to the page'),
                            'filessizetotal' => new external_value(PARAM_INT, 'The total size of the files'),
                        ),
                        'The amcat pages'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for launch_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function launch_attempt_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'password' => new external_value(PARAM_RAW, 'optional password (the amcat may be protected)', VALUE_DEFAULT, ''),
                'pageid' => new external_value(PARAM_INT, 'page id to continue from (only when continuing an attempt)', VALUE_DEFAULT, 0),
                'review' => new external_value(PARAM_BOOL, 'if we want to review just after finishing', VALUE_DEFAULT, false),
            )
        );
    }

    /**
     * Return amcat messages formatted according the external_messages structure
     *
     * @param  amcat $amcat amcat instance
     * @return array          messages formatted
     * @since Moodle 3.3
     */
    protected static function format_amcat_messages($amcat) {
        $messages = array();
        foreach ($amcat->messages as $message) {
            $messages[] = array(
                'message' => $message[0],
                'type' => $message[1],
            );
        }
        return $messages;
    }

    /**
     * Return a external structure representing messages.
     *
     * @return external_multiple_structure messages structure
     * @since Moodle 3.3
     */
    protected static function external_messages() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'message' => new external_value(PARAM_RAW, 'Message.'),
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Message type: usually a CSS identifier like:
                                success, info, warning, error, notifyproblem, notifyerror, notifytiny, notifysuccess')
                ), 'The amcat generated messages'
            )
        );
    }

    /**
     * Starts a new attempt or continues an existing one.
     *
     * @param int $amcatid amcat instance id
     * @param string $password optional password (the amcat may be protected)
     * @param int $pageid page id to continue from (only when continuing an attempt)
     * @param bool $review if we want to review just after finishing
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function launch_attempt($amcatid, $password = '', $pageid = 0, $review = false) {
        global $CFG, $USER;

        $params = array('amcatid' => $amcatid, 'password' => $password, 'pageid' => $pageid, 'review' => $review);
        $params = self::validate_parameters(self::launch_attempt_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);
        self::validate_attempt($amcat, $params);

        $newpageid = 0;
        // Starting a new amcat attempt.
        if (empty($params['pageid'])) {
            // Check if there is a recent timer created during the active session.
            $alreadystarted = false;
            if ($timers = $amcat->get_user_timers($USER->id, 'starttime DESC', '*', 0, 1)) {
                $timer = array_shift($timers);
                $endtime = $amcat->timelimit > 0 ? min($CFG->sessiontimeout, $amcat->timelimit) : $CFG->sessiontimeout;
                if (!$timer->completed && $timer->starttime > time() - $endtime) {
                    $alreadystarted = true;
                }
            }
            if (!$alreadystarted && !$amcat->can_manage()) {
                $amcat->start_timer();
            }
        } else {
            if ($params['pageid'] == amcat_EOL) {
                throw new moodle_exception('endofamcat', 'amcat');
            }
            $timer = $amcat->update_timer(true, true);
            if (!$amcat->check_time($timer)) {
                throw new moodle_exception('eolstudentoutoftime', 'amcat');
            }
        }
        $messages = self::format_amcat_messages($amcat);

        $result = array(
            'status' => true,
            'messages' => $messages,
            'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Describes the launch_attempt return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function launch_attempt_returns() {
        return new external_single_structure(
            array(
                'messages' => self::external_messages(),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_page_data.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_page_data_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'pageid' => new external_value(PARAM_INT, 'the page id'),
                'password' => new external_value(PARAM_RAW, 'optional password (the amcat may be protected)', VALUE_DEFAULT, ''),
                'review' => new external_value(PARAM_BOOL, 'if we want to review just after finishing (1 hour margin)',
                    VALUE_DEFAULT, false),
                'returncontents' => new external_value(PARAM_BOOL, 'if we must return the complete page contents once rendered',
                    VALUE_DEFAULT, false),
            )
        );
    }

    /**
     * Return information of a given page, including its contents.
     *
     * @param int $amcatid amcat instance id
     * @param int $pageid page id
     * @param string $password optional password (the amcat may be protected)
     * @param bool $review if we want to review just after finishing (1 hour margin)
     * @param bool $returncontents if we must return the complete page contents once rendered
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_page_data($amcatid, $pageid,  $password = '', $review = false, $returncontents = false) {
        global $PAGE, $USER;

        $params = array('amcatid' => $amcatid, 'password' => $password, 'pageid' => $pageid, 'review' => $review,
            'returncontents' => $returncontents);
        $params = self::validate_parameters(self::get_page_data_parameters(), $params);

        $warnings = $contentfiles = $answerfiles = $responsefiles = $answers = array();
        $pagecontent = $ongoingscore = '';
        $progress = $pagedata = null;

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);
        self::validate_attempt($amcat, $params);

        $pageid = $params['pageid'];

        // This is called if a student leaves during a amcat.
        if ($pageid == amcat_UNSEENBRANCHPAGE) {
            $pageid = amcat_unseen_question_jump($amcat, $USER->id, $pageid);
        }

        if ($pageid != amcat_EOL) {
            $reviewmode = $amcat->is_in_review_mode();
            $amcatoutput = $PAGE->get_renderer('mod_amcat');
            // Prepare page contents avoiding redirections.
            list($pageid, $page, $pagecontent) = $amcat->prepare_page_and_contents($pageid, $amcatoutput, $reviewmode, false);

            if ($pageid > 0) {

                $pagedata = self::get_page_fields($page, true);

                // Files.
                $contentfiles = external_util::get_area_files($context->id, 'mod_amcat', 'page_contents', $page->id);

                // Answers.
                $answers = array();
                $pageanswers = $page->get_answers();
                foreach ($pageanswers as $a) {
                    $answer = array(
                        'id' => $a->id,
                        'answerfiles' => external_util::get_area_files($context->id, 'mod_amcat', 'page_answers', $a->id),
                        'responsefiles' => external_util::get_area_files($context->id, 'mod_amcat', 'page_responses', $a->id),
                    );
                    // For managers, return all the information (including correct answers, jumps).
                    // If the teacher enabled offline attempts, this information will be downloaded too.
                    if ($amcat->can_manage() || $amcat->allowofflineattempts) {
                        $extraproperties = array('jumpto', 'grade', 'score', 'flags', 'timecreated', 'timemodified');
                        foreach ($extraproperties as $prop) {
                            $answer[$prop] = $a->{$prop};
                        }

                        $options = array('noclean' => true);
                        list($answer['answer'], $answer['answerformat']) =
                            external_format_text($a->answer, $a->answerformat, $context->id, 'mod_amcat', 'page_answers', $a->id,
                                $options);
                        list($answer['response'], $answer['responseformat']) =
                            external_format_text($a->response, $a->responseformat, $context->id, 'mod_amcat', 'page_responses',
                                $a->id, $options);
                    }
                    $answers[] = $answer;
                }

                // Additional amcat information.
                if (!$amcat->can_manage()) {
                    if ($amcat->ongoing && !$reviewmode) {
                        $ongoingscore = $amcat->get_ongoing_score_message();
                    }
                    if ($amcat->progressbar) {
                        $progress = $amcat->calculate_progress();
                    }
                }
            }
        }

        $messages = self::format_amcat_messages($amcat);

        $result = array(
            'newpageid' => $pageid,
            'ongoingscore' => $ongoingscore,
            'progress' => $progress,
            'contentfiles' => $contentfiles,
            'answers' => $answers,
            'messages' => $messages,
            'warnings' => $warnings,
            'displaymenu' => !empty(amcat_displayleftif($amcat)),
        );

        if (!empty($pagedata)) {
            $result['page'] = $pagedata;
        }
        if ($params['returncontents']) {
            $result['pagecontent'] = $pagecontent;  // Return the complete page contents rendered.
        }

        return $result;
    }

    /**
     * Describes the get_page_data return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_page_data_returns() {
        return new external_single_structure(
            array(
                'page' => self::get_page_structure(VALUE_OPTIONAL),
                'newpageid' => new external_value(PARAM_INT, 'New page id (if a jump was made)'),
                'pagecontent' => new external_value(PARAM_RAW, 'Page html content', VALUE_OPTIONAL),
                'ongoingscore' => new external_value(PARAM_TEXT, 'The ongoing score message'),
                'progress' => new external_value(PARAM_INT, 'Progress percentage in the amcat'),
                'contentfiles' => new external_files(),
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The ID of this answer in the database'),
                            'answerfiles' => new external_files(),
                            'responsefiles' => new external_files(),
                            'jumpto' => new external_value(PARAM_INT, 'Identifies where the user goes upon completing a page with this answer',
                                                            VALUE_OPTIONAL),
                            'grade' => new external_value(PARAM_INT, 'The grade this answer is worth', VALUE_OPTIONAL),
                            'score' => new external_value(PARAM_INT, 'The score this answer will give', VALUE_OPTIONAL),
                            'flags' => new external_value(PARAM_INT, 'Used to store options for the answer', VALUE_OPTIONAL),
                            'timecreated' => new external_value(PARAM_INT, 'A timestamp of when the answer was created', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT, 'A timestamp of when the answer was modified', VALUE_OPTIONAL),
                            'answer' => new external_value(PARAM_RAW, 'Possible answer text', VALUE_OPTIONAL),
                            'answerformat' => new external_format_value('answer', VALUE_OPTIONAL),
                            'response' => new external_value(PARAM_RAW, 'Response text for the answer', VALUE_OPTIONAL),
                            'responseformat' => new external_format_value('response', VALUE_OPTIONAL),
                        ), 'The page answers'

                    )
                ),
                'messages' => self::external_messages(),
                'displaymenu' => new external_value(PARAM_BOOL, 'Whether we should display the menu or not in this page.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for process_page.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function process_page_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'pageid' => new external_value(PARAM_INT, 'the page id'),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'data name'),
                            'value' => new external_value(PARAM_RAW, 'data value'),
                        )
                    ), 'the data to be saved'
                ),
                'password' => new external_value(PARAM_RAW, 'optional password (the amcat may be protected)', VALUE_DEFAULT, ''),
                'review' => new external_value(PARAM_BOOL, 'if we want to review just after finishing (1 hour margin)',
                    VALUE_DEFAULT, false),
            )
        );
    }

    /**
     * Processes page responses
     *
     * @param int $amcatid amcat instance id
     * @param int $pageid page id
     * @param array $data the data to be saved
     * @param string $password optional password (the amcat may be protected)
     * @param bool $review if we want to review just after finishing (1 hour margin)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function process_page($amcatid, $pageid,  $data, $password = '', $review = false) {
        global $USER;

        $params = array('amcatid' => $amcatid, 'pageid' => $pageid, 'data' => $data, 'password' => $password,
            'review' => $review);
        $params = self::validate_parameters(self::process_page_parameters(), $params);

        $warnings = array();
        $pagecontent = $ongoingscore = '';
        $progress = null;

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Update timer so the validation can check the time restrictions.
        $timer = $amcat->update_timer();
        self::validate_attempt($amcat, $params);

        // Create the $_POST object required by the amcat question engine.
        $_POST = array();
        foreach ($data as $element) {
            // First check if we are handling editor fields like answer[text].
            if (preg_match('/(.+)\[(.+)\]$/', $element['name'], $matches)) {
                $_POST[$matches[1]][$matches[2]] = $element['value'];
            } else {
                $_POST[$element['name']] = $element['value'];
            }
        }

        // Ignore sesskey (deep in some APIs), the request is already validated.
        $USER->ignoresesskey = true;

        // Process page.
        $page = $amcat->load_page($params['pageid']);
        $result = $amcat->process_page_responses($page);

        // Prepare messages.
        $reviewmode = $amcat->is_in_review_mode();
        $amcat->add_messages_on_page_process($page, $result, $reviewmode);

        // Additional amcat information.
        if (!$amcat->can_manage()) {
            if ($amcat->ongoing && !$reviewmode) {
                $ongoingscore = $amcat->get_ongoing_score_message();
            }
            if ($amcat->progressbar) {
                $progress = $amcat->calculate_progress();
            }
        }

        // Check conditionally everything coming from result (except newpageid because is always set).
        $result = array(
            'newpageid'         => (int) $result->newpageid,
            'inmediatejump'     => $result->inmediatejump,
            'nodefaultresponse' => !empty($result->nodefaultresponse),
            'feedback'          => (isset($result->feedback)) ? $result->feedback : '',
            'attemptsremaining' => (isset($result->attemptsremaining)) ? $result->attemptsremaining : null,
            'correctanswer'     => !empty($result->correctanswer),
            'noanswer'          => !empty($result->noanswer),
            'isessayquestion'   => !empty($result->isessayquestion),
            'maxattemptsreached' => !empty($result->maxattemptsreached),
            'response'          => (isset($result->response)) ? $result->response : '',
            'studentanswer'     => (isset($result->studentanswer)) ? $result->studentanswer : '',
            'userresponse'      => (isset($result->userresponse)) ? $result->userresponse : '',
            'reviewmode'        => $reviewmode,
            'ongoingscore'      => $ongoingscore,
            'progress'          => $progress,
            'displaymenu'       => !empty(amcat_displayleftif($amcat)),
            'messages'          => self::format_amcat_messages($amcat),
            'warnings'          => $warnings,
        );
        return $result;
    }

    /**
     * Describes the process_page return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function process_page_returns() {
        return new external_single_structure(
            array(
                'newpageid' => new external_value(PARAM_INT, 'New page id (if a jump was made).'),
                'inmediatejump' => new external_value(PARAM_BOOL, 'Whether the page processing redirect directly to anoter page.'),
                'nodefaultresponse' => new external_value(PARAM_BOOL, 'Whether there is not a default response.'),
                'feedback' => new external_value(PARAM_RAW, 'The response feedback.'),
                'attemptsremaining' => new external_value(PARAM_INT, 'Number of attempts remaining.'),
                'correctanswer' => new external_value(PARAM_BOOL, 'Whether the answer is correct.'),
                'noanswer' => new external_value(PARAM_BOOL, 'Whether there aren\'t answers.'),
                'isessayquestion' => new external_value(PARAM_BOOL, 'Whether is a essay question.'),
                'maxattemptsreached' => new external_value(PARAM_BOOL, 'Whether we reachered the max number of attempts.'),
                'response' => new external_value(PARAM_RAW, 'The response.'),
                'studentanswer' => new external_value(PARAM_RAW, 'The student answer.'),
                'userresponse' => new external_value(PARAM_RAW, 'The user response.'),
                'reviewmode' => new external_value(PARAM_BOOL, 'Whether the user is reviewing.'),
                'ongoingscore' => new external_value(PARAM_TEXT, 'The ongoing message.'),
                'progress' => new external_value(PARAM_INT, 'Progress percentage in the amcat.'),
                'displaymenu' => new external_value(PARAM_BOOL, 'Whether we should display the menu or not in this page.'),
                'messages' => self::external_messages(),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for finish_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function finish_attempt_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id.'),
                'password' => new external_value(PARAM_RAW, 'Optional password (the amcat may be protected).', VALUE_DEFAULT, ''),
                'outoftime' => new external_value(PARAM_BOOL, 'If the user run out of time.', VALUE_DEFAULT, false),
                'review' => new external_value(PARAM_BOOL, 'If we want to review just after finishing (1 hour margin).',
                    VALUE_DEFAULT, false),
            )
        );
    }

    /**
     * Finishes the current attempt.
     *
     * @param int $amcatid amcat instance id
     * @param string $password optional password (the amcat may be protected)
     * @param bool $outoftime optional if the user run out of time
     * @param bool $review if we want to review just after finishing (1 hour margin)
     * @return array of warnings and information about the finished attempt
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function finish_attempt($amcatid, $password = '', $outoftime = false, $review = false) {

        $params = array('amcatid' => $amcatid, 'password' => $password, 'outoftime' => $outoftime, 'review' => $review);
        $params = self::validate_parameters(self::finish_attempt_parameters(), $params);

        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Update timer so the validation can check the time restrictions.
        $timer = $amcat->update_timer();

        // Return the validation to avoid exceptions in case the user is out of time.
        $params['pageid'] = amcat_EOL;
        $validation = self::validate_attempt($amcat, $params, true);

        if (array_key_exists('eolstudentoutoftime', $validation)) {
            // Maybe we run out of time just now.
            $params['outoftime'] = true;
            unset($validation['eolstudentoutoftime']);
        }
        // Check if there are more errors.
        if (!empty($validation)) {
            reset($validation);
            throw new moodle_exception(key($validation), 'amcat', '', current($validation));   // Throw first error.
        }

        // Set out of time to normal (it is the only existing mode).
        $outoftimemode = $params['outoftime'] ? 'normal' : '';
        $result = $amcat->process_eol_page($outoftimemode);

        // Return the data.
         $validmessages = array(
            'notenoughtimespent', 'numberofpagesviewed', 'youshouldview', 'numberofcorrectanswers',
            'displayscorewithessays', 'displayscorewithoutessays', 'yourcurrentgradeisoutof', 'eolstudentoutoftimenoanswers',
            'welldone', 'displayofgrade', 'modattemptsnoteacher', 'progresscompleted');

        $data = array();
        foreach ($result as $el => $value) {
            if ($value !== false) {
                $message = '';
                if (in_array($el, $validmessages)) { // Check if the data comes with an informative message.
                    $a = (is_bool($value)) ? null : $value;
                    $message = get_string($el, 'amcat', $a);
                }
                // Return the data.
                $data[] = array(
                    'name' => $el,
                    'value' => (is_bool($value)) ? 1 : json_encode($value), // The data can be a php object.
                    'message' => $message
                );
            }
        }

        $result = array(
            'data'     => $data,
            'messages' => self::format_amcat_messages($amcat),
            'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Describes the finish_attempt return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function finish_attempt_returns() {
        return new external_single_structure(
            array(
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUMEXT, 'Data name.'),
                            'value' => new external_value(PARAM_RAW, 'Data value.'),
                            'message' => new external_value(PARAM_RAW, 'Data message (translated string).'),
                        )
                    ), 'The EOL page information data.'
                ),
                'messages' => self::external_messages(),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_attempts_overview.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_attempts_overview_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'groupid' => new external_value(PARAM_INT, 'group id, 0 means that the function will determine the user group',
                                                VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Get a list of all the attempts made by users in a amcat.
     *
     * @param int $amcatid amcat instance id
     * @param int $groupid group id, 0 means that the function will determine the user group
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_attempts_overview($amcatid, $groupid = 0) {

        $params = array('amcatid' => $amcatid, 'groupid' => $groupid);
        $params = self::validate_parameters(self::get_attempts_overview_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);
        require_capability('mod/amcat:viewreports', $context);

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        $result = array(
            'warnings' => $warnings
        );

        list($table, $data) = amcat_get_overview_report_table_and_data($amcat, $groupid);
        if ($data !== false) {
            $result['data'] = $data;
        }

        return $result;
    }

    /**
     * Describes the get_attempts_overview return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_attempts_overview_returns() {
        return new external_single_structure(
            array(
                'data' => new external_single_structure(
                    array(
                        'amcatscored' => new external_value(PARAM_BOOL, 'True if the amcat was scored.'),
                        'numofattempts' => new external_value(PARAM_INT, 'Number of attempts.'),
                        'avescore' => new external_value(PARAM_FLOAT, 'Average score.'),
                        'highscore' => new external_value(PARAM_FLOAT, 'High score.'),
                        'lowscore' => new external_value(PARAM_FLOAT, 'Low score.'),
                        'avetime' => new external_value(PARAM_INT, 'Average time (spent in taking the amcat).'),
                        'hightime' => new external_value(PARAM_INT, 'High time.'),
                        'lowtime' => new external_value(PARAM_INT, 'Low time.'),
                        'students' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'User id.'),
                                    'fullname' => new external_value(PARAM_TEXT, 'User full name.'),
                                    'bestgrade' => new external_value(PARAM_FLOAT, 'Best grade.'),
                                    'attempts' => new external_multiple_structure(
                                        new external_single_structure(
                                            array(
                                                'try' => new external_value(PARAM_INT, 'Attempt number.'),
                                                'grade' => new external_value(PARAM_FLOAT, 'Attempt grade.'),
                                                'timestart' => new external_value(PARAM_INT, 'Attempt time started.'),
                                                'timeend' => new external_value(PARAM_INT, 'Attempt last time continued.'),
                                                'end' => new external_value(PARAM_INT, 'Attempt time ended.'),
                                            )
                                        )
                                    )
                                )
                            ), 'Students data, including attempts.', VALUE_OPTIONAL
                        ),
                    ),
                    'Attempts overview data (empty for no attemps).', VALUE_OPTIONAL
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_user_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_user_attempt_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id.'),
                'userid' => new external_value(PARAM_INT, 'The user id. 0 for current user.'),
                'amcatattempt' => new external_value(PARAM_INT, 'The attempt number.'),
            )
        );
    }

    /**
     * Return information about the given user attempt (including answers).
     *
     * @param int $amcatid amcat instance id
     * @param int $userid the user id
     * @param int $amcatattempt the attempt number
     * @return array of warnings and page attempts
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_user_attempt($amcatid, $userid, $amcatattempt) {
        global $USER;

        $params = array(
            'amcatid' => $amcatid,
            'userid' => $userid,
            'amcatattempt' => $amcatattempt,
        );
        $params = self::validate_parameters(self::get_user_attempt_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        // Extra checks so only users with permissions can view other users attempts.
        if ($USER->id != $params['userid']) {
            self::check_can_view_user_data($params['userid'], $course, $cm, $context);
        }

        list($answerpages, $userstats) = amcat_get_user_detailed_report_data($amcat, $userid, $params['amcatattempt']);
        // Convert page object to page record.
        foreach ($answerpages as $answerp) {
            $answerp->page = self::get_page_fields($answerp->page);
        }

        $result = array(
            'answerpages' => $answerpages,
            'userstats' => $userstats,
            'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Describes the get_user_attempt return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_user_attempt_returns() {
        return new external_single_structure(
            array(
                'answerpages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'page' => self::get_page_structure(VALUE_OPTIONAL),
                            'title' => new external_value(PARAM_RAW, 'Page title.'),
                            'contents' => new external_value(PARAM_RAW, 'Page contents.'),
                            'qtype' => new external_value(PARAM_TEXT, 'Identifies the page type of this page.'),
                            'grayout' => new external_value(PARAM_INT, 'If is required to apply a grayout.'),
                            'answerdata' => new external_single_structure(
                                array(
                                    'score' => new external_value(PARAM_TEXT, 'The score (text version).'),
                                    'response' => new external_value(PARAM_RAW, 'The response text.'),
                                    'responseformat' => new external_format_value('response.'),
                                    'answers' => new external_multiple_structure(
                                        new external_multiple_structure(new external_value(PARAM_RAW, 'Possible answers and info.')),
                                        'User answers',
                                        VALUE_OPTIONAL
                                    ),
                                ), 'Answer data (empty in content pages created in Moodle 1.x).', VALUE_OPTIONAL
                            )
                        )
                    )
                ),
                'userstats' => new external_single_structure(
                    array(
                        'grade' => new external_value(PARAM_FLOAT, 'Attempt final grade.'),
                        'completed' => new external_value(PARAM_INT, 'Time completed.'),
                        'timetotake' => new external_value(PARAM_INT, 'Time taken.'),
                        'gradeinfo' => self::get_user_attempt_grade_structure(VALUE_OPTIONAL)
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_pages_possible_jumps.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_pages_possible_jumps_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
            )
        );
    }

    /**
     * Return all the possible jumps for the pages in a given amcat.
     *
     * You may expect different results on consecutive executions due to the random nature of the amcat module.
     *
     * @param int $amcatid amcat instance id
     * @return array of warnings and possible jumps
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_pages_possible_jumps($amcatid) {
        global $USER;

        $params = array('amcatid' => $amcatid);
        $params = self::validate_parameters(self::get_pages_possible_jumps_parameters(), $params);

        $warnings = $jumps = array();

        list($amcat, $course, $cm, $context) = self::validate_amcat($params['amcatid']);

        // Only return for managers or if offline attempts are enabled.
        if ($amcat->can_manage() || $amcat->allowofflineattempts) {

            $amcatpages = $amcat->load_all_pages();
            foreach ($amcatpages as $page) {
                $jump = array();
                $jump['pageid'] = $page->id;

                $answers = $page->get_answers();
                if (count($answers) > 0) {
                    foreach ($answers as $answer) {
                        $jump['answerid'] = $answer->id;
                        $jump['jumpto'] = $answer->jumpto;
                        $jump['calculatedjump'] = $amcat->calculate_new_page_on_jump($page, $answer->jumpto);
                        // Special case, only applies to branch/end of branch.
                        if ($jump['calculatedjump'] == amcat_RANDOMBRANCH) {
                            $jump['calculatedjump'] = amcat_unseen_branch_jump($amcat, $USER->id);
                        }
                        $jumps[] = $jump;
                    }
                } else {
                    // Imported amcats from 1.x.
                    $jump['answerid'] = 0;
                    $jump['jumpto'] = $page->nextpageid;
                    $jump['calculatedjump'] = $amcat->calculate_new_page_on_jump($page, $page->nextpageid);
                    $jumps[] = $jump;
                }
            }
        }

        $result = array(
            'jumps' => $jumps,
            'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Describes the get_pages_possible_jumps return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_pages_possible_jumps_returns() {
        return new external_single_structure(
            array(
                'jumps' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'pageid' => new external_value(PARAM_INT, 'The page id'),
                            'answerid' => new external_value(PARAM_INT, 'The answer id'),
                            'jumpto' => new external_value(PARAM_INT, 'The jump (page id or type of jump)'),
                            'calculatedjump' => new external_value(PARAM_INT, 'The real page id (or EOL) to jump'),
                        ), 'Jump for a page answer'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_amcat.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_amcat_parameters() {
        return new external_function_parameters (
            array(
                'amcatid' => new external_value(PARAM_INT, 'amcat instance id'),
                'password' => new external_value(PARAM_RAW, 'amcat password', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Return information of a given amcat.
     *
     * @param int $amcatid amcat instance id
     * @param string $password optional password (the amcat may be protected)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_amcat($amcatid, $password = '') {
        global $PAGE;

        $params = array('amcatid' => $amcatid, 'password' => $password);
        $params = self::validate_parameters(self::get_amcat_parameters(), $params);
        $warnings = array();

        list($amcat, $course, $cm, $context, $amcatrecord) = self::validate_amcat($params['amcatid']);

        $amcatrecord = self::get_amcat_summary_for_exporter($amcatrecord, $params['password']);
        $exporter = new amcat_summary_exporter($amcatrecord, array('context' => $context));

        $result = array();
        $result['amcat'] = $exporter->export($PAGE->get_renderer('core'));
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_amcat return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_amcat_returns() {
        return new external_single_structure(
            array(
                'amcat' => amcat_summary_exporter::get_read_structure(),
                'warnings' => new external_warnings(),
            )
        );
    }
}
