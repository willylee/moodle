<?php  // $Id: mod_form.php 673 2011-09-01 20:40:12Z griffisd $
/**
 * Form to define a new instance of lesson or edit an instance.
 * It is used from /course/modedit.php.
 *
 * @version $Id: mod_form.php 673 2011-09-01 20:40:12Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once('locallib.php');

class mod_languagelesson_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $LL_NEXTPAGE_ACTION, $COURSE;

        $mform    =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        // Create a text box that can be enabled/disabled for lesson time limit
        $timedgrp = array();
        $timedgrp[] = &$mform->createElement('text', 'maxtime');
        $timedgrp[] = &$mform->createElement('checkbox', 'timed', '', get_string('enable'));
        $mform->addGroup($timedgrp, 'timedgrp', get_string('maxtime', 'languagelesson'), array(' '), false);
        $mform->disabledIf('timedgrp', 'timed');

        // Add numeric rule to text field
        $timedgrprules = array();
        $timedgrprules['maxtime'][] = array(null, 'numeric', null, 'client');
        $mform->addGroupRule('timedgrp', $timedgrprules);

        // Rest of group setup
        $mform->setDefault('timed', 0);
        $mform->setDefault('maxtime', 20);
        $mform->setType('maxtime', PARAM_INT);
        $mform->setHelpButton('timedgrp', array('timed', get_string('timed', 'languagelesson'), 'languagelesson'));


//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('gradeoptions', 'languagelesson'));

		$options = array();
		$options[LL_TYPE_PRACTICE] = get_string('practicetype', 'languagelesson');
		$options[LL_TYPE_ASSIGNMENT] = get_string('assignmenttype', 'languagelesson');
		$options[LL_TYPE_TEST] = get_string('testtype', 'languagelesson');
        $mform->addElement('select', 'type', get_string('type', 'languagelesson'), $options);
        $mform->setHelpButton('type', array('type', get_string('type', 'languagelesson'),'languagelesson'));
        $mform->setDefault('type', LL_TYPE_ASSIGNMENT);
		// assign custom javascript for updating form values based on lesson type selection
		$changeEvent = "var theform = document.getElementById('mform1');
						var typeField = theform.elements['type']
						
						autograde = theform.elements['autograde'];
						defaultpoints = theform.elements['defaultpoints'];
						penalty = theform.elements['penalty'];
						penaltytype = theform.elements['penaltytype'];
						penaltyvalue = theform.elements['penaltyvalue'];
						ongoingscore = theform.elements['showongoingscore'];
						maxattempts = theform.elements['maxattempts'];
						showoldanswer = theform.elements['showoldanswer'];
						defaultfeedback = theform.elements['defaultfeedback'];
						contextcolors = theform.elements['contextcolors'];
						
						if (typeField.value == ".LL_TYPE_PRACTICE.") {
							autograde.disabled = true;
							defaultpoints.disabled = true;
							penalty.disabled = true;
							penaltytype.disabled = true;
							penaltyvalue.disabled = true;
							ongoingscore.disabled = true;
							maxattempts.value = 0;
							showoldanswer.value = 1;
							defaultfeedback.value = 1;
							contextcolors.value = 1;
						} else {
							autograde.disabled = false;
							defaultpoints.disabled = false;
							penalty.disabled = false;
							if (penalty.value == '1') { penaltytype.disabled = false; }
							if (!penaltytype.disabled &&
								penaltytype.value == '".LL_PENALTY_SET."') { penaltyvalue.disabled = false; }
							ongoingscore.disabled = false;
							
							// if it's a test, change other things as necessary
							if (typeField.value == ".LL_TYPE_TEST.") {
								maxattempts.value = 1;
								showoldanswer.value = 0;
								defaultfeedback.value = 0;
								contextcolors.value = 0;
							} else {
								maxattempts.value = 0;
								showoldanswer.value = 1;
								contextcolors.value = 1;
							}
						}";
		$mform->updateElementAttr('type', 'onchange="'.$changeEvent.'"');

        $mform->addElement('selectyesno', 'autograde', get_string('automaticgrading', 'languagelesson'));
        $mform->setHelpButton('autograde', array('automaticgrading', get_string('automaticgrading', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('autograde', 0);

		$mform->addElement('text', 'defaultpoints', get_string('defaultpoints', 'languagelesson'));
		$mform->setDefault('defaultpoints', 1);
		$mform->setType('defaultpoints', PARAM_NUMBER);
		$mform->setHelpButton('defaultpoints', array('defaultpoints', get_string('defaultpoints', 'languagelesson'),
					'languagelesson'));

		$mform->addElement('selectyesno', 'penalty', get_string('usepenalty', 'languagelesson'));
		$mform->setHelpButton('penalty', array('penalty', get_string('usepenalty','languagelesson'), 'languagelesson'));
		$mform->setDefault('penalty', 0);
		
		$options = array();
		$options[LL_PENALTY_MEAN] = get_string('penaltymean', 'languagelesson');
		$options[LL_PENALTY_SET] = get_string('penaltyset', 'languagelesson');
		$mform->addElement('select', 'penaltytype', get_string('penaltytype', 'languagelesson'), $options);
		$mform->setHelpButton('penaltytype', array('penaltytype', get_string('penaltytype', 'languagelesson'), 'languagelesson'));
		// disable penalty type if Penalty is set to No
		$mform->disabledIf('penaltytype', 'penalty', 'selectedIndex', '1');
		
		$mform->addElement('text', 'penaltyvalue', get_string('penaltyvalue', 'languagelesson'));
		$mform->setDefault('penaltyvalue', 10);
		$mform->setType('penaltyvalue', PARAM_NUMBER);
		// disable the penalty value if Penalty is set to No, OR if the Penalty Type is set to Mean
		$mform->disabledIf('penaltyvalue', 'penalty', 'selectedIndex', '1');
		$mform->disabledIf('penaltyvalue', 'penaltytype', 'selectedIndex', '1');

        $mform->addElement('selectyesno', 'showongoingscore', get_string('showongoingscore', 'languagelesson'));
        $mform->setHelpButton('showongoingscore', array('showongoingscore', get_string('showongoingscore', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('showongoingscore', 0);

//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('flowcontrol', 'languagelesson'));
        
        $numbers = array();
		$numbers[0] = 'Unlimited';
        for ($i=10; $i>0; $i--) {
            $numbers[$i] = $i;
        }
        $mform->addElement('select', 'maxattempts', get_string('maximumnumberofattempts', 'languagelesson'), $numbers);
        $mform->setHelpButton('maxattempts', array('maxattempts', get_string('maximumnumberofattempts', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('maxattempts', 0);

		$mform->addElement('selectyesno', 'showoldanswer', get_string('showoldanswer', 'languagelesson'));
		$mform->setHelpButton('showoldanswer', array('showoldanswer', get_string('showoldanswer', 'languagelesson'), 'languagelesson'));
		$mform->setDefault('showoldanswer', 1);

        $mform->addElement('selectyesno', 'defaultfeedback', get_string('displaydefaultfeedback', 'languagelesson'));
		$mform->setHelpButton('defaultfeedback', array('defaultfeedback', get_string('displaydefaultfeedback', 'languagelesson'),
					'languagelesson'));
        $mform->setDefault('defaultfeedback', 0);

		$mform->addElement('text', 'defaultcorrect', get_string('defaultcorrectfeedback', 'languagelesson'));
		$mform->setHelpButton('defaultcorrect', array('defaultcorrect', get_string('defaultcorrectfeedback', 'languagelesson'),
					'languagelesson'));
		$mform->setDefault('defaultcorrect', get_string('defaultcorrectfeedbacktext', 'languagelesson'));
		$mform->disabledIf('defaultcorrect', 'defaultfeedback', 'selectedIndex', '1');

		$mform->addElement('text', 'defaultwrong', get_string('defaultwrongfeedback', 'languagelesson'));
		$mform->setHelpButton('defaultwrong', array('defaultwrong', get_string('defaultwrongfeedback', 'languagelesson'),
					'languagelesson'));
		$mform->setDefault('defaultwrong', get_string('defaultwrongfeedbacktext', 'languagelesson'));
		$mform->disabledIf('defaultwrong', 'defaultfeedback', 'selectedIndex', '1');
		
		$mform->addElement('selectyesno', 'shuffleanswers', get_string('shuffleanswers', 'languagelesson'));
		$mform->setHelpButton('shuffleanswers', array('shuffleanswers', get_string('shuffleanswers', 'languagelesson'), 'languagelesson'));
		$mform->setDefault('shuffleanswers', 1);


//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('lessonformating', 'languagelesson'));

        $mform->addElement('selectyesno', 'displayleft', get_string('displayleftmenu', 'languagelesson'));
        $mform->setHelpButton('displayleft', array('displayleft', get_string('displayleftmenu', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('displayleft', 1);

        $mform->addElement('selectyesno', 'contextcolors', get_string('displayleftmenucontextcolor', 'languagelesson'));
        $mform->setHelpButton('contextcolors', array('leftmenucontextcolors', get_string('displayleftmenucontextcolor', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('contextcolors', 1);

        $mform->addElement('selectyesno', 'progressbar', get_string('progressbar', 'languagelesson'));
        $mform->setHelpButton('progressbar', array('progressbar', get_string('progressbar', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('progressbar', 0);


//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('accesscontrol', 'languagelesson'));

        $mform->addElement('date_time_selector', 'available', get_string('available', 'languagelesson'), array('optional'=>true));
        $mform->setDefault('available', 0);

        $mform->addElement('date_time_selector', 'deadline', get_string('deadline', 'languagelesson'), array('optional'=>true));
        $mform->setDefault('deadline', 0);

//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('dependencyon', 'languagelesson'));

        $options = array(0=>get_string('none'));
        if ($lessons = get_all_instances_in_course('lesson', $COURSE)) {
            foreach($lessons as $lesson) {
                if ($lesson->id != $this->_instance){
                    $options[$lesson->id] = format_string($lesson->name, true);
                }

            }
        }
        $mform->addElement('select', 'dependency', get_string('dependencyon', 'languagelesson'), $options);
        $mform->setHelpButton('dependency', array('dependency', get_string('dependencyon', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('dependency', 0);

        $mform->addElement('text', 'timespent', get_string('timespentminutes', 'languagelesson'));
        $mform->setDefault('timespent', 0);
        $mform->setType('timespent', PARAM_INT);

        $mform->addElement('checkbox', 'completed', get_string('completed', 'languagelesson'));
        $mform->setDefault('completed', 0);

        $mform->addElement('text', 'gradebetterthan', get_string('gradebetterthan', 'languagelesson'));
        $mform->setDefault('gradebetterthan', 0);
        $mform->setType('gradebetterthan', PARAM_INT);

//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('mediafile', 'languagelesson'));

        $mform->addElement('choosecoursefile', 'mediafile', get_string('mediafile', 'languagelesson'), array('courseid'=>$COURSE->id));
        $mform->setHelpButton('mediafile', array('mediafile', get_string('mediafile', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('mediafile', '');
        $mform->setType('mediafile', PARAM_RAW);

        $mform->addElement('text', 'mediaheight', get_string('mediaheight', 'languagelesson'));
        $mform->setHelpButton('mediaheight', array('mediaheight', get_string('mediaheight', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('mediaheight', 100);
        $mform->addRule('mediaheight', null, 'required', null, 'client');
        $mform->addRule('mediaheight', null, 'numeric', null, 'client');
        $mform->setType('mediaheight', PARAM_INT);

        $mform->addElement('text', 'mediawidth', get_string('mediawidth', 'languagelesson'));
        $mform->setHelpButton('mediawidth', array('mediawidth', get_string('mediawidth', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('mediawidth', 650);
        $mform->addRule('mediawidth', null, 'required', null, 'client');
        $mform->addRule('mediawidth', null, 'numeric', null, 'client');
        $mform->setType('mediawidth', PARAM_INT);

//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('other', 'languagelesson'));

        // get the modules
        if ($mods = get_course_mods($COURSE->id)) {
            $modinstances = array();
            foreach ($mods as $mod) {

                // get the module name and then store it in a new array
                if ($module = get_coursemodule_from_instance($mod->modname, $mod->instance, $COURSE->id)) {
                    if (isset($this->_cm->id) and $this->_cm->id != $mod->id){
                        $modinstances[$mod->id] = $mod->modname.' - '.$module->name;
                    }
                }
            }
            asort($modinstances); // sort by module name
            $modinstances=array(0=>get_string('none'))+$modinstances;

            $mform->addElement('select', 'activitylink', get_string('activitylink', 'languagelesson'), $modinstances);
            $mform->setHelpButton('activitylink', array('activitylink', get_string('activitylink', 'languagelesson'), 'languagelesson'));
            $mform->setDefault('activitylink', 0);

        }

        $mform->addElement('selectyesno', 'lessondefault', get_string('lessondefault', 'languagelesson'));
        $mform->setHelpButton('lessondefault', array('lessondefault', get_string('lessondefault', 'languagelesson'), 'languagelesson'));
        $mform->setDefault('lessondefault', 0);

//-------------------------------------------------------------------------------
        $features = new stdClass;
        $features->groups = false;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    /**
     * Enforce defaults here
     *
     * @param array $default_values Form defaults
     * @return void
     **/
    function data_preprocessing(&$default_values) {
        global $module;
        if (isset($default_values['conditions'])) {
            $conditions = unserialize($default_values['conditions']);
            $default_values['timespent'] = $conditions->timespent;
            $default_values['completed'] = $conditions->completed;
            $default_values['gradebetterthan'] = $conditions->gradebetterthan;
        }
        if (isset($default_values['add']) and $defaults = get_record('languagelesson_default', 'course', $default_values['course'])) {
            foreach ($defaults as $fieldname => $default) {
                switch ($fieldname) {
                    case 'conditions':
                        $conditions = unserialize($default);
                        $default_values['timespent'] = $conditions->timespent;
                        $default_values['completed'] = $conditions->completed;
                        $default_values['gradebetterthan'] = $conditions->gradebetterthan;
                        break;
                    default:
                        $default_values[$fieldname] = $default;
                        break;
                }
            }
        }
    }

    /**
     * Enforce validation rules here
     *
     * @param object $data Post data to validate
     * @return array
     **/
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['maxtime']) and !empty($data['timed'])) {
            $errors['timedgrp'] = get_string('err_numeric', 'form');
        }

        return $errors;
    }
}
?>
