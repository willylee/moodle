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
  /// variable to check if files are going to be autouploaded by this baby
    var $autouploading = false;

/// Importing functions

    function importpreprocess() {
    /// Does any pre-processing that may be desired

        return true;
    }

    function importprocess($filename, $lesson, $pageid) {
    	global $CFG;
    /// Processes a given file.  There's probably little need to change this
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
		
		
	  /// initialize the variable for tracking pageID of the branch table being
	  /// populated; default value is just a nextpage pointer
		$currentbranchtable = LL_NEXTPAGE;
	  /// initialize the stack for holding branch tables while we wait for their
	  /// branches to get pageID values (so we can feed the branches into the
	  /// answers table); unlike the other branch table stack, this can have as
	  /// many items as necessary
		$branchtablestack = array();
	  /// initialize the array for holding filename paths to upload--this is
	  /// populated as question pages are added, then fed into a hidden form at
	  /// the end of the import process which calls the auto_upload.php script
		$autouploadfnames = array();
	  /// initialize the path to the upload destination directory; all auto-uploaded
	  /// files are stored within a folder in the course's data root called
	  /// LanguageLesson_Prompt_Files; the files for a particular lesson are then stored
	  /// within a unique subfolder named as the name of the lesson (with spaces
	  /// converted to underscores) followed by a timestamp in a format like
	  /// 2010-12-15_11.17.38
		/*$upload_destination = addslashes("$lesson->course/LanguageLesson_Prompt_Files/"
			.preg_replace('/ /', '_', $lesson->name) . '_' . date('Y-m-d_H.i.s'));*/
		$upload_destination = addslashes("$lesson->course/LanguageLesson_Prompt_Files/"
			. clean_filename($lesson->name) . '_' . date('Y-m-d_H.i.s'));
	  /// initialize the base dir for setting href values to in autoupload text
	  /// replacing; this is just setting up the link to use Moodle's file.php
	  /// protocol
		$autoupload_link_dir = addslashes("$CFG->wwwroot/file.php/$upload_destination");
		
        foreach ($questions as $question) {   // Process and store each question
            switch ($question->qtype) {
                // the good ones
                case LL_SHORTANSWER :
                //case LL_NUMERICAL :
                case LL_TRUEFALSE :
                case LL_MULTICHOICE :
                case LL_MATCHING :
              /// added - questions
				case LL_CLOZE :
              	case LL_ESSAY :
              	case LL_DESCRIPTION :
              	case LL_AUDIO :
              	case LL_VIDEO :
              /// added - structural
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
                    $newpage = new stdClass;
                    $newpage->lessonid = $lesson->id;
                    
                    //$newpage->qtype = $this->qtypeconvert[$question->qtype];
                  	$newpage->qtype = $question->qtype;
                    
                  /// handle special data
                    switch ($question->qtype) {
                        case LL_SHORTANSWER :
                            if (isset($question->usecase)) {
                                $newpage->qoption = $question->usecase;
                            }
                            break;
                        case LL_MULTICHOICE :
                            if (isset($question->single)) {
                                $newpage->qoption = !$question->single;
                            }
                            break;
                        case LL_DESCRIPTION :
                       	  /// use the lesson's built-in hack for description questions by setting
                  		  /// the page as a multichoice that has no answers
                        	$newpage->qtype = LL_MULTICHOICE;
                        	break;
                      /// set structural pages to invisible
                        case LL_BRANCHTABLE :
                        case LL_ENDOFBRANCH :
                        case LL_CLUSTER :
                        case LL_ENDOFCLUSTER :
                        	$newpage->layout = 1;
                        	
                        	if ($question->qtype == LL_ENDOFBRANCH) {
							  /// if it's an ENDOFBRANCH, store the pageID of the branch table
							  /// that owns said branch
								$question->branchparent = $currentbranchtable;
							}	
                        	
                        	break;
                    }
                    $newpage->timecreated = $timenow;
                    if ($question->name != $question->questiontext) {
                        $newpage->title = $question->name;
                        $newpage->contents = $question->questiontext;
                    } else {
                      /// sub-switch for title/contents labeling, to match up with
                      /// Moodle default DB behavior for structural pages
                    	switch($question->qtype) {
							case LL_ENDOFBRANCH :
								$newpage->title = 'End of branch';
								$newpage->contents = 'End of branch';
							  /// if it's an ENDOFBRANCH, store the pageID of the branch table
							  /// that owns said branch
								$question->branchparent = $currentbranchtable;
								break;
							case LL_CLUSTER :
								$newpage->title = 'Cluster';
								$newpage->contents = 'Cluster';
								break;
							case LL_ENDOFCLUSTER :
								$newpage->title = 'End of cluster';
								$newpage->contents = 'End of cluster';
								break;
							default:
                        		$newpage->title = "Page $count";
                        		$newpage->contents = $question->questiontext;
								break;
                        }
                    }
                    //$newpage->contents = $question->questiontext;

					// mark the ordering value
					$newpage->ordering = $count;

                    // set up page links
                    if ($pageid) {
                        // the new page follows on from this page
                        if (!$page = get_record("languagelesson_pages", "id", $pageid)) {
                            error ("Format: Page $pageid not found");
                        }
                        $newpage->prevpageid = $pageid;
                        $newpage->nextpageid = $page->nextpageid;
                        // insert the page and reset $pageid
                        if (!$newpageid = insert_record("languagelesson_pages", $newpage)) {
                            error("Format: Could not insert new page!");
                        }
                        // update the linked list
                        if (!set_field("languagelesson_pages", "nextpageid", $newpageid, "id", $pageid)) {
                            error("Format: unable to update link");
                        }

                    } else {
                        // new page is the first page
                        // get the existing (first) page (if any)
                        if (!$page = get_record_select("languagelesson_pages", "lessonid = $lesson->id AND prevpageid = 0")) {
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
                            $newpage->nextpageid = $page->id;
                            $newpageid = insert_record("languagelesson_pages", $newpage);
                            if (!$newpageid) {
                                error("Insert page: first page not inserted");
                            }
                            // update the linked list
                            if (!set_field("languagelesson_pages", "prevpageid", $newpageid, "id", $page->id)) {
                                error("Insert page: unable to update link");
                            }
                        }
                    }
                    // reset $pageid and put the page ID in $question, used in save_question_option()
                    $pageid = $newpageid;
                    $question->id = $newpageid;
                    
                    $this->questionids[] = $question->id;
                    
                  /// if we've run into a branch table, update the currentbranchtable pageID
                  /// pointer to reflect the most recent branch table ID so we can set proper
                  /// jumpto values for ENDOFBRANCH pages answer records
                    if ($question->qtype == LL_BRANCHTABLE) {
                    	$currentbranchtable = $newpageid;
                    	$branchtablestack[] = $question;
                    }

                    // Now to save all the answers and type-specific options

                    $question->lessonid = $lesson->id; // needed for foreign key
                    //$question->qtype = $this->qtypeconvert[$question->qtype];
                    
                  /// if the current question is not a branch table, it has no latent data
                  /// to be populated, so save its answer data
                    if ($question->qtype != LL_BRANCHTABLE) {
                    	$result = languagelesson_save_question_options($question);
                  /// if it is a branch table, hang on till the end, when all pages have
                  /// been created, then we can go through and set all the jumpto values
                  /// appropriately for answer records
                    } else {
                    	break;
                    }

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
        
        
      /// now that we've populated all the pages, we can go through each of the branch
      /// tables created and save the correct jumpto values for each branch
        foreach ($branchtablestack as $branchtable) {
        	
          /// pull the relative list of branches for modifying into an absolute list
          /// of branch pageIDs
        	$branches =& $branchtable->branches;
        	
        	for ($i=0; $i<count($branches); $i++) {
        		
        		$index = $branches[$i][0]; /// branch items are [ <jumpto pageid> , <jump text> ]
        		$branch = $questions[$index];
        		$branchid = $branch->id;
        		
        		$branches[$i][0] = $branchid;
        		
        	}
        	
          /// now, finally, actually save the "answer" data for the branchtable
        	$result = languagelesson_save_question_options($branchtable);
        	
          /// perform the above error-checking
        	if (!empty($result->error)) {
        		notify($result->error);
        		return false;
        	}
        	if (!empty($result->notice)) {
        		notify($result->notice);
        		return true;
        	}
        	
        }
        
        
      /// and now take care of the last little bit of business, that is, actually
      /// uploading the autoupload files, by building and submitting a form for them
      	if (count($autouploadfnames) > 0) {
      		$this->autouploading = true; //store this so we can give the below form one
      									 //more input in import.php
      		$this->build_submit_autoupload_form($autouploadfnames, $upload_destination);
      	}
        
        
        
        return true;
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

        echo "<p>This flash question format has not yet been completed!</p>";

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

?>
