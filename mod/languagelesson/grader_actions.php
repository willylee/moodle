<?php

require('../../config.php');
require('lib.php');
require('locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$mode = optional_param('mode', 0, PARAM_INT);
$savegradesflag = optional_param('savegradesflag', 0, PARAM_INT);

list($cm, $course, $lesson) = languagelesson_get_basics($cmid);


///////////////////////////////////////////////////////
// SAVING MANUALLY-ASSIGNED GRADES
///////////////////////////////////////////////////////

if ($savegradesflag) {
	$stugrades = $_POST['students_grades'];
	$stugrades = explode('|',$stugrades);
	// kill the final entry in the array, as it's empty
	array_pop($stugrades);
	$stugraders = array();
	foreach ($stugrades as $stugrade) {
		$chunks = explode(',', $stugrade);
		$stugraders[(int)$chunks[0]] = (int)$chunks[1];
	}
	
	foreach ($stugraders as $ID => $gradeval) {
		$grade = new stdClass();
		$grade->lessonid = $lesson->id;
		$grade->userid = $ID;
		$grade->grade = $gradeval;
		$grade->completed = time();
		
		/// if this lesson already has a grade saved for this user, update it
		if ($oldgrade = get_record("languagelesson_grades", "lessonid", $lesson->id,
								   "userid", $ID)) {
			$grade->id = $oldgrade->id;
			if (!$update = update_record("languagelesson_grades", $grade)) {
				error("Grader: Manual grade not updated");
			}
		}
		/// otherwise, just insert it
		else {
			if (!$newgradeid = insert_record("languagelesson_grades", $grade)) {
				error("Grader: Manual grade not inserted");
			}
		}

		// also, mark all manual attempts for this lessonID, userID pair as graded
		execute_sql("UPDATE {$CFG->prefix}languagelesson_manattempts
					SET graded = 1
					WHERE lessonid = $lesson->id
					AND userid = $ID");
	}
	
	languagelesson_update_grades($lesson);
	
}

///////////////////////////////////////////////////////
///////////////////////////////////////////////////////



///////////////////////////////////////////////////////
// SENDING NOTIFICATION EMAILS
///////////////////////////////////////////////////////

else {
	$stuids = $_POST['students_toemail'];

	$stuids = explode(",", $stuids);

	foreach ($stuids as $stuid) {
		$stu = get_record('user', 'id', $stuid);
		
		$a->coursename = $course->shortname;
		$a->modulename = get_string('modulenameplural', 'languagelesson');
		$a->llname = $lesson->name;
		
		$subject = get_string('emailsubject', 'languagelesson', $a);
		
		$thisteach = get_record('user', 'id', $USER->id);
		$info = new stdClass();
		$info->teacher = fullname($thisteach);
		$info->llname = $lesson->name;
		$info->url = "$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id";
		
		$usehtml = optional_param('useHTML', false, PARAM_BOOL);
		
		$plaintext = "$course->shortname -> $a->modulename -> $lesson->name\n";
		$plaintext .= "-------------------------------------------------------\n";
		$plaintext .= get_string('emailmessage', 'languagelesson', $info);
		$plaintext .= "-------------------------------------------------------\n";
		
		$html = '';
		if ($usehtml) {
			$html = "<p><font face=\"sans-serif\">".
			"<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> -> ".
			"<a href=\"$CFG->wwwroot/mod/languagelesson/index.php?id=$course->id\">$a->modulename</a> -> ".
			"<a href=\"$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id\">$lesson->name</a></font></p>";
			$html .= "<hr /><font face=\"sans-serif\">";
			$html .= "<p>".get_string('emailmessagehtml', 'languagelesson', $info)."</p>";
			$html .= "</font><hr />";
		}
		
		if (!email_to_user($stu, $thisteach, $subject, $plaintext, $html)) {
			//error_log('Emailing students failed.');
			error('Emailing students failed.');
		} else {
			error_log('Emailing students succeeded!');
		}
	}
}

/// and redirect to the grader page
$path = "$CFG->wwwroot/mod/languagelesson/grader.php?id=$cmid";
if ($savegradesflag) {
	$path .= "&amp;savedgrades=1";
} else {
	$path .= "&amp;sentemails=1";
}
redirect($path);
