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
 * @package   local_cohort_profile
 * @copyright 2019, YuriyYurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

const COHORTLIMIT = 10;

function local_cohort_profile_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/cohort/lib.php');

    $showallcohorts = optional_param('showallcohorts', 0, PARAM_INT);

    if (is_siteadmin()) {
        $sql = 'SELECT c.*
              FROM {cohort} c
              JOIN {cohort_members} cm ON c.id = cm.cohortid
             WHERE cm.userid = ?';
        $cohorts = $DB->get_records_sql($sql, array($user->id));
    } else {
        $cohorts = cohort_get_user_cohorts($user->id);
    }

    if ($cohorts) {
        $cohortdetailscategory = new core_user\output\myprofile\category('cohortdetails', get_string('cohorts', 'core_cohort'));
        $tree->add_category($cohortdetailscategory);

        $shown = 0;
        $cohortslisting = '<dt>';
        foreach ($cohorts as $cohort) {
            $attr = null;
            if ($cohort->visible == 0) {
                $attr['style'] = 'color: #999;';
                $attr['title'] = get_string('hidden', 'local_cohort_profile');
            }
            $cohortslisting .= html_writer::tag('dd', $cohort->name, $attr);

            $shown++;
            if (!$showallcohorts && $shown == COHORTLIMIT) {
                $url = new moodle_url('/user/profile.php', array('id' => $user->id, 'showallcohorts' => 1));

                $cohortslisting .= html_writer::tag('dd', html_writer::link($url, get_string('viewmore'),
                    array('title' => get_string('viewmore'))), array('class' => 'viewmore'));
                break;
            }
        }
        $cohortslisting .= '</dt>';

        $node = new core_user\output\myprofile\node('cohortdetails', 'cohortprofile', $cohortslisting);
        $tree->add_node($node);
    }
}
