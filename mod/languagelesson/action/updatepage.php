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

    $form = data_submitted();
	$timenow = time();

    $pageid = clean_param($form->pageid, PARAM_INT);

    // check to see if the cancel button was pushed
    if (optional_param('cancel', '', PARAM_ALPHA)) {
        if ($redirect == 'navigation') {
            // redirect to viewing the page
            redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$pageid");
        } else {
            redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
        }
    }

    if ($form->redisplay) {
        redirect("$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cm->id&amp;action=editpage&amp;pageid=$pageid&amp;redirect=$redirect");
    }

	$updater = new LanguageLessonPageUpdater($form, $lesson->id, $timenow);
	$updater->update();
    
	$pageTitle = get_field('languagelesson_pages', 'title', 'id', $pageid);
    languagelesson_set_message(get_string('updatedpage', 'languagelesson').': '.format_string($pageTitle, true), 'notifysuccess');
    if ($redirect == 'navigation') {
        // takes us back to viewing the page
        redirect("$CFG->wwwroot/mod/languagelesson/view.php?id=$cm->id&amp;pageid=$pageid");
    } else {
        redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
    }



class LanguageLessonPageUpdater {

	
	public $form = null;
	public $pageid = null;
	public $lessonid = null;
	public $timenow = null;


	function __construct($form, $lessonid, $timenow) {
		$this->form = $form;
		$this->pageid = clean_param($form->pageid, PARAM_INT);
		$this->lessonid = $lessonid;
		$this->timenow = $timenow;
	}

	/*
	 * Updates the record for the page to reflect new title/contents/type
	 */
	private function updatePageRecord() {
		$page = new stdClass;
		$page->id = $this->pageid;

		$page->timemodified = time();
		$page->qtype = clean_param($this->form->qtype, PARAM_INT);

		// if this page is a CLOZE, validate it before we change any records
		if ($page->qtype == LL_CLOZE) {
			// note that since the text is coming from TinyMCE, it's pre-slashed, so we have to strip the slashes to get a proper
			// DOMDocument reading of the HTML
			languagelesson_validate_cloze_text($this->form);
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
		$oldanswer->timemodified = $this->timenow;
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
			$answer->timemodified = $this->timenow;
		} else {
			// it's a new answer
			$answer->lessonid = $this->lessonid;
			$answer->pageid = $this->pageid;
			$answer->timecreated = $this->timenow;
		}
		$answer->answer = trim($this->form->answer[$i]);
		// if this is a CLOZE type, make sure we note the order of the answers
		if ($this->form->qtype == LL_CLOZE) { $answer->answer = $i.'|'.$answer->answer; }
		// and check to see if it's a drop-down type question
		if ($this->form->qtype == LL_CLOZE && isset($this->form->dropdown[$i])) {
			$answer->flags = 1;
		}
		$answer->response = ((isset($this->form->response[$i])) ? trim($this->form->response[$i]) : '');
		$answer->jumpto = ((isset($this->form->jumpto[$i])) ? clean_param($this->form->jumpto[$i], PARAM_INT) : 0);
		$answer->score = ((isset($this->form->score[$i])) ? clean_param($this->form->score[$i], PARAM_NUMBER) : 0);

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
								$oldanswer->timemodified = $this->timenow;
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
					$newanswer->lessonid = $this->lessonid;
					$newanswer->pageid = $this->pageid;
					$newanswer->timecreated = $this->timenow;

					$this->handleClozeResponse($newanswer, 'correct');
					$this->handleClozeResponse($newanswer, 'wrong');


				}
			break;

			case LL_BRANCHTABLE:

				// pull the IDs of all the old branches for this page (we need these for handling EOB records below)
				$oldBranches = get_records('languagelesson_branches', 'parentid', $this->pageid);
				$oldBranchids = array_keys($oldBranches);

				// separate the data in the submitted form into existing, removed, and new branches
				list($existingBranches, $removedBranches, $newBranches) = $this->classifyBranches();
				if (count($existingBranches) == 0
						&& count($newBranches) == 0) {
					error("You cannot remove all branches!");
				}

				
				// update existing branch records
				foreach ($existingBranches as $branch) {
					if (! update_record('languagelesson_branches', $branch)) {
						error("Update: could not update existing branch record");
					}
				}


				// delete removed branch records
				$stringrep = implode(',', $removedBranches);
				if (! empty($stringrep) && ! delete_records_select('languagelesson_branches', "id in ($stringrep)")) {
					error("Update: could not delete removed branch records");
				}


				// create new branch records
				$newBranchids = array();
				foreach ($newBranches as $branch) {
					if (! $newid = insert_record('languagelesson_branches', $branch)) {
						error("Update: could not insert new branch record");
					}
					$newBranchids[] = $newid;
				}


				// update EOB records
				$this->updateEOBRecords($oldBranchids);

				// update ordering
				languagelesson_update_ordering($this->lessonid);

			break;

			default:
			break;
		}

		// Now that setting answers is done, update the languagelesson instance's calculated max grade
		languagelesson_recalculate_maxgrade($this->lessonid);

	}


	private function updateEOBRecords($oldBranchids) {
		// pull all the old EOB records belonging to this BT
		$oldBranchids_str = implode(',', $oldBranchids);
		$oldEOBs = get_records_select('languagelesson_pages', "qtype = ".LL_ENDOFBRANCH."
																and branchid in ($oldBranchids_str)");
		$oldEOBids = array_keys($oldEOBs);
		// remove them from the prevpageid/nextpageid linkage system
		foreach ($oldEOBids as $eobid) {
			// there will always be a previous page, as EOBs cannot exist by themselves
			$previousid = get_field('languagelesson_pages', 'id', 'nextpageid', $eobid);
			// there is not always guaranteed to be a next page, though
			if (! $nextid = get_field('languagelesson_pages', 'id', 'prevpageid', $eobid)) {
				$nextid = 0;
			}
			// update the previous page to point at the nextpage
			set_field('languagelesson_pages', 'nextpageid', $nextid, 'id', $previousid);
			// and if we have a nextpage, update it to point back to the previous page
			if ($nextid) { set_field('languagelesson_pages', 'prevpageid', $previousid, 'id', $nextid); }
		}
		// now delete the old EOB records 
		$oldEOBids_str = implode(',', $oldEOBids);
		if (! delete_records_select('languagelesson_pages', "id in ($oldEOBids_str)")) {
			error("Update: could not remove old end of branch records");
		}

		// pull the updated list of branch records for this BT
		$branches = get_records('languagelesson_branches', 'parentid', $this->pageid, 'ordering');
		// get rid of the records being keyed to their ids (give them sequential, meaningless indices)
		$branches = array_values($branches);

		$this->generateEOBRecords($branches);
	}


	private function classifyBranches() {
		$maxanswers = $this->form->maxanswers;

		$existingBranches = array();
		$removedBranches = array();
		$newBranches = array();
		// since there can be empty fields (that we should ignore) between valid branch designations,
		// the new branch's ordering value will not always equal $i, so track it separately
		$ordering = 0;
		for ($i=0; $i < $maxanswers; $i++) {
			if (isset($this->form->branchid[$i])) {
				if (! empty($this->form->branchtitle[$i])) {
					// put it in existing, with modified information recorded
					$branch = new stdClass;
					$branch->id = clean_param($this->form->branchid[$i], PARAM_INT);
					$branch->title = addslashes(trim($this->form->branchtitle[$i]));
					$branch->firstpage = clean_param($this->form->jumpto[$i], PARAM_INT);
					$branch->timemodified = time();

					$existingBranches[] = $branch;

					// note that we saw a valid branch
					$ordering++;
				} else {
					// put its ID in removed
					$removedBranches[] = clean_param($this->form->branchid[$i], PARAM_INT);
				}
			} else if (! empty($this->form->branchtitle[$i])) {
				// put it in new
				$branch = new stdClass;
				$branch->lessonid = $this->lessonid;
				$branch->parentid = $this->pageid;
				$branch->ordering = ++$ordering;
				$branch->title = addslashes(trim($this->form->branchtitle[$i]));
				$branch->timecreated = time();
				// set the firstpage field (a jumpto value of 0 means stick it at the end of the lesson)
				$branch->firstpage = $this->form->jumpto[$i];

				$newBranches[] = $branch;
			}
		}

		return array($existingBranches, $removedBranches, $newBranches);
	}



	/* STRAIGHT COPIED FROM INSERTPAGE.PHP -- NEED THIS AS SHARED FUNCTION */
	private function generateEOBRecords($branches) {
		// determine what the nextpageid should be for the case of inserting an EOB at the end of the current level (lesson or
		// branch)
		// - if the parent branch table has no branch ID, this branching structure is not inside another branching structure,
		// so the EOB is being inserted at lesson level, therefore the nextpageid will be 0 (marking it as the end of the lesson)
		// - if the parent branch table does have a branch ID, the EOB is being inserted at branch level, so its nextpageid
		// should be the id of the EOB record ending the containing branch
		if (! $thisbranch = get_field('languagelesson_pages', 'branchid', 'id', $this->pageid)) {
			$endpageid = 0;
		} else {
			$containingBranchPages = get_records('languagelesson_pages', 'branchid', $thisbranch, 'ordering');
			$lastContainingBranchPage = end($containingBranchPages);
			$endpageid = $lastContainingBranchPage->id;
		}


		// create the ENDOFBRANCH page records
		//   - for all except last one, nextpageid points to the parent branch table
		//   - use placement of branch head n+1 to decide where EOB n goes
		for ($i=0; $i<count($branches); $i++) {
			$branch = $branches[$i];

			$neweob = new stdClass;
			$neweob->lessonid = $this->lessonid;
			$neweob->branchid = $branch->id;
			$neweob->qtype = LL_ENDOFBRANCH;
			$neweob->timecreated = time();
			$neweob->title = 'ENDOFBRANCH';

			// determine prevpageid as follows:
			// - if this is the last branch, the EOB becomes the last page in the current structural level (e.g. lesson or branch),
			// period, so its prevpageid is set to the ID of what is currently the last page in that level
			// - if the next branch record has a firstpageid, this EOB's prevpageid becomes that page's prevpageid
			// - if the next branch does not have a firstpageid, this EOB becomes the last page in the current level
			$goesAtEnd = false;
			if ($i+1 < count($branches) && $branches[$i+1]->firstpage) {
				$neweob->prevpageid = get_field('languagelesson_pages', 'id', 'nextpageid', $branches[$i+1]->firstpage);
			} else {
				$neweob->prevpageid = get_field('languagelesson_pages', 'id', 'nextpageid', $endpageid, 'lessonid', $this->lessonid);
				$goesAtEnd = true;
			}

			// set nextpageid; if this is being inserted as the last page in the current structural level, its nextpageid will be that
			// of what was formerly the last page in the same level (e.g. 0 if inserted at lesson level, the parent BT or next page
			// after the complete branchset if inserted at branch level)
			if ($goesAtEnd) {
				$neweob->nextpageid = $endpageid;
			} else {
				$neweob->nextpageid = $this->pageid;
			}

			// insert the EOB page
			if (! $neweobid = insert_record('languagelesson_pages', $neweob)) {
				error('Insert page: failed to insert EndOfBranch record');
			}

			// and now that we have the ID, go back and correct the references of the pages around the new EOB

			// handle nextpageid of the page preceding the EOB record
			// - if the page directly before the new EOB is not itself an EOB, point it to the EOB as next page
			if (get_field('languagelesson_pages', 'qtype', 'id', $neweob->prevpageid) != LL_ENDOFBRANCH) {
				set_field('languagelesson_pages', 'nextpageid', $neweobid, 'id', $neweob->prevpageid);
			// - if it is an EOB, however, point it to the branch table, as its old nextpageid value should currently be 0 (it was the
			// last page in the LL instance)
			} else {
				set_field('languagelesson_pages', 'nextpageid', $branch->parentid, 'id', $neweob->prevpageid);
			}

			// handle prevpageid of the page following the EOB record
			// - if the branch following this EOB has a firstpage pointer (that is, it has content), then point that following branch's
			// first page's prevpageid value to the newly-inserted EOB record
			if ($i+1 < count($branches) && $branches[$i+1]->firstpage) {
				set_field('languagelesson_pages', 'prevpageid', $neweobid, 'id', $branches[$i+1]->firstpage);
			// - if this is the last EOB record; if it's not getting inserted inside another branch level, there will be no page after
			// this, so don't bother setting anything, but if this is inside another branch structure, then set that containing
			// branch's EOB to be following this last record
			} else if ($i+1 == count($branches) && $endpageid) {
				set_field('languagelesson_pages', 'prevpageid', $neweobid, 'id', $endpageid);
			}

		}
	}




	/*
	 * Process CLOZE type holistic responses here, since they are stored and handled differently than normal responses
	 */
	private function handleClozeResponse($newanswer, $type) {

		// set the variable names for fetching response data from the form
		$rid = "{$type}responseid";
		$rscore = "{$type}responsescore";
		$rjump = "{$type}answerjump";
		$rtext = "{$type}response";

		// if there was an old correct response, handle the new information 
		if (isset($this->form->$rid)) {
			$oldanswer = new stdClass();
			$oldanswer->id = $this->form->$rid;
			$oldanswer->timemodified = $this->timenow;
			$oldanswer->jumpto = clean_param($this->form->$rjump, PARAM_INT);
			// if there is still a correct response, update the record to reflect it
			if (isset($this->form->$rtext)) {
				$oldanswer->response = trim($this->form->$rtext);
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
		} else if (isset($this->form->$rtext)) {
			$newanswer->response = trim($this->form->$rtext);
			$newanswer->score = $this->form->$rscore;
			$newanswer->jumpto = clean_param($this->form->$rscore, PARAM_INT);
			if (!$newanswerid = insert_record('languagelesson_answers', $newanswer)) {
				error("Insert page: $type response not inserted");
			}
		}

	}

}
?>
