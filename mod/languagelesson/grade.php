<?php  // $Id: grade.php 637 2011-07-19 16:02:21Z griffisd $

    require_once("../../config.php");

    $id   = required_param('id', PARAM_INT);          // Course module ID

    if (! $cm = get_coursemodule_from_id('lesson', $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $lesson = get_record("languagelesson", "id", $cm->instance)) {
        error("lesson ID was incorrect");
    }

    if (! $course = get_record("course", "id", $lesson->course)) {
        error("Course is misconfigured");
    }

    require_login($course->id, false, $cm);

    if (has_capability('mod/languagelesson:edit', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        redirect('report.php?id='.$cm->id);
    } else {
        redirect('view.php?id='.$cm->id);
    }

?>
