<?php // $Id: delete.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Action for deleting a page
 *
 * @version $Id: delete.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    require_sesskey();
    
    require_once('lib.php');
	require_once('locallib.php');

	// pull the page to be deleted it
    $pageid = required_param('pageid', PARAM_INT);
    if (!$delPage = get_record("languagelesson_pages", "id", $pageid)) {
        error("Delete: page record not found");
    }

	// delete the page
	$deleter = new LanguageLessonPageDeleter();
	$deleter->lesson = $lesson;
	$deleter->deletePage($delPage);

	// and go back to the interface
    languagelesson_set_message(get_string('deletedpage', 'languagelesson').': '.format_string($delPage->title, true), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");


////////////////////////////////////////
////////////////////////////////////////

class LanguageLessonPageDeleter {

	// declare a lesson object for this class, just to have a placeholder
	public $lesson;


	/*
	 * Delete the hell out of a page.
	 * @param object $delPage The page record to delete.
	 */
	public function deletePage($delPage) {

		$this->deletePageData($delPage);

		// set the prevpageid and nextpageid values to use in patching the hole in the linked list
		$prevpageid = $delPage->prevpageid;
		$nextpageid = (($delPage->qtype == LL_ENDOFBRANCH) ? get_field('languagelesson_pages', 'id', 'prevpageid', $delPage->id)
															: $delPage->nextpageid);

		// now delete the page itself
		$this->deleteFrom('pages', 'id', $delPage->id, 'could not delete page record');

		// repair the hole in the linkage
		$this->repairLinkedList($prevpageid, $nextpageid);

		// and update ordering values
		// if we deleted a Branch Table, a variable number of pages was deleted and branchids are no longer valid, so just rebuild the
		// ordering and branchid values for the lesson
		if ($delPage->qtype == LL_BRANCHTABLE) {
			languagelesson_update_ordering($this->lesson->id);
		}
		// otherwise, can just linearly adjust all of the following ordering values
		else {
			if ($changePages = get_records_select('languagelesson_pages', "ordering > $delPage->ordering", 'ordering')) {
				foreach ($changePages as $page) {
					$this->setTo('pages', 'ordering', $page->ordering-1, 'id', $page->id, 'ordering value');
				}
			}
		}

		// if this was the first page in a branch, update the firstpage value for that branch
		if ($delPage->branchid && $delPage->id == get_field('languagelesson_branches', 'firstpage', 'id', $delPage->branchid)) {
			if(! set_field('languagelesson_branches', 'firstpage', $delPage->nextpageid, 'id', $delPage->branchid)) {
				error("Delete page: could not update firstpage value of branch");
			}
		}

		// update the lesson's calculated max grade
		languagelesson_recalculate_maxgrade($this->lesson->id);

	}


	/*
	 * Deletes all relevant data related to the delPage, based on its type
	 * @param object $delPage The page record to delete associated data for
	 */
	private function deletePageData($delPage) {
		switch ($delPage->qtype) {

			// These pages have no associated content
			case LL_CLUSTER:
			case LL_ENDOFCLUSTER:
			case LL_ENDOFBRANCH:
			break;
			
			// Need to delete ENDOFBRANCH records, seen branches, and branches
			case LL_BRANCHTABLE:
				$branches = get_records('languagelesson_branches', 'parentid', $delPage->id);
				foreach ($branches as $branch) {
					// delete the branch's ENDOFBRANCH page
					$eob = get_record('languagelesson_pages', 'qtype', LL_ENDOFBRANCH, 'branchid', $branch->id);
					$this->deletePage($eob);

					// delete any related seenbranch records and the branches themselves
					$this->deleteFrom('seenbranches', 'id', $branch->id, 'could not delete seen branch records');
					$this->deleteFrom('branches', 'id', $branch->id, 'could not delete branch record');

					// and reset the branch IDs for every page that was in this branch
					if ($branchpages = get_records('languagelesson_pages', 'branchid', $eob->branchid)) {
						foreach($branchpages as $bpid => $bp) {
							$this->setTo('pages', 'branchid', $delPage->branchid, 'id', $bpid, 'could not reset branchid');
						}
					}
				}
			break;

			// otherwise, it's a question page, so get rid of attempt data
			default:
				// student-submitted data
				$this->deleteFrom('attempts', 'pageid', $delPage->id, 'could not delete attempt records');
				$this->deleteFrom('manattempts', 'pageid', $delPage->id, 'could not delete manual attempt records');
				$this->deleteFrom('feedback', 'pageid', $delPage->id, 'could not delete feedback records');
				if (! languagelesson_delete_user_files($this->lesson, $delPage->id)) {
					error('Deleting page: could not delete submitted files!');
				}
				// question-specific data
				$this->deleteFrom('answers', 'pageid', $delPage->id, 'could not delete answer records');
			break;
		}
	}


	/*
	 * Patches the hole left in the prevpageid/nextpageid linked list structure left by removing the delPage
	 * @param int $prevpageid The ID of the page directly before the delPage
	 * @param int $nextpageid The ID of the page directly succeeding the delPage
	 */
	private function repairLinkedList($prevpageid, $nextpageid) {
		if (!$prevpageid AND !$nextpageid) {
			//This is the only page, no repair needed
		} elseif (!$prevpageid) {
			// this is the first page...
			$this->setTo('pages', 'prevpageid', 0, 'id', $nextpageid, 'prevpage link');
		} elseif (!$nextpageid) {
			// this is the last page...
			$this->setTo('pages', 'nextpageid', 0, 'id', $prevpageid, 'nextpage link');
		} else {
			// page is in the middle...
			$this->setTo('pages', 'nextpageid', $nextpageid, 'id', $prevpageid, 'next link');
			$this->setTo('pages', 'prevpageid', $prevpageid, 'id', $nextpageid, 'previous link');
		}
	}


	// these two functions allow for very specific error-checking but keep the code below brief
	private function deleteFrom($table, $field, $val, $error) {
		if (!delete_records("languagelesson_$table", $field, $val)) {
			error("Delete page: $error");
		}
	}
	private function setTo($table, $setfield, $setval, $findfield, $findval, $error) {
        if (!set_field("languagelesson_$table", $setfield, $setval, $findfield, $findval)) {
            error("Delete: unable to set $error");
        }
	}

}

?>
