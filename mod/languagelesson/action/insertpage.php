<?php // $Id: insertpage.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for processing the form from addpage action and inserts the page.
 *
 * @version $Id: insertpage.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package languagelesson
 **/
    require_sesskey();

    // check to see if the cancel button was pushed
    if (optional_param('cancel', '', PARAM_ALPHA)) {
        redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
    }

    $timenow = time();
    
    $form = data_submitted();

	// if this page is a CLOZE, validate it before we change any records
	if ($form->qtype == LL_CLOZE) {
		// note that since the text is coming from TinyMCE, it's pre-slashed, so we have to strip the slashes to get a proper
		// DOMDocument reading of the HTML
		languagelesson_validate_cloze_text(stripslashes($form->contents), $form->answer, $form->dropdown);
	}

    $newpage = new stdClass;
    $newanswer = new stdClass;
    if ($form->pageid) {
        // the new page is not the first page
        if (!$page = get_record("languagelesson_pages", "id", $form->pageid)) {
            error("Insert page: page record not found");
        }
        $newpage->lessonid = clean_param($lesson->id, PARAM_INT);
        $newpage->prevpageid = clean_param($form->pageid, PARAM_INT);
        $newpage->nextpageid = clean_param($page->nextpageid, PARAM_INT);
		// set the ordering value for the new page as the order val for prev page + 1
		$lastOrderVal = get_field('languagelesson_pages', 'ordering', 'id', $newpage->prevpageid);
		$newpage->ordering = $lastOrderVal + 1;
		// set the branchid value for the new page to the branchid of the preceding page
		$newpage->branchid = $page->branchid;
        $newpage->timecreated = $timenow;
        $newpage->qtype = $form->qtype;
        if (isset($form->qoption)) {
            $newpage->qoption = clean_param($form->qoption, PARAM_INT);
        } else {
            $newpage->qoption = 0;
        }
        if (isset($form->layout)) {
            $newpage->layout = clean_param($form->layout, PARAM_INT);
        } else {
            $newpage->layout = 0;
        }
        $newpage->title = clean_param($form->title, PARAM_CLEANHTML);
        $newpage->contents = trim($form->contents);
        $newpage->title = addslashes($newpage->title);
        $newpageid = insert_record("languagelesson_pages", $newpage);
        if (!$newpageid) {
            error("Insert page: new page not inserted");
        }
        // update the linked list (point the previous page to this new one)
        if (!set_field("languagelesson_pages", "nextpageid", $newpageid, "id", $newpage->prevpageid)) {
            error("Insert page: unable to update next link");
        }
        if ($page->nextpageid) {
            // new page is not the last page
            if (!set_field("languagelesson_pages", "prevpageid", $newpageid, "id", $page->nextpageid)) {
                error("Insert page: unable to update previous link");
            }
        }
    } else {
        // new page is the first page
        // get the existing (first) page (if any)
        if (!$page = get_record_select("languagelesson_pages", "lessonid = $lesson->id AND prevpageid = 0")) {
            // there are no existing pages
            $newpage->lessonid = $lesson->id;
            $newpage->prevpageid = 0; // this is a first page
            $newpage->nextpageid = 0; // this is the only page
            $newpage->timecreated = $timenow;
            $newpage->qtype = clean_param($form->qtype, PARAM_INT);
            if (isset($form->qoption)) {
                $newpage->qoption = clean_param($form->qoption, PARAM_INT);
            } else {
                $newpage->qoption = 0;
            }
            if (isset($form->layout)) {
                $newpage->layout = clean_param($form->layout, PARAM_INT);
            } else {
                $newpage->layout = 0;
            }
            $newpage->title = clean_param($form->title, PARAM_CLEANHTML);
            $newpage->contents = trim($form->contents);
            $newpage->title = addslashes($newpage->title);
            $newpageid = insert_record("languagelesson_pages", $newpage);
            if (!$newpageid) {
                error("Insert page: new first page not inserted");
            }
        } else {
            // there are existing pages put this at the start
            $newpage->lessonid = $lesson->id;
            $newpage->prevpageid = 0; // this is a first page
            $newpage->nextpageid = $page->id;
            $newpage->timecreated = $timenow;
            $newpage->qtype = clean_param($form->qtype, PARAM_INT);
            if (isset($form->qoption)) {
                $newpage->qoption = clean_param($form->qoption, PARAM_INT);
            } else {
                $newpage->qoption = 0;
            }
            if (isset($form->layout)) {
                $newpage->layout = clean_param($form->layout, PARAM_INT);
            } else {
                $newpage->layout = 0;
            }
            $newpage->title = clean_param($form->title, PARAM_CLEANHTML);
            $newpage->contents = trim($form->contents);
            $newpage->title = addslashes($newpage->title);
            $newpageid = insert_record("languagelesson_pages", $newpage);
            if (!$newpageid) {
                error("Insert page: first page not inserted");
            }
            // update the linked list
            if (!set_field("languagelesson_pages", "prevpageid", $newpageid, "id", $newpage->nextpageid)) {
                error("Insert page: unable to update link");
            }
        }
    }
    // now add the answers
    if ($form->qtype == LL_ESSAY || $form->qtype == LL_AUDIO || $form->qtype == LL_VIDEO) {
        $newanswer->lessonid = $lesson->id;
        $newanswer->pageid = $newpageid;
        $newanswer->timecreated = $timenow;
        if (isset($form->jumpto[0])) {
            $newanswer->jumpto = clean_param($form->jumpto[0], PARAM_INT);
        }
        if (isset($form->score[0])) {
            $newanswer->score = clean_param($form->score[0], PARAM_NUMBER);
        }
        $newanswerid = insert_record("languagelesson_answers", $newanswer);
        if (!$newanswerid) {
            error("Insert Page: answer record not inserted");
        }
    } elseif ($form->qtype != LL_BRANCHTABLE) {
        $maxanswers = $form->maxanswers;
        if ($form->qtype == LL_MATCHING) {
            // need to add two to offset correct response and wrong response
            $maxanswers = $maxanswers + 2;
        }
        for ($i = 0; $i < $maxanswers; $i++) {
			// re-initialize to a blank answer object
			$newanswer = new stdClass();
            if (!empty($form->answer[$i]) and trim(strip_tags($form->answer[$i]))) { // strip_tags because the HTML editor adds <p><br />
                $newanswer->lessonid = $lesson->id;
                $newanswer->pageid = $newpageid;
                $newanswer->timecreated = $timenow;
                $newanswer->answer = trim($form->answer[$i]);
				// if this is a CLOZE, need a hard-coded way to distinguish ordering of questions, so note that here
				if ($form->qtype == LL_CLOZE) { $newanswer->answer = $i.'|'.$newanswer->answer; }
                if (isset($form->response[$i])) {
                    $newanswer->response = trim($form->response[$i]);
                }
                if (isset($form->jumpto[$i])) {
                    $newanswer->jumpto = clean_param($form->jumpto[$i], PARAM_INT);
                }
                if (isset($form->score[$i])) {
                    $newanswer->score = clean_param($form->score[$i], PARAM_NUMBER);
                }
				// if this is a cloze subquestion marked as a dropdown, save it as such
				if ($form->qtype == LL_CLOZE && isset($form->dropdown[$i])) {
					$newanswer->flags = 1;
				}
                $newanswerid = insert_record("languagelesson_answers", $newanswer);
                if (!$newanswerid) {
                    error("Insert Page: answer record $i not inserted");
                }
            } else {
                if ($form->qtype == LL_MATCHING) {
                    if ($i < 2) {
                        $newanswer->lessonid = $lesson->id;
                        $newanswer->pageid = $newpageid;
                        $newanswer->timecreated = $timenow;
                        $newanswerid = insert_record("languagelesson_answers", $newanswer);
                        if (!$newanswerid) {
                            error("Insert Page: answer record $i not inserted");
                        }
                    }
				} else {
					break;
				}
			}
		}
		// if this is a CLOZE type, then the responses are not associated with specific answers, so save them here on their own
		if ($form->qtype == LL_CLOZE) {
			// initialize the answer template to save the responses with
			$newanswer = new stdClass();
			$newanswer->lessonid = $lesson->id;
			$newanswer->pageid = $newpageid;
			$newanswer->timecreated = $timenow;

			// set the responses
			if (isset($form->correctresponse)) {
				$newanswer->response = trim($form->correctresponse);
				$newanswer->score = $form->correctresponsescore;
				$newanswer->jumpto = clean_param($form->correctanswerjump, PARAM_INT);
				if (!$newanswerid = insert_record('languagelesson_answers', $newanswer)) {
					error("Insert page: correct response not inserted");
				}
			}

			if (isset($form->wrongresponse)) {
				$newanswer->response = trim($form->wrongresponse);
				$newanswer->score = $form->wrongresponsescore;
				$newanswer->jumpto = clean_param($form->wronganswerjump, PARAM_INT);
				if (!$newanswerid = insert_record('languagelesson_answers', $newanswer)) {
					error("Insert page: wrong response not inserted");
				}
			}
        }
    }



	// if we just inserted a branch table, handle creating branch records and ENDOFBRANCH page records here
	else if ($form->qtype == LL_BRANCHTABLE) {
        $maxanswers = $form->maxanswers;
		
		//init array to hold $branch objects for use in ENDOFBRANCH page population
		$branches = array();

		// create languagelesson_branch records
		//   - one for each branch
		//   - stores the pageid of the first page in the branch (that is, the one specified as the branch's jumpto in the submitted
		//   data
		//   - if submitted data just has the jumpto as NEXTPAGE (default setting), firstpage points to 0
        for ($i = 0; $i < $maxanswers; $i++) {
			// since maxanswers is the number of maximum POSSIBLE answers, some of these may be empty; if so, skip them
			if (empty($form->answer[$i])) { continue; }

			$branch = new stdClass;
			$branch->lessonid = $lesson->id;
			$branch->parentid = $newpageid;
			$branch->title = addslashes(trim($form->answer[$i]));
			$branch->timecreated = time();
			// set the firstpage field (a jumpto value of 0 means stick it at the end of the lesson)
			$branch->firstpage = $form->jumpto[$i];

			if (! insert_record('languagelesson_branches', $branch)) {
				error('Insert page: branch record not inserted');
			}
		}

		// now pull the just-created branches in order to use their IDs
		$branches = get_records('languagelesson_branches', 'parentid', $newpageid, 'id');
		// get rid of the records being keyed to their ids (give them sequential, meaningless indices)
		$branches = array_values($branches);


		// determine what the nextpageid should be for the case of inserting an EOB at the end of the current level (lesson or branch)
		// - if the parent branch table has no branch ID, this branching structure is not inside another branching structure, so the
		// EOB is being inserted at lesson level, therefore the nextpageid will be 0 (marking it as the end of the lesson)
		// - if the parent branch table does have a branch ID, the EOB is being inserted at branch level, so the nextpageid should be
		// the nextpageid of the EOB record ending the containing branch
		if (!$newpage->branchid) {
			$endpageid = 0;
		} else {
			$containingBranchPages = get_records('languagelesson_pages', 'branchid', $newpage->branchid, 'ordering');
			$lastContainingBranchPage = end($containingBranchPages);
			$endpageid = $lastContainingBranchPage->nextpageid;
		}


		// create the ENDOFBRANCH page records
		//   - for all except last one, nextpageid points to the parent branch table
		//   - use placement of branch head n+1 to decide where EOB n goes
		for ($i=0; $i<count($branches); $i++) {
			$branch = $branches[$i];

			$neweob = new stdClass;
			$neweob->lessonid = $lesson->id;
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
				$neweob->prevpageid = get_field('languagelesson_pages', 'id', 'nextpageid', $endpageid, 'lessonid', $lesson->id);
				$goesAtEnd = true;
			}

			// set nextpageid; if this is being inserted as the last page in the current structural level, its nextpageid will be that
			// of what was formerly the last page in the same level (e.g. 0 if inserted at lesson level, the parent BT or next page
			// after the complete branchset if inserted at branch level)
			if ($goesAtEnd) {
				$neweob->nextpageid = $endpageid;
			} else {
				$neweob->nextpageid = $newpageid;
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
			}
			// - otherwise, this EOB record was inserted at the end, so there should be no prevpageid pointing to this record

		}


		// update the languagelesson instance's ordering values, for certainty of accuracy
		languagelesson_update_ordering($lesson->id);

	}

    
	// Now that setting answers is done, update the languagelesson instance's calculated max grade
	recalculate_maxgrade($lesson->id);


    languagelesson_set_message(get_string('insertedpage', 'languagelesson').': '.format_string($newpage->title, true), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
?>
