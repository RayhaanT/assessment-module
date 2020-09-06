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
 * @package mod_amcat
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_amcat_activity_task
 */

/**
 * Structure step to restore one amcat activity
 */
class restore_amcat_activity_structure_step extends restore_activity_structure_step {
    // Store the answers as they're received but only process them at the
    // end of the amcat
    protected $answers = array();

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('amcat', '/activity/amcat');
        $paths[] = new restore_path_element('amcat_page', '/activity/amcat/pages/page');
        $paths[] = new restore_path_element('amcat_answer', '/activity/amcat/pages/page/answers/answer');
        $paths[] = new restore_path_element('amcat_override', '/activity/amcat/overrides/override');
        if ($userinfo) {
            $paths[] = new restore_path_element('amcat_attempt', '/activity/amcat/pages/page/answers/answer/attempts/attempt');
            $paths[] = new restore_path_element('amcat_grade', '/activity/amcat/grades/grade');
            $paths[] = new restore_path_element('amcat_branch', '/activity/amcat/pages/page/branches/branch');
            $paths[] = new restore_path_element('amcat_highscore', '/activity/amcat/highscores/highscore');
            $paths[] = new restore_path_element('amcat_timer', '/activity/amcat/timers/timer');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_amcat($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->available = $this->apply_date_offset($data->available);
        $data->deadline = $this->apply_date_offset($data->deadline);

        // The amcat->highscore code was removed in MDL-49581.
        // Remove it if found in the backup file.
        if (isset($data->showhighscores)) {
            unset($data->showhighscores);
        }
        if (isset($data->highscores)) {
            unset($data->highscores);
        }

        // Supply items that maybe missing from previous versions.
        if (!isset($data->completionendreached)) {
            $data->completionendreached = 0;
        }
        if (!isset($data->completiontimespent)) {
            $data->completiontimespent = 0;
        }

        if (!isset($data->intro)) {
            $data->intro = '';
            $data->introformat = FORMAT_HTML;
        }

        // Compatibility with old backups with maxtime and timed fields.
        if (!isset($data->timelimit)) {
            if (isset($data->timed) && isset($data->maxtime) && $data->timed) {
                $data->timelimit = 60 * $data->maxtime;
            } else {
                $data->timelimit = 0;
            }
        }
        // insert the amcat record
        $newitemid = $DB->insert_record('amcat', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_amcat_page($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->amcatid = $this->get_new_parentid('amcat');

        // We'll remap all the prevpageid and nextpageid at the end, once all pages have been created

        $newitemid = $DB->insert_record('amcat_pages', $data);
        $this->set_mapping('amcat_page', $oldid, $newitemid, true); // Has related fileareas
    }

    protected function process_amcat_answer($data) {
        global $DB;

        $data = (object)$data;
        $data->amcatid = $this->get_new_parentid('amcat');
        $data->pageid = $this->get_new_parentid('amcat_page');
        $data->answer = $data->answer_text;

        // Set a dummy mapping to get the old ID so that it can be used by get_old_parentid when
        // processing attempts. It will be corrected in after_execute
        $this->set_mapping('amcat_answer', $data->id, 0, true); // Has related fileareas.

        // Answers need to be processed in order, so we store them in an
        // instance variable and insert them in the after_execute stage
        $this->answers[$data->id] = $data;
    }

    protected function process_amcat_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->amcatid = $this->get_new_parentid('amcat');
        $data->pageid = $this->get_new_parentid('amcat_page');

        // We use the old answerid here as the answer isn't created until after_execute
        $data->answerid = $this->get_old_parentid('amcat_answer');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('amcat_attempts', $data);
        $this->set_mapping('amcat_attempt', $oldid, $newitemid, true); // Has related fileareas.
    }

    protected function process_amcat_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->amcatid = $this->get_new_parentid('amcat');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('amcat_grades', $data);
        $this->set_mapping('amcat_grade', $oldid, $newitemid);
    }

    protected function process_amcat_branch($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->amcatid = $this->get_new_parentid('amcat');
        $data->pageid = $this->get_new_parentid('amcat_page');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('amcat_branch', $data);
    }

    protected function process_amcat_highscore($data) {
        // Do not process any high score data.
        // high scores were removed in Moodle 3.0 See MDL-49581.
    }

    protected function process_amcat_timer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->amcatid = $this->get_new_parentid('amcat');
        $data->userid = $this->get_mappingid('user', $data->userid);
        // Supply item that maybe missing from previous versions.
        if (!isset($data->completed)) {
            $data->completed = 0;
        }
        $newitemid = $DB->insert_record('amcat_timer', $data);
    }

    /**
     * Process a amcat override restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_amcat_override($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Based on userinfo, we'll restore user overides or no.
        $userinfo = $this->get_setting_value('userinfo');

        // Skip user overrides if we are not restoring userinfo.
        if (!$userinfo && !is_null($data->userid)) {
            return;
        }

        $data->amcatid = $this->get_new_parentid('amcat');

        if (!is_null($data->userid)) {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }
        if (!is_null($data->groupid)) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        }

        // Skip if there is no user and no group data.
        if (empty($data->userid) && empty($data->groupid)) {
            return;
        }

        $data->available = $this->apply_date_offset($data->available);
        $data->deadline = $this->apply_date_offset($data->deadline);

        $newitemid = $DB->insert_record('amcat_overrides', $data);

        // Add mapping, restore of logs needs it.
        $this->set_mapping('amcat_override', $oldid, $newitemid);
    }

    protected function after_execute() {
        global $DB;

        // Answers must be sorted by id to ensure that they're shown correctly
        ksort($this->answers);
        foreach ($this->answers as $answer) {
            $newitemid = $DB->insert_record('amcat_answers', $answer);
            $this->set_mapping('amcat_answer', $answer->id, $newitemid, true);

            // Update the amcat attempts to use the newly created answerid
            $DB->set_field('amcat_attempts', 'answerid', $newitemid, array(
                    'amcatid' => $answer->amcatid,
                    'pageid' => $answer->pageid,
                    'answerid' => $answer->id));
        }

        // Add amcat files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_amcat', 'intro', null);
        $this->add_related_files('mod_amcat', 'mediafile', null);
        // Add amcat page files, by amcat_page itemname
        $this->add_related_files('mod_amcat', 'page_contents', 'amcat_page');
        $this->add_related_files('mod_amcat', 'page_answers', 'amcat_answer');
        $this->add_related_files('mod_amcat', 'page_responses', 'amcat_answer');
        $this->add_related_files('mod_amcat', 'essay_responses', 'amcat_attempt');
        $this->add_related_files('mod_amcat', 'essay_answers', 'amcat_attempt');

        // Remap all the restored prevpageid and nextpageid now that we have all the pages and their mappings
        $rs = $DB->get_recordset('amcat_pages', array('amcatid' => $this->task->get_activityid()),
                                 '', 'id, prevpageid, nextpageid');
        foreach ($rs as $page) {
            $page->prevpageid = (empty($page->prevpageid)) ? 0 : $this->get_mappingid('amcat_page', $page->prevpageid);
            $page->nextpageid = (empty($page->nextpageid)) ? 0 : $this->get_mappingid('amcat_page', $page->nextpageid);
            $DB->update_record('amcat_pages', $page);
        }
        $rs->close();

        // Remap all the restored 'jumpto' fields now that we have all the pages and their mappings
        $rs = $DB->get_recordset('amcat_answers', array('amcatid' => $this->task->get_activityid()),
                                 '', 'id, jumpto');
        foreach ($rs as $answer) {
            if ($answer->jumpto > 0) {
                $answer->jumpto = $this->get_mappingid('amcat_page', $answer->jumpto);
                $DB->update_record('amcat_answers', $answer);
            }
        }
        $rs->close();

        // Remap all the restored 'nextpageid' fields now that we have all the pages and their mappings.
        $rs = $DB->get_recordset('amcat_branch', array('amcatid' => $this->task->get_activityid()),
                                 '', 'id, nextpageid');
        foreach ($rs as $answer) {
            if ($answer->nextpageid > 0) {
                $answer->nextpageid = $this->get_mappingid('amcat_page', $answer->nextpageid);
                $DB->update_record('amcat_branch', $answer);
            }
        }
        $rs->close();

        // Replay the upgrade step 2015030301
        // to clean amcat answers that should be plain text.
        // 1 = amcat_PAGE_SHORTANSWER, 8 = amcat_PAGE_NUMERICAL, 20 = amcat_PAGE_BRANCHTABLE.

        $sql = 'SELECT a.*
                  FROM {amcat_answers} a
                  JOIN {amcat_pages} p ON p.id = a.pageid
                 WHERE a.answerformat <> :format
                   AND a.amcatid = :amcatid
                   AND p.qtype IN (1, 8, 20)';
        $badanswers = $DB->get_recordset_sql($sql, array('amcatid' => $this->task->get_activityid(), 'format' => FORMAT_MOODLE));

        foreach ($badanswers as $badanswer) {
            // Strip tags from answer text and convert back the format to FORMAT_MOODLE.
            $badanswer->answer = strip_tags($badanswer->answer);
            $badanswer->answerformat = FORMAT_MOODLE;
            $DB->update_record('amcat_answers', $badanswer);
        }
        $badanswers->close();

        // Replay the upgrade step 2015032700.
        // Delete any orphaned amcat_branch record.
        if ($DB->get_dbfamily() === 'mysql') {
            $sql = "DELETE {amcat_branch}
                      FROM {amcat_branch}
                 LEFT JOIN {amcat_pages}
                        ON {amcat_branch}.pageid = {amcat_pages}.id
                     WHERE {amcat_pages}.id IS NULL";
        } else {
            $sql = "DELETE FROM {amcat_branch}
               WHERE NOT EXISTS (
                         SELECT 'x' FROM {amcat_pages}
                          WHERE {amcat_branch}.pageid = {amcat_pages}.id)";
        }

        $DB->execute($sql);
    }
}
