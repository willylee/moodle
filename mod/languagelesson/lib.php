<?php  // $Id$
/**
 * Standard library of functions and constants for lesson
 *
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

define("LL_MAX_EVENT_LENGTH", "432000");   // 5 days maximum

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $lesson Lesson post data from the form
 * @return int
 **/
function languagelesson_add_instance($lesson) {
    global $SESSION;

    languagelesson_process_pre_save($lesson);

    if (!$lesson->id = insert_record("languagelesson", $lesson)) {
        return false; // bad
    }

    languagelesson_process_post_save($lesson);

    languagelesson_grade_item_update(stripslashes_recursive($lesson));

    return $lesson->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $lesson Lesson post data from the form
 * @return boolean
 **/
function languagelesson_update_instance($lesson) {

    $lesson->id = $lesson->instance;

    languagelesson_process_pre_save($lesson);

    if (!$result = update_record("languagelesson", $lesson)) {
        return false; // Awe man!
    }

    languagelesson_process_post_save($lesson);

    // update grade item definition
    languagelesson_grade_item_update(stripslashes_recursive($lesson));

    // update grades - TODO: do it only when grading style changes
    languagelesson_update_grades(stripslashes_recursive($lesson), 0, false);

    return $result;
}


/*******************************************************************/
function languagelesson_delete_instance($id) {
/// Given an ID of an instance of this module,
/// this function will permanently delete the instance
/// and any data that depends on it.
	global $CFG;
    if (! $lesson = get_record("languagelesson", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("languagelesson", "id", "$lesson->id")) {
        $result = false;
    }
    if (! delete_records("languagelesson_pages", "lessonid", "$lesson->id")) {
        $result = false;
    }
    if (! delete_records("languagelesson_answers", "lessonid", "$lesson->id")) {
        $result = false;
    }
    if (!delete_records("languagelesson_attempts", "lessonid", "$lesson->id")) {
        $result = false;
    }
	if (! languagelesson_delete_user_files($lesson, null, null, false)) { // delete everything
		$result = false;
    }
    if (! delete_records("languagelesson_grades", "lessonid", "$lesson->id")) {
        $result = false;
    }
    if (! delete_records("languagelesson_timer", "lessonid", "$lesson->id")) {
            $result = false;
    }
    if (! delete_records("languagelesson_seenbranches", "lessonid", "$lesson->id")) {
            $result = false;
    }
    if ($events = get_records_select('event', "modulename = 'languagelesson' and instance = '$lesson->id'")) {
        foreach($events as $event) {
            delete_event($event->id);
        }
    }
    $pagetypes = page_import_types('mod/languagelesson/');
    foreach ($pagetypes as $pagetype) {
        if (!blocks_delete_all_on_page($pagetype, $lesson->id)) {
            $result = false;
        }
    }

    languagelesson_grade_item_delete($lesson);

    return $result;
}



/**
 * Deletes all of a user's containing subfolders for the pages within a given languagelesson's folder in the course's moddata
 *
 * @param object $lesson Lesson record object for the LL to delete attempts from (if this is null, delete moddata/languagelesson)
 * @param int $pageid ID of the page to delete from (if this is null, delete over all pages)
 * @param int $userid ID of the user whose attempts we're deleting (if this is null, we delete ALL user data)
 * @param bool $errorout If directory deletion fails, should we throw an error?
 * @param int $courseid ID of the course to delete LLs from (only used if $lesson is null)
 */
function languagelesson_delete_user_files($lesson=null, $pageid=null, $userid=null, $errorout=true, $courseid=null) {
	global $CFG;

	// initialize the error output to blank
	$error = '';
	
	// set the path to the moddata/languagelesson folder based on inputs
	if ($lesson) {
		$rootdir = "$CFG->dataroot/$lesson->course/moddata/languagelesson";
	} else {
		$rootdir = "$CFG->dataroot/$courseid/moddata/languagelesson";
	}

	// if we are deleting all languagelessons, just hit the moddata/languagelesson folder
	if ($lesson === null) {
		if (! remove_dir($rootdir)) {
			$error .= "Failed to nuke languagelesson moddata folder!";
		}
	}
	// otherwise, look specifically at the input languagelesson
	else {
		$lldir = "$rootdir/$lesson->id";

		// if no attempts have been submitted at all, then the above directory will not exist or be empty, so leave quietly
		if (! $pages = get_directory_list($lldir, '', false, true, false)) { return true; }

		// if we're getting rid of everything in the lesson, wipe it
		if ($pageid === null && $userid === null) {
			if (! remove_dir($lldir)) {
				$error .= "Failed to remove this languagelesson directory: $lldir";
			}
		// if we're getting rid of a specific page, try to wipe it
		} else if ($pageid && $userid === null) {
			// if the page is not in $pages, it has no moddata saved, so leave quietly
			if (! array_search($pageid, $pages)) { return true; }
			$pagedir = "$lldir/$pageid";
			if (! remove_dir($pagedir)) {
				$error .= "Failed to remove the page directory: $pagedir";
			}
		// if we're getting rid of a specific user's data, loop over the pages to find directories containing their files
		} else if ($pageid === null && $userid) {
			// init the array containing paths to their userdata folders
			$userdirs = array();

			// now loop over the page subfolders to see where we need to delete data
			foreach ($pages as $page) {
				$pagedir = "$lldir/$page";
				// if there is no submitted content to be found, ignore this page
				if (! $users = get_directory_list($pagedir, '', false, true, false)) {
					continue;
				}
				// if the queried user has submitted content, save the path to their directory into $dirs
				if (in_array($userid, $users)) {
					$userdirs[] = "$pagedir/$userid";
				}
			}

			// if $dirs is empty, there are no folders to delete, so leave quietly
			if (count($userdirs) < 1) {	return true; }

			// otherwise, loop over the dirs to delete and delete them
			foreach ($userdirs as $userdir) {
				if (! remove_dir($userdir)) {
					$error .= "Failed to remove directory $userdir<br />";
				}
			}
		// otherwise, we're getting rid of a specific user's data on a specific page
		} else {
			$dir = "$lldir/$pageid/$userid";
			if (! remove_dir($dir)) {
				$error .= "Failed to remove user $userid's data on page $pageid";
			}
		}
	}

	// if we failed to remove any directories, print them out here
	if (! empty($error) && $errorout) {
		error($error);
	}
	else if (! empty($error)) {
		return false;
	}

	return true;
	
}





/**
 * Given a course object, this function will clean up anything that
 * would be leftover after all the instances were deleted.
 *
 * As of now, this function just cleans the lesson_default table
 *
 * @param object $course an object representing the course that is being deleted
 * @param boolean $feedback to specify if the process must output a summary of its work
 * @return boolean
 */
function languagelesson_delete_course($course, $feedback=true) {

    $count = count_records('languagelesson_default', 'course', $course->id);
    delete_records('languagelesson_default', 'course', $course->id);

    //Inform about changes performed if feedback is enabled
    if ($feedback) {
        notify(get_string('deletedefaults', 'languagelesson', $count));
    }

    return true;
}

/*******************************************************************/
function languagelesson_user_outline($course, $user, $mod, $lesson) {
/// Return a small object with summary information about what a
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'languagelesson', $lesson->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $return->info = get_string("no")." ".get_string("attempts", "languagelesson");
    } else {
        $grade = reset($grades->items[0]->grades);
        $return->info = get_string("grade") . ': ' . $grade->str_long_grade;
        $return->time = $grade->dategraded;
    }
    return $return;
}

/*******************************************************************/
function languagelesson_user_complete($course, $user, $mod, $lesson) {
/// Print a detailed representation of what a  user has done with
/// a given particular instance of this module, for user activity reports.
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'languagelesson', $lesson->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo '<p>'.get_string('grade').': '.$grade->str_long_grade.'</p>';
        if ($grade->str_feedback) {
            echo '<p>'.get_string('feedback').': '.$grade->str_feedback.'</p>';
        }
    }

    if ($attempts = get_records_select("languagelesson_attempts", "lessonid = $lesson->id AND userid = $user->id",
                "retry, timeseen")) {
        print_simple_box_start();
        $table->head = array (get_string("attempt", "languagelesson"),  get_string("numberofpagesviewed", "languagelesson"),
            get_string("numberofcorrectanswers", "languagelesson"), get_string("time"));
        $table->width = "100%";
        $table->align = array ("center", "center", "center", "center");
        $table->size = array ("*", "*", "*", "*");
        $table->cellpadding = 2;
        $table->cellspacing = 0;
		
        $retry = 0;
        $npages = 0;
        $ncorrect = 0;

        foreach ($attempts as $attempt) {
            if ($attempt->retry == $retry) {
                $npages++;
                if ($attempt->correct) {
                    $ncorrect++;
                }
                $timeseen = $attempt->timeseen;
            } else {
                $table->data[] = array($retry + 1, $npages, $ncorrect, userdate($timeseen));
                $retry++;
                $npages = 1;
                if ($attempt->correct) {
                    $ncorrect = 1;
                } else {
                    $ncorrect = 0;
                }
            }
        }
        if ($npages) {
                $table->data[] = array($retry + 1, $npages, $ncorrect, userdate($timeseen));
        }
        print_table($table);
        print_simple_box_end();
    }

    return true;
}

/**
 * Prints lesson summaries on MyMoodle Page
 *
 * Prints lesson name, due date and attempt information on
 * lessons that have a deadline that has not already passed
 * and it is available for taking.
 *
 * @param array $courses An array of course objects to get lesson instances from
 * @param array $htmlarray Store overview output array( course ID => 'lesson' => HTML output )
 */
function languagelesson_print_overview($courses, &$htmlarray) {
    global $USER, $CFG;

    if (!$lessons = get_all_instances_in_courses('languagelesson', $courses)) {
        return;
    }

/// Get Necessary Strings
    $strlesson       = get_string('modulename', 'languagelesson');
    $strnotattempted = get_string('nolessonattempts', 'languagelesson');
    $strattempted    = get_string('lessonattempted', 'languagelesson');

    $now = time();
    foreach ($lessons as $lesson) {
        if ($lesson->deadline != 0                                         // The lesson has a deadline
            and $lesson->deadline >= $now                                  // And it is before the deadline has been met
            and ($lesson->available == 0 or $lesson->available <= $now)) { // And the lesson is available

            // Lesson name
            if (!$lesson->visible) {
                $class = ' class="dimmed"';
            } else {
                $class = '';
            }
            $str = print_box("$strlesson: <a$class href=\"$CFG->wwwroot/mod/languagelesson/view.php?id=$lesson->coursemodule\">".
                             format_string($lesson->name).'</a>', 'name', '', true);

            // Deadline
            $str .= print_box(get_string('lessoncloseson', 'languagelesson', userdate($lesson->deadline)), 'info', '', true);

            // Attempt information
            if (has_capability('mod/languagelesson:manage', get_context_instance(CONTEXT_MODULE, $lesson->coursemodule))) {
                // Number of user attempts
                $attempts = count_records('languagelesson_attempts', 'lessonid', $lesson->id);
                $str     .= print_box(get_string('xattempts', 'languagelesson', $attempts), 'info', '', true);
            } else {
                // Determine if the user has attempted the lesson or not
                if (count_records('languagelesson_attempts', 'lessonid', $lesson->id, 'userid', $USER->id)) {
                    $str .= print_box($strattempted, 'info', '', true);
                } else {
                    $str .= print_box($strnotattempted, 'info', '', true);
                }
            }
            $str = print_box($str, 'lesson overview', '', true);

            if (empty($htmlarray[$lesson->course]['lesson'])) {
                $htmlarray[$lesson->course]['lesson'] = $str;
            } else {
                $htmlarray[$lesson->course]['lesson'] .= $str;
            }
        }
    }
}

/*******************************************************************/
function languagelesson_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such
/// as sending out mail, toggling flags etc ...

    global $CFG;

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $lessonid id of lesson
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function languagelesson_get_user_grades($lesson, $userid=0) {
    global $CFG;
	
	/// each user only ever has one grade for any given lesson at any time,
	/// so all we need to do is join the tables and return the results

    $user = $userid ? "AND u.id = $userid" : "";
    $fuser = $userid ? "AND uu.id = $userid" : "";

	$sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
			  FROM {$CFG->prefix}user u, {$CFG->prefix}languagelesson_grades g
			 WHERE u.id = g.userid AND g.lessonid = $lesson->id
				   $user";

    return get_records_sql($sql);
}

/**
 * Update grades in central gradebook
 *
 * @param object $lesson null means all lessons
 * @param int $userid specific user only, 0 mean all
 */
function languagelesson_update_grades($lesson=null, $userid=0, $nullifnone=true) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($lesson != null) {
        if ($grades = languagelesson_get_user_grades($lesson, $userid)) {
            languagelesson_grade_item_update($lesson, $grades);

        } else if ($userid and $nullifnone) {
            $grade = new object();
            $grade->userid   = $userid;
            $grade->rawgrade = NULL;
            languagelesson_grade_item_update($lesson, $grade);

        } else {
            languagelesson_grade_item_update($lesson);
        }

    } else {
        $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
                  FROM {$CFG->prefix}languagelesson l, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='languagelesson' AND m.id=cm.module AND cm.instance=l.id";
        if ($rs = get_recordset_sql($sql)) {
            while ($lesson = rs_fetch_next_record($rs)) {
                if ($lesson->grade != 0) {
                    languagelesson_update_grades($lesson, 0, false);
                } else {
                    languagelesson_grade_item_update($lesson);
                }
            }
            rs_close($rs);
        }
    }
}

/**
 * Create grade item for given lesson
 *
 * @param object $lesson object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function languagelesson_grade_item_update($lesson, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $lesson)) { //it may not be always present
        $params = array('itemname'=>$lesson->name, 'idnumber'=>$lesson->cmidnumber);
    } else {
        $params = array('itemname'=>$lesson->name);
    }

    if ($lesson->grade > 0 && $lesson->type != LL_TYPE_PRACTICE) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $lesson->grade;
        $params['grademin']   = 0;

    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $lesson->grade / 100);
        }
    }

    return grade_update('mod/languagelesson', $lesson->course, 'mod', 'languagelesson', $lesson->id, 0, $grades, $params);
}

/**
 * Delete grade item for given lesson
 *
 * @param object $lesson object
 * @return object lesson
 */
function languagelesson_grade_item_delete($lesson) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/languagelesson', $lesson->course, 'mod', 'languagelesson', $lesson->id, 0, NULL, array('deleted'=>1));
}


/*******************************************************************/
function languagelesson_get_participants($lessonid) {
//Must return an array of user records (all data) who are participants
//for a given instance of lesson. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)

    global $CFG;

    //Get students
    $students = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}languagelesson_attempts a
                                 WHERE a.lessonid = '$lessonid' and
                                       u.id = a.userid");

    //Return students array (it contains an array of unique users)
    return ($students);
}

function languagelesson_get_view_actions() {
    return array('view','view all');
}

function languagelesson_get_post_actions() {
    return array('end','start', 'update grade attempt');
}

/**
 * Runs any processes that must run before
 * a lesson insert/update
 *
 * @param object $lesson Lesson form data
 * @return void
 **/
function languagelesson_process_pre_save(&$lesson) {
    $lesson->timemodified = time();

    if (empty($lesson->timed)) {
        $lesson->timed = 0;
    }
    if (empty($lesson->timespent) or !is_numeric($lesson->timespent) or $lesson->timespent < 0) {
        $lesson->timespent = 0;
    }
    if (!isset($lesson->completed)) {
        $lesson->completed = 0;
    }
    if (empty($lesson->gradebetterthan) or !is_numeric($lesson->gradebetterthan) or $lesson->gradebetterthan < 0) {
        $lesson->gradebetterthan = 0;
    } else if ($lesson->gradebetterthan > 100) {
        $lesson->gradebetterthan = 100;
    }

    // Conditions for dependency
    $conditions = new stdClass;
    $conditions->timespent = $lesson->timespent;
    $conditions->completed = $lesson->completed;
    $conditions->gradebetterthan = $lesson->gradebetterthan;
    $lesson->conditions = addslashes(serialize($conditions));
    unset($lesson->timespent);
    unset($lesson->completed);
    unset($lesson->gradebetterthan);

    if ($lesson->lessondefault) {
        $default = new stdClass;
        $default = clone($lesson);
        unset($default->name);
        unset($default->timemodified);
        unset($default->available);
        unset($default->deadline);
        if ($default->id = get_field('languagelesson_default', 'id', 'course', $default->course)) {
            update_record('languagelesson_default', $default);
        } else {
            insert_record('languagelesson_default', $default);
        }
    }
    unset($lesson->lessondefault);
}

/**
 * Runs any processes that must be run
 * after a lesson insert/update
 *
 * @param object $lesson Lesson form data
 * @return void
 **/
function languagelesson_process_post_save(&$lesson) {
    if ($events = get_records_select('event', "modulename = 'languagelesson' and instance = '$lesson->id'")) {
        foreach($events as $event) {
            delete_event($event->id);
        }
    }

    $event = new stdClass;
    $event->description = $lesson->name;
    $event->courseid    = $lesson->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'languagelesson';
    $event->instance    = $lesson->id;
    $event->eventtype   = 'open';
    $event->timestart   = $lesson->available;
    $event->visible     = instance_is_visible('languagelesson', $lesson);
    $event->timeduration = ($lesson->deadline - $lesson->available);

    if ($lesson->deadline and $lesson->available and $event->timeduration <= LL_MAX_EVENT_LENGTH) {
        // Single event for the whole lesson.
        $event->name = $lesson->name;
        add_event($event);
    } else {
        // Separate start and end events.
        $event->timeduration  = 0;
        if ($lesson->available) {
            $event->name = $lesson->name.' ('.get_string('lessonopens', 'languagelesson').')';
            add_event($event);
            unset($event->id); // So we can use the same object for the close event.
        }
        if ($lesson->deadline) {
            $event->name      = $lesson->name.' ('.get_string('lessoncloses', 'languagelesson').')';
            $event->timestart = $lesson->deadline;
            $event->eventtype = 'close';
            add_event($event);
        }
    }
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the lesson.
 * @param $mform form passed by reference
 */
function languagelesson_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'lessonheader', get_string('modulenameplural', 'languagelesson'));
    $mform->addElement('advcheckbox', 'reset_languagelesson', get_string('deleteallattempts','languagelesson'));
}

/**
 * Course reset form defaults.
 */
function languagelesson_reset_course_form_defaults($course) {
    return array('reset_languagelesson'=>1);
}

/**
 * Removes all grades from gradebook
 * @param int $courseid
 * @param string optional type
 */
function languagelesson_reset_gradebook($courseid, $type='') {
    global $CFG;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
              FROM {$CFG->prefix}languagelesson l, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
             WHERE m.name='languagelesson' AND m.id=cm.module AND cm.instance=l.id AND l.course=$courseid";

    if ($lessons = get_records_sql($sql)) {
        foreach ($lessons as $lesson) {
            languagelesson_grade_item_update($lesson, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset courses functionality, delete all the
 * lesson attempts for course $data->courseid.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function languagelesson_reset_userdata($data) {
    global $CFG;

    $componentstr = get_string('modulenameplural', 'languagelesson');
    $status = array();

    if (!empty($data->reset_languagelesson)) {
        $lessonssql = "SELECT l.id
                         FROM {$CFG->prefix}languagelesson l
                        WHERE l.course={$data->courseid}";


        delete_records_select('languagelesson_timer', "lessonid IN ($lessonssql)");
        delete_records_select('languagelesson_grades', "lessonid IN ($lessonssql)");
        delete_records_select('languagelesson_seenbranches', "lessonid IN ($lessonssql)");
        delete_records_select('languagelesson_attempts', "lessonid IN ($lessonssql)");
        delete_records_select('languagelesson_manattempts', "lessonid IN ($lessonssql)");
        delete_records_select('languagelesson_feedback', "lessonid IN ($lessonssql)");
		languagelesson_delete_user_files(null, null, null, true, $data->courseid); // nuke all the user-submitted files for every LL 

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            languagelesson_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', 'languagelesson'), 'error'=>false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('languagelesson', array('available', 'deadline'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * Returns all other caps used in module
 */
function languagelesson_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * Tells if files in moddata are trusted and can be served without XSS protection.
 * @return bool true if file can be submitted by teacher only (trusted), false otherwise
 */
function languagelesson_is_moddata_trusted() {
    return true;
}








function languagelesson_install() {
	return true;
}

?>
