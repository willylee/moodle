<?php // $Id: moveit.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for actually moving the page (database changes)
 *
 * @version $Id: moveit.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    require_sesskey();

    $pageid = required_param('pageid', PARAM_INT); //  page to move
    if (!$page = get_record("languagelesson_pages", "id", $pageid)) {
        error("Moveit: page not found");
    }
    $after = required_param('after', PARAM_INT); // target page

    // first step. determine the new first page
    // (this is done first as the current first page will be lost in the next step)
    if (!$after) {
        // the moved page is the new first page
        $newfirstpageid = $pageid;
        // reset $after so that is points to the last page 
        // (when the pages are in a ring this will in effect be the first page)
        if ($page->nextpageid) {
            if (!$after = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "nextpageid", 0)) {
                error("Moveit: last page id not found");
            }
        } else {
            // the page being moved is the last page, so the new last page will be
            $after = $page->prevpageid;
        }
    } elseif (!$page->prevpageid) {
        // the page to be moved was the first page, so the following page must be the new first page
        $newfirstpageid = $page->nextpageid;
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
    if (!$prevpageid = get_field("languagelesson_pages", "prevpageid", "id", $pageid)) {
        error("Moveit: prevpageid not found");
    }
    if (!$nextpageid = get_field("languagelesson_pages", "nextpageid", "id", $pageid)) {
        error("Moveit: nextpageid not found");
    }
    if (!set_field("languagelesson_pages", "nextpageid", $nextpageid, "id", $prevpageid)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "prevpageid", $prevpageid, "id", $nextpageid)) {
        error("Moveit: unable to update link");
    }
    
    // fourth step. insert page to be moved in new place...
    if (!$nextpageid = get_field("languagelesson_pages", "nextpageid", "id", $after)) {
        error("Movit: nextpageid not found");
    }
    if (!set_field("languagelesson_pages", "nextpageid", $pageid, "id", $after)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "prevpageid", $pageid, "id", $nextpageid)) {
        error("Moveit: unable to update link");
    }
    // ...and set the links in the moved page
    if (!set_field("languagelesson_pages", "prevpageid", $after, "id", $pageid)) {
        error("Moveit: unable to update link");
    }
    if (!set_field("languagelesson_pages", "nextpageid", $nextpageid, "id", $pageid)) {
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

	
	
	// handle branch stuff
	
	// if the page was the first page in a branch before, update the branch's firstpage value to the page's old nextpageid value
	if ($page->branchid && $page->id == get_field('languagelesson_branches', 'firstpage', 'id', $page->branchid)) {
		if (! set_field('languagelesson_branches', 'firstpage', $page->nextpageid, 'id', $page->branchid)) {
			error('Moveit: unable to update branch firstpage value');
		}
	}

	// now if the page has a nextpage and that page is in a branch, set this page's branchid to that page's branchid value
	// NOTE that a page can never have been moved somewhere inside a branch and NOT have a nextpage (because of ENDOFBRANCH records)
	// similarly, no page that is not in a branch will have a next page that is in a branch, since there must be an intermediary branch
	// table
// TODO: error when moving a page from the head of one branch to the head of another
	$newnextpageid = get_field('languagelesson_pages', 'nextpageid', 'id', $page->id);
	error_log("newnextpageid is $newnextpageid");
	error_log("thebranch is " . get_field('languagelesson_pages', 'branchid', 'id', $newnextpageid));
	if ($newnextpageid && $thebranch = get_field('languagelesson_pages', 'branchid', 'id', $newnextpageid)) {
		if (! set_field('languagelesson_pages', 'branchid', $thebranch, 'id', $page->id)) {
			error('Moveit: unable to set the moved page\'s branchid');
		} else {
			$page->branchid = $thebranch;
		}

		// since it is in a branch, need to check if it was moved in as the first page in the branch
		$newprevpageid = get_field('languagelesson_pages', 'prevpageid', 'id', $page->id);
		if (get_field('languagelesson_pages', 'branchid', 'id', $newprevpageid) != $page->branchid) {
			if (! set_field('languagelesson_branches', 'firstpage', $page->id, 'id', $page->branchid)) {
				error('Moveit: unable to set moved page as the first page in its branch');
			}
		}
	} else {
		if (! set_field('languagelesson_pages', 'branchid', null, 'id', $page->id)) {
			error('Moveit: unable to unset the moved page\'s branchid');
		}
	}


	// since moving may have completely screwed ordering values, just rebuild the LL's ordering
	languagelesson_update_ordering($lesson->id);
	


    languagelesson_set_message(get_string('movedpage', 'languagelesson'), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
?>
