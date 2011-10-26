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

    $page->timemodified = $timenow;
    $page->qtype = clean_param($form->qtype, PARAM_INT);

	// if this page is a CLOZE, validate it before we change any records
	if ($page->qtype == LL_CLOZE) {
		// note that since the text is coming from TinyMCE, it's pre-slashed, so we have to strip the slashes to get a proper
		// DOMDocument reading of the HTML
		languagelesson_validate_cloze_text(stripslashes($form->contents), $form->answer, $form->dropdown);
	}

    if (isset($form->qoption)) {
        $page->qoption = clean_param($form->qoption, PARAM_INT);
    } else {
        $page->qoption = 0;
    }
    if (isset($form->layout)) {
        $page->layout = clean_param($form->layout, PARAM_INT);
    } else {
        $page->layout = 0;
    }
    $page->title = clean_param($form->title, PARAM_CLEANHTML);
    $page->contents = addslashes(trim($form->contents));
    $page->title = addslashes($page->title);
    
    if (!update_record("languagelesson_pages", $page)) {
        error("Update page: page not updated");
    }
    if ($page->qtype == LL_ENDOFBRANCH
        || $page->qtype == LL_ESSAY
        || $page->qtype == LL_AUDIO
        || $page->qtype == LL_VIDEO
        || $page->qtype == LL_CLUSTER
        || $page->qtype == LL_ENDOFCLUSTER) {
        // there's just a single answer with a jump
        $oldanswer = new stdClass;
        $oldanswer->id = $form->answerid[0];
        $oldanswer->timemodified = $timenow;
        $oldanswer->jumpto = clean_param($form->jumpto[0], PARAM_INT);
        if (isset($form->score[0])) {
            $oldanswer->score = clean_param($form->score[0], PARAM_NUMBER);
        }
        // delete other answers  this is mainly for essay questions.  If one switches from using a qtype like Multichoice,
        // then switches to essay, the old answers need to be removed because essay is
        // supposed to only have one answer record
        if ($answers = get_records_select("languagelesson_answers", "pageid = ".$page->id)) {
            foreach ($answers as $answer) {
                if ($answer->id != clean_param($form->answerid[0], PARAM_INT)) {
                    if (!delete_records("languagelesson_answers", "id", $answer->id)) {
                        error("Update page: unable to delete answer record");
                    }
                }
            }
        }        
        if (!update_record("languagelesson_answers", $oldanswer)) {
            error("Update page: EOB not updated");
        }
    } else {
        // it's an "ordinary" page
        $maxanswers = $form->maxanswers;
		if ($page->qtype == LL_MATCHING) {
            // need to add two to offset correct response and wrong response
            $maxanswers = $maxanswers + 2;
        }
        for ($i = 0; $i < $maxanswers; $i++) {
            // strip tags because the editor gives <p><br />...
            // also save any answers where the editor is (going to be) used
            if ((isset($form->answer[$i]) and (trim(strip_tags($form->answer[$i]))) != '')) {
                if ($form->answerid[$i]) {
                    $oldanswer = new stdClass;
                    $oldanswer->id = clean_param($form->answerid[$i], PARAM_INT);
                    $oldanswer->timemodified = $timenow;
                    $oldanswer->answer = trim($form->answer[$i]);
					// if this is a CLOZE type, make sure we note the order of the answers
					if ($form->qtype == LL_CLOZE) { $oldanswer->answer = $i.'|'.$oldanswer->answer; }
					// and check to see if it's a drop-down type question
					if ($form->qtype == LL_CLOZE && isset($form->dropdown[$i])) {
						$oldanswer->flags = 1;
					}
                    if (isset($form->response[$i])) {
                        $oldanswer->response = trim($form->response[$i]);
                    } else {
                        $oldanswer->response = '';
                    }
					if (isset($form->jumpto[$i])) {
						$oldanswer->jumpto = clean_param($form->jumpto[$i], PARAM_INT);
					}
                    if (isset($form->score[$i])) {
                        $oldanswer->score = clean_param($form->score[$i], PARAM_NUMBER);
                    }
                    if (!update_record("languagelesson_answers", $oldanswer)) {
                        error("Update page: answer $i not updated");
                    }
                } else {
                    // it's a new answer
                    $newanswer = new stdClass; // need to clear id if more than one new answer is ben added
                    $newanswer->lessonid = $lesson->id;
                    $newanswer->pageid = $page->id;
                    $newanswer->timecreated = $timenow;
                    $newanswer->answer = trim($form->answer[$i]);
					// if this is a CLOZE type, make sure we note the order of the answers
					if ($form->qtype == LL_CLOZE) { $newanswer->answer = $i.'|'.$newanswer->answer; }
					// and check to see if it's a drop-down type question
					if ($form->qtype == LL_CLOZE && isset($form->dropdown[$i])) {
						$newanswer->flags = 1;
					}
                    if (isset($form->response[$i])) {
                        $newanswer->response = trim($form->response[$i]);
                    }
					if (isset($form->jumpto[$i])) {
						$newanswer->jumpto = clean_param($form->jumpto[$i], PARAM_INT);
					}
                    if (isset($form->score[$i])) {
                        $newanswer->score = clean_param($form->score[$i], PARAM_NUMBER);
                    }
                    $newanswerid = insert_record("languagelesson_answers", $newanswer);
                    if (!$newanswerid) {
                        error("Update page: answer record not inserted");
                    }
                }
            } else {
                 if ($form->qtype == LL_MATCHING) {
                    if ($i >= 2) {
                        if ($form->answerid[$i]) {
                            // need to delete blanked out answer
                            if (!delete_records("languagelesson_answers", "id", clean_param($form->answerid[$i], PARAM_INT))) {
                                error("Update page: unable to delete answer record");
                            }
                        }
                    } else {
                        $oldanswer = new stdClass;
                        $oldanswer->id = clean_param($form->answerid[$i], PARAM_INT);
                        if (!isset($form->answereditor[$i])) {
                            $form->answereditor[$i] = 0;
                        }
                        if (!isset($form->responseeditor[$i])) {
                            $form->responseeditor[$i] = 0;
                        }                        
                        $oldanswer->flags = $form->answereditor[$i] * LL_ANSWER_EDITOR +
                                            $form->responseeditor[$i] * LL_RESPONSE_EDITOR;
                        $oldanswer->timemodified = $timenow;
                        $oldanswer->answer = NULL;
                        if (!update_record("languagelesson_answers", $oldanswer)) {
                            error("Update page: answer $i not updated");
                        }
                    }                        
                } elseif (!empty($form->answerid[$i])) {
                    // need to delete blanked out answer
                    if (!delete_records("languagelesson_answers", "id", clean_param($form->answerid[$i], PARAM_INT))) {
                        error("Update page: unable to delete answer record");
                    }
                }
            }
        }
		// if this is a CLOZE type, then the responses are not associated with specific answers, so handle them here on their own
		if ($form->qtype == LL_CLOZE) {
			// initialize the answer template to save new responses with
			$newanswer = new stdClass();
			$newanswer->lessonid = $lesson->id;
			$newanswer->pageid = $page->id;
			$newanswer->timecreated = $timenow;

			// if there was an old correct response, handle the new information 
			if (isset($form->correctresponseid)) {
				$oldanswer = new stdClass();
				$oldanswer->id = $form->correctresponseid;
				$oldanswer->timemodified = $timenow;
				$oldanswer->jumpto = clean_param($form->correctanswerjump, PARAM_INT);
				// if there is still a correct response, update the record to reflect it
				if (isset($form->correctresponse)) {
					$oldanswer->response = trim($form->correctresponse);
					if (!update_record("languagelesson_answers", $oldanswer)) {
						error("Update page: Cloze type correct feedback not updated");
					}
				// if it was taken out, however, delete the record of it
				} else {
					if (!delete_records('languagelesson_answers', 'id', $oldanswer->id)) {
						error("Update page: could not delete removed old correct feedback");
					}
				}
			// if there was no old correct response, but one was given, save it as a new one
			} else if (isset($form->correctresponse)) {
				$newanswer->response = trim($form->correctresponse);
				$newanswer->score = $form->correctresponsescore;
				$newanswer->jumpto = clean_param($form->correctanswerjump, PARAM_INT);
				if (!$newanswerid = insert_record('languagelesson_answers', $newanswer)) {
					error("Insert page: correct response not inserted");
				}
			}

			// handle old wrong response
			if (isset($form->wrongresponseid)) {
				$oldanswer = new stdClass();
				$oldanswer->id = $form->wrongresponseid;
				$oldanswer->timemodified = $timenow;
				$oldanswer->jumpto = clean_param($form->wronganswerjump, PARAM_INT);
				// if there is still a correct response, update the record to reflect it
				if (isset($form->wrongresponse)) {
					$oldanswer->response = trim($form->wrongresponse);
					if (!update_record("languagelesson_answers", $oldanswer)) {
						error("Update page: Cloze type wrong feedback not updated");
					}
				// if it was taken out, however, delete the record of it
				} else {
					if (!delete_records('languagelesson_answers', 'id', $oldanswer->id)) {
						error("Update page: could not delete removed old wrong feedback");
					}
				}
			// handle new wrong response
			} else if (isset($form->wrongresponse)) {
				$newanswer->response = trim($form->wrongresponse);
				$newanswer->score = $form->wrongresponsescore;
				$newanswer->jumpto = clean_param($form->wronganswerjump, PARAM_INT);
				if (!$newanswerid = insert_record('languagelesson_answers', $newanswer)) {
					error("Insert page: wrong response not inserted");
				}
			}

        }
    }

    if ($form->redisplay) {
        redirect("$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cm->id&amp;action=editpage&amp;pageid=$page->id&amp;redirect=$redirect");
    }
    
    languagelesson_set_message(get_string('updatedpage', 'languagelesson').': '.format_string($page->title, true), 'notifysuccess');
    if ($redirect == 'navigation') {
        // takes us back to viewing the page
        redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$page->id");
    } else {
        redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
    }
?>
