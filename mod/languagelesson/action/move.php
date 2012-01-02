<?php // $Id: move.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for actually moving the page (database changes)
 *
 * @version $Id: move.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    $movepageid = required_param('pageid', PARAM_INT); //  page to move
    if (!$movepage = get_record("languagelesson_pages", "id", $movepageid)) {
        error("Move: page not found");
    }
	$mode = required_param('mode', PARAM_RAW);
	if ($mode == 'showslots') {
		$display = new LanguageLessonPageMover();
		$display->cmid = $cm->id;
		$display->lessonid = $lesson->id;
		$display->displaySlots($movepage);

	} else if ($mode == 'move') {
		require_capability('mod/languagelesson:edit', $context);
		require_sesskey();

		$after = required_param('after', PARAM_INT); // target page

		$mover = new LanguageLessonPageMover();
		$mover->lessonid = $lesson->id;
		$mover->move($movepage, $after);

		languagelesson_set_message(get_string('movedpage', 'languagelesson'), 'notifysuccess');
		redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
	}






class LanguageLessonPageMover {

	public $cmid = 0;
	public $lessonid = 0;

	public function displaySlots($movepage) {
		global $USER;

		print_heading(get_string("moving", "languagelesson", format_string($movepage->title)));
	   
		echo "<center><table cellpadding=\"5\" border=\"1\">\n";


		if ($movepage->qtype != LL_ENDOFBRANCH) {
			echo "<tr><td><a href=\"lesson.php?id=$this->cmid&amp;sesskey=".$USER->sesskey."&amp;action=move&amp;mode=move&amp;pageid=$movepage->id"
					."&amp;after=0\"><small>".get_string("movepagehere", "languagelesson")."</small></a></td></tr>\n";

			if (! $pages = get_records('languagelesson_pages', 'lessonid', $this->lessonid, 'ordering')) {
				error('Move: pages not found!');
			}
		} else {


			// the EOB can be moved from:
			// - after the first page of its branch
			// to:
			// - 1) if it is not the last branch, then up to before the last page of the following branch
			// - 2) if it is the last branch but there is a containing branch, then up to before the last page of the containing
			// branch
			// - 3) if it is the last branch and there is NO containing branch, then up to AFTER the last question in the LL
			//
			// this list SHOULD INCLUDE the slot where the EOB is already, so there is always at least one valid slot displayed
			// ^^^^^^^THAT IS NOT TRUE RIGHT NOW FIX IT



			$branch = get_record('languagelesson_branches', 'id', $movepage->branchid);
			$firstPageOrdering = get_field('languagelesson_pages', 'ordering', 'id', $branch->firstpage);

			// the last place the page can go is before the last question of the next branch
			// if the EOB being moved is the end of the last branch, it can go after any of the questions after itself at the same
			// depth level (either all questions afterwards if at the top LL level, or the remaining questions in the containing
			// branch if nested in a BT structure)
			if ($nextbranchid = get_field('languagelesson_branches', 'id', 'ordering', $branch->ordering+1, 'parentid',
						$branch->parentid)) {
				$lastValidOrdering = get_field('languagelesson_pages', 'ordering', 'branchid', $nextbranchid, 'qtype',
										LL_ENDOFBRANCH);
				// decrement the resulting value to make it actually the last VALID ordering
				$lastValidOrdering--;
			} else {
				if ($containingBranch = get_field('languagelesson_pages', 'branchid', 'id', $branch->parentid)) {
					$lastValidOrdering = get_field('languagelesson_pages', 'ordering', 'branchid', $containingBranch,
													'qtype', LL_ENDOFBRANCH);
					$lastValidOrdering--;
				} else {
					$lastValidOrdering = get_field('languagelesson_pages', 'ordering', 'nextpageid', 0, 'lessonid', $this->lessonid);
				}
			}

			$pages = get_records_select('languagelesson_pages', "lessonid=$this->lessonid and ordering >= $firstPageOrdering and ordering <= $lastValidOrdering",
										'ordering');
		}

		// only try to print slots if we got pages at all and if there are more than just the page being moved itself
		if ($pages) {
			foreach ($pages as $pageid => $page) {
				if ($pageid != $movepage->id) {
					if (!$title = trim(format_string($page->title))) {
						$title = "<< ".get_string("notitle", "languagelesson")."  >>";
					}
					echo "<tr><td><b>$title</b></td></tr>\n";

					if ($movepage->qtype != LL_ENDOFBRANCH
							|| $page->ordering < $lastValidOrdering
							|| $this->isLastBranchEnd($movepage)) {
						echo "<tr><td><a href=\"lesson.php?id=$this->cmid&amp;sesskey=".$USER->sesskey."&amp;action=move"
								."&amp;mode=move&amp;pageid=$movepage->id&amp;after={$pageid}\"><small>"
								.get_string("movepagehere", "languagelesson")."</small></a></td></tr>\n";
					}
				}
			}
		}
		echo "</table>\n";
	}

	public function move($movepage, $after) {
		// store the old next page id, because we'll need it later (note that this is just for clarity; $movepage->nextpageid
		// remains the same throughout the script execution)
		$oldnextpageid = $movepage->nextpageid;

		// determine the new first page
		// (this is done first as the current first page will be lost in the next step)
		$newfirstpageid = $this->findNewFirstPage($movepage, $after);

		// join pages into a ring 
		$firstpageid = $this->getFrom('pages', 'id', 'prevpageid', 0, 'first page not found');
		$lastpageid = $this->getFrom('pages', 'id', 'nextpageid', 0, 'last page not found');
		$this->setTo('pages', 'prevpageid', $lastpageid, 'id', $firstpageid, 'unable to link last page to first page');
		$this->setTo('pages', 'nextpageid', $firstpageid, 'id', $lastpageid, 'unable to link first page to last page');

		// remove the page to be moved
		$ringprevpageid = $this->getFrom('pages', 'prevpageid', 'id', $movepage->id, 'ID of previous page not found');
		if ($movepage->qtype == LL_ENDOFBRANCH) {
			$ringnextpageid = $this->getFrom('pages', 'id', 'prevpageid', $movepage->id, 'ID of page after EOB not found');
		} else {
			$ringnextpageid = $this->getFrom('pages', 'nextpageid', 'id', $movepage->id, 'ID of next page not found');
		}
		// DO NOT change the nextpageid of the preceding page if it is an EOB record
		if (get_field('languagelesson_pages', 'qtype', 'id', $ringprevpageid) != LL_ENDOFBRANCH) {
			$this->setTo('pages', 'nextpageid', $ringnextpageid, 'id', $ringprevpageid, 'could not excise page to move');
		}
		$this->setTo('pages', 'prevpageid', $ringprevpageid, 'id', $ringnextpageid, 'could not remove page to move');
		
		// insert movepage into its new place 
		// check to see if the page is this getting moved after is an ENDOFBRANCH page
		if (get_field('languagelesson_pages', 'qtype', 'id', $after) == LL_ENDOFBRANCH) {
			// if so and the EOB's nextpageid value is equal to the parentid of its corresponding branch record, then this page is
			// getting inserted at the head of a branch, so pull the next branch's firstpage value as the nextpageid for the moved
			// page
			$eob = get_record('languagelesson_pages', 'id', $after);
			$branch = get_record('languagelesson_branches', 'id', $eob->branchid);
			if ($eob->nextpageid == $branch->parentid) {
				$newnextpageid = get_field('languagelesson_branches', 'firstpage', 'ordering', $branch->ordering+1, 'parentid',
						$branch->parentid);
				// if the result is 0, that means the branch being moved into is empty, so get the ID of the only page in it (the
				// EOB page) as the newnextpageid
				if (! $newnextpageid) {
					$nextbranchid = get_field('languagelesson_branches', 'id', 'ordering', $branch->ordering+1, 'parentid',
							$branch->parentid);
					$newnextpageid = $this->getFrom('pages', 'id', 'branchid', $nextbranchid, 'could not find next branch EOB
							record');
				}
			}
			// otherwise, the page is getting inserted after the end of the last branch, so it's not a part of the next depth level
			// down,  so can just use the last branch's pointer to the next record (since it's the last branch, it will point to the
			// next page, not the parent branch table page)
			else {
				$newnextpageid = $eob->nextpageid;
			}
		} else {
			$newnextpageid = get_field("languagelesson_pages", "nextpageid", "id", $after);
		}
		// error-check to see if we got a nextpageid value 
		if (!$newnextpageid) {
			error("Move: nextpageid not found");
		}

		// set pointers on surrounding pages for the moved page
		// DO NOT change the nextpageid value for the $after page if it is an EOB record
		if (get_field('languagelesson_pages', 'qtype', 'id', $after) != LL_ENDOFBRANCH) {
			$this->setTo('pages', 'nextpageid', $movepage->id, 'id', $after, 'could not point to moved page');
		}
		$this->setTo('pages', 'prevpageid', $movepage->id, 'id', $newnextpageid, 'could not point from moved page');
		// and set the links in the moved page
		$this->setTo('pages', 'prevpageid', $after, 'id', $movepage->id, 'could not set moved page prevpageid');
		// if the page being moved is an EOB record, its nextpageid will not change unless it is end of the last branch
		if ($movepage->qtype != LL_ENDOFBRANCH || $this->isLastBranchEnd($movepage)) {
			$this->setTo('pages', 'nextpageid', $newnextpageid, 'id', $movepage->id, 'could not set moved page nextpageid');
		}
		
		// break the ring
		$newlastpageid = $this->getFrom('pages', 'prevpageid', 'id', $newfirstpageid, 'ID of new last page not found');
		$this->setTo('pages', 'prevpageid', 0, 'id', $newfirstpageid, 'failed to delink first page from last page');
		$this->setTo('pages', 'nextpageid', 0, 'id', $newlastpageid, 'failed to unlink last page from first page');


		// check if newnextpageid is still valid
		if ($newlastpageid == $movepage->id) {
			$newnextpageid = 0;
		}
		
		// if the page moved was not an ENDOFBRANCH, then take care of any branchid and firstpage pointer issues that may have
		// come up; if it is an ENDOFBRANCH but not the last, then fix the firstpage pointer of the following branch
		if ($movepage->qtype != LL_ENDOFBRANCH) {
			$this->repairBranchPtrs($movepage, $oldnextpageid, $newnextpageid);
		} else if (! $this->isLastBranchEnd($movepage)) {
			$thisBranch = get_record('languagelesson_branches', 'id', $movepage->branchid);
			$nextbranch = get_field('languagelesson_branches', 'id', 'ordering', $thisBranch->ordering+1, 'parentid',
					$thisBranch->parentid);
			$this->setTo('branches', 'firstpage', $newnextpageid, 'id', $nextbranch, 'could not update first page pointer of next
					branch');
		}

		// since moving may have completely screwed ordering values, just rebuild the LL's ordering
		languagelesson_update_ordering($this->lessonid);

	}



	/*
	 * Determine the ID of the first page of the LL after the page is moved
	 * @param object $movepage The page record being moved
	 * @param int $after The ID of the page directly before the movepage's new location
	 */
	private function findNewFirstPage($movepage, $after) {
		if (!$after) {
			// the moved page is the new first page
			$newfirstpageid = $movepage->id;
			// reset $after so that is points to the last page 
			// (when the pages are in a ring this will in effect be the first page)
			if ($movepage->nextpageid) {
				$after = $this->getFrom('pages', 'id', 'nextpageid', 0, 'last page id not found');
			} else {
				// the page being moved is the last page, so the new last page will be it's previous page
				$after = $movepage->prevpageid;
			}
		} elseif (!$movepage->prevpageid) {
			// the page to be moved was the first page, so the following page must be the new first page
			$newfirstpageid = $movepage->nextpageid;
		} else {
			// the current first page remains the first page
			$newfirstpageid = $this->getFrom('pages', 'id', 'prevpageid', 0, 'current first page id not found');
		}

		return $newfirstpageid;
	}


	/*
	 * Shorthand function to determine if the input ENDOFBRANCH record is the end of the last branch of its BT
	 * @param object $eob The ENDOFBRANCH page to check
	 */
	private function isLastBranchEnd($eob) {
		$parentid = get_field('languagelesson_branches', 'parentid', 'id', $eob->branchid);
		return $parentid != $eob->nextpageid;
	}


	/*
	 * Handle updating branch-related information for the moved page
	 * @param object $movepage The record of the page being moved
	 * @param int $oldnextpageid The ID of the page after the movepage's old location
	 * @param int $newnextpageid The ID of the page after the movepage's new location
	 */
	private function repairBranchPtrs($movepage, $oldnextpageid, $newnextpageid) {
		// if the page was the first page in a branch before, update the branch's firstpage value to the page's old nextpageid value
		if ($movepage->branchid && $movepage->id == get_field('languagelesson_branches', 'firstpage', 'id', $movepage->branchid)) {
			$this->setTo('branches', 'firstpage', $oldnextpageid, 'id', $movepage->branchid, 'unable to update branch firstpage
					value');
		}

		// now if the page has a nextpage and that page is in a branch, set this page's branchid to that page's branchid value
		// NOTE that a page can never have been moved somewhere inside a branch and NOT have a nextpage (because of ENDOFBRANCH records)
		// similarly, no page that is not in a branch will have a next page that is in a branch, since there must be an intermediary
		// branch table
		if ($newnextpageid && $newbranch = get_field('languagelesson_pages', 'branchid', 'id', $newnextpageid)) {
			$this->setTo('pages', 'branchid', $newbranch, 'id', $movepage->id, 'unable to set the moved page branchid value');

			// since it is in a branch, need to check if it was moved in as the first page in the branch
			$newprevpageid = get_field('languagelesson_pages', 'prevpageid', 'id', $movepage->id);
			if (get_field('languagelesson_pages', 'branchid', 'id', $newprevpageid) != $newbranch) {
				$this->setTo('branches', 'firstpage', $movepage->id, 'id', $newbranch, 'unable to set moved page as the first
						page in its branch');
			}
		} else {
			$this->setTo('pages', 'branchid', null, 'id', $movepage->id, 'unable to unset the moved page branchid value');
		}

		// now check if the movepage's old branch is now empty; if so, need to set its firstpage value to 0
		if (count_records('languagelesson_pages', 'branchid', $movepage->branchid) == 1) { //only the EOB page
			$this->setTo('branches', 'firstpage', 0, 'id', $movepage->branchid, 'could not mark the old branch as empty');
		}
	}




	// shorthand functions to support lots of error messaging, but keep functions above brief
	private function getFrom($table, $field, $searchfield, $searchval, $error) {
		if (!$val = get_field("languagelesson_$table", $field, $searchfield, $searchval, 'lessonid', $this->lessonid)) {
			error("Move: $error");
		}
		return $val;
	}
	private function setTo($table, $setfield, $setval, $findfield, $findval, $error) {
        if (!set_field("languagelesson_$table", $setfield, $setval, $findfield, $findval)) {
            error("Move: $error");
        }
	}

}
	

?>
