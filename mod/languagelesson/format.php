<?php  // $Id: format.php 677 2011-10-12 18:38:45Z griffisd $ 
/**
 * format.php  - Default format class for file imports/exports. Doesn't do 
 * everything on it's own -- it needs to be extended.
 *
 * @version $Id: format.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

// Included by import.php

class qformat_default {

    var $displayerrors = true;
    var $category = NULL;
    var $questionids = array();
    var $qtypeconvert = array(NUMERICAL   => LL_NUMERICAL,
                              MULTICHOICE => LL_MULTICHOICE,
                              TRUEFALSE   => LL_TRUEFALSE,
                              SHORTANSWER => LL_SHORTANSWER,
                              MATCH       => LL_MATCHING,
                              //added in
                              ESSAY		  => LL_ESSAY
                              );
	// check if files are going to be autouploaded by this baby
    var $autouploading = false;
	// include a lessonid field
	var $lessonid = 0;


	// Importing functions

	// Does any pre-processing that may be desired
    function importpreprocess() {
        return true;
    }



    function importprocess($filename, $lesson, $prevpageid) {
    	global $CFG;

		$this->lessonid = $lesson->id;

        $timenow = time();

        if (! $lines = $this->readdata($filename)) {
            notify("File could not be read, or was empty");
            return false;
        }

        if (! $questions = $this->readquestions($lines)) {   // Extract all the questions
            notify("There are no questions in this file!");
            return false;
        }

        notify(get_string('importcount', 'languagelesson', sizeof($questions)));

        $count = 0;
		
		
		// initialize the BranchTracker instance for tracking what branch table we're
		// populating and maintaining data about it
		$branchTracker = new BranchTracker();
		// initialize the array for holding filename paths to upload--this is
		// populated as question pages are added, then fed into a hidden form at
		// the end of the import process which calls the auto_upload.php script
		$autouploadfnames = array();
		// initialize the path to the upload destination directory; all auto-uploaded
		// files are stored within a folder in the course's data root called
		// LanguageLesson_Prompt_Files; the files for a particular lesson are then stored
		// within a unique subfolder named as the name of the lesson (with spaces
		// converted to underscores) followed by a timestamp in a format like
		// 2010-12-15_11.17.38
		$upload_destination = addslashes("$lesson->course/LanguageLesson_Prompt_Files/"
			. clean_filename($lesson->name) . '_' . date('Y-m-d_H.i.s'));
		// initialize the base dir for setting href values to in autoupload text
		// replacing; this is just setting up the link to use Moodle's file.php
		// protocol
		$autoupload_link_dir = addslashes("$CFG->wwwroot/file.php/$upload_destination");
		// init the flag for if the next page to insert will be the first page of a branch
		// or not
		$expectsFirstpage = false;
		
        foreach ($questions as $question) {   // Process and store each question
            switch ($question->qtype) {
                // the good ones
                case LL_SHORTANSWER :
                //case LL_NUMERICAL :
                case LL_TRUEFALSE :
                case LL_MULTICHOICE :
                //case LL_MATCHING :
				case LL_CLOZE :
              	case LL_ESSAY :
              	case LL_DESCRIPTION :
              	case LL_AUDIO :
              	case LL_VIDEO :
				// structural
              	case LL_BRANCHTABLE :
              	case LL_ENDOFBRANCH :
              	case LL_CLUSTER :
              	case LL_ENDOFCLUSTER :
                    $count++;
                    
                    if ($this->get_autoupload_string_bounds($question->questiontext)) {
                    	$autouploadfnames = $this->append_single_or_list(
                    							$autouploadfnames,
                    							$this->pull_autoupload_fname($question->questiontext));
                    	$question->questiontext = $this->autoupload_replacetext($question->questiontext, $autoupload_link_dir);
                    }

                    echo "<hr><p><b>$count</b>. ".stripslashes($question->questiontext)."</p>";

					// set the known, unconditional values for the new page
                    $newpage = new stdClass;
                    $newpage->lessonid = $lesson->id;
                  	$newpage->qtype = $question->qtype;
                    $newpage->timecreated = $timenow;
					$newpage->ordering = $count;

					// set the qoption field, dependent on question type
					$newpage->qoption = $this->handleQOption($question);
					// set the page's title and contents, also dependent on question type and submitted data
                    list($newpage->title, $newpage->contents) = $this->handleTitleContents($question);
					// if we're currently in a branch, set this page's branchid
					if ($branchTracker->curBranchID) {
						$newpage->branchid = $branchTracker->curBranchID;
					}

					// TEMP HACK :: if it's a DESCRIPTION, mark it as a MULTICHOICE until description works
					if ($question->qtype == LL_DESCRIPTION) { $newpage->qtype = LL_MULTICHOICE; }

					// save the page record
					$newpageid = $this->savePage($newpage, $prevpageid);

					// if we flagged to expect a first page, that means the page just inserted is the first page of the
					// current branch, so update the branch's firstpage value
					if ($expectsFirstpage) {
						set_field('languagelesson_branches', 'firstpage', $newpageid, 'id', $branchTracker->curBranchID);
						$expectsFirstpage = false;
					}

					// if we just hit the end of the current branch, move the branchTracker to the next branch
					if ($question->qtype == LL_ENDOFBRANCH) {
						$branchTracker->nextBranch();
					}

					// if we just inserted a BRANCHTABLE or a non-final ENDOFBRANCH, flag that we need to set
					// the next branch's firstpage value to the next page created
					if ($question->qtype == LL_BRANCHTABLE
							|| ($question->qtype == LL_ENDOFBRANCH && ! $branchTracker->isComplete())) {
						$expectsFirstpage = true;
					}

					// if we just inserted the final ENDOFBRANCH for the branch table we've been working on,
					// go back and correct nextpageid pointers and pop to the next branch table
					if ($question->qtype == LL_ENDOFBRANCH && $branchTracker->isComplete()) {
						$this->correctEOBPointers($branchTracker->current);
						$branchTracker->pop();
					}

                    // update $prevpageid to point to this pageand put the new page ID in $question
					// for save_question_option()
                    $prevpageid = $newpageid;
                    $question->id = $newpageid;
                    
                    $this->questionids[] = $question->id;
                    
					// if the page just inserted was a branch table, update the currentbranchtable
					// pageID pointer to reflect the most recent branch table ID so we can set proper
					// jumpto values for ENDOFBRANCH pages answer records
                    if ($question->qtype == LL_BRANCHTABLE) {
						$newbranches = $this->createBranchRecords($question, $timenow);

						$btData = new stdClass;
						$btData->id = $newpageid;
						$btData->curBranch = 0;
						$btData->branches = $newbranches;

						$branchTracker->push($btData);
                    }

                    // Now to save all the answers and type-specific options

                    $question->lessonid = $lesson->id; // needed for foreign key
                    
					// save question answer data
					$result = languagelesson_save_question_options($question);

                    if (!empty($result->error)) {
                        notify($result->error);
                        return false;
                    }

                    if (!empty($result->notice)) {
                        notify($result->notice);
                        return true;
                    }
                    break;
            // the Bad ones
                default :
                    notify(get_string('unsupportedqtype', 'languagelesson', $question->qtype));
            } // end switch ($question->qtype)
 
        } // end foreach ($questions as $question)
        
        
		// and now take care of the last little bit of business, that is, actually
		// uploading the autoupload files, by building and submitting a form for them
      	if (count($autouploadfnames) > 0) {
      		$this->autouploading = true; //store this so we can give the below form one
      									 //more input in import.php
      		$this->build_submit_autoupload_form($autouploadfnames, $upload_destination);
      	}
        
        
        return true;
    }


	private function correctEOBPointers($branchData) {
		$branchids_str = implode(',', $branchData->branches);
		$eobs = get_records_select('languagelesson_pages', "qtype=".LL_ENDOFBRANCH." and branchid in ($branchids_str)");
		// get rid of the final EOB, as its pointers are fine
		array_pop($eobs);
		// now loop through the EOB records and update them to point nextpageid at the parent branch table
		foreach ($eobs as $eobid => $eob) {
			$ueob = new stdClass;
			$ueob->id = $eobid;
			$ueob->nextpageid = $branchData->id;
			if (! update_record('languagelesson_pages', $ueob)) {
				error('Importing: could not update end of branch page nextpage pointer');
			}
		}
	}




	private function createBranchRecords($question, $timenow) {
		$ordering = 0;
		$branchids = array();
		foreach ($question->branchnames as $title) {
			$branch = new stdClass;
			$branch->lessonid = $this->lessonid;
			$branch->parentid = $question->id;
			$branch->ordering = ++$ordering;
			$branch->title = $title;
			$branch->timecreated = $timenow; 

			if (! $newbranchid = insert_record('languagelesson_branches', $branch)) {
				error('Importing: could not insert new branch record');
			}

			$branchids[] = $newbranchid;
		}
		// return the IDs of the created branches
		return $branchids;
	}




	private function handleQOption($question) {
		$qoption = 0;
		switch ($question->qtype) {
			case LL_SHORTANSWER :
				if (isset($question->usecase)) {
					$qoption = $question->usecase;
				}
				break;
			case LL_MULTICHOICE :
				if (isset($question->single)) {
					$qoption = !$question->single;
				}
				break;
			// case LL_BRANCHTABLE :
			//     stuff
			default: break;
		}
		return $qoption;
	}





	private function handleTitleContents($question) {
		// init title and contents both to empty
		$title = '';
		$contents = '';

		if ($question->name != $question->questiontext) {
			$title = $question->name;
			$contents = $question->questiontext;
		} else {
			// sub-switch for title/contents labeling, to match up with
			// Moodle default DB behavior for structural pages
			switch($question->qtype) {
				case LL_ENDOFBRANCH :
					$title = 'ENDOFBRANCH';
					break;
				case LL_CLUSTER :
					$title = 'Cluster';
					$contents = 'Cluster';
					break;
				case LL_ENDOFCLUSTER :
					$title = 'ENDOFCLUSTER';
					break;
				default:
					$title = "Page $count";
					$contents = $question->questiontext;
					break;
			}
		}

		return array($title, $contents);
	}




	private function savePage($newpage, $prevpageid) {

		// set up page links
		if ($prevpageid) {
			// this is not the first page 
			if (!$prevpage = get_record("languagelesson_pages", "id", $prevpageid)) {
				error ("Format: Page $prevpageid not found");
			}
			$newpage->prevpageid = $prevpageid;
			$newpage->nextpageid = $prevpage->nextpageid;
			// insert the page and reset $prevpageid
			if (!$newpageid = insert_record("languagelesson_pages", $newpage)) {
				error("Format: Could not insert new page!");
			}
			// update the linked list
			if (!set_field("languagelesson_pages", "nextpageid", $newpageid, "id", $prevpageid)) {
				error("Format: unable to update link");
			}

		} else {
			// new page is the first page
			// get the existing (first) page (if any)
			if (!$prevpage = get_record_select("languagelesson_pages", "lessonid = $this->lessonid AND prevpageid = 0")) {
				// there are no existing pages
				$newpage->prevpageid = 0; // this is a first page
				$newpage->nextpageid = 0; // this is the only page
				$newpageid = insert_record("languagelesson_pages", $newpage);
				if (!$newpageid) {
					error("Insert page: new first page not inserted");
				}
			} else {
				// there are existing pages put this at the start
				$newpage->prevpageid = 0; // this is a first page
				$newpage->nextpageid = $prevpage->id;
				$newpageid = insert_record("languagelesson_pages", $newpage);
				if (!$newpageid) {
					error("Insert page: first page not inserted");
				}
				// update the linked list
				if (!set_field("languagelesson_pages", "prevpageid", $newpageid, "id", $prevpage->id)) {
					error("Insert page: unable to update link");
				}
			}
		}

		return $newpageid;
	}



    function readdata($filename) {
    /// Returns complete file with an array, one item per line

        if (is_readable($filename)) {
            $filearray = file($filename);

            /// Check for Macintosh OS line returns (ie file on one line), and fix
            if (ereg("\r", $filearray[0]) AND !ereg("\n", $filearray[0])) {
                return explode("\r", $filearray[0]);
            } else {
                return $filearray;
            }
        }
        return false;
    }

    function readquestions($lines) {
    /// Parses an array of lines into an array of questions, 
    /// where each item is a question object as defined by 
    /// readquestion().   Questions are defined as anything 
    /// between blank lines.
     
        $questions = array();
        $currentquestion = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if (!empty($currentquestion)) {
                    if ($question = $this->readquestion($currentquestion)) {
                        $questions[] = $question;
                    }
                    $currentquestion = array();
                }
            } else {
                $currentquestion[] = $line;
            }
        }

        if (!empty($currentquestion)) {  // There may be a final question
            if ($question = $this->readquestion($currentquestion)) {
                $questions[] = $question;
            }
        }

        return $questions;
    }


    function readquestion($lines) {
    /// Given an array of lines known to define a question in 
    /// this format, this function converts it into a question 
    /// object suitable for processing and insertion into Moodle.
        echo "<p>This question format has not yet been defined!</p>";
        return NULL;
    }


    function defaultquestion() {
    // returns an "empty" question
    // Somewhere to specify question parameters that are not handled
    // by import but are required db fields.
    // This should not be overridden. 
        global $CFG;

        $question = new stdClass();
        $question->shuffleanswers = $CFG->quiz_shuffleanswers;
        $question->defaultgrade = 1;
        $question->image = "";
        $question->usecase = 0;
        $question->multiplier = array();
        $question->generalfeedback = '';
        $question->correctfeedback = '';
        $question->partiallycorrectfeedback = '';
        $question->incorrectfeedback = '';
        $question->answernumbering = 'abc';
        $question->penalty = 0.1;
        $question->length = 1;
        $question->qoption = 0;
        $question->layout = 1;
        
        return $question;
    }

    function importpostprocess() {
    /// Does any post-processing that may be desired
    /// Argument is a simple array of question ids that 
    /// have just been added.

		// update the languagelesson's ordering values
		languagelesson_update_ordering($this->lessonid);

		// update the calculated max score for this lesson
		languagelesson_recalculate_maxgrade($this->lessonid);
        

        return true;
    }
    
    
    
    
  ///// added in autoupload managing functions /////
    
    function get_autoupload_string_bounds($questiontext) {
    /*
     * Returns an array consisting of ( <low bound> , <high bound> ),
     * marking the indices between which the FIRST autoupload string
     * is found in the input $questiontext.
     *
     * Returns false if no autoupload string is found.
     */
    	
      /// by the time it's gotten here, markup declarations
      /// will have been processed and removed, so we don't
      /// have to worry about them
    	if ($startpos = strpos($questiontext, '[')) {
    		
    		$afterslice = substr($questiontext, $startpos);
    		if ($endpos = strpos($afterslice, ']')) {
    			
    			$betweenslice = substr($afterslice, 0, $endpos);
    			$chunks = explode('|', $betweenslice);
    			if (count($chunks) == 2) {
    				return array($startpos, $endpos);
    			} else {
    				
    			  /// it's entirely possible that the question text itself
    			  /// has a square-bracketed expression in it, but an
    			  /// auto-upload included later, so recurse on the text
    			  /// after this first square-bracketed substring to see
    			  /// if the rest of it contains an autoupload string
    				$outsideslice = substr($afterslice, $endpos);
    				return $this->get_autoupload_string_bounds($outsideslice);
    			}
    			
    		} else {
    			return false;
    		}
    		
    	} else {
    		return false;
    	}
    	
    }
    
    
    
    
    
    
    function pull_autoupload_fname($questiontext) {
    /*
     * Given a questiontext, returns all filename paths for autoupload
     * files contained in the text.
     *
     * Returns either a single string or an array of strings.
     */
    	    	
    	if (! $bounds = $this->get_autoupload_string_bounds($questiontext)) {
    		return false;
    	}
    	
    	$autouploadstr = substr($questiontext, $bounds[0]+1, $bounds[1]);
    	
    	$chunks = explode('|', $autouploadstr);
    	
    	$fname = trim($chunks[0]);
    	
    	$restoftext = substr($questiontext, $bounds[0] + $bounds[1]);
    	
    	if ($nextfname = $this->pull_autoupload_fname($restoftext)) {
    		return $this->append_single_or_list(array($fname), $nextfname);
    	} else {
    		return $fname;
    	}
    	
    }
    
    
    
    
    function append_single_or_list($inlist, $appenditem) {
    /*
     * Extends $inlist with the contents of $appenditem, and returns
     * the modified $inlist.
     *
     * If $appenditem is a single item (i.e., not an array), it is
     * appended directly to $inlist.  If $appenditem is an array,
     * each of its values is successively appended to $inlist.
     *
     * Please note that this does NOT keep track of any key mapping
     * that was done in the $appenditem array--its contents will simply
     * be mapped to consecutive indices in $inlist.
     */
    	
    	if (is_array($appenditem)) {
    		foreach ($appenditem as $subitem) {
    			$inlist[] = $subitem;
    		}
    	} else {
    		$inlist[] = $appenditem;
    	}
    	
    	return $inlist;
    	
    }
    
    
    
    
    function autoupload_replacetext($questiontext, $hrefbasedir, $bounds=null) {
    /*
     * Reformats all autoupload strings in input $questiontext to HTML links,
     * with files assumed to be contained in the $hrefbasedir.
     */
    	
      /// if we didn't recurse, snag the first autoupload string bounds
    	if ($bounds === null) {
    		$bounds = $this->get_autoupload_string_bounds($questiontext);
    	}
    	
      /// store the chunks of the string on either side of the autoupload string
    	$left = substr($questiontext, 0, $bounds[0]);
    	$right = substr($questiontext, $bounds[0] + $bounds[1] + 1);
    	
      /// pull the autoupload string and blow it into component parts
    	$autouploadstr = substr($questiontext, $bounds[0]+1, $bounds[1]-1);
    	$chunks = explode('|', $autouploadstr);
    	
    	$fname = trim($chunks[0]);
    	$fname = clean_filename($fname);
    	
      /// build the full custom link text for this particular autouploaded file
    	$linktext = "<a href=\"$hrefbasedir/$fname\">" .
    				trim($chunks[1]) . "</a>";
    	
      /// compile the complete updated questiontext
    	$questiontext = $left . $linktext . $right;
    	
      /// if there are more autoupload strings, replace them too
    	if ($bounds = $this->get_autoupload_string_bounds($questiontext)) {
    		return $this->autoupload_replacetext($questiontext, $hrefbasedir, $bounds);
    	}
    	
    	return $questiontext;
    }
    
    
    
    
    function build_submit_autoupload_form($autouploadfnames, $upload_destination) {
    	
    	echo "<hr />";
    	echo "<h3>File Uploads</h3>";
    	
    	$form = '<form enctype="multipart/form-data" action="import_format/autoupload_script.php"
    			 method="post" name="autouploadform">';
    	
    	$form .= '<input type="hidden" name="destination" value="' . $upload_destination . '" />';
    	$form .= '<input type="hidden" name="filecount" value="' . count($autouploadfnames) . '" />';
    	
      /// initialize the string that will be storing a comma-separated representation
      /// of the filenames contained in $autouploadfnames, so we can check them against
      /// the files that were actually uploaded
    	$autouploadfnamesstring = '(';
    	for ($i=0; $i<count($autouploadfnames);$i++) {
    		
    		$form .= '<label target="file'.$i.'">Please locate the file "'
    				  . $autouploadfnames[$i] . '" for uploading:</label>';
    		$form .= '<input type="file" name="file'.$i.'" id="file'.$i.'" /> <br />';
    		
    	  /// continue populating the $autouploadfnamesstring
    		$autouploadfnamesstring .= $autouploadfnames[$i];
    		if ($i < (count($autouploadfnames)-1)) { $autouploadfnamesstring .= ','; }
    	}
    	
      /// complete and set as an input the full string represenation of the
      /// $autouploadfnames array
    	$autouploadfnamesstring .= ')';
    	$form .= '<input type="hidden" name="expected_filenames" value="' . $autouploadfnamesstring . '" />';
    	
      /// don't need to print a submit button, 'cause the "Continue" button Moodle prints
      /// out functions as a submit for this form, since we never close this one
    	//$form .= '<input type="submit" />';
    	
    	echo $form;
    	
    	/*echo '<script type="text/javascript">
    			//<![CDATA[
    				document.autouploadform.submit();
    			//]]>
    		  </script>';*/
    	
    }
  	
  ///// end autoupload managing functions /////

}




class BranchTracker {
	
	var $current = null;
	var $stack = array();
	var $curBranchID = 0;

	function push($branch) {
		// if we have been working with a BT object already, store it in the stack
		if (! is_null($this->current)) {
			$this->stack[] = $this->current;
		}
		$this->current = $branch;
		$this->curBranchID = (count($this->current->branches) ? $this->current->branches[0] : 0);
	}

	function pop() {
		$this->current = array_pop($this->stack);
		if ($this->current) {
			$this->curBranchID = $this->current->branches[$this->current->curBranch];
		}
	}

	function nextBranch() {
		++$this->current->curBranch;
		if ($this->current && ! $this->isComplete()) {
			$this->curBranchID = $this->current->branches[$this->current->curBranch];
		} else { $this->curBranchID = null; }
	}

	function isComplete() {
		if ($this->current) {
			return ($this->current->curBranch == (count($this->current->branches)));
		} else { return false; }
	}

}



?>
