<?php  //$Id$

// This file keeps track of upgrades to 
// the languagelesson module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_languagelesson_upgrade($oldversion=0) {

	global $CFG, $THEME, $db;

	$result = true;
	
	if ($result && $oldversion < 2011071902) {
		
	/// Adding qtypes table
		$table = new XMLDBTable('languagelesson_qtypes');
		
		$id = new XMLDBField('id');
		$id->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
		$table->addField($id);
		
		$textid = new XMLDBField('textid');
		$textid->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'LL_NEW_VAR', 'id');
		$table->addField($textid);
		
		$name = new XMLDBField('name');
		$name->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'New Type', 'textid');
		$table->addField($name);
		
		$enabled = new XMLDBField('enabled');
		$enabled->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'name');
		$table->addField($enabled);
		
		
		$pkey = new XMLDBKey('primary');
		$pkey->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
		$table->addKey($pkey);
		
		
		$result = $result && create_table($table);
		
	}
	
	if ($result && $oldversion < 2011071902 && !get_records('languagelesson_qtypes')) {
		
    /// new values for question types
        $value_map = array( 1 => 4,   // LESSON_SHORTANSWER
                            2 => 3,   // LESSON_TRUEFALSE
                            3 => 2,   // LESSON_MULTICHOICE
                            4 => 6,   // LESSON_MATCHING
                            10 => 8,  // LESSON_ESSAY
                            11 => 9,  // LESSON_AUDIO
                            12 => 10  // LESSON_VIDEO
                          );
        
        $updatepages = array();
        foreach ($value_map as $oldtype => $newtype) {
            if ($thesepages = get_records('languagelesson_pages', 'qtype', $oldtype)) {
                $updatepages[$oldtype] = array( 'newtype' => $newtype,
                                                'pages'   => $thesepages);
            }
        }
        
        foreach ($updatepages as $oldtype => $arr) {
            foreach($arr['pages'] as $page) {
                
                $page->qtype = $arr['newtype'];
				// we don't need to bother with any of the text of the page, and since it can potentially include single quotes, unset
				// it so that it doesn't screw with the update_record function
				unset($page->title);
				unset($page->contents);
                if (! update_record('languagelesson_pages', $page)) {
                    error('Failed to update the qtype value of the page record!');
                    $result = false;
                }
            }
        }
        
        
	/// Populating the qtypes table
        $descrip = new stdClass();
        $descrip->textid = get_string('descriptiontextid', 'languagelesson');
        $descrip->name = get_string('descriptionname', 'languagelesson');
        
        $multichoice = new stdClass();
        $multichoice->textid = get_string('multichoicetextid', 'languagelesson');
        $multichoice->name = get_string('multichoicename', 'languagelesson');
        
        $truefalse = new stdClass();
        $truefalse->textid = get_string('truefalsetextid', 'languagelesson');
        $truefalse->name = get_string('truefalsename', 'languagelesson');
        
        $shortanswer = new stdClass();
        $shortanswer->textid = get_string('shortanswertextid', 'languagelesson');
        $shortanswer->name = get_string('shortanswername', 'languagelesson');
        
        $cloze = new stdClass();
        $cloze->textid = get_string('clozetextid', 'languagelesson');
        $cloze->name = get_string('clozename', 'languagelesson');
        
        $matching = new stdClass();
        $matching->textid = get_string('matchingtextid', 'languagelesson');
        $matching->name = get_string('matchingname', 'languagelesson');
        
        $numerical = new stdClass();
        $numerical->textid = get_string('numericaltextid', 'languagelesson');
        $numerical->name = get_string('numericalname', 'languagelesson');
		
        $essay = new stdClass();
        $essay->textid = get_string('essaytextid', 'languagelesson');
        $essay->name = get_string('essayname', 'languagelesson');
        
        $audio = new stdClass();
        $audio->textid = get_string('audiotextid', 'languagelesson');
        $audio->name = get_string('audioname', 'languagelesson');
        
        $video = new stdClass();
        $video->textid = get_string('videotextid', 'languagelesson');
        $video->name = get_string('videoname', 'languagelesson');
        
        $types = array ($descrip,
                        $multichoice,
                        $truefalse,
                        $shortanswer,
                        $cloze,
                        $matching,
                        $numerical,
                        $essay,
                        $audio,
                        $video);
        
        foreach ($types as $type) {
            if (! $typeid = insert_record('languagelesson_qtypes', $type)) {
                error('Failed to insert the new question type!');
                $result = false;
				break;
            }
        }
        
	}

	/// add the feedbackcorrect and feedbackwrong default feedback fields to the languagelesson table
	if ($result && $oldversion < 2011072101) {
		$table = new XMLDBTable('languagelesson');

		$feedback = new XMLDBField('feedback');
		$feedback->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 1, 'shuffleanswers');
		$result = rename_field($table, $feedback, 'defaultfeedback');
		if (!$result) { return false; }

		$defaultcorrect = new XMLDBField('defaultcorrect');
		$defaultcorrect->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'defaultfeedback');
		$result = add_field($table, $defaultcorrect);
		if (!$result) { return false; }

		$defaultwrong = new XMLDBField('defaultwrong');
		$defaultwrong->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'defaultcorrect');
		$result = add_field($table, $defaultwrong);
		
	}



	/// add the timeseen field to manattempts
	if ($result && $oldversion < 2011081101) {
		$table = new XMLDBTable('languagelesson_manattempts');

		$timeseen = new XMLDBField('timeseen');
		$timeseen->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'resubmit');
		$result = add_field($table, $timeseen);
	}



	/// do some maintenance:
	///   - set qtype fields in _pages and _manattempts to foreign keys referencing _qtypes
	///   - set timeseen field in _feedback to not null
	///   - rename feedback field in _default to defaultfeedback
	///   - add shuffleanswers field to _default
	///   - add defaultcorrect and defaultwrong fields to _default
	///   - kill retry field in _seenbranches (it no longer means anything)
	if ($result && $oldversion < 2011081601) {
		
		// take care of _pages and _manattempts foreign keying
		$table = new XMLDBTable('languagelesson_pages');
		$key = new XMLDBKey('qtype');
		$key->setAttributes(XMLDB_KEY_FOREIGN, 'qtype', 'languagelesson_qtypes', 'id');
		$result = add_key($table, $key);
		if (!$result) { return false; }

		$table = new XMLDBTable('languagelesson_manattempts');
		$key = new XMLDBKey('type');
		$key->setAttributes(XMLDB_KEY_FOREIGN, 'type', 'languagelesson_qtypes', 'id');
		$result = add_key($table, $key);
		if (!$result) { return false; }


		// set not null on _feedback's timeseen
		$table = new XMLDBTable('languagelesson_feedback');
		$timeseen = new XMLDBField('timeseen');
		$timeseen->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'text');
		$result = change_field_notnull($table, $timeseen);
		if (!$result) { return false; }


		// kill retry in _seenbranches
		$table = new XMLDBTable('languagelesson_seenbranches');
		$retry = new XMLDBField('retry');
		$result = drop_field($table, $retry);
		if (!$result) { return false; }


		// handle _default
		$table = new XMLDBTable('languagelesson_default');
		
		$field = new XMLDBField('shuffleanswers');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 1, 'autograde');
		$result = add_field($table, $field);
		if (!$result) { return false; }

		$field = new XMLDBField('feedback');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 1, 'shuffleanswers');
		$result = rename_field($table, $field, 'defaultfeedback');
		if (!$result) { return false; }
		
		$field = new XMLDBField('defaultcorrect');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'defaultfeedback');
		$result = add_field($table, $field);
		if (!$result) { return false; }

		$field = new XMLDBField('defaultwrong');
		$field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'defaultcorrect');
		$result = add_field($table, $field);

	}
	

	return $result;
}

?>
