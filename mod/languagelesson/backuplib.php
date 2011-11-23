<?php //$Id: backuplib.php 651 2011-07-22 21:27:21Z griffisd $
/**
 * Lesson's backup routine
 *
 * @version $Id: backuplib.php 651 2011-07-22 21:27:21Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    //This is the "graphical" structure of the lesson mod: 
    //
    //          languagelesson_default                  lesson ----------------------------|--------------------------|--------------------------|
    //     (UL, pk->id,fk->courseid)         (CL,pk->id)                           |                          |                          | 
    //                                             |                               |                          |                          |
    //                                             |                         languagelesson_grades                                        languagelesson_timer
    //                                             |                  (UL, pk->id,fk->lessonid)    (UL, pk->id,fk->lessonid)   (UL, pk->id,fk->lessonid)
    //                                             |
    //                                             |
    //                                      languagelesson_pages---------------------------|
    //                                  (CL,pk->id,fk->lessonid)                   |
    //                                             |                               |
    //                                             |                         languagelesson_seenbranches
    //                                             |                   (UL, pk->id,fk->pageid)
    //                                       languagelesson_answers
    //                                    (CL,pk->id,fk->pageid)
    //                                             |
    //                                             |
    //                                             |
    //                                       languagelesson_attempts
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

    //This function executes all the backup procedure about this mod
    function languagelesson_backup_mods($bf, $preferences) {

        global $CFG;

        $status = true;

        //Iterate over lesson table
        $lessons = get_records("languagelesson", "course", $preferences->backup_course, "id");
        if ($lessons) {
            foreach ($lessons as $lesson) {
                if (backup_mod_selected($preferences,'languagelesson',$lesson->id)) {
                    $status = languagelesson_backup_one_mod($bf,$preferences,$lesson);
                }
            }
        }
        return $status;  
    }

    function languagelesson_backup_one_mod($bf,$preferences,$lesson) {

        global $CFG;
    
        if (is_numeric($lesson)) {
            $lesson = get_record('languagelesson','id',$lesson);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print lesson data
        fwrite ($bf,full_tag("ID",4,false,$lesson->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"languagelesson"));
        fwrite ($bf,full_tag("NAME",4,false,$lesson->name));
        fwrite ($bf,full_tag("TYPE",4,false,$lesson->type));
        fwrite ($bf,full_tag("DEPENDENCY",4,false,$lesson->dependency));
        fwrite ($bf,full_tag("CONDITIONS",4,false,$lesson->conditions));
        fwrite ($bf,full_tag("DEFAULTPOINTS",4,false,$lesson->defaultpoints));
        fwrite ($bf,full_tag("GRADE",4,false,$lesson->grade));
        fwrite ($bf,full_tag("SHOWONGOINGSCORE",4,false,$lesson->showongoingscore));
		fwrite ($bf,full_tag("SHOWOLDANSWER",4,false,$lesson->showoldanswer));
        fwrite ($bf,full_tag("MAXATTEMPTS",4,false,$lesson->maxattempts));
        fwrite ($bf,full_tag("PENALTY",4,false,$lesson->penalty));
        fwrite ($bf,full_tag("PENALTYTYPE",4,false,$lesson->penaltytype));
        fwrite ($bf,full_tag("PENALTYVALUE",4,false,$lesson->penaltyvalue));
        fwrite ($bf,full_tag("AUTOGRADE",4,false,$lesson->autograde));
        fwrite ($bf,full_tag("SHUFFLEANSWERS",4,false,$lesson->shuffleanswers));
        fwrite ($bf,full_tag("DEFAULTFEEDBACK",4,false,$lesson->defaultfeedback));
        fwrite ($bf,full_tag("DEFAULTCORRECT",4,false,$lesson->defaultcorrect));
        fwrite ($bf,full_tag("DEFAULTWRONG",4,false,$lesson->defaultwrong));
        fwrite ($bf,full_tag("TIMED",4,false,$lesson->timed));
        fwrite ($bf,full_tag("MAXTIME",4,false,$lesson->maxtime));
        fwrite ($bf,full_tag("ACTIVITYLINK",4,false,$lesson->activitylink));
        fwrite ($bf,full_tag("MEDIAFILE",4,false,$lesson->mediafile));
        fwrite ($bf,full_tag("MEDIAHEIGHT",4,false,$lesson->mediaheight));
        fwrite ($bf,full_tag("MEDIAWIDTH",4,false,$lesson->mediawidth));
        fwrite ($bf,full_tag("DISPLAYLEFT",4,false,$lesson->displayleft));
        fwrite ($bf,full_tag("CONTEXTCOLORS",4,false,$lesson->contextcolors));
        fwrite ($bf,full_tag("PROGRESSBAR",4,false,$lesson->progressbar));
        fwrite ($bf,full_tag("AVAILABLE",4,false,$lesson->available));
        fwrite ($bf,full_tag("DEADLINE",4,false,$lesson->deadline));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$lesson->timemodified));

        //Now we backup lesson pages
        $status = backup_languagelesson_pages($bf,$preferences,$lesson->id);
        //if we've selected to backup users info, then backup grades, high scores, and timer info
        if ($status) {
            if (backup_userdata_selected($preferences,'languagelesson',$lesson->id)) {
                if(!backup_languagelesson_grades($bf, $preferences, $lesson->id)) {
                    return false;
                }
                if (!backup_languagelesson_timer($bf, $preferences, $lesson->id)) {
                    return false;
                }
                
                //also, back up all submitted files for the instance
                if (!backup_languagelesson_files_instance($bf, $preferences, $lesson->id)) {
                	return false;
                }
            }
            // back up the default for the course.  There might not be one, but if there
            //  is, there will only be one.
            $status = backup_languagelesson_default($bf,$preferences);
            //End mod
            if ($status) {
                $status =fwrite ($bf,end_tag("MOD",3,true));
            }
        }

        return $status;
    }

    //Backup languagelesson_pages contents (executed from languagelesson_backup_mods)
    function backup_languagelesson_pages ($bf, $preferences, $lessonid) {

        global $CFG;

        $status = true;

        // run through the pages in their logical order, get the first page
        if ($page = get_record_select("languagelesson_pages", "lessonid = $lessonid AND prevpageid = 0")) {
            //Write start tag
            $status =fwrite ($bf,start_tag("PAGES",4,true));
            //Iterate over each page
            while (true) {
                //Start of page
                $status =fwrite ($bf,start_tag("PAGE",5,true));
                //Print page contents (prevpageid and nextpageid not needed)
                fwrite ($bf,full_tag("PAGEID",6,false,$page->id)); // needed to fix (absolute) jumps
                fwrite ($bf,full_tag("ORDERING",6,false,$page->ordering));
                fwrite ($bf,full_tag("QTYPE",6,false,$page->qtype));
                fwrite ($bf,full_tag("QOPTION",6,false,$page->qoption));
                fwrite ($bf,full_tag("LAYOUT",6,false,$page->layout));
                fwrite ($bf,full_tag("TIMECREATED",6,false,$page->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",6,false,$page->timemodified));
                fwrite ($bf,full_tag("TITLE",6,false,$page->title));
                fwrite ($bf,full_tag("CONTENTS",6,false,$page->contents));
                //Now we backup lesson answers for this page
                $status = backup_languagelesson_answers($bf, $preferences, $page->id);
                // backup branch table info for branch tables.
                if ($status && backup_userdata_selected($preferences,'languagelesson',$lessonid)) {
                    if (!backup_languagelesson_seenbranches($bf, $preferences, $page->id)) {
                        return false;
                    }
                }
                //End of page
                $status =fwrite ($bf,end_tag("PAGE",5,true));
                // move to the next (logical) page
                if ($page->nextpageid) {
                    if (!$page = get_record("languagelesson_pages", "id", $page->nextpageid)) {
                        error("Language Lesson Backup: Next page not found!");
                    }
                } else {
                    // last page reached
                    break;
                }

            }
            //Write end tag
            $status =fwrite ($bf,end_tag("PAGES",4,true));
        }
        return $status;
    }

    //Backup languagelesson_answers contents (executed from backup_languagelesson_pages)
    function backup_languagelesson_answers($bf,$preferences,$pageid) {

        global $CFG;

        $status = true;

        // get the answers in a set order, the id order
        $lesson_answers = get_records("languagelesson_answers", "pageid", $pageid, "id");

        //If there is languagelesson_answers
        if ($lesson_answers) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ANSWERS",6,true));
            //Iterate over each element
            foreach ($lesson_answers as $answer) {
                //Start answer
                $status =fwrite ($bf,start_tag("ANSWER",7,true));
                //Print answer contents
                fwrite ($bf,full_tag("ID",8,false,$answer->id));
                fwrite ($bf,full_tag("JUMPTO",8,false,$answer->jumpto));
                fwrite ($bf,full_tag("SCORE",8,false,$answer->score));
                fwrite ($bf,full_tag("FLAGS",8,false,$answer->flags));
                fwrite ($bf,full_tag("TIMECREATED",8,false,$answer->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",8,false,$answer->timemodified));
                fwrite ($bf,full_tag("ANSWERTEXT",8,false,$answer->answer));
                fwrite ($bf,full_tag("RESPONSE",8,false,$answer->response));
                //Now we backup any lesson attempts (if student data required)
                if (backup_userdata_selected($preferences,'languagelesson',$answer->lessonid)) {
                    $status = backup_languagelesson_attempts($bf,$preferences,$answer->id);
                }
                //End rubric
                $status =fwrite ($bf,end_tag("ANSWER",7,true));
            }
			//If we're saving userdata and there ARE answerid=0 attempts, then save an answer shell for incorrect short-answer attempts
			//(id=0)
			if (backup_userdata_selected($preferences,'languagelesson',$answer->lessonid)
					&& count_records('languagelesson_attempts', 'answerid', 0, 'pageid', $pageid)) {
				//start answer shell
				$status = fwrite ($bf, start_tag("ANSWER", 7, true));
				//save ID field only
				fwrite ($bf, full_tag('ID', 8, false, 0));
				//and backup the relevant attempts
				$status = backup_languagelesson_attempts($bf, $preferences, 0, $pageid);
				//now close the answer shell
				$status = fwrite ($bf, end_tag("ANSWER", 7, true));
			}
            //Write end tag
            $status =fwrite ($bf,end_tag("ANSWERS",6,true));
        }
        return $status;
    }

    //Backup languagelesson_attempts contents (executed from languagelesson_backup_answers)
    function backup_languagelesson_attempts ($bf, $preferences, $answerid, $pageid=null) {

        global $CFG;

        $status = true;

		//if using a non-specific answerid (e.g. 0), we need to fetch based on pageid as well
		if ($pageid != null) {
			$lesson_attempts = get_records_select('languagelesson_attempts', "answerid=$answerid and pageid=$pageid");
		} else {
			$lesson_attempts = get_records("languagelesson_attempts","answerid", $answerid);
		}

        //If there are attempts
        if ($lesson_attempts) {
            //Write start tag
            $status =fwrite ($bf,start_tag("ATTEMPTS",8,true));
            //Iterate over each attempt
            foreach ($lesson_attempts as $attempt) {
                //Start Attempt
                $status =fwrite ($bf,start_tag("ATTEMPT",9,true));
                //Print attempt contents
                fwrite ($bf,full_tag("USERID",10,false,$attempt->userid));       
                fwrite ($bf,full_tag("RETRY",10,false,$attempt->retry));       
                fwrite ($bf,full_tag("ISCURRENT",10,false,$attempt->iscurrent));       
                fwrite ($bf,full_tag("CORRECT",10,false,$attempt->correct));     
                fwrite ($bf,full_tag("SCORE",10,false,$attempt->score));     
                if (!fwrite ($bf,full_tag("USERANSWER",10,false,$attempt->useranswer))) {
					error_log("FAILED to backup useranswer: $attempt->useranswer");
				}
                fwrite ($bf,full_tag("MANATTEMPTID",10,false,$attempt->manattemptid));
                fwrite ($bf,full_tag("TIMESEEN",10,false,$attempt->timeseen));       
				//Backup the manualattempt, if there is one
				if ($attempt->manattemptid) {
					$status = backup_languagelesson_manattempt($bf,$preferences,$attempt->manattemptid);
				}
                //End attempt
                $status =fwrite ($bf,end_tag("ATTEMPT",9,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("ATTEMPTS",8,true));
        }
        return $status;
    }
	
	//Backup a languagelesson_manattempts record (executed from languagelesson_backup_attempts)
	function backup_languagelesson_manattempt($bf, $preferences, $manattemptid) {
		
		global $CFG;

		$status = true;

		$manattempt = get_record('languagelesson_manattempts', 'id', $manattemptid);
		//If this function has been called, then there should be a record, and there will only ever be 1, because any attempt can only
		//correspond to 1 manattempt

		//Start the manattempt
		$status =fwrite ($bf,start_tag("MANATTEMPT",11,true));
		//Save manattempt contents
		fwrite ($bf,full_tag("VIEWED",12,false,$manattempt->viewed));
		fwrite ($bf,full_tag("GRADED",12,false,$manattempt->graded));
		fwrite ($bf,full_tag("TYPE",12,false,$manattempt->type));
		fwrite ($bf,full_tag("ESSAY",12,false,$manattempt->essay));
		fwrite ($bf,full_tag("FNAME",12,false,$manattempt->fname));
		fwrite ($bf,full_tag("RESUBMIT",12,false,$manattempt->resubmit));
		fwrite ($bf,full_tag("TIMESEEN",12,false,$manattempt->timeseen));
		//If there are feedbacks to save for this manattempt, save them
		if (count_records('languagelesson_feedback', 'manattemptid', $manattempt->id)) {
			$status = backup_languagelesson_feedback($bf, $preferences, $manattempt->id);
		}
		//End manattempt
		$status =fwrite ($bf, end_tag("MANATTEMPT", 11, true));

		return $status;
	}

	//Backup languagelesson_feedback records for a manual attempt (executed from backup_languagelesson_manattempt)
	function backup_languagelesson_feedback($bf, $preferences, $manattemptid) {
		
		global $CFG;

		$status = true;

		$feedbacks = get_records('languagelesson_feedback', 'manattemptid', $manattemptid);
		if ($feedbacks) {
			//Write start tag for feedbacks
			$status =fwrite ($bf, start_tag("FEEDBACKS",13,true));
			foreach ($feedbacks as $feedback) {
				//Start the feedback
				$status =fwrite ($bf, start_tag("FEEDBACK",14,true));
				//Save feedback contents
				fwrite ($bf,full_tag("TEACHERID",15,false,$feedback->teacherid));
				fwrite ($bf,full_tag("FNAME",15,false,$feedback->fname));
				fwrite ($bf,full_tag("TEXT",15,false,$feedback->text));
				fwrite ($bf,full_tag("TIMESEEN",15,false,$feedback->timeseen));
				//End the feedback
				$status =fwrite ($bf, end_tag("FEEDBACK",14,true));
			}
			//End feedbacks
			$status =fwrite ($bf, end_tag("FEEDBACKS",13,true));
		}

		return $status;
	}
    


/*****************************************************************************************/
/*****************************************************************************************/
/// File backup functions; copied from mod/assignment/backuplib.php, modified for languagelesson
    
    //Backup assignment files because we've selected to backup user info
    //and files are user info's level
    function backup_languagelesson_files($bf,$preferences) {

        global $CFG;
       
        $status = true;

        //First we check if moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        //Now copy the languagelesson dir
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/languagelesson")) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/languagelesson",
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/languagelesson");
            }
        }

        return $status;

    } 

    function backup_languagelesson_files_instance($bf,$preferences,$instanceid) {

        global $CFG;
       
        $status = true;

        //First we check if moddata exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/languagelesson/",true);
        //Now copy the languagelesson dir
        if ($status) {
			error_log("status was true");
            //Only if it exists !! Thanks to Daniel Miksik.
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/languagelesson/".$instanceid)) {
				error_log("found the instance dir");
				$status =
					backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/languagelesson/".$instanceid,
						   $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/languagelesson/".$instanceid);
				error_log((($status) ? "instance backup succeeded" : "instance backup failed"));
            } else {
				error_log("failed to find instance dir");
			}
        } else {
			error_log("status was false");
		}

        return $status;

    }
    
/*****************************************************************************************/
/*****************************************************************************************/


   //Backup languagelesson_grades contents (executed from backup_lesson_mods)
    function backup_languagelesson_grades ($bf,$preferences,$lessonid) {

        global $CFG;

        $status = true;

        $grades = get_records("languagelesson_grades", "lessonid", $lessonid);

        //If there is grades
        if ($grades) {
            //Write start tag
            $status =fwrite ($bf,start_tag("GRADES",4,true));
            //Iterate over each grade
            foreach ($grades as $grade) {
                //Start grade
                $status =fwrite ($bf,start_tag("GRADE",5,true));
                //Print grade contents
                fwrite ($bf,full_tag("USERID",6,false,$grade->userid));
                fwrite ($bf,full_tag("GRADE_VALUE",6,false,$grade->grade));
                fwrite ($bf,full_tag("LATE",6,false,$grade->late));
                fwrite ($bf,full_tag("COMPLETED",6,false,$grade->completed));
                //End grade
                $status =fwrite ($bf,end_tag("GRADE",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("GRADES",4,true));
        }
        return $status;
    }

    //Backup languagelesson_seenbranches contents (executed from backup_languagelesson_pages)
    function backup_languagelesson_seenbranches($bf,$preferences,$pageid) {

        global $CFG;

        $status = true;

        // get the branches in a set order, the id order
        $languagelesson_seenbranches = get_records("languagelesson_seenbranches", "pageid", $pageid, "id");

        //If there is languagelesson_seenbranches
        if ($languagelesson_seenbranches) {
            //Write start tag
            $status =fwrite ($bf,start_tag("BRANCHES",6,true));
            //Iterate over each element
            foreach ($languagelesson_seenbranches as $branch) {
                //Start branch
                $status =fwrite ($bf,start_tag("BRANCH",7,true));
                //Print branch contents
                fwrite ($bf,full_tag("USERID",8,false,$branch->userid));
                fwrite ($bf,full_tag("RETRY",8,false,$branch->retry));
                fwrite ($bf,full_tag("FLAG",8,false,$branch->flag));
                fwrite ($bf,full_tag("TIMESEEN",8,false,$branch->timeseen));
                // END BRANCH
                $status =fwrite ($bf,end_tag("BRANCH",7,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("BRANCHES",6,true));
        }
        return $status;
    }

   //Backup languagelesson_timer contents (executed from backup_lesson_mods)
    function backup_languagelesson_timer ($bf,$preferences,$lessonid) {

        global $CFG;

        $status = true;

        $times = get_records("languagelesson_timer", "lessonid", $lessonid);

        //If there is times
        if ($times) {
            //Write start tag
            $status =fwrite ($bf,start_tag("TIMES",4,true));
            //Iterate over each time
            foreach ($times as $time) {
                //Start time
                
                
                $status =fwrite ($bf,start_tag("TIME",5,true));
                //Print time contents
                fwrite ($bf,full_tag("USERID",6,false,$time->userid));
                fwrite ($bf,full_tag("STARTTIME",6,false,$time->starttime));
                fwrite ($bf,full_tag("LESSONTIME",6,false,$time->lessontime));
                //End time
                $status =fwrite ($bf,end_tag("TIME",5,true));
            }
            //Write end tag
            $status =fwrite ($bf,end_tag("TIMES",4,true));
        }
        return $status;
    }
    
    // backup languagelesson_default contents (executed from backup_lesson_mods)
    function backup_languagelesson_default ($bf,$preferences) {
        global $CFG;

        $status = true;

        //only one default record per course
        $default = get_record("languagelesson_default", "course", $preferences->backup_course);
        if ($default) {
            //Start mod
            $status =fwrite ($bf,start_tag("DEFAULTS",4,true));            
            //Print default data
            fwrite ($bf,full_tag("TYPE",5,false,$default->type));
            fwrite ($bf,full_tag("CONDITIONS",5,false,$default->conditions));
            fwrite ($bf,full_tag("DEFAULTPOINTS",5,false,$default->defaultpoints));
            fwrite ($bf,full_tag("GRADE",5,false,$default->grade));
            fwrite ($bf,full_tag("SHOWONGOINGSCORE",5,false,$default->showongoingscore));
			fwrite ($bf,full_tag("SHOWOLDANSWER",5,false,$lesson->showoldanswer));
            fwrite ($bf,full_tag("MAXATTEMPTS",5,false,$default->maxattempts));
			fwrite ($bf,full_tag("PENALTY",5,false,$lesson->penalty));
			fwrite ($bf,full_tag("PENALTYTYPE",5,false,$lesson->penaltytype));
			fwrite ($bf,full_tag("PENALTYVALUE",5,false,$lesson->penaltyvalue));
            fwrite ($bf,full_tag("DEFAULTFEEDBACK",5,false,$default->defaultfeedback));
			fwrite ($bf,full_tag("DEFAULTCORRECT",5,false,$lesson->defaultcorrect));
			fwrite ($bf,full_tag("DEFAULTWRONG",5,false,$lesson->defaultwrong));
            fwrite ($bf,full_tag("AUTOGRADE",5,false,$default->autograde));
			fwrite ($bf,full_tag("SHUFFLEANSWERS",5,false,$lesson->shuffleanswers));
            fwrite ($bf,full_tag("TIMED",5,false,$default->timed));
            fwrite ($bf,full_tag("MAXTIME",5,false,$default->maxtime));
            fwrite ($bf,full_tag("MEDIAHEIGHT",5,false,$default->mediaheight));
            fwrite ($bf,full_tag("MEDIAWIDTH",5,false,$default->mediawidth));
            fwrite ($bf,full_tag("DISPLAYLEFT",5,false,$default->displayleft));
            fwrite ($bf,full_tag("CONTEXTCOLORS",5,false,$default->contextcolors));
            fwrite ($bf,full_tag("PROGRESSBAR",5,false,$default->progressbar));
            $status =fwrite ($bf,end_tag("DEFAULTS",4,true));
        }
        return $status;  
    }
    
    //Return an array of info (name,value)
    function languagelesson_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += languagelesson_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        //First the course data
        $info[0][0] = get_string("modulenameplural","languagelesson");
        if ($ids = languagelesson_ids($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[1][0] = get_string("attempts","languagelesson");
            if ($ids = languagelesson_attempts_ids_by_course ($course)) { 
                $info[1][1] = count($ids);
            } else {
                $info[1][1] = 0;
            }
        }
        return $info;
    }

    //Return an array of info (name,value)
    function languagelesson_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'1'][0] = get_string("attempts","languagelesson");
            if ($ids = languagelesson_attempts_ids_by_instance ($instance->id)) { 
                $info[$instance->id.'1'][1] = count($ids);
            } else {
                $info[$instance->id.'1'][1] = 0;
            }
        }
        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function languagelesson_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of lessons
        $buscar="/(".$base."\/mod\/languagelesson\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@LANGUAGELESSONINDEX*$2@$',$content);

        //Link to lesson view by moduleid
        $buscar="/(".$base."\/mod\/languagelesson\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@LANGUAGELESSONVIEWBYID*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of lesson id 
    function languagelesson_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT l.id, l.course
                                 FROM {$CFG->prefix}languagelesson l
                                 WHERE l.course = '$course'");
    }
    
    //Returns an array of languagelesson_submissions id
    function languagelesson_attempts_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id , a.lessonid
                                 FROM {$CFG->prefix}languagelesson_attempts a,
                                      {$CFG->prefix}languagelesson l
                                 WHERE l.course = '$course' AND
                                       a.lessonid = l.id");
    }

    //Returns an array of languagelesson_submissions id
    function languagelesson_attempts_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT a.id , a.lessonid
                                 FROM {$CFG->prefix}languagelesson_attempts a
                                 WHERE a.lessonid = $instanceid");
    }
?>
