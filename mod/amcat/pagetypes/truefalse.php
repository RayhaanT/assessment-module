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
 * True/false
 *
 * @package mod_amcat
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/** True/False question type */
define("amcat_PAGE_TRUEFALSE",     "2");

class amcat_page_type_truefalse extends amcat_page {

    protected $type = amcat_page::TYPE_QUESTION;
    protected $typeidstring = 'truefalse';
    protected $typeid = amcat_PAGE_TRUEFALSE;
    protected $string = null;

    public function get_typeid() {
        return $this->typeid;
    }
    public function get_typestring() {
        if ($this->string===null) {
            $this->string = get_string($this->typeidstring, 'amcat');
        }
        return $this->string;
    }
    public function get_idstring() {
        return $this->typeidstring;
    }
    public function display($renderer, $attempt) {
        global $USER, $CFG, $PAGE;
        $answers = $this->get_answers();
        foreach ($answers as $key => $answer) {
            $answers[$key] = parent::rewrite_answers_urls($answer);
        }
        shuffle($answers);

        $params = array('answers'=>$answers, 'amcatid'=>$this->amcat->id, 'contents'=>$this->get_contents(), 'attempt'=>$attempt);
        $mform = new amcat_display_answer_form_truefalse($CFG->wwwroot.'/mod/amcat/continue.php', $params);
        $data = new stdClass;
        $data->id = $PAGE->cm->id;
        $data->pageid = $this->properties->id;
        $mform->set_data($data);

        // Trigger an event question viewed.
        $eventparams = array(
            'context' => context_module::instance($PAGE->cm->id),
            'objectid' => $this->properties->id,
            'other' => array(
                    'pagetype' => $this->get_typestring()
                )
            );

        $event = \mod_amcat\event\question_viewed::create($eventparams);
        $event->trigger();
        return $mform->display();
    }
    public function check_answer() {
        global $DB, $CFG;
        $formattextdefoptions = new stdClass();
        $formattextdefoptions->noclean = true;
        $formattextdefoptions->para = false;

        $answers = $this->get_answers();
        shuffle($answers);
        $params = array('answers'=>$answers, 'amcatid'=>$this->amcat->id, 'contents'=>$this->get_contents());
        $mform = new amcat_display_answer_form_truefalse($CFG->wwwroot.'/mod/amcat/continue.php', $params);
        $data = $mform->get_data();
        require_sesskey();

        $result = parent::check_answer();

        if (empty($data->answerid)) {
            $result->noanswer = true;
            return $result;
        }
        $result->answerid = $data->answerid;
        $answer = $DB->get_record("amcat_answers", array("id" => $result->answerid), '*', MUST_EXIST);
        $answer = parent::rewrite_answers_urls($answer);
        if ($this->amcat->jumpto_is_correct($this->properties->id, $answer->jumpto)) {
            $result->correctanswer = true;
        }
        if ($this->amcat->custom) {
            if ($answer->score > 0) {
                $result->correctanswer = true;
            } else {
                $result->correctanswer = false;
            }
        }
        $result->newpageid = $answer->jumpto;
        $result->response  = format_text($answer->response, $answer->responseformat, $formattextdefoptions);
        $result->studentanswer = $result->userresponse = $answer->answer;
        return $result;
    }

    public function display_answers(html_table $table) {
        $answers = $this->get_answers();
        $options = new stdClass();
        $options->noclean = true;
        $options->para = false;
        $i = 1;
        foreach ($answers as $answer) {
            $answer = parent::rewrite_answers_urls($answer);
            $cells = array();
            if ($this->amcat->custom && $answer->score > 0) {
                // if the score is > 0, then it is correct
                $cells[] = '<label class="correct">' . get_string('answer', 'amcat') . " {$i}</label>: \n";
            } else if ($this->amcat->custom) {
                $cells[] = '<label>' . get_string('answer', 'amcat') . " {$i}</label>: \n";
            } else if ($this->amcat->jumpto_is_correct($this->properties->id, $answer->jumpto)) {
                // underline correct answers
                $cells[] = '<span class="correct">' . get_string('answer', 'amcat') . " {$i}</span>: \n";
            } else {
                $cells[] = '<label class="correct">' . get_string('answer', 'amcat') . " {$i}</label>: \n";
            }
            $cells[] = format_text($answer->answer, $answer->answerformat, $options);
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = '<label>' . get_string('response', 'amcat') . ' ' . $i . '</label>:';
            $cells[] = format_text($answer->response, $answer->responseformat, $options);
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = '<label>' . get_string('score', 'amcat') . '</label>:';
            $cells[] = $answer->score;
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = '<label>' . get_string('jump', 'amcat') . '</label>:';
            $cells[] = $this->get_jump_name($answer->jumpto);
            $table->data[] = new html_table_row($cells);

            if ($i === 1){
                $table->data[count($table->data)-1]->cells[0]->style = 'width:20%;';
            }

            $i++;
        }
        return $table;
    }

    /**
     * Updates the page and its answers
     *
     * @global moodle_database $DB
     * @global moodle_page $PAGE
     * @param stdClass $properties
     * @return bool
     */
    public function update($properties, $context = null, $maxbytes = null) {
        global $DB, $PAGE;
        $answers  = $this->get_answers();
        $properties->id = $this->properties->id;
        $properties->amcatid = $this->amcat->id;
        $properties->timemodified = time();
        $properties = file_postupdate_standard_editor($properties, 'contents', array('noclean'=>true, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$PAGE->course->maxbytes), context_module::instance($PAGE->cm->id), 'mod_amcat', 'page_contents', $properties->id);
        $DB->update_record("amcat_pages", $properties);

        // Trigger an event: page updated.
        \mod_amcat\event\page_updated::create_from_amcat_page($this, $context)->trigger();

        // need to reset offset for correct and wrong responses
        $this->amcat->maxanswers = 2;
        for ($i = 0; $i < $this->amcat->maxanswers; $i++) {
            if (!array_key_exists($i, $this->answers)) {
                $this->answers[$i] = new stdClass;
                $this->answers[$i]->amcatid = $this->amcat->id;
                $this->answers[$i]->pageid = $this->id;
                $this->answers[$i]->timecreated = $this->timecreated;
            }

            if (!empty($properties->answer_editor[$i]) && is_array($properties->answer_editor[$i])) {
                $this->answers[$i]->answer = $properties->answer_editor[$i]['text'];
                $this->answers[$i]->answerformat = $properties->answer_editor[$i]['format'];
            }

            if (!empty($properties->response_editor[$i]) && is_array($properties->response_editor[$i])) {
                $this->answers[$i]->response = $properties->response_editor[$i]['text'];
                $this->answers[$i]->responseformat = $properties->response_editor[$i]['format'];
            }

            // we don't need to check for isset here because properties called it's own isset method.
            if ($this->answers[$i]->answer != '') {
                if (isset($properties->jumpto[$i])) {
                    $this->answers[$i]->jumpto = $properties->jumpto[$i];
                }
                if ($this->amcat->custom && isset($properties->score[$i])) {
                    $this->answers[$i]->score = $properties->score[$i];
                }
                if (!isset($this->answers[$i]->id)) {
                    $this->answers[$i]->id =  $DB->insert_record("amcat_answers", $this->answers[$i]);
                } else {
                    $DB->update_record("amcat_answers", $this->answers[$i]->properties());
                }
                // Save files in answers and responses.
                $this->save_answers_files($context, $maxbytes, $this->answers[$i],
                        $properties->answer_editor[$i], $properties->response_editor[$i]);
            } else if (isset($this->answers[$i]->id)) {
                $DB->delete_records('amcat_answers', array('id'=>$this->answers[$i]->id));
                unset($this->answers[$i]);
            }
        }
        return true;
    }

    public function stats(array &$pagestats, $tries) {
        if(count($tries) > $this->amcat->maxattempts) { // if there are more tries than the max that is allowed, grab the last "legal" attempt
            $temp = $tries[$this->amcat->maxattempts - 1];
        } else {
            // else, user attempted the question less than the max, so grab the last one
            $temp = end($tries);
        }
        if ($this->properties->qoption) {
            $userresponse = explode(",", $temp->useranswer);
            foreach ($userresponse as $response) {
                if (isset($pagestats[$temp->pageid][$response])) {
                    $pagestats[$temp->pageid][$response]++;
                } else {
                    $pagestats[$temp->pageid][$response] = 1;
                }
            }
        } else {
            if (isset($pagestats[$temp->pageid][$temp->answerid])) {
                $pagestats[$temp->pageid][$temp->answerid]++;
            } else {
                $pagestats[$temp->pageid][$temp->answerid] = 1;
            }
        }
        if (isset($pagestats[$temp->pageid]["total"])) {
            $pagestats[$temp->pageid]["total"]++;
        } else {
            $pagestats[$temp->pageid]["total"] = 1;
        }
        return true;
    }

    public function report_answers($answerpage, $answerdata, $useranswer, $pagestats, &$i, &$n) {
        $answers = $this->get_answers();
        $formattextdefoptions = new stdClass(); //I'll use it widely in this page
        $formattextdefoptions->para = false;
        $formattextdefoptions->noclean = true;
        $formattextdefoptions->context = $answerpage->context;

        foreach ($answers as $answer) {
            $answer = parent::rewrite_answers_urls($answer);
            if ($this->properties->qoption) {
                if ($useranswer == null) {
                    $userresponse = array();
                } else {
                    $userresponse = explode(",", $useranswer->useranswer);
                }
                if (in_array($answer->id, $userresponse)) {
                    // make checked
                    $data = "<input  readonly=\"readonly\" disabled=\"disabled\" name=\"answer[$i]\" checked=\"checked\" type=\"checkbox\" value=\"1\" />";
                    if (!isset($answerdata->response)) {
                        if ($answer->response == null) {
                            if ($useranswer->correct) {
                                $answerdata->response = get_string("thatsthecorrectanswer", "amcat");
                            } else {
                                $answerdata->response = get_string("thatsthewronganswer", "amcat");
                            }
                        } else {
                            $answerdata->response = format_text($answer->response, $answer->responseformat, $formattextdefoptions);
                        }
                    }
                    if (!isset($answerdata->score)) {
                        if ($this->amcat->custom) {
                            $answerdata->score = get_string("pointsearned", "amcat").": ".$answer->score;
                        } elseif ($useranswer->correct) {
                            $answerdata->score = get_string("receivedcredit", "amcat");
                        } else {
                            $answerdata->score = get_string("didnotreceivecredit", "amcat");
                        }
                    }
                } else {
                    // unchecked
                    $data = "<input type=\"checkbox\" readonly=\"readonly\" name=\"answer[$i]\" value=\"0\" disabled=\"disabled\" />";
                }
                if (($answer->score > 0 && $this->amcat->custom) || ($this->amcat->jumpto_is_correct($this->properties->id, $answer->jumpto) && !$this->amcat->custom)) {
                    $data .= "<div class=highlight>".format_text($answer->answer, $answer->answerformat, $formattextdefoptions)."</div>";
                } else {
                    $data .= format_text($answer->answer, $answer->answerformat, $formattextdefoptions);
                }
            } else {
                if ($useranswer != null and $answer->id == $useranswer->answerid) {
                    // make checked
                    $data = "<input  readonly=\"readonly\" disabled=\"disabled\" name=\"answer[$i]\" checked=\"checked\" type=\"checkbox\" value=\"1\" />";
                    if ($answer->response == null) {
                        if ($useranswer->correct) {
                            $answerdata->response = get_string("thatsthecorrectanswer", "amcat");
                        } else {
                            $answerdata->response = get_string("thatsthewronganswer", "amcat");
                        }
                    } else {
                        $answerdata->response = format_text($answer->response, $answer->responseformat, $formattextdefoptions);
                    }
                    if ($this->amcat->custom) {
                        $answerdata->score = get_string("pointsearned", "amcat").": ".$answer->score;
                    } elseif ($useranswer->correct) {
                        $answerdata->score = get_string("receivedcredit", "amcat");
                    } else {
                        $answerdata->score = get_string("didnotreceivecredit", "amcat");
                    }
                } else {
                    // unchecked
                    $data = "<input type=\"checkbox\" readonly=\"readonly\" name=\"answer[$i]\" value=\"0\" disabled=\"disabled\" />";
                }
                if (($answer->score > 0 && $this->amcat->custom) || ($this->amcat->jumpto_is_correct($this->properties->id, $answer->jumpto) && !$this->amcat->custom)) {
                    $data .= "<div class=\"highlight\">".format_text($answer->answer, $answer->answerformat, $formattextdefoptions)."</div>";
                } else {
                    $data .= format_text($answer->answer, $answer->answerformat, $formattextdefoptions);
                }
            }
            if (isset($pagestats[$this->properties->id][$answer->id])) {
                $percent = $pagestats[$this->properties->id][$answer->id] / $pagestats[$this->properties->id]["total"] * 100;
                $percent = round($percent, 2);
                $percent .= "% ".get_string("checkedthisone", "amcat");
            } else {
                $percent = get_string("noonecheckedthis", "amcat");
            }

            $answerdata->answers[] = array($data, $percent);
            $answerpage->answerdata = $answerdata;
        }
        return $answerpage;
    }
}

class amcat_add_page_form_truefalse extends amcat_add_page_form_base {

    public $qtype = 'truefalse';
    public $qtypestring = 'truefalse';
    protected $answerformat = amcat_ANSWER_HTML;
    protected $responseformat = amcat_ANSWER_HTML;

    public function custom_definition() {
        $this->_form->addElement('header', 'answertitle0', get_string('correctresponse', 'amcat'));
        $this->add_answer(0, null, true, $this->get_answer_format());
        $this->add_response(0);
        $this->add_jumpto(0, get_string('correctanswerjump', 'amcat'), amcat_NEXTPAGE);
        $this->add_score(0, get_string('correctanswerscore', 'amcat'), 1);

        $this->_form->addElement('header', 'answertitle1', get_string('wrongresponse', 'amcat'));
        $this->add_answer(1, null, true, $this->get_answer_format());
        $this->add_response(1);
        $this->add_jumpto(1, get_string('wronganswerjump', 'amcat'), amcat_THISPAGE);
        $this->add_score(1, get_string('wronganswerscore', 'amcat'), 0);
    }
}

class amcat_display_answer_form_truefalse extends moodleform {

    public function definition() {
        global $USER, $OUTPUT;
        $mform = $this->_form;
        $answers = $this->_customdata['answers'];
        $amcatid = $this->_customdata['amcatid'];
        $contents = $this->_customdata['contents'];
        if (array_key_exists('attempt', $this->_customdata)) {
            $attempt = $this->_customdata['attempt'];
        } else {
            $attempt = new stdClass();
            $attempt->answerid = null;
        }

        // Disable shortforms.
        $mform->setDisableShortforms();

        $mform->addElement('header', 'pageheader');

        $mform->addElement('html', $OUTPUT->container($contents, 'contents'));

        $hasattempt = false;
        $disabled = '';
        if (isset($USER->modattempts[$amcatid]) && !empty($USER->modattempts[$amcatid])) {
            $hasattempt = true;
            $disabled = array('disabled' => 'disabled');
        }

        $options = new stdClass();
        $options->para = false;
        $options->noclean = true;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $i = 0;
        $radiobuttons = array();
        foreach ($answers as $answer) {
            $ansid = 'answerid';
            if ($hasattempt) {
                $ansid = 'answer_id';
            }

            $answer = amcat_page_type_truefalse::rewrite_answers_urls($answer);
            $radiobuttons[] = $mform->createElement('radio', $ansid, null,
                format_text($answer->answer, $answer->answerformat, $options), $answer->id, $disabled);

            $mform->setType($ansid, PARAM_INT);
            if ($hasattempt && $answer->id == $USER->modattempts[$amcatid]->answerid) {
                $mform->setDefault($ansid, $attempt->answerid);
                $mform->addElement('hidden', 'answerid', $answer->id);
                $mform->setType('answerid', PARAM_INT);
            }
            $i++;
        }

        $radiogroup = $mform->addGroup($radiobuttons, $ansid, '', array(''), false);
        $radiogroup->setAttributes(array('class' => 'answeroptiongroup'));

        if ($hasattempt) {
            $this->add_action_buttons(null, get_string("nextpage", "amcat"));
        } else {
            $this->add_action_buttons(null, get_string("submit", "amcat"));
        }

    }

}
