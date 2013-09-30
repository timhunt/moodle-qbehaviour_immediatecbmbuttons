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
 * Question behaviour with CBM and immediate feedback, where the action of
 * choosing the certainty is the same as submitting.
 *
 * @package   qbehaviour_immediatecbmbuttons
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../immediatefeedback/behaviour.php');


/**
 * Question behaviour for CBM with immediate submit.
 *
 * This is similar to the Immediate feedback with CBM behaviour, except the
 * the student chooses their certainty by deciding which button to click to
 * submit their response. Thus, the act of selecting a certainty and committing
 * to a response are inextricably linked.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_immediatecbmbuttons extends qbehaviour_immediatecbm {
    public function get_expected_data() {
        if (!$this->qa->get_state()->is_active()) {
            return parent::get_expected_data();
        }

        $expected = array('giveup' => PARAM_BOOL);
        foreach (question_cbm::$certainties as $certainty) {
            $expected['certainty' . $certainty] = PARAM_BOOL;
        }
        return $expected;
    }

    public function get_correct_response() {
        if ($this->qa->get_state()->is_active()) {
            return array('certainty' . question_cbm::HIGH => 1);
        }
        return array();
    }

    protected function get_our_resume_data() {
        return array();
    }

    protected function is_same_response(question_attempt_step $pendingstep) {
        return $this->question->is_same_response(
                $this->qa->get_last_step()->get_qt_data(), $pendingstep->get_qt_data());
    }

    protected function is_complete_response(question_attempt_step $pendingstep) {
        return $this->question->is_complete_response($pendingstep->get_qt_data());
    }

    // TODO from this point onwards.

    public function process_submit(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        if (!$this->qa->get_question()->is_gradable_response($pendingstep->get_qt_data()) ||
                !$pendingstep->has_behaviour_var('certainty')) {
            $pendingstep->set_state(question_state::$invalid);
            return question_attempt::KEEP;
        }

        return $this->do_grading($pendingstep, $pendingstep);
    }

    public function process_finish(question_attempt_pending_step $pendingstep) {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        $laststep = $this->qa->get_last_step();
        return $this->do_grading($laststep, $pendingstep);
    }

    protected function do_grading(question_attempt_step $responsesstep,
            question_attempt_pending_step $pendingstep) {
        if (!$this->question->is_gradable_response($responsesstep->get_qt_data())) {
            $pendingstep->set_state(question_state::$gaveup);

        } else {
            $response = $responsesstep->get_qt_data();
            list($fraction, $state) = $this->question->grade_response($response);

            if ($responsesstep->has_behaviour_var('certainty')) {
                $certainty = $responsesstep->get_behaviour_var('certainty');
            } else {
                $certainty = question_cbm::default_certainty();
                $pendingstep->set_behaviour_var('_assumedcertainty', $certainty);
            }

            $pendingstep->set_behaviour_var('_rawfraction', $fraction);
            $pendingstep->set_fraction(question_cbm::adjust_fraction($fraction, $certainty));
            $pendingstep->set_state($state);
            $pendingstep->set_new_response_summary(question_cbm::summary_with_certainty(
                    $this->question->summarise_response($response),
                    $responsesstep->get_behaviour_var('certainty')));
        }
        return question_attempt::KEEP;
    }

    public function summarise_action(question_attempt_step $step) {
        $summary = parent::summarise_action($step);
        if ($step->has_behaviour_var('certainty')) {
            $summary = question_cbm::summary_with_certainty($summary,
                    $step->get_behaviour_var('certainty'));
        }
        return $summary;
    }
}
