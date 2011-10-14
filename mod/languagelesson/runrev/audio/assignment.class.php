<?php // $Id$

/**
 * Extend the base assignment class for assignments where you upload a single file
 *
 */
class assignment_audio2 extends assignment_base {


    function print_student_answer($userid, $return=false){
           global $CFG, $USER;

        $filearea = $this->file_area_name($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);
                    $ffurl = get_file_url("$filearea/$file");

                    //died right here
                    //require_once($ffurl);
                    $output = '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                            '<a href="'.$ffurl.'" >'.$file.'</a><br />';
                }
            }
        }

        $output = '<div class="files">'.$output.'</div>';
        return $output;
    }

    function assignment_audio2($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'audio2';
    }

    function view() {

        global $USER;

        $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        require_capability('mod/assignment:view', $context);

        add_to_log($this->course->id, "assignment", "view", "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

        $this->view_header();

        $this->view_intro();

        $this->view_dates();

        $filecount = $this->count_user_files($USER->id);

        if ($submission = $this->get_submission()) {
            if ($submission->timemarked) {
                $this->view_feedback();
            }
            if ($filecount) {
                // print_simple_box($this->print_user_files($USER->id, true), 'center');

                $formatoptions = new stdClass;
                $formatoptions->noclean = true;
                $formatoptions->filter = 1;
                $formatoptions->nocache = true;
                // print_simple_box($this->print_user_files($USER->id, true), 'center');
                print_simple_box(
                    format_text($this->print_user_files($USER->id, true), "0", $formatoptions),
                    'center');



            }
        }

        if (has_capability('mod/assignment:submit', $context)  && $this->isopen() && $submission->timemarked == 0 && ($filecount == 0 || $this->assignment->resubmit == 1 ) ) {
            $this->view_upload_form();
        }

        $this->view_footer();
    }


    function view_upload_form() {
        global $CFG;
        $struploadafile = get_string("uploadafile");

        $maxbytes = $this->assignment->maxbytes == 0 ? $this->course->maxbytes : $this->assignment->maxbytes;
        $strmaxsize = get_string('maxsize', '', display_size($maxbytes));

        echo '<div style="text-align:center">';
/*
        echo '<form enctype="multipart/form-data" method="post" '.
             "action=\"$CFG->wwwroot/mod/assignment/upload.php\">";
        echo '<fieldset class="invisiblefieldset">';
        echo "<p>$struploadafile ($strmaxsize)</p>";
        echo '<input type="hidden" name="id" value="'.$this->cm->id.'" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        
        require_once($CFG->libdir.'/uploadlib.php');
        upload_print_form_fragment(1,array('newfile'),false,null,0,$this->assignment->maxbytes,false);
        echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
        echo '</fieldset>';
        echo '</form>';
*/

/*
        $video_url = $CFG->wwwroot . 
                    '/mod/assignment/type/video/video.php?id=' . 
                    $this->cm->id .
                    '&sesskey=' . sesskey() . 
                    '&MoodleSession=' . $_REQUEST['MoodleSession'] . 
                    '&MoodleSessionTest=' . $_REQUEST['MoodleSessionTest'] . 
                    '&MOODLEID=' . $_REQUEST['MOODLEID_'] ;

        echo "<a href=\"#\" onclick='javascript: window.open(\"" . $video_url . "\", \"video_window\", \"width=800,height=600\");'>popup</a>"; 

        echo " / " ; 

        echo '<a href=' . $CFG->wwwroot . '/mod/assignment/type/video/video.php?id=' . $this->cm->id . 
                '&sesskey=' . sesskey() . 
                '&MoodleSession=' . $_REQUEST['MoodleSession'] . 
                '&MoodleSessionTest=' . $_REQUEST['MoodleSessionTest'] . 
                '&MOODLEID=' . $_REQUEST['MOODLEID_'] . 
                '>Launch Video Capture</a>'; 

        echo "<br /><br />"; 
*/
        readfile($CFG->wwwroot . "/mod/assignment/type/audio2/revA.html");

        echo "\t\tMoodleSession=\"". $_REQUEST['MoodleSession'] . "\"\n" ;  
        echo "\t\tMoodleSessionTest=\"" . $_REQUEST['MoodleSessionTest'] . "\"\n";
        echo "\t\tMOODLEID=\"" . $_REQUEST['MOODLEID_'] . "\"\n"; 
        echo "\t\tsesskey=\"" . sesskey() . "\"\n"; 
        echo "\t\tid=\"" . $this->cm->id . "\"\n"; 
        echo "\t\tuploadtarget=\"" . $CFG->wwwroot . "/mod/assignment/upload.php\"\n"; 
        echo "\t\tuploadhost=\"" . str_replace("https://", "", $CFG->wwwroot) . "\"\n"; 
        echo "\t\tuploadpath=\"/mod/assignment/upload.php\"\n"; 
        echo "\t\tuploadparams=\"id=" . $this->cm->id . "&sesskey=" . sesskey() . "\"\n"; 
        readfile($CFG->wwwroot . "/mod/assignment/type/audio2/revB.html");
        echo '</div>';
    }


    function upload() {

        global $CFG, $USER;

        require_capability('mod/assignment:submit', get_context_instance(CONTEXT_MODULE, $this->cm->id));

        $this->view_header(get_string('upload'));

        $filecount = $this->count_user_files($USER->id);
        $submission = $this->get_submission($USER->id);
        if ($this->isopen() && (!$filecount || $this->assignment->resubmit || !$submission->timemarked)) {
            if ($submission = $this->get_submission($USER->id)) {
                //TODO: change later to ">= 0", to prevent resubmission when graded 0
                if (($submission->grade > 0) and !$this->assignment->resubmit) {
                    notify(get_string('alreadygraded', 'assignment'));
                }
            }

            $dir = $this->file_area_name($USER->id);

            require_once($CFG->dirroot.'/lib/uploadlib.php');
            $um = new upload_manager('newfile',true,false,$this->course,false,$this->assignment->maxbytes);
            if ($um->process_file_uploads($dir) and confirm_sesskey()) {
                $newfile_name = $um->get_new_filename();
                if ($submission) {
                    $submission->timemodified = time();
                    $submission->numfiles     = 1;
                    $submission->submissioncomment = addslashes($submission->submissioncomment);
                    unset($submission->data1);  // Don't need to update this.
                    unset($submission->data2);  // Don't need to update this.
                    if (update_record("assignment_submissions", $submission)) {
                        add_to_log($this->course->id, 'assignment', 'upload',
                                'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                        $submission = $this->get_submission($USER->id);
                        $this->update_grade($submission);
                        $this->email_teachers($submission);
                        print_heading(get_string('uploadedfile'));
                    } else {
                        notify(get_string("uploadfailnoupdate", "assignment"));
                    }
                } else {
                    $newsubmission = $this->prepare_new_submission($USER->id);
                    $newsubmission->timemodified = time();
                    $newsubmission->numfiles = 1;
                    if (insert_record('assignment_submissions', $newsubmission)) {
                        add_to_log($this->course->id, 'assignment', 'upload',
                                'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                        $submission = $this->get_submission($USER->id);
                        $this->update_grade($submission);
                        $this->email_teachers($newsubmission);
                        print_heading(get_string('uploadedfile'));
                    } else {
                        notify(get_string("uploadnotregistered", "assignment", $newfile_name) );
                    }
                }
            }
        } else {
            notify(get_string("uploaderror", "assignment")); //submitting not allowed!
        }

        print_continue('view.php?id='.$this->cm->id);

        $this->view_footer();
    }

    function setup_elements(&$mform) {
        global $CFG, $COURSE;

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'resubmit', get_string("allowresubmit", "assignment"), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit', get_string('allowresubmit', 'assignment'), 'assignment'));
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string("emailteachers", "assignment"), $ynoptions);
        $mform->setHelpButton('emailteachers', array('emailteachers', get_string('emailteachers', 'assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumsize', 'assignment'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

    }

    function print_user_files($userid=0, $return=false) {
        global $CFG, $USER;

        if (!$userid) {
            if (!isloggedin()) {
                return '';
            }
            $userid = $USER->id;
        }

        $filearea = $this->file_area_name($userid);

        $output = '';

        if ($basedir = $this->file_area($userid)) {
            if ($files = get_directory_list($basedir)) {
                require_once($CFG->libdir.'/filelib.php');
                foreach ($files as $key => $file) {

                    $icon = mimeinfo('icon', $file);
                    $ffurl = get_file_url("$filearea/$file");

                    $output .= '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
                            '<a href="'.$ffurl.'" >'.$file.'</a><br />';
                }
            }
        }

        $output = '<div class="files">'.$output.'</div>';

        if ($return) {
            return $output;
        }
        echo $output;
    }




}

?>
