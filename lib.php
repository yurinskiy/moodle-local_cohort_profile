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
 * This file contains the code for the plugin integration.
 *
 * @package   local_cohort_profile
 * @copyright 2019, Yuriy Yurinskiy <yuriyyurinskiy@yandex.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * LOCAL_COHORT_PROFILE_COHORT_LIMIT - number of cohorts to be derived
 */
define('LOCAL_COHORT_PROFILE_COHORT_LIMIT', 10);

/**
 * To add the category and node information into the my profile page.
 *
 * @param core_user\output\myprofile\tree $tree The myprofile tree to add categories and nodes to.
 * @param stdClass                        $user The user object that the profile page belongs to.
 * @param bool                            $iscurrentuser If the $user object is the current user.
 * @param stdClass                        $course The course to determine if we are in a course context or system context.
 * @return void
 */
function local_cohort_profile_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/cohort/lib.php');

    $showallcohorts = optional_param('showallcohorts', 0, PARAM_INT);

    $sql = 'SELECT %s FROM {cohort} c
              JOIN {cohort_members} cm ON c.id = cm.cohortid
             WHERE cm.userid = ?';

    if (!is_siteadmin()) {
        $sql .= ' AND c.visible = 1';
    }

    if ($showallcohorts) {
        $cohorts = $DB->get_records_sql(sprintf($sql, 'c.*'), array($user->id));
        $countcohorts = count($cohorts);
    } else {
        $cohorts = $DB->get_records_sql(sprintf($sql, 'c.*'), array($user->id), 0, LOCAL_COHORT_PROFILE_COHORT_LIMIT);
        $countcohorts = $DB->count_records_sql(sprintf($sql, 'count(c.id)'), array($user->id));
    }

    if ($cohorts) {
        $cohortdetailscategory = new core_user\output\myprofile\category('cohortdetails', get_string('cohorts', 'core_cohort'));
        $tree->add_category($cohortdetailscategory);

        $cohortslisting = '';

        foreach ($cohorts as $cohort) {
            $attr = null;
            if (0 == $cohort->visible) {
                $attr['class'] = 'dimmed_text';
                $attr['title'] = get_string('hidden', 'local_cohort_profile');
            }
            $cohortslisting .= html_writer::tag('dd', $cohort->name, $attr);
        }

        if (!$showallcohorts && $countcohorts > count($cohorts)) {
            $url = new moodle_url('/user/profile.php', array('id' => $user->id, 'showallcohorts' => 1));

            $cohortslisting .= html_writer::tag('dd', html_writer::link($url, get_string('viewmore'),
                    array('title' => get_string('viewmore'))), array('class' => 'viewmore'));
        }

        $node = new core_user\output\myprofile\node('cohortdetails', 'cohortprofile', $cohortslisting);
        $tree->add_node($node);
    }
}
