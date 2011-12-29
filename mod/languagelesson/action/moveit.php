<?php // $Id: moveit.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for actually moving the page (database changes)
 *
 * @version $Id: moveit.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    require_sesskey();

    $movepageid = required_param('pageid', PARAM_INT); //  page to move
    if (!$movepage = get_record("languagelesson_pages", "id", $movepageid)) {
        error("Moveit: page not found");
    }
    $after = required_param('after', PARAM_INT); // target page

	// store the old next page id, because we'll need it later (note that this is just for clarity; $movepage->nextpageid remains the
	// same throughout the script execution)
	$oldnextpageid = $movepage->nextpageid;

    // first step. determine the new first page
    // (this is done first as the current first page will be lost in the next step)
    if (!$after) {
        // the moved page is the new first page
        $newfirstpageid = $movepageid;
        // reset $after so that is points to the last page 
        // (when the pages are in a ring this will in effect be the first page)
        if ($movepage->nextpageid) {
            if (!$after = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "nextpageid", 0)) {
                error("Moveit: last page id not found");
            }
        } else {
            // the page being moved is the last page, so the new last page will be
            $after = $movepage->prevpageid;
        }
    } elseif (!$movepage->prevpageid) {
        // the page to be moved was the first page, so the following page must be the new first page
        $newfirstpageid = $movepage->nextpageid;
    } else {
        // the current first page remains the first page
        if (!$newfirstpageid = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "prevpageid", 0)) {
            error("Moveit: current first page id not found");
        }
    }

    // the rest is all unconditional...
    
    // second step. join pages into a ring 
    if (!$firstpageid = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "prevpageid", 0)) {
        error("Moveit: firstpageid not found");
    }
    if (!$lastpageid = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "nextpageid", 0)) {
        error("Moveit: lastpage not found");
    }
    if (!set_field("languagelesson_pages", "prevpageid", $lastpageid, "id", $firstpageid)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "nextpageid", $firstpageid, "id", $lastpageid)) {
        error("Moveit: unable to update link");
    }

    // third step. remove the page to be moved
    if (!$ringprevpageid = get_field("languagelesson_pages", "prevpageid", "id", $movepageid)) {
        error("Moveit: prevpageid not found");
    }
    if (!$ringnextpageid = get_field("languagelesson_pages", "nextpageid", "id", $movepageid)) {
        error("Moveit: nextpageid not found");
    }
    if (!set_field("languagelesson_pages", "nextpageid", $ringnextpageid, "id", $ringprevpageid)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "prevpageid", $ringprevpageid, "id", $ringnextpageid)) {
        error("Moveit: unable to update link");
    }
    
    // fourth step. insert page to be moved in new place...
	// check to see if the page is this getting moved after is an ENDOFBRANCH page
	if (get_field('languagelesson_pages', 'qtype', 'id', $after) == LL_ENDOFBRANCH) {
		// if so and the EOB's nextpageid value is equal to the parentid of its corresponding branch record, then this page is getting
		// inserted at the head of a branch, so pull the next branch's firstpage value as the nextpageid for the moved page
		$eob = get_record('languagelesson_pages', 'id', $after);
		$branch = get_record('languagelesson_branches', 'id', $eob->branchid);
		if ($eob->nextpageid == $branch->parentid) {
			$newnextpageid = get_field('languagelesson_branches', 'firstpage', 'ordering', $branch->ordering+1, 'parentid',
					$branch->parentid);
		}
		// otherwise, the page is getting inserted after the end of the last branch, so it's not a part of the next depth level down,
		// so can just use the last branch's pointer to the next record (since it's the last branch, it will point to the next page,
		// not the parent branch table page)
		else {
			$newnextpageid = $eob->nextpageid;
		}
	} else {
		$newnextpageid = get_field("languagelesson_pages", "nextpageid", "id", $after);
	}
	// error-check to see if we got a nextpageid value 
    if (!$newnextpageid) {
        error("Movit: nextpageid not found");
    }

    if (!set_field("languagelesson_pages", "nextpageid", $movepageid, "id", $after)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "prevpageid", $movepageid, "id", $newnextpageid)) {
        error("Moveit: unable to update link");
    }
    // ...and set the links in the moved page
    if (!set_field("languagelesson_pages", "prevpageid", $after, "id", $movepageid)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "nextpageid", $newnextpageid, "id", $movepageid)) {
        error("Moveit: unable to update link");
    }
    
    // fifth step. break the ring
    if (!$newlastpageid = get_field("languagelesson_pages", "prevpageid", "id", $newfirstpageid)) {
        error("Moveit: newlastpageid not found");
    }
    if (!set_field("languagelesson_pages", "prevpageid", 0, "id", $newfirstpageid)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "nextpageid", 0, "id", $newlastpageid)) {
            error("Moveit: unable to update link");
    }

	// check if newnextpageid is still valid
	if ($newlastpageid == $movepageid) {
		$newnextpageid = 0;
	}

	
	
	// handle branch stuff
	
	// if the page was the first page in a branch before, update the branch's firstpage value to the page's old nextpageid value
	if ($movepage->branchid && $movepageid == get_field('languagelesson_branches', 'firstpage', 'id', $movepage->branchid)) {
		if (! set_field('languagelesson_branches', 'firstpage', $oldnextpageid, 'id', $movepage->branchid)) {
			error('Moveit: unable to update branch firstpage value');
		}
	}

	// now if the page has a nextpage and that page is in a branch, set this page's branchid to that page's branchid value
	// NOTE that a page can never have been moved somewhere inside a branch and NOT have a nextpage (because of ENDOFBRANCH records)
	// similarly, no page that is not in a branch will have a next page that is in a branch, since there must be an intermediary branch
	// table
	if ($newnextpageid && $thebranch = get_field('languagelesson_pages', 'branchid', 'id', $newnextpageid)) {
		if (! set_field('languagelesson_pages', 'branchid', $thebranch, 'id', $movepageid)) {
			error('Moveit: unable to set the moved page\'s branchid');
		} else {
			$movepage->branchid = $thebranch;
		}

		// since it is in a branch, need to check if it was moved in as the first page in the branch
		$newprevpageid = get_field('languagelesson_pages', 'prevpageid', 'id', $movepageid);
		if (get_field('languagelesson_pages', 'branchid', 'id', $newprevpageid) != $movepage->branchid) {
			if (! set_field('languagelesson_branches', 'firstpage', $movepage->id, 'id', $movepage->branchid)) {
				error('Moveit: unable to set moved page as the first page in its branch');
			}
		}
	} else {
		if (! set_field('languagelesson_pages', 'branchid', null, 'id', $movepage->id)) {
			error('Moveit: unable to unset the moved page\'s branchid');
		}
	}


	// since moving may have completely screwed ordering values, just rebuild the LL's ordering
	languagelesson_update_ordering($lesson->id);
	


    languagelesson_set_message(get_string('movedpage', 'languagelesson'), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
?>
