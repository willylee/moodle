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

    $pageid = required_param('pageid', PARAM_INT);
    if (!$thispage = get_record("languagelesson_pages", "id", $pageid)) {
        error("Delete: page record not found");
    }
	
    // first delete all the associated records...
    if (! delete_records("languagelesson_attempts", "pageid", $pageid)) {
		error('Deleting page: could not delete attempt records!');
	}
    // ...now delete all submitted files for this page...
    if (! languagelesson_delete_user_files($lesson, $pageid)) {
		error('Deleting page: could not delete submitted files!');
	}
	// ...now delete the answers...
    if (! delete_records("languagelesson_answers", "pageid", $pageid)) {
		error('Deleting page: could not delete answer records!');
	}
    // ..and the page itself
    if (! delete_records("languagelesson_pages", "id", $pageid)) {
		error('Deleting page: could not delete page record!');
	}

    // repair the hole in the linkage
    if (!$thispage->prevpageid AND !$thispage->nextpageid) {
        //This is the only page, no repair needed
    } elseif (!$thispage->prevpageid) {
        // this is the first page...
        if (!$page = get_record("languagelesson_pages", "id", $thispage->nextpageid)) {
            error("Delete: next page not found");
        }
        if (!set_field("languagelesson_pages", "prevpageid", 0, "id", $page->id)) {
            error("Delete: unable to set prevpage link");
        }
    } elseif (!$thispage->nextpageid) {
        // this is the last page...
        if (!$page = get_record("languagelesson_pages", "id", $thispage->prevpageid)) {
            error("Delete: prev page not found");
        }
        if (!set_field("languagelesson_pages", "nextpageid", 0, "id", $page->id)) {
            error("Delete: unable to set nextpage link");
        }
    } else {
        // page is in the middle...
        if (!$prevpage = get_record("languagelesson_pages", "id", $thispage->prevpageid)) {
            error("Delete: prev page not found");
        }
        if (!$nextpage = get_record("languagelesson_pages", "id", $thispage->nextpageid)) {
            error("Delete: next page not found");
        }
        if (!set_field("languagelesson_pages", "nextpageid", $nextpage->id, "id", $prevpage->id)) {
            error("Delete: unable to set next link");
        }
        if (!set_field("languagelesson_pages", "prevpageid", $prevpage->id, "id", $nextpage->id)) {
            error("Delete: unable to set prev link");
        }
    }

	// repair the hole in the ordering
	$changePages = get_records_select('languagelesson_pages', "ordering > $thispage->ordering", 'ordering');
	foreach ($changePages as $page) {
		if (!set_field('languagelesson_pages', 'ordering', $page->ordering - 1, 'id', $page->id)) {
			error('Delete: unable to update ordering value');
		}
	}

    languagelesson_set_message(get_string('deletedpage', 'languagelesson').': '.format_string($thispage->title, true), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
?>
