<?php // $Id$

/**
 * Extend the base assignment class for assignments where you upload a single file
 *
 */
class assignment_audio extends assignment_base {


    function print_student_answer($userid, $return=false){
           global $CFG, $USER;

        $filearea = $this->file_area_name($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);
                    $ffurl = get_file_url("$filearea/$file");

                    //died right here
                    //require_once($ffurl);
                    $output = '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                            '<a href="'.$ffurl.'" >'.$file.'</a><br />';
                }
            }
        }

        $output = '<div class="files">'.$output.'</div>';
        return $output;
    }





    function assignment_audio($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'audio';
    }






    function view() {

        global $USER;

        $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        require_capability('mod/assignment:view', $context);

        add_to_log($this->course->id, "assignment", "view", "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

        $this->view_header();

        $this->view_intro();

        $this->view_dates();

        $filecount = $this->count_user_files($USER->id);

        if ($submission = $this->get_submission()) {
			// view_feedback handles printing the FeedbackPlayer revlet, doesn't show feedback if feedback doesn't exist
			$this->view_feedback($submission);
        }

        if (has_capability('mod/assignment:submit', $context)
				&& $this->isopen()
				&& (!$submission
					|| ( $submission->timemarked == 0
						|| $filecount == 0
						|| $this->assignment->resubmit == 1 ) ) ) {
            $this->view_upload_form();
        }

        $this->view_footer();
    }






    function view_feedback($submission=NULL) {
        global $USER, $CFG;
        require_once($CFG->libdir.'/gradelib.php');
		require_once($CFG->libdir.'/filelib.php');

        if (!$submission) { /// Get submission for this assignment
            $submission = $this->get_submission($USER->id);
        }

		print_heading(get_string('submission', 'assignment'), '', 3);

		// open up the box that we'll put the feedback player in
		print_simple_box_start('center');
		echo '<div style="text-align:center">';



		/////////////////////////////////////////////////////////////////////
		// ADDED IN TO REPLACE COMPLEX FEEDBACK FUNCTIONALITY
		// (commented out below)

		// pull the path to the student's submitted file
		$fdirname = $this->file_area_name($submission->userid);
		$fdir = $this->file_area($submission->userid);
		$files = get_directory_list($fdir, '', false);
		// the desired student file should always be the only file in the dir (only one file upload allowed at once, old ones are
		// overwritten)
		if (count($files) != 1) { error('Found the wrong number of files!'); }
		$fname = array_pop($files);
		$furl = get_file_url("$fdirname/$fname");

		// END ADDED IN CODE
		/////////////////////////////////////////////////////////////////////



		// if textual response and/or a grade has been posted for this submission, display it 
		if ($submission->timemarked) {
			// since there is only one grading record stored for any student's submission on any assignment, pull it here and print out
			// the text-feedback and grade table
			$grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id, $USER->id);
			$item = $grading_info->items[0];
			$grade = $item->grades[$USER->id];
			if (!$grade->hidden && $grade->grade !== false && $grade->usermodified != $USER->id) {

				$teacher = get_record('user', 'id', $grade->usermodified);

				echo '<table cellspacing="0" class="feedback">';

				echo '<tr>';
				echo '<td class="left picture">';
				print_user_picture($teacher, $this->course->id, $teacher->picture);
				echo '</td>';
				echo '<td class="topic">';
				echo '<div class="from">';
				echo '<div class="fullname">'.fullname($teacher).'</div>';
				echo '<div class="time">'.userdate($submission->timemarked).'</div>';
				echo '</div>';
				echo '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="left side">&nbsp;</td>';
				echo '<td class="content">';
				if ($this->assignment->grade) {
					echo '<div class="grade">';
					echo get_string("grade").': '.$grade->str_long_grade;
					echo '</div>';
					echo '<div class="clearer"></div>';
				}

				echo '<div class="comment">';
				echo $grade->str_feedback;
				echo '</div>';
				echo '</tr>';
				


				/////////////////////////////////////////////////////////////////////
				// ADDED IN TO REPLACE COMPLEX FEEDBACK FUNCTIONALITY
				// (commented out below)
				// This will print out the FeedbackPlayer revlet for the single allowed recorded feedback file

				echo '<tr><td class="left side">&nbsp;</td><td class="content">';

				// check if there is audio feedback
				$feedbackdir = $this->feedback_area($submission->userid);
				error_log("feedbackdir is $feedbackdir");
				$contents = get_directory_list($feedbackdir, '', false);
				// if so, display heading and instructions appropriately
				if (count($contents)) {
					print_heading(get_string('audiofeedback', 'assignment'), '', 5);
					echo '<div class="revletInstructions">'.get_string('feedbackplayerinstructions', 'assignment').'</div>';
				}
				// otherwise, display submission heading and instructions
				else {
					print_heading(get_string('yoursubmission', 'assignment'), '', 5);
					echo '<div class="revletInstructions">'.get_string('feedbackplayerinstructionsnorspns', 'assignment').'</div>';
				}

				// set up vars so that the feedback player gets put in its own div (treated separately from the recorder)
				$flag = false;
				$qmodpluginID = true;
				$modpluginID = 'plugina';

				// show the FB player revlet stack
				include('feedback/player/revA.php');

				// print out the authentication variables
				echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
				echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
				echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
				echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
				echo "\t\tid=\"" . $this->cm->id . "\"\n";
				echo "\t\tuserid=\"" . $USER->id . "\"\n";
				// print out the requisite file paths
				// NOTE that in this case, teachers respond by just recording a single audio file of their own, so it is treated here
				// as a "student file", and the feedbackfnames are left blank
				echo "\t\tstudentfile=\"$furl\"\n"; //path to the student file to be downloaded
				echo "\t\tfeedbackfnames=\"".$this->get_feedback_file_paths($USER->id)."\"\n";

				include('feedback/player/revB.php');

				echo '</td></tr>';

				// END ADDED IN CODE
				/////////////////////////////////////////////////////////////////////




				echo '</table>';

			}
		}




		/*
		/////////////////////////////////////////////////////////////////
		// COMPLEX FEEDBACK CODE
		// Commented out because decision was made to limit assignment type feedbacks to one text, one audio file, period.
		// This both minimizes the number of changes to basic assignment functionality as used in these added types, and makes these
		// types behave more like the other assignment types (which limit feedback to one text feedback, period)

		// pull the path to the student's submitted file
		$fdirname = $this->file_area_name($submission->userid);
		$fdir = $this->file_area($submission->userid);
		$files = get_directory_list($fdir, '', false);
		// the desired student file should always be the only file in the dir (only one file upload allowed at once, old ones are
		// overwritten)
		if (count($files) != 1) { error('Found the wrong number of files!'); }
		$fname = array_pop($files);
		$furl = get_file_url("$fdirname/$fname");

		// pull the IDs of the teachers who have responded to this
		$feedbackdir = $this->feedback_area($submission->userid);
		$teacherIDs = get_directory_list($feedbackdir, '', false, true, false); // get only the directories

		// do all the fancy-schmancy multiple-revlet feedback printing stuff only if there actually are feedback files
		if (count($teacherIDs) > 0) {


			// print out the javascript used for swapping visible responses from different teachers
			echo 	"<script type=\"text/javascript\">
					
					var curselected = null;
					var curselected_oldid = null;
					var curpic = null;
					var curpic_oldid = null;
					
					function displayThisTeach(elname, picname) {
					  /// pull the element corresponding to the input name and the currently-visible element
						var element = document.getElementById(elname);
						curselected = document.getElementById('curselected');

						var pic = document.getElementById(picname);
						curpic = document.getElementById('curselectedpic');
						
					  /// only toggle elements if clicked on non-selected picture
						if (element.style.display == \"none\") {
							element.style.display = \"block\";
							curselected.style.display = \"none\";

							pic.className = 'activePic';
							curpic.className = 'inactivePic';
							
						  /// reset the formerly-visible element's id
							curselected.id = curselected_oldid;
						  /// and update the relevant values for the newly-selected element
							curselected_oldid = element.id;
							curselected = element;
							curselected.id = 'curselected';

							curpic.id = curpic_oldid;
							curpic_oldid = pic.id;
							curpic = pic;
							pic.id = 'curselectedpic';
						}
					}
				</script>";
			


			// establish variables used later in printing feedback content
			$basename = "fb_block_";
			$picname = "teacherPic_";
			$teachers = array();


			print_heading(get_string('submissionaudiofeedback', 'assignment'), '', 4);


			// print out the tabrow of the teacher pictures
			echo '<ul class="teacherPics">';
			foreach ($teacherIDs as $teacherid) {
				echo "<li id=\"{$picname}{$teacherid}\" class=\"inactivePic\" 
					onclick='displayThisTeach(\"{$basename}{$teacherid}\", \"{$picname}{$teacherid}\");'>";
				$teacher = get_record('user', 'id', $teacherid);
				print_user_picture($teacher, $this->course->id, $teacher->picture, 0, false, false);
				// store the teacher record for later access
				$teachers[$teacherid] = $teacher;
				echo '</li>';
			}
			echo '</ul>';
			// print this to cancel out the float=left of the above ul
			echo '<div style="clear:both"></div>';



			$firstFeedback = $basename . $teacherIDs[0];
			$firstPic = $picname . $teacherIDs[0];
			$flag = false;
			foreach ($teacherIDs as $teacherid) {
				echo "<div id='{$basename}{$teacherid}' class='feedbackBlock' style='display:none'>";
				//echo "<p>".$fbarr['text']."</p>";
				
				// print custom header for this teacher's comments
				$a->fullname = fullname($teachers[$teacherid]);
				echo '<p>'.get_string('teachercomments', 'assignment', $a).'</p>';

				// print instructions for using the feedback player revlet
				echo '<p class="revletInstructions">'.get_string('feedbackplayerinstructions', 'assignment').'</p>';

				if (!$flag) {
					// set up vars so that the feedback player gets put in its own div (treated separately from the recorder)
					$qmodpluginID = true;
					$modpluginID = 'plugina';
				}
				
				// show the FB player revlet stack
				include('feedback/player/revA.php');

				// print out the authentication variables
				echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
				echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
				echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
				echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
				echo "\t\tid=\"" . $this->cm->id . "\"\n";
				echo "\t\tuserid=\"" . $USER->id . "\"\n";
				// print out the requisite file paths
				echo "\t\tstudentfile=\"$furl\"\n"; //path to the student file to be downloaded
				echo "\t\tfeedbackfnames=\"".$this->get_feedback_file_paths($USER->id, $teacherid)."\"\n"; //comma-separated list of
																										   //teacher file URLS
				
				// only include the revB file once (it's only necessary once); after that, just close the embedding tags
				if (!$flag) {
					include($CFG->dirroot . '/mod/languagelesson/runrev/revB.php');
					$flag = true;
					// make sure that the extra revlets in the page are still not in <divs id="plugin" ..., so that if revWeb is not
					// installed, the audio/video recorder gets hidden properly
					$modpluginID = "irrelevant";
				} else {
					echo "></embed></object></div>";
				}
					
				// close this teacher's feedback div
				echo '</div>';
			}
			
			// print out the javascript that makes the first teacher's feedback visible
			echo '<script type="text/javascript">
					var firstFeedback = document.getElementById("'.$firstFeedback.'");
					firstFeedback.style.display = "block";
					curselected_oldid = "'.$firstFeedback.'";
					curselected = firstFeedback;
					curselected.id = "curselected";

					var firstPic = document.getElementById("'.$firstPic.'");
					firstPic.className = "activePic";
					curpic_oldid = "'.$firstPic.'";
					curpic = firstPic;
					curpic.id = "curselectedpic";
					</script>';

		
		}

		// END COMPLEX FEEDBACK CODE
		/////////////////////////////////////////////////////////////////
		*/





		// otherwise, just print out a FeedbackPlayer with the student's file in it, so they can see/hear their submission
		else {

			// print instructions for using the feedback player revlet
			echo '<p class="revletInstructions">'.get_string('feedbackplayerinstructionsnorspns', 'assignment').'</p>';

			// set up vars so that the feedback player gets put in its own div (treated separately from the recorder)
			$flag = false;
			$qmodpluginID = true;
			$modpluginID = 'fbplugin';

			// pull in starting tags
			include('feedback/player/revA.php');
			// print out authentication vars
			echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
			echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
			echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
			echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
			echo "\t\tid=\"" . $this->cm->id . "\"\n";
			echo "\t\tuserid=\"" . $USER->id . "\"\n";
			// print out file paths
			echo "\t\tstudentfile=\"$furl\"\n"; //path to the student file to be downloaded
			echo "\t\tfeedbackfnames=\"\"\n";
			// pull in ending script
			include('feedback/player/revB.php');

		}

		echo '</div>';

		print_simple_box_end();

    }

	




	/**
	 * Fetch the list of feedback files by a teacher for a student as a comma-separated string of web URLs
	 * @param int $stuid The student whose submission we're checking
	 * @param int $teacherid The teacher whose feedback we're getting
	 * @return string $fpaths Comma-separated list of URLs to the feedback files
	 */
	function get_feedback_file_paths($stuid, $teacherid=-1) {
		global $CFG;
		$dir = $this->file_area_name($stuid) . '/feedback';
		$basedir = $this->file_area($stuid) . '/feedback';

		if ($teacherid != -1) {
			$dir .= $teacherid;
			$basedir .= $teacherid;
		}

		$fpaths = array();
		if ($files = get_directory_list($basedir)) {
			foreach ($files as $fname) {
				$fpaths[] =  $CFG->wwwroot . '/file.php/' . $dir . '/' . $fname;
			}
		}
		$fpaths = implode(',', $fpaths);

		return $fpaths;
	}






    function view_upload_form() {
        global $CFG;
        $struploadafile = get_string("uploadafile");

        $maxbytes = $this->assignment->maxbytes == 0 ? $this->course->maxbytes : $this->assignment->maxbytes;
        $strmaxsize = get_string('maxsize', '', display_size($maxbytes));

        echo '<div style="text-align:center">';

		echo "<p>".get_string('pleaserecordaudio', 'assignment')."</p>";

		// pull in the opening <object> and <embed> tags
        include("type/audio/revA.php");

        echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
        echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
        echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
        echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
        echo "\t\tid=\"" . $this->cm->id . "\"\n"; 
        echo "\t\tuploadtarget=\"" . $CFG->wwwroot . "/mod/assignment/upload.php\"\n"; 
        echo "\t\tuploadhost=\"" . $_SERVER['HTTP_HOST'] . "\"\n"; 
        echo "\t\tuploadpath=\"".preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME'])."upload.php\"\n"; 
        echo "\t\tuploadparams=\"id=" . $this->cm->id . "&sesskey=" . sesskey() . "\"\n"; 
		echo "\t\tsubmitscript=\"window.location.reload();\"\n";

		// pull in closing </embed> and </object> tags, and javascript to handle prompting revWeb plugin
        readfile($CFG->wwwroot . "/mod/assignment/type/audio/revB.html");
        echo '</div>';
    }







    function upload() {

        global $CFG, $USER;

		error_log("audio-type assignment: upload function called");
	
		require_capability('mod/assignment:submit', get_context_instance(CONTEXT_MODULE, $this->cm->id));

        $this->view_header(get_string('upload'));

		if (count($_FILES) > 0) {
			error_log("audio-type assignment: temp files array includes at least one file");
		} else {
			error_log("audio-type assignment: temp files array is EMPTY");
		}

        $filecount = $this->count_user_files($USER->id);
        $submission = $this->get_submission($USER->id);
        if ($this->isopen() && (!$filecount || $this->assignment->resubmit || !$submission->timemarked)) {
            if ($submission = $this->get_submission($USER->id)) {
                //TODO: change later to ">= 0", to prevent resubmission when graded 0
                if (($submission->grade > 0) and !$this->assignment->resubmit) {
                    notify(get_string('alreadygraded', 'assignment'));
                }
            }

            $dir = $this->file_area_name($USER->id);

			// pull the feedback directory as well, so that we can clear it on a successful resubmission
			$fbdir = $this->feedback_area($USER->id);

            require_once($CFG->dirroot.'/lib/uploadlib.php');
            $um = new upload_manager('newfile',true,false,$this->course,false,$this->assignment->maxbytes);
            if ($um->process_file_uploads($dir) and confirm_sesskey()) {

				error_log("audio-type assignment: upload succeeded");

                $newfile_name = $um->get_new_filename();
                if ($submission) {
                    $submission->timemodified = time();
                    $submission->numfiles     = 1;
                    $submission->submissioncomment = addslashes($submission->submissioncomment);
                    unset($submission->data1);  // Don't need to update this.
                    unset($submission->data2);  // Don't need to update this.
                    if (update_record("assignment_submissions", $submission)) {
                        add_to_log($this->course->id, 'assignment', 'upload',
                                'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                        $submission = $this->get_submission($USER->id);
                        $this->update_grade($submission);
                        $this->email_teachers($submission);
                        print_heading(get_string('uploadedfile'));
                    } else {
                        notify(get_string("uploadfailnoupdate", "assignment"));
                    }
                } else {
                    $newsubmission = $this->prepare_new_submission($USER->id);
                    $newsubmission->timemodified = time();
                    $newsubmission->numfiles = 1;
                    if (insert_record('assignment_submissions', $newsubmission)) {
                        add_to_log($this->course->id, 'assignment', 'upload',
                                'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                        $submission = $this->get_submission($USER->id);
                        $this->update_grade($submission);
                        $this->email_teachers($newsubmission);
                        print_heading(get_string('uploadedfile'));
                    } else {
                        notify(get_string("uploadnotregistered", "assignment", $newfile_name) );
                    }
                }

				// clear out the old feedback files, if they exist
				if (is_dir($fbdir)) {
					require_once('../../lib/filelib.php');
					if (fulldelete($fbdir)) {
						error_log("Successfully wiped old feedback files");
					} else {
						error_log("Failed to wipe old feedback files. View page is gonna look mighty strange!");
					}
				} else {
					error_log("It's not a dir!");
					error_log("The feedbackdir is $fbdir");
				}

            }

        } else {
            notify(get_string("uploaderror", "assignment")); //submitting not allowed!
			error_log("audio-type assignment: upload failed");
        }

        print_continue('view.php?id='.$this->cm->id);

        $this->view_footer();
    }






    function upload_feedback($stuid) {

        global $CFG, $USER;

		error_log("audio-type assignment: upload feedback function called");
	
		require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $this->cm->id));

		if (count($_FILES) > 0) {
			error_log("audio-type assignment: temp files array includes at least one file");
		} else {
			error_log("audio-type assignment: temp files array is EMPTY");
		}

		//$dir = $this->feedback_area_name($stuid, $USER->id);
		$dir = $this->feedback_area_name($stuid);

		require_once($CFG->dirroot.'/lib/uploadlib.php');
		$um = new upload_manager('',true,false,$this->course,false,$this->assignment->maxbytes);
		if ($um->process_file_uploads($dir) && confirm_sesskey()) {
			error_log("audio-type assignment: feedback upload succeeded");
		} else {
			error_log("audio-type assignment: feedback upload failed");
		}

    }




	function feedback_area_name($stuid, $teacherid=-1) {
		$fbdir = $this->file_area_name($stuid);
		$fbdir .= '/feedback';
		if ($teacherid != -1) {
			$fbdir .= '/' . $teacherid;
		}
		return $fbdir;
	}

	function feedback_area($stuid, $teacherid=-1) {
		global $CFG;
		return $CFG->dataroot . '/' . $this->feedback_area_name($stuid, $teacherid);
	}








    function setup_elements(&$mform) {
        global $CFG, $COURSE;

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'resubmit', get_string("allowresubmit", "assignment"), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit', get_string('allowresubmit', 'assignment'), 'assignment'));
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string("emailteachers", "assignment"), $ynoptions);
        $mform->setHelpButton('emailteachers', array('emailteachers', get_string('emailteachers', 'assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

    }








    function print_user_files($userid=0, $return=false) {
        global $CFG, $USER;

		echo '<script type="text/javascript">window.resizeTo(925,750);</script>';

        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }

        $filearea = $this->file_area_name($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir, '', false)) {
                require_once($CFG->libdir.'/filelib.php');

				// submission uploading guarantees that there will only be 1 file for each student's submission (the file gets
				// overwritten with each new resubmission), and there'd better be a file if they've submitted
				if (count($files) != 1) { error('Wrong number of files found for an audio-type assignment.'); }
				
				// so pull that one file
				$file = array_pop($files);
				
				// build the path to it
				$ffurl = get_file_url("$filearea/$file");

				///////////////////////////////////////////////////////
				// Revlet embed code

				// print instructions for using the feedback recorder revlet
				$output .= '<p>'.get_string('feedbackinstructions', 'assignment').'</p>';

				// pull in the start script
				// here and below (pulling in revB), using output buffering functions ob_start and ob_get_clean to dump the include
				// contents into a local variable, not directly to the page
				ob_start();
				include('feedback/recorder/revA.php');
				$output .= ob_get_clean();
				
				// print out authentication vars
				$output .= "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
				$output .= "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
				$output .= "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
				$output .= "\t\tsesskey=\"" . sesskey() . "\"\n"; 
				// print out revlet vars
				$output .= "\t\tid=\"" . $this->cm->id . "\"\n";
				$output .= "\t\tuserid=\"" . $USER->id . "\"\n";
				$output .= "\t\tsubmitscript=\"document.forms['submitform'].elements['submit'].click();\"\n";
				// print out relevant file paths
				$output .= "\t\tstudentfile=\"$ffurl\"\n"; //path to the student file to be downloaded
				//$output .= "\t\tfeedbackfnames=\"".$this->get_feedback_file_paths($userid, $USER->id)."\"\n";
				$output .= "\t\tfeedbackfnames=\"".$this->get_feedback_file_paths($userid)."\"\n";
				// print out uploading information				
				$output .= "\t\tuploadtarget=\"" . $CFG->wwwroot . "/mod/assignment/upload.php\"\n"; 
				$output .= "\t\tuploadhost=\"" . $_SERVER['HTTP_HOST'] . "\"\n";
				$output .= "\t\tuploadpath=\"" . preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME'])."upload.php\"\n";
				$output .= "\t\tuploadparams=\"id=" . $this->cm->id . "&sesskey=" . sesskey() . "&feedback=1&stuid=$userid\"\n"; 

				// pull in end script
				ob_start();
				readfile($CFG->wwwroot.'/mod/assignment/feedback/recorder/revB.html');
				$output .= ob_get_clean();

				// End revlet embed code
				///////////////////////////////////////////////////////
            }
        }

        $output = '<br /><div class="files">'.$output.'</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    }




}

?>
