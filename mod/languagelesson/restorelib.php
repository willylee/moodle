<?php //$Id: restorelib.php 651 2011-07-22 21:27:21Z griffisd $
/**
 * This php script contains all the stuff to restore lesson mods
 *
 * @version $Id: restorelib.php 651 2011-07-22 21:27:21Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

    //This is the "graphical" structure of the lesson mod: 
    //
    //          lesson_default                  lesson ----------------------------|--------------------------|--------------------------|
    //     (UL, pk->id,fk->courseid)         (CL,pk->id)                           |                          |                          |
    //                                             |                               |                          |                          |
    //                                             |                         lesson_grades                       lesson_timer
    //                                             |                  (UL, pk->id,fk->lessonid)    (UL, pk->id,fk->lessonid)   (UL, pk->id,fk->lessonid)
    //                                             |
    //                                             |
    //                                      lesson_pages---------------------------|
    //                                  (CL,pk->id,fk->lessonid)                   |
    //                                             |                               |
    //                                             |                         languagelesson_seenbranches
    //                                             |                   (UL, pk->id,fk->pageid)
    //                                       lesson_answers
    //                                    (CL,pk->id,fk->pageid)
    //                                             |
    //                                             |
    //                                             |
    //                                       lesson_attempts
    //                                  (UL,pk->id,fk->answerid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------


// we need the globals established in locallib.php
require_once('locallib.php');


    //This function executes all the restore procedure about this mod
    function languagelesson_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('Language Lesson', $restore, $info['MOD']['#'], array('AVAILABLE', 'DEADLINE'));
            }
            //traverse_xmlize($info);                                                              //Debug
            //print_object ($GLOBALS['traverse_array']);                                           //Debug
            //$GLOBALS['traverse_array']="";                                                       //Debug

            //Now, build the lesson record structure
            $lesson->course = $restore->course_id;
            $lesson->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $lesson->type = backup_todb($info['MOD']['#']['TYPE']['0']['#']);
            $lesson->dependency = backup_todb($info['MOD']['#']['DEPENDENCY']['0']['#']);
            $lesson->conditions = backup_todb($info['MOD']['#']['CONDITIONS']['0']['#']);
            $lesson->defaultpoints = backup_todb($info['MOD']['#']['DEFAULTPOINTS']['0']['#']);
            $lesson->grade = backup_todb($info['MOD']['#']['GRADE']['0']['#']);
            $lesson->showongoingscore = backup_todb($info['MOD']['#']['SHOWONGOINGSCORE']['0']['#']);
            $lesson->showoldanswer = backup_todb($info['MOD']['#']['SHOWOLDANSWER']['0']['#']);
            $lesson->maxattempts = backup_todb($info['MOD']['#']['MAXATTEMPTS']['0']['#']);
            $lesson->penalty = backup_todb($info['MOD']['#']['PENALTY']['0']['#']);
            $lesson->penaltytype = backup_todb($info['MOD']['#']['PENALTYTYPE']['0']['#']);
            $lesson->penaltyvalue = backup_todb($info['MOD']['#']['PENALTYVALUE']['0']['#']);
            $lesson->autograde = backup_todb($info['MOD']['#']['AUTOGRADE']['0']['#']);
            $lesson->shuffleanswers = backup_todb($info['MOD']['#']['SHUFFLEANSWERS']['0']['#']);
            $lesson->defaultfeedback = backup_todb($info['MOD']['#']['DEFAULTFEEDBACK']['0']['#']);
            $lesson->defaultcorrect = isset($info['MOD']['#']['DEFAULTCORRECT']['0']['#'])
										? backup_todb($info['MOD']['#']['DEFAULTCORRECT']['0']['#'])
										: null;
            $lesson->defaultwrong = isset($info['MOD']['#']['DEFAULTWRONG']['0']['#'])
										? backup_todb($info['MOD']['#']['DEFAULTWRONG']['0']['#'])
										: null;
            $lesson->timed = backup_todb($info['MOD']['#']['TIMED']['0']['#']);
            $lesson->maxtime = backup_todb($info['MOD']['#']['MAXTIME']['0']['#']);
            $lesson->activitylink = backup_todb($info['MOD']['#']['ACTIVITYLINK']['0']['#']);
            $lesson->mediafile = backup_todb($info['MOD']['#']['MEDIAFILE']['0']['#']);
            $lesson->mediaheight = backup_todb($info['MOD']['#']['MEDIAHEIGHT']['0']['#']);
            $lesson->mediawidth = backup_todb($info['MOD']['#']['MEDIAWIDTH']['0']['#']);
            $lesson->displayleft = backup_todb($info['MOD']['#']['DISPLAYLEFT']['0']['#']);
            $lesson->contextcolors = backup_todb($info['MOD']['#']['CONTEXTCOLORS']['0']['#']);
            $lesson->progressbar = backup_todb($info['MOD']['#']['PROGRESSBAR']['0']['#']);
            $lesson->available = backup_todb($info['MOD']['#']['AVAILABLE']['0']['#']);
            $lesson->deadline = backup_todb($info['MOD']['#']['DEADLINE']['0']['#']);
            $lesson->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the lesson
            $newid = insert_record("languagelesson", $lesson);

            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","languagelesson")." \"".format_string(stripslashes($lesson->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
                //We have to restore the lesson pages which are held in their logical order...
                $userdata = restore_userdata_selected($restore,"languagelesson",$mod->id);
                $status = languagelesson_pages_restore_mods($mod->id,$newid,$info,$restore,$userdata);
                //...and the user grades, high scores, and timer (if required)
                if ($status) {
                    if ($userdata) {
                        if(!languagelesson_grades_restore_mods($newid,$info,$restore)) {
                            return false;
                        }
                        if (!languagelesson_timer_restore_mods($newid,$info,$restore)) {
                            return false;
                        }
                    }
                    // restore the default for the course.  Only do this once by checking for an id for lesson_default
                    $lessondefault = backup_getid($restore->backup_unique_code,'languagelesson_default',$restore->course_id);
                    if (!$lessondefault) {
                        $status = languagelesson_default_restore_mods($info,$restore);
                    }
                    
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }
        return $status;
    }

    //This function restores the lesson_pages
    function languagelesson_pages_restore_mods($oldlessonid,$newlessonid,$info,$restore,$userdata=false) {

        global $CFG;

        $status = true;

        //Get the lesson_elements array
        $pages = $info['MOD']['#']['PAGES']['0']['#']['PAGE'];

        //Iterate over lesson pages (they are held in their logical order)
        $prevpageid = 0;
        for($i = 0; $i < sizeof($pages); $i++) {
            $page_info = $pages[$i];
            //traverse_xmlize($ele_info);                                                          //Debug
            //print_object ($GLOBALS['traverse_array']);                                           //Debug
            //$GLOBALS['traverse_array']="";                                                       //Debug

            //We'll need this later!!
            $oldid = backup_todb($page_info['#']['PAGEID']['0']['#']);

            //Now, build the lesson_pages record structure
            $page->lessonid = $newlessonid;
            $page->prevpageid = $prevpageid;
			$page->ordering = backup_todb($page_info['#']['ORDERING']['0']['#']);
            $page->qtype = backup_todb($page_info['#']['QTYPE']['0']['#']);
            $page->qoption = backup_todb($page_info['#']['QOPTION']['0']['#']);
            $page->layout = backup_todb($page_info['#']['LAYOUT']['0']['#']);
            $page->timecreated = backup_todb($page_info['#']['TIMECREATED']['0']['#']);
            $page->timemodified = backup_todb($page_info['#']['TIMEMODIFIED']['0']['#']);
            $page->title = backup_todb($page_info['#']['TITLE']['0']['#']);
            $page->contents = backup_todb($page_info['#']['CONTENTS']['0']['#']);

            //The structure is equal to the db, so insert the lesson_pages
            $newid = insert_record("languagelesson_pages",$page);

            //Fix the forwards link of the previous page
            if ($prevpageid) {
                if (!set_field("languagelesson_pages", "nextpageid", $newid, "id", $prevpageid)) {
                    error("Language Lesson restorelib: unable to update link");
                }
            }
            $prevpageid = $newid;

            //Do some output
            if (($i+1) % 10 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 200 == 0) {
                        echo "<br/>";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids (restore logs will use it!!)
                backup_putid($restore->backup_unique_code,"languagelesson_pages", $oldid, $newid);
                //We have to restore the lesson_answers table now (a page level table)
				$status =
					languagelesson_answers_restore($oldlessonid,$newlessonid,$oldid,$newid,$page_info,$page->qtype,$restore,$userdata);
                
                //Need to update useranswer field (which has answer id's in it)
                //for matching and multi-answer multi-choice questions
                if ($userdata) { // first check to see if we even have to do this
                    // if multi-answer multi-choice question or matching
                    if (($page->qtype == LL_MULTICHOICE && $page->qoption) ||
                         $page->qtype == LL_MATCHING) {
                        // get all the attempt records for this page
                        if ($attempts = get_records("languagelesson_attempts", "pageid", $newid)) {
                            foreach ($attempts as $attempt) {
                                unset($newuseranswer);
                                if ($attempt->useranswer != NULL) {
                                    // explode the user answer.  Each element in
                                    // $useranswer is an old answer id, so needs to be updated
                                    $useranswer = explode(",", $attempt->useranswer);
                                    foreach ($useranswer as $oldanswerid) {
                                         $backupdata = backup_getid($restore->backup_unique_code,"languagelesson_answers",$oldanswerid);
                                         $newuseranswer[] = $backupdata->new_id;
                                    }
                                    // get the useranswer in the right format
                                    $attempt->useranswer = implode(",", $newuseranswer);
                                    // update it
                                    update_record("languagelesson_attempts", $attempt);
                                }
                            }
                        }
                    }
                }

                // backup branch table info for branch tables.
                if ($status && $userdata) {
                    if (!languagelesson_seenbranches_restore($newlessonid,$newid,$page_info,$restore)) {
                        return false;
                    }
                }
            } else {
                $status = false;
            }
        }

        //We've restored all the pages and answers, we now need to fix the jumps in the
        //answer records if they are absolute
        if ($answers = get_records("languagelesson_answers", "lessonid", $newlessonid)) {
            foreach ($answers as $answer) {
                if ($answer->jumpto > 0) {
                    // change the absolute page id
                    $page = backup_getid($restore->backup_unique_code,"languagelesson_pages",$answer->jumpto);
                    if ($page) {
                        if (!set_field("languagelesson_answers", "jumpto", $page->new_id, "id", $answer->id)) {
                            error("Lesson restorelib: unable to reset jump");
                        }
                    }
                }
            }
        }
        return $status;
    }


    //This function restores the lesson_answers
    function languagelesson_answers_restore($oldlessonid,$newlessonid,$oldpageid,$newpageid,$info,$qtype,$restore,$userdata=false) {

        global $CFG;

        $status = true;

        //Get the lesson_answers array (optional)
        if (isset($info['#']['ANSWERS']['0']['#']['ANSWER'])) {

            $answers = $info['#']['ANSWERS']['0']['#']['ANSWER'];

            //Iterate over lesson_answers
            for($i = 0; $i < sizeof($answers); $i++) {
                $answer_info = $answers[$i];
                //traverse_xmlize($rub_info);                                  //Debug
                //print_object ($GLOBALS['traverse_array']);                   //Debug
                //$GLOBALS['traverse_array']="";                               //Debug

                //We'll need this later!!
                $oldid = backup_todb($answer_info['#']['ID']['0']['#']);

				//If this answer was stored with an ID of 0, then it's a placeholder for restoring incorrect attempts on
				//arbitrary-answer questions (e.g. SHORTANSWER), so restore the attempts and move on
				if ($oldid == 0 && $userdata) {
					$status = languagelesson_attempts_restore($oldlessonid, $newlessonid, $oldpageid, $newpageid, 0, $answer_info,
							$qtype, $restore);
					continue;
				}

                //Now, build the lesson_answers record structure
                $answer->lessonid = $newlessonid;
                $answer->pageid = $newpageid;
                // the absolute jumps will need fixing later
                $answer->jumpto = backup_todb($answer_info['#']['JUMPTO']['0']['#']);
                $answer->score = backup_todb($answer_info['#']['SCORE']['0']['#']);
                $answer->flags = backup_todb($answer_info['#']['FLAGS']['0']['#']);
                $answer->timecreated = backup_todb($answer_info['#']['TIMECREATED']['0']['#']);
                $answer->timemodified = backup_todb($answer_info['#']['TIMEMODIFIED']['0']['#']);
                $answer->answer = backup_todb($answer_info['#']['ANSWERTEXT']['0']['#']);
                $answer->response = backup_todb($answer_info['#']['RESPONSE']['0']['#']);

                //The structure is equal to the db, so insert the lesson_answers
                $newid = insert_record("languagelesson_answers",$answer);

                //Do some output
                if (($i+1) % 10 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 200 == 0) {
                            echo "<br/>";
                        }
                    }
                    backup_flush(300);
                }

                if ($newid) {
                    // need to store the id so we can update the useranswer
                    // field in attempts.  This is done in the languagelesson_pages_restore_mods
                    backup_putid($restore->backup_unique_code,"languagelesson_answers", $oldid, $newid);                                 

                    if ($userdata) {
                        //We have to restore the lesson_attempts table now (a answers level table)
						$status = languagelesson_attempts_restore($oldlessonid, $newlessonid, $oldpageid, $newpageid, $newid,
								$answer_info, $qtype, $restore);
                    }
                } else {
                    $status = false;
                }
            }
        }
        return $status;
    }


    //This function restores the attempts
    function languagelesson_attempts_restore($oldlessonid, $newlessonid, $oldpageid, $newpageid, $answerid, $info, $qtype, $restore) {

        global $CFG;

        $status = true;

        //Get the attempts array (optional)
        if (isset($info['#']['ATTEMPTS']['0']['#']['ATTEMPT'])) {
            $attempts = $info['#']['ATTEMPTS']['0']['#']['ATTEMPT'];
            //Iterate over attempts
            for($i = 0; $i < sizeof($attempts); $i++) {
                $attempt_info = $attempts[$i];
                //traverse_xmlize($sub_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                          //Debug
                //$GLOBALS['traverse_array']="";                                                      //Debug

                //We'll need this later!!
                $olduserid = backup_todb($attempt_info['#']['USERID']['0']['#']);

                //Now, build the lesson_attempts record structure
                $attempt->lessonid = $newlessonid;
                $attempt->pageid = $newpageid;
                $attempt->answerid = $answerid;
                $attempt->userid = backup_todb($attempt_info['#']['USERID']['0']['#']);
                $attempt->retry = backup_todb($attempt_info['#']['RETRY']['0']['#']);
				$attempt->iscurrent = backup_todb($attempt_info['#']['ISCURRENT']['0']['#']);
                $attempt->correct = backup_todb($attempt_info['#']['CORRECT']['0']['#']);
                $attempt->score = backup_todb($attempt_info['#']['SCORE']['0']['#']);
                $attempt->useranswer = (isset($attempt_info['#']['USERANSWER']['0']['#']))
										? addslashes(backup_todb($attempt_info['#']['USERANSWER']['0']['#']))
										: null;
                $attempt->timeseen = backup_todb($attempt_info['#']['TIMESEEN']['0']['#']);

                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
                if ($user) {
                    $attempt->userid = $user->new_id;
                }

                //The structure is equal to the db, so insert the lesson_attempt
                $newid = insert_record("languagelesson_attempts",$attempt);

				//Re-insert the corresponding manual attempts, if any
				$status = languagelesson_manattempts_restore($newlessonid, $newpageid, $newid, $qtype, $attempt->userid, $attempt_info,
						$restore);

				if ($newid) {
					//If we're backing up userdata and the question page being restored is an audio or video type, restore its
					//submitted files
					if ($qtype == LL_ESSAY
							|| $qtype == LL_AUDIO
							|| $qtype == LL_VIDEO) {
						languagelesson_restore_files($oldlessonid, $newlessonid, $oldpageid, $newpageid, $olduserid, $attempt->userid,
								$restore);
					}

					//Do some output
					if (($i+1) % 50 == 0) {
						if (!defined('RESTORE_SILENTLY')) {
							echo ".";
							if (($i+1) % 1000 == 0) {
								echo "<br/>";
							}
						}
						backup_flush(300);
					}
				} else {
					$status = false;
				}
            }
        }

    return $status;
    }

	
	
	function languagelesson_manattempts_restore($newlessonid, $newpageid, $attid, $qtype, $newuserid, $attemptinfo, $restore) {
		
		global $CFG;

		$status = true;
		
        //Get the manattempts array (optional)
        if (isset($attemptinfo['#']['MANATTEMPT']['0'])) {
            $manattempt_info = $attemptinfo['#']['MANATTEMPT']['0'];

			$manattempt = new stdClass;
			$manattempt->lessonid = $newlessonid;
			$manattempt->pageid = $newpageid;
			$manattempt->userid = $newuserid;
			$manattempt->viewed = backup_todb($manattempt_info['#']['VIEWED']['0']['#']);
			$manattempt->graded = backup_todb($manattempt_info['#']['GRADED']['0']['#']);
			$manattempt->type = $qtype;
			$manattempt->essay = backup_todb($manattempt_info['#']['ESSAY']['0']['#']);
			$manattempt->fname = backup_todb($manattempt_info['#']['FNAME']['0']['#']);
			$manattempt->resubmit = backup_todb($manattempt_info['#']['RESUBMIT']['0']['#']);
			$manattempt->timeseen = backup_todb($manattempt_info['#']['TIMESEEN']['0']['#']);

			//This structure is now equal to the db, so insert the lesson_attempt
			$newid = insert_record("languagelesson_manattempts",$manattempt);

			//continue restoring and update the assignment record manattempt pointer if successful insertion
			if ($newid && set_field('languagelesson_attempts', 'manattemptid', $newid, 'id', $attid)) {
				//Now restore any feedback records corresponding to this manattempt
				$status = languagelesson_feedbacks_restore($newlessonid, $newpageid, $newuserid, $newid, $manattempt_info, $restore);
			} else {
				$status = false;
			}
		}

		return $status;

	}


	
	function languagelesson_feedbacks_restore($newlessonid, $newpageid, $newuserid, $manattemptid, $manattempt_info, $restore) {
		
		global $CFG;

		$status = true;

		//Get the optional feedbacks array
		if (isset($manattempt_info['#']['FEEDBACKS']['0']['#']['FEEDBACK'])) {
			$feedbacks = $manattempt_info['#']['FEEDBACKS']['0']['#']['FEEDBACK'];
            for($i = 0; $i < sizeof($feedbacks); $i++) {
				$feedback_info = $feedbacks[$i];

				$oldteacherid = backup_todb($feedback_info['#']['TEACHERID']['0']['#']);

				$feedback = new stdClass;
				$feedback->lessonid = $newlessonid;
				$feedback->pageid = $newpageid;
				$feedback->userid = $newuserid;
				$feedback->manattemptid = $manattemptid;
				$feedback->teacherid = backup_todb($feedback_info['#']['TEACHERID']['0']['#']);
				$feedback->fname = backup_todb($feedback_info['#']['FNAME']['0']['#']);
				$feedback->text = backup_todb($feedback_info['#']['TEXT']['0']['#']);
				$feedback->timeseen = backup_todb($feedback_info['#']['TIMESEEN']['0']['#']);

                //We have to recode the teacherid field
                $teacher = backup_getid($restore->backup_unique_code,"user",$oldteacherid);
                if ($teacher) {
                    $feedback->teacherid = $teacher->new_id;
                }

				//Push it into the db
				$status = insert_record("languagelesson_feedback", $feedback);

			}
		}

		return $status;
	}

    


/******************************************************************************************************/
/******************************************************************************************************/
/// copied from mod/assignment/restorelib.php, modified for languagelesson
    
    //This function copies the languagelesson-related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (languagelesson id and user id) 
    function languagelesson_restore_files ($oldllid, $newllid, $oldpageid, $newpageid, $olduserid, $newuserid, $restore) {
    //function languagelesson_restore_files($oldllid, $newllid, $restore) {


		error_log("restore_files called");

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $languagelesson_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;
   
        //Check it exists and create it
        $status = check_dir_exists($moddata_path,true);

        //Now, locate languagelesson directory
        if ($status) {
            $languagelesson_path = $moddata_path."/languagelesson";
            //Check it exists and create it
            $status = check_dir_exists($languagelesson_path,true);
        }
        
        //Now locate the temp dir we are going to restore
        if ($status) {
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/languagelesson/".$oldllid."/".$oldpageid."/".$olduserid;
            //Check it exists
            if (is_dir($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/assignment
        if ($status and $todo) {
            //First this languagelesson id
            $this_languagelesson_path = $languagelesson_path."/".$newllid;
            $status = check_dir_exists($this_languagelesson_path,true);
            //Now this page id
            $page_languagelesson_path = $this_languagelesson_path."/".$newpageid;
            $status = check_dir_exists($page_languagelesson_path,true);
            //Now this user id
            $user_languagelesson_path = $page_languagelesson_path."/".$newuserid;
            $status = check_dir_exists($user_languagelesson_path,true);
            //And now, copy temp_path to user_assignment_path
            $status = backup_copy_file($temp_path, $user_languagelesson_path);
        }
   
        return $status;
    }
    
    
    
/******************************************************************************************************/
/******************************************************************************************************/
    
    

    //This function restores the lesson_grades
    function languagelesson_grades_restore_mods($lessonid, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the grades array (optional)
        if (isset($info['MOD']['#']['GRADES']['0']['#']['GRADE'])) {
            $grades = $info['MOD']['#']['GRADES']['0']['#']['GRADE'];

            //Iterate over grades
            for($i = 0; $i < sizeof($grades); $i++) {
                $grade_info = $grades[$i];
                //traverse_xmlize($grade_info);                         //Debug
                //print_object ($GLOBALS['traverse_array']);            //Debug
                //$GLOBALS['traverse_array']="";                        //Debug

                //We'll need this later!!
                $olduserid = backup_todb($grade_info['#']['USERID']['0']['#']);

                //Now, build the lesson_GRADES record structure
                $grade->lessonid = $lessonid;
                $grade->userid = backup_todb($grade_info['#']['USERID']['0']['#']);
                $grade->grade = backup_todb($grade_info['#']['GRADE_VALUE']['0']['#']);
                $grade->late = backup_todb($grade_info['#']['LATE']['0']['#']);
                $grade->completed = backup_todb($grade_info['#']['COMPLETED']['0']['#']);

                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
                if ($user) {
                    $grade->userid = $user->new_id;
                }

                //The structure is equal to the db, so insert the lesson_grade
                $newid = insert_record("languagelesson_grades",$grade);

                //Do some output
                if (($i+1) % 50 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 1000 == 0) {
                            echo "<br/>";
                        }
                    }
                    backup_flush(300);
                }

                if (!$newid) {
                    $status = false;
                }
            }
        }

        return $status;
    }
    
    
    
    //This function restores the languagelesson_seenbranches
    function languagelesson_seenbranches_restore($lessonid, $pageid, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the branch array (optional)
        if (isset($info['#']['BRANCHES']['0']['#']['BRANCH'])) {
            $branches = $info['#']['BRANCHES']['0']['#']['BRANCH'];
            //Iterate over branches
            for($i = 0; $i < sizeof($branches); $i++) {
                $branch_info = $branches[$i];
                //traverse_xmlize($branch_info);                                                         //Debug
                //print_object ($GLOBALS['traverse_array']);                                          //Debug
                //$GLOBALS['traverse_array']="";                                                      //Debug

                //We'll need this later!!
                $olduserid = backup_todb($branch_info['#']['USERID']['0']['#']);

                //Now, build the lesson_attempts record structure
                $branch->lessonid = $lessonid;
                $branch->userid = backup_todb($branch_info['#']['USERID']['0']['#']);
                $branch->pageid = $pageid;
                $branch->retry = backup_todb($branch_info['#']['RETRY']['0']['#']);
                $branch->flag = backup_todb($branch_info['#']['FLAG']['0']['#']);
                $branch->timeseen = backup_todb($branch_info['#']['TIMESEEN']['0']['#']);

                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
                if ($user) {
                    $branch->userid = $user->new_id;
                }

                //The structure is equal to the db, so insert the lesson_attempt
                $newid = insert_record("languagelesson_seenbranches",$branch);

                //Do some output
                if (($i+1) % 50 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 1000 == 0) {
                            echo "<br/>";
                        }
                    }
                    backup_flush(300);
                }
            }
        }

    return $status;
    }

    //This function restores the lesson_timer
    function languagelesson_timer_restore_mods($lessonid, $info, $restore) {

        global $CFG;

        $status = true;
        //Get the timer array (optional)
        if (isset($info['MOD']['#']['TIMES']['0']['#']['TIME'])) {
            $times = $info['MOD']['#']['TIMES']['0']['#']['TIME'];
            //Iterate over times
            for($i = 0; $i < sizeof($times); $i++) {
                $time_info = $times[$i];
                //traverse_xmlize($time_info);                         //Debug
                //print_object ($GLOBALS['traverse_array']);            //Debug
                //$GLOBALS['traverse_array']="";                        //Debug

                //We'll need this later!!
                $olduserid = backup_todb($time_info['#']['USERID']['0']['#']);

                //Now, build the lesson_time record structure
                $time->lessonid = $lessonid;
                $time->userid = backup_todb($time_info['#']['USERID']['0']['#']);
                $time->starttime = backup_todb($time_info['#']['STARTTIME']['0']['#']);
                $time->lessontime = backup_todb($time_info['#']['LESSONTIME']['0']['#']);

                //We have to recode the userid field
                $user = backup_getid($restore->backup_unique_code,"user",$olduserid);
                if ($user) {
                    $time->userid = $user->new_id;
                }

                //The structure is equal to the db, so insert the lesson_grade
                $newid = insert_record("languagelesson_timer",$time);

                //Do some output
                if (($i+1) % 50 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 1000 == 0) {
                            echo "<br/>";
                        }
                    }
                    backup_flush(300);
                }

                if (!$newid) {
                    $status = false;
                }
            }
        }

        return $status;
    }

    //This function restores the lesson_default
    function languagelesson_default_restore_mods($info, $restore) {

        global $CFG;

        $status = true;

        //Get the default array (optional)
        if (isset($info['MOD']['#']['DEFAULTS'])) {
            $defaults = $info['MOD']['#']['DEFAULTS'];

            //Iterate over defaults (should only be 1!)
            for($i = 0; $i < sizeof($defaults); $i++) {
                $default_info = $defaults[$i];
                //traverse_xmlize($default_info);                       //Debug
                //print_object ($GLOBALS['traverse_array']);            //Debug
                //$GLOBALS['traverse_array']="";                        //Debug

                //Now, build the lesson_default record structure
                $default->course = $restore->course_id;
                $default->type = backup_todb($default_info['#']['TYPE']['0']['#']);
                $default->conditions = backup_todb($default_info['#']['CONDITIONS']['0']['#']);
                $default->defaultpoints = backup_todb($default_info['#']['DEFAULTPOINTS']['0']['#']);
                $default->grade = backup_todb($default_info['#']['GRADE']['0']['#']);
                $default->showongoingscore = backup_todb($default_info['#']['SHOWONGOINGSCORE']['0']['#']);
                $default->showoldanswer = backup_todb($default_info['#']['SHOWOLDANSWER']['0']['#']);
                $default->maxattempts = backup_todb($default_info['#']['MAXATTEMPTS']['0']['#']);
                $default->penalty = backup_todb($default_info['#']['PENALTY']['0']['#']);
                $default->penaltytype = backup_todb($default_info['#']['PENALTYTYPE']['0']['#']);
                $default->penaltyvalue = backup_todb($default_info['#']['PENALTYVALUE']['0']['#']);
                $default->defaultfeedback = backup_todb($default_info['#']['DEFAULTFEEDBACK']['0']['#']);
                $default->defaultcorrect = backup_todb($default_info['#']['DEFAULTCORRECT']['0']['#']);
                $default->defaultwrong = backup_todb($default_info['#']['DEFAULTWRONG']['0']['#']);
                $default->autograde = backup_todb($default_info['#']['AUTOGRADE']['0']['#']);
                $default->shuffleanswers = backup_todb($default_info['#']['SHUFFLEANSWERS']['0']['#']);
                $default->timed = backup_todb($default_info['#']['TIMED']['0']['#']);
                $default->maxtime = backup_todb($default_info['#']['MAXTIME']['0']['#']);
                $default->mediaheight = backup_todb($default_info['#']['MEDIAHEIGHT']['0']['#']);
                $default->mediawidth = backup_todb($default_info['#']['MEDIAWIDTH']['0']['#']);
                $default->displayleft = backup_todb($default_info['#']['DISPLAYLEFT']['0']['#']);
                $default->contextcolors = backup_todb($default_info['#']['CONTEXTCOLORS']['0']['#']);
                $default->progressbar = backup_todb($default_info['#']['PROGRESSBAR']['0']['#']);

                //The structure is equal to the db, so insert the lesson_grade
                $newid = insert_record("languagelesson_default",$default);
                
                if ($newid) {
                    backup_putid($restore->backup_unique_code,'languagelesson_default',
                                 $restore->course_id, $newid);
                }
                
                //Do some output
                if (($i+1) % 50 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 1000 == 0) {
                            echo "<br/>";
                        }
                    }
                    backup_flush(300);
                }

                if (!$newid) {
                    $status = false;
                }
            }
        }

        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //languagelesson_decode_content_links_caller() function in each module
    //in the restore process
    function languagelesson_decode_content_links ($content,$restore) {
            
        global $CFG;
            
        $result = $content;
                
        //Link to the list of lessons
                
        $searchstring='/\$@(LESSONINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(LESSONINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/languagelesson/index.php?id='.$rec->new_id,$result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/languagelesson/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to lesson view by moduleid

        $searchstring='/\$@(LESSONVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(LESSONVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($rec->new_id) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/languagelesson/view.php?id='.$rec->new_id,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/languagelesson/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function languagelesson_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
        
        //Process every lesson PAGE in the course
        if ($pages = get_records_sql ("SELECT p.id, p.contents
                                   FROM {$CFG->prefix}languagelesson_pages p,
                                        {$CFG->prefix}languagelesson l
                                   WHERE l.course = $restore->course_id AND
                                         p.lessonid = l.id")) {
            //Iterate over each page->message
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($pages as $page) {
                //Increment counter
                $i++;
                $content = $page->contents;
                $result = restore_decode_content_links_worker($content,$restore);
                if ($result != $content) {
                    //Update record
                    $page->contents = addslashes($result);
                    $status = update_record("languagelesson_pages",$page);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }

        // Remap activity links
        if ($lessons = get_records_select('languagelesson', "activitylink != 0 AND course = $restore->course_id", '', 'id, activitylink')) {
            foreach ($lessons as $lesson) {
                if ($newcmid = backup_getid($restore->backup_unique_code, 'course_modules', $lesson->activitylink)) {
                    $status = $status and set_field('languagelesson', 'activitylink', $newcmid->new_id, 'id', $lesson->id);
                }
            }
        }

        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function languagelesson_restore_logs($restore,$log) {

        $status = false;

        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "start":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "end":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the page (to recode the url field)
                $pag = backup_getid($restore->backup_unique_code,"languagelesson_pages",$log->info);
                if ($pag) {
                    $log->url = "view.php?id=".$log->cmid."&action=navigation&pageid=".$pag->new_id;
                    $log->info = $pag->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br/>";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
