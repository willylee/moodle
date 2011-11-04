<?php
/* uploadlib.php
	Functions used in uploading a RunRev submission (audio/video type question in modified lesson module)
*/
/*
 * NOTE that in these functions, the Moodle error() functions is not used,
 * and an error_log followed by a return takes its place.  This is because these functions
 * are only called in unseen pages by the Revlets, so calling error() is pointless--no one
 * can see the error message.  As such, error_logging is done instead, so at least the admin
 * can find the messages.
 */


function upload() {
/*
 * function to handle uploading of audio and video RunRev submissions in the
 * modified lesson module; moves the file from temp directory it was uploaded
 * to into a permanent location in the moodle data folder, and updates database
 * to reflect submission
 */
	global $CFG, $USER;
	global $cm, $id, $pageid, $lesson, $userid;
	
	require_capability('mod/languagelesson:submit', get_context_instance(CONTEXT_MODULE, $cm->id));
	
	//make sure uploaded file actually made it to the temp directory we'll pull it from
	if (!is_uploaded_file($_FILES['newfile']['tmp_name']))
	{
		error_log('File failed to upload to temp directory.  Bailing on upload function.');
		return;
	}
	
	// pull the path to the directory within the filesystem to upload the submitted file to
	$to = languagelesson_get_local_file_area(null, null, $pageid, $userid);
	
	require_once($CFG->dirroot . '/lib/uploadlib.php');
	$um = new upload_manager('newfile',false,false,$lesson->course,false);
	if ($um->process_file_uploads($to))
	{
	  /// pull the filename of the just-submitted file
		$fname = $um->get_new_filename();
		
	  /// pull the old submission for this question so we can modify it;
	  /// if there isn't one, insert a dummy submission
	  	$firstAttempt = false;
		$attempt = languagelesson_get_most_recent_attempt_on($pageid, $userid);
	
		// if there was no old attempt, create a dummy submission
		// we need this so that manattempt can have an attemptid to store
		if (!$attempt) {
			$attempt = insert_dummy_record($lesson->id, $pageid, $userid);
			$firstAttempt = true;
		// if there was, mark it as no longer current
		} else {
			$updater = new stdClass;
			$updater->id = $attempt->id;
			$updater->current = 0;
			if (! update_record('languagelesson_attempts', $updater)) {
				error_log("Failed to mark old attempt # $updater->id as non-current!");
			}
		}

		// create the $uattempt object to use in updating the attempt record
		$uattempt = new stdClass;
		$uattempt->id = $attempt->id;
		
	  /// set up the manual attempt record, depending on if first attempt or not
		if ($firstAttempt) {
		  /// store all relevant data about student's uploaded submission
			$manattempt = new stdClass();
			$manattempt->lessonid = $lesson->id;
			$manattempt->userid = $userid;
			$manattempt->pageid = $pageid;
			$manattempt->attemptid = $attempt->id;
			// store the question type for this attempt
			$page = get_record('languagelesson_pages', 'id', $pageid);
			$manattempt->type = $page->qtype;
		} else {
			$manattempt = get_record('languagelesson_manattempts', 'id', $attempt->manattemptid);
			// unset the essay attribute, as it could have single quotes in it that will make Moodle freak the fuck out
			unset($manattempt->essay);
		}

		$manattempt->fname = $fname;
		$manattempt->timeseen = time();

		// mark as resubmission if necessary
		if (!$firstAttempt && ($manattempt->viewed || $manattempt->graded)) {
			$manattempt->resubmit = 1;
			$manattempt->viewed = 0;
			$manattempt->graded = 0;
		}
			
		// if this lesson is to be auto-graded...
		if ($lesson->autograde) {
		  	// flag it as graded
			$manattempt->graded = 1;
			$manattempt->viewed = 1;
		  	// set the grade to the maximum point value for this question
			$maxscore = get_field('languagelesson_answers', 'score', 'id', $attempt->answerid);
			$uattempt->score = $maxscore;
		}
			
		// insert/update the manual attempt record
		if ( ($firstAttempt && ! $manattemptid = insert_record('languagelesson_manattempts', $manattempt))
				|| (!$firstAttempt && ! update_record('languagelesson_manattempts', $manattempt)) ) {
			error_log('Failed to upload manual attempt record for attempt ID ' . $attempt->id .
					  ' and filename ' . $manattempt->fname . '. Bailing now...');
			return;
		}
			
		// store any other fields that need to be updated in the attempt record, including updating retry field
		if ($firstAttempt) { $uattempt->manattemptid = $manattemptid; }
		if (! $firstAttempt) { $uattempt->retry = $attempt->retry + 1; }
		$uattempt->timeseen = time();
		
		// update attempt record
		if (update_record('languagelesson_attempts', $uattempt)) {
			error_log("Student file upload and record update succeeded!");
		} else {
			error_log("Student file upload succeeded; record update did not.");
		}
	}

	// if the file upload failed, log it
	else
	{
		error_log("Student file upload failed.");
	}
}





function upload_feedback() {
/*
 * function to handle uploading of audio and video RunRev submissions in the
 * modified lesson module; moves the file from temp directory it was uploaded
 * to into a permanent location in the moodle data folder, and updates database
 * to reflect submission
 */
	global $CFG, $USER;
	global $cm, $lesson, $userid, $attemptid;
	
	error_log("upload_feedback called to handle " . count($_FILES) . " feedback files");
	
	require_capability('mod/languagelesson:submit', get_context_instance(CONTEXT_MODULE, $cm->id));
	
  /// make sure uploaded files actually made it to the temp directory we'll pull it from
	foreach (array_keys($_FILES) as $file) {
		if (!is_uploaded_file($_FILES[$file]['tmp_name']))
		{
			error_log('A file failed to upload to temp directory.  Bailing on upload function.');
			return;
		}
	}
	
/// pull the student's attempt data
	if (! $attempt = get_record("languagelesson_attempts", "id", $attemptid)) {;
		error_log("Failed to fetch attempt record. Bailing on upload.");
		return;
	}
	
/// and pull the manual attempt record
	if (! $manattempt = get_record('languagelesson_manattempts', 'id', $attempt->manattemptid)) {
		error_log("Failed to fetch manual attempt record for this attempt. Bailing on upload.");
		return;
	}
	
/// pull the path to the feedback directory to upload to
	$uploadpath = languagelesson_get_local_file_area(null, true, $attempt->pageid, $attempt->userid);
	
	//languagelesson_tempify_old_feedback_files($uploadpath);
	
/// pull the list of old feedback record IDs (to be deleted after successful insertion of new ones)
	$oldfeedbackids = array();
	$oldfeedbacks = get_records_select('languagelesson_feedback', "manattemptid=$manattempt->id and teacherid=$userid");
	foreach ($oldfeedbacks as $oldfeedback) { $oldfeedbackids[] = $oldfeedback->id; }
	
/// if there are feedback files to upload, upload them
	if (count($_FILES) > 0) {
		require_once($CFG->dirroot . '/lib/uploadlib.php');
		$um = new upload_manager('',false,false,$lesson->course,false);
		if ($um->process_file_uploads($uploadpath))
		{
			//languagelesson_delete_tempified_feedback_files($uploadpath);

		/// erase the old feedback records
			error_log('imploded oldfeedbackids is ' . implode(',',$oldfeedbackids));
			delete_records_select('languagelesson_feedback', 'id in ('.implode(',',$oldfeedbackids).')');
			
		/// store the languagelesson_feedback record for each submitted file
			foreach (array_keys($_FILES) as $file) {
				
			/// construct the feedback record
				$feedback = new stdClass();
				$feedback->lessonid = $lesson->id;
				$feedback->pageid = $attempt->pageid;
				$feedback->userid = $attempt->userid;
				$feedback->attemptid = $attempt->id;
				$feedback->manattemptid = $manattempt->id;
				$feedback->teacherid = $userid;
				$feedback->fname = $_FILES[$file]['name'];
				$feedback->timeseen = time();
				
			/// save the feedback record
				if (! $feedbackid = insert_record('languagelesson_feedback', $feedback)) {
					error_log("Could not insert feedback record for file $feedback->fname!");
					//languagelesson_revert_old_feedback_files($uploadpath);
					return;
				} else {
					error_log("Inserted feedback record for file $feedback->fname");
				}
				
			}
			
			// and update the manual attempt record to say it's graded
			$manattempt->graded = 1;
			// deflag resubmission
			$manattempt->resubmit = 0;
			// unset the essay attribute so single quotes don't make Moodle have a seizure
			unset($manattempt->essay);

			// update manual attempt record
			if (! $update = update_record('languagelesson_manattempts', $manattempt)) {
				error_log("Feedback file upload succeeded, but updating manual attempt record did not");
			} else {
				error_log("Feedback file upload and manual attempt record update succeeded!");
			}
			
		}
		else
		{
			error_log("Feedback file upload failed.");
			//languagelesson_revert_old_feedback_files($uploadpath);
			return;
		}
		
	}
	
/// delete the old feedback records
	//delete_records_select('languagelesson_feedback', 'id in ('. implode(',', $oldfeedbackids) .')');
	
}




/* 
 * insert a dummy answer attempt record into the lesson_attempts table;
 * establishes lessonid, pageid, userid, and answerid
 */
function insert_dummy_record($lessonid, $page, $user)
{
	$data = new Object;
	$data->lessonid = $lessonid;
	$data->pageid = $page;
	$data->userid = $user;
	$data->answerid = get_field_select("languagelesson_answers", "id", "lessonid = $lessonid and pageid = $page");
	// since only one attempt record is kept for audio/video questions, it will ALWAYS be the current one, so
	// mark it here as such, and then never worry about it again
	$data->iscurrent = 1;
	
	if (insert_record("languagelesson_attempts", $data)) {
		return get_record("languagelesson_attempts", "lessonid", $lessonid, "pageid", $page, "userid", $user);
	} else {
	  /// insertion failed somehow, so mock the user mercilessly
		error_log("Failed to insert dummy record.  Fail at being a dummy?  Epic, man.");
	}
}




?>
