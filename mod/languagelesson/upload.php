<?php  // $Id: upload.php 661 2011-07-26 21:21:30Z griffisd $

/*
 * uploadparams -- params fed into the uploadtarget
 *
 * @param $id => context ID for the languagelesson
 * @param $pageid => ID of the languagelesson page being graded
 * @param $userid => ID of the user grading (the teacher)
 * @param $attemptid => ID of the attempt being graded
 * @param $sesskey => the Moodle session key -- used for validation in the upload script
 * @param $mode => OPTIONAL switch to mark this as uploading feedback (as opposed to student file)
//  * @param $hasfile => flag if a file is being uploaded for feedback; defaults to 0 (no file)
//  * @param $hastext => flag if text is being uploaded for feedback; defaults to 0 (no text)
//  * @param $textfeedback => submitted textual feedback
//  * @param $grade => submitted grade value for the question
 */


	error_log('upload.php called');
    require_once("../../config.php");
	require_once('locallib.php');
    require_once("uploadlib.php");
    $id = optional_param('id', 0, PARAM_INT);  // Course module ID
    $pageid = optional_param('pageid', 0, PARAM_INT); // Lesson page ID
    $userid = optional_param('userid', 0, PARAM_INT); // temp hack to get $USER->id, since $USER->id isn't working

  /// feedback-specific params
  	$attemptid = optional_param('attemptid', null, PARAM_INT);
    $mode = optional_param('mode', null, PARAM_INT);
	

    $error = false;
    if ($id) {
        if (! $cm = get_coursemodule_from_id('languagelesson', $id)) {
            error_log("Course Module ID was incorrect");
            $error = true;
        }

        if (! $lesson = get_record("languagelesson", "id", $cm->instance)) {
            error_log("Lesson ID was incorrect");
            $error = true;
        }

        if (! $course = get_record("course", "id", $lesson->course)) {
            error_log("Course is misconfigured");
            $error = true;
        }
    } else {
        if (!$lesson = get_record("languagelesson", "id", $l)) {
            error_log("Course module is incorrect");
            $error = true;
        }
        if (! $course = get_record("course", "id", $lesson->course)) {
            error_log("Course is misconfigured");
            $error = true;
        }
        if (! $cm = get_coursemodule_from_instance("languagelesson", $lesson->id, $course->id)) {
            error_log("Course Module ID was incorrect");
            $error = true;
        }
    }
    
    if ($error) {
    	error_log("Param initialization threw an error. Exiting now.");
    	exit();
    }

	// pull the context for permissions checking below
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);


  /// now that we've taken care of preliminaries, actually do the uploading;
  /// if the optional 'mode' param is set, then we're uploading recorded teacher
  /// feedback, so call that; otherwise, call the student upload function
  /// NOTE: only call the student upload function if it's actually a student uploading
	if (isset($mode)) {
		upload_feedback();
	} elseif (!has_capability('mod/languagelesson:manage', $context)) {
		upload();
	}

?>
