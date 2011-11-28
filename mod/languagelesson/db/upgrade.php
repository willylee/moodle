<?php  //$Id: upgrade.php 677 2011-10-12 18:38:45Z griffisd $

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
	
	// run the qtype population code--NO MATTER THE CURRENT VERSION--if the table is empty
	// This is done because on installation, the table is not populated, so it needs to
	// force being populated
	if ($result && !get_records('languagelesson_qtypes')) {
		
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


	// add an "iscurrent" binary flag to the attempts table for faster retrieval of most recent attempts
	if ($result && $oldversion < 2011092801) {

		$table = new XMLDBTable('languagelesson_attempts');
		$field = new XMLDBField('iscurrent');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'retry');
		$result = add_field($table, $field);

		$data = get_records_sql("select *
								 from {$CFG->prefix}languagelesson_attempts
								 order by userid, pageid, retry desc");

		$ID = 0;
		$RETRY = 1;

		$attempts_toupdate = array();
		foreach ($data as $datum) {
			if (!array_key_exists($datum->userid, $attempts_toupdate)) {
				$attempts_toupdate[$datum->userid] = array();
			}
			if (!array_key_exists($datum->pageid, $attempts_toupdate[$datum->userid])) {
				$attempts_toupdate[$datum->userid][$datum->pageid] = array();
				$attempts_toupdate[$datum->userid][$datum->pageid][$ID] = $datum->id;
				$attempts_toupdate[$datum->userid][$datum->pageid][$RETRY] = $datum->retry;
			}
		}

		foreach ($attempts_toupdate as $user => $pagearr) {
			foreach ($pagearr as $page => $adata) {
				$record = new stdClass;
				$record->id = $adata[$ID];
				$record->iscurrent = 1;

				if (! update_record('languagelesson_attempts', $record)) {
					error("Failed to flag highest-retry-value record as current record! user $user, page $page, retry
							".$adata[$RETRY]);
				}
			}
		}
	}


	// add the "ordering" field to pages table for easier page-sorting
	if ($result && $oldversion < 2011100501) {
		
		$table = new XMLDBTable('languagelesson_pages');
		$field = new XMLDBField('ordering');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'nextpageid');
		$result = add_field($table, $field);



		// now mark all pages with their ordering values

		// start by pulling the list of languagelessons, so we can order them one at a time
		$all_lls = get_records('languagelesson');

		foreach($all_lls as $llid => $ll) {
			// if there are no pages, skip this languagelesson
			if (! count_records('languagelesson_pages', 'lessonid', $llid)) { continue; }

			// pull the id of the first page as the next page to look at
			$nextpageid = get_field('languagelesson_pages', 'id', 'lessonid', $llid, 'prevpageid', '0');
			$ordering = 0;
			// now, while there is a next page to look at, update that next page's ordering value
			do {
				$upage = new stdClass;
				$upage->id = $nextpageid;
				$upage->ordering = $ordering++;

				if (! update_record('languagelesson_pages', $upage)) {
					error("Failed to assign ordering in lesson $llid, failed on page $nextpageid");
				}

				$nextpageid = get_field('languagelesson_pages', 'nextpageid', 'lessonid', $llid, 'id', $nextpageid);
			} while ($nextpageid);
		}
	}

	
	// add the "viewed" flag to manattempts table for more userful Grader page interface
	if ($result && $oldversion < 2011102601) {
		$table = new XMLDBTable('languagelesson_manattempts');
		$field = new XMLDBField('viewed');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'attemptid');

		$result = add_field($table, $field);

		if ($result) {
			$result = execute_sql("UPDATE {$CFG->prefix}languagelesson_manattempts SET viewed = 1 WHERE graded = 1");
		}
	}


	// add the "defaultpoints" field to languagelesson and languagelesson_default to enable setting default number of points per question in an instance
	if ($result && $oldversion < 2011112301) {
		$table = new XMLDBTable('languagelesson');
		$table2 = new XMLDBTable('languagelesson_default');
		$field = new XMLDBField('defaultpoints');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 1, 'conditions');

		$result = add_field($table, $field);
		if ($result) { $result = add_field($table2, $field); }
	}


	// add the languagelesson_branches table
	if ($result && $oldversion < 2011112801) {
		$table = new XMLDBTable('languagelesson_branches');

		//fields
		$id = new XMLDBField('id');
		$id->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
		$table->addField($id);

		$lessonid = new XMLDBField('lessonid');
		$lessonid->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'id');
		$table->addField($lessonid);

		$parentid = new XMLDBField('parentid');
		$parentid->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'lessonid');
		$table->addField($parentid);

		$firstpage = new XMLDBField('firstpage');
		$firstpage->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'parentid');
		$table->addField($firstpage);

		$title = new XMLDBField('title');
		$title->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, '', 'firstpage');
		$table->addField($title);

		$timecreated = new XMLDBField('timecreated');
		$timecreated->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'title');
		$table->addField($timecreated);

		$timemodified = new XMLDBField('timemodified');
		$timemodified->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'timecreated');
		$table->addField($timemodified);

		//keys
		$id = new XMLDBKey('id');
		$id->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
		$table->addKey($id);
		
		$lessonid = new XMLDBKey('lessonid');
		$lessonid->setAttributes(XMLDB_KEY_FOREIGN, array('lessonid'), "{$CFG->prefix}languagelesson", 'id');
		$table->addKey($lessonid);

		$parentid = new XMLDBKey('parentid');
		$parentid->setAttributes(XMLDB_KEY_FOREIGN, array('parentid'), "{$CFG->prefix}languagelesson_pages", 'id');
		$table->addKey($parentid);

		$firstpage = new XMLDBKey('firstpage');
		$firstpage->setAttributes(XMLDB_KEY_FOREIGN, array('firstpage'), "{$CFG->prefix}languagelesson_pages", 'id');
		$table->addKey($firstpage);

		$result = create_table($table);

	}


	// add the branchid field to languagelesson_pages and key it to languagelesson_branches
	if ($result && $oldversion < 2011112802) {
		$table = new XMLDBTable('languagelesson_pages');
		$field = new XMLDBField('branchid');
		$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'lessonid');
		$key = new XMLDBKey('branchid');
		$key->setAttributes(XMLDB_KEY_FOREIGN, array('branchid'), 'languagelesson_branches', 'id');

		$result = add_field($table, $field);
		if ($result) { $result = add_key($table, $key); }
	}



	return $result;
}

?>
