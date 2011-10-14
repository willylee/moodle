<?php // $Id$
/**
 * Action for adding an end of cluster page
 *
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/
    require_sesskey();

    // first get the preceeding page
    $pageid = required_param('pageid', PARAM_INT);
        
    $timenow = time();
    
    // the new page is not the first page (end of cluster always comes after an existing page)
    if (!$page = get_record("languagelesson_pages", "id", $pageid)) {
        error("Error: Could not find page");
    }
    
    // could put code in here to check if the user really can insert an end of cluster
    
    $newpage = new stdClass;
    $newpage->lessonid = $lesson->id;
    $newpage->prevpageid = $pageid;
    $newpage->nextpageid = $page->nextpageid;
    $newpage->qtype = LL_ENDOFCLUSTER;
    $newpage->timecreated = $timenow;
    $newpage->title = get_string("endofclustertitle", "languagelesson");
    $newpage->contents = get_string("endofclustertitle", "languagelesson");
    if (!$newpageid = insert_record("languagelesson_pages", $newpage)) {
        error("Insert page: end of cluster page not inserted");
    }
    // update the linked list...
    if (!set_field("languagelesson_pages", "nextpageid", $newpageid, "id", $pageid)) {
        error("Add end of cluster: unable to update link");
    }
    if ($page->nextpageid) {
        // the new page is not the last page
        if (!set_field("languagelesson_pages", "prevpageid", $newpageid, "id", $page->nextpageid)) {
            error("Insert end of cluster: unable to update previous link");
        }
    }
    // ..and the single "answer"
    $newanswer = new stdClass;
    $newanswer->lessonid = $lesson->id;
    $newanswer->pageid = $newpageid;
    $newanswer->timecreated = $timenow;
    $newanswer->jumpto = LL_NEXTPAGE;
    if(!$newanswerid = insert_record("languagelesson_answers", $newanswer)) {
        error("Add end of cluster: answer record not inserted");
    }
    languagelesson_set_message(get_string('addedendofcluster', 'languagelesson'), 'notifysuccess');
    redirect("$CFG->wwwroot/mod/languagelesson/edit.php?id=$cm->id");
