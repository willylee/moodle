<?php // $Id: updatepage.php 675 2011-09-16 19:27:51Z griffisd $
/**
 * Action for processing the form in editpage action and saves the page
 *
 * @version $Id: updatepage.php 675 2011-09-16 19:27:51Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    require_sesskey();
    
    $redirect = optional_param('redirect', '', PARAM_ALPHA);

    $timenow = time();
    $form = data_submitted();

    $page = new stdClass;
    $page->id = clean_param($form->pageid, PARAM_INT);

    // check to see if the cancel button was pushed
    if (optional_param('cancel', '', PARAM_ALPHA)) {
        if ($redirect == 'navigation') {
            // redirect to viewing the page
            redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$page->id");
        } else {
            redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
        }
    }

    if ($form->redisplay) {
        redirect("$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cm->id&amp;action=editpage&amp;pageid=$page->id&amp;redirect=$redirect");
    }

	$updater = new LanguageLessonPageUpdater($form);
	$updater->update();
    
    languagelesson_set_message(get_string('updatedpage', 'languagelesson').': '.format_string($page->title, true), 'notifysuccess');
    if ($redirect == 'navigation') {
        // takes us back to viewing the page
        redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$page->id");
    } else {
        redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
    }



class LanguageLessonPageUpdater {

	
	public $form = null;
	public $pageid = null;


	function __construct($form) {
		$this->form = $form;
		$this->pageid = clean_param($form->pageid, PARAM_INT);
	}

	/*
	 * Updates the record for the page to reflect new title/contents/type
	 */
	private function updatePageRecord() {
		$page = new stdClass;
		$page->id = $this->pageid;

		$page->timemodified = $timenow;
		$page->qtype = clean_param($this->form->qtype, PARAM_INT);

		// if this page is a CLOZE, validate it before we change any records
		if ($page->qtype == LL_CLOZE) {
			// note that since the text is coming from TinyMCE, it's pre-slashed, so we have to strip the slashes to get a proper
			// DOMDocument reading of the HTML
			languagelesson_validate_cloze_text(stripslashes($this->form->contents), $this->form->answer, $this->form->dropdown);
		}

		$page->qoption = ((isset($this->form->qoption)) ? clean_param($this->form->qoption, PARAM_INT) : 0);
		$page->layout = ((isset($this->form->layout)) ? clean_param($this->form->layout, PARAM_INT) : 0);
		$page->title = addslashes(clean_param($this->form->title, PARAM_CLEANHTML));
		$page->contents = addslashes(trim($this->form->contents));
		
		if (!update_record("languagelesson_pages", $page)) {
			error("Update page: page not updated");
		}
	}


	/*
	 * Update the single placeholder answer used for Audio, Video, and Essay types (stores score and jump)
	 */
	private function updatePlaceholderAnswer() {
		// there's just a single answer with a jump
		$oldanswer = new stdClass;
		$oldanswer->id = clean_param($this->form->answerid[0], PARAM_INT);
		$oldanswer->timemodified = $timenow;
		$oldanswer->jumpto = clean_param($this->form->jumpto[0], PARAM_INT);
		if (isset($this->form->score[0])) {
			$oldanswer->score = clean_param($this->form->score[0], PARAM_NUMBER);
		}
		// delete other answers; this is mainly for essay questions.  If one switches from using a qtype like Multichoice,
		// then switches to essay, the old answers need to be removed because essay is
		// supposed to only have one answer record
		if ($answers = get_records_select("languagelesson_answers", "pageid = ".$this->pageid)) {
			// remove the answer record we want to keep from the list
			unset($answers[$oldanswer->id]);
			// delete everything else in the list
			if (! delete_records_select('languagelesson_answers', 'id in ('.implode(',',array_keys($answers)).')')) {
				error("Update page: unable to delete extraneous answers");
			}
		}        
		if (!update_record("languagelesson_answers", $oldanswer)) {
			error("Update page: EOB not updated");
		}
	}



	/*
	 * Update the record for a single answer to reflect new contents/response
	 * @param int $i The index in the form of the answer to update
	 */
	private function updateSingleAnswer($i) {
		$answer = new stdClass;
		if ($this->form->answerid[$i]) {
			$answer->id = clean_param($this->form->answerid[$i], PARAM_INT);
			$answer->timemodified = $timenow;
		} else {
			// it's a new answer
			$answer->lessonid = $lesson->id;
			$answer->pageid = $this->pageid;
			$answer->timecreated = $timenow;
		}
		$answer->answer = trim($this->form->answer[$i]);
		// if this is a CLOZE type, make sure we note the order of the answers
		if ($this->form->qtype == LL_CLOZE) { $answer->answer = $i.'|'.$answer->answer; }
		// and check to see if it's a drop-down type question
		if ($this->form->qtype == LL_CLOZE && isset($this->form->dropdown[$i])) {
			$answer->flags = 1;
		}
		$answer->response = ((isset($this->form->response[$i])) ? trim($this->form->response[$i]) : '');
		$answer->jumpto = ((isset($this->form->jumpto[$i])) ? clean_param($this->form->jumpto[$i], PARAM_INT) : null);
		$answer->score = ((isset($this->form->score[$i])) ? clean_param($this->form->score[$i], PARAM_NUMBER) : null);

		if ($this->form->answerid[$i] && !update_record("languagelesson_answers", $answer)) {
			error("Update page: answer $i not updated");
		} else if (!$this->form->answerid[$i] && !$newanswerid = insert_record('languagelesson_answers', $answer)) {
			error("Update page: answer record not inserted");
		}
	}



	/*
	 * Master function.  Handles all page updating actions.
	 */
	public function update() {
		$this->updatePageRecord();

		switch ($this->form->qtype) {

			case LL_ESSAY:
			case LL_AUDIO:
			case LL_VIDEO:
				$this->updatePlaceholderAnswer();
			break;

			case LL_MULTICHOICE:
			case LL_TRUEFALSE:
			case LL_SHORTANSWER:
			case LL_CLOZE:
			case LL_MATCHING:
			case LL_NUMERICAL:
				// it's an "ordinary" page
				$maxanswers = $this->form->maxanswers;
				if ($this->form->qtype == LL_MATCHING) {
					// need to add two to offset correct response and wrong response
					$maxanswers = $maxanswers + 2;
				}
				for ($i = 0; $i < $maxanswers; $i++) {
					if ((isset($this->form->answer[$i]) and (trim($this->form->answer[$i])) != '')) {
						$this->updateSingleAnswer($i);
					} else {
						 if ($this->form->qtype == LL_MATCHING) {
							if ($i >= 2) {
								if ($this->form->answerid[$i]) {
									// need to delete blanked out answer
									if (!delete_records("languagelesson_answers", "id", clean_param($this->form->answerid[$i],
													PARAM_INT))) {
										error("Update page: unable to delete answer record");
									}
								}
							} else {
								$oldanswer = new stdClass;
								$oldanswer->id = clean_param($this->form->answerid[$i], PARAM_INT);
								if (!isset($this->form->answereditor[$i])) { $this->form->answereditor[$i] = 0; }
								if (!isset($this->form->responseeditor[$i])) { $this->form->responseeditor[$i] = 0;	} 
								$oldanswer->flags = $this->form->answereditor[$i] * LL_ANSWER_EDITOR +
													$this->form->responseeditor[$i] * LL_RESPONSE_EDITOR;
								$oldanswer->timemodified = $timenow;
								$oldanswer->answer = NULL;
								if (!update_record("languagelesson_answers", $oldanswer)) {
									error("Update page: answer $i not updated");
								}
							}                        
						} elseif (!empty($this->form->answerid[$i])) {
							// need to delete blanked out answer
							if (!delete_records("languagelesson_answers", "id", clean_param($this->form->answerid[$i], PARAM_INT))) {
								error("Update page: unable to delete answer record");
							}
						}
					}
				}
				// if this is a CLOZE type, then the responses are not associated with specific answers, so handle them here on their own
				if ($this->form->qtype == LL_CLOZE) {
					// initialize the answer template to save new responses with
					$newanswer = new stdClass();
					$newanswer->lessonid = $lesson->id;
					$newanswer->pageid = $this->pageid;
					$newanswer->timecreated = $timenow;

					$this->handleClozeResponse($newanswer, 'correct');
					$this->handleClozeResponse($newanswer, 'wrong');


				}
			break;

			case LL_BRANCHTABLE:
				$maxanswers = $this->form->maxanswers;
			break;

			default:
			break;
		}

		// Now that setting answers is done, update the languagelesson instance's calculated max grade
		languagelesson_recalculate_maxgrade($lesson->id);

	}


	/*
	 * Process CLOZE type holistic responses here, since they are stored and handled differently than normal responses
	 */
	private function handleClozeResponse($newanswer, $type) {

		// if there was an old correct response, handle the new information 
		if (isset($this->form["${type}responseid"])) {
			$oldanswer = new stdClass();
			$oldanswer->id = $this->form["{$type}responseid"];
			$oldanswer->timemodified = $timenow;
			$oldanswer->jumpto = clean_param($this->form["{$type}answerjump"], PARAM_INT);
			// if there is still a correct response, update the record to reflect it
			if (isset($this->form["{$type}response"])) {
				$oldanswer->response = trim($this->form["{$type}response"]);
				if (!update_record("languagelesson_answers", $oldanswer)) {
					error("Update page: Cloze type $type feedback not updated");
				}
			// if it was taken out, however, delete the record of it
			} else {
				if (!delete_records('languagelesson_answers', 'id', $oldanswer->id)) {
					error("Update page: could not delete removed old $type feedback");
				}
			}
		// if there was no old correct response, but one was given, save it as a new one
		} else if (isset($this->form["{$type}response"])) {
			$newanswer->response = trim($this->form["{$type}response"]);
			$newanswer->score = $this->form["{$type}responsescore"];
			$newanswer->jumpto = clean_param($this->form["{$type}answerjump"], PARAM_INT);
			if (!$newanswerid = insert_record('languagelesson_answers', $newanswer)) {
				error("Insert page: $type response not inserted");
			}
		}

	}

}
?>
