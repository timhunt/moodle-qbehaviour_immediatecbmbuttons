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
 * Defines the renderer for the CBM with immediate submit behaviour.
 *
 * @package   qbehaviour_immediatecbmbuttons
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../deferredcbm/renderer.php');


/**
 * Renderer for outputting parts of a question belonging to the CBM with
 * immediate submit behaviour.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_immediatecbmbuttons_renderer extends qbehaviour_immediatecbm_renderer {
    public function controls(question_attempt $qa, question_display_options $options) {
        $attributes = array(
            'type' => 'submit',
        );
        if ($options->readonly) {
            $attributes['disabled'] = 'disabled';
        }

        $attributes['name'] = $qa->get_behaviour_field_name('giveup');
        $attributes['value'] = get_string('dontknow', 'qbehaviour_deferredcbm');
        $choices = ' ' . html_writer::empty_tag('input', $attributes);
        foreach (question_cbm::$certainties as $certainty) {
            $attributes['name'] = $qa->get_behaviour_field_name('certainty' . $certainty);
            $attributes['value'] = question_cbm::get_string($certainty);
            $choices .= ' ' . html_writer::empty_tag('input', $attributes);
        }

        $a = new stdClass();
        $a->help = $this->output->help_icon('certainty', 'qbehaviour_deferredcbm');
        $a->choices = $choices;
        return html_writer::tag('div', get_string('howcertainareyou', 'qbehaviour_deferredcbm', $a),
                array('class' => 'certaintychoices'));
    }
}
