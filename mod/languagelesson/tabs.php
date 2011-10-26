<?php  // $Id: tabs.php 671 2011-08-11 21:45:41Z griffisd $
/**
* Sets up the tabs used by the lesson pages for teachers.
*
* This file was adapted from the mod/quiz/tabs.php
*
* @version $Id: tabs.php 671 2011-08-11 21:45:41Z griffisd $
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package lesson
*/

/// This file to be included so we can assume config.php has already been included.

    if (empty($lesson)) {
        error('You cannot call this script in that way');
    }
    if (!isset($currenttab)) {
        $currenttab = '';
    }
    if (!isset($cm)) {
        $cm = get_coursemodule_from_instance('lesson', $lesson->id);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    }
    if (!isset($course)) {
        $course = get_record('course', 'id', $lesson->course);
    }

    $tabs = $row = $inactive = $activated = array();

/// user attempt count for reports link hover (completed attempts - much faster)
    $counts           = new stdClass;
    $counts->attempts = count_records('languagelesson_grades', 'lessonid', $lesson->id);
    $counts->student  = $course->student;
    
	$row[] = new tabobject('view', "$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id", get_string('preview', 'languagelesson'),
			get_string('previewlesson', 'languagelesson', format_string($lesson->name)));

	if (has_capability('mod/languagelesson:manage', $context)) {
		$row[] = new tabobject('edit', "$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id", get_string('edit', 'languagelesson'),
				get_string('edit', 'moodle', format_string($lesson->name)));

		$row[] = new tabobject('reports', "$CFG->wwwroot/mod/languagelesson/report.php?id=$cm->id", get_string('reports',
					'languagelesson'), get_string('viewreports', 'languagelesson', $counts));
	}

    if (has_capability('mod/languagelesson:grade', $context)) {
		$row[] = new tabobject('grader', "$CFG->wwwroot/mod/languagelesson/grader.php?id=$cm->id", get_string('holisticgrader',
					'languagelesson'));
    }

    $tabs[] = $row;


    switch ($currenttab) {
        case 'reportoverview':
        case 'reportdetail':
        /// sub tabs for reports (overview and detail)
            $inactive[] = 'reports';
            $activated[] = 'reports';

            $row    = array();
            $row[]  = new tabobject('reportoverview', "$CFG->wwwroot/mod/languagelesson/report.php?id=$cm->id&amp;action=reportoverview", get_string('overview', 'languagelesson'));
            $row[]  = new tabobject('reportdetail', "$CFG->wwwroot/mod/languagelesson/report.php?id=$cm->id&amp;action=reportdetail", get_string('detailedstats', 'languagelesson'));
            $tabs[] = $row;
            break;
        case 'collapsed':
        case 'full':
        case 'single':
        /// sub tabs for edit view (collapsed and expanded aka full)
            $inactive[] = 'edit';
            $activated[] = 'edit';
            
            $row    = array();
            $row[]  = new tabobject('collapsed', "$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id&amp;mode=collapsed", get_string('collapsed', 'languagelesson'));
            $row[]  = new tabobject('full', "$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id&amp;mode=full", get_string('full', 'languagelesson'));
            $tabs[] = $row;
            break;
    }

    print_tabs($tabs, $currenttab, $inactive, $activated);

?>
