<?php


require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');


/// pull the course module context id
	$cmid = required_param('cmid', PARAM_INT);
/// and retrieve the course module object
	list($cm, $course, $lesson) = languagelesson_get_basics($cmid);
	
/// pull the attempt data for the attempt clicked on
	$attemptid = required_param('attemptid', PARAM_INT);
	
	if (! $attempt = get_record('languagelesson_attempts','id',$attemptid)) {
		error('Could not retrieve attempt record.');
	}
	if (! $manualattempt = get_record('languagelesson_manattempts', 'id', $attempt->manattemptid)) {
		error('Could not retrieve manual attempt record.');
	}
	
//////////////////////////////////////////////////////////////////////////////
/// if 'submitting' is set, then the form was just submitted here, so save ///
/// thesaved textual response and grade into the attempt data              ///
	
	$submitting = optional_param('submitting', null, PARAM_RAW);
	if ($submitting) {
		
		//require_once('uploadlib.php');
		
		
	////////////////////////////////
	// Handle the text response
		$text_response = trim(optional_param('text_response', '', PARAM_RAW));
		$text_response = clean_param($text_response, PARAM_CLEANHTML);
		$text_response = addslashes($text_response);
		
		if ($text_response) {
		/// pull the id of the old text feedback record, if there is one
			$oldfeedbackid = get_field_select('languagelesson_feedback', 'id',
												"lessonid = $lesson->id
												and pageid = $attempt->pageid
												and userid = $attempt->userid
												and attemptid = $attempt->id
												and teacherid = $USER->id
												and text NOT LIKE ''");
			
		/// build the text feedback object
			$feedback = new stdClass();
			$feedback->lessonid = $lesson->id;
			$feedback->pageid = $attempt->pageid;
			$feedback->userid = $attempt->userid;
			$feedback->attemptid = $attempt->id;
			$feedback->manattemptid = $manualattempt->id;
			$feedback->teacherid = $USER->id;
			$feedback->text = $text_response;
			$feedback->timeseen = time();
			
		/// and insert it into the DB
			if ($oldfeedbackid) {
				$feedback->id = $oldfeedbackid;
				if (! $update = update_record('languagelesson_feedback', $feedback)) {
					error('Could not update text feedback record.');
				}
			} else {
				if (! $feedbackid = insert_record('languagelesson_feedback', $feedback)) {
					error("Could not insert text feedback record!");
				}
			}
			
		}
	// /handle the text response
	////////////////////////////////
		
		
		
	////////////////////////////////
	// Handle the manual attempt record
		$manualattempt->graded = 1; //flag as graded
		$manualattempt->resubmit = 0; //reset the resubmit flag
		// unset the essay attribute so that update_record doesn't have a heart attack
		unset($manualattempt->essay);
		
		if (! $update = update_record('languagelesson_manattempts', $manualattempt)) {
			error('Could not update manual attempt record!');
		}
	// /handle the manual attempt record
	////////////////////////////////
		
		
		
	////////////////////////////////
	// Handle the assigned score
		$grade = optional_param('grade', -1, PARAM_NUMBER);
		if ($grade != -1) {
			$attempt->score = $grade;
			if (! $update = update_record('languagelesson_attempts', $attempt)) {
				error('Could not save the score for this attempt!');
			}
		}
	// /handle the assigned score
	////////////////////////////////
		
		
		
		
	/// nothing more to do here: refresh the opener screen (the grader window), and
	/// refresh this window to reflect the newly-submitted textual feedback
		echo "<script type=\"text/javascript\">window.opener.location.reload();"
			 ."window.location.href='$CFG->wwwroot/mod/languagelesson/respond_window.php"
			 ."?cmid=$cmid&attemptid=$attemptid';</script>";
	}
	
/// end submission code //////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
	
	
/// print the header
	print_header("$course->fullname: $lesson->name", "$course->fullname: Grading $lesson->name");
	
	$student = get_record("user", "id", $attempt->userid);
	$stuname = $student->firstname . ' ' . $student->lastname;
	
	$page = get_record('languagelesson_pages', 'id', $attempt->pageid);
	
  /// since the FB recorder only supports audio questions right now, check if this is an
  /// audio or not and flag use of FB recorder appropriately
	if ($page->qtype == LL_AUDIO) {
		$force_simple_recorder = false;
	} else {
		$force_simple_recorder = true;
	}
	
	echo "<h3>Feedback for $stuname on $page->title</h3>";


?>

<script type="text/javascript">
//<!--[CDATA[


/*
 * Javascript for handling textual feedback and grading submissions
 *
 * Stores both values in JS variables, so they can be fed by calling
 * retrieve_text_and_grade into the feedback revlet
 */

	var text_rspns = "";
	var grade = null;

	function update_text_rspns() {
	/*
	 * refresher script to update the value of the stored text_rspns
	 * on a regular basis (called with a setInterval at bottom of page)
	 */
		
	  /// detect if the WYSIWYG is being used -- if so, there'll be an
	  /// iframe in the page
		var allframes = document.getElementsByTagName('iframe');
		if (allframes.length > 0) {
		  /// there is, and we want to save the formatting, so get its
		  /// innerHTML value
			theframe = allframes[0];
			text_rspns = theframe.contentWindow.document.body.innerHTML;
		} else {
	  /// not using WYSIWYG, so just get the default textarea
			theframe = document.getElementById('edit-text_response');
			if (theframe) {
				text_rspns = theframe.textContent;
			} else {
	  /// found nothing; issue a warning
				alert('WARNING: could not retrieve the text feedback. Textual feedback cannot be saved for this submission.');
			}
		}
		
////////////////////////////////////////////////////////////////////////////
// extra lines to account for textual response and grade tracking being
// performed by this page, and NOT the upload function
		var the_input = document.getElementById('text_response_input');
		the_input.value = text_rspns;
////////////////////////////////////////////////////////////////////////////
		
	}
	
	function update_grade(sender, maxgrade) {
	/*
	 * The onblur of the grade input field.  Does error-checking for
	 * the input grade, and if it's valid, updates the stored grade
	 * value.
	 */
	 	var valid = true;
		grd = Number(sender.value);
	  /// check if it's a legit number
		if (grd === null) {
			alert("You have entered an invalid grade format.");
			valid = false;
		}
	  /// make sure it's not too high
		if (grd > maxgrade) {
			alert("The grade you entered is higher than the maximum grade allowed for this submission.");
			valid = false;
		}
	  /// it's good, so save it
		if (valid) { grade = grd; }
		
	  /// if it was bad, reset it
	  	else {
			var the_input = document.getElementById('grade_input');
			the_input.value = grade;
		}
	}
	
	function retrieve_text_and_grade() {
	/*
	 * feeds out the teacher's textual feedback and their input grade
	 * value as an array.
	 */
		
	  /// if the teacher never explicitly set a grade, but was
	  /// satisfied with the default value, grade will still be null,
	  /// so pull the value of the text field
		if (grade === null) {
			grade = Number(document.getElementById('grade').value);
		  /// since they could have entered an invalid grade and never
		  /// entered a valid one, it's possible that in retrieving the
		  /// value of the input field, what we snagged was an invalid
		  /// format--if so, set it to 0
		  /// TODO: debug here for if they put in too high of a grade
			if (grade === null) {
				grade = 0;
			}
		}
		
	  /// debug
		//alert('text is ' + text_rspns + '\n\ngrade is ' + grade);
		
	  /// construct and return the data array
		data = new Array();
		data['textfeedback'] = text_rspns;
		data['grade'] = grade;
		return data;
	}




//]]-->
</script>

<form id="submissionform" action="respond_window.php" method="post">

<?php print_simple_box_start('center'); ?>
	
	<div id="grade_area">
		<label target="grade">Grade <?php
			$maxscore = get_record('languagelesson_answers', 'id', $attempt->answerid);
			$maxscore = (float)$maxscore->score;
			echo '(0-'.$maxscore.')';
		?>
		</label>
		<input id="grade_input" name="grade" type="text" value="<?php echo $maxscore; ?>" onblur="update_grade(this, <?php echo
			$maxscore; ?>);" />
		<input type="hidden" name="maxgrade" value="<?php echo $maxscore; ?>" />
	</div>

	<table id="top_half">
		<tr>
			<td id="textFeedbackCell" class="halfcell">
				<?php languagelesson_print_feedback_table($manualattempt, true); ?>
			</td>
			<td id="studentSubmissionCell" class="halfcell">
				<?php
				if ($page->qtype == LL_AUDIO) {
					echo '<div id="audioSubmissionString">'.get_string('audiosubmissionstring', 'languagelesson').'</div>';
				} else {
					echo '<div class="subheader">'.get_string('studentsubmission', 'languagelesson').'</div>';
					?>
					<table id="studentSubmissionTable">
						<tr>
							<td id="studentPicture">
								<?php print_user_picture($student, $lesson->course, $student->picture); ?>
							</td>
							<td id="submissionInfo">
								<div class="studentName"><?php echo $stuname; ?></div>
								<div class="submissionTime"><?php echo userdate($attempt->timeseen); ?></div>
							</td>
						</tr>
						<tr>
							<td id="submissionCell" colspan="2">
								<?php
								if ($page->qtype == LL_VIDEO) {
									// do shit
									echo '<div id="essaySubmission">';
									$dir = languagelesson_get_file_area($manualattempt);
									$src = "$dir/$manualattempt->fname";
									languagelesson_embed_video_player($src);
									echo '</div>';
								} else {
									// it's an essay, print out their submission
									echo '<div id="essaySubmission">';
									echo clean_param($manualattempt->essay, PARAM_CLEANHTML);
									echo '</div>';
								}
								?>
							</td>
						</tr>
					</table><?php
				}
				?>
			</td>
		</tr>
	</table>

<?php print_simple_box_end(); ?>


<?php print_simple_box_start('center'); ?>

<table id="recorderContainer">
	
	<tr>
		
		<?php if ($page->qtype == LL_AUDIO) { ?>
		<td id="student_picture_area">
			<?php
			print_user_picture($student, $lesson->course, $student->picture);
			?>
		</td>
		<?php } ?>
		
		<td id="feedback_recorder_container">
			<?php if ($page->qtype == LL_AUDIO) { ?>
			<div class="student_name"> <?php echo $stuname; ?> submitted </div>
			<div class="submission_time"> on <?php echo userdate($attempt->timeseen); ?> </div>
			<?php } 
			
		/// if this is an AUDIO question, show the fancy audio feedback recorder
			if(!$force_simple_recorder) {
			
				// print the directions
				echo '<div class="revletInstructions">'.get_string('fbrecorderinstructions', 'languagelesson').'</div>';

			  /// show the FB recorder revlet stack
				include('runrev/feedback/recorder/revA.php');
	
				echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
				echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
				echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
				echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
				echo "\t\tid=\"" . $attempt->lessonid . "\"\n";
				echo "\t\tuserid=\"" . $USER->id . "\"\n";
				echo "\t\tsubmitscript=\"document.forms['submissionform'].submit();\"\n";
				
				$stufilepath = languagelesson_get_student_file_path($manualattempt);
				
				echo "\t\tstudentfile=\"" . $CFG->wwwroot . "/file.php" . $stufilepath . "\"\n"; //path to the student file
				
				$feedbackpaths = languagelesson_get_feedback_file_paths($manualattempt, $USER->id);
				echo "\t\tfeedbackfnames=\"".(($feedbackpaths) ? implode(',', $feedbackpaths) : '')."\"\n";
				
				echo "\t\tuploadtarget=\"" . $CFG->wwwroot . "/mod/languagelesson/upload.php\"\n"; 
				echo "\t\tuploadhost=\"" . $_SERVER['HTTP_HOST'] . "\"\n";
				echo "\t\tuploadpath=\"" . preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME'])."upload.php\"\n";
				/*
				 * uploadparams -- params fed into the uploadtarget
				 *
				 // * NOTE that this is only a list of those params fed to the upload script
				 // * via this field -- upload.php has a full list of all params used.
				 *
				 * @param $id => context ID for the languagelesson
				 * @param $pageid => ID of the languagelesson page being graded
				 * @param $userid => ID of the user grading (the teacher)
				 * @param $attemptid => ID of the attempt being graded
				 * @param $sesskey => the Moodle session key -- used for validation in the upload script
				 * @param $mode => OPTIONAL switch to mark this as uploading feedback
				 */
				echo "\t\tuploadparams=\"id=" . $cmid . "&pageid=" . $attempt->pageid . "&userid="
					 . $USER->id . "&sesskey=" . sesskey() . "&attemptid=" . $attempt->id
					 . "&mode=1\"\n"; 
				
				include('runrev/revB.php');
			}
			
		/// if, on the other hand, it's an ESSAY or a VIDEO type, just print out a simple single-file recorder for the teacher's use
			else {

				// print the directions
				echo '<div class="revletInstructions">'.get_string('fbrecorderinstructionssimple', 'languagelesson').'</div>';

				include('runrev/audio/revA.php');
				
				echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
				echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
				echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
				echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
				echo "\t\tid=\"" . $lesson->id . "\"\n"; 
				echo "\t\tpageid=\"" . $page->id . "\"\n";
				echo "\t\tpageURL=\"" . languagelesson_get_current_page_url() . "\"\n";
				echo "\t\tuploadtarget=\"" . $CFG->wwwroot . "/mod/languagelesson/upload.php\"\n"; 
				echo "\t\tuploadhost=\"" . $_SERVER['HTTP_HOST'] . "\"\n";
				echo "\t\tuploadpath=\"".preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME'])."upload.php\"\n";
				echo "\t\tuploadparams=\"id=" . $cmid . "&pageid=" . $attempt->pageid . "&userid="
					 . $USER->id . "&sesskey=" . sesskey() . "&attemptid=" . $attempt->id
					 . "&mode=1\"\n"; 
				// NOTE that this array should only ever have one item in it, but it comes out as an array, so pull it here for brevity
				// in access below
				$feedbackarray = languagelesson_get_feedback_file_paths($manualattempt, $USER->id);
				echo "\t\tlodefile=\"".$feedbackarray[0]."\"\n";
				echo "\t\tsubmitscript=\"document.forms['submissionform'].submit();\"\n";

				include('runrev/revB.php');
			}
			?>
		</td>
		
	</tr>
	
</table>


<?php print_simple_box_end(); ?>

	<!-- And close the submission form -->
	<input id="text_response_input" type="hidden" name="text_response" />
	<input type="hidden" name="cmid" value="<?php echo $cmid; ?>" />
	<input type="hidden" name="attemptid" value="<?php echo $attemptid; ?>" />
	<input type="hidden" name="submitting" value="true" />

</form>


<?php
/*
 * The "cancel" and "next" buttons 
 */
?>
<form id="theform" action="respond_window.php" method="get">
	<input type="hidden" name="cmid" value="<?php echo $cmid; ?>" />
	<input type="hidden" id="attemptidinput" name="attemptid" />
	
	<script type="text/javascript">
		var theInp;
		function setAttemptId(val) {
			theInp = document.getElementById('attemptidinput');
			theInp.value = val;
		}
	</script>
	<?php
		
		function find_page_by_id($pageid, $pages) {
			$i = 0;
			while ($i < count($pages) && $pages[$i]->id != $pageid) {
				$i++;
			}
			if ($i >= count($pages)) {
				return null;
			} else {
				return $i;
			}
		}
		
		function strip_autograde_pages($sortedpages) {
			$outsortedpages = array();
			for ($i=0; $i<count($sortedpages); $i++) {
				$curpageqtype = $sortedpages[$i]->qtype;
				if ($curpageqtype == LL_AUDIO
					|| $curpageqtype == LL_VIDEO
					|| $curpageqtype == LL_ESSAY) {
					$outsortedpages[] = $sortedpages[$i];
				}
			}
			return $outsortedpages;
		}
		
		/// note that we need to get ALL pages first, so that languagelesson_sort_pages
		/// works, then we can safely remove the autograded pages from the page list
		$pages = get_records("languagelesson_pages", "lessonid", $attempt->lessonid);
		$sortedpages = languagelesson_sort_pages($pages);
		$sortedpages = strip_autograde_pages($sortedpages);
		$thispageindex = find_page_by_id($attempt->pageid, $sortedpages);
		
		$userid = $attempt->userid;
		
		if (($thispageindex + 1) >= count($sortedpages)) {
			$nextpageid = null;
			$nextattempt = null;
		} else {
			$nextpageid = $sortedpages[$thispageindex + 1]->id;
			$nextattempt = languagelesson_get_most_recent_attempt_on($nextpageid, $userid);
		}
		
		if (($thispageindex - 1) < 0) {
			$prevpageid = null;
			$prevattempt = null;
		} else {
			$prevpageid = $sortedpages[$thispageindex - 1]->id;
			$prevattempt = languagelesson_get_most_recent_attempt_on($prevpageid, $userid);
		}
		
		
		$students = languagelesson_get_students($lesson->course);
		$studentIDs = array_keys($students);
		$thisStudentIndex = array_search($userid, $studentIDs);
		
		if (($thisStudentIndex + 1) >= count($studentIDs)) {
			$nextstuid = null;
			$nextstuattempt = null;
		} else {
			$offset = 1;
			$nextstuid = $studentIDs[$thisStudentIndex + $offset];
			while ($thisStudentIndex + $offset < count($studentIDs)
				   && !$nextstuattempt = languagelesson_get_most_recent_attempt_on($attempt->pageid, $nextstuid)) {
				$offset++;
				$nextstuid = $studentIDs[$thisStudentIndex + $offset];
			}
		}
		
	?>
	
	<table id="nav_table">
		<tr>
			<td class="thiscell" colspan="2">
				<input class="nav_button" id="nav_nextstu_button" type="submit" value="<?php echo get_string('nextstudent',
					'languagelesson'); ?>" <?php echo (($nextstuattempt) ? "onclick='setAttemptId(\"$nextstuattempt->id\")';" :
					'disabled="disabled"'); ?> />
			</td>
		</tr><tr>
			<td class="thiscell">
				<input class="nav_button" id="nav_prev_button" type="submit" value="<?php echo get_string('previousquestion',
					'languagelesson'); ?>" <?php echo (($prevattempt) ? "onclick='setAttemptId(\"$prevattempt->id\");'" :
					'disabled="disabled"'); ?> />
			</td><td class="thiscell">
				<input class="nav_button" id="nav_next_button" type="submit" value="<?php echo get_string('nextquestion',
					'languagelesson'); ?>" <?php echo (($nextattempt) ? "onclick='setAttemptId(\"$nextattempt->id\");'" :
					'disabled="disabled"');  ?> />
			</td>
		</tr><tr>
			<td class="thiscell" colspan="2">
				<input class="nav_button" id="nav_cancel_button" type="submit" onclick="window.close();" value="<?php echo
					get_string('cancel', 'languagelesson'); ?>" />
			</td>
		</tr>
	</table>
			
	<div style="text-align:center">
		<a href="https://docs.google.com/a/carleton.edu/spreadsheet/viewform?formkey=dGw5bjNrN2tjS3MwbC05NnVnNV9HZFE6MQ"
			target="_blank" style="font-size:0.75em; margin-top:25px;">Report a problem</a>
			</div>
	
</form>



<script type="text/javascript">
// tell the page to refresh the stored value of the teacher's input textual feedback
// really, really damn often
setInterval("update_text_rspns()",10);
</script>
