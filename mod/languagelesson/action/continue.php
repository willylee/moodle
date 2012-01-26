<?php // $Id: continue.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for processing page answers by users
 *
 * @version $Id: continue.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package languagelesson
 **/
    require_sesskey();

    require_once($CFG->dirroot.'/mod/languagelesson/pagelib.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/locallib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/lib.php');

    $thispageid = required_param('pageid', PARAM_INT);
    if (!$page = get_record("languagelesson_pages", "id", $thispageid)) {
        error("Continue: Page record not found");
    }

	$continuer = new LanguageLessonContinuer($context, $lesson, $page);
	$continuer->processSubmission();



class LanguageLessonContinuer {

    // set up some defaults
	public $lesson = null;
	public $page = null;
	public $userid = 0;

	private $isStudent = false;

	private $skipRecordChanging = false;  // Flag for if to skip changing any records in the DB
	private $showFeedback  = false;       // Flag to mark if there is feedback to show for this submission
    
    
	function __construct($context, $lesson, $page) {
		global $USER;
		$this->lesson = $lesson;
		// store the lessonid for easy access
		$this->lessonid = $lesson->id;
		$this->page = $page;
		// store the pageid for easy access
		$this->pageid = $page->id;
		$this->userid = $USER->id;
		$this->isStudent = ! has_capability('mod/languagelesson:manage', $context);
	}



	public function processSubmission() {

		// update the student's recorded time for this LL
		$this->updateTimer();

		// check if the student has maxed out their attempts on this question
		if ($this->lesson->maxattempts > 0) { // if maxattempts is 0, attempts are unlimited
			$nattempts = count_records("languagelesson_attempts", "pageid", $this->pageid, "userid", $this->userid);
			if ($nattempts >= $this->lesson->maxattempts) {
				$this->skipRecordChanging = true;
			}
		}

		// process the submitted answer(s)
		list($jumpValue, $answerData) = $this->processAnswers();

		// if they didn't submit an answer at all, reload the page and give them a warning 
		if (is_null($answerData)) {
			error_log("no answers submitted");
			$feedback  = get_string('noanswer', 'languagelesson');
			$this->redirect(null, null, true);
		}

		// if this isn't a student, there's no reason to touch the tables
		if ($this->isStudent) {
			// If we don't need to change attempts records, don't do so
			if (!$this->skipRecordChanging) {
				$attemptid = $this->recordAttempt($answerData);
			}
			// and update the languagelesson's grade
			// NOTE that this happens no matter the question type
			if ($this->lesson->type != LL_TYPE_PRACTICE) {
				// get the lesson's graded information
				$gradeinfo = languagelesson_grade($this->lesson);
				// save the grade
				languagelesson_save_grade($this->lessonid, $this->userid, $gradeinfo->grade);
				// finally, update the records in the gradebook
				languagelesson_update_grades($this->lesson, $this->userid);
			}
		}

		// Determine if we should display feedback
		if (($answerData->response || $this->lesson->defaultfeedback)
				&& $this->page->qtype != LL_BRANCHTABLE) {
			$this->showFeedback = true;
			// if so, we should also feed in the next page (irrespective of the correctness of the attempt) for the "continue" button
			$jumpValue = LL_NEXTPAGE;
		}

		// use the jump value and any override conditions to determine where the user should go next
		$newpageid = $this->findNextPage($jumpValue);

		// if no attempt was recorded, make attemptid empty
		if (! isset($attemptid)) { $attemptid = null; }

		$this->redirect($newpageid, $attemptid);
	}


	private function findNextPage($jumpValue) {
		$newpageid = $this->processJumpValue($jumpValue);

		// $newpageid override checks

		// since lesson questions can be answered in arbitrary order, check if lesson is complete after each
		// submission--if so, and if the lesson hasn't been completed before (marked by the 'completed' field
		// in languagelesson_grades), jump to the EOL page; if it has, and we are marked to go to the EOL page,
		// redirect to view.php to handle the "Old Grade" page instead
		if (languagelesson_is_lesson_complete($this->lessonid, $this->userid)) {
			if (!get_field('languagelesson_grades', 'completed', 'lessonid', $this->lessonid, 'userid', $this->userid)) {
				$newpageid = LL_EOL;
			} else if ($newpageid == LL_EOL) {
				/// mark that we have completed it before, so let view just direct us to the "Old Grade"
				/// page by not giving it a pageid
				$newpageid = null;
			}
		}
	
		// if it's NOT complete, BUT the next page is found to be the EOL, then user jumped ahead in the lesson and just answered the
		// last
		// question, so boot them back to the first one they haven't answered
		elseif ($newpageid == LL_EOL) {
			$newpageid = languagelesson_find_first_unanswered_pageid($this->lessonid, $this->userid);
		}

		return $newpageid;
	}



	private function processAnswers() {
		// init this here, as not all qtypes require it, but it is checked later
		$answerData = new stdClass;

		// init the bool checker of if the field will come in the form of an array or not (for checking accurately if it is empty)
		$isarray = false;
		switch ($this->page->qtype) {
			case LL_ESSAY:
			case LL_SHORTANSWER:
			case LL_CLOZE:
			case LL_TRUEFALSE:
			case LL_MULTICHOICE:
			//case LL_MATCHING:
			//case LL_NUMERICAL:
				// init stuff

				switch ($this->page->qtype) {
					case LL_ESSAY:
						$ext = 'Essay';
						$field = 'answer';
						break;
					case LL_SHORTANSWER:
						$ext = 'ShortAnswer';
						$field = 'answer';
						break;
					case LL_CLOZE: 
						$ext = 'Cloze'; 
						$field = 'answer';
						$isarray = true;
						break;
					case LL_TRUEFALSE:
						$ext = 'TrueFalse'; 
						$field = 'answerid';
						break;
					case LL_MULTICHOICE:
						$ext = 'Multichoice';
						$field = ($this->page->qoption ? 'answer' : 'answerid');
						if ($this->page->qoption) { $isarray = true; }
						break;
					/*case LL_MATCHING:
						$ext = 'Matching';
						$field = 'response';
						break;*/
					/*case LL_NUMERICAL:
						$ext = 'Numerical';
						$field = 'answer';
						break;*/
					default: break;
				}

				$funcname = "handle$ext";

				// if the answers are coming as an array, check if there are any answers actually contained in the array
				if ($isarray) {
					$foundData = false;
					foreach ($_POST[$field] as $key => $value) {
						if (! empty($value)) {
							$foundData = true;
							break;
						}
					}
				}

				// if answers were submitted, then process them
				if (isset($_POST[$field]) && 
						(!$isarray && ! empty($_POST[$field]))
						|| ($isarray && $foundData)) {
					list($jumpValue, $answerData) = $this->$funcname();
				// otherwise, return default empty values
				} else {
					$jumpValue = LL_THISPAGE;
					$answerData = null;
				}

				break;

			case LL_BRANCHTABLE:
				$jumpValue = $this->handleBranchTable();
				// no need to record anything in lesson_attempts
				$this->skipRecordChanging = true;
				break;
			case LL_AUDIO:
			case LL_VIDEO:
				// all attempt record handling is done in upload function, so don't need to do anything here
				$jumpValue = get_field('languagelesson_answers', 'jumpto', 'pageid', $this->pageid);
				$correctanswer = true;
				$this->skipRecordChanging = true;
				break;

			default:
				break;
		}

		return array($jumpValue, $answerData);

	}





	/**
	 * Update the user's recorded time spent doing this LL 
	 **/
	private function updateTimer() {
		$timer = new stdClass;
		if ($this->isStudent) {
			// PHP will throw a fatal error if the nested property is accessed in the string below, so pull this here
			if (!$timer = get_records_select('languagelesson_timer', "lessonid = $this->lessonid AND userid = $this->userid",
						'starttime')) {
				error('Error: could not find records');
			} else {
				$timer = array_pop($timer); // this will get the latest start time record
			}
			
			if ($this->lesson->timed) {
				$timeleft = ($timer->starttime + $this->lesson->maxtime * 60) - time();

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
	}





	private function recordAttempt($answerData) {
		// pull the retry value for this attempt, and handle deflagging former current attempt 
		if ($oldAttempt = languagelesson_get_most_recent_attempt_on($this->pageid, $this->userid)) {
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
		$attempt->lessonid = $this->lessonid;
		$attempt->pageid = $this->pageid;
		$attempt->userid = $this->userid;
		$attempt->answerid = $answerData->answerid;
		$attempt->retry = $nretakes;
		// flag this as the current attempt
		$attempt->iscurrent = 1;
		$attempt->correct = $answerData->correctanswer;
		$attempt->score = $answerData->score;
		if(isset($answerData->userresponse)) {
			$attempt->useranswer = $answerData->userresponse;
		}
		$attempt->timeseen = time();

		// every try is recorded as a new one (by increasing retry value), so just insert this one
		if (!$newattemptid = insert_record("languagelesson_attempts", $attempt)) {
			error("Continue: attempt not inserted");
		}
		
		// if it's an essay question, handle the manual attempt record
		// (NOTE that audio/video manual attempt records are handled in file uploading functions)
		if ($this->page->qtype == LL_ESSAY) {
			// pull the mostly-built manualattempt data record from the answerData
			$manualattempt = $answerData->manattempt;
			// save the manual attempt record
			$manualattempt->timeseen = time();
			if (!$manattemptid = insert_record('languagelesson_manattempts', $manualattempt)) {
				error("Continue: manual attempt not inserted.");
			}
			
			// and log its ID in the attempt record
			$uattempt = get_record('languagelesson_attempts', 'id', $newattemptid);
			$uattempt->manattemptid = $manattemptid;
			if (! update_record('languagelesson_attempts', $uattempt)) {
				error("Continue: failed to note manual attempt id in attempt record.");
			}
		}

		// return the ID of the attempt record changed
		if (isset($newattemptid)) { return $newattemptid; }
		else { return $uattempt->id; }
	}







	private function processJumpValue($jumpValue) {
		// init newpageid to empty value
		$newpageid = 0;

		// if this is a test lesson and is a normal page, they should always be moved to the next page, regardless  of whether
		// they got it right or not
		if ($this->lesson->type == LL_TYPE_TEST) { return $this->page->nextpageid; }

		switch ($jumpValue) {

			case LL_NEXTPAGE:
				$newpageid = ($this->page->nextpageid ? $this->page->nextpageid : LL_EOL);
				break;
			case LL_THISPAGE:
				$newpageid = $this->pageid;
				break;
			case LL_PREVIOUSPAGE:
				$newpageid = $this->page->prevpageid;
				break;
			case LL_RANDOMPAGE:
				$newpageid = languagelesson_random_question_jump($this->lessonid, $this->pageid);
				break;
			case LL_CLUSTERJUMP:
				if (! $this->isStudent) {
					if ($this->page->nextpageid == 0) {  // if teacher, go to next page
						$newpageid = LL_EOL;
					} else {
						$newpageid = $this->page->nextpageid;
					}            
				} else {
					$newpageid = languagelesson_cluster_jump($this->lessonid, $this->userid, $this->pageid);
				}
				break;
			case LL_UNSEENBRANCHPAGE:
				if (! $this->isStudent) {
					if ($this->page->nextpageid == 0) {
						$newpageid = LL_EOL;
					} else {
						$newpageid = $this->page->nextpageid;
					}
				} else {
					$newpageid = languagelesson_unseen_question_jump($this->lessonid, $this->userid, $this->pageid);
				}            
				break;
			// otherwise, it's an actual specific pageid, so just pass it in
			default:
				$newpageid = $jumpValue;
				break;
		}

		return $newpageid;
	}











	private function redirect($newpageid, $attemptid, $noanswer=false) {
		global $CFG, $cm;

		// if the user did not submit an answer, just refresh the page and tell them so
		if ($noanswer) {
			redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$this->pageid&amp;noanswer=1");
		}

		$nopageid = is_null($newpageid);

		// if we are to show the viewer feedback on their submission, set up the required variables to display feedback output
		if ($this->showFeedback) {
			if ($this->isStudent) {
				$aid = $attemptid;
			} else {
				$aid = $answerid;
				if (!$aid) {
					$atext = $userresponse;
				}
			}
		
		// finally, redirect to display feedback or not
			redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$this->pageid"
					 ."&amp;showfeedback=1&amp;aid=$aid" . ((isset($atext)?"&amp;atext=$atext":''))
					 .(($nopageid) ? '' : "&amp;nextpageid=$newpageid"));
		} else if ($this->page->qtype == LL_AUDIO || $this->page->qtype == LL_VIDEO) {
			// if it's an audio or video, force showing same page again to confirm successful submission
			redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$this->pageid"
					 .(($nopageid) ? '' : "&amp;nextpageid=$newpageid&amp;submitted=1"));
		} else {
			// Don't display feedback
			redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id"
					 . (($nopageid) ? '' : "&amp;pageid=$newpageid")
					 . (($newpageid == $this->pageid) ? "&amp;submitted=1" : ''));
		}

	}







	private function handleEssay() {
		$useranswer = $_POST['answer'];
		
		$manualattempt = new stdClass();
		$manualattempt->lessonid = $this->lessonid;
		$manualattempt->userid = $this->userid;
		$manualattempt->pageid = $this->pageid;
		$manualattempt->type = LL_ESSAY;

		$useranswer = clean_param($useranswer, PARAM_RAW);
		$manualattempt->essay = $useranswer;
		
		// if the student had previously submitted an attempt on this question, and it has since been graded,
		// mark this new submission as a resubmit
		if ($prevAttempt = languagelesson_get_most_recent_attempt_on($this->pageid, $this->userid)) {
			if (! $oldManAttempt = get_record('languagelesson_manattempts', 'id', $prevAttempt->manattemptid)) {
				error('Failed to fetch matching manual_attempt record for old attempt on this question!');
			}
			if ($oldManAttempt->graded && !$this->lesson->autograde) {
				$manualattempt->resubmit = 1;
				$manualattempt->viewed = 0;
				$manualattempt->graded = 0;
			}
		}
	
		if (!$answer = get_record("languagelesson_answers", "pageid", $this->pageid)) {
			error("Continue: No answer found");
		}
		$correctanswer = false;
		$answerid = $answer->id;
		$jumpValue = $answer->jumpto;
		
		// if this lesson is to be auto-graded, then grade it
		if ($this->lesson->autograde) {
			$correctanswer = true;
			// flag it as graded
			$manualattempt->graded = 1;
			$manualattempt->viewed = 1;
			// set the grade to the maximum point value for this question
			$maxscore = get_record('languagelesson_answers','id',$answerid);
			$maxscore = $maxscore->score;
			$score = $maxscore;
		}
		/// if it's not, mark these submissions as ungraded
		else {
			$score = 0;
		}

		$answerData = new stdClass;
		$answerData->answerid = $answerid;
		$answerData->correctanswer = $correctanswer;
		$answerData->score = $score;
		// save the manualattempt into the answerData
		$answerData->manattempt = $manualattempt;
		
		return array($jumpValue, $answerData);
	}



	private function handleShortAnswer() {
		$useranswer = $_POST['answer'];

		$correctanswer = false;
		$useranswer = s(stripslashes(clean_param($useranswer, PARAM_RAW)));
		$userresponse = addslashes($useranswer);
		if (!$answers = get_records("languagelesson_answers", "pageid", $this->pageid)) {
			error("Continue: No answers found");
		}
		$i=0;
		foreach ($answers as $answer) {
			$i += 1;
			$expectedanswer  = $answer->answer; // for easier handling of $answer->answer
			$ismatch         = false; 
			$markit          = false; 
			$useregexp       = false;

			if ($this->page->qoption) {
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
				$jumpValue = $answer->jumpto;
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
		// completely wrong and tell the jump to stay here
		if (!isset($score)) {
			$score = 0;
			$jumpValue = LL_THISPAGE;
		}

		$answerData = new stdClass;
		$answerData->answerid = (isset($answerid) ? $answerid : 0);
		$answerData->correctanswer = $correctanswer;
		$answerData->score = $score;
		$answerData->userresponse = $useranswer;
		if (isset($response)) { $answerData->response = $response; }
		
		return array($jumpValue, $answerData);
	}







	private function handleCloze() {
		// pull the array of answers submitted by the user
		$useranswers = $_POST['answer'];

		// pull the array of correct answers, keyed to their question number
		if (!$answers = get_records_select("languagelesson_answers", "pageid=$this->pageid and not isnull(answer)")) {
			error("Continue: No answers found");
		}
		$keyedAnswers = languagelesson_key_cloze_answers($answers);

		// pull the page responses as well, for use in determining $jumpValue
		if (!$responses = get_records_select('languagelesson_answers', "pageid=$this->pageid
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
			if (! $this->page->qoption) {
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

		// determine the jumpValue and answerid
		if ($correctanswer) {
			$jumpValue = $correctresponse->jumpto;
			$responseid = $correctresponse->id;
			if (! empty($correctresponse->response)) {
				$response = $correctresponse->response;
			}
		} else {
			$jumpValue = $wrongresponse->jumpto;
			$responseid = $wrongresponse->id;
			if (! empty($wrongresponse->response)) {
				$response = $wrongresponse->response;
			}
		}

		$answerData = new stdClass;
		$answerData->answerid = $responseid;
		$answerData->correctanswer = $correctanswer;
		$answerData->score = $score;
		$answerData->userresponse = serialize($useranswers);
		if (isset($response)) { $answerData->response = $response; }
		
		return array($jumpValue, $answerData);
	}







	private function handleTrueFalse() {
		$answerid = required_param('answerid', PARAM_INT); 
		if (!$answer = get_record("languagelesson_answers", "id", $answerid)) {
			error("Continue: answer record not found");
		} 
		if (languagelesson_iscorrect($this->pageid, $answer->jumpto)) {
			$correctanswer = true;
		}
		$correctanswer = ($answer->score > 0);

		$answerData = new stdClass;
		$answerData->answerid = $answerid;
		$answerData->score = $answer->score;
		$answerData->correctanswer = $correctanswer;
		if (! empty($answer->response)) { $answerData->response = $answer->response; }

		$jumpValue = $answer->jumpto;

		return array($jumpValue, $answerData);
	}




	private function handleMultichoice() {
		if ($this->page->qoption) {
			// MULTIANSWER allowed, user's answer is an array
			$useranswers = $_POST['answer'];
			foreach ($useranswers as $key => $useranswer) {
				$useranswers[$key] = clean_param($useranswer, PARAM_INT);
			}
			// get what the user answered
			$userresponse = implode(",", $useranswers);
			// get the answers in a set order, the id order
			if (!$answers = get_records("languagelesson_answers", "pageid", $this->pageid)) {
				error("Continue: No answers found");
			}
			$ncorrect = 0;
			$nhits = 0;
			$correctresponse = '';
			$wrongresponse = '';
			$correctanswerid = 0;
			$wronganswerid = 0;
			$score = 0;
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
				$jumpValue = $correctpageid;
				$answerid  = $correctanswerid;
			} else {
				$correctanswer = false;
				$response  = $wrongresponse;
				$jumpValue = $wrongpageid;
				$answerid  = $wronganswerid;
			}
		} else {
			// only one answer allowed
			$answerid = required_param('answerid', PARAM_INT); 
			if (!$answer = get_record("languagelesson_answers", "id", $answerid)) {
				error("Continue: answer record not found");
			}
			if (languagelesson_iscorrect($this->pageid, $answer->jumpto)) {
				$correctanswer = true;
			}
			if ($answer->score > 0) {
				$correctanswer = true;
			} else {
				$correctanswer = false;
			}
			$score = $answer->score;
			$jumpValue = $answer->jumpto;
			$response  = trim($answer->response);
		}

		$answerData = new stdClass;
		$answerData->answerid = $answerid;
		$answerData->correctanswer = $correctanswer;
		$answerData->score = $score;
		if (isset($userresponse)) { $answerData->userresponse = $userresponse; }
		if (isset($response)) { $answerData->response = $response; }
		
		return array($jumpValue, $answerData);
	}




	private function handleMatching() {
		if (is_array($_POST['response'])) { // only arrays should be submitted
			$response = array();
			foreach ($_POST['response'] as $key => $value) {
				$response[$key] = stripslashes($value);
			}
		} else {
			return array(LL_THISPAGE, null);
		}

		if (!$answers = get_records("languagelesson_answers", "pageid", $this->pageid)) {
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
		// get the user's exact responses for record keeping
		$score = 0;
		$userresponse = array();
		foreach ($response as $key => $value) {
			foreach($answers as $answer) {
				if ($value == $answer->response) {
					$userresponse[] = $answer->id;
					$score += $answer->score;
				}
			}
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
				$jumpValue = $correctpageid;
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
			$jumpValue = $wrongpageid;
			$answerid = $wronganswerid;
		}


		$answerData = new stdClass;
		$answerData->answerid = $answerid;
		$answerData->correctanswer = $correctanswer;
		$answerData->score = $score;
		$answerData->userresponse = $userresponse;
		if (isset($response)) { $answerData->response = $response; }
		
		return array($jumpValue, $answerData);
	}





	private function handleNumerical() {
		$useranswer = (float) optional_param('answer');

		if (!$answers = get_records("languagelesson_answers", "pageid", $this->pageid)) {
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
				$jumpValue = $answer->jumpto;
				$response = trim($answer->response);
				if (languagelesson_iscorrect($this->pageid, $jumpValue)) {
					$correctanswer = true;
				}
				if ($answer->score > 0) {
					$correctanswer = true;
				} else {
					$correctanswer = false;
				}
				$answerid = $answer->id;
				$score = $answer->score;
				break;
			} else {
				$correctanswer = false;
				$score = 0;
			}
		}

		$answerData = new stdClass;
		$answerData->answerid = $answerid;
		$answerData->correctanswer = $correctanswer;
		$answerData->score = $score;
		$answerData->userresponse = $useranswer;
		if (isset($response)) { $answerData->response = $response; }
		
		return array($jumpValue, $answerData);
	}




	/**
	 * Handle the _seenbranches record
	 */
	private function handleBranchTable() {
		$jumpValue = optional_param('jumpto', NULL, PARAM_INT);
		// going to insert into languagelesson_seenbranches                
		if ($jumpValue == LL_RANDOMBRANCH) {
			$branchflag = 1;
		} else {
			$branchflag = 0;
		}
		if ($grades = get_records_select("languagelesson_grades", "lessonid = $this->lessonid AND userid = $this->userid",
					"grade DESC")) {
			$retries = count($grades);
		} else {
			$retries = 0;
		}
		$branch = new stdClass;
		$branch->lessonid = $this->lessonid;
		$branch->userid = $this->userid;
		$branch->pageid = $this->pageid;
		$branch->retry = $retries;
		$branch->flag = $branchflag;
		$branch->timeseen = time();
	
		if (!insert_record("languagelesson_seenbranches", $branch)) {
			error("Error: could not insert row into languagelesson_seenbranches table");
		}

		return $jumpValue;
	}
    

}

?>
