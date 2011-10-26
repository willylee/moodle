<?php // $Id: continue.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for processing page answers by users
 *
 * @version $Id: continue.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    require_sesskey();

    require_once($CFG->dirroot.'/mod/languagelesson/pagelib.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/locallib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/lib.php');

/////////////////////////////////////////////////////
// TIMER CHECK
/////////////////////////////////////////////////////
	///  @TIMER@ ///
    // This is the code updates the lesson time for a timed test
    // get time information for this user
    $timer = new stdClass;
    if (!has_capability('mod/languagelesson:manage', $context)) {
        if (!$timer = get_records_select('languagelesson_timer', "lessonid = $lesson->id AND userid = $USER->id", 'starttime')) {
            error('Error: could not find records');
        } else {
            $timer = array_pop($timer); // this will get the latest start time record
        }
        
        if ($lesson->timed) {
            $timeleft = ($timer->starttime + $lesson->maxtime * 60) - time();

            if ($timeleft <= 0) {
                // Out of time
                languagelesson_set_message(get_string('eolstudentoutoftime', 'languagelesson'));
                redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=".LL_EOL."&outoftime=normal");
                die; // Shouldn't be reached, but make sure
            } else if ($timeleft < 60) {
                // One minute warning
                languagelesson_set_message(get_string("studentoneminwarning", "languagelesson"));
            }
        }
        
        $timer->lessontime = time();
        if (!update_record("languagelesson_timer", $timer)) {
            error("Error: could not update lesson_timer table");
        }
	}
////////////////////////////////////////////////////
// END TIMER CHECK
/////////////////////////////////////////////////////

    // record answer (if necessary) and show response (if none say if answer is correct or not)
    $thispageid = required_param('pageid', PARAM_INT);
    if (!$page = get_record("languagelesson_pages", "id", $thispageid)) {
        error("Continue: Page record not found");
    }
    // set up some defaults
    $answerid        = 0;
    $noanswer        = false;
    $correctanswer   = false;
    $isessayquestion = false;   // use this to turn off review button on essay questions
    $newpageid       = 0;       // stay on the page
    $studentanswer   = '';      // use this to store student's answer(s) in order to display it on feedback page
    
    



    
///////////////////////////////////////////////////////////
// CHECK NUMBER OF ATTEMPTS ON QUESTION
///////////////////////////////////////////////////////////

	$skip_record_changing = false;
	$showfeedback  = false; // Flag to mark if there is feedback to show for this submission

	/// check if the student has maxed out their attempts on this question
	if ($lesson->maxattempts > 0) { // if maxattempts is 0, attempts are unlimited
		$nattempts = count_records("languagelesson_attempts", "pageid", $page->id, "userid", $USER->id);
		if ($nattempts >= $lesson->maxattempts) {
			$skip_record_changing = true;
		}
	}

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////







///////////////////////////////////////////////////////////
// PULL AND CHECK USER-SUBMITTED ANSWERS
///////////////////////////////////////////////////////////
	
	// init this here, because not every situation sets it, but it is checked below
	$response = '';
	
    switch ($page->qtype) {
    
    
    
    
    
    
         case LL_ESSAY :
            $isessayquestion = true;
            if (!$useranswer = $_POST['answer']) {
                $noanswer = true;
                break;
            }
			
			$manualattempt = new stdClass();
			$manualattempt->lessonid = $lesson->id;
			$manualattempt->userid = $USER->id;
			$manualattempt->pageid = $page->id;
			$manualattempt->type = LL_ESSAY;

            $useranswer = clean_param($useranswer, PARAM_RAW);
			$manualattempt->essay = $useranswer;
			
			// if the student had previously submitted an attempt on this question, and it has since been graded,
			// mark this new submission as a resubmit
			if ($prevAttempt = languagelesson_get_most_recent_attempt_on($page->id, $USER->id)) {
				if (! $oldManAttempt = get_record('languagelesson_manattempts', 'attemptid', $prevAttempt->id)) {
					error('Failed to fetch matching manual_attempt record for old attempt on this question!');
				}
				if ($oldManAttempt->graded && !$lesson->autograde) {
					$manualattempt->resubmit = 1;
					$manualattempt->graded = 0;
				}
			}
        
            if (!$answer = get_record("languagelesson_answers", "pageid", $page->id)) {
                error("Continue: No answer found");
            }
            $correctanswer = false;
			$answerid = $answer->id;
			$newpageid = $answer->jumpto;
            
            /// 1/4/11 ///
        /////////////////////////////////////////////////
        // AUTOMATIC GRADING
            /// if this lesson is to be auto-graded...
			if ($lesson->autograde) {
				$correctanswer = true;
			  /// flag it as graded
				$manualattempt->graded = 1;
			  /// set the grade to the maximum point value for this question
				$maxscore = get_record('languagelesson_answers','id',$answerid);
				$maxscore = $maxscore->score;
				$score = $maxscore;
			}
		/////////////////////////////////////////////////
			/// if it's not, mark these submissions as ungraded
			else {
				$score = 0;
			}
			
            break;
            
            
            
            
            
            
            
            
            
         case LL_SHORTANSWER :
            if (isset($_POST['answer'])) {
                $useranswer = $_POST['answer'];
            } else {
                $noanswer = true;
                break;
            }            
			$correctanswer = false;
            $useranswer = s(stripslashes(clean_param($useranswer, PARAM_RAW)));
            $userresponse = addslashes($useranswer);
            if (!$answers = get_records("languagelesson_answers", "pageid", $page->id)) {
                error("Continue: No answers found");
            }
            $i=0;
            foreach ($answers as $answer) {
                $i += 1;
                $expectedanswer  = $answer->answer; // for easier handling of $answer->answer
                $ismatch         = false; 
                $markit          = false; 
                $useregexp       = false;

                if ($page->qoption) {
                    $useregexp = true;
                }
                
                if (!$useregexp) { //we are using 'normal analysis', which ignores case
                    $ignorecase = '';
                    if ( substr($expectedanswer,strlen($expectedanswer) - 2, 2) == '/i') {
                        $expectedanswer = substr($expectedanswer,0,strlen($expectedanswer) - 2);
                        $ignorecase = 'i';
                    }
                } else {
                    $expectedanswer = str_replace('*', '#####', $expectedanswer);
                    $expectedanswer = preg_quote($expectedanswer, '/');
                    $expectedanswer = str_replace('#####', '.*', $expectedanswer);
                }
                // see if user typed in any of the correct answers
				if (!$useregexp) { //we are using 'normal analysis'
					 // see if user typed in any of the wrong answers; don't worry about case
					 if (preg_match('/^'.$expectedanswer.'$/i',$useranswer)) {
						 $ismatch = true;
					 }
				} else { // we are using regular expressions analysis
					 $startcode = substr($expectedanswer,0,2);
					 switch ($startcode){
						 //1- check for absence of required string in $useranswer (coded by initial '--')
						 case "--":
							 $expectedanswer = substr($expectedanswer,2);
							 if (!preg_match('/^'.$expectedanswer.'$/'.$ignorecase,$useranswer)) {
								 $ismatch = true;
							 }
							 break;                                      
						 //2- check for code for marking wrong strings (coded by initial '++')
						 case "++":
							 $expectedanswer=substr($expectedanswer,2);
							 $markit = true;
							 //check for one or several matches
							 if (preg_match_all('/'.$expectedanswer.'/'.$ignorecase,$useranswer, $matches)) {
								 $ismatch   = true;
								 $nb        = count($matches[0]);
								 $original  = array(); 
								 $marked    = array();
								 $fontStart = '<span class="incorrect matches">';
								 $fontEnd   = '</span>';
								 for ($i = 0; $i < $nb; $i++) {
									 array_push($original,$matches[0][$i]);
									 array_push($marked,$fontStart.$matches[0][$i].$fontEnd);
								 }
								 $useranswer = str_replace($original, $marked, $useranswer);
							 }
							 break;
						 //3- check for wrong answers belonging neither to -- nor to ++ categories 
						 default:
							 if (preg_match('/^'.$expectedanswer.'$/'.$ignorecase,$useranswer, $matches)) {
								 $ismatch = true;
							 }
							 break;
					 }
                }
                if ($ismatch) {
                    $newpageid = $answer->jumpto;
                    if (trim(strip_tags($answer->response))) {
                        $response = $answer->response;
                    }
                    $answerid = $answer->id;
					$correctanswer = true;
					$score = $answer->score;
                    break; // quit answer analysis immediately after a match has been found
                }
            }
			// if the score hasn't yet been set, the answer that they put in matches none of the answers in the database, so mark it as
			// completely wrong
			if (!isset($score)) { $score = 0; }
            $studentanswer = $useranswer;
            break;
        
        
        


        


		case LL_CLOZE :
			// pull the array of answers submitted by the user
			if (isset($_POST['answer'])) {
				$useranswers = $_POST['answer'];
			} else {
				$noanswer = true;
				break;
			}

			// pull the array of correct answers, keyed to their question number
            if (!$answers = get_records_select("languagelesson_answers", "pageid=$page->id and not isnull(answer)")) {
                error("Continue: No answers found");
            }
			$keyedAnswers = languagelesson_key_cloze_answers($answers);

			// pull the page responses as well, for use in determining $newpageid
			if (!$responses = get_records_select('languagelesson_answers', "pageid=$page->id
																			and not isnull(response)
																			and (isnull(answer)
																				 or answer='')")) {
				error("Continue: No feedback records found");
			}
			foreach ($responses as $rspns) {
				if (! empty($rspns->answer)) { continue; }
				if ($rspns->score > 0) { $correctresponse = $rspns; }
				else { $wrongresponse = $rspns; }
			}

			// compare the answers
			$score = 0;
			$correctanswer = true;
			foreach ($keyedAnswers as $qnum => $answer) {
				// if they didn't answer the question, mark it as wrong and move on
				if (! isset($useranswers[$qnum])) {
					$correctanswer = false;
					continue;
				}
				
				$useranswer = $useranswers[$qnum];
				// if the page is not set to be case-sensitive, force lower case on both strings
				if (! $page->qoption) {
					$useranswer = strtolower($useranswer);
					$answer->answer = strtolower($answer->answer);
				}
				// if the answer is a fill-in-the-blank, do a straight string comparison
				if (!$answer->flags) {
					if (trim($useranswer) == trim($answer->answer)) {
						$score += $answer->score;
					} else {
						$correctanswer = false;
					}
				// otherwise, comma-splode it, find the right answer (marked with =) and check it
				} else {
					$options = explode(',', $answer->answer);
					// trim the options
					foreach ($options as $key => $val) { $options[$key] = trim($val); }
					// find the correct one
					foreach ($options as $val) {
						if ($val[0] == '=') {
							$correct = substr($val,1); // get rid of the '='
							break;
						}
					}
					// make sure there WAS a correct one
					if (! isset($correct)) { error('Continue: no correct answer found on cloze-type drop-down question!'); }
					// and now do string comparison
					if (trim($useranswer) == trim($correct)) {
						$score += $answer->score;
					} else {
						$correctanswer = false;
					}
				}
			}

			// determine the newpageid and answerid
			if ($correctanswer) {
				$newpageid = $correctresponse->jumpto;
				$answerid = $correctresponse->id;
				if (! empty($correctresponse->response)) {
					$response = $correctresponse->response;
				}
			} else {
				$newpageid = $wrongresponse->jumpto;
				$answerid = $wrongresponse->id;
				if (! empty($wrongresponse->response)) {
					$response = $wrongresponse->response;
				}
			}

			// and save their answers in serialized format for later retrieval
			$userresponse = serialize($useranswers);

			break;




        
        
        
        
        case LL_TRUEFALSE :
            if (empty($_POST['answerid'])) {
                $noanswer = true;
                break;
            }
            $answerid = required_param('answerid', PARAM_INT); 
            if (!$answer = get_record("languagelesson_answers", "id", $answerid)) {
                error("Continue: answer record not found");
            } 
            if (languagelesson_iscorrect($page->id, $answer->jumpto)) {
                $correctanswer = true;
            }
			if ($answer->score > 0) {
				$correctanswer = true;
			} else {
				$correctanswer = false;
			}
			$score = $answer->score;
            $newpageid = $answer->jumpto;
            $response  = trim($answer->response);
            $studentanswer = $answer->answer;
            break;
        
        
        
        
        
        
        
        case LL_MULTICHOICE :
            if ($page->qoption) {
                // MULTIANSWER allowed, user's answer is an array
                if (isset($_POST['answer'])) {
                    $useranswers = $_POST['answer'];
                    foreach ($useranswers as $key => $useranswer) {
                        $useranswers[$key] = clean_param($useranswer, PARAM_INT);
                    }
                } else {
                    $noanswer = true;
                    break;
                }
                // get what the user answered
                $userresponse = implode(",", $useranswers);
                // get the answers in a set order, the id order
                if (!$answers = get_records("languagelesson_answers", "pageid", $page->id)) {
                    error("Continue: No answers found");
                }
                $ncorrect = 0;
                $nhits = 0;
                $correctresponse = '';
                $wrongresponse = '';
                $correctanswerid = 0;
                $wronganswerid = 0;
				$score = 0;
                // store student's answers for displaying on feedback page
                foreach ($answers as $answer) {
                    foreach ($useranswers as $key => $answerid) {
                        if ($answerid == $answer->id) {
                            $studentanswer .= '<br />'.$answer->answer;
                        }
                    }
                }
                // If score on answer is positive, it is correct                    
				$ncorrect = 0;
				$nhits = 0;
				foreach ($answers as $answer) {
					if ($answer->score > 0) {
						$ncorrect++;
						$score += $answer->score;
				
						foreach ($useranswers as $key => $answerid) {
							if ($answerid == $answer->id) {
							   $nhits++;
							}
						}
						// save the first jumpto page id, may be needed!...
						if (!isset($correctpageid)) {  
							// leave in its "raw" state - will converted into a proper page id later
							$correctpageid = $answer->jumpto;
						}
						// save the answer id for scoring
						if ($correctanswerid == 0) {
							$correctanswerid = $answer->id;
						}
						// ...also save any response from the correct answers...
						if (trim(strip_tags($answer->response))) {
							$correctresponse = $answer->response;
						}
					} else {
						// save the first jumpto page id, may be needed!...
						if (!isset($wrongpageid)) {   
							// leave in its "raw" state - will converted into a proper page id later
							$wrongpageid = $answer->jumpto;
						}
						// save the answer id for scoring
						if ($wronganswerid == 0) {
							$wronganswerid = $answer->id;
						}
						// ...and from the incorrect ones, don't know which to use at this stage
						if (trim(strip_tags($answer->response))) {
							$wrongresponse = $answer->response;
						}
					}
				}                    
                if ((count($useranswers) == $ncorrect) and ($nhits == $ncorrect)) {
                    $correctanswer = true;
                    $response  = $correctresponse;
                    $newpageid = $correctpageid;
                    $answerid  = $correctanswerid;
                } else {
                    $response  = $wrongresponse;
                    $newpageid = $wrongpageid;
                    $answerid  = $wronganswerid;
                }
            } else {
                // only one answer allowed
                if (empty($_POST['answerid'])) {
                    $noanswer = true;
                    break;
                }
                $answerid = required_param('answerid', PARAM_INT); 
                if (!$answer = get_record("languagelesson_answers", "id", $answerid)) {
                    error("Continue: answer record not found");
                }
                if (languagelesson_iscorrect($page->id, $answer->jumpto)) {
                    $correctanswer = true;
                }
				if ($answer->score > 0) {
					$correctanswer = true;
				} else {
					$correctanswer = false;
				}
				$score = $answer->score;
                $newpageid = $answer->jumpto;
                $response  = trim($answer->response);
                $studentanswer = $answer->answer;
            }
            break;
            
            
            
            
            
            
            
        case LL_MATCHING :
            if (isset($_POST['response']) && is_array($_POST['response'])) { // only arrays should be submitted
                $response = array();
                foreach ($_POST['response'] as $key => $value) {
                    $response[$key] = stripslashes($value);
                }
            } else {
                $noanswer = true;
                break;
            }

            if (!$answers = get_records("languagelesson_answers", "pageid", $page->id)) {
                error("Continue: No answers found");
            }

            $ncorrect = 0;
            $i = 0;
            foreach ($answers as $answer) {
                if ($i == count($answers)-2 || $i == count($answers)-1) {
                    // ignore last two answers, they are correct response
                    // and wrong response
                    $i++;
                    continue;
                }
                if ($answer->response == $response[$answer->id]) {
                    $ncorrect++;
                }
                if ($i == 2) {
                    $correctpageid = $answer->jumpto;
                    $correctanswerid = $answer->id;
                }
                if ($i == 3) {
                    $wrongpageid = $answer->jumpto;
                    $wronganswerid = $answer->id;                        
                }
                $i++;
            }
            // get he users exact responses for record keeping
			$score = 0;
            $userresponse = array();
            foreach ($response as $key => $value) {
                foreach($answers as $answer) {
                    if ($value == $answer->response) {
                        $userresponse[] = $answer->id;
						$score += $answer->score;
                    }
                }
                $studentanswer .= '<br />'.$answers[$key]->answer.' = '.$value;
            }
            $userresponse = implode(",", $userresponse);

            $response = '';
            if ($ncorrect == count($answers)-2) {  // dont count correct/wrong responses in the total.
                foreach ($answers as $answer) {
                    if ($answer->response == NULL && $answer->answer != NULL) {
                        $response = $answer->answer;
                        break;
                    }
                }
                if (isset($correctpageid)) {
                    $newpageid = $correctpageid;
                }
                if (isset($correctanswerid)) {
                    $answerid = $correctanswerid;
                }
                $correctanswer = true;
            } else {
                $t = 0;
                foreach ($answers as $answer) {
                    if ($answer->response == NULL && $answer->answer != NULL) {
                        if ($t == 1) {
                            $response = $answer->answer;
                            break;
                        }
                        $t++;
                    }
                }
                $newpageid = $wrongpageid;
                $answerid = $wronganswerid;
            }
            break;







        /*case LL_NUMERICAL :
            // set defaults
            $response = '';
            $newpageid = 0;

            if (isset($_POST['answer'])) {
                $useranswer = (float) optional_param('answer');  // just doing default PARAM_CLEAN, not doing PARAM_INT because it could be a float
            } else {
                $noanswer = true;
                break;
            }
            $studentanswer = $userresponse = $useranswer;
            if (!$answers = get_records("languagelesson_answers", "pageid", $page->id)) {
                error("Continue: No answers found");
            }
            foreach ($answers as $answer) {
                if (strpos($answer->answer, ':')) {
                    // there's a pairs of values
                    list($min, $max) = explode(':', $answer->answer);
                    $minimum = (float) $min;
                    $maximum = (float) $max;
                } else {
                    // there's only one value
                    $minimum = (float) $answer->answer;
                    $maximum = $minimum;
                }
                if (($useranswer >= $minimum) and ($useranswer <= $maximum)) {
                    $newpageid = $answer->jumpto;
                    $response = trim($answer->response);
                    if (languagelesson_iscorrect($page->id, $newpageid)) {
                        $correctanswer = true;
                    }
					if ($answer->score > 0) {
						$correctanswer = true;
					} else {
						$correctanswer = false;
					}
                    $answerid = $answer->id;
                    break;
                }
            }
            break;*/






        case LL_BRANCHTABLE:
            $noanswer = false;
            $newpageid = optional_param('jumpto', NULL, PARAM_INT);
            // going to insert into languagelesson_seenbranches                
            if ($newpageid == LL_RANDOMBRANCH) {
                $branchflag = 1;
            } else {
                $branchflag = 0;
            }
            if ($grades = get_records_select("languagelesson_grades", "lessonid = $lesson->id AND userid = $USER->id",
                        "grade DESC")) {
                $retries = count($grades);
            } else {
                $retries = 0;
            }
            $branch = new stdClass;
            $branch->lessonid = $lesson->id;
            $branch->userid = $USER->id;
            $branch->pageid = $page->id;
            $branch->retry = $retries;
            $branch->flag = $branchflag;
            $branch->timeseen = time();
        
            if (!insert_record("languagelesson_seenbranches", $branch)) {
                error("Error: could not insert row into languagelesson_seenbranches table");
            }

            //  this is called when jumping to random from a branch table
            if($newpageid == LL_UNSEENBRANCHPAGE) {
                if (has_capability('mod/languagelesson:manage', $context)) {
                     $newpageid = LL_NEXTPAGE;
                } else {
                     $newpageid = languagelesson_unseen_question_jump($lesson->id, $USER->id, $page->id);  // this may return 0
                }
            }
            // convert jumpto page into a proper page id
            if ($newpageid == 0) {
                $newpageid = $page->id;
            } elseif ($newpageid == LL_NEXTPAGE) {
                if (!$newpageid = $page->nextpageid) {
                    // no nextpage go to end of lesson
                    $newpageid = LL_EOL;
                }
            } elseif ($newpageid == LL_PREVIOUSPAGE) {
                $newpageid = $page->prevpageid;
            } elseif ($newpageid == LL_RANDOMPAGE) {
                $newpageid = languagelesson_random_question_jump($lesson->id, $page->id);
            } elseif ($newpageid == LL_RANDOMBRANCH) {
                $newpageid = languagelesson_unseen_branch_jump($lesson->id, $USER->id);
            }
            // no need to record anything in lesson_attempts
            $skip_record_changing = true;
            break;





            
        case LL_AUDIO:
        case LL_VIDEO:
			// all attempt record handling is done in upload function, so don't need to do anything here

        	$newpageid = get_field('languagelesson_pages', 'nextpageid', 'id', $page->id);
			$correctanswer = true;
       		
            // mark that we don't need to make any changes in languagelesson_attempts
            $skip_record_changing = true;
            break;
         
        
    }

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////






///////////////////////////////////////////////////////////
// HANDLE LESSON ATTEMPTS AND UPDATE GRADE
///////////////////////////////////////////////////////////

	/// if they didn't submit an answer at all, kick them back to the same page
	if ($noanswer) {
		$newpageid = $page->id; // display same page again
		$feedback  = get_string('noanswer', 'languagelesson');
	} else {
		/// if this isn't a student, there's no reason to touch the tables
		if (!has_capability('mod/languagelesson:manage', $context)) {
			/// If we don't need to change attempts records, don't do so
			if (!$skip_record_changing) {

				// pull the retry value for this attempt, and handle deflagging former current attempt 
				if ($oldAttempt = languagelesson_get_most_recent_attempt_on($page->id, $USER->id)) {
					$nretakes = $oldAttempt->retry + 1;

					// update the old attempt to no longer be marked as the current one
					$uattempt = new stdClass;
					$uattempt->id = $oldAttempt->id;
					$uattempt->iscurrent = 0;

					if (! update_record('languagelesson_attempts', $uattempt)) {
						error('Failed to deflag former current attempt!');
					}
				} else { $nretakes = 0; }
				
				// record student's attempt
				$attempt = new stdClass;
				$attempt->lessonid = $lesson->id;
				$attempt->pageid = $page->id;
				$attempt->userid = $USER->id;
				$attempt->answerid = $answerid;
				$attempt->retry = $nretakes;
				// flag this as the current attempt
				$attempt->iscurrent = 1;
				$attempt->correct = $correctanswer;
				$attempt->score = $score;
				if(isset($userresponse)) {
					$attempt->useranswer = $userresponse;
				}
				$attempt->timeseen = time();

			/// every try is recorded as a new one (by increasing retry value), so just insert this one
				if (!$newattemptid = insert_record("languagelesson_attempts", $attempt)) {
					error("Continue: attempt not inserted");
				}
				
			/// if it's an essay question, handle the manual attempt record
			/// (NOTE that audio/video manual attempt records are handled in file uploading functions)
				if ($isessayquestion) {
				/// save the manual attempt record
					$manualattempt->attemptid = $newattemptid;
					$manualattempt->timeseen = time();
					if (!$manattemptid = insert_record('languagelesson_manattempts', $manualattempt)) {
						error("Continue: manual attempt not inserted.");
					}
					
				/// and log its ID in the attempt record
					$attempt = get_record('languagelesson_attempts', 'id', $newattemptid);
					$attempt->manattemptid = $manattemptid;
					if (!$update = update_record('languagelesson_attempts', $attempt)) {
						error("Continue: failed to note manual attempt id in attempt record.");
					}
				}
				
				
			} // </if $skip_record_changing>
			
			
		/// and update the languagelesson's grade
		/// NOTE that this happens no matter the question type
			if ($lesson->type != LL_TYPE_PRACTICE) {
			/// get the lesson's graded information
				$gradeinfo = languagelesson_grade($lesson);
				
			/// build the grade object
				$grade->lessonid = $lesson->id;
				$grade->userid = $USER->id;
				$grade->grade = $gradeinfo->grade;
				
			/// and update the old grade record, if there is one; if not, insert the record
				if ($oldgrade = get_record("languagelesson_grades", "lessonid", $lesson->id,
										   "userid", $USER->id)) {
					/// if the old grade was for a completed lesson attempt, update the completion time
					if ($oldgrade->completed) { $grade->completed = time(); }
					$grade->id = $oldgrade->id;
					if (!$update = update_record("languagelesson_grades", $grade)) {
						error("Navigation: grade not updated");
					}
				} else {
					if (!$newgradeid = insert_record("languagelesson_grades", $grade)) {
						error("Navigation: grade not inserted");
					}
				}
				
			/// finally, update the records in the gradebook
				languagelesson_update_grades($lesson, $USER->id);
			}
			
			
		} // </if !hascapability(languagelesson_manage) >
		
		
	} // </if !$noanswer>

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////

    

    
///////////////////////////////////////////////////////////
// HANDLE FEEDBACK
///////////////////////////////////////////////////////////

	// Determine if we should display feedback
	if (($response || $lesson->defaultfeedback)
			&& $page->qtype != LL_BRANCHTABLE) {
		$showfeedback = true;
		// if so, we should also feed in the next page (irrespective of the correctness of the attempt) for the "continue" button
		$newpageid = LL_NEXTPAGE;
	}

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////




///////////////////////////////////////////////////////////
// NEXTPAGE HANDLING
///////////////////////////////////////////////////////////

	// ignore all of this if the page is a branchtable
	if ($page->qtype != LL_BRANCHTABLE) {

		// if this is a test lesson and is a normal page, they should always be moved to the next page, irrespective of if they got it
		// right or not
		if ($lesson->type == LL_TYPE_TEST) { $newpageid = $page->nextpageid; }

		if ($newpageid == 0) {
			$newpageid = $page->id;
		} elseif (!$newpageid = $page->nextpageid) {
			// no nextpage go to end of lesson
			$newpageid = LL_EOL;
		} elseif ($newpageid != LL_CLUSTERJUMP && $page->id != 0 && $newpageid > 0) { //going to check to see if the page that the user is
																					  //going to view next, is a cluster page. If so, dont
																					  //display, go into the cluster.  The $newpageid > 0
																					  //is used to filter out all the negative code jumps.
			if (!$page = get_record("languagelesson_pages", "id", $newpageid)) {
				error("Error: could not find page");
			}
			if ($page->qtype == LL_CLUSTER) {
				$newpageid = languagelesson_cluster_jump($lesson->id, $USER->id, $page->id);
			} elseif ($page->qtype == LL_ENDOFCLUSTER) {
				$jump = get_field("languagelesson_answers", "jumpto", "pageid", $page->id, "lessonid", $lesson->id);
				if ($jump == LL_NEXTPAGE) {
					if ($page->nextpageid == 0) {
						$newpageid = LL_EOL;
					} else {
						$newpageid = $page->nextpageid;
					}
				} else {
					$newpageid = $jump;
				}
			}
		} elseif ($newpageid == LL_UNSEENBRANCHPAGE) {
			if (has_capability('mod/languagelesson:manage', $context)) {
				if ($page->nextpageid == 0) {
					$newpageid = LL_EOL;
				} else {
					$newpageid = $page->nextpageid;
				}
			} else {
				$newpageid = languagelesson_unseen_question_jump($lesson->id, $USER->id, $page->id);
			}            
		} elseif ($newpageid == LL_PREVIOUSPAGE) {
			$newpageid = $page->prevpageid;
		} elseif ($newpageid == LL_RANDOMPAGE) {
			$newpageid = languagelesson_random_question_jump($lesson->id, $page->id);
		} elseif ($newpageid == LL_CLUSTERJUMP) {
			if (has_capability('mod/languagelesson:manage', $context)) {
				if ($page->nextpageid == 0) {  // if teacher, go to next page
					$newpageid = LL_EOL;
				} else {
					$newpageid = $page->nextpageid;
				}            
			} else {
				$newpageid = languagelesson_cluster_jump($lesson->id, $USER->id, $page->id);
			}
		}

	}

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
  


///////////////////////////////////////////////////////////
// FINAL CHECKS
///////////////////////////////////////////////////////////
/// since lesson questions can be answered in arbitrary order, check if lesson is complete after each
/// submission--if so, and if the lesson hasn't been completed before (marked by the 'completed' field
/// in languagelesson_grades), jump to the EOL page; if it has, and we are marked to go to the EOL page,
/// redirect to view.php to handle the "Old Grade" page instead
	$nopageid = false;
	if (languagelesson_is_lesson_complete($lesson->id, $USER->id)) {
		if (!get_field('languagelesson_grades', 'completed', 'lessonid', $lesson->id, 'userid', $USER->id)) {
			$newpageid = LL_EOL;
		} else if ($newpageid == LL_EOL) {
			/// mark that we have completed it before, so let view just direct us to the "Old Grade"
			/// page by not giving it a pageid
			$nopageid = true;
		}
	}
	
/// if it's NOT complete, BUT the next page is found to be the EOL, then user jumped ahead in the lesson and just answered the last
/// question, so boot them back to the first one they haven't answered
	elseif ($newpageid == LL_EOL) {
		$newpageid = languagelesson_find_first_unanswered_pageid($lesson->id, $USER->id);
	}

/// if we are to show the viewer feedback on their submission, set up the required variables to display feedback output
	if ($showfeedback) {
		if (!has_capability('mod/languagelesson:manage', $context)) {
			$aid = $newattemptid;
		} else {
			$aid = $answerid;
			if (!$aid) {
				$atext = $userresponse;
			}
		}
    
/// finally, redirect to display feedback or not
		redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$thispageid"
				 ."&amp;showfeedback=1&amp;aid=$aid" . ((isset($atext)?"&amp;atext=$atext":''))
				 .(($nopageid) ? '' : "&amp;nextpageid=$newpageid"));
    } else if ($page->qtype == LL_AUDIO || $page->qtype == LL_VIDEO) {
		// if it's an audio or video, force showing same page again to confirm successful submission
		redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$thispageid"
				 .(($nopageid) ? '' : "&amp;nextpageid=$newpageid"));
	} else {
        // Don't display feedback
        redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id"
				 . (($nopageid) ? '' : "&amp;pageid=$newpageid"));
	}
    
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////

?>
