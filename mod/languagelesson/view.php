<?php // $Id: view.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * This page prints the main interface for a languagelesson instance
 *
 * @package languagelesson
 * @category mod 
 * @version $Id: view.php 677 2011-10-12 18:38:45Z griffisd $
 * @author $Author: griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */




///////////////////////////////////////////////////////////
// BASIC SETUP
///////////////////////////////////////////////////////////

    require_once('../../config.php');
	
    require_once($CFG->dirroot.'/mod/languagelesson/locallib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/lib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/pagelib.php');
    
	require_once($CFG->libdir.'/blocklib.php');
	require_once($CFG->libdir.'/accesslib.php');
	
    $id      = required_param('id', PARAM_INT);             // Course Module ID
    $pageid  = optional_param('pageid', NULL, PARAM_INT);   // Lesson Page ID
    $edit    = optional_param('edit', -1, PARAM_BOOL);
    
    list($cm, $course, $lesson) = languagelesson_get_basics($id);

    require_login($course->id, false, $cm);
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

	/// check if reviewing
	$reviewing = optional_param('reviewing', 0, PARAM_INT);

	/// fetch feedback variables, if they exist
	$showfeedback = optional_param('showfeedback', 0, PARAM_INT);
	$aid = optional_param('aid', 0, PARAM_INT);
	$atext = optional_param('atext', '', PARAM_RAW);
	$saved_nextpageid = optional_param('nextpageid', 0, PARAM_INT);

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////





///////////////////////////////////////////////////////////
// CHECK LESSON USABILITY FOR STUDENT
///////////////////////////////////////////////////////////

/// Check these for students only
///     Check lesson availability
///     Check dependencies

	/// if this is a student
	if (!has_capability('mod/languagelesson:manage', $context)) {

	
	//////////////////////////////////////////////////////
	/// check if the lesson is available at all
        if (($lesson->available != 0 and time() < $lesson->available) or
            ($lesson->deadline != 0 and time() > $lesson->deadline)) {  // Deadline restrictions
            
			/// if it's past the deadline
			if ($lesson->deadline != 0 and time() > $lesson->deadline) {
                $message = get_string('lessonclosed', 'languagelesson', userdate($lesson->deadline));
            }
			/// otherwise, the lesson's just straightup closed
			else {
                $message = get_string('lessonopen', 'languagelesson', userdate($lesson->available));
            }
            
			/// and print the "it's closed!" message
            languagelesson_print_header($cm, $course, $lesson);
            print_simple_box_start('center');
            echo '<div style="text-align:center;">';
            echo '<p>'.$message.'</p>';
			echo '<div class="lessonbutton standardbutton" style="padding: 5px;"><a href="'.$CFG->wwwroot.'/course/view.php?id='.
				$course->id .'">'. get_string('returnto', 'languagelesson', format_string($course->fullname, true)) .'</a></div>';
            echo '</div>';
            print_simple_box_end();
            print_footer($course);
            exit();
	/// </check lesson availability>
	//////////////////////////////////////////////////////
		
		
		
		
		
	//////////////////////////////////////////////////////
	/// check lesson dependency
        } else if ($lesson->dependency) { // check for dependencies
            if ($dependentlesson = get_record('languagelesson', 'id', $lesson->dependency)) {
                // lesson exists, so we can proceed            
                $conditions = unserialize($lesson->conditions);
                // assume false for all
                $timespent = false;
                $completed = false;
                $gradebetterthan = false;
                // check for the timespent condition
                if ($conditions->timespent) {
					if ($attempttimes = get_records_select('languagelesson_timer', "userid = $USER->id AND lessonid =
								$dependentlesson->id")) {
                        // go through all the times and test to see if any of them satisfy the condition
                        foreach($attempttimes as $attempttime) {
                            $duration = $attempttime->lessontime - $attempttime->starttime;
                            if ($conditions->timespent < $duration/60) {
                                $timespent = true;
                            }
                        }
                    } 
                } else {
                    $timespent = true; // there isn't one set
                }

                // check for the gradebetterthan condition
                if($conditions->gradebetterthan) {
					if ($studentgrades = get_records_select('languagelesson_grades', "userid = $USER->id AND lessonid =
								$dependentlesson->id")) {
                        // go through all the grades and test to see if any of them satisfy the condition
                        foreach($studentgrades as $studentgrade) {
                            if ($studentgrade->grade >= $conditions->gradebetterthan) {
                                $gradebetterthan = true;
                            }
                        }
                    }
                } else {
                    $gradebetterthan = true; // there isn't one set
                }

                // check for the completed condition
                if ($conditions->completed) {
                    if (count_records('languagelesson_grades', 'userid', $USER->id, 'lessonid', $dependentlesson->id)) {
                        $completed = true;
                    }
                } else {
                    $completed = true; // not set
                }

                $errors = array();
                // collect all of our error statements
                if (!$timespent) {
                    $errors[] = get_string('timespenterror', 'languagelesson', $conditions->timespent);
                }
                if (!$completed) {
                    $errors[] = get_string('completederror', 'languagelesson');
                }
                if (!$gradebetterthan) {
                    $errors[] = get_string('gradebetterthanerror', 'languagelesson', $conditions->gradebetterthan);
                }
                if (!empty($errors)) {  // print out the errors if any
                    languagelesson_print_header($cm, $course, $lesson);
                    echo '<p>';
                    print_simple_box_start('center');
                    print_string('completethefollowingconditions', 'languagelesson', $dependentlesson->name);
                    echo '<p style="text-align:center;">'.implode('<br />'.get_string('and', 'languagelesson').'<br />', $errors).'</p>';
                    print_simple_box_end();
                    echo '</p>';
                    print_footer($course);
                    exit();
                } // </if has errors>
				
            } // </if the dependency exists>
			
		} // </if the lesson has a dependency>
		
	/// </check lesson dependency>
	//////////////////////////////////////////////////////
		
		
    } // </if this is a student>
	
	
	
	
// </check lesson usability for student> //////////////////
///////////////////////////////////////////////////////////
	
	
	
	
	
	
	
	
    // set up some general variables
    $path = $CFG->wwwroot .'/course';

    // this is called if a student leaves during a lesson
    if($pageid == LL_UNSEENBRANCHPAGE) {
        $pageid = languagelesson_unseen_question_jump($lesson->id, $USER->id, $pageid);
    }
    
	
	
	
	
	
	
	
///////////////////////////////////////////////////////////
// IF THE PAGEID IS EMPTY (COMING FROM COURSE PAGE)
///////////////////////////////////////////////////////////
	
	
	if (empty($pageid)) {
		
		
    /// make sure there are pages to view
        if (!get_field('languagelesson_pages', 'id', 'lessonid', $lesson->id, 'prevpageid', 0)) {
            if (!has_capability('mod/languagelesson:manage', $context)) {
				languagelesson_set_message(get_string('lessonnotready', 'languagelesson', $course->teacher)); // a nice message to the
																											//student
            } else {
                if (!count_records('languagelesson_pages', 'lessonid', $lesson->id)) {
                    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id"); // no pages - redirect to add pages
                } else {
                    languagelesson_set_message(get_string('lessonpagelinkingbroken', 'languagelesson'));  // ok, bad mojo
                }
            }
        }
		
		
	/// log that this lesson got started   
        add_to_log($course->id, 'lesson', 'start', 'view.php?id='. $cm->id, $lesson->id, $cm->id);
        
		
		
	/// check to see if this user has completed the lesson before
		$hascompleted = false;
        // if no pageid given see if the lesson has been started
        if (!has_capability('mod/languagelesson:manage', $context) && languagelesson_is_lesson_complete($lesson->id, $USER->id)) {
			$hascompleted = true;
        }
		
		
		
	/// whether or not they've completed it before, we'll need the list of most recent
	/// attempts on the questions in this lesson, so pull it
		$attempts = languagelesson_get_most_recent_attempts($lesson->id, $USER->id);
		
		
		
		
		
		
	//////////////////////////////////////////////////////
	// HANDLE "YOU HAVE SEEN" PAGE
	// @youhaveseen@
	//////////////////////////////////////////////////////
	
	/// if they have NOT completed the lesson before, BUT they have recorded attempts,
	/// show them the "You have seen more than one page of this lesson before, do you want
	/// to start at the next page" message
	/// ALSO, don't show this if the user is a teacher
		if (!$hascompleted && $attempts && !has_capability('mod/languagelesson:manage', $context)) {
			
		/// find the page to jump to if the user continues their attempt
			
			/// pull the most recent attempt
            $lastattempt = end($attempts);
			/// then, since end forces $attempts' pointer to the last item, reset it to the first
			reset($attempts);
			
			/// now pull the jumpto value of the most recent attempt's answer
			$jumpto = get_field('languagelesson_answers', 'jumpto', 'id', $lastattempt->answerid);
			/// and convert the jumpto to a proper page id
			if ($jumpto == 0) {
				// they got it wrong, so jump to that page
				$lastpageseen = $lastattempt->pageid;
			}
			else if ($jumpto == LL_NEXTPAGE) {
				// they got it right, so jump to the next page
				if (!$lastpageseen = get_field('languagelesson_pages', 'nextpageid', 'id', 
							$lastattempt->pageid)) {
					// next page was 0, so go to end of lesson
					$lastpageseen = LL_EOL;
				}
			} else {
				// strange jumpto value, so just feed it straight in
				$lastpageseen = $jumpto;
			}
				
			/// now check the most recently-seen branch table, and if it was seen more
			/// recently than the above attempt, jump to it instead
			if ($recentBranchTable = languagelesson_get_last_branch_table_seen($lesson->id, $USER->id)) {
				if ($recentBranchTable->timeseen > $lastattempt->timeseen) {
					$lastpageseen = $recentBranchTable->pageid;
				}
			}
			
			
		/// get the first page of the lesson
            if (!$firstpageid = get_field('languagelesson_pages', 'id', 'lessonid', $lesson->id,
                        'prevpageid', 0)) {
                error('Navigation: first page not found');
            }
			
		/// print the page header
            languagelesson_print_header($cm, $course, $lesson);
			
		/// if the lesson was timed, give them the restart option as relevant
            if ($lesson->timed) {
            	
				if ($lesson->type != LL_TYPE_TEST) {
					print_simple_box('<p style="text-align:center;">'. get_string('leftduringtimed', 'languagelesson') .'</p>',
							'center');
                    echo '<div style="text-align:center;" class="lessonbutton standardbutton">'.
                              '<a href="view.php?id='.$cm->id.'&amp;pageid='.$firstpageid.'&amp;startlastseen=no">'.
                                get_string('continue', 'languagelesson').'</a></div>';
                } else {
                    print_simple_box_start('center');
                    echo '<div style="text-align:center;">';
                    echo get_string('leftduringtimednoretake', 'languagelesson');
					echo '<br /><br /><div class="lessonbutton standardbutton"><a href="../../course/view.php?id='. $course->id .'">'.
						get_string('returntocourse', 'languagelesson') .'</a></div>';
                    echo '</div>';
                    print_simple_box_end();
                }   
            }
			
			
		/// if it wasn't, display the "You have seen..." page
			else {
                print_simple_box("<p style=\"text-align:center;\">".get_string('youhaveseen','languagelesson').'</p>',
                        "center");
                
                echo '<div style="text-align:center;">';
                echo '<span class="lessonbutton standardbutton">'.
                        '<a href="view.php?id='.$cm->id.'&amp;pageid='.$lastpageseen.'&amp;startlastseen=yes">'.
                        get_string('yes').'</a></span>&nbsp;&nbsp;&nbsp;';
                echo '<span class="lessonbutton standardbutton">'.
                        '<a href="view.php?id='.$cm->id.'&amp;pageid='.$firstpageid.'&amp;startlastseen=no">'.
                        get_string('no').'</a></div>';
                echo '</span>';
            }
			
			
		/// print the footer and quit
            print_footer($course);
            exit();
        }
	
	// </handle "You have seen" page>
	//////////////////////////////////////////////////////
        
		
		
		
		
	//////////////////////////////////////////////////////
	// HANDLE "OLD GRADE" PAGE
	// @oldgrade@
	//////////////////////////////////////////////////////
		
		if ($lesson->type != LL_TYPE_PRACTICE
			&& $hascompleted) {
			
			$grade = get_record('languagelesson_grades', 'lessonid', $lesson->id, 'userid', $USER->id);
			
			languagelesson_print_header($cm, $course, $lesson, 'view');
			print_simple_box_start('center');
			echo "<div style=\"text-align:center;\">";
			
			print_heading(get_string('oldgradeheader', 'languagelesson'));
			
		/// pull the ID of the first page in the lesson's order
			$firstpageID = get_field_select('languagelesson_pages', 'id', "lessonid = $lesson->id
																			and prevpageid = 0");
			
			if ($course->showgrades) {
				$a->grade = $grade->grade;
				echo '<p>'.get_string('oldgradethisisyourgrade', 'languagelesson', $a).'</p>';

				if (count_records('languagelesson_manattempts', 'lessonid', $lesson->id, 'userid', $USER->id, 'graded', 0)) {
					echo '<p>'.get_string('oldgradehasungraded', 'languagelesson').'</p>';
				}
			}
			
			if ($lesson->type == LL_TYPE_ASSIGNMENT) {
				echo get_string('oldgradeassignmentmessage', 'languagelesson');
				$buttontext = get_string('oldgradeassignmentbutton', 'languagelesson');
			} else {
				echo get_string('oldgradetestmessage', 'languagelesson');
				$buttontext = get_string('oldgradetestbutton', 'languagelesson');
			}
			
		/// check if feedback has been posted; if so, tell student it has
			if ($feedbacks = get_records_select('languagelesson_feedback', "lessonid=$lesson->id and userid=$USER->id")) {
				
				echo '<br /><br /><br />';
				print_simple_box_start('center');
				//echo '<div>';
				echo get_string('oldgradeyouhavefeedback', 'languagelesson');
				
			/// build the list of which pages have feedback posted
				$distinctpageIDs = array();
				foreach ($feedbacks as $feedback) {
					if (!in_array($feedback->pageid, $distinctpageIDs)) {
						$distinctpageIDs[] = $feedback->pageid;
					}
				}
				
			/// then use these to print out links to them
				foreach ($distinctpageIDs as $pid) {
					$thispage = get_record('languagelesson_pages', 'id', $pid);
					echo "<a
						href=\"$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$thispage->id\">$thispage->title</a><br
						/>";
				}
				
				print_simple_box_end();
				//echo '</div>';
				
			}
			
			echo "<br /><br /><div class=\"lessonbutton standardbutton\"><a
				href=\"view.php?id=$cm->id&amp;pageid=$firstpageID".
				(($lesson->type == LL_TYPE_TEST) ? "&amp;reviewing=1" : '') . "\">".$buttontext.'</a></div>';
			
			echo "<br /><br /><div class=\"lessonbutton standardbutton\"><a
				href=\"../../course/view.php?id=$course->id\">".get_string('returntocourse', 'languagelesson').'</a></div>';
			echo "</div>";
			print_simple_box_end();
			print_footer($course);
			exit();
        }
		
		
	// </handle "old grade" page>
	//////////////////////////////////////////////////////
		
		
		
		
	//////////////////////////////////////////////////////
	// START THE LESSON
	//////////////////////////////////////////////////////
        
	/// if we got here, the user is just starting the lesson
	/// for the first time, so record that and push them to the first page
	
		// get the first page
        if (!$pageid = get_field('languagelesson_pages', 'id', 'lessonid', $lesson->id, 'prevpageid', 0)) {
                error('Navigation: first page not found');
        }
        
		
		// save the timer record that they started
        if(!isset($USER->startlesson[$lesson->id]) && !has_capability('mod/languagelesson:manage', $context)) {
			
            $USER->startlesson[$lesson->id] = true;
            $startlesson = new stdClass;
            $startlesson->lessonid = $lesson->id;
            $startlesson->userid = $USER->id;
            $startlesson->starttime = time();
            $startlesson->lessontime = time();
            
            if (!insert_record('languagelesson_timer', $startlesson)) {
                error('Error: could not insert row into lesson_timer table');
            }
            
			if ($lesson->timed) {
                languagelesson_set_message(get_string('maxtimewarning', 'languagelesson', $lesson->maxtime), 'center');
            }
        }
		
	// </start the lesson> ///////////////////////////////
	//////////////////////////////////////////////////////
		
	}
	

// </if the pageID is empty> //////////////////////////////	
///////////////////////////////////////////////////////////
	
	
	
	
	
	
	
	
///////////////////////////////////////////////////////////
// IF THE PAGEID IS ANYTHING OTHER THAN THE EOL
///////////////////////////////////////////////////////////
	
	
	if ($pageid != LL_EOL) {
		
	/// log that they hit the view page
        add_to_log($course->id, 'lesson', 'view', 'view.php?id='. $cm->id, $pageid, $cm->id);
        
		
		
	//////////////////////////////////////////////////////
	// HANDLE INVISIBLE STRUCTURAL PAGE TYPES
	//////////////////////////////////////////////////////
		
	/// pull the page record
        if (!$page = get_record('languagelesson_pages', 'id', $pageid)) {
            error('Navigation: the page record not found');
        }
		

		////////////
		// HACK  ///
		// @cache //
		/*if ($page->qtype == LL_AUDIO || $page->qtype == LL_VIDEO) {
			header('Cache: no-cache');
			header('Cache-Control: no-cache, must-revalidate');
		}*/
		// END HACK //
		//////////////
		
		
	/// handle an opening cluster page
		if ($page->qtype == LL_CLUSTER) {  //this only gets called when a user starts up a new lesson and the first page is a cluster
											//page
            if (!has_capability('mod/languagelesson:manage', $context)) {
			/// it's a student, so jump within the cluster
                // get page ID to jump to
                $pageid = languagelesson_cluster_jump($lesson->id, $USER->id, $pageid);
                // get page record for that ID
                if (!$page = get_record('languagelesson_pages', 'id', $pageid)) {
                    error('Navigation: the page record not found');
                }
				// relog the viewing of the page
                add_to_log($course->id, 'lesson', 'view', 'view.php?id='. $cm->id, $pageid, $cm->id);
            } else {
			/// it's an editing user, so just move on to the next page
                // get the next page
                $pageid = $page->nextpageid;
                if (!$page = get_record('languagelesson_pages', 'id', $pageid)) {
                    error('Navigation: the page record not found');
                }
            }
			
			
	/// handle the end of a cluster
        } elseif ($page->qtype == LL_ENDOFCLUSTER) {
			/// move on to the EOL or the next page as appropriate
            if ($page->nextpageid == 0) {
                $nextpageid = LL_EOL;
            } else {
                $nextpageid = $page->nextpageid;
            }
            redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$nextpageid");
			
			
	/// handle the end of a branch
        } else if ($page->qtype == LL_ENDOFBRANCH) {
            
			/*
			// languagelesson_seenbranches
			
			if ($branch = get_record('languagelesson_seenbranches', 'pageid', $page->id)) {
				
				/// random branch jump
				if ($branch->jumpto == LL_RANDOMBRANCH) {
					$jumpto = languagelesson_unseen_branch_jump($lesson->id, $USER->id);
				}
				//...etc...
			} else {
				error('Navigation: No branch record for EOB');
			}
			*/
			
			///*
			if ($answers = get_records('languagelesson_answers', 'pageid', $page->id, 'id')) {
                // print_heading(get_string('endofbranch', 'languagelesson'));
                foreach ($answers as $answer) {
                    // just need the first answer
                    if ($answer->jumpto == LL_RANDOMBRANCH) {
                        $answer->jumpto = languagelesson_unseen_branch_jump($lesson->id, $USER->id);
                    } elseif ($answer->jumpto == LL_CLUSTERJUMP) {
                        if (!has_capability('mod/languagelesson:manage', $context)) {
                            $answer->jumpto = languagelesson_cluster_jump($lesson->id, $USER->id, $pageid);
                        } else {
                            if ($page->nextpageid == 0) {  
                                $answer->jumpto = LL_EOL;
                            } else {
                                $answer->jumpto = $page->nextpageid;
                            }
                        }
                    } else if ($answer->jumpto == LL_NEXTPAGE) {
                        if ($page->nextpageid == 0) {  
                            $answer->jumpto = LL_EOL;
                        } else {
                            $answer->jumpto = $page->nextpageid;
                        }
                    } else if ($answer->jumpto == 0) {
                        $answer->jumpto = $page->id;
                    } else if ($answer->jumpto == LL_PREVIOUSPAGE) {
                        $answer->jumpto = $page->prevpageid;                            
                    }
                    redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$answer->jumpto");
                    break;
                } 
            } else {
                error('Navigation: No answers on EOB');
            }
			//*/
        }
		
	// </handle invisible structural page types> /////////
	//////////////////////////////////////////////////////
        
		
		
		
        
	//////////////////////////////////////////////////////
	// HANDLE LESSON MESSAGES
	//////////////////////////////////////////////////////
		
        // This is where several messages (usually warnings) are displayed
        // all of this is displayed above the actual page
        
	/// get the student's timer information
        $timer = new stdClass;
        if(!has_capability('mod/languagelesson:manage', $context)) {
            if (!$timer = get_records_select('languagelesson_timer', "lessonid = $lesson->id AND userid = $USER->id", 'starttime')) {
				//error('Error: could not find records'); ///TODO: find what causes this error, and get rid of the causes!  Shouldn't
														//just ignore the error...
                languagelesson_insert_bs_timer($lesson->id, $USER->id);
                header('Location: '.$CFG->wwwroot.'/mod/languagelesson/view.php?id='.$cm->id
					   . '&amp;pageid='.$page->id);
                exit();
            } else {
                $timer = array_pop($timer); // this will get the latest start time record
            }
        }
		
		
	/// handle updating the time if coming from the "You have seen..." page
        $startlastseen = optional_param('startlastseen', '', PARAM_ALPHA);
        if ($startlastseen == 'yes') {  // continue a previous test, need to update the clock  (think this option is disabled atm)
            $timer->starttime = time() - ($timer->lessontime - $timer->starttime);
            $timer->lessontime = time();
        } else if ($startlastseen == 'no') {  // starting over
            // starting over, so reset the clock
            $timer->starttime = time();
            $timer->lessontime = time();
        }
            
			
    /// for timed lessons, display the clock
        if ($lesson->timed) {
            if(has_capability('mod/languagelesson:manage', $context)) {
                languagelesson_set_message(get_string('teachertimerwarning', 'languagelesson'));
            } else {
                $timeleft = ($timer->starttime + $lesson->maxtime * 60) - time();
				
                if ($timeleft <= 0) {
                    // Out of time
                    languagelesson_set_message(get_string('eolstudentoutoftime', 'languagelesson'));
                    redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=".LL_EOL."&amp;outoftime=normal");
                    die; // Shouldn't be reached, but make sure
                } else if ($timeleft < 60) {
                    // One minute warning
                    languagelesson_set_message(get_string('studentoneminwarning', 'languagelesson'));
                }
            }
        }
		
	/// and update the clock
        if (!has_capability('mod/languagelesson:manage', $context)) {
            $timer->lessontime = time();
            if (!update_record('languagelesson_timer', $timer)) {
                error('Error: could not update lesson_timer table');
            }
        }
		
		
	///  Print the warning msg for teachers to inform them that cluster and unseen does not work while logged in as a teacher
        if(has_capability('mod/languagelesson:manage', $context)) {
            if (languagelesson_display_teacher_warning($lesson->id)) {
                $warningvars->cluster = get_string('clusterjump', 'languagelesson');
                $warningvars->unseen = get_string('unseenpageinbranch', 'languagelesson');
                languagelesson_set_message(get_string('teacherjumpwarning', 'languagelesson', $warningvars));
            }
			// and print the message warning that no attempts will be recorded
			languagelesson_set_message(get_string('teacherrecordswarning', 'languagelesson'));
        }
		
		
	/// handle attempt warning flags
	///   lastattemptwarning flags if user should see the "This is your last attempt
	///			on this question" message
	///   reviewing flags if user cannot change answer anymore
	/// NOTE that the associated warning messages are NOT printed here, but below, inside the page
		$lastattemptwarning = false;
		$nomoreattempts = false;
		if ($lesson->maxattempts > 0 &&  // if maxattempts=0, attempts are unlimited
				!has_capability('mod/languagelesson:manage', $context)) { // show this only to students
			/// pull the number of times they've already attempted this page
			$numprevattempts = count_records('languagelesson_attempts', 'lessonid', $lesson->id,
											 'userid', $USER->id, 'pageid', $page->id);
			/// if this is the "maxattempts"th time, flag to warn them they've only got one more shot
			/// IGNORE the warning if this lesson only has 1 attempt on each question
			if ($lesson->maxattempts > 1 && $numprevattempts == $lesson->maxattempts-1) {
				$lastattemptwarning = true;
			/// if they've already maxed it, flag to tell them they can't do anything more
			} else if ($numprevattempts >= $lesson->maxattempts) {
				$nomoreattempts = true;
			}
		}
		/// if reviewing, don't need to display the other messages 
		if ($reviewing) {
			$nomoreattempts = false;
			$lastattemptwarning = false;
		}
		
		
	// </handle lesson messages> /////////////////////////
	//////////////////////////////////////////////////////
		
		
		
		
		
		
		
		
	//////////////////////////////////////////////////////
	// START ACTUALLY PRINTING THE LESSON PAGE
	//////////////////////////////////////////////////////
		
	/// basic page setup
        $PAGE = page_create_instance($lesson->id);
        $PAGE->set_lessonpageid($page->id);
        $pageblocks = blocks_setup($PAGE);
		
        $leftcolumnwidth  = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);
        $rightcolumnwidth = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 210);

        if (($edit != -1) and $PAGE->user_allowed_editing()) {
            $USER->editing = $edit;
        }

    /// Print the page header
        $PAGE->print_header();
		
	/// print attempt warnings (as flagged above)
        if ($lastattemptwarning) {
			print_heading(get_string('lastattempt', 'languagelesson'));
        } else if ($nomoreattempts) {
			print_heading(get_string('nomoreattempts', 'languagelesson'));
		} else if ($reviewing) {
			print_heading(get_string('reviewingtest', 'languagelesson'));
		}
		
		
    /// calculate and print the ongoing score
        if ($lesson->showongoingscore and !empty($pageid)) {
            languagelesson_print_ongoing_score($lesson);
        }
		
		
	/// print out the start of the page template
		?>
		<table id="layout-table" cellpadding="0" cellspacing="0">
			<tr>
				<!-- First Column -->
				<?php if (languagelesson_blocks_have_content($lesson, $pageblocks, BLOCK_POS_LEFT)) { ?>
				<td id="left-column" style="width: <?php echo $leftcolumnwidth; ?>px;">
					<?php
						languagelesson_print_menu_block($cm->id, $lesson);

						if (!empty($CFG->showblocksonmodpages)) {
							if ((blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing())) {
								blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
							}
						}
					?>
				</td>
				<?php } ?>
				<!-- Start main column -->
				<td id="middle-column" align="center">

					<?php if ($lesson->displayleft) { ?>

					<a name="maincontent" id="maincontent" title="<?php print_string('anchortitle', 'lesson') ?>"></a>

					<?php }



    /// print the page's contents (description)
        if ($page->qtype == LL_BRANCHTABLE) {
            print_heading(format_string($page->title));
        } else {
            $options = new stdClass;
            $options->noclean = true;
			
		/// print out the instructions on how to complete the question directly above the question text
			$textid = get_field('languagelesson_qtypes', 'textid', 'id', $page->qtype);
			////////////////////////////////////////
			// HACK ////////////////////////////////
			if ($page->qtype == LL_MULTICHOICE) {
				if (! $answers = get_records('languagelesson_answers', 'pageid', $page->id)) {
					$textid = 'LL_DESCRIPTION';
				}
			}
			// END HACK ////////////////////////////
			////////////////////////////////////////
			// check if this is a multiple choice with multiple correct answers; if so, tag it appropriately
			if ($page->qtype == LL_MULTICHOICE && $page->qoption) { $textid .= 'multiple'; }
			echo '<div class="instructions">'.
				get_string("{$textid}instructions", 'languagelesson').
				'</div>';
			
		/// print the contents (the question prompt)
		/// special case for LL_CLOZE: the prompt should display as containing the answer fields, so it's printed below
			if ($page->qtype != LL_CLOZE) {
				print_simple_box('<div class="contents">'.
								format_text(stripslashes($page->contents), FORMAT_MOODLE, $options).
								'</div>', 'center');
			}
        }
        
		
	// </start printing lesson page> /////////////////////
	//////////////////////////////////////////////////////
		
		
		
		
		
		
	//////////////////////////////////////////////////////
	// HANDLE PRINTING ANSWERS
	//////////////////////////////////////////////////////
		
		
	/// if there is an old attempt for this question by this user, pull it so we can pre-select
	/// their old answer for them, and flag that we need to do so
		$showOldAttempt = false;
		if (($lesson->showoldanswer || $nomoreattempts)
			&& $oldAttempt = languagelesson_get_most_recent_attempt_on($page->id, $USER->id)) {
			
			$showOldAttempt = true;
		}
		
		// if this a teacher, there's no attempt record stored, so use GET variables provided by continue.php to construct the
		// oldAttempt object for printing feedback
		else if (has_capability('mod/languagelesson:manage', $context) && $lesson->showoldanswer) {
			$oldAttempt = new stdClass();
			$oldAttempt->answerid = $aid;

			// if there is an answer for the $aid, use its data; otherwise, it was a wrong shortanswer/numerical (non hard-limited)
			// type, so use the GET $atext variable to store its data
			$answer = get_record('languagelesson_answers', 'id', $aid);
			if ($answer && $answer->score > 0) {
				$oldAttempt->correct = true;
				$oldAttempt->useranswer = $answer->answer;
				$showOldAttempt = true;
			} else if ($atext) {
				$oldAttempt->correct = false;
				$oldAttempt->useranswer = $atext;
				$showOldAttempt = true;
			}
			// if neither $answer nor $atext exists, that means they're just viewing the question, they haven't attempted it, so
			// display nothing

		}
	
	/// if we are showing old attempts and displaying correct/incorrect info in the left menu, mark the image to show the user next to
	//their previous answer
		$img = '';
		if ($showOldAttempt && $lesson->contextcolors && isset($oldAttempt)) {
			$img = '<img src="'.$CFG->wwwroot.
				get_string( (($oldAttempt->correct) ? 'iconsrccorrect' : 'iconsrcwrong' ) , 'languagelesson').
				'" width="16" height="16" alt="' . 
				(($oldAttempt->correct) ? 'Correct' : 'Incorrect') . '" />';
		}
     
	    
	/// get the answers in a set order, the id order
        if ($answers = get_records("languagelesson_answers", "pageid", $page->id, "id")) {
            if ($page->qtype != LL_BRANCHTABLE) {  // To fix XHTML problem (BT have their own forms)
                echo "<form id=\"answerform\" method =\"post\" action=\"lesson.php\" autocomplete=\"off\">";
                echo '<fieldset class="invisiblefieldset">';
                echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />";
                echo "<input type=\"hidden\" name=\"action\" value=\"continue\" />";
                echo "<input type=\"hidden\" name=\"pageid\" value=\"$pageid\" />";
                echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />";
            }
            // default format text options
            $options = new stdClass;
            $options->para = false; // no <p></p>
            $options->noclean = true;
            // echo "qtype is $page->qtype"; // debug
            
			
			
			
		/// handle printing answers based on page's question type	
			switch ($page->qtype) {
            
			
			
			
			
			    case LL_SHORTANSWER :
                //case LL_NUMERICAL :
					print_simple_box_start("center");
					echo '<table id="answertable">';
					if ($showOldAttempt) {
						$value = 'value="'.s($oldAttempt->useranswer).'"';
                    } else {
                        $value = "";
                    }       
                    echo '<tr><td style="text-align:center;"><label for="answer">'.get_string('youranswer', 'languagelesson').'</label>'.
                        ": <input type=\"text\" id=\"answer\" name=\"answer\" size=\"50\" maxlength=\"200\" $value /> $img \n";
                    echo '</td></tr></table>';
                    print_simple_box_end();
				break;




				

				case LL_CLOZE :

					print_simple_box_start("center");
					echo '<div class="content">';

					$qtext = format_text(stripslashes($page->contents), FORMAT_MOODLE, $options);
					$textchunks = languagelesson_parse_cloze($qtext);

					// save the answers in an array, keyed to their order of appearance
					$keyedAnswers = languagelesson_key_cloze_answers($answers);

					// make sure we parsed correctly
					// $textchunks should be in the form of ( 'text', questionNumber, 'text', questionNumber, ... , 'text' )
					if (count($textchunks) != ((2*count($keyedAnswers)) + 1)) {
						error('Cloze parsing failed! Number of questions and answers did not match.');
					}

					// if showing the old answers, parse them
					if ($showOldAttempt && (! empty($oldAttempt->useranswer))) {
						$oldAnswers = unserialize($oldAttempt->useranswer);
					}

					foreach ($textchunks as $item) {
						// if it's a string, then it's a chunk of the question prompt, so print it out
						if (is_string($item)) {
							echo $item;
						}
						// otherwise, it's an int indicating which question should go here, so print the question input
						else {
							$answer = $keyedAnswers[$item];
							// if it's a drop-down type
							if ($answer->flags) {
								// remove the '=' marking the correct answer
								$options = preg_replace('/=/', '', $answer->answer);
								// then comma-separate it
								$options = explode(',', $options);
								// and turn it into an array to use in a drop-down menu
								$responseoptions = array();
								foreach ($options as $option) {
									$responseoptions[htmlspecialchars(trim($option))] = $option;
								}
								//choose_from_menu ($responseoptions, "response[$answer->id]", $selected);
								if ($showOldAttempt && isset($oldAnswers[$item])) {
									choose_from_menu ($responseoptions, "answer[$item]", $oldAnswers[$item]);
								} else {
									choose_from_menu ($responseoptions, "answer[$item]");
								}
							// otherwise, it's a fill in the blank, so print a blank
							} else {
								// print out the old attempt if necessary
								if ($showOldAttempt && isset($oldAnswers[$item])) {
									$value = 'value="'.$oldAnswers[$item].'"';
								} else {
									$value = '';
								}
								echo "<input type=\"text\" size=\"15\" name=\"answer[$item]\" $value />";
							}
						}
					}

					echo '</div>';
					print_simple_box_end();

				break;
				
				
				
				
				
				
			    case LL_TRUEFALSE :
					print_simple_box_start("center");
					echo '<table id="answertable">';
                    if ($lesson->shuffleanswers) { shuffle($answers); }
                    $i = 0;
                    foreach ($answers as $answer) {
                        echo '<tr><td valign="top">';
                        if ($showOldAttempt	&& $answer->id == $oldAttempt->answerid) {
                            $checked = 'checked="checked"';
							$im = $img;
                        } else {
                            $checked = '';
							$im = '';
                        } 
                        echo "<input type=\"radio\" id=\"answerid$i\" name=\"answerid\" value=\"{$answer->id}\" $checked />";
                        echo "</td><td>";
                        echo "<label for=\"answerid$i\">".format_text(trim($answer->answer), FORMAT_MOODLE, $options).'</label>';
						echo "</td><td>$im";
                        echo '</td></tr>';
                        if ($answer != end($answers)) {
                            echo "<tr><td><br /></td></tr>";                            
                        }
                        $i++;
                    }
                    echo '</table>';
                    print_simple_box_end();
				break;
                
				
				
				
				
				
				
				
				
				case LL_MULTICHOICE :
					print_simple_box_start("center");
					echo '<table id="answertable">';

                    $i = 0;
                    if ($lesson->shuffleanswers) { shuffle($answers); }

                    foreach ($answers as $answer) {
                        echo '<tr><td valign="top">';
                        if ($page->qoption) {
                            $checked = '';
							$im = '';
                            if ($showOldAttempt) {
                                $answerids = explode(",", $oldAttempt->useranswer);
                                if (in_array($answer->id, $answerids)) {
                                    $checked = ' checked="checked"';
									$im = $img;
                                } else {
                                    $checked = '';
									$im = '';
                                }
                            }
                            // more than one answer allowed 
                            echo "<input type=\"checkbox\" id=\"answerid$i\" name=\"answer[$i]\" value=\"{$answer->id}\"$checked />";
                        } else {
                            if ($showOldAttempt	&& $answer->id == $oldAttempt->answerid) {
                                $checked = ' checked="checked"';
								$im = $img;
                            } else {
                                $checked = '';
								$im = '';
                            } 
                            // only one answer allowed
                            echo "<input type=\"radio\" id=\"answerid$i\" name=\"answerid\" value=\"{$answer->id}\"$checked />";
                        }
                        echo '</td><td>';
                        echo "<label for=\"answerid$i\" >".format_text(trim($answer->answer), FORMAT_MOODLE, $options).'</label>'; 
						echo "</td><td>$im";
                        echo '</td></tr>';
                        if ($answer != end($answers)) {
                            echo '<tr><td><br /></td></tr>';
                        } 
                        $i++;
                    }
                    echo '</table>';
                    print_simple_box_end();
				break;
                    
                
				
				
				
				
				
				
				
				
				
				case LL_MATCHING :
					print_simple_box_start("center");
					echo '<table id="answertable">';

                    // don't shuffle answers (could be an option??)
                    foreach ($answers as $answer) {
                        // get all the response
                        if ($answer->response != NULL) {
                            $responses[] = trim($answer->response);
                        }
                    }
                    
                    $responseoptions = array();
                    if (!empty($responses)) {
                        if ($lesson->shuffleanswers) { shuffle($responses); }
                        $responses = array_unique($responses);                     
                        foreach ($responses as $response) {
                            $responseoptions[htmlspecialchars(trim($response))] = $response;
                        }
                    }
                    if ($showOldAttempt) {
                        $useranswers = explode(',', $oldAttempt->useranswer);
                        $t = 0;
                    }
                    foreach ($answers as $answer) {
                        if ($answer->response != NULL) {
                            echo '<tr><td align="right">';
                            echo "<b><label for=\"menuresponse[$answer->id]\">".
                                    format_text($answer->answer,FORMAT_MOODLE,$options).
                                    '</label>: </b></td><td valign="bottom">';
								
                            if ($showOldAttempt) {
								$selected = htmlspecialchars(trim($answers[$useranswers[$t]]->response));  // gets the user's previous
																											//answer
                                choose_from_menu ($responseoptions, "response[$answer->id]", $selected);
                                $t++;
                            } else {
                                choose_from_menu ($responseoptions, "response[$answer->id]");
                            }
                            echo '</td></tr>';
                            if ($answer != end($answers)) {
                                echo '<tr><td><br /></td></tr>';
                            } 
                        }
                    }
                    echo '</table>';
                    print_simple_box_end();
				break;
                
				
				
				
				
				
				
				
				
				case LL_BRANCHTABLE :                  
					print_simple_box_start("center");
					echo '<table id="branchtable">';

                    $options = new stdClass;
                    $options->para = false;
                    $buttons = array();
                    $i = 0;
                    foreach ($answers as $answer) {
                        // Each button must have its own form inorder for it to work with JavaScript turned off
                        $button  = "<form id=\"answerform$i\" method=\"post\" action=\"$CFG->wwwroot/mod/languagelesson/lesson.php\">\n".
                                   '<div>'.
                                   "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />\n".
                                   "<input type=\"hidden\" name=\"action\" value=\"continue\" />\n".
                                   "<input type=\"hidden\" name=\"pageid\" value=\"$pageid\" />\n".
                                   "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />\n".
                                   "<input type=\"hidden\" name=\"jumpto\" value=\"$answer->jumpto\" />\n".
								   languagelesson_print_submit_link(strip_tags(format_text($answer->answer, FORMAT_MOODLE, $options)),
										   "answerform$i", '', true).
                                   '</form>';
                        
                        $buttons[] = $button;
                        $i++;
                    }
                    
                /// Set the orientation
                    if ($page->layout) {
                        $orientation = 'horizontal';
                    } else {
                        $orientation = 'vertical';
                    }
                    
                    $fullbuttonhtml = "\n<div class=\"branchbuttoncontainer $orientation\">\n" .
                                      implode("\n", $buttons).
                                      "\n</div>\n";
                
					echo $fullbuttonhtml;

					echo '</table>';
                    
				break;
                
				
				
				
				
				
				
				
				case LL_ESSAY :
					$value = '';
					
					if ($attempt = languagelesson_get_most_recent_attempt_on($page->id, $USER->id)) {
						if (!$manattempt = get_record('languagelesson_manattempts', 'attemptid', $attempt->id)) {
							error('Retrieved attempt record, but failed to retrieve manual attempt record!');
						}

						// print out the submission (if we should) and any feedback that has been recorded for it
						languagelesson_print_submission_feedback_area($manattempt, $page->qtype, $showOldAttempt);
						
						// if the lesson is set to show the old attempt, plug their old submission into the WYSIWYG
						if ($showOldAttempt) {
							$value = $manattempt->essay;
						}
					}
                    
					print_simple_box_start("center");
					echo '<table id="answertable">';

                  /// use the HTML editor instead of a plain textarea, so students have
                  /// HTML capability in responses
                    $usehtmleditor = can_use_html_editor();
                    echo '<tr><td style="text-align:center;" valign="top" nowrap="nowrap">';
                    print_textarea($usehtmleditor, 15, 60, 0, 0, 'answer', $value);
                    if ($usehtmleditor) { use_html_editor('answer'); }
                    echo '</td></tr></table>';
                    
                    print_simple_box_end();
				break;
                
                
				
				
				
				
				
				
				case LL_AUDIO :
				case LL_VIDEO :
            	/// if they're running Windows, warn them the plugin might not work properly
            		$browser = get_browser($_SERVER['HTTP_USER_AGENT']);
            		if ($browser->platform == "WinXP" || $browser->platform == "WinVista" || $browser->platform == "Win7") {
						echo "<p>Our servers show that your computer is running Windows.  Please be aware that this plugin may be
							unstable on Windows, and for best results, we
            							suggest viewing this page on a Mac.</p>";
            		}
            		
            		$hassubmitted = false; //default behavior: it's a new try on a new question
				
					if ($attempt = languagelesson_get_most_recent_attempt_on($page->id, $USER->id)) {
						if (!$manattempt = get_record('languagelesson_manattempts', 'attemptid', $attempt->id)) {
							error('Failed to retrieve corresponding manual attempt record for this attempt.');
						}
            			$hassubmitted = true; //there's a stored submission, so enable continuing
            			
						// print out the submission and any feedback that has been recorded for it
						languagelesson_print_submission_feedback_area($manattempt, $page->qtype);
            		}
            		
				/// print the recording revlet in its own box
					print_simple_box_start('center');

					/// pull in the header template
					if ($page->qtype == LL_AUDIO) {
						//readfile($CFG->wwwroot . "/mod/languagelesson/runrev/audio/revA.html");
						include('runrev/audio/revA.php');
					} else {
						//readfile($CFG->wwwroot . "/mod/languagelesson/runrev/video/revA.html");
						include('runrev/video/revA.php');
					}
					
					/// print dynamic parameters
					echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
					echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
					echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
					echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
					echo "\t\tid=\"" . $lesson->id . "\"\n"; 
					echo "\t\tpageid=\"" . $pageid . "\"\n";
					echo "\t\tpageURL=\"" . languagelesson_get_current_page_url() . "\"\n";
					echo "\t\tuploadtarget=\"" . $CFG->wwwroot . "/mod/languagelesson/upload.php\"\n"; 
					echo "\t\tuploadhost=\"" . $_SERVER['HTTP_HOST'] . "\"\n";
					echo "\t\tuploadpath=\"".preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME'])."upload.php\"\n";
					echo "\t\tuploadparams=\"id=" . $cm->id . "&pageid=" . $pageid . "&userid=" . $USER->id . "&sesskey=" . sesskey() .
						"\"\n";
					echo "\t\tsubmitscript=\"document.forms['answerform'].submit();\"\n";
					
					/// pull in the ending template
					include($CFG->dirroot . "/mod/languagelesson/runrev/revB.php");
				            		
            		if (!$newpageid = get_field("languagelesson_pages", "nextpageid", "id", $pageid)) {
						// this is the last page - flag end of lesson
						$newpageid = LL_EOL;
                	}

                	print_simple_box_end();
				break;
                
            	
				
				
				
				
				
				
				
				
				
				
				default: // close the tags MDL-7861
					echo ('</table>');
					print_simple_box_end();
				break;

			}	// </switch($page->qtype)
			
	// </handle printing answers> ////////////////////////
	//////////////////////////////////////////////////////






	//////////////////////////////////////////////////////
	// HANDLE PRINTING FEEDBACK AND SUBMISSION BUTTONS  
	// @feedback@
	//////////////////////////////////////////////////////
			
		/// display feedback and print the submit/continue button
            if ($page->qtype != LL_BRANCHTABLE) { 
				
				echo '</fieldset>';
				echo "</form>\n";

				if ($page->qtype == LL_AUDIO
						|| $page->qtype == LL_VIDEO) {
					echo '<form id="submissionform" action="view.php" method="get">';
					echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
					echo '<input type="hidden" name="pageid" value="'.$page->id.'" />';
					if ($lesson->defaultfeedback) { echo '<input type="hidden" name="showfeedback" value="1" />'; }
					echo '</form>';
				}


			/// feedback

				// if we should show feedback, print it here
				if ($showfeedback) {
					print_simple_box_start('center');
					
					// if this is an autogradable page, check the correctness of the submission
					if ($page->qtype != LL_AUDIO
							&& $page->qtype != LL_VIDEO
							&& $page->qtype != LL_ESSAY) {
						// if this is a student, use the attempt that was just submitted
						if (!has_capability('mod/languagelesson:manage', $context)) {
							if (!$aid || !$attempt = get_record('languagelesson_attempts', 'id', $aid)) {
								error('Failed to retrieve attempt record for feedback.');
							}
							// set the default feedback message according to if the attempt was correct
							if ($attempt->correct) { $feedback = $lesson->defaultcorrect; }
							else { $feedback = $lesson->defaultwrong; }
							// pull the record for the answer they chose; if there isn't one, then they submitted something that's not
							// stored as a predicted answer, so create an answer frame object for a wrong answer
							if (! $answer = get_record('languagelesson_answers', 'id', $attempt->answerid)) {
								$answer = new stdClass();
								$answer->score = 0;
								$answer->response = '';
							}
						}
						// otherwise, it's a teacher, so skip the attempt stuff
						else {
							// if there's no answer to pull, create a blank incorrect answer for use in printing feedback message below
							if (!$aid || !$answer = get_record('languagelesson_answers', 'id', $aid)) {
								$answer = new stdClass();
								$answer->score = 0;
								$answer->response = '';
							}
							// set default feedback according to score of answer
							if ($answer->score > 0) { $feedback = $lesson->defaultcorrect; }
							else { $feedback = $lesson->defaultwrong; }
						}
						// and if that answer has custom feedback, it overrides the default
						$customresponse = trim($answer->response);
						if (!empty($customresponse)) { $feedback = $customresponse; }
						// print the colored bar 
						if ((!has_capability('mod/languagelesson:manage', $context) && $attempt->correct)
								|| (has_capability('mod/languagelesson:manage', $context) && $answer->score > 0)) {
							echo '<div class="feedbackbar greenbar">'.get_string('correct', 'languagelesson').'</div>';
						} else { echo '<div class="feedbackbar redbar">'.get_string('incorrect', 'languagelesson').'</div>'; }
						// print the feedback
						echo "<div>$feedback</div>";
					}
					// if it's a manually-graded page, tell them it'll get graded later
					else {
						echo '<div class="feedbackbar graybar">'.get_string('submitted', 'languagelesson').'</div>';
						echo '<div>'.get_string('waitingforfeedback', 'languagelesson').'</div>';
					}
					print_simple_box_end();
				}


			/// nav buttons

				// print the start of the containing table for the nav buttons
				echo '<table><tr>';

				// if not a revlet page (revlets have own submit buttons), print the submit button
				if ($page->qtype != LL_AUDIO && $page->qtype != LL_VIDEO
						&& !$nomoreattempts && !$reviewing) {
					echo '<td>';
					languagelesson_print_submit_link(get_string('submit', 'languagelesson'), 'answerform',
							"document.forms['answerform'].submit();");
					echo '</td>';
				}
				
				// if showing feedback or attempts are locked or it's a submitted revlet page, print the continue button
				if ($showfeedback || $nomoreattempts || $reviewing
						|| (($page->qtype == LL_AUDIO || $page->qtype == LL_VIDEO) && $hassubmitted)) {
					echo '<td>';
					echo '<form id="continueform" action="view.php" method="get">';
					echo '<input type="hidden" name="id" value="'.$cm->id.'" />';

					// if continue.php output a nextpageid, print it here
					if ($saved_nextpageid) {
						echo '<input type="hidden" name="pageid" value="'.$saved_nextpageid.'" />';
					}

					// if they're reviewing, keep them reviewing
					if ($reviewing) { echo '<input type="hidden" name="reviewing" value="1" />'; }

					/// put the buttons in a table outside the form, make the submit button just submit the answerform from outside

					languagelesson_print_submit_link(get_string('continue', 'languagelesson'), 'continueform');
					echo '</form>';
					echo '</td>';
				}

				echo '</tr></table>';
				
			}
	
		}
		
	// </handle printing feedback/submission buttons> ////
	//////////////////////////////////////////////////////
		
		
		
		
		
		
	//////////////////////////////////////////////////////
	// HANDLE PAGES WITHOUT ANSWERS
	//////////////////////////////////////////////////////
        else {
            // a page without answers - find the next (logical) page
            echo "<form id=\"pageform\" method=\"get\" action=\"$CFG->wwwroot/mod/languagelesson/view.php\">\n";
            echo '<div>';
            echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />\n";
			
			if (!$newpageid = get_field("languagelesson_pages", "nextpageid", "id", $pageid)) {
				// this is the last page - flag end of lesson
				$newpageid = LL_EOL;
			}
			echo "<input type=\"hidden\" name=\"pageid\" value=\"$newpageid\" />\n";
            languagelesson_print_submit_link(get_string('continue', 'languagelesson'), 'pageform');
            echo "</form>\n";
        }
		
		
	// </handle no answers> //////////////////////////////
	//////////////////////////////////////////////////////
        
        // Finish printing the page
        languagelesson_print_progress_bar($lesson, $course);
		echo '<a href="https://docs.google.com/a/carleton.edu/spreadsheet/viewform?formkey=dGw5bjNrN2tjS3MwbC05NnVnNV9HZFE6MQ"
			target="_blank" style="font-size:0.75em; margin-top:25px;">Report a problem</a>';
		?>
				</td>
				<?php if (languagelesson_blocks_have_content($lesson, $pageblocks, BLOCK_POS_RIGHT)) { ?>
				<td id="right-column" style="width: <?php echo $rightcolumnwidth; ?>px;">
					<?php
						languagelesson_print_clock_block($cm->id, $lesson, $timer);
						languagelesson_print_mediafile_block($cm->id, $lesson);

						if (!empty($CFG->showblocksonmodpages)) {
							if ((blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $PAGE->user_is_editing())) {
								blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
							}
						}
					?>
				</td>
				<?php } ?>
			</tr>
		</table><?php

    }
	
	
// </if the page ID was anything other than EOL> //////////
///////////////////////////////////////////////////////////
	
	
	
	
	
	
	
	
	
///////////////////////////////////////////////////////////
// IF THE PAGE IS THE END OF THE LESSON
// @eol@
///////////////////////////////////////////////////////////
	
	
	
	else {
    	// if $pageid == LL_EOL
        // end of lesson reached work out grade
		// NOTE that thanks to code in action/continue.php, user will never
		// actually view the EOL page unless they are out of time or they have
		// answered every question
        
        // Used to check to see if the student ran out of time
        $outoftime = optional_param('outoftime', '', PARAM_ALPHA);
		
		
	/// update the user's timer record
        if (!has_capability('mod/languagelesson:manage', $context)) { // if the user is a student
            unset($USER->startlesson[$lesson->id]);
            if (!$timer = get_records_select('languagelesson_timer', "lessonid = $lesson->id AND userid = $USER->id", 'starttime')) {
				//error('Error: could not find records'); ///TODO: find what causes this error, and get rid of the causes!  Shouldn't
					//just ignore the error...
                languagelesson_insert_bs_timer($lesson->id, $USER->id);
                header('Location: '.$CFG->wwwroot.'/mod/languagelesson/view.php?id='.$cm->id."&amp;pageid=".$page->id);
                exit();
            } else {
                $timer = array_pop($timer); // this will get the latest start time record
            }
            $timer->lessontime = time();
            
            if (!update_record("languagelesson_timer", $timer)) {
                error("Error: could not update lesson_timer table");
            }
        }
        
	/// log that they got to the end of the lesson
        add_to_log($course->id, "lesson", "end", "view.php?id=$cm->id", "$lesson->id", $cm->id);
        
		
	/// start printing EOL report page
        languagelesson_print_header($cm, $course, $lesson, 'view');
        print_heading(get_string("congratulations", "languagelesson"));
        print_simple_box_start("center");
		
		
	/// if this is a student, grade the lesson and print out grade information
        if (!has_capability('mod/languagelesson:manage', $context)) {
			
			if (! $oldgrade = get_record("languagelesson_grades", 'lessonid', $lesson->id, 'userid', $USER->id)) {
				error("Could not fetch old grade record.");
			}
			
		/// if the old grade is marked as completed, redirect the user to the "Old Grade" page
			if ($oldgrade->completed) {
				redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id", '', 0);
			}
            
		/// grade the lesson
		/// @TODO@ this is redundant, as action/continue now grades the lesson
		///        after every question submission; however, need the object
		///        produced by languagelesson_grade here
            $gradeinfo = languagelesson_grade($lesson);
			
		/// build the grade object used to mark that this lesson was actually completed
			$gradeobj = new stdClass();
			$gradeobj->id = $oldgrade->id;
			$gradeobj->lessond = $lesson->id;
			$gradeobj->userid = $USER->id;
			$gradeobj->grade = $gradeinfo->grade;
			$gradeobj->completed = time();
			
		/// and update the record
			if (! $update = update_record("languagelesson_grades", $gradeobj)) {
				error("Grade not updated.");
			}
            
			
		/// and print out information on the grade they got
		/// print this ONLY if this course allows students to see their grades
            if ($gradeinfo->nanswered && $course->showgrades) { //changed from if($gradeinfo->attempts)
			/// build the message to show to the student
                $a = new stdClass;
                $a->earned = $gradeinfo->earned;
                $a->total = $gradeinfo->total;
                $a->grade = number_format($gradeinfo->grade * $lesson->grade / 100, 1);
				
				/// if there were manually-graded questions in the lesson, let the student know that
				/// the grade they have now is NOT necessarily their final grade
				/// ignore this if the lesson was set to autograde manual-type questions
                if ($gradeinfo->nmanual && !$lesson->autograde) {
				    $a->tempmaxgrade = $gradeinfo->total - $gradeinfo->manualpoints;
					$a->nmanquestions = $gradeinfo->nmanual;
					echo "<div style=\"text-align:center;\">".get_string("displayscorewithmanuals",
						"languagelesson", $a)."</div>";
                } else {
                    echo "<div style=\"text-align:center;\">".get_string("displayscorewithoutmanuals", "languagelesson", $a)."</div>";  
                }
				
            } else {
                if ($lesson->timed) {
                    if ($outoftime == 'normal') {
                        echo get_string("eolstudentoutoftimenoanswers", "languagelesson");
                    }
                } else {
                    echo get_string("welldone", "languagelesson");
                }
            }

        } else { 
            // display for teacher
            echo "<p style=\"text-align:center;\">".get_string("displayofgrade", "languagelesson")."</p>\n";
        }
        print_simple_box_end(); //End of Lesson button to Continue.
		
        
	/// if this lesson links to another activity, print out that link now
        if ($lesson->activitylink) {
            if ($module = get_record('course_modules', 'id', $lesson->activitylink)) {
                if ($modname = get_field('modules', 'name', 'id', $module->module))
                    if ($instance = get_record($modname, 'id', $module->instance)) {
                        echo "<div style=\"text-align:center; padding:5px;\" class=\"lessonbutton standardbutton\">".
                                "<a href=\"$CFG->wwwroot/mod/$modname/view.php?id=$lesson->activitylink\">".
                                get_string('activitylinkname', 'languagelesson', $instance->name)."</a></div>\n";
                    }
            }
        }
		
	
	/// print out course and gradebook links
		echo "<div style=\"text-align:center; padding:5px;\" class=\"lessonbutton standardbutton\"><a
			href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".get_string('returnto', 'languagelesson',
					format_string($course->fullname, true))."</a></div>\n";
        if ($course->showgrades) {
			echo "<div style=\"text-align:center; padding:5px;\" class=\"lessonbutton standardbutton\"><a
				href=\"$CFG->wwwroot/grade/index.php?id=$course->id\">".get_string('viewgrades', 'languagelesson')."</a></div>\n";
		}
    }
	
	
	
// </if the page was EOL> /////////////////////////////////
///////////////////////////////////////////////////////////






/// Finish the page
    print_footer($course);

?>
