<?php // $Id: format.php 673 2011-09-01 20:40:12Z griffisd $
//
///////////////////////////////////////////////////////////////
// The GIFT import filter was designed as an easy to use method 
// for teachers writing questions as a text file. It supports most
// question types and the missing word format.
//
// Multiple Choice / Missing Word
//     Who's buried in Grant's tomb?{~Grant ~Jefferson =no one}
//     Grant is {~buried =entombed ~living} in Grant's tomb.
// True-False:
//     Grant is buried in Grant's tomb.{FALSE}
// Short-Answer.
//     Who's buried in Grant's tomb?{=no one =nobody}
// Numerical
//     When was Ulysses S. Grant born?{#1822:5}
// Matching
//     Match the following countries with their corresponding
//     capitals.{=Canada->Ottawa =Italy->Rome =Japan->Tokyo}
//
// Comment lines start with a double backslash (//). 
// Optional question names are enclosed in double colon(::). 
// Answer feedback is indicated with hash mark (#).
// Percentage answer weights immediately follow the tilde (for
// multiple choice) or equal sign (for short answer and numerical),
// and are enclosed in percent signs (% %). See docs and examples.txt for more.
// 
// This filter was written through the collaboration of numerous 
// members of the Moodle community. It was originally based on 
// the missingword format, which included code from Thomas Robb
// and others. Paul Tsuchido Shew wrote this filter in December 2003.
//////////////////////////////////////////////////////////////////////////
// Based on default.php, included by ../import.php
/**
 * @package questionbank
 * @subpackage importexport
 */
class qformat_giftplus extends qformat_default {

    function provide_import() {
        return true;
    }

    function provide_export() {
        return true;
    }

    function answerweightparser(&$answer) {
        $answer = substr($answer, 1);                        // removes initial %
        $end_position  = strpos($answer, "%");
        $answer_weight = substr($answer, 0, $end_position);  // gets weight as integer
        $answer_weight = $answer_weight/100;                 // converts to percent
        $answer = substr($answer, $end_position+1);          // removes comment from answer
        return $answer_weight;
    }


    function commentparser(&$answer) {
        if (strpos($answer,"#") > 0){
            $hashpos = strpos($answer,"#");
            $comment = substr($answer, $hashpos+1);
            $comment = addslashes(trim($this->escapedchar_post($comment)));
            $answer  = substr($answer, 0, $hashpos);
        } else {
            $comment = " ";
        }
        return $comment;
    }

    function split_truefalse_comment($comment){
        // splits up comment around # marks
        // returns an array of true/false feedback
        $bits = explode('#',$comment);
        $feedback = array('wrong' => $bits[0]);
        if (count($bits) >= 2) {
            $feedback['right'] = $bits[1];
        } else {
            $feedback['right'] = '';
        }
        return $feedback;
    }
    
    function escapedchar_pre($string) {
        //Replaces escaped control characters with a placeholder BEFORE processing
        
        $escapedcharacters = array("\\:",    "\\#",    "\\=",    "\\{",    "\\}",    "\\~",    "\\n"   );  //dlnsk
        $placeholders      = array("&&058;", "&&035;", "&&061;", "&&123;", "&&125;", "&&126;", "&&010" );  //dlnsk

        $string = str_replace("\\\\", "&&092;", $string);
        $string = str_replace($escapedcharacters, $placeholders, $string);
        $string = str_replace("&&092;", "\\", $string);
        return $string;
    }

    function escapedchar_post($string) {
        //Replaces placeholders with corresponding character AFTER processing is done
        $placeholders = array("&&058;", "&&035;", "&&061;", "&&123;", "&&125;", "&&126;", "&&010"); //dlnsk
        $characters   = array(":",     "#",      "=",      "{",      "}",      "~",      "\n"   ); //dlnsk
        $string = str_replace($placeholders, $characters, $string);
        return $string;
    }

    function check_answer_count( $min, $answers, $text ) {
        $countanswers = count($answers);
        if ($countanswers < $min) {
            $importminerror = get_string( 'importminerror', 'quiz' );
            error( $importminerror . ' ' . $text );
            return false;
        }

        return true;
    }
	
	
	
	
  ///// Override of readquestions, to allow stack behavior in
  ///// creating branch tables
	function readquestions($lines) {
    /// Parses an array of lines into an array of questions, 
    /// where each item is a question object as defined by 
    /// readquestion().   Questions are defined as anything 
    /// between blank lines.
     
        $questions = array();
        $currentquestion = array();
        
      /// this stack should have a maximum of one item on it
        $stack = array();
        
      /// the marker of whether or not the next question is the start of a branch
        $flag = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if (!empty($currentquestion)) {
                    if ($question = $this->readquestion($currentquestion)) {
                        
                        if ($question->qtype != LL_BRANCHTABLE) {
                        	$questions[] = $question;
                        	
                        	if ($question->qtype == LL_ENDOFBRANCH) {
                        		if (count($stack) < 1) {
                        			error('Too many ENDOFBRANCHes!');
                        		}
                        		
                        		$table =& $stack[0];
                        		$table['seenbranches']++;
                        		
                        		if ($table['seenbranches'] == $table['expectedbranches']) {
                        		  /// we have seen the expected number of branches,
                        		  /// so put the completed object back into the
                        		  /// questions array
                        		  	$questions[$table['position']] = $table['tableobject'];
                        		  /// and pop it from the stack
                        		  	array_shift($stack);
                        		} else {
                        		  /// we haven't yet seen all the branches, so the
                        		  /// next question should be the start of the next
                        		  /// branch
                        			$flag = true;
                        		}
                        	
                        	  ///save the relative position of the ENDOFBRANCH page's
                        	  ///parent branch table, for later filling in proper jumpto vals
                        		$question->branchparent = $table['position'];
                        		
                        	} elseif ($flag) {
                        		if (count($stack) < 1) {
                        			error("A question was marked as the start of a branch
                        				   for a branch table that doesn't exist.");
                        		}
                        		
                        		$table =& $stack[0]['tableobject'];
                        		
                        	  ///since pageids haven't been created yet (as none of this
                        	  ///is yet in the database), we have to save relative positions
                        	  ///of each of the branch-beginning pages (these are the only
                        	  ///truly unique attributes available);
                        	  ///we also need to save the input jump text for labeling the
                        	  ///jump buttons
                        	  	$branchdata = array();
                        	  	$branchdata[0] = count($questions) - 1;
                        	  	$branchdata[1] = trim($table->branchnames[count($table->branches)]);
                        		$table->branches[] = $branchdata;
                        		
                        	  ///now that we've saved the start of the current branch,
                        	  ///reset the flag
                        		$flag = false;
                        	}
                        	
                        } else {
                          ///it's the start of a branch table
                          
                          ///shove an empty placeholder into the questions array,
                          ///and save where the complete object should be placed
                        	$questions[] = null;
                        	$position = count($questions) - 1;
                        	
                          ///push the table onto the stack, stored with the position
                          ///of its placeholder, the number of branches seen so far,
                          ///and the expected number of branches
                           	$stack[] = array('tableobject' => $question,
											 'position' => $position,
											 'seenbranches' => 0,
											 'expectedbranches' => $question->numbranches);
                           	
                          ///we just created a branch table, so the very next question
                          ///should be the first question in the first branch
                           	$flag = true;
                        }
                    
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
        
      ///the proper number of ENDBRANCH tags may not have been put in
      	if (!empty($stack)) {
      	  ///if the currently-open branch is not the last branch, bail
      		if (($stack[0]['seenbranches'] + 1) != $stack[0]['expectedbranches']) {
      			error('Too few branches designated.');
      	  ///otherwise, forgive, forget, and push in the complete branch table
      		} else {
      			$questions[$stack[0]['position']] = $stack[0]['tableobject'];
      		}
      	}

        return $questions;
    }
	
	
	
	
	
	
	

    function readquestion($lines) {
    // Given an array of lines known to define a question in this format, this function
    // converts it into a question object suitable for processing and insertion into Moodle.

        $question = $this->defaultquestion();
        $comment = NULL;
        // define replaced by simple assignment, stop redefine notices
        $gift_answerweight_regex = "^%\-*([0-9]{1,2})\.?([0-9]*)%";        

        // REMOVED COMMENTED LINES and IMPLODE
        foreach ($lines as $key => $line) {
            $line = trim($line);
            if (substr($line, 0, 2) == "//") {
                $lines[$key] = " ";
            }
        }

        $text = trim(implode(" ", $lines));

        if ($text == "") {
            return false;
        }

        // Substitute escaped control characters with placeholders
        $text = $this->escapedchar_pre($text);

        // Look for category modifier
        if (ereg( '^\$CATEGORY:', $text)) {
            // $newcategory = $matches[1];
            $newcategory = trim(substr( $text, 10 ));

            // build fake question to contain category
            $question->qtype = 'category';
            $question->category = $newcategory;
            return $question;
        }
        
        // QUESTION NAME parser
        if (substr($text, 0, 2) == "::") {
            $text = substr($text, 2);

            $namefinish = strpos($text, "::");
            if ($namefinish === false) {
                $question->name = false;
                // name will be assigned after processing question text below
            } else {
                $questionname = substr($text, 0, $namefinish);
                $question->name = addslashes(trim($this->escapedchar_post($questionname)));
                $text = trim(substr($text, $namefinish+2)); // Remove name from text
            }
        } else {
            $question->name = false;
        }


        // FIND ANSWER section
        // no answer means it's a description
		// multiple answer areas means it's a cloze
        $answerstart = strpos($text, "{");
        $answerfinish = strpos($text, "}");

        $description = false;
		$cloze = false;
        if (($answerstart === false) and ($answerfinish === false)) {
            $description = true;
            $answertext = '';
            $answerlength = 0;
        }
        elseif (!(($answerstart !== false) and ($answerfinish !== false))) {
            error( get_string( 'braceerror', 'quiz' ) . ' ' . $text );
            return false;
        }
		// if multiple answers are found, it's a CLOZE
		elseif (strpos(substr($text, $answerfinish), "{")) {
			$cloze = true;
		}
        else {
            $answerlength = $answerfinish - $answerstart;
            $answertext = trim(substr($text, $answerstart + 1, $answerlength - 1));
        }

        // Format QUESTION TEXT without answer
        if ($description) {
            $questiontext = $text;
        }
		// If this was detected as a CLOZE, push in anchors for all the questions
		elseif ($cloze) {
			$answertexts = array();
			$questiontext = $text;
			$answerlength = $answerfinish - $answerstart;
			$answertexts[] = trim(substr($questiontext, $answerstart + 1, $answerlength - 1));
			$questiontext = substr_replace($questiontext, "<a name=\"1\"></a>", $answerstart, $answerlength+1);
			$i = 2;
			while ($newstart = strpos($questiontext, "{")) {
				if(! $newfinish = strpos($questiontext, "}")) {
					error(get_string('braceerror', 'quiz').' '.$text);
				}
				$newlength = $newfinish-$newstart;
				// pull the text of the answer, excluding the braces
				$atext = trim(substr($questiontext, $newstart + 1, $newlength - 1));
				$answertexts[] = $atext;
				// if it's not setting the custom feedback for the question, replace it with a placeholder in the questiontext 
				if ($atext[0] != '#') {
					$questiontext = substr_replace($questiontext, "<a name=\"$i\"></a>", $newstart, $newlength+1);
					$i++;
				// if it is, just get rid of it
				} else {
					$questiontext = substr_replace($questiontext, '', $newstart, $newlength+1);
				}
			}
		}
        elseif (substr($text, -1) == "}") {
            // no blank line if answers follow question, outside of closing punctuation
            $questiontext = substr_replace($text, "", $answerstart, $answerlength+1);
		// If this is a missing word format SHORTANSWER, add a _____ in the question
        } else {
            // inserts blank line for missing word format
            $questiontext = substr_replace($text, "_____", $answerstart, $answerlength+1);
        }

        // get questiontext format from questiontext
        $oldquestiontext = $questiontext;
        $questiontextformat = 0;
        if (substr($questiontext,0,1)=='[') {
            $questiontext = substr( $questiontext,1 );
            $rh_brace = strpos( $questiontext, ']' );
            $qtformat= substr( $questiontext, 0, $rh_brace );
            $questiontext = substr( $questiontext, $rh_brace+1 );
            if (!$questiontextformat = text_format_name( $qtformat )) {
                $questiontext = $oldquestiontext;
            }          
        }
        $question->questiontextformat = $questiontextformat;
        $question->questiontext = addslashes(trim($this->escapedchar_post($questiontext)));

        // set question name if not already set
        if ($question->name === false) {
            $question->name = $question->questiontext;
		}

        // ensure name is not longer than 250 characters
        $question->name = shorten_text( $question->name, 200 );
        $question->name = strip_tags(substr( $question->name, 0, 250 ));

        // determine QUESTION TYPE
        $question->qtype = NULL;

        // give plugins first try
        // plugins must promise not to intercept standard qtypes
        // MDL-12346, this could be called from lesson mod which has its own base class =(
		if (method_exists($this, 'try_importing_using_qtypes') && ($try_question = $this->try_importing_using_qtypes( $lines,
						$question, $answertext ))) {
            return $try_question;
        }

        if ($description) {
            $question->qtype = LL_DESCRIPTION;
        }
    /////ADDED IN/////
		elseif ($cloze) {
			$question->qtype = LL_CLOZE;
		}
    //////////////////
        elseif ($answertext == '') {
            $question->qtype = LL_ESSAY;
        }
        elseif ($answertext{0} == "#"){
            $question->qtype = LL_NUMERICAL;

        } elseif (strpos($answertext, "~") !== false)  {
            // only Multiplechoice questions contain tilde ~
            $question->qtype = LL_MULTICHOICE;
    
        } elseif (strpos($answertext, "=")  !== false 
                && strpos($answertext, "->") !== false) {
            // only Matching contains both = and ->
            $question->qtype = LL_MATCHING;
        
    /////ADDED IN/////
        
      ///question types///
        } elseif ($answertext == 'AUDIO') {
        	$question->qtype = LL_AUDIO;
        
        } elseif ($answertext == 'VIDEO') {
        	$question->qtype = LL_VIDEO;
        
      ///structural///
      	//if it has any pipes in it, it's declaring a branch table
      	} elseif (strpos($answertext, "|") !== false) {
      		$question->qtype = LL_BRANCHTABLE;
      	
        } elseif ($answertext == 'ENDBRANCH') {
        	$question->qtype = LL_ENDOFBRANCH;
        
        //BEGINCLUSTER tag has OPTIONAL show=x attribute, so avoid that
        } elseif ($answertext == 'CLUSTER' ||
        		  substr($answertext, 0, strpos($answertext, ' ')) == 'CLUSTER') {
        	$question->qtype = LL_CLUSTER;
        
        } elseif ($answertext == 'ENDCLUSTER') {
        	$question->qtype = LL_ENDOFCLUSTER;
        
    //////////////////

        } else { // either TRUEFALSE or SHORTANSWER
    
            // TRUEFALSE question check
            $truefalse_check = $answertext;
            if (strpos($answertext,"#") > 0){ 
                // strip comments to check for TrueFalse question
                $truefalse_check = trim(substr($answertext, 0, strpos($answertext,"#")));
            }

            $valid_tf_answers = array("T", "TRUE", "F", "FALSE");
            if (in_array($truefalse_check, $valid_tf_answers)) {
                $question->qtype = LL_TRUEFALSE;

            } else { // Must be SHORTANSWER
                    $question->qtype = LL_SHORTANSWER;
            }
        }

        if (!isset($question->qtype)) {
            $giftqtypenotset = get_string('giftqtypenotset','quiz');
            error( $giftqtypenotset . ' ' . $text );
            return false;
        }

        switch ($question->qtype) {
            case LL_DESCRIPTION:
                $question->defaultgrade = 0;
                $question->length = 0;
                return $question;
                break;
            case LL_ESSAY:
            case LL_AUDIO:
            case LL_VIDEO:
                $question->feedback = '';
                $question->fraction = 0;
                return $question;
                break;
            case LL_MULTICHOICE:
                if (strpos($answertext,"=") === false) {
                    $question->single = 0;   // multiple answers are enabled if no single answer is 100% correct                        
                } else {
                    $question->single = 1;   // only one answer allowed (the default)
                }

                $answertext = str_replace("=", "~=", $answertext);
                $answers = explode("~", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }
    
                $countanswers = count($answers);
                
                if (!$this->check_answer_count( 2,$answers,$text )) {
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // determine answer weight
                    if ($answer[0] == "=") {
                        $answer_weight = 1;
                        $answer = substr($answer, 1);
    
                    } elseif (ereg($gift_answerweight_regex, $answer)) {    // check for properly formatted answer weight
                        $answer_weight = $this->answerweightparser($answer);
                    
                    } else {     //default, i.e., wrong anwer
                        $answer_weight = 0;
                    }
                    $question->fraction[$key] = $answer_weight;
                    $question->feedback[$key] = $this->commentparser($answer); // commentparser also removes comment from $answer
                    $question->answer[$key]   = addslashes($this->escapedchar_post($answer));
                    $question->correctfeedback = '';
                    $question->partiallycorrectfeedback = '';
                    $question->incorrectfeedback = '';
                }  // end foreach answer
    
                //$question->defaultgrade = 1;
                //$question->image = "";   // No images with this format
                return $question;
                break;

            case LL_MATCHING:
                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }
    
                if (!$this->check_answer_count( 2,$answers,$text )) {
                    return false;
                    break;
                }
    
                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);
                    if (strpos($answer, "->") === false) {
                        $giftmatchingformat = get_string('giftmatchingformat','quiz');
                        error($giftmatchingformat . ' ' . $answer );
                        return false;
                        break 2;
                    }

                    $marker = strpos($answer,"->");
                    $question->subquestions[$key] = addslashes(trim($this->escapedchar_post(substr($answer, 0, $marker))));
                    $question->subanswers[$key]   = addslashes(trim($this->escapedchar_post(substr($answer, $marker+2))));

                }  // end foreach answer
    
                return $question;
                break;
            
            case LL_TRUEFALSE:
                $answer = $answertext;
                $comment = $this->commentparser($answer); // commentparser also removes comment from $answer
                $feedback = $this->split_truefalse_comment($comment);

                if ($answer == "T" OR $answer == "TRUE") {
                    $question->answer = 1;
                    $question->feedbacktrue = $feedback['right'];
                    $question->feedbackfalse = $feedback['wrong'];
                } else {
                    $question->answer = 0;
                    $question->feedbackfalse = $feedback['right'];
                    $question->feedbacktrue = $feedback['wrong'];
                }

                $question->penalty = 1;
                $question->correctanswer = $question->answer;

                return $question;
                break;
                
            case LL_SHORTANSWER:
                // SHORTANSWER Question
                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }
    
                if (!$this->check_answer_count( 1,$answers,$text )) {
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Answer Weight
                    if (ereg($gift_answerweight_regex, $answer)) {    // check for properly formatted answer weight
                        $answer_weight = $this->answerweightparser($answer);
                    } else {     //default, i.e., full-credit anwer
                        $answer_weight = 1;
                    }
                    $question->fraction[$key] = $answer_weight;
                    $question->feedback[$key] = $this->commentparser($answer); //commentparser also removes comment from $answer
                    $question->answer[$key]   = addslashes($this->escapedchar_post($answer));
                }     // end foreach

                //$question->usecase = 0;  // Ignore case
                //$question->defaultgrade = 1;
                //$question->image = "";   // No images with this format
                return $question;
                break;

			case LL_CLOZE :
				$i = 0;
				foreach ($answertexts as $answertext) {
					// check if this text is setting the question's feedback
					if ($answertext[0] == '#') {
						// it's setting the question feedback
						$answertext = trim(substr($answertext, 1));
						if (strpos($answertext, '#')) {
							// both feedbacks are being set
							$chunks = explode('#', $answertext);
							foreach ($chunks as $chunk) {
								$chunk = trim($chunk);
								// if it's marked as the correct feedback, save it as such
								if ($chunk[0] == '=') {
									$question->feedback[1] = substr($chunk, 1);
								// otherwise, save it as the "wrong" feedback
								} else {
									$question->feedback[0] = $chunk;
								}
							}
						} else {
							// save the one that was given, and store the other as empty
							if ($answertext[0] == '=') {
								$question->feedback[1] = substr($answertext, 1);
								$question->feedback[0] = '';
							} else {
								$question->feedback[0] = $answertext;
								$question->feedback[1] = '';
							}
						}
					}
					// otherwise, it's actually an answer
					else {
						if (strpos($answertext, ':')) {
							$chunks = explode(':', $answertext);
							if (! $score = intval($chunks[0])) {
								error('Invalid score input: '.$answertext);
							}
							$answertext = trim($chunks[1]);
						} else {
							$score = 1;
						}
						$key = $i++;
						$question->fraction[$key] = $score;
						$question->answer[$key] = $answertext;
					}
				}
				// make sure that feedback attribute exists
				if (!isset($question->feedback)) {
					$question->feedback[0] = '';
					$question->feedback[1] = '';
				}
				return $question;
				break;

            /*case LL_NUMERICAL:
                // Note similarities to ShortAnswer
                $answertext = substr($answertext, 1); // remove leading "#"

                // If there is feedback for a wrong answer, store it for now.
                if (($pos = strpos($answertext, '~')) !== false) {
                    $wrongfeedback = substr($answertext, $pos);
                    $answertext = substr($answertext, 0, $pos);
                } else {
                    $wrongfeedback = '';
                }

                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }
    
                if (count($answers) == 0) {
                    // invalid question
                    $giftnonumericalanswers = get_string('giftnonumericalanswers','quiz');
                    error( $giftnonumericalanswers . ' ' . $text );
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Answer weight
                    if (ereg($gift_answerweight_regex, $answer)) {    // check for properly formatted answer weight
                        $answer_weight = $this->answerweightparser($answer);
                    } else {     //default, i.e., full-credit anwer
                        $answer_weight = 1;
                    }
                    $question->fraction[$key] = $answer_weight;
                    $question->feedback[$key] = $this->commentparser($answer); //commentparser also removes comment from $answer

                    //Calculate Answer and Min/Max values
                    if (strpos($answer,"..") > 0) { // optional [min]..[max] format
                        $marker = strpos($answer,"..");
                        $max = trim(substr($answer, $marker+2));
                        $min = trim(substr($answer, 0, $marker));
                        $ans = ($max + $min)/2;
                        $tol = $max - $ans;
                    } elseif (strpos($answer,":") > 0){ // standard [answer]:[errormargin] format
                        $marker = strpos($answer,":");
                        $tol = trim(substr($answer, $marker+1));
                        $ans = trim(substr($answer, 0, $marker));
                    } else { // only one valid answer (zero errormargin)
                        $tol = 0;
                        $ans = trim($answer);
                    }
    
                    if (!(is_numeric($ans) || $ans = '*') || !is_numeric($tol)) {
                            $errornotnumbers = get_string( 'errornotnumbers' );
                            error( $errornotnumbers . ' ' . $text );
                        return false;
                        break;
                    }
                    
                    // store results
                    $question->answer[$key] = $ans;
                    $question->tolerance[$key] = $tol;
                } // end foreach

                if ($wrongfeedback) {
                    $key += 1;
                    $question->fraction[$key] = 0;
                    $question->feedback[$key] = $this->commentparser($wrongfeedback);
                    $question->answer[$key] = '';
                    $question->tolerance[$key] = '';
                }

                return $question;
                break;

                default:
                    $giftnovalidquestion = get_string('giftnovalidquestion','quiz');
                    error( $giftnovalidquestion . ' ' . $text );
                return false;
                break;    */      
            
          ///// added structural types /////
          	case LL_BRANCHTABLE:
      			$question->numbranches = count(explode('|', $answertext));
      			$question->branches = array();
      			$question->branchnames = explode('|', $answertext);
      			return $question;
          		break;
          	
          	default: /// this is LL_ENDOFBRANCH, LL_CLUSTER, && LL_ENDOFCLUSTER;
          			 /// nothing special needs to be done with these pages, they just need
          			 /// to exist
          	 	return $question;
          		break;
        
        } // end switch ($question->qtype)

    }    // end function readquestion($lines)

function repchar( $text, $format=0 ) {
    // escapes 'reserved' characters # = ~ { ) : and removes new lines
    // also pushes text through format routine
    $reserved = array( '#', '=', '~', '{', '}', ':', "\n","\r");
    $escaped =  array( '\#','\=','\~','\{','\}','\:','\n',''  ); //dlnsk

    $newtext = str_replace( $reserved, $escaped, $text ); 
    $format = 0; // turn this off for now
    if ($format) {
        $newtext = format_text( $format );
    }
    return $newtext;
    }

function writequestion( $question ) {
    // turns question into string
    // question reflects database fields for general question and specific to type

    global $QTYPES; 

    // initial string;
    $expout = "";

    // add comment
    $expout .= "// question: $question->id  name: $question->name \n";

    // get  question text format
    $textformat = $question->questiontextformat;
    $tfname = "";
    if ($textformat!=FORMAT_MOODLE) {
        $tfname = text_format_name( (int)$textformat );
        $tfname = "[$tfname]";
    }

    // output depends on question type
    switch($question->qtype) {
    case 'category':
        // not a real question, used to insert category switch
        $expout .= "\$CATEGORY: $question->category\n";    
        break;
    case DESCRIPTION:
        $expout .= '::'.$this->repchar($question->name).'::';
        $expout .= $tfname;
        $expout .= $this->repchar( $question->questiontext, $textformat);
        break;
    case ESSAY:
        $expout .= '::'.$this->repchar($question->name).'::';
        $expout .= $tfname;
        $expout .= $this->repchar( $question->questiontext, $textformat);
        $expout .= "{}\n";
        break;
    case TRUEFALSE:
        $trueanswer = $question->options->answers[$question->options->trueanswer];
        $falseanswer = $question->options->answers[$question->options->falseanswer];
        if ($trueanswer->fraction == 1) {
            $answertext = 'TRUE';
            $right_feedback = $trueanswer->feedback;
            $wrong_feedback = $falseanswer->feedback;
        } else {
            $answertext = 'FALSE';
            $right_feedback = $falseanswer->feedback;
            $wrong_feedback = $trueanswer->feedback;
        }

        $wrong_feedback = $this->repchar($wrong_feedback);
        $right_feedback = $this->repchar($right_feedback);
        $expout .= "::".$this->repchar($question->name)."::".$tfname.$this->repchar( $question->questiontext,$textformat )."{".$this->repchar( $answertext );
        if ($wrong_feedback) {
            $expout .= "#" . $wrong_feedback;
        } else if ($right_feedback) {
            $expout .= "#";
        }
        if ($right_feedback) {
            $expout .= "#" . $right_feedback;
        }
        $expout .= "}\n";
        break;
    case MULTICHOICE:
        $expout .= "::".$this->repchar($question->name)."::".$tfname.$this->repchar( $question->questiontext, $textformat )."{\n";
        foreach($question->options->answers as $answer) {
            if ($answer->fraction==1) {
                $answertext = '=';
            }
            elseif ($answer->fraction==0) {
                $answertext = '~';
            }
            else {
                $export_weight = $answer->fraction*100;
                $answertext = "~%$export_weight%";
            }
            $expout .= "\t".$answertext.$this->repchar( $answer->answer );
            if ($answer->feedback!="") {
                $expout .= "#".$this->repchar( $answer->feedback );
            }
            $expout .= "\n";
        }
        $expout .= "}\n";
        break;
    case SHORTANSWER:
        $expout .= "::".$this->repchar($question->name)."::".$tfname.$this->repchar( $question->questiontext, $textformat )."{\n";
        foreach($question->options->answers as $answer) {
            $weight = 100 * $answer->fraction;
            $expout .= "\t=%".$weight."%".$this->repchar( $answer->answer )."#".$this->repchar( $answer->feedback )."\n";
        }
        $expout .= "}\n";
        break;
    case NUMERICAL:
        $expout .= "::".$this->repchar($question->name)."::".$tfname.$this->repchar( $question->questiontext, $textformat )."{#\n";
        foreach ($question->options->answers as $answer) {
            if ($answer->answer != '') {
                $percentage = '';
                if ($answer->fraction < 1) {
                    $pval = $answer->fraction * 100;
                    $percentage = "%$pval%";
                }
                $expout .= "\t=$percentage".$answer->answer.":".(float)$answer->tolerance."#".$this->repchar( $answer->feedback )."\n";
            } else {
                $expout .= "\t~#".$this->repchar( $answer->feedback )."\n";
            }
        }
        $expout .= "}\n";
        break;
    case MATCH:
        $expout .= "::".$this->repchar($question->name)."::".$tfname.$this->repchar( $question->questiontext, $textformat )."{\n";
        foreach($question->options->subquestions as $subquestion) {
            $expout .= "\t=".$this->repchar( $subquestion->questiontext )." -> ".$this->repchar( $subquestion->answertext )."\n";
        }
        $expout .= "}\n";
        break;
    default:
        // check for plugins
        if ($out = $this->try_exporting_using_qtypes( $question->qtype, $question )) {
            $expout .= $out;
        }
        else {
            $expout .= "// $question->qtype is not supported by the GIFT format\n";
            $menuname = $QTYPES[$question->qtype]->menu_name(); 
            notify( get_string('nohandler','qformat_gift', $menuname ) );
        }
    }
    // add empty line to delimit questions
    $expout .= "\n";
    return $expout;
}
}
?>
