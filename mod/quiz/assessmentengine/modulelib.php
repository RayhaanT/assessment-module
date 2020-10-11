<?php

function addSelectCondition($currentCondition, $column, $value) {
    if (!$value) {
        return $currentCondition;
    }
    if ($currentCondition != '') {
        $currentCondition .= ' AND ';
    }
    $currentCondition .= $column . " = '" . $value . "'";
    return $currentCondition;
}

function filterDuplicates($questions, $existing) {
    foreach ($existing as $e) {
        if (($key = array_search($e, $questions)) !== false) {
            unset($questions[$key]);
        }
    }
    return $questions;
}

function getAverageDifficulty($diffstring) {
    global $DB;
    $alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
    if (strpos($diffstring, ':') !== false) {
        $diffpairs = explode(',', $diffstring);
        $diffs = array();
        foreach ($diffpairs as $d) {
            array_push($diffs, explode(':', $d)[1]);
        }
        $diffnumbers = array();
        foreach ($diffs as $d) {
            foreach ($alldiffs as $a) {
                if ($d == $a->name) {
                    array_push($diffnumbers, $a->listindex);
                    break;
                }
            }
        }
        $total = 0;
        foreach ($diffnumbers as $n) {
            $total += $n;
        }
        $averageindex = ceil($total / count($diffnumbers));
        foreach ($alldiffs as $d) {
            if ($d->listindex == $averageindex) {
                return $d->name;
            }
        }
    } else {
        return $diffstring;
    }
    return null;
}

function generateBlankQuestion() {
    global $USER;

    $q = new stdClass();
    $q->questiontext = 0;
    $q->generalfeedback = '';
    $q->stamp = make_unique_id_code();
    $q->createdby = $USER->id;
    $q->timecreated = time();
    $q->modifiedby = $USER->id;
    $q->timemodified = time();
    $q->penalty = 0;
    return $q;
}

function objectArrayUnique($array) {
    $duplicate_keys = array();
    $tmp = array();

    foreach ($array as $key => $val) {
        // Convert objects to arrays, in_array() does not support objects
        if (is_object($val))
            $val = (array)$val;

        if (!in_array($val, $tmp)) {
            $tmp[] = $val;
        }
        else {
            $duplicate_keys[] = $key;
        }
    }

    foreach ($duplicate_keys as $key)
        unset($array[$key]);

    return $array;
}

function validateTemplatesWithQuiz($quiz, $pendingTemplates = null, $filterSuspended = false) {
    $questions = getQuestionsInQuiz($quiz);
    return validateTemplates($questions, $pendingTemplates, $filterSuspended);
}

function validateTemplates($questions, $pendingTemplates = null, $filterSuspended = false) {
    global $DB;

    $templatesNo = 0;
    $templates = array();
    $regular = array();
    foreach ($questions as $q) {
        if ($q->qtype == 'modtemplate') {
            $templatesNo++;
            // Extract only the data that is relevant to the template
            $temp = new stdClass();
            $temp->topic = $q->topic;
            $temp->subject = $q->subject;
            if (strpos(':', $q->difficulty) === false) {
                $temp->difficulty = $q->difficulty;
            } else {
                $diffpair = explode(':', $q->difficulty);
                $temp->role = $diffpair[0];
                $temp->difficulty = $diffpair[1];
            }
            array_push($templates, $temp);
        } else {
            array_push($regular, $q);
        }
    }
    foreach ($pendingTemplates as $p) {
        $templatesNo++;
        $temp = new stdClass();
        $temp->topic = $p->topic;
        $temp->subject = $p->subject;
        if (strpos(':', $p->difficulty) === false) {
            $temp->difficulty = $p->difficulty;
        } else {
            $diffpair = explode(':', $p->difficulty);
            $temp->role = $diffpair[0];
            $temp->difficulty = $diffpair[1];
        }
        array_push($templates, $temp);
    }

    $templateDuplicates = array();
    $temp = array();
    foreach($templates as $t) {
        if (!in_array($t, $temp)) {
            $temp[] = $t;
            $tcopy = fullclone($t);
            $tcopy->duplicates = 0;
            $templateDuplicates[] = $tcopy;
        } else {
            for($x = 0; $x < sizeof($temp); $x++) {
                if($temp[$x] == $t) {
                    $templateDuplicates[$x]->duplicates++;
                }
            }
        }
    }

    $templates = objectArrayUnique($templates);

    $allquestions = array();
    foreach ($templates as $t) {
        $duplicates = 0;
        foreach($templateDuplicates as $dup) {
            $tmp = fullclone($dup);
            unset($tmp->duplicates);
            if($tmp == $t) {
                $duplicates = $dup->duplicates;
            }
        }

        $condition = "qtype != 'modtemplate'";
        if ($t->topic) {
            $condition = addSelectCondition($condition, 'topic', $t->topic);
        }
        if($t->subject) {
            $condition = addSelectCondition($condition, 'subject', $t->subject);
        }
        if ($condition != '') {
            $rolecondition = $condition . " AND ";
        }
        if (isset($t->role)) {
            $rolecondition .= "difficulty REGEXP '" . $t->role . ':' . $t->difficulty . "'";
        } else {
            $rolecondition .= "difficulty REGEXP '" . $t->difficulty . "'";
        }

        if (strpos($t->difficulty, ':') !== false) {
            $diffname = explode(':', $t->difficulty)[1];
        } else {
            $diffname = $t->difficulty;
        }
        $rawcondition = addSelectCondition($condition, 'difficulty', $diffname);
        $qpool = $DB->get_records_select('question', $rolecondition);
        $qpool = array_merge($qpool, $DB->get_records_select('question', $rawcondition));
        if(sizeof($qpool) < $duplicates + 1) {
            return $duplicates + 1 - sizeof($qpool);
        }

        $allquestions = array_merge($allquestions, $qpool);
        $allquestions = objectArrayUnique($allquestions);
    }
    $allquestions = filterDuplicates($allquestions, $regular);
    if($filterSuspended) {
        $allquestions = filterAndEvaluateRetirement($allquestions);
    }

    if (sizeof($allquestions) < $templatesNo) {
        return $templatesNo - sizeof($allquestions);
    }
    return true;
}

function filterAndEvaluateRetirement($questions) {
    global $DB;
    $filteredQuestions = array();
    $ranges = $DB->get_records('question_retirement_ranges', null, 'upperbound');

    foreach ($questions as $q) {
        $flags = '00000';
        // If permanently removed for too many correct answers
        if ($q->retirementflags > 15) {
            continue;
        }

        // Question manually suspended or in suspension cycle
        if ($q->suspensionend > time()) {
            $flags[4] = '1';
        }
        if ($q->disableperiod && $q->enableperiod && $flags[4] != '1') {
            $timenow = time();
            while ($timenow - $q->lastcycle > ($q->cyclesuspended ? $q->disableperiod : $q->enableperiod)) {
                $q->lastcycle += $q->cyclesuspended ? $q->disableperiod : $q->enableperiod;
                $q->cyclesuspended = $q->cyclesuspended ? 0 : 1;
            }
            if ($q->cyclesuspended) {
                $flags[4] = '1';
            }
        }

        // Question retired based on version
        if ($version = $DB->get_record('question_versions', array('topic' => $q->topic))) {
            if ($q->techversion > 0 && $q->techversion < $version->version) {
                $flags[3] = '1';
            }
        }

        // Question expired
        if ($q->lifecycleexpiry > 0 && $q->lifecycleexpiry < time()) {
            $flags[2] = '1';
        }

        // Question retired/suspended because of too many correct answers
        if ($q->attempts > 40) {
            $thisrange = 1;
            foreach ($ranges as $r) {
                if ($r->upperbound >= $q->attempts) {
                    break;
                }
                $thisrange++;
            }

            if ($q->overalldifficulty > 0) {
                $thisdiff = $DB->get_record('question_difficulties', array('listindex' => $q->overalldifficulty));
            } else {
                $diffname = getAverageDifficulty($q->difficulty);
                $thisdiff = $DB->get_record('question_difficulties', array('name' => $diffname));
            }

            $rangename = 'range' . $thisrange;
            if ($q->attempts != 0) {
                if ($thisdiff->$rangename / 100 < $q->attemptaccuracy / $q->attempts) {
                    $flags[1] = '1';

                    // If its already been suspended
                    if ($q->retirementflags > 7) {
                        $flags[0] = '1';
                    } else {
                        // Suspend for a week
                        $q->suspensionend = time() + 604800;
                    }
                }
            }
        }

        $flagsdec = bindec($flags);
        // Update permanent flags in db
        if ($flagsdec != $q->retirementflags) {
            $baseflags = 0;
            if ($q->retirementflags > 15 || $flagsdec > 15) {
                $baseflags = 16;
            } else if ($q->retirementflags > 7 || $flagsdec > 7) {
                $baseflags = 8;
            }
            $variableflags = bindec(substr($flags, 2));
            $q->retirementflags = $baseflags + $variableflags;
        }
        $DB->update_record('question', $q);
        // If the current flags are all down, let it through
        if ($flagsdec == 0) {
            array_push($filteredQuestions, $q);
        }
    }

    return $filteredQuestions;
}

function getQuestionsInQuiz($quiz) {
    global $DB;

    $questionslotsinquiz = $DB->get_records('quiz_slots', array('quizid' => $quiz->id));
    $selectquestions = '';
    foreach ($questionslotsinquiz as $q) {
        if ($selectquestions != '') {
            $selectquestions .= ' OR ';
        }
        $selectquestions .= "id = '" . $q->questionid . "'";
    }
    if ($selectquestions != '') {
        return $DB->get_records_select('question', $selectquestions);
    }
    return array();
}

// Returns array of 2 conditions
function getTemplateSQLConditions($subject, $topic, $difficulty) {
    $condition = "qtype != 'modtemplate'";
    if ($topic) {
        $condition = addSelectCondition($condition, 'topic', $topic);
    }
    if($subject) {
        $condition = addSelectCondition($condition, 'subject', $subject);
    }
    if ($condition != '') {
        $roleCondition = $condition . " AND ";
    }
    $roleCondition .= "difficulty REGEXP '" . $difficulty . "'";

    if (strpos($difficulty, ':') !== false) {
        $diffname = explode(':', $difficulty)[1];
    } else {
        $diffname = $difficulty;
    }
    $rawCondition = addSelectCondition($condition, 'difficulty', $diffname);

    return [$roleCondition, $rawCondition];
}

// Accepts question_definition objects as parameters
function fillTemplate($templateObj, $existing) {
    global $DB;
    $templateObjs = array();
    $usedObjs = array();

    foreach($existing as $e) {
        if($e->qtype == 'modtemplate') {
            $templateObjs[] = $e;
        }
        else {
            $usedObjs[] = $e;
        }
    }

    $condition = "id = $templateObj->id";
    foreach($templateObjs as $t) {
        $condition .= " OR id = $t->id";
    }
    $templates = $DB->get_records_select('question', $condition);
    $condition = '';
    foreach($usedObjs as $u) {
        if($condition != '') {
            $condition .= " OR ";
        }
        $condition .= "id = $u->id";
    }
    $used = $DB->get_records_select('question', $condition);
    $temp = $DB->get_record('question', array('id' => $templateObj->id));

    $pullPool = array();
    $conditions = getTemplateSQLConditions($temp->subject, $temp->topic, $temp->difficulty);
    $pullPool = $DB->get_records_select('question', $conditions[0]);
    $pullPool = array_merge($pullPool, $DB->get_records_select('question', $conditions[1]));
    $pullPool = filterDuplicates($pullPool, $used);
    $pullPool = filterAndEvaluateRetirement($pullPool);
    $noOverlap = fullclone($pullPool);

    foreach($templates as $t) {
        $conditions = getTemplateSQLConditions($temp->subject, $temp->topic, $temp->difficulty);
        $newPool = $DB->get_records_select('question', $conditions[0]);
        $newPool = array_merge($newPool, $DB->get_records_select('question', $conditions[1]));
        $newPool = filterAndEvaluateRetirement($newPool);
        $noOverlap = filterDuplicates($noOverlap, $newPool);

        /* For when we figure out an algorithm to optimize picking order
        // // Only process it if there is overlap
        // if(array_intersect(array_keys($pullPool), array_keys($newPool))) {

        // }
        */
    }

    if(sizeof($noOverlap) == 0 && sizeof($pullPool) == 0) {
        return false;
    }

    if(sizeof($noOverlap) > 0) {
        return $noOverlap[array_rand($noOverlap)];
    }
    return $pullPool[array_rand($pullPool)];
}