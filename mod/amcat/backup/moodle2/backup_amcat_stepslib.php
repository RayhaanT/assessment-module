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
 * This file contains the backup structure for the amcat module
 *
 * This is the "graphical" structure of the amcat module:
 *
 *         amcat ---------->-------------|------------>---------|----------->----------|
 *      (CL,pk->id)                       |                      |                      |
 *            |                           |                      |                      |
 *            |                     amcat_grades           amcat_timer           amcat_overrides
 *            |            (UL, pk->id,fk->amcatid)  (UL, pk->id,fk->amcatid) (UL, pk->id,fk->amcatid)
 *            |                           |
 *            |                           |
 *            |                           |
 *            |                           |
 *      amcat_pages-------->-------amcat_branch
 *   (CL,pk->id,fk->amcatid)     (UL, pk->id,fk->pageid)
 *            |
 *            |
 *            |
 *      amcat_answers
 *   (CL,pk->id,fk->pageid)
 *            |
 *            |
 *            |
 *      amcat_attempts
 *  (UL,pk->id,fk->answerid)
 *
 * Meaning: pk->primary key field of the table
 *          fk->foreign key to link with parent
 *          nt->nested field (recursive data)
 *          CL->course level info
 *          UL->user level info
 *          files->table may have files)
 *
 * @package mod_amcat
 * @copyright  2010 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step class that informs a backup task how to backup the amcat module.
 *
 * @copyright  2010 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_amcat_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // The amcat table
        // This table contains all of the goodness for the amcat module, quite
        // alot goes into it but nothing relational other than course when will
        // need to be corrected upon restore.
        $amcat = new backup_nested_element('amcat', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'practice', 'modattempts',
            'usepassword', 'password',
            'dependency', 'conditions', 'grade', 'custom', 'ongoing', 'usemaxgrade',
            'maxanswers', 'maxattempts', 'review', 'nextpagedefault', 'feedback',
            'minquestions', 'maxpages', 'timelimit', 'retake', 'activitylink',
            'mediafile', 'mediaheight', 'mediawidth', 'mediaclose', 'slideshow',
            'width', 'height', 'bgcolor', 'displayleft', 'displayleftif', 'progressbar',
            'available', 'deadline', 'timemodified',
            'completionendreached', 'completiontimespent', 'allowofflineattempts'
        ));

        // The amcat_pages table
        // Grouped within a `pages` element, important to note that page is relational
        // to the amcat, and also to the previous/next page in the series.
        // Upon restore prevpageid and nextpageid will need to be corrected.
        $pages = new backup_nested_element('pages');
        $page = new backup_nested_element('page', array('id'), array(
            'prevpageid','nextpageid','qtype','qoption','layout',
            'display','timecreated','timemodified','title','contents',
            'contentsformat'
        ));

        // The amcat_answers table
        // Grouped within an answers `element`, the amcat_answers table relates
        // to the page and amcat with `pageid` and `amcatid` that will both need
        // to be corrected during restore.
        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', array('id'), array(
            'jumpto','grade','score','flags','timecreated','timemodified','answer_text',
            'response', 'answerformat', 'responseformat'
        ));
        // Tell the answer element about the answer_text elements mapping to the answer
        // database field.
        $answer->set_source_alias('answer', 'answer_text');

        // The amcat_attempts table
        // Grouped by an `attempts` element this is relational to the page, amcat,
        // and user.
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'), array(
            'userid','retry','correct','useranswer','timeseen'
        ));

        // The amcat_branch table
        // Grouped by a `branch` element this is relational to the page, amcat,
        // and user.
        $branches = new backup_nested_element('branches');
        $branch = new backup_nested_element('branch', array('id'), array(
             'userid', 'retry', 'flag', 'timeseen', 'nextpageid'
        ));

        // The amcat_grades table
        // Grouped by a grades element this is relational to the amcat and user.
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
            'userid','grade','late','completed'
        ));

        // The amcat_timer table
        // Grouped by a `timers` element this is relational to the amcat and user.
        $timers = new backup_nested_element('timers');
        $timer = new backup_nested_element('timer', array('id'), array(
            'userid', 'starttime', 'amcattime', 'completed', 'timemodifiedoffline'
        ));

        $overrides = new backup_nested_element('overrides');
        $override = new backup_nested_element('override', array('id'), array(
            'groupid', 'userid', 'available', 'deadline', 'timelimit',
            'review', 'maxattempts', 'retake', 'password'));

        // Now that we have all of the elements created we've got to put them
        // together correctly.
        $amcat->add_child($pages);
        $pages->add_child($page);
        $page->add_child($answers);
        $answers->add_child($answer);
        $answer->add_child($attempts);
        $attempts->add_child($attempt);
        $page->add_child($branches);
        $branches->add_child($branch);
        $amcat->add_child($grades);
        $grades->add_child($grade);
        $amcat->add_child($timers);
        $timers->add_child($timer);
        $amcat->add_child($overrides);
        $overrides->add_child($override);

        // Set the source table for the elements that aren't reliant on the user
        // at this point (amcat, amcat_pages, amcat_answers)
        $amcat->set_source_table('amcat', array('id' => backup::VAR_ACTIVITYID));
        //we use SQL here as it must be ordered by prevpageid so that restore gets the pages in the right order.
        $page->set_source_table('amcat_pages', array('amcatid' => backup::VAR_PARENTID), 'prevpageid ASC');

        // We use SQL here as answers must be ordered by id so that the restore gets them in the right order
        $answer->set_source_table('amcat_answers', array('pageid' => backup::VAR_PARENTID), 'id ASC');

        // amcat overrides to backup are different depending of user info.
        $overrideparams = array('amcatid' => backup::VAR_PARENTID);

        // Check if we are also backing up user information
        if ($this->get_setting_value('userinfo')) {
            // Set the source table for elements that are reliant on the user
            // amcat_attempts, amcat_branch, amcat_grades, amcat_timer.
            $attempt->set_source_table('amcat_attempts', array('answerid' => backup::VAR_PARENTID));
            $branch->set_source_table('amcat_branch', array('pageid' => backup::VAR_PARENTID));
            $grade->set_source_table('amcat_grades', array('amcatid'=>backup::VAR_PARENTID));
            $timer->set_source_table('amcat_timer', array('amcatid' => backup::VAR_PARENTID));
        } else {
            $overrideparams['userid'] = backup_helper::is_sqlparam(null); //  Without userinfo, skip user overrides.
        }

        // Skip group overrides if not including groups.
        $groupinfo = $this->get_setting_value('groups');
        if (!$groupinfo) {
            $overrideparams['groupid'] = backup_helper::is_sqlparam(null);
        }

        $override->set_source_table('amcat_overrides', $overrideparams);

        // Annotate the user id's where required.
        $attempt->annotate_ids('user', 'userid');
        $branch->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'userid');
        $timer->annotate_ids('user', 'userid');
        $override->annotate_ids('user', 'userid');
        $override->annotate_ids('group', 'groupid');

        // Annotate the file areas in user by the amcat module.
        $amcat->annotate_files('mod_amcat', 'intro', null);
        $amcat->annotate_files('mod_amcat', 'mediafile', null);
        $page->annotate_files('mod_amcat', 'page_contents', 'id');
        $answer->annotate_files('mod_amcat', 'page_answers', 'id');
        $answer->annotate_files('mod_amcat', 'page_responses', 'id');
        $attempt->annotate_files('mod_amcat', 'essay_responses', 'id');
        $attempt->annotate_files('mod_amcat', 'essay_answers', 'id');

        // Prepare and return the structure we have just created for the amcat module.
        return $this->prepare_activity_structure($amcat);
    }
}
