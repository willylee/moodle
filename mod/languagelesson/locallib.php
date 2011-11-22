<?php // $Id: locallib.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Local library file for Lesson.  These are non-standard functions that are used
 * only by Lesson.
 *
 * @version $Id: locallib.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

//error_reporting(E_ALL error_reporting(E_ALL & ~(E_DEPRECATED|E_NOTICE));
//ini_set('display_errors','1');


///////////////////////////////////
// MODES
///////////////////////////////////
/**
 * Practice Type
 */
if (!defined("LL_TYPE_PRACTICE")) {
	define("LL_TYPE_PRACTICE", 0);
}
/**
 * Assignment Type
 */
if (!defined("LL_TYPE_ASSIGNMENT")) {
	define("LL_TYPE_ASSIGNMENT", 1);
}
/**
 * Test Type
 */
if (!defined("LL_TYPE_TEST")) {
	define("LL_TYPE_TEST", 2);
}
///////////////////////////////////
///////////////////////////////////


///////////////////////////////////
// PENALTY TYPES
///////////////////////////////////
/**
 * Use mean type
 */
if (!defined("LL_PENALTY_MEAN")) {
	define("LL_PENALTY_MEAN", 0);
}
/**
 * Use set penalty multiplier type
 */
if (!defined("LL_PENALTY_SET")) {
	define("LL_PENALTY_SET", 1);
}
///////////////////////////////////
///////////////////////////////////



/**
* Next page -> any page not seen before
*/    
if (!defined("LL_UNSEENPAGE")) {
    define("LL_UNSEENPAGE", 1); // Next page -> any page not seen before
}
/**
* Next page -> any page not answered correctly
*/
if (!defined("LL_UNANSWEREDPAGE")) {
    define("LL_UNANSWEREDPAGE", 2); // Next page -> any page not answered correctly
}

/**
* Define different lesson flows for next page
*/
$LL_NEXTPAGE_ACTION = array (0 => get_string("normal", "languagelesson"),
                          LL_UNSEENPAGE => get_string("showanunseenpage", "languagelesson"),
                          LL_UNANSWEREDPAGE => get_string("showanunansweredpage", "languagelesson") );




/////////////////////////////////////////////////////////////////
// DEFINE JUMP VALUES
/////////////////////////////////////////////////////////////////
//  TODO: instead of using define statements, create an array with all the jump values

/**
 * Jump to Next Page
 */
if (!defined("LL_NEXTPAGE")) {
    define("LL_NEXTPAGE", -1);
}
/**
 * End of Lesson
 */
if (!defined("LL_EOL")) {
    define("LL_EOL", -9);
}
/**
 * Jump to an unseen page within a branch and end of branch or end of lesson
 */
if (!defined("LL_UNSEENBRANCHPAGE")) {
    define("LL_UNSEENBRANCHPAGE", -50);
}
/**
 * Jump to Previous Page
 */
if (!defined("LL_PREVIOUSPAGE")) {
    define("LL_PREVIOUSPAGE", -40);
}
/**
 * Jump to a random page within a branch and end of branch or end of lesson
 */
if (!defined("LL_RANDOMPAGE")) {
    define("LL_RANDOMPAGE", -60);
}
/**
 * Jump to a random Branch
 */
if (!defined("LL_RANDOMBRANCH")) {
    define("LL_RANDOMBRANCH", -70);
}
/**
 * Cluster Jump
 */
if (!defined("LL_CLUSTERJUMP")) {
    define("LL_CLUSTERJUMP", -80);
}
/**
 * Undefined
 */    
if (!defined("LL_UNDEFINED")) {
    define("LL_UNDEFINED", -99);
}




/////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////








/////////////////////////////////////////////////////////////////
// DEFINE PAGE TYPES
/////////////////////////////////////////////////////////////////


// Lesson question types defined

/**
 * Description question type
 */
if (!defined("LL_DESCRIPTION")) {
	define("LL_DESCRIPTION", 	get_field('languagelesson_qtypes', 'id', 'textid', get_string('descriptiontextid', 'languagelesson')));
    //define("LL_DESCRIPTION",   "1");
}
/**
 * Multichoice question type
 */
if (!defined("LL_MULTICHOICE")) {
	define("LL_MULTICHOICE", 	get_field('languagelesson_qtypes', 'id', 'textid', get_string('multichoicetextid', 'languagelesson')));
    //define("LL_MULTICHOICE",	"2");
}
/**
 * True/False question type
 */
if (!defined("LL_TRUEFALSE")) {
    define("LL_TRUEFALSE",		get_field('languagelesson_qtypes', 'id', 'textid', get_string('truefalsetextid', 'languagelesson')));
}
/**
 * Short answer question type
 */
if (!defined("LL_SHORTANSWER")) {
    define("LL_SHORTANSWER", 	get_field('languagelesson_qtypes', 'id', 'textid', get_string('shortanswertextid', 'languagelesson')));
}
/**
 * Cloze question type
 */
if (!defined("LL_CLOZE")) {
	define("LL_CLOZE",		get_field('languagelesson_qtypes', 'id', 'textid', get_string('clozetextid', 'languagelesson')));
}
/**
 * Matching question type
 */
if (!defined("LL_MATCHING")) {
    define("LL_MATCHING",      get_field('languagelesson_qtypes', 'id', 'textid', get_string('matchingtextid', 'languagelesson')));
}
/**
 * Numerical question type
 */
if (!defined("LL_NUMERICAL")) {
    define("LL_NUMERICAL",     get_field('languagelesson_qtypes', 'id', 'textid', get_string('numericaltextid', 'languagelesson')));
}
/**
 * Essay question type
 */
if (!defined("LL_ESSAY")) {
    define("LL_ESSAY", 		get_field('languagelesson_qtypes', 'id', 'textid', get_string('essaytextid', 'languagelesson')));
}
/**
 * Audio question type
 */
 if (!defined("LL_AUDIO")) {
 	 define("LL_AUDIO", 		get_field('languagelesson_qtypes', 'id', 'textid', get_string('audiotextid', 'languagelesson')));
 	 }
/**
 * Video question type
 */
 if (!defined("LL_VIDEO")) {
     define("LL_VIDEO", 		get_field('languagelesson_qtypes', 'id', 'textid', get_string('videotextid', 'languagelesson')));
 }




/**
 * Lesson question type array.
 * Contains all question types used
 *
 * Decided to fetch these using get_string, as opposed to from the database, for two reasons:
 * 1) Speed -- this file is included in every single page used in the language lesson module,
 * 				and adding an additional 10 database queries to it is not desirable.
 * 2) Changeability -- leaving the database out of it makes it easier to rename a question type,
 * 						if necessary; however, please be aware that the database entry for the
 * 						question type should also be updated to reflect a name change
 */
$LL_QUESTION_TYPE = array (
								LL_DESCRIPTION   => get_string("descriptionname", "languagelesson"),
								LL_MULTICHOICE => get_string("multichoicename", "languagelesson"),
								LL_TRUEFALSE     => get_string("truefalsename", "languagelesson"),
								LL_SHORTANSWER   => get_string("shortanswername", "languagelesson"),
								LL_CLOZE		=> get_string('clozename', 'languagelesson'),
								LL_MATCHING      => get_string("matchingname", "languagelesson"),
								LL_NUMERICAL     => get_string("numericalname", "languagelesson"),
								LL_ESSAY           => get_string("essayname", "languagelesson"),
								LL_AUDIO  => get_string("audioname", "languagelesson"),
								LL_VIDEO  => get_string("videoname", "languagelesson")
                              );

// Non-question page types

/**
 * Branch Table page
 */
if (!defined("LL_BRANCHTABLE")) {
    define("LL_BRANCHTABLE",   "20");
}
/**
 * End of Branch page
 */
if (!defined("LL_ENDOFBRANCH")) {
    define("LL_ENDOFBRANCH",   "21");
}
/**
 * Start of Cluster page
 */
if (!defined("LL_CLUSTER")) {
    define("LL_CLUSTER",   "30");
}
/**
 * End of Cluster page
 */
if (!defined("LL_ENDOFCLUSTER")) {
    define("LL_ENDOFCLUSTER",   "31");
}


/////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////




// other variables...

/**
 * Flag for the editor for the answer textarea.
 */
if (!defined("LL_ANSWER_EDITOR")) {
    define("LL_ANSWER_EDITOR",   "1");
}
/**
 * Flag for the editor for the response textarea.
 */
if (!defined("LL_RESPONSE_EDITOR")) {
    define("LL_RESPONSE_EDITOR",   "2");
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other languagelesson functions go here.  Each of them must have a name that 
/// starts with languagelesson_

/**
 * Print the standard header for languagelesson module
 *
 * @uses $CFG
 * @uses $USER
 * @param object $cm Course module record object
 * @param object $course Couse record object
 * @param object $lesson Lesson module record object
 * @param string $currenttab Current tab for the lesson tabs
 * @return boolean
 **/
function languagelesson_print_header($cm, $course, $lesson, $currenttab = '') {
    global $CFG, $USER;

    $strlesson = get_string('modulename', 'languagelesson');
    $strname   = format_string($lesson->name, true, $course->id);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (has_capability('mod/languagelesson:edit', $context)) {
        $button = update_module_button($cm->id, $course->id, $strlesson);
    } else {
        $button = '';
    }

/// Header setup
    $navigation = build_navigation('', $cm);
    
/// Print header, heading, tabs and messages
    print_header("$course->shortname: $strname", $course->fullname, $navigation,
                  '', '', true, $button, navmenu($course, $cm));

    if (has_capability('mod/languagelesson:grade', $context)) {
        print_heading_with_help($strname, "overview", "languagelesson");

        if (!empty($currenttab)) {
            include($CFG->dirroot.'/mod/languagelesson/tabs.php');
        }
    } else {
        print_heading($strname);
    }

    languagelesson_print_messages();

    return true;
}




/**
 * Returns course module, course and module instance given 
 * either the course module ID or a lesson module ID.
 *
 * @param int $cmid Course Module ID
 * @param int $lessonid Lesson module instance ID
 * @return array array($cm, $course, $lesson)
 **/
function languagelesson_get_basics($cmid = 0, $lessonid = 0) {
    if ($cmid) {
        if (!$cm = get_coursemodule_from_id('languagelesson', $cmid)) {
            error('Course Module ID was incorrect');
        }
        if (!$course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (!$lesson = get_record('languagelesson', 'id', $cm->instance)) {
            error('Course module is incorrect');
        }
    } else if ($lessonid) {
        if (!$lesson = get_record('languagelesson', 'id', $lessonid)) {
            error('Course module is incorrect');
        }
        if (!$course = get_record('course', 'id', $lesson->course)) {
            error('Course is misconfigured');
        }
        if (!$cm = get_coursemodule_from_instance('lesson', $lesson->id, $course->id)) {
            error('Course Module ID was incorrect');
        }
    } else {
        error('No course module ID or lesson ID were passed');
    }
    
    return array($cm, $course, $lesson);
}





/**
 * Sets a message to be printed.  Messages are printed
 * by calling {@link languagelesson_print_messages()}.
 *
 * @uses $SESSION
 * @param string $message The message to be printed
 * @param string $class Class to be passed to {@link notify()}.  Usually notifyproblem or notifysuccess.
 * @param string $align Alignment of the message
 * @return boolean
 **/
function languagelesson_set_message($message, $class="notifyproblem", $align='center') {
    global $SESSION;
    
    if (empty($SESSION->lesson_messages) or !is_array($SESSION->lesson_messages)) {
        $SESSION->lesson_messages = array();
    }
    
    $SESSION->lesson_messages[] = array($message, $class, $align);
    
    return true;
}





/**
 * Print all set messages.
 *
 * See {@link languagelesson_set_message()} for setting messages.
 *
 * Uses {@link notify()} to print the messages.
 *
 * @uses $SESSION
 * @return boolean
 **/
function languagelesson_print_messages() {
    global $SESSION;
    
    if (empty($SESSION->lesson_messages)) {
        // No messages to print
        return true;
    }
    
    foreach($SESSION->lesson_messages as $message) {
        notify($message[0], $message[1], $message[2]);
    }
    
    // Reset
    unset($SESSION->lesson_messages);
    
    return true;
}



/**
 * Prints a lesson link that submits a form.
 *
 * @param string $name Name of the link or button
 * @param string $form The name of the form to be submitted
 * @param string $onclick The onclick event for the button
 * @param boolean $return Return flag
 * @return mixed boolean/html
 **/
function languagelesson_print_submit_link($value, $form, $onclick='', $return = false) {
	if (!empty($onclick)) {
		$onclick = "onclick=\"$onclick\"";
	}

    $output = "<div class=\"lessonbutton standardbutton\">\n";
    $output .= "<input type=\"submit\" value=\"$value\" $onclick />";
	$output .= "</div>";
    
    if ($return) {
        return $output;
    } else {
        echo $output;
        return true;
    }
}





/**
 * Prints a time remaining in the following format: H:MM:SS
 *
 * @param int $starttime Time when the lesson started
 * @param int $maxtime Length of the lesson
 * @param boolean $return Return output switch
 * @return mixed boolean/string
 **/
function languagelesson_print_time_remaining($starttime, $maxtime, $return = false) {
    // Calculate hours, minutes and seconds
    $timeleft = $starttime + $maxtime * 60 - time();
    $hours = floor($timeleft/3600);
    $timeleft = $timeleft - ($hours * 3600);
    $minutes = floor($timeleft/60);
    $secs = $timeleft - ($minutes * 60);
    
    if ($minutes < 10) {
        $minutes = "0$minutes";
    }
    if ($secs < 10) {
        $secs = "0$secs";
    }
    $output   = array();
    $output[] = $hours;
    $output[] = $minutes;
    $output[] = $secs;
    
    $output = implode(':', $output);
    
    if ($return) {
        return $output;
    } else {
        echo $output;
        return true;
    }
}





/**
 * Prints the page action buttons
 *
 * Move/Edit/Preview/Delete
 *
 * @uses $CFG
 * @param int $cmid Course Module ID
 * @param object $page Page record
 * @param boolean $printmove Flag to print the move button or not
 * @param boolean $printaddpage Flag to print the add page drop-down or not
 * @param boolean $return Return flag
 * @return mixed boolean/string
 **/
function languagelesson_print_page_actions($cmid, $page, $printmove, $printaddpage = false, $return = false) {
    global $CFG;
    
    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    $actions = array();
    
    if (has_capability('mod/languagelesson:edit', $context)) {
        if ($printmove) {
            $actions[] = "<a title=\"".get_string('move')."\" href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;action=move&amp;pageid=$page->id\">
                          <img src=\"$CFG->pixpath/t/move.gif\" class=\"iconsmall\" alt=\"".get_string('move')."\" /></a>\n";
        }
        $actions[] = "<a title=\"".get_string('update')."\" href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;action=editpage&amp;pageid=$page->id\">
                      <img src=\"$CFG->pixpath/t/edit.gif\" class=\"iconsmall\" alt=\"".get_string('update')."\" /></a>\n";
        
        $actions[] = "<a title=\"".get_string('preview')."\" href=\"$CFG->wwwroot/mod/languagelesson/view.php?id=$cmid&amp;pageid=$page->id\">
                      <img src=\"$CFG->pixpath/t/preview.gif\" class=\"iconsmall\" alt=\"".get_string('preview')."\" /></a>\n";
        
        $actions[] = "<a title=\"".get_string('delete')."\" href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;sesskey=".sesskey()."&amp;action=confirmdelete&amp;pageid=$page->id\">
                      <img src=\"$CFG->pixpath/t/delete.gif\" class=\"iconsmall\" alt=\"".get_string('delete')."\" /></a>\n";
        
        if ($printaddpage) {
            // Add page drop-down
            $options = array();
            $options['addcluster&amp;sesskey='.sesskey()]      = get_string('clustertitle', 'languagelesson');
            $options['addendofcluster&amp;sesskey='.sesskey()] = get_string('endofclustertitle', 'languagelesson');
            $options['addbranchtable']                         = get_string('branchtable', 'languagelesson');
            $options['addendofbranch&amp;sesskey='.sesskey()]  = get_string('endofbranch', 'languagelesson');
            $options['addpage']                                = get_string('question', 'languagelesson');
            // Base url
            $common = "$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;pageid=$page->id&amp;action=";
        
            $actions[] = popup_form($common, $options, "addpage_$page->id", '', get_string('addpage', 'languagelesson').'...', '', '', true);
        }
    }
    
    $actions = implode(' ', $actions);
    
    if ($return) {
        return $actions;
    } else {
        echo $actions;
        return false;
    }
}




/**
 * Prints the add links in expanded view or single view when editing
 *
 * @uses $CFG
 * @param int $cmid Course Module ID
 * @param int $prevpageid Previous page id
 * @param boolean $return Return flag
 * @return mixed boolean/string
 * @todo &amp;pageid does not make sense, it is prevpageid
 **/
function languagelesson_print_add_links($cmid, $prevpageid, $return = false) {
    global $CFG;
    
    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    
    $links = '';
    if (has_capability('mod/languagelesson:edit', $context)) {
        $links = array();
        $links[] = "<a href=\"$CFG->wwwroot/mod/languagelesson/import.php?id=$cmid&amp;pageid=$prevpageid\">".
                    get_string('importquestions', 'languagelesson').'</a>';
        
        $links[] = "<a href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;sesskey=".sesskey()."&amp;action=addcluster&amp;pageid=$prevpageid\">".
                    get_string('addcluster', 'languagelesson').'</a>';
        
        if ($prevpageid != 0) {
            $links[] = "<a href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;sesskey=".sesskey()."&amp;action=addendofcluster&amp;pageid=$prevpageid\">".
                        get_string('addendofcluster', 'languagelesson').'</a>';
        }
        $links[] = "<a href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;action=addbranchtable&amp;pageid=$prevpageid\">".
                    get_string('addabranchtable', 'languagelesson').'</a>';
        
        if ($prevpageid != 0) {
            $links[] = "<a href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;sesskey=".sesskey()."&amp;action=addendofbranch&amp;pageid=$prevpageid\">".
                        get_string('addanendofbranch', 'languagelesson').'</a>';
        }
        
        $links[] = "<a href=\"$CFG->wwwroot/mod/languagelesson/lesson.php?id=$cmid&amp;action=addpage&amp;pageid=$prevpageid\">".
                    get_string('addaquestionpagehere', 'languagelesson').'</a>';
        
        $links = implode(" | \n", $links);
        $links = "\n<div class=\"addlinks\">\n$links\n</div>\n";
    }

    if ($return) {
        return $links;
    } else {
        echo $links;
        return true;
    }
}




/**
 * Returns the string for a page type
 *
 * @uses $LL_QUESTION_TYPE
 * @param int $qtype Page type
 * @return string
 **/
function languagelesson_get_qtype_name($qtype) {
    global $LL_QUESTION_TYPE;
    switch ($qtype) {
        case LL_ESSAY :
        case LL_SHORTANSWER :
        case LL_MULTICHOICE :
        case LL_MATCHING :
        case LL_TRUEFALSE :
        //case LL_NUMERICAL :
        case LL_AUDIO:
        case LL_VIDEO:
            return $LL_QUESTION_TYPE[$qtype];
            break;
        case LL_BRANCHTABLE :    
            return get_string("branchtable", "languagelesson");
            break;
        case LL_ENDOFBRANCH :
            return get_string("endofbranch", "languagelesson");
            break;
        case LL_CLUSTER :
            return get_string("clustertitle", "languagelesson");
            break;
        case LL_ENDOFCLUSTER :
            return get_string("endofclustertitle", "languagelesson");
            break;
        default:
            return '';
            break;
    }
}





/**
 * Returns the string for a jump name
 *
 * @param int $jumpto Jump code or page ID
 * @return string
 **/
function languagelesson_get_jump_name($jumpto) {
    if ($jumpto == 0) {
        $jumptitle = get_string('thispage', 'languagelesson');
    } elseif ($jumpto == LL_NEXTPAGE) {
        $jumptitle = get_string('nextpage', 'languagelesson');
    } elseif ($jumpto == LL_EOL) {
        $jumptitle = get_string('endoflesson', 'languagelesson');
    } elseif ($jumpto == LL_UNSEENBRANCHPAGE) {
        $jumptitle = get_string('unseenpageinbranch', 'languagelesson');
    } elseif ($jumpto == LL_PREVIOUSPAGE) {
        $jumptitle = get_string('previouspage', 'languagelesson');
    } elseif ($jumpto == LL_RANDOMPAGE) {
        $jumptitle = get_string('randompageinbranch', 'languagelesson');
    } elseif ($jumpto == LL_RANDOMBRANCH) {
        $jumptitle = get_string('randombranch', 'languagelesson');
    } elseif ($jumpto == LL_CLUSTERJUMP) {
        $jumptitle = get_string('clusterjump', 'languagelesson');
    } else {
        if (!$jumptitle = get_field('languagelesson_pages', 'title', 'id', $jumpto)) {
            $jumptitle = '<strong>'.get_string('notdefined', 'languagelesson').'</strong>';
        }
    }
    
    return format_string($jumptitle,true);
}





/**
 * Given some question info and some data about the the answers
 * this function parses, organises and saves the question
 *
 * This is only used when IMPORTING questions and is only called
 * from format.php
 * Lifted from mod/quiz/lib.php - 
 *    1. all reference to oldanswers removed
 *    2. all reference to quiz_multichoice table removed
 *    3. In SHORTANSWER questions usecase is store in the qoption field
 *    4. In NUMERIC questions store the range as two answers
 *    5. TRUEFALSE options are ignored
 *    6. For MULTICHOICE questions with more than one answer the qoption field is true
 * 
 * @param opject $question Contains question data like question, type and answers.
 * @return object Returns $result->error or $result->notice.
 **/
function languagelesson_save_question_options($question) {
    
	/**
	 * THIS FUNCTION NEEDS SERIOUS WORK
	 *
	 * Need to fix score/grade handling, among several other things
	 */
	
    $timenow = time();
    switch ($question->qtype) {
        case LL_SHORTANSWER:

            $answers = array();
            $maxfraction = -1;

            // Insert all the new answers
            foreach ($question->answer as $key => $dataanswer) {
                if ($dataanswer != "") {
                    $answer = new stdClass;
                    $answer->lessonid   = $question->lessonid;
                    $answer->pageid   = $question->id;
                    if ($question->fraction[$key] >=0.5) {
                        $answer->jumpto = LL_NEXTPAGE;
                    }
					$answer->score = $question->fraction[$key];
                    $answer->timecreated   = $timenow;
                    $answer->answer   = $dataanswer;
                    $answer->response = $question->feedback[$key];
                    if (!$answer->id = insert_record("languagelesson_answers", $answer)) {
                        $result->error = "Could not insert shortanswer quiz answer!";
                        return $result;
                    }
                    $answers[] = $answer->id;
                    if ($question->fraction[$key] > $maxfraction) {
                        $maxfraction = $question->fraction[$key];
                    }
                }
            }


            /// Perform sanity checks on fractional grades
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $result->notice = get_string("fractionsnomax", "quiz", $maxfraction);
                return $result;
            }
            break;

		case LL_CLOZE :

			$answers = array();

			// Insert all the answers
			foreach ($question->answer as $key => $atext) {
				$answer = new stdClass;
				$answer->lessonid = $question->lessonid;
				$answer->pageid = $question->id;
				$answer->score = $question->fraction[$key];
				$answer->timecreated = $timenow;
				$answer->answer = $key . '|' . $atext;
				// check if it's a drop-down
				if (strpos($atext, '=') !== false && strpos($atext, ',')) {
					$answer->flags = 1;
				}
				// insert it
				if (!insert_record('languagelesson_answers', $answer)) {
					$result->error = 'Could not insert Cloze type answer!';
					return $result;
				}
			}
			// And insert the feedbacks
			foreach ($question->feedback as $score => $ftext) {
				$fb = new stdClass;
				$fb->lessonid = $question->lessonid;
				$fb->pageid = $question->id;
				$fb->score = $score;
				if ($fb->score > 0) { $fb->jumpto = LL_NEXTPAGE; }
				$fb->response = $ftext;
				$fb->timecreated = $timenow;
				if (!insert_record('languagelesson_answers', $fb)) {
					$result->error = 'Could not insert Cloze type feedback!';
					return $result;
				}
			}
			break;

        /*case LL_NUMERICAL:   // Note similarities to SHORTANSWER

            $answers = array();
            $maxfraction = -1;

            
            // for each answer store the pair of min and max values even if they are the same 
            foreach ($question->answer as $key => $dataanswer) {
                if ($dataanswer != "") {
                    $answer = new stdClass;
                    $answer->lessonid   = $question->lessonid;
                    $answer->pageid   = $question->id;
                    $answer->jumpto = LL_NEXTPAGE;
                    $answer->timecreated   = $timenow;
                    $min = $question->answer[$key] - $question->tolerance[$key];
                    $max = $question->answer[$key] + $question->tolerance[$key];
					$answer->score = $question->fraction[$key];
                    $answer->answer   = $min.":".$max;
                    // $answer->answer   = $question->min[$key].":".$question->max[$key]; original line for min/max
                    $answer->response = $question->feedback[$key];
                    if (!$answer->id = insert_record("languagelesson_answers", $answer)) {
                        $result->error = "Could not insert numerical quiz answer!";
                        return $result;
                    }
                    
                    $answers[] = $answer->id;
                    if ($question->fraction[$key] > $maxfraction) {
                        $maxfraction = $question->fraction[$key];
                    }
                }
            }

            /// Perform sanity checks on fractional grades
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $result->notice = get_string("fractionsnomax", "quiz", $maxfraction);
                return $result;
            }
        break;
	    */

        case LL_TRUEFALSE:

            // the truth
            $answer->lessonid   = $question->lessonid;
            $answer->pageid = $question->id;
            $answer->timecreated   = $timenow;
            $answer->answer = get_string("true", "quiz");
            if ((int)$question->correctanswer == 1) {
            	$answer->score = 1;
                $answer->jumpto = LL_NEXTPAGE;
            } else {
            	$answer->score = 0;
            }
            if (isset($question->feedbacktrue)) {
                $answer->response = $question->feedbacktrue;
            }
            if (!$true->id = insert_record("languagelesson_answers", $answer)) {
                $result->error = "Could not insert quiz answer \"true\")!";
                return $result;
            }

            // the lie    
            $answer = new stdClass;
            $answer->lessonid   = $question->lessonid;
            $answer->pageid = $question->id;
            $answer->timecreated   = $timenow;
            $answer->answer = get_string("false", "quiz");
            if ((int)$question->correctanswer == 0) {
                $answer->score = 1;
                $answer->jumpto = LL_NEXTPAGE;
            } else {
            	$answer->score = 0;
            }
            if (isset($question->feedbackfalse)) {
                $answer->response = $question->feedbackfalse;
            }
            if (!$false->id = insert_record("languagelesson_answers", $answer)) {
                $result->error = "Could not insert quiz answer \"false\")!";
                return $result;
            }

          break;

		//////////////////////////////////////////////////////////////
		// DIRTY HACK FOR TEMPORARY HANDLING OF DESCRIPTIONS
		case LL_DESCRIPTION:
        case LL_MULTICHOICE:

            $totalfraction = 0;
            $maxfraction = -1;

            $answers = array();

            // Insert all the new answers
            foreach ($question->answer as $key => $dataanswer) {
                if ($dataanswer != "") {
                    $answer = new stdClass;
                    $answer->lessonid   = $question->lessonid;
                    $answer->pageid   = $question->id;
                    $answer->timecreated   = $timenow;
					// ARGHBL NEED TO HANDLE SPECIFIC SCORES
					$answer->score = $question->fraction[$key];
					if ($answer->score > 0) { $answer->jumpto = LL_NEXTPAGE; }
                    $answer->answer   = $dataanswer;
                    $answer->response = $question->feedback[$key];
                    if (!$answer->id = insert_record("languagelesson_answers", $answer)) {
                        $result->error = "Could not insert multichoice quiz answer! ";
                        return $result;
                    }
                    // for Sanity checks
                    if ($question->fraction[$key] > 0) {                 
                        $totalfraction += $question->fraction[$key];
                    }
                    if ($question->fraction[$key] > $maxfraction) {
                        $maxfraction = $question->fraction[$key];
                    }
                }
            }

			////////////////////////////////////////////////////////////////
			// DIRTY, DIRTY HACK
			if (count($question->answer) > 0) {
				/// Perform sanity checks on fractional grades
				if ($question->single) {
					if ($maxfraction != 1) {
						$maxfraction = $maxfraction * 100;
						$result->notice = get_string("fractionsnomax", "quiz", $maxfraction);
						return $result;
					}
				} else {
					$totalfraction = round($totalfraction,2);
					if ($totalfraction != 1) {
						$totalfraction = $totalfraction * 100;
						$result->notice = get_string("fractionsaddwrong", "quiz", $totalfraction);
						return $result;
					}
				}
			}
			// END DIRTY, DIRTY HACK
			////////////////////////////////////////////////////////////////
		// END DIRTY HACK FOR TEMPORARY HANDLING OF DESCRIPTIONS
		//////////////////////////////////////////////////////////////
        break;

        case LL_MATCHING:

            $subquestions = array();

            $i = 0;
            // Insert all the new question+answer pairs
            foreach ($question->subquestions as $key => $questiontext) {
                $answertext = $question->subanswers[$key];
                if (!empty($questiontext) and !empty($answertext)) {
                    $answer = new stdClass;
                    $answer->lessonid   = $question->lessonid;
                    $answer->pageid   = $question->id;
                    $answer->timecreated   = $timenow;
                    $answer->answer = $questiontext;
                    $answer->response   = $answertext;
                    if ($i == 0) {
                        // first answer contains the correct answer jump
                        $answer->jumpto = LL_NEXTPAGE;
                    }
                    if (!$subquestion->id = insert_record("languagelesson_answers", $answer)) {
                        $result->error = "Could not insert quiz match subquestion!";
                        return $result;
                    }
                    $subquestions[] = $subquestion->id;
                    $i++;
                }
            }

            if (count($subquestions) < 3) {
                $result->notice = get_string("notenoughsubquestions", "quiz");
                return $result;
            }

            break;



      ///// added question types /////
        case LL_ESSAY:
        case LL_AUDIO:
        case LL_VIDEO:
        	$answer = new stdClass;
        	$answer->lessonid = $question->lessonid;
        	$answer->pageid = $question->id;
        	$answer->timecreated = $timenow;
        	$answer->jumpto = LL_NEXTPAGE;
        	$answer->score = 1;
        	
        	if (!insert_record("languagelesson_answers", $answer)) {
        		$result->error = "Could not insert languagelesson essay/audio/video answer!";
        		return $result;
        	}
        break;
        
        
      ///// added structural types /////
        case LL_BRANCHTABLE:
        	$branches = $question->branches;
        	
        	foreach ($branches as $branchdata) {
        		$answer = new stdClass;
        		$answer->lessonid = $question->lessonid;
        		$answer->pageid = $question->id;
        		$answer->timecreated = $timenow;
        		
        	  /// each $branchdata is an array consisting of
        	  /// [ <jumpto pageid> , <jump label> ]
        		$answer->answer = $branchdata[1];
        		$answer->jumpto = $branchdata[0];
        		$answer->score = 0;
        		
        		if (!insert_record("languagelesson_answers", $answer)) {
        			$result->error = "Could not insert languagelesson branchtable branch!";
        			return $result;
        		}
        	}
        break;
        
        case LL_ENDOFBRANCH:
        	$answer = new stdClass;
        	$answer->lessonid = $question->lessonid;
        	$answer->pageid = $question->id;
        	$answer->timecreated = $timenow;
        	
        	$answer->jumpto = $question->branchparent;
        	$answer->score = 0;
        	
        	if (!insert_record("languagelesson_answers", $answer)) {
        		$result->error = "Could not insert languagelesson endofbranch answer!";
        		return $result;
        	}
        break;
        
        case LL_CLUSTER:
        	$answer = new stdClass;
        	$answer->lessonid = $question->lessonid;
        	$answer->pageid = $question->id;
        	$answer->timecreated = $timenow;
        	
        	$answer->jumpto = LL_CLUSTERJUMP;
        	$answer->score = 0;
        	
        	if (!insert_record("languagelesson_answers", $answer)) {
        		$result->error = "Could not insert languagelesson cluster answer!";
        		return $result;
        	}
        break;
        
        case LL_ENDOFCLUSTER:
        	$answer = new stdClass;
        	$answer->lessonid = $question->lessonid;
        	$answer->pageid = $question->id;
        	$answer->timecreated = $timenow;
        	
        	$answer->jumpto = LL_NEXTPAGE;
        	$answer->score = 0;
        	
        	if (!insert_record("languagelesson_answers", $answer)) {
        		$result->error = "Could not insert languagelesson endofcluster answer!";
        		return $result;
        	}
        break;
        
      ///// end additions /////
        

        default:
            $result->error = get_string('unsupportedqtype', 'languagelesson', $question->qtype);
			return $result;
        break;
    }
    return true;
}





/**
 * Determines if a jumpto value is correct or not.
 *
 * returns true if jumpto page is (logically) after the pageid page or
 * if the jumpto value is a special value.  Returns false in all other cases.
 * 
 * @param int $pageid Id of the page from which you are jumping from.
 * @param int $jumpto The jumpto number.
 * @return boolean True or false after a series of tests.
 **/
function languagelesson_iscorrect($pageid, $jumpto) {
    
    // first test the special values
    if (!$jumpto) {
        // same page
        return false;
    } elseif ($jumpto == LL_NEXTPAGE) {
        return true;
    } elseif ($jumpto == LL_UNSEENBRANCHPAGE) {
        return true;
    } elseif ($jumpto == LL_RANDOMPAGE) {
        return true;
    } elseif ($jumpto == LL_CLUSTERJUMP) {
        return true;
    } elseif ($jumpto == LL_EOL) {
        return true;
    }
    // we have to run through the pages from pageid looking for jumpid
    if ($lessonid = get_field('languagelesson_pages', 'lessonid', 'id', $pageid)) {
        if ($pages = get_records('languagelesson_pages', 'lessonid', $lessonid, '', 'id, nextpageid')) {
            $apageid = $pages[$pageid]->nextpageid;
            while ($apageid != 0) {
                if ($jumpto == $apageid) {
                    return true;
                }
                $apageid = $pages[$apageid]->nextpageid;
            }
        }
    }
    return false;
}





/**
 * Checks to see if a page is a branch table or is
 * a page that is enclosed by a branch table and an end of branch or end of lesson.
 * May call this function: {@link languagelesson_is_page_in_branch()}
 *
 * @param int $lesson Id of the lesson to which the page belongs.
 * @param int $pageid Id of the page.
 * @return boolean True or false.
 **/
function languagelesson_display_branch_jumps($lessonid, $pageid) {
    if($pageid == 0) {
        // first page
        return false;
    }
    // get all of the lesson pages
    if (!$lessonpages = get_records_select("languagelesson_pages", "lessonid = $lessonid")) {
        // adding first page
        return false;
    }

    if ($lessonpages[$pageid]->qtype == LL_BRANCHTABLE) {
        return true;
    }
    
    return languagelesson_is_page_in_branch($lessonpages, $pageid);
}




/**
 * Checks to see if a page is a cluster page or is
 * a page that is enclosed by a cluster page and an end of cluster or end of lesson 
 * May call this function: {@link languagelesson_is_page_in_cluster()}
 * 
 * @param int $lesson Id of the lesson to which the page belongs.
 * @param int $pageid Id of the page.
 * @return boolean True or false.
 **/
function languagelesson_display_cluster_jump($lesson, $pageid) {
    if($pageid == 0) {
        // first page
        return false;
    }
    // get all of the lesson pages
    if (!$lessonpages = get_records_select("languagelesson_pages", "lessonid = $lesson")) {
        // adding first page
        return false;
    }

    if ($lessonpages[$pageid]->qtype == LL_CLUSTER) {
        return true;
    }
    
    return languagelesson_is_page_in_cluster($lessonpages, $pageid);

}





/**
 * Checks to see if a LL_CLUSTERJUMP or 
 * a LL_UNSEENBRANCHPAGE is used in a lesson.
 *
 * This function is only executed when a teacher is 
 * checking the navigation for a lesson.
 *
 * @param int $lesson Id of the lesson that is to be checked.
 * @return boolean True or false.
 **/
function languagelesson_display_teacher_warning($lesson) {
    // get all of the lesson answers
    if (!$lessonanswers = get_records_select("languagelesson_answers", "lessonid = $lesson")) {
        // no answers, then not useing cluster or unseen
        return false;
    }
    // just check for the first one that fulfills the requirements
    foreach ($lessonanswers as $lessonanswer) {
        if ($lessonanswer->jumpto == LL_CLUSTERJUMP || $lessonanswer->jumpto == LL_UNSEENBRANCHPAGE) {
            return true;
        }
    }
    
    // if no answers use either of the two jumps
    return false;
}






/**
 * Interprets LL_CLUSTERJUMP jumpto value.
 *
 * This will select a page randomly
 * and the page selected will be inbetween a cluster page and end of cluter or end of lesson
 * and the page selected will be a page that has not been viewed already
 * and if any pages are within a branch table or end of branch then only 1 page within 
 * the branch table or end of branch will be randomly selected (sub clustering).
 * 
 * @param int $lessonid Id of the lesson.
 * @param int $userid Id of the user.
 * @param int $pageid Id of the current page from which we are jumping from.
 * @return int The id of the next page.
 **/
function languagelesson_cluster_jump($lessonid, $userid, $pageid) {
    // get the number of retakes
    if (!$retakes = count_records("languagelesson_grades", "lessonid", $lessonid, "userid", $userid)) {
        $retakes = 0;
    }

    // get all the lesson_attempts aka what the user has seen
    if ($seen = get_records_select("languagelesson_attempts", "lessonid = $lessonid AND userid = $userid AND retry = $retakes", "timeseen DESC")) {
        foreach ($seen as $value) { // load it into an array that I can more easily use
            $seenpages[$value->pageid] = $value->pageid;
        }
    } else {
        $seenpages = array();
    }

    // get the lesson pages
    if (!$lessonpages = get_records_select("languagelesson_pages", "lessonid = $lessonid")) {
        error("Error: could not find records in lesson_pages table");
    }
    // find the start of the cluster
    while ($pageid != 0) { // this condition should not be satisfied... should be a cluster page
        if ($lessonpages[$pageid]->qtype == LL_CLUSTER) {
            break;
        }
        $pageid = $lessonpages[$pageid]->prevpageid;
    }

    $pageid = $lessonpages[$pageid]->nextpageid; // move down from the cluster page
    
    $clusterpages = array();
    while (true) {  // now load all the pages into the cluster that are not already inside of a branch table.
        if ($lessonpages[$pageid]->qtype == LL_ENDOFCLUSTER) {
            // store the endofcluster page's jump
            $exitjump = get_field("languagelesson_answers", "jumpto", "pageid", $pageid, "lessonid", $lessonid);
            if ($exitjump == LL_NEXTPAGE) {
                $exitjump = $lessonpages[$pageid]->nextpageid;
            }
            if ($exitjump == 0) {
                $exitjump = LL_EOL;
            }
            break;
        } elseif (!languagelesson_is_page_in_branch($lessonpages, $pageid) && $lessonpages[$pageid]->qtype != LL_ENDOFBRANCH) {
            // load page into array when it is not in a branch table and when it is not an endofbranch
            $clusterpages[] = $lessonpages[$pageid];
        }
        if ($lessonpages[$pageid]->nextpageid == 0) {
            // shouldn't ever get here... should be using endofcluster
            $exitjump = LL_EOL;
            break;
        } else {
            $pageid = $lessonpages[$pageid]->nextpageid;
        }
    }

    // filter out the ones we have seen
    $unseen = array();
    foreach ($clusterpages as $clusterpage) {
        if ($clusterpage->qtype == LL_BRANCHTABLE) {            // if branchtable, check to see if any pages inside have been viewed
            $branchpages = languagelesson_pages_in_branch($lessonpages, $clusterpage->id); // get the pages in the branchtable
            $flag = true;
            foreach ($branchpages as $branchpage) {
                if (array_key_exists($branchpage->id, $seenpages)) {  // check if any of the pages have been viewed
                    $flag = false;
                }
            }
            if ($flag && count($branchpages) > 0) {
                // add branch table
                $unseen[] = $clusterpage;
            }        
        } else {
            // add any other type of page that has not already been viewed
            if (!array_key_exists($clusterpage->id, $seenpages)) {
                $unseen[] = $clusterpage;
            }
        }
    }

    if (count($unseen) > 0) { // it does not contain elements, then use exitjump, otherwise find out next page/branch
        $nextpage = $unseen[rand(0, count($unseen)-1)];
    } else {
        return $exitjump; // seen all there is to see, leave the cluster
    }
    
    if ($nextpage->qtype == LL_BRANCHTABLE) { // if branch table, then pick a random page inside of it
        $branchpages = languagelesson_pages_in_branch($lessonpages, $nextpage->id);
        return $branchpages[rand(0, count($branchpages)-1)]->id;
    } else { // otherwise, return the page's id
        return $nextpage->id;
    }
}





/**
 * Returns pages that are within a branch table and another branch table, end of branch or end of lesson
 * 
 * @param array $lessonpages An array of lesson page objects.
 * @param int $branchid The id of the branch table that we would like the containing pages for.
 * @return array An array of lesson page objects.
 **/
function languagelesson_pages_in_branch($lessonpages, $branchid) {
    $pageid = $lessonpages[$branchid]->nextpageid;  // move to the first page after the branch table
    $pagesinbranch = array();
    
    while (true) { 
        if ($pageid == 0) { // EOL
            break;
        } elseif ($lessonpages[$pageid]->qtype == LL_BRANCHTABLE) {
            break;
        } elseif ($lessonpages[$pageid]->qtype == LL_ENDOFBRANCH) {
            break;
        }
        $pagesinbranch[] = $lessonpages[$pageid];
        $pageid = $lessonpages[$pageid]->nextpageid;
    }
    
    return $pagesinbranch;
}





/**
 * Interprets the LL_UNSEENBRANCHPAGE jump.
 * 
 * will return the pageid of a random unseen page that is within a branch
 *
 * @see languagelesson_pages_in_branch()
 * @param int $lesson Id of the lesson.
 * @param int $userid Id of the user.
 * @param int $pageid Id of the page from which we are jumping.
 * @return int Id of the next page.
 **/
function languagelesson_unseen_question_jump($lesson, $user, $pageid) {
    // get the number of retakes
    if (!$retakes = count_records("languagelesson_grades", "lessonid", $lesson, "userid", $user)) {
        $retakes = 0;
    }

    // get all the lesson_attempts aka what the user has seen
    if ($viewedpages = get_records_select("languagelesson_attempts", "lessonid = $lesson AND userid = $user AND retry = $retakes", "timeseen DESC")) {
        foreach($viewedpages as $viewed) {
            $seenpages[] = $viewed->pageid;
        }
    } else {
        $seenpages = array();
    }

    // get the lesson pages
    if (!$lessonpages = get_records_select("languagelesson_pages", "lessonid = $lesson")) {
        error("Error: could not find records in lesson_pages table");
    }
    
    if ($pageid == LL_UNSEENBRANCHPAGE) {  // this only happens when a student leaves in the middle of an unseen question within a branch series
        $pageid = $seenpages[0];  // just change the pageid to the last page viewed inside the branch table
    }

    // go up the pages till branch table
    while ($pageid != 0) { // this condition should never be satisfied... only happens if there are no branch tables above this page
        if ($lessonpages[$pageid]->qtype == LL_BRANCHTABLE) {
            break;
        }
        $pageid = $lessonpages[$pageid]->prevpageid;
    }
    
    $pagesinbranch = languagelesson_pages_in_branch($lessonpages, $pageid);
    
    // this foreach loop stores all the pages that are within the branch table but are not in the $seenpages array
    $unseen = array();
    foreach($pagesinbranch as $page) {    
        if (!in_array($page->id, $seenpages)) {
            $unseen[] = $page->id;
        }
    }

    if(count($unseen) == 0) {
        if(isset($pagesinbranch)) {
            $temp = end($pagesinbranch);
            $nextpage = $temp->nextpageid; // they have seen all the pages in the branch, so go to EOB/next branch table/EOL
        } else {
            // there are no pages inside the branch, so return the next page
            $nextpage = $lessonpages[$pageid]->nextpageid;
        }
        if ($nextpage == 0) {
            return LL_EOL;
        } else {
            return $nextpage;
        }
    } else {
        return $unseen[rand(0, count($unseen)-1)];  // returns a random page id for the next page
    }
}





/**
 * Handles the unseen branch table jump.
 *
 * @param int $lessonid Lesson id.
 * @param int $userid User id.
 * @return int Will return the page id of a branch table or end of lesson
 **/
function languagelesson_unseen_branch_jump($lessonid, $userid) {
    if (!$retakes = count_records("languagelesson_grades", "lessonid", $lessonid, "userid", $userid)) {
        $retakes = 0;
    }

    if (!$seenbranches = get_records_select("languagelesson_seenbranches", "lessonid = $lessonid AND userid = $userid AND retry = $retakes",
                "timeseen DESC")) {
        error("Error: could not find records in languagelesson_seenbranches table");
    }

    // get the lesson pages
    if (!$lessonpages = get_records_select("languagelesson_pages", "lessonid = $lessonid")) {
        error("Error: could not find records in lesson_pages table");
    }
    
    // this loads all the viewed branch tables into $seen untill it finds the branch table with the flag
    // which is the branch table that starts the unseenbranch function
    $seen = array();    
    foreach ($seenbranches as $seenbranch) {
        if (!$seenbranch->flag) {
            $seen[$seenbranch->pageid] = $seenbranch->pageid;
        } else {
            $start = $seenbranch->pageid;
            break;
        }
    }
    // this function searches through the lesson pages to find all the branch tables
    // that follow the flagged branch table
    $pageid = $lessonpages[$start]->nextpageid; // move down from the flagged branch table
    while ($pageid != 0) {  // grab all of the branch table till eol
        if ($lessonpages[$pageid]->qtype == LL_BRANCHTABLE) {
            $branchtables[] = $lessonpages[$pageid]->id;
        }
        $pageid = $lessonpages[$pageid]->nextpageid;
    }
    $unseen = array();
    foreach ($branchtables as $branchtable) {
        // load all of the unseen branch tables into unseen
        if (!array_key_exists($branchtable, $seen)) {
            $unseen[] = $branchtable;
        }
    }
    if (count($unseen) > 0) {
        return $unseen[rand(0, count($unseen)-1)];  // returns a random page id for the next page
    } else {
        return LL_EOL;  // has viewed all of the branch tables
    }
}





/**
 * Handles the random jump between a branch table and end of branch or end of lesson (LL_RANDOMPAGE).
 * 
 * @param int $lessonid Lesson id.
 * @param int $pageid The id of the page that we are jumping from (?)
 * @return int The pageid of a random page that is within a branch table
 **/
function languagelesson_random_question_jump($lessonid, $pageid) {
    // get the lesson pages
    if (!$lessonpages = get_records_select("languagelesson_pages", "lessonid = $lessonid")) {
        error("Error: could not find records in lesson_pages table");
    }

    // go up the pages till branch table
    while ($pageid != 0) { // this condition should never be satisfied... only happens if there are no branch tables above this page

        if ($lessonpages[$pageid]->qtype == LL_BRANCHTABLE) {
            break;
        }
        $pageid = $lessonpages[$pageid]->prevpageid;
    }

    // get the pages within the branch    
    $pagesinbranch = languagelesson_pages_in_branch($lessonpages, $pageid);
    
    if(count($pagesinbranch) == 0) {
        // there are no pages inside the branch, so return the next page
        return $lessonpages[$pageid]->nextpageid;
    } else {
        return $pagesinbranch[rand(0, count($pagesinbranch)-1)]->id;  // returns a random page id for the next page
    }
}





/**
 * Check to see if a page is below a branch table (logically).
 * 
 * Will return true if a branch table is found logically above the page.
 * Will return false if an end of branch, cluster or the beginning
 * of the lesson is found before a branch table.
 *
 * @param array $pages An array of lesson page objects.
 * @param int $pageid Id of the page for testing.
 * @return boolean
 */
function languagelesson_is_page_in_branch($pages, $pageid) {
    $pageid = $pages[$pageid]->prevpageid; // move up one

    // go up the pages till branch table    
    while (true) {
        if ($pageid == 0) {  // ran into the beginning of the lesson
            return false;
        } elseif ($pages[$pageid]->qtype == LL_ENDOFBRANCH) { // ran into the end of another branch table
            return false;
        } elseif ($pages[$pageid]->qtype == LL_CLUSTER) { // do not look beyond a cluster
            return false;
        } elseif ($pages[$pageid]->qtype == LL_BRANCHTABLE) { // hit a branch table
            return true;
        }
        $pageid = $pages[$pageid]->prevpageid;
    }

}






/**
 * Check to see if a page is below a cluster page (logically).
 * 
 * Will return true if a cluster is found logically above the page.
 * Will return false if an end of cluster or the beginning
 * of the lesson is found before a cluster page.
 *
 * @param array $pages An array of lesson page objects.
 * @param int $pageid Id of the page for testing.
 * @return boolean
 */
function languagelesson_is_page_in_cluster($pages, $pageid) {
    $pageid = $pages[$pageid]->prevpageid; // move up one

    // go up the pages till branch table    
    while (true) {
        if ($pageid == 0) {  // ran into the beginning of the lesson
            return false;
        } elseif ($pages[$pageid]->qtype == LL_ENDOFCLUSTER) { // ran into the end of another branch table
            return false;
        } elseif ($pages[$pageid]->qtype == LL_CLUSTER) { // hit a branch table
            return true;
        }
        $pageid = $pages[$pageid]->prevpageid;
    }
}





/**
 * Calculates a user's grade for a lesson.
 *
 * @param object $lesson The lesson that the user is taking.
 * @param int $userid Id of the user (optional, default current user).
 * @return object { nanswered => number of questions answered
                    total => max points possible
                    earned => points earned by student
                    grade => calculated percentage grade
                    nmanual => number of manually graded questions
                    manualpoints => point value for manually graded questions }
 */
function languagelesson_grade($lesson, $userid = 0) {  
    global $USER, $LL_QUESTION_TYPE;
	
    if ($userid == 0) {
        $userid = $USER->id;
    }
    
    // Initialize all our counters to 0
	$ntotal 		= 0; 	// the total number of questions
    $nanswered	 	= 0;	// the number of questions the user answered
    $ncorrect		= 0;	// the number of questions the user answered correctly
    $nmanual		= 0;	// the number of manual questions answered
	
    $earnedpts     	= 0;	// the user's earned number of points
    $manualpoints 	= 0;	// the number of potential manual points
    
	if (!$lesson->penalty && ($pageattempts = languagelesson_get_most_recent_attempts($lesson->id, $userid)) ||
		$lesson->penalty && ($pageattempts = languagelesson_get_all_attempts($lesson->id, $userid))) {
		
	//////////////////////////////////////
	// GET THE ANSWERS
		/// pull all the question pages for this lesson, as the most recent attempts won't
		/// necessarily include every page
		$pages = get_records_select('languagelesson_pages', "lessonid=$lesson->id AND qtype in
				(".implode(',',array_keys($LL_QUESTION_TYPE)).')',
					'ordering');
		/// pull the answers for these page IDs to calculate earned and total points
		$answers = get_records_select("languagelesson_answers", "lessonid = $lesson->id
										AND pageid IN (".implode(',', array_keys($pages)).")");
	// </get the answers>
	//////////////////////////////////////
		
		
	//////////////////////////////////////
	// FILL IN INITIAL VALUES
		// store the number of pages they attempted
		$nanswered = count($pageattempts);
		
		// and store the number of question pages there are total
		$ntotal = count($pages);
	// </fill in initial vals>
	//////////////////////////////////////
		
		
	//////////////////////////////////////
	// GET GRADING INFO

	/// handle grading only most recent attempts
		if (!$lesson->penalty) {
			foreach ($pageattempts as $pageattempt) {
				// no matter what, we add in whatever they scored on it
				$earnedpts += $pageattempt->score;
				// if it's saved as correct, the type doesn't matter, so mark it
				if ($pageattempt->correct) {
					$ncorrect++;
				}
				// if it's not, though, it may be an ungraded manual, so check
				else if ($pageattempt->qtype == LL_ESSAY ||
						   $pageattempt->qtype == LL_AUDIO ||
						   $pageattempt->qtype == LL_VIDEO) {
					$nmanual++;
					
					/// if we got here, this may be a non-autograded lesson;
					/// if so, record the possible points for this question in manualpoints
					if (!$lesson->autograde) {
						$manualpoints += $answers[$pageattempt->answerid]->score;
					}
				}
				// otherwise, it's just straight-up wrong, so ignore it
			}
		}
		
	/// handle grading on all attempts
		else { //if $lesson->penalty
			/// loop over the pages
			foreach ($pageattempts as $pageID => $pageattemptset) {
				// initialize the array to store which distinct answer IDs we have seen so far,
				// to prevent students gaming the system to change their grades
				$seenAnswerIDs = array();
				
			/// handle grading using the "mean" penalty type
				if ($lesson->penaltytype == LL_PENALTY_MEAN) {
					// calculate the average score on this question
					$sum = 0;
					foreach ($pageattemptset as $pageattempt) {
						/// only note this attempt's score in the sum if we have not
						/// seen its answer before (prevents upping mean score by logging multiple
						/// attempts with correct answer)
						if (!in_array($pageattempt->answerid, $seenAnswerIDs)) {
							$sum += $pageattempt->score;
							$seenAnswerIDs[] = $pageattempt->answerid;
						}
					}
					$avg = $sum / (float) count($seenAnswerIDs);
					// and add that to earnedpts
					$earnedpts += $avg;
					
			/// handle grading using the "set" penalty type
				} else if ($lesson->penaltytype == LL_PENALTY_SET) {
					// pull the penalty multiplier and the most recent score the student got
					$penaltyval = ($lesson->penaltyvalue / 100); // it's stored as a whole number percent value, pull it as a decimal
					$basescore = end($pageattemptset)->score;
					// log the distinct answers the student submitted attempts with
					foreach ($pageattemptset as $pageattempt) {
						if (!in_array($pageattempt->answerid, $seenAnswerIDs)) { $seenAnswerIDs[] = $pageattempt->answerid; }
					}
					// penalize that score using the penalty multiplier multiplied by the number
					// of distinct answers used (offset by 1, so we don't penalize the correct answer);
					// since this gives a percentage, we then multiply the result by the score for the answer they gave
					$thisscore = $basescore - ($basescore * ($penaltyval * (count($seenAnswerIDs) - 1)));
					// if it's correct, make sure they get at least some credit; otherwise, make sure it doesn't go below 0
					if ($thisscore <= 0 && end($pageattemptset)->correct) { $thisscore = $basescore * $penaltyval; }
					else if ($thisscore < 0) { $thisscore = 0; }
					// and save that into earnedpts
					$earnedpts += $thisscore;
				} else {
					error("Grading: unknown penalty type.");
				}
				
			/// handle manualpoints if this isn't an autograded lesson
			/// NOTE that $pageattemptset[0] will always exist, by how
			/// is written
				if (!$lesson->autograde &&
						$pageattemptset[0]->qtype == LL_ESSAY ||
						$pageattemptset[0]->qtype == LL_VIDEO ||
						$pageattemptset[0]->qtype == LL_AUDIO) {
					$manualpoints += $answers[$pageattemptset[0]->answerid]->score;
				}
			}
		}
		
	// </get grading info>
	//////////////////////////////////////


		
	}
	else { error_log("didn't get any attempts"); }
	
    // Build the grade information object
    $gradeinfo               	= new stdClass;
    $gradeinfo->nanswered 		= $nanswered;
	$gradeinfo->nmanual			= $nmanual;
    $gradeinfo->grade			= $earnedpts;
    $gradeinfo->manualpoints	= $manualpoints;
    
    return $gradeinfo;
}




/**
 * Stores the SQL record of a student's grade on a lesson
 *
 * @param int $lessonid The ID of the lesson graded
 * @param int $userid The ID of the student graded
 * @param real $gradeval The grade the student received
 */
function languagelesson_save_grade($lessonid, $userid, $gradeval) {
	// build the grade object
	$grade = new stdClass;
	$grade->lessonid = $lessonid;
	$grade->userid = $userid;
	$grade->grade = $gradeval;

	// and update the old grade record, if there is one; if not, insert the record
	if ($oldgrade = get_record("languagelesson_grades", "lessonid", $lessonid,
							   "userid", $userid)) {
		/// if the old grade was for a completed lesson attempt, update the completion time
		if ($oldgrade->completed) { $grade->completed = time(); }
		$grade->id = $oldgrade->id;
		if (! update_record("languagelesson_grades", $grade)) {
			error("Navigation: grade not updated");
		}
	} else {
		if (! insert_record("languagelesson_grades", $grade)) {
			error("Navigation: grade not inserted");
		}
	}
}





/**
 * Prints the ongoing message to the user.
 *
 * Displays points earned out of total points possible thus far.
 *
 * @param object $lesson The lesson that the user is taking.
 * @return void
 **/
function languagelesson_print_ongoing_score($lesson) {
    global $USER;
    $cm = get_coursemodule_from_instance('lesson', $lesson->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (has_capability('mod/languagelesson:manage', $context)) {
        echo "<p align=\"center\">".get_string('teacherongoingwarning', 'languagelesson').'</p>';
    } else {
		/// build and print the score message
        $a = new stdClass;
		$a->score = get_field('languagelesson_grades', 'grade', 'lessonid', $lesson->id, 'userid', $USER->id);
		$a->currenthigh = get_field('languagelesson', 'grade', 'id', $lesson->id);
        print_simple_box(get_string("ongoingscoremessage", "languagelesson", $a), "center");
    }
}





/**
 * Prints tabs for the editing and adding pages.  Each tab is a question type.
 *  
 * @param array $qtypes The question types array (may not need to pass this because it is defined in this file)
 * @param string $selected Current selected tab
 * @param string $link The base href value of the link for the tab
 * @param string $onclick Javascript for the tab link
 * @return void
 */
function languagelesson_qtype_menu($qtypes, $selected="", $link="", $onclick="") {
    $tabs = array();
    $tabrows = array();

    foreach ($qtypes as $qtype => $qtypename) {
	if ($qtype == LL_DESCRIPTION) { continue; }
        $tabrows[] = new tabobject($qtype, "$link&amp;qtype=$qtype\" onclick=\"$onclick", $qtypename);
    }
    $tabs[] = $tabrows;
    print_tabs($tabs, $selected);
    echo "<input type=\"hidden\" name=\"qtype\" value=\"$selected\" /> \n";

}






/**
 * Prints out a Progress Bar which depicts a user's progress within a lesson.
 *
 * Currently works best with a linear lesson.  Clusters are counted as a single page.
 * Also, only viewed branch tables and questions that have been answered correctly count
 * toward lesson completion (or progress).  Only Students can see the Progress bar as well.
 *
 * @param object $lesson The lesson that the user is currently taking.
 * @param object $course The course that the to which the lesson belongs.
 * @return boolean The return is not significant as of yet.  Will return true/false.
 **/
function languagelesson_print_progress_bar($lesson, $course) {
    global $CFG, $USER;
    $cm = get_coursemodule_from_instance('languagelesson', $lesson->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    // lesson setting to turn progress bar on or off
    if (!$lesson->progressbar) {
        return false;
    }
    
    // catch teachers
    if (has_capability('mod/languagelesson:manage', $context)) {
        notify(get_string('progressbarteacherwarning', 'languagelesson', $course->teachers));
        return false;
    }
	
	// all of the lesson pages
	if (!$pages = get_records('languagelesson_pages', 'lessonid', $lesson->id)) {
		return false;
	} else {
		foreach ($pages as $page) {
			if ($page->prevpageid == 0) {
				$pageid = $page->id;  // find the first page id
				break;
			}
		}
	}

	// current attempt number
	if (!$ntries = count_records("languagelesson_grades", "lessonid", $lesson->id, "userid", $USER->id)) {
		$ntries = 0;  // may not be necessary
	}

	$viewedpageids = array();

	// collect all of the correctly answered questions
	//if ($viewedpages = get_records_select("languagelesson_attempts", "lessonid = $lesson->id AND userid = $USER->id AND retry = $ntries AND correct = 1", 'timeseen DESC', 'pageid, id')) {
	if ($viewedpages = get_records_select("languagelesson_attempts", "lessonid = $lesson->id AND userid = $USER->id AND retry = $ntries", 'timeseen DESC', 'pageid, id')) {
		$viewedpageids = array_keys($viewedpages);
	}
	// collect all of the branch tables viewed
	if ($viewedbranches = get_records_select("languagelesson_seenbranches", "lessonid = $lesson->id AND userid = $USER->id AND retry = $ntries", 'timeseen DESC', 'pageid, id')) {
		$viewedpageids = array_merge($viewedpageids, array_keys($viewedbranches));
	}

	// Filter out the following pages:
	//      End of Cluster
	//      End of Branch
	//      Pages found inside of Clusters
	// Do not filter out Cluster Page(s) because we count a cluster as one.
	// By keeping the cluster page, we get our 1
	$validpages = array(); 
	while ($pageid != 0) {
		if ($pages[$pageid]->qtype == LL_CLUSTER) {
			$clusterpageid = $pageid; // copy it
			$validpages[$clusterpageid] = 1;  // add the cluster page as a valid page
			$pageid = $pages[$pageid]->nextpageid;  // get next page
		
			// now, remove all necessary viewed paged ids from the viewedpageids array.
			while ($pages[$pageid]->qtype != LL_ENDOFCLUSTER and $pageid != 0) {
				if (in_array($pageid, $viewedpageids)) {
					unset($viewedpageids[array_search($pageid, $viewedpageids)]);  // remove it
					// since the user did see one page in the cluster, add the cluster pageid to the viewedpageids
					if (!in_array($clusterpageid, $viewedpageids)) { 
						$viewedpageids[] = $clusterpageid;
					}
				}
				$pageid = $pages[$pageid]->nextpageid;
			}
		} elseif ($pages[$pageid]->qtype == LL_ENDOFCLUSTER or $pages[$pageid]->qtype == LL_ENDOFBRANCH) {
			// dont count these, just go to next
			$pageid = $pages[$pageid]->nextpageid;
		} else {
			// a counted page
			$validpages[$pageid] = 1;
			$pageid = $pages[$pageid]->nextpageid;
		}
	}    

	// progress calculation as a percent
	$progress = round(count($viewedpageids)/count($validpages), 2) * 100; 

    // print out the Progress Bar.  Attempted to put as much as possible in the style sheets.
    echo '<div class="progress_bar" align="center">';
    echo '<table class="progress_bar_table"><tr>';
    if ($progress != 0) {  // some browsers do not repsect the 0 width.
        echo '<td style="width:'.$progress.'%;" class="progress_bar_completed">';
        echo '</td>';
    }
    echo '<td class="progress_bar_todo">';
    echo '<div class="progress_bar_token"></div>';
    echo '</td>';
    echo '</tr></table>';
    echo '</div>';
    
    return true;
}






/**
 * If there is a media file associated with this 
 * lesson, then print it in a block.
 *
 * @param int $cmid Course Module ID for this lesson
 * @param object $lesson Full lesson record object
 * @return void
 **/
function languagelesson_print_mediafile_block($cmid, $lesson) {
    if (!empty($lesson->mediafile)) {
        $url      = '/mod/languagelesson/mediafile.php?id='.$cmid;
        $options  = 'menubar=0,location=0,left=5,top=5,scrollbars,resizable,width='. $lesson->mediawidth .',height='. $lesson->mediaheight;
        $name     = 'lessonmediafile';

        $content  = link_to_popup_window ($url, $name, get_string('mediafilepopup', 'languagelesson'), '', '', get_string('mediafilepopup', 'languagelesson'), $options, true);
        $content .= helpbutton("mediafilestudent", get_string("mediafile", "languagelesson"), "lesson", true, false, '', true);
        
        print_side_block(get_string('linkedmedia', 'languagelesson'), $content, NULL, NULL, '', array('class' => 'mediafile'), get_string('linkedmedia', 'languagelesson'));
    }
}






/**
 * If a timed lesson and not a teacher, then
 * print the clock
 *
 * @param int $cmid Course Module ID for this lesson
 * @param object $lesson Full lesson record object
 * @param object $timer Full timer record object
 * @return void
 **/
function languagelesson_print_clock_block($cmid, $lesson, $timer) {
    global $CFG;

    $context = get_context_instance(CONTEXT_MODULE, $cmid);

    // Display for timed lessons and for students only
    if($lesson->timed and !has_capability('mod/languagelesson:manage', $context) and !empty($timer)) {
        $content  = '<script type="text/javascript" charset="utf-8">'."\n";
        $content .= "<!--\n";
        $content .= '    var starttime  = '.$timer->starttime.";\n";
        $content .= '    var servertime = '.time().";\n";
        $content .= '    var testlength = '.($lesson->maxtime * 60).";\n";
        $content .= '    document.write(\'<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/languagelesson/timer.js" charset="utf-8"><\/script>\');'."\n";
        $content .= "    window.onload = function () { show_clock(); };\n";
        $content .= "// -->\n";
        $content .= "</script>\n";
        $content .= "<noscript>\n";
        $content .= languagelesson_print_time_remaining($timer->starttime, $lesson->maxtime, true)."\n";
        $content .= "</noscript>\n";
    
        print_side_block(get_string('timeremaining', 'languagelesson'), $content, NULL, NULL, '', array('class' => 'clock'), get_string('timeremaining', 'languagelesson'));
    }
}





/**
 * If left menu is turned on, then this will
 * print the menu in a block
 *
 * @param int $cmid Course Module ID for this lesson
 * @param object $lesson Full lesson record object
 * @return void
 **/


function languagelesson_print_menu_block($cmid, $lesson) {

/*
 * This has been extensively customized from the original for use in
 * languagelessons.  In lesson functionality, only branch tables are
 * printed here.  In language lessons, the following rules are followed:
 *
 * - Cluster, End of Cluster, and End of Branch demarcation structural pages
 *	 are not printed at all.
 * - If a question page is not contained in a branch table, it is printed.
 * - If a branch table is encountered, the following happens:
 *	 :: the branch table page itself is printed
 *	 :: the titles of each of the branches in the table are printed as links
 *		to the first page in each branch
 *	 :: all question pages that are in the branch the user is currently working
 *	 	on are printed
 *	 :: all question pages in other branches are not printed
 *
 * The logic is thoroughly commented below.
 *
 */

    global $CFG, $USER;

    if ($lesson->displayleft) {
        $pageid = get_field('languagelesson_pages', 'id', 'lessonid', $lesson->id, 'prevpageid', 0);
        $pages  = get_records_select('languagelesson_pages', "lessonid = $lesson->id");
        $currentpageid = optional_param('pageid', $pageid, PARAM_INT);
        
        
      /// initialize all the variables used in context-sensitive printing of the
      /// left menu contents
      /*
       * @param branchtable_id :: the pageID of the most recent branch table seen
       * @param branch_heads :: list of pageIDs of pages that start each branch in the
       *						current branch table
       * @param branch_pages :: a temp array of all pageIDs belonging to the branch
       *						currently being checked; used to determine the contents
       *						of currentbranch_pages
       * @param currentbranch_pages :: list of all pageIDs belonging to the branch that
       *							   the user is currently in
       * @param branches_seen :: count variable used (with branches_expected) to determine
       *						 when the end of the current branch table has been reached;
       *						 incremented when a LL_ENDOFBRANCH page is seen
       * @param branches_expected :: count variable used (with branches_seen) to determine
       *							 when the end of the current branch table has been reached
       * @param inbranchtable :: bool flag used to mark whether the page currently being
       *						 checked belongs to a branch table or not
       * @param print :: bool flag marking whether page currently being checked should be
       *				 printed in the left menu block
       * @param indent :: multiplier variable used to mark with how many degrees of indentation
       *				  page currently being checked should be printed in the left menu
       * @param indent_pixels :: int constant setting the number of pixels the indent
       *						 multiplier is multiplied by to yield final indentation value
       */
        $branchtable_id = null;
        $branch_heads = array();
        $branch_pages = array();
        $currentbranch_pages = array();
        $branches_seen = 0;
        $branches_expected = 0;
        $inbranchtable = false;
        $print = true;
        $indent = 0;
        $indent_pixels = 20;
        
        
      /// initialize the default (base) texts used for printing selected or not selected
      /// page links in the left menu
        $selected = '<li class="selected"><span %s>%s</span> %s %s</li>';
        $notselected = "<li class=\"notselected\"><a href=\"$CFG->wwwroot/mod/"
        				  . "languagelesson/view.php?id=$cmid&amp;pageid=%d\""
        				  . "class=\"%s\" %s >%s</a>%s %s</li>\n";
      /// initialize the base style declaration used in setting indent values
        $indent_style = 'style="margin-left:%dpx;"';
        

        if ($pageid and $pages) {
			$content = '<a href="#maincontent" class="skip">'.get_string('skip', 'languagelesson')."</a>\n<div
				class=\"menuwrapper\">\n<ul>\n";
            while ($pageid != 0) {
                $page = $pages[$pageid];

                switch ($page->qtype) {
                	case LL_CLUSTER:
                	case LL_ENDOFCLUSTER:
                		break;
                	case LL_BRANCHTABLE:
                		$branchtable_id = $page->id;
                		$branch_heads = languagelesson_get_branch_heads($page->id);
                		$branches_seen = 0; //reset count of branches seen
                		$branches_expected = count($branch_heads);
                		$inbranchtable = true;
                		if ($page->id == $currentpageid) {
                			$content .= sprintf($selected, sprintf($indent_style, 0*$indent_pixels), format_string($page->title,true),
									'', '');
                		} else {
							$content .= sprintf($notselected, $page->id, '', sprintf($indent_style, 0*$indent_pixels),
									format_string($page->title, true), '', '');
                		}
                		break;
                	case LL_ENDOFBRANCH:
                		$branches_seen++;
                		if ($branches_seen == $branches_expected) {
                			$inbranchtable = false;
                		}
                		break;
                	default:
                		
                	///// PRINT BOOL CHECKING /////
                		
                	  /// if we aren't in a branch table, flag it as to-be-printed with no
                	  /// indent, and move on
                		if (! $inbranchtable) {
                			$print = true;
                			$indent = 0;
                		} 
                	  /// otherwise, do special checking to see if it should be printed and
                	  /// manage behind-the-scenes variables
                		else {
                		  /// if it's the first page in a branch (a branch header)
                			if (in_array($page->id, $branch_heads)) {
                			  /// get its title...
                			  	$branchheader_title = languagelesson_get_branch_header_title($branchtable_id, $page->id);
                				
                			  /// ...get the list of pageIDs belonging to this branch...
                				$branch_pages = languagelesson_get_current_branch_pages($lesson->id, $page->id);
                				
                			  /// ...if the currently selected page is among the pageIDs belonging
                			  /// to this branch, save that list as the list of branch pages in
                			  /// the current branch...
                			  	if (in_array($currentpageid, $branch_pages)) {
                			  		$currentbranch_pages = $branch_pages;
                			  	}
                				
                			  /// ...and print the branch header
                				if (in_array($page->id, $currentbranch_pages)) {
                				  /// if the branch header being checked is in the current branch,
                				  /// print the header as selected
									$content .= sprintf($selected, sprintf($indent_style, 1*$indent_pixels),
											format_string($branchheader_title,true), '', '');
                				} else {
                				  /// otherwise, just print the header as not selected
									$content .= sprintf($notselected, $page->id, '', sprintf($indent_style, 1*$indent_pixels),
											format_string($branchheader_title,true), '', '');
                				}
                			}
                			
                		  /// now that we may have updated the list of current branch pageids,
                		  /// check this page against it: if it's in the current branch, flag
                		  /// it as to-be-printed and set the indent, otherwise, hide it
                			if (in_array($page->id, $currentbranch_pages)) {
                				$print = true;
                				$indent = 2;
                			} else {
                				$print = false;
                			}
                		}
                		
                		
                	///// PRINTING /////
                		
                		if ($print) {
							if ($state = languagelesson_get_autograde_state($lesson->id, $page->id, $USER->id)) {
								if ($lesson->contextcolors) {
									// reset the optional second image string
									$img2 = '';
									if ($state == 'correct') {
										$class = 'leftmenu_autograde_correct';
										$img = "<img src=\"{$CFG->wwwroot}".get_string('iconsrccorrect', 'languagelesson')."\"
											width=\"10\" height=\"10\" alt=\"correct\" />";
									} else if ($state == 'incorrect') {
										$class = 'leftmenu_autograde_incorrect';
										$img = "<img src=\"{$CFG->wwwroot}".get_string('iconsrcwrong', 'languagelesson')."\"
											width=\"10\" height=\"10\" alt=\"incorrect\" />";
									} else {
										//it's manually-graded
										$class = 'leftmenu_manualgrade';
										$src = get_string('iconsrcmanual', 'languagelesson');
										$fbsrc = get_string('iconsrcfeedback', 'languagelesson');
										$img = "<img src=\"{$CFG->wwwroot}$src\"
											width=\"10\" height=\"10\" alt=\"manually-graded\" />";
										if ($state == 'feedback') {
											$img2 = "<img src=\"{$CFG->wwwroot}$fbsrc\"
												width=\"15\" height=\"15\" alt=\"manually-graded\" />";
										}
									}
								} else {
									$class = 'leftmenu_attempted';
									$img = '';
								}
							} else {
								//page has not been attempted, so don't mod the style and don't include an image
								$class = 'leftmenu_noattempt';
								$img = '';
							}
						/// print the link based on if it is the current page or not
							if ($page->id == $currentpageid) { 
								$content .= sprintf($selected, sprintf($indent_style, $indent*$indent_pixels),
									format_string($page->title,true), $img, ((!empty($img2)) ? $img2 : ''));
							} else {
								$content .= sprintf($notselected, $page->id, $class, sprintf($indent_style, $indent*$indent_pixels),
									format_string($page->title,true), $img, ((!empty($img2)) ? $img2 : ''));
							}
						}
						break;
						
                } // end switch($page->qtype)
                
                $pageid = $page->nextpageid;
            } // end while($pageid != 0)
            $content .= "</ul>\n</div>\n";
			print_side_block(get_string('lessonmenu', 'languagelesson'), $content, NULL, NULL, '', array('class' => 'menu'),
					get_string('lessonmenu', 'languagelesson'));
        }
    }
}






/**
 * @NEEDSDOC@
 **/
function languagelesson_get_branch_heads($branchtable_id) {
	
	$branches = get_records('languagelesson_answers', 'pageid', $branchtable_id);
	
	$branch_heads = array();
	
	foreach ($branches as $branch) {
		$branch_heads[] = $branch->jumpto;
	}
	
	return $branch_heads;
	
}






/**
 * @NEEDSDOC@
 **/
function languagelesson_get_current_branch_pages($lessonid, $branchhead_pageid) {
	
	$pageid = get_field('languagelesson_pages', 'id', 'lessonid', $lessonid, 'prevpageid', 0);
    $pages  = get_records_select('languagelesson_pages', "lessonid = $lessonid");
    
    $current_branch_pages = array();
    $isinbranch = false;
    
    while ($pageid != 0) {
    	$page = $pages[$pageid];
    	
    	if ($page->id == $branchhead_pageid) {
    		$isinbranch = true;
    	}
    	
    	if ($page->qtype == LL_ENDOFBRANCH) {
    		$isinbranch = false;
    	}
    	
    	if ($isinbranch) {
    		$current_branch_pages[] = $page->id;
    	}
    	
    	$pageid = $page->nextpageid;
    }
    
    return $current_branch_pages;
	
}
                				




/**
 * @NEEDSDOC@
 **/
function languagelesson_get_branch_header_title($branchtable_id, $pageid) {
	
	$branches = get_records('languagelesson_answers', 'pageid', $branchtable_id);
	
	$title = '';
	foreach ($branches as $branch) {
		if ((int)$branch->jumpto == $pageid) {
			$title = $branch->answer;
		}
	}
	
	return $title;
	
}















/**
 * This is not ideal, but checks to see if a
 * column has "block" content.
 *
 * In the future, it would be nice if the lesson
 * media file, timer and navigation were blocks
 * then this would be unnecessary.
 *
 * @uses $CFG
 * @uses $PAGE
 * @param object $lesson Full lesson record object
 * @param array $pageblocks An array of block instances organized by left and right columns
 * @param string $column Pass either BLOCK_POS_RIGHT or BLOCK_POS_LEFT constants
 * @return boolean
 **/
function languagelesson_blocks_have_content($lesson, $pageblocks, $column) {
    global $CFG, $PAGE;

    // First check lesson conditions
    if ($column == BLOCK_POS_RIGHT) {
        $managecap = false;
        if ($cm = get_coursemodule_from_instance('lesson', $lesson->id, $lesson->course)) {
            $managecap = has_capability('mod/languagelesson:manage', get_context_instance(CONTEXT_MODULE, $cm->id));
        }
        if (($lesson->timed and !$managecap) or !empty($lesson->mediafile)) {
            return true;
        }
    } else if ($column == BLOCK_POS_LEFT) {
        if ($lesson->displayleft) {
            return true;
        }
    }
    if (!empty($CFG->showblocksonmodpages)) {
        if ((blocks_have_content($pageblocks, $column) || $PAGE->user_is_editing())) {
            return true;
        }
    }

    return false;
}









/**
 * @NEEDSDOC@
 **/
function languagelesson_list_submitted_files($lesson = null, $page = null, $user = null, $lessonsql = null) {
	/* robust function to list relative paths (from dataroot) for files submitted by students for the lesson in question (whether all files for whole lesson,
		all files for one page, all files for one user, all files for one try, etc.);
    */
	global $CFG;
	
	$fileslist = array();
	
	// selection is always the same, so store it
	$select_from = "select * from {$CFG->prefix}languagelesson_attempts ";
	
/// construct the where clause, based on the input variables ///
	$where = "where ";
	$multi_cond = false;
	if ($lesson !== null) {
		$where .= "lessonid = $lesson";
		$multi_cond = true;
	}
	if ($page !== null) {
		if ($multi_cond) {
			$where .= " and ";
		}
		$where .= "pageid = $page";
		$multi_cond = true;
	}
	if ($user !== null) {
		if ($multi_cond) {
			$where .= " and ";
		}
		$where .= "userid = $user";
		$multi_cond = true;
	}
	if ($lessonsql !== null) {
		if ($multi_cond) {
			$where .= " and ";
		}
		$where .= "lessonid in ($lessonsql)";
		$multi_cond = true;
	}
	
	/// if multi_cond is not true, there was nothing in the where clause
	if ($multi_cond) {
		$sql = $select_from . $where;
	} else {
		$sql = $select_from;
	}
	
	//error_log("sql is $sql");
	
	$records = get_records_sql($sql);
	if (!$records) {
		return false;
	}
	
	foreach ($records as $record) {
		$useranswer = unserialize($record->useranswer);
		if (isset($useranswer->fname)) {
		/// it could be unserialized and has an fname attribute, so it's an audio or video submission
			$fpath = languagelesson_get_student_file_path($record);
			$fileslist[] = $CFG->dataroot . $fpath;			
		}
	}
	
	return $fileslist;
}

















/**
 * @NEEDSDOC@
 **/
function languagelesson_get_current_page_url() {
	/* Credit for this function goes to http://www.webcheatsheet.com/PHP/get_current_page_url.php 
	    Constructs the URL of the current page and returns it as a string */
	$pageURL = 'http';
	if(isset($_SERVER["HTTPS"]))
	{
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	
	return $pageURL;
}









/**
 * @NEEDSDOC@
 **/
function languagelesson_find_first_unanswered_pageid($lessonid, $userid) {
/*
 * returns the id value of the first page for input $lessonid that does not
 * have a recorded attempt by input $userid for input $retry value;
 */
	
	// pull the list of the user's attempts for this lesson 
	$attempts = languagelesson_get_most_recent_attempts($lessonid, $userid);
	// and pull the lesson's pages in order
	$pages = get_records('languagelesson_pages', 'lessonid', $lessonid, 'ordering');

	// loop over the attempts to store the pageids of all pages seen
	$seenpages = array();
	foreach ($attempts as $attempt) {
		$seenpages[] = $attempt->pageid;
	}
	
	// now loop through the pages until we find one with no attempt
	foreach ($pages as $pageid => $page) {
		if (! in_array($pageid, $seenpages)) {
			return $pageid;
		}
	}

	// otherwise, all pages have been attempted, return null
	return null;
}









/**
 * @NEEDSDOC@
 **/
function languagelesson_sort_pages($pages) {
/* function to sort pages by lesson progression order; returns array of sorted pages w/o keys */
	//store pages in an array, keyed to their id values
	$pages_byid = array();
	foreach ($pages as $page) {
		$pages_byid[$page->id] = $page;
	}
	
	//create array to hold pages in sorted order and populate the first item
	$sorted_pages = array();
	foreach ($pages as $page) {
		//find first page in lesson (the page with prevpageid value of 0)...
		if ($page->prevpageid == 0) {
			$sorted_pages[] = $page; //...and store it as the first item in $sorted_pages
			break;
		}
	}
	
	//sort the rest of the pages; inchworm, storing value of the latest page added to $sorted_pages as $curpage, and continue pulling pages from
	//the $pages_byid array by the nextpageid value of $curpage until $curpage->nextpageid == 0, at which point we've reached the end of the lesson
	$curpage = $sorted_pages[0];
	while ($curpage->nextpageid != 0) {
		$curpage = $pages_byid[$curpage->nextpageid];
		$sorted_pages[] = $curpage;
	}
	
	return $sorted_pages;
}







/**
 * @NEEDSDOC@
 **/
function languagelesson_get_sorted_pages($lessonid) {
	$pages = get_records('languagelesson_pages', 'lessonid', $lessonid);
	return languagelesson_sort_pages($pages);
}







/**
 * @NEEDSDOC@
 **/
function languagelesson_insert_bs_timer($lessonid, $userid) {
	/* temp fix to avoid errors from deletion of timer record (see MDL-23886) */
	$timer = new stdClass;
	$timer->lessonid = $lessonid;
	$timer->userid = $userid;
	$timer->starttime = time();
	$timer->lessontime = time();
	insert_record('languagelesson_timer', $timer);
}









/* 
 * function to check if an answer has been given by the userid for each
 * question in the lessonid
 *
 * This is done by comparing the number of question pages stored for the input
 * lesson with the number of record attempts stored for the input lesson from
 * the input user on the relevant run-through.
 *
 * @param lessonid => ID value for the lesson being examined
 * @param userid => ID value for the user being examined
 */
function languagelesson_is_lesson_complete($lessonid, $userid) {
	global $CFG, $LL_QUESTION_TYPE;

  /// pull the list of all question types as a string of format [type],[type],[type],... ///
	$qtypeslist = implode(',', array_keys($LL_QUESTION_TYPE));

	
///// find the number of question pages /////
	
  /// this alias must be the same in both queries, so establish it here
	$tmp_name = "page";
  /// a sub-query used to ignore pages that have no answers stored for them
  /// (instruction pages)
	$do_answers_exist = "select *
						 from {$CFG->prefix}languagelesson_answers ans
						 where ans.pageid = $tmp_name.id";
  /// query to pull only pages of stored languagelesson question types, belonging
  /// to the current lesson, and having answer records stored
	$get_only_question_pages = "select *
								from {$CFG->prefix}languagelesson_pages $tmp_name
								where qtype in ($qtypeslist)
									  and $tmp_name.lessonid=$lessonid
									  and exists ($do_answers_exist)";
	$qpages = get_records_sql($get_only_question_pages);
	$numqpages = count($qpages);
	
	
///// find the number of questions attempted /////
	
	/// see how many questions have been attempted
	$numattempts = languagelesson_count_most_recent_attempts($lessonid, $userid);

	/// if the number of question pages matches the number of attempted questions, it's complete
	if ($numqpages == $numattempts) { return true; }
	else { return false; }
}








/**
 * @NEEDSDOC@
 **/
function languagelesson_get_autograde_state($lessonid, $pageid, $userid, $retry=null) {
	/* function to return a string representation of the auto-grade state of a page; returns false if page has not been attempted */
	global $CFG;
	
	if ($retry===null) {
		$retry = get_field('languagelesson_attempts', 'retry', 'iscurrent', 1, 'userid', $userid, 'pageid', $pageid);
	}
	
	$result = get_record_select('languagelesson_attempts', "lessonid=$lessonid and pageid=$pageid and userid=$userid and iscurrent=1");
	if ($result) {
		if ($result->manattemptid !== null) {
			if (count_records('languagelesson_feedback', 'manattemptid', $result->manattemptid)) {
				return 'feedback';
			} else {
				return 'manual';
			}
		} else if ($result->correct) {
			return 'correct';
		} else {
			return 'incorrect';
		}
	}
	
	return false;	
}










/**
 * @NEEDSDOC@
 **/
function languagelesson_get_student_file_path($manattempt, $courseid=null) {
	/* function to return file path within moodle data folder of submitted student file for input attempt on input course */
	global $CFG;
	
	if ($courseid === null) {
		$courseid = get_field('languagelesson', 'course', 'id', $manattempt->lessonid);
	}
	
	$path = '/' . $courseid . '/' . $CFG->moddata . '/languagelesson/';
	
	$path .= $manattempt->lessonid . '/';
	$path .= $manattempt->pageid . '/';
	$path .= $manattempt->userid . '/';
	$path .= $manattempt->fname;
	
	return $path;
	
}







/**
 * @NEEDSDOC@
 **/
function languagelesson_get_student_file_full_path($attempt, $courseid=null) {
	global $CFG;
	
	$path = languagelesson_get_student_file_path($attempt, $courseid);
	
	$fullpath = $CFG->dataroot . $path;
	
	return $fullpath;
}







/**
 * Count the number of questions for which attempts have been submitted
 * for input user on input lesson
 *
 * @param int $lesson The ID of the LanguageLesson to check attempts on
 * @param int $user The ID of the user whose attempts to check
 * @return int $count The number of questions with saved attempts
 **/
function languagelesson_count_most_recent_attempts($lesson, $user) {
	global $CFG;
	
	$querytext = 	"select count(*)
					from {$CFG->prefix}languagelesson_pages p,
						 {$CFG->prefix}languagelesson_attempts a
					where a.pageid = p.id
					  and a.lessonid = $lesson
					  and a.userid = $user
					  and a.iscurrent = 1";
	$result = count_records_sql($querytext);
	
	return $result;
}





/**
 * Shorthand function; retrieves the most recent attempt by a user for all questions in a lesson
 *
 * @param int $lessonid The languagelesson ID to fetch attempts for
 * @param int $userid The user ID to fetch attempts by
 * @return array $attempts Array of attempt record objects, one for each page in the languagelesson
 */
function languagelesson_get_most_recent_attempts($lessonid, $userid) {
	return languagelesson_get_attempts($lessonid, $userid, true);
}



/**
 * Shorthand function; retrieves all attempts a user has made for the pages in this languagelesson
 *
 * @param int $lessonid The languagelesson ID to fetch attempts for
 * @param int $userid The user ID to fetch attempts by
 * @return array $pageattempts 2-D array of pageID => [ attempts by user on that page ]
 */
function languagelesson_get_all_attempts($lessonid, $userid) {
	// pull all attempt sets for the lesson (this func call return a 2-d array, where subarrays are
	// attempt sets on pages)
	$atts = languagelesson_get_attempts($lessonid, $userid, false);
	// if the user has not recorded any actual attempts, $atts will be empty, so bail
	if (!$atts) { return null; }
	// initialize the array to map them to
	$pageattempts = array();
	// crank through the attempt sets
	foreach($atts as $attempt) {
		// pull the page ID for this attempt
		$thispageid = $attempt->pageid;
		// and map it
		if (!array_key_exists($thispageid, $pageattempts)) {
			$pageattempts[$thispageid] = array();
		}
		$pageattempts[$thispageid][] = $attempt;
	}
	
	return $pageattempts;
}






/*
 * Function to retrieve attempts by a user for questions in a languagelesson
 *
 * This allows for retry values to not all be the same (thus helping partial-attempt correcting
 * functionality)
 *
 * @param int $lesson The lessonid to fetch attempts for
 * @param int $user The userid to fetch attempts for
 * @param bool $mostrecent Should we only be fetching the most recent attempt on each question?
 * @return array $attempts An array of attempt record objects, one for each page that the user has
 * 							submitted an attempt for (returns only the most recent attempt for
 * 							each page)
 */
function languagelesson_get_attempts($lesson, $user, $mostrecent) {
	global $CFG;
	
	$select = "select a.*, p.ordering as ordering, p.qtype as qtype";

	$from = "from ({$CFG->prefix}languagelesson_pages p
				inner join
				{$CFG->prefix}languagelesson_attempts a
				on p.id = a.pageid)";
	
	$where = "where p.lessonid=$lesson
				and a.userid=$user"
				. (($mostrecent) ? ' and a.iscurrent=1' : '');

	$orderby = "order by p.ordering" . ((!$mostrecent) ? ', a.retry' : '');

	$query =	"$select
				$from
				$where
				$orderby";

	$attempts = get_records_sql($query);
	
	return $attempts;
}




/**
 * @NEEDSDOC@
 */
function languagelesson_get_last_branch_table_seen($lesson, $user) {
	global $CFG;
	
	$query = "select *
	          from {$CFG->prefix}languagelesson_seenbranches
			  where lessonid=$lesson
			    and userid=$user
				and retry=(select MAX(retry)
				           from {$CFG->prefix}languagelesson_seenbranches
						   where lessonid=$lesson
						     and userid=$user)
			  order by timeseen DESC";
	
	$results = get_records_sql($query);
	
	return $results[0];
}




/**
 * @NEEDSDOC@
 */
function languagelesson_get_most_recent_attempt_on($page, $user) {
	global $CFG;
	$query = "select *
	          from {$CFG->prefix}languagelesson_attempts
			  where userid=$user
				and pageid=$page
				and iscurrent=1";
	
	$result = get_record_sql($query);
	
	if ($result) { return $result; }
	else { return null; }
	
}



/**
 * @NEEDSDOC@
 */
function languagelesson_get_feedback_file_paths($manualattempt, $teacherid) {
	global $CFG;
	
/// pull only those feedback records that correspond to this attempt and involve recorded files
	$fbrecords = get_records_select('languagelesson_feedback', "manattemptid=$manualattempt->id
																and teacherid=$teacherid
																and not isnull(fname)");
	
/// if there aren't any, bail
	if (!$fbrecords) { return null; }
	
/// build the full path for each feedback file
	$fullpaths = array();
	foreach ($fbrecords as $fb) {
		//$fullpaths[] = $CFG->wwwroot . '/file.php' . $path . $fb->fname;
		$dir = languagelesson_get_file_area($manualattempt, $fb);
		$fullpaths[] = "$dir/$fb->fname";
	}
	
/// and return the array containing all the full paths
	return $fullpaths;
}







////////////////////////////////////////////////////////////////
// SUBMISSION / FEEDBACK RETRIEVAL FUNCTIONS ///////////////////
////////////////////////////////////////////////////////////////


/**
 * For manually-graded question types (currently LL_AUDIO, LL_VIDEO, LL_ESSAY);
 * Prints out the user's submission and the teacher's feedback, if there is any
 * @param object $manattempt The student's manual attempt record for this question
 * @param int $qtype The question type of the page
 * @param bool $showsubmission Optional flag (default true): should we print the student's old submission?
 */
function languagelesson_print_submission_feedback_area($manattempt, $qtype, $showsubmission=true) {
	/// print both submitted file and teacher feedback in a table within a Moodle-styled box
	print_simple_box_start('center');
	echo '<table id="submissionfeedbacktable">';

	// only print out the submission row if it's not an audio question (audio submissions are shown within
	// the feedback table)
	if ($qtype != LL_AUDIO) {
		echo '<tr><td>';
			
		// if this is a video question, submission is always shown, so print it out here
		// (audio submissions get loaded directly into the feedback players)
		if ($qtype == LL_VIDEO) {
			languagelesson_view_submission($manattempt, LL_VIDEO);
		}
		// otherwise, it's an essay question and if we should show their submission, do so
		// (if there's feedback, force showing of submission)
		else if ($qtype == LL_ESSAY) {
			$hasFeedback = count_records('languagelesson_feedback', 'manattemptid', $manattempt->id);
			if ($hasFeedback || $showsubmission) {
				languagelesson_view_submission($manattempt, LL_ESSAY);
			}
		}

		// close the submission row
		echo '</td></tr>';
	}

	// open up the feedback row
	echo '<tr><td>';
	
	/// if has submitted feedback, show it
	languagelesson_print_feedback_table($manattempt);
	
	//close the submission and feedback table and the containing box
	echo '</td></tr></table>';
	print_simple_box_end();
}



/**
 * Print out the user's submission for a manually-graded question
 * Currently supports VIDEO and ESSAY type questions
 * @param object $manattempt The user's manual attempt record for this question
 * @param int $qtype The question type of the page
 */
function languagelesson_view_submission($manattempt, $qtype) {
	// print out the submission in a centered div
	echo '<div style="text-align: center">';

	// print out submission info
	echo '<div>'.get_string('yousubmitted', 'languagelesson').'</div>';
	echo '<div class="submissionTime">'.userdate($manattempt->timeseen).'</div>';

	// if this is a video attempt, embed a Quicktime player for the file
	if ($qtype == LL_VIDEO) {
		// pull the path to the student's recorded file
		$dir = languagelesson_get_file_area($manattempt);
		$src = "$dir/$manattempt->fname";
		// print out the video player
		languagelesson_embed_video_player($src);
	}
	// if it's an essay, just print out the text of their submission
	else if ($qtype == LL_ESSAY) {
		echo '<div class="essaySubmission">';
		// TODO: Note that while this removes <script> elements, it does nothing to ensure that the HTML printed out is balanced,
		// meaning that the user can royally screw the page layout by shoving in unbalanced tags
		echo clean_param($manattempt->essay, PARAM_CLEANHTML);
		echo '</div>';
	}

	// close the centered div
	echo '</div>';
}


/**
 * Print out the embed code for a video object
 * @param string $src The URL for the video file to embed
 * @param bool $autoplay Set the autoplay attribute of the embedded player
 * @param bool $forceaudiosize Force the player to be sized like an audio player
 */
function languagelesson_embed_video_player($src, $autoplay=false, $return=false, $forceaudiosize=false) {
 	$string = '';
	$string .= '<p>
			<span class="mediaplugin mediaplugin_qt">

			<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
			  codebase="http://www.apple.com/qtactivex/qtplugin.cab" ';
	if ($forceaudiosize) { $string .= 'width="280" height="20">'; }
	else { $string .= 'width="320" height="256">'; }
	$string .= 	'<param name="pluginspage" value="http://www.apple.com/quicktime/download/" />
			 <param name="src" value="'.$src.'" />
			 <param name="controller" value="true" />
			 <param name="loop" value="false" />
			 <param name="autoplay" value="'. ($autoplay ? 'true' : 'false') . '" />
			 <param name="autostart" value="' . ($autoplay ? 'true' : 'false') . '" />
			 <param name="scale" value="aspect" />
			<!--[if !IE]>-->';
	
	//declare type of embedded file to match input $type
	//$string .= '<object data="'.$src.'" type="' . ($type=='audio' ? 'audio/x-aiff' : 'video/quicktime') . '"';
	$string .= '<object data="'.$src.'" type="video/quicktime"';
	if ($forceaudiosize) { $string .= ' width="280" height="20">'; }
	else { $string .= ' width="320" height="256">'; }
			
	$string .= '<param name="src" value="'.$src.'" />
			   <param name="pluginurl" value="http://www.apple.com/quicktime/download/" />
			   <param name="controller" value="true" />
			   <param name="loop" value="false" />
			   <param name="autoplay" value="'. ($autoplay ? 'true' : 'false') . '" />
			   <param name="autostart" value="' . ($autoplay ? 'true' : 'false') . '" />
			   <param name="scale" value="aspect" />
			  </object>
			<!--<![endif]-->
			
			</object>
			</span>
			</p>';

	if ($return) {
		return $string;
	} else {
		echo $string;
	}
}





/**
 * Display feedback submitted for a manual-type question attempt; used both for student
 * viewing (within the languagelesson) and for teacher viewing (seeing what other teachers
 * have submitted in the respond_window grading interface)
 *
 * @param object $manattempt The manual attempt record object for the attempt in question
 * @param bool $gradingmode Marks if this is called from the teacher's view or the student's
 */
function languagelesson_print_feedback_table($manattempt, $gradingmode=false) {
	global $CFG, $USER, $lesson;
	
	$where = "manattemptid = $manattempt->id";
	if ($gradingmode) { $where .= ' and not isnull(text)'; }
	
	$feedbacks = get_records_select('languagelesson_feedback', $where);

	$hasFeedbackFiles = count_records_select('languagelesson_feedback', "manattemptid = $manattempt->id
																		 and not isnull(fname)");
	
	
/// if this was called from view.php ($gradingmode=false), then only print anything if there
/// is feedback to show; if this was called from respond_window.php ($gradingmode=true), at least
/// the WYSIWYG text editor needs to be printed, regardless if other feedback exists
	if ($feedbacks || $gradingmode) {
		echo '<div id="feedback_area">';

		if (!$gradingmode) { print_heading(get_string('submissionfeedback', 'languagelesson'), '', 4); }
		
		echo 	"<script type=\"text/javascript\">
				
				var curselected = null;
				var curselected_oldid = null;
				var curpic = null;
				var curpic_oldid = null;
				var element = null;
				var pic = null;
				
				function displayThisTeach(elname, picname) {
				  /// pull the element corresponding to the input name and the currently-visible element
					element = document.getElementById(elname);
					curselected = document.getElementById('curselected');

					pic = document.getElementById(picname);
					curpic = document.getElementById('curselectedpic');
					
				  /// only toggle elements if clicked on non-selected picture
					if (element.style.display == \"none\") {
						element.style.display = \"table-row\";
						curselected.style.display = \"none\";

						pic.className = 'activePic';
						curpic.className = 'inactivePic';
						
					  /// reset the formerly-visible element's id
						curselected.id = curselected_oldid;
					  /// and update the relevant values for the newly-selected element
						curselected_oldid = element.id;
						curselected = element;
						curselected.id = 'curselected';

						curpic.id = curpic_oldid;
						curpic_oldid = pic.id;
						curpic = pic;
						pic.id = 'curselectedpic';
					}
				}
			</script>";
		
	/// establish feedback storage variables here (they're referred to later, even if there is no feedback saved)
		$feedbackdata = array();    // 2-d arr storing information of all saved feedback
		$thistext = '';             // stores the text to be put into the WYSIWYG editor as starting value
		$basename = 'fb_block_';    // establish the base of the ID attribute for each teacher feedback div
		$picname  = 'teacher_pic_'; // establish the base of the ID attribute for each teacher picture tab
		$teachernames = array();    // array mapping <teacherID> => <teacherName> (for clearer information for student)
		

	/// print the start of the feedback table
		echo '<table class="feedbackTable">';

	/// print out the start of the teacher/feedback-selection tab row
		echo '<tr id="teacherTabRowContainer">';
		echo '<td id="teacherTabRowContainerCell">';
		echo '<div class="teacherTabRow ' . (($gradingmode) ? 'left' : 'center') . '">';
		echo '<ul class="teacherPics">';

		// if this is the grading window, the current teacher's picture will be displayed (along with the WYSIWYG editor) no matter
		// what, so force that here
		if ($gradingmode) {
			echo 	"<li id='wysiwyg_pic' onclick = \"displayThisTeach('wysiwyg', 'wysiwyg_pic');\">";
			print_user_picture($USER, $lesson->course, $USER->picture, 0, false, false);
			echo 	'</li>';
		}
		
	/// regardless of mode, this content should only be called if there are other feedback
	/// records to display
		if ($feedbacks) {
			

		/// fill in the feedbackdata array with all the info for each teacher's submitted feedback
		/// feedback data looks like:
		///       teacherID => { 'text'   =>   <textual feedback>,
		///						 'files'  =>   <feedback file paths>,
		/// 					 'time'   =>   <time of most recent feedback submission> }
			foreach ($feedbacks as $feedback) {
			/// if this is the teacher's view (respond_window) and the feedback being examined
			/// is the viewing teacher's text feedback, save it and skip the below code
				if ($gradingmode && $feedback->teacherid == $USER->id && $feedback->text) {
					$thistext = $feedback->text;
					continue;
				}
				
			/// if the current feedback is from a teacher we haven't seen yet, initialize the feedback
			/// data structure for that teacher
				if (!array_key_exists($feedback->teacherid, $feedbackdata)) {
					$feedbackdata[$feedback->teacherid] = array();
					$feedbackdata[$feedback->teacherid]['text'] = '';
					$feedbackdata[$feedback->teacherid]['files'] = array();
					$feedbackdata[$feedback->teacherid]['time'] = 0;
				}
				
				// set text or file feedback for this item's submitting teacher appropriately
				if ($feedback->text) { $feedbackdata[$feedback->teacherid]['text'] = $feedback->text; }
				else if ($feedback->fname) {
					$dir = languagelesson_get_file_area($manattempt, $feedback);
					$feedbackdata[$feedback->teacherid]['files'][] = "$dir/$feedback->fname";
				}

				// and update the time of submission for the most recent feedback by this teacher
				if ($feedback->timeseen > $feedbackdata[$feedback->teacherid]['time']) {
					$feedbackdata[$feedback->teacherid]['time'] = $feedback->timeseen;
				}
				
			}
			
			
		// print out the rest of the teacher pictures in tabbed form to enable switching between different feedback sets; also implode
		// feedback files list here
			foreach ($feedbackdata as $teachID => $fbdata) {
				// implode each teacher's feedback file paths set into a comma-separated list
				$feedbackdata[$teachID]['files'] = implode(',', $fbdata['files']);

				// print this teacher's tab
				if ($teachID != $USER->id || !$gradingmode) {
					echo "<li id=\"{$picname}{$teachID}\" class=\"inactivePic\"
						onclick='displayThisTeach(\"{$basename}{$teachID}\", \"{$picname}{$teachID}\");'>";
					$thisteach = get_record('user', 'id', $teachID);
					print_user_picture($thisteach, $lesson->course, $thisteach->picture, 0, false, false);
					// store the teacher's full name for printing later to distinguish feedbacks
					$teachernames[$teachID] = fullname($thisteach);
					echo '</li>';
				}
				
			/// if this is the respond_window, include submission times in the text feedback
				if ($gradingmode && $teachID != $USER->id) {
					$a->fullname = fullname($thisteach);
					$a->text = $feedbackdata[$teachID]['text'];
					$feedbackdata[$teachID]['text'] = get_string('feedbacktextframe', 'languagelesson', $a);
				}
			}
			
			
		}

	// close out the teacher/feedback-tab row
		echo '</ul>';
		// print this to cancel out the float=left of the above ul
		echo '<div style="clear:both"></div>';
		echo '</td></tr>';


	// if this is the respond_window (the teacher is grading), then print out their required WYSIWYG editor
		if ($gradingmode) {
			echo 		"<tr id=\"wysiwyg\" class=\"contentRow\">
						 <td class=\"feedbackCell\">
						<script type=\"text/javascript\">
						  /// initialize the curselected data to point to the WYSIWYG editor
							var wysiwyg = document.getElementById('wysiwyg');
							curselected_oldid = 'wysiwyg';
							curselected = wysiwyg;
							curselected.id = 'curselected';

							var wyspic = document.getElementById('wysiwyg_pic');
							curpic_oldid = 'wysiwyg_pic';
							curpic = wyspic;
							curpic.id = 'curselectedpic';
							curpic.className = 'activePic';
						</script>";
			
			/// check if we can use the WYSIWYG
			$usehtmleditor = can_use_html_editor();
			/// print out the area for text feedback
			print_textarea($usehtmleditor,0,0,300,50, 'text_response', $thistext);
			/// if we can use WYSIWYG, switch it on
			if ($usehtmleditor) { use_html_editor('text_response'); }
			
			echo		'</td></tr>';
		}
		
		$teacherIDs = array_keys($feedbackdata);
		// there may be no submitted feedback yet, so set the div ids $firstFeedback and $firstPic accordingly
		if (! empty($teacherIDs)) {	$firstFeedback = $basename . $teacherIDs[0]; $firstPic = $picname . $teacherIDs[0]; }
		else { $firstFeedback = $basename; $firstPic = $picname; }
		$flag = false;
		foreach ($feedbackdata as $teachID => $fbarr) {
			echo "<tr id='{$basename}{$teachID}' class='contentRow' style='display:none'><td>";

			// open the single feedback table
			echo '<table class="singleFeedback">';

			// and open the teacher info/text feedback row
			echo '<tr class="textRow">';

			// print out the teacher's submission info
			echo '<td class="feedbackCell teacherInfoCell">';
			echo '<div class="teacherName">'.$teachernames[$teachID].'</div>';
			echo '<div class="submissionTime">'.userdate($fbarr['time']).'</div>';
			echo '</td>';

			// if there is text feedback, print it here
			if (!empty($fbarr['text'])) {
				echo '<td class="feedbackCell textFeedbackCell">';
				echo '<div class="subheader">'.get_string('comments','languagelesson').'</div>';
				echo '<div class="textFeedback">'.$fbarr['text'].'</div>';
				echo '</td>';
			}
			
			// close out the info row
			echo '</tr>';
			
			// now, if the student is viewing and there are feedback files to display, print them out here
			if (!$gradingmode && $fbarr['files']) {

				// open up the row and cell to contain the revlet
				echo '<tr class="filesRow">';
				echo '<td class="feedbackCell filesContainer" colspan="2">';

				echo '<div class="subheader">'.get_string('audioresponse','languagelesson').'</div>';

				// print out the instructions for hearing the feedback dependent on the question type (if it's an audio, it's complex
				// feedback; if it's ESSAY or VIDEO, it's simple
				echo '<div class="revletInstructions">'
					.get_string( (($manattempt->type == LL_AUDIO) ? 'feedbackplayerinstructions' : 'feedbackplayerinstructionssimple'),
							'languagelesson')
					.'</div>';
				
				if (!$flag) {
					$qmodpluginID = true;
					$modpluginID = 'plugina';
				}
				
			  /// show the FB player revlet stack
				include($CFG->dirroot . '/mod/languagelesson/runrev/feedback/player/revA.php');
	
				echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
				echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
				echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
				echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
				echo "\t\tid=\"" . $manattempt->lessonid . "\"\n";
				echo "\t\tuserid=\"" . $USER->id . "\"\n";
				
				// if this is an audio type question, we're using complex feedback, so get the path to the student's submitted file and
				// load it in as the basic file to display, then load in the (multiple) feedback files to display as speech bubbles
				if ($manattempt->type == LL_AUDIO) {
					$dir = languagelesson_get_file_area($manattempt);
					$src = "$dir/$manattempt->fname";
					echo "\t\tstudentfile=\"$src\"\n"; //path to the student file to be downloaded
					echo "\t\tfeedbackfnames=\"".$fbarr['files']."\"\n";
				}
				// if it's not, though, we're using simple feedback, so use the (ONE!) feedback file whose path is stored in
				// $fbarr['files'] and load it in as the main file, then load an empty list for the speech bubble files
				else {
					echo "\t\tstudentfile=\"".$fbarr['files']."\"\n";
					echo "\t\tfeedbackfnames=\"\"\n";
				}
				
			/// only include the revB file once (it's only necessary once); after that, just close
			/// the embedding tags
				if (!$flag) {
					include($CFG->dirroot . '/mod/languagelesson/runrev/revB.php');
					$flag = true;
					// make sure that the extra revlets in the page are still not in <divs id="plugin" ..., so that if revWeb is not
					// installed, the audio/video recorder gets hidden properly
					$modpluginID = "irrelevant";
				} else {
					echo "></embed></object></div>";
				}

				// close the containing cell and row
				echo '</td></tr>';
				
			}

			// close the feedback table
			echo '</table>';

			// close this teacher's feedback div
			echo '</td></tr>';
		}
		
		if (!$gradingmode) {
			echo '<script type="text/javascript">
					var firstFeedback = document.getElementById("'.$firstFeedback.'");
					firstFeedback.style.display = "table-row";
					curselected_oldid = "'.$firstFeedback.'";
					curselected = firstFeedback;
					curselected.id = "curselected";

					var firstPic = document.getElementById("'.$firstPic.'");
					firstPic.className = "activePic";
					curpic_oldid = "'.$firstPic.'";
					curpic = firstPic;
					curpic.id = "curselectedpic";
					</script>';
		}

		echo '</table>';

		// close the "feedbackarea" div
		echo '</div>';
		
	}

	// if there are no feedbacks, just display a FeedbackPlayer revlet with the student file in it
	//else if ($manattempt->type == LL_AUDIO) {
	if (! $gradingmode && ! $hasFeedbackFiles && $manattempt->type == LL_AUDIO) {

		echo '<div>'.get_string('yousubmitted', 'languagelesson').'</div>';
		echo '<div class="submissionTime">'.userdate($manattempt->timeseen).'</div>';

		$flag = false;
		$qmodpluginID = true;
		$modpluginID = 'plugina';
		
		// print out opening tags for revlet embed code 
		include($CFG->dirroot . '/mod/languagelesson/runrev/feedback/player/revA.php');
		// print out authentication variables
		echo "\t\tMoodleSession=\"". $_COOKIE['MoodleSession'] . "\"\n" ;  
		echo "\t\tMoodleSessionTest=\"" . $_COOKIE['MoodleSessionTest'] . "\"\n";
		echo "\t\tMOODLEID=\"" . $_COOKIE['MOODLEID_'] . "\"\n"; 
		echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
		echo "\t\tid=\"" . $manattempt->lessonid . "\"\n";
		echo "\t\tuserid=\"" . $USER->id . "\"\n";
		// print out filepath variables
		$stufilepath = languagelesson_get_student_file_path($manattempt);
		echo "\t\tstudentfile=\"" . $CFG->wwwroot . "/file.php" . $stufilepath . "\"\n"; //path to the student file to be
		echo "\t\tfeedbackfnames=\"\"\n";
		// print out closing tags for revlet embed code
		include($CFG->dirroot . '/mod/languagelesson/runrev/revB.php');
		
	}

}
	
	






////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////





/**
 * Fetches an array of users assigned to the 'student' role in the input course
 * @param int $courseid The Course to fetch students from
 */
function languagelesson_get_students($courseid)
{	
	// pull the context value for input courseid
	$context = get_context_instance(CONTEXT_COURSE, $courseid);
	
	// pull the student role record
	$role = get_record('role', 'shortname', 'student');

    // collect a list of users that match the role/context
    $students = get_role_users($role->id, $context);
	
	return $students;
}




/**
 * Constructs the local path to the directory containing a recorded file (starting from root of $CFG->datadir), returns it
 * @param object $manattempt The manual attempt record to be fetching files for
 * @param object $feedback The optional feedback record marking that this is building the URL of a feedback file
 * @param int $pageid Optional input for what pageid to use; this is only used if $manattempt is null
 * @param int $userid Optional input for what userid to use; this is only used if $manattempt is null
 * @return string $src The full http:// path to the file
 */
function languagelesson_get_local_file_area($manattempt, $feedback=null, $pageid=null, $userid=null) {
	global $lesson;

	if ($manattempt) {
		$pageid = $manattempt->pageid;
		$userid = $manattempt->userid;
	}

	$src = $lesson->course;
	$src .= '/moddata/languagelesson/';
	$src .= $lesson->id.'/'.$pageid.'/'.$userid.'';
	if ($feedback) {
		$src .= '/feedback';
	}
	return $src;
}
/**
 * Constructs the full, web-accessible path to the directory containing a recorded file
 * @param object $manattempt The manual attempt record to be fetching files for
 * @param object $feedback The optional feedback record marking that this is building the URL of a feedback file
 * @param int $pageid Optional input for what pageid to use; this is only used if $manattempt is null
 * @param int $userid Optional input for what userid to use; this is only used if $manattempt is null
 * @return string $src The full http:// path to the file
 */
function languagelesson_get_file_area($manattempt, $feedback=null, $pageid=null, $userid=null) {
	global $CFG;
	$src = $CFG->wwwroot . '/file.php/';
	$src .= languagelesson_get_local_file_area($manattempt, $feedback, $pageid, $userid);
	return $src;
}




/**
 * Breaks apart a CLOZE question text into text chunks between questions
 *
 * Assumes that cloze question locations are marked with anchor tags <a name="..."></a>
 *
 * NOTE that this runs using regular expressions. This is emphatically NOT the way to deal with HTML code most of the time, but the
 * situation here (finding the string indices of these tags, the fact that the tags are very specific) and the fact that using anchor
 * tags introduces a little bit of unreliability in and of itself (it would be, for example, easy to create unbalanced tags in a
 * question and throw off the processing by editing the HTML) make it such that regexes can be considered a viable solution, and in my
 * opinion possibly a better (certainly an easier and faster) solution than using DOM or XML parsers.
 *
 * @param string $text The question text to be parsed
 * @return array $chunks The chunks of the text around the cloze questions
 */
function languagelesson_parse_cloze($text) {
	
	// pattern to ungreedily find paired anchor open/close tags
	$pattern = '/<\s*a[^>]*name="[^"]*"[^>]*>.*<\/a>/U';

	// pull all matches of the pattern and store them in $elements
	preg_match_all($pattern, $text, $elements);
	// this stores them in a 2-d array, so pull the first (only) result (the matches with the first pattern)
	$elements = reset($elements);

	// initialize the array to hold the question text chunks
	$chunks = array();
	// set the current index (from which we look ahead) to 0
	$offset = 0;
	// init the array to hold question 
	foreach ($elements as $question) {
		// pull the index at which the current question starts, looking from the end of the previous question
		$start = strpos($text, $question, $offset);
		// pull the chunk of text between the end of the previous question and the start of this one
		$nextchunk = substr($text, $offset, $start-$offset);
		$chunks[] = $nextchunk;
		// pull the name value for this question (indicating the question number)
		$qnum = languagelesson_extract_qnum($question);
		// and store that in chunks (offset by 1, so that it's indexed from 0)
		$chunks[] = intval($qnum)-1;
		// move the current index past the end of this question
		$offset = $start + strlen($question);
	}
	// if there is a last chunk of text (after the final question), save it, otherwise, save an empty string
	if ($lastchunk = substr($text, $offset)) {
		$chunks[] = $lastchunk;
	} else {
		$chunks[] = '';
	}

	return $chunks;
}



function languagelesson_extract_qnum($question) {
	$doc = new DOMDocument();
	$doc->loadHTML($question);
	$links = $doc->getElementsByTagName('a');
	$anchor = $links->item(0);
	$name = $anchor->attributes->getNamedItem('name')->nodeValue;
	return $name;
}





function languagelesson_key_cloze_answers($answers) {
	// save the answers in an array, keyed to their order of appearance
	$keyedAnswers = array();
	foreach ($answers as $answer) {
		// only look at the actual answers, not custom feedback (saved to its own answer record)
		if ($answer->answer) {
			$atext = $answer->answer;
			list($num, $text) = explode('|', $atext);
			$answer->answer = $text;
			$keyedAnswers[$num] = $answer;
		}
	}
	return $keyedAnswers;
}






/**
 * Checks the input HTML question text against the list of answers given to make sure that the question is valid
 *
 * Valid is defined as:
 *  - Each question location is defined by a named anchor
 *  - The name of each anchor is a number
 *  - The number of each anchor matches with the number of exactly one answer
 *  - There are exactly as many question anchors and questions
 *
 * @param string $html The (plain, non-escaped) HTML of the question text to validate
 * @param array $answertexts The keyed array of answers as entered into the question page form
 * @return bool True if valid, errors out if invalid
 */
function languagelesson_validate_cloze_text($html, $answertexts, $dropdowns) {
	$valid = true;

	$doc = new DOMDocument();
	$doc->loadHTML($html);
	$links = $doc->getElementsByTagName('a');

	// pull the subset of the links that are anchors (have 'name' attribute)
	$anchors = array();
	foreach ($links as $link) {
		$attrs = $link->attributes;
		if ($attrs->getNamedItem('name')) {
			$anchors[] = $link;
		}
	}

	// pull the list of anchor names given (if any clones are found, error out)
	$namesseen = array();
	foreach ($anchors as $anchor) {
		$name = $anchor->attributes->getNamedItem('name')->nodeValue;
		if (in_array($name, $namesseen)) {
			$message = 'Found two questions with the same number!';
			$valid = false;
			break;
		} else if (!is_numeric($name)) {
			$message = 'Found non-numeric question label: '.$name;
			$valid = false;
			break;
		}
		$namesseen[] = $name;
	}

	// remove any empty items from $answertexts
	if ($valid) {
		$realanswers = array();
		foreach ($answertexts as $num => $answer) {
			if (!empty($answer)) { $realanswers[$num] = $answer; }
		}
		$answertexts = $realanswers;
	}

	// make sure the number of anchors and the number of answers match
	if ($valid) {
		$numqs = count($namesseen);
		$numas = count($answertexts);
		if ($numqs != $numas) {
			$message = "Cloze parsing: the number of questions placed in the text did not match the number of answers provided: $numqs
				questions found, $numas answers found. You may have forgotten to place a question, or to have named one.";
			$valid = false;
		}
	}

	// compare anchor names given to answer numbers given
	$matches = array();
	if ($valid) {
		foreach ($namesseen as $name) {
			$name = intval($name);
			$namecorrected = $name - 1; // the question names are indexed from 1, but answers are indexed from 0
			if (!isset($answertexts[$namecorrected])) {
				$message = 'Found question label with no corresponding answer: '.$name;
				$valid = false;
				break;
			}
			$matches[] = $name;
		}
	}

	// check that the number of matches corresponds with the number of answers
	if ($valid && count($matches) != count($answertexts)) {
		$message = 'More answers were provided than matched question labels given!';
		$valid = false;
	}

	// now go through and make sure that any drop-downs have a correct answer marked
	foreach ($dropdowns as $num => $val) {
		if ($val) {
			if (! preg_match('/=/', $answertexts[$num])) {
				$message = 'No correct answer was found for drop-down question '.($num+1);
				$valid = false;
			}
		}
	}

	// if an error was thrown, print it out here
	if (!$valid) {
		$text = "Cloze parsing: $message <br /><br />Please use your browser's back button to return to the question editing page.";
		error($text);
	}

	return true;
}




/*
 * Update the languagelesson instance's calculated maximum grade
 *
 * @param int $lessonid The ID of the lesson to update
 */
function recalculate_maxgrade($lessonid) {
	// initialize the array containing pageID => best score
	$bestscores = array();

	// pull all pages and answers
	$pages = get_records('languagelesson_pages', 'lessonid', $lessonid);
	$answers = get_records('languagelesson_answers', 'lessonid', $lessonid);
	
	// construct the array storing page => [answers]
	$answersByPageID = array();
	foreach ($answers as $answer) {
		if (!array_key_exists($answer->pageid, $answersByPageID)) {
			$answersByPageID[$answer->pageid] = array();
		}
		$answersByPageID[$answer->pageid][] = $answer;
	}
	
	// Find the highest possible score per page
	foreach ($answersByPageID as $pageid => $answerset) {
		$page = $pages[$pageid];
		// if we're looking at a page with multiple correct answers, sum their scores
		if (($page->qtype == LL_MULTICHOICE && $page->qoption) // it's a multiple-choice with multiple correct answers
			|| $page->qtype == LL_MATCHING
			|| $page->qtype == LL_CLOZE) {
			$thissum = 0;
			foreach ($answerset as $answer) {
				if ($answer->score > 0 && (!empty($answer->answer))) { $thissum += $answer->score; }
			}
			$bestscores[$page->id] = $thissum;
		}
		// otherwise, pull the highest score from the possible answers
		else {
			foreach ($answerset as $answer) {
				if(!array_key_exists($page->id, $bestscores)) {
					$bestscores[$page->id] = $answer->score;
				} else if ($bestscores[$page->id] < $answer->score) {
					$bestscores[$page->id] = $answer->score;
				}
			}
		}
	}
		
	// and sum them to get the total
	$totalpts = array_sum($bestscores);

	// now update the instance's set grade value
	if (! set_field('languagelesson', 'grade', $totalpts, 'id', $lessonid)) {
		error('Updatepage: Could not update languagelesson instance saved max grade');
	}

}

?>
