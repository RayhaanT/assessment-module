<?php
namespace core_question\bank;

defined('MOODLE_INTERNAL') || die();

class suspend_action_column extends menu_action_column_base
{
    public function init()
    {
        parent::init();
    }

    public function get_name()
    {
        return 'suspendaction';
    }

    /**
     * Work out the info required to display this action, if appropriate.
     *
     * If the action is not appropriate to this question, return [null, null, null].
     *
     * Otherwise return an array with three elements:
     * moodel_url $url the URL to perform the action.
     * string $icon the icon name. E.g. 't/delete'.
     * string $label the label to display.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @return array [$url, $label, $icon] as above.
     */
    protected function get_url_icon_and_label(\stdClass $question): array
    {
        if (!question_has_capability_on($question, 'edit')) {
            return [null, null, null];
        }
        $url = new \moodle_url('/question/suspend.php', array('sesskey' => sesskey(), 'id' => $question->id, 'courseid' => $this->qbank->get_courseid()));
        return [$url, 'i/duration', 'Suspend'];
    }

    public function get_required_fields()
    {
        $required = parent::get_required_fields();
        return $required;
    }
}
