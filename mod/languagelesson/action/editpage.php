<?php // $Id: editpage.php 675 2011-09-16 19:27:51Z griffisd $
/**
 *  Action for editing a page.  Prints an HTML form.
 *
 * @version $Id: editpage.php 675 2011-09-16 19:27:51Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

////////////////////////////////////////////////
// BASIC SETUP
//////////////////////////////////////////////// 
	// get the page
	$pageid = required_param('pageid', PARAM_INT);
	$redirect = optional_param('redirect', '', PARAM_ALPHA);

	if (!$page = get_record("languagelesson_pages", "id", $pageid)) {
		error("Edit page: page record not found");
	}

	$page->qtype = optional_param('qtype', $page->qtype, PARAM_INT);

	// set up jump array
	$jump = array();
	$jump[0] = get_string("thispage", "languagelesson");
	$jump[LL_NEXTPAGE] = get_string("nextpage", "languagelesson");
	$jump[LL_PREVIOUSPAGE] = get_string("previouspage", "languagelesson");
	if(languagelesson_display_branch_jumps($lesson->id, $page->id)) {
		$jump[LL_UNSEENBRANCHPAGE] = get_string("unseenpageinbranch", "languagelesson");
		$jump[LL_RANDOMPAGE] = get_string("randompageinbranch", "languagelesson");
	}
	if ($page->qtype == LL_ENDOFBRANCH || $page->qtype == LL_BRANCHTABLE) {
		$jump[LL_RANDOMBRANCH] = get_string("randombranch", "languagelesson");
	}
	if(languagelesson_display_cluster_jump($lesson->id, $page->id) && $page->qtype != LL_BRANCHTABLE && $page->qtype != LL_ENDOFCLUSTER) {
		$jump[LL_CLUSTERJUMP] = get_string("clusterjump", "languagelesson");
	}
	$jump[LL_EOL] = get_string("endoflesson", "languagelesson");
	if (!$apageid = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "prevpageid", 0)) {
		error("Edit page: first page not found");
	}
	while (true) {
		if ($apageid) {
			if (!$apage = get_record("languagelesson_pages", "id", $apageid)) {
				error("Edit page: apage record not found");
			}
			// removed != LL_ENDOFBRANCH...
			if (trim($page->title)) { // ...nor nuffin pages
				$jump[$apageid] = strip_tags(format_string($apage->title,true));
			}
			$apageid = $apage->nextpageid;
		} else {
			// last page reached
			break;
		}
	}

	// pull any updated data submitted on adding new questions
	$data = data_submitted();

	// give teacher a proforma
	?>
	<form id="editpage" method="post" action="lesson.php">
	<fieldset class="invisiblefieldset fieldsetfix">
	<input type="hidden" name="id" value="<?php echo $cm->id ?>" />
    <input type="hidden" name="action" id="actioninput" />
	<input type="hidden" name="pageid" value="<?php echo $pageid ?>" />
	<input type="hidden" name="sesskey" value="<?php echo $USER->sesskey ?>" />        
	<input type="hidden" name="redirect" value="<?php echo $redirect ?>" />        
	<input type="hidden" name="redisplay" value="0" />
	<center>
	<?php

//////////////////////////////////////////////// 
////////////////////////////////////////////////




//////////////////////////////////////////////// 
// QTYPES AND PAGE CONTENTS
//////////////////////////////////////////////// 

	/// print out the tabbed question type selector and any qoption checkbox that may exist
		echo '<b>'.get_string("questiontype", "languagelesson").":</b> \n";
		echo helpbutton("questiontypes", get_string("questiontype", "languagelesson"), "languagelesson")."<br />";
		languagelesson_qtype_menu($LL_QUESTION_TYPE, $page->qtype, 
						  "lesson.php?id=$cm->id&amp;action=editpage&amp;pageid=$page->id",
						  "getElementById('editpage').redisplay.value=1;getElementById('editpage').submit();");

		// handle the question types that include a possible qoption
		switch ($page->qtype) {
			case LL_MULTICHOICE :
			case LL_CLOZE :
			case LL_SHORTANSWER :
				switch ($page->qtype) {
					case LL_MULTICHOICE :
						echo "<p><b><label for=\"qoption\">".get_string('multianswer', 'languagelesson').":</label></b> \n";
						break;
					case LL_SHORTANSWER :
						echo "<p><b><label for=\"qoption\">".get_string('casesensitive', 'languagelesson').":</label></b> \n";
						break;
					case LL_CLOZE :
						echo "<p><b><label for=\"qoption\">".get_string('casesensitive', 'languagelesson').":</label></b> \n";
						break;
				}
				if ($page->qoption) {
					echo "<input type=\"checkbox\" id=\"qoption\" name=\"qoption\" value=\"1\" checked=\"checked\" />";
				} else {
					echo "<input type=\"checkbox\" id=\"qoption\" name=\"qoption\" value=\"1\" />";
				}
				helpbutton("questionoption", get_string("questionoption", "languagelesson"), "languagelesson");
				echo "</p>\n";
				break;
			default :
				break;
		}
	?>



	<table cellpadding="5" class="generalbox" border="1">
	<tr valign="top">
	<td><b><label for="title"><?php print_string('pagetitle', 'languagelesson'); ?>:</label></b><br />
	<input type="text" id="title" name="title" size="80" maxsize="255" value="<?php
		echo ((isset($data->title) && ! empty($data->title) && ($page->title != $data->title)) ? $data->title : $page->title);
		?>" /></td>
	</tr>
	<?php
	echo "<tr><td><b>";
	echo get_string("pagecontents", "languagelesson").":</b><br />\n";
	$pagecontents = clean_param(stripslashes(trim($page->contents)), PARAM_CLEANHTML);
	print_textarea($usehtmleditor, 25, 70, 630, 400, "contents",
		((isset($data->contents) && ! empty($data->contents) && ($data->contents != $pagecontents)) ? $data->contents : $pagecontents));
	if ($usehtmleditor) {
		use_html_editor("contents");
	}
	echo "</td></tr></table>\n";
	?>

	<table cellpadding="5" class="generalbox" border="1">
	<?php
	$n = 0;
	/// switch to handle structural pages info
	switch ($page->qtype) {
		case LL_BRANCHTABLE :
			echo "<input type=\"hidden\" name=\"qtype\" value=\"$page->qtype\" />\n";
			echo "<tr><td>\n";
			echo "<center>";
			if ($page->layout) {
				echo "<input checked=\"checked\" name=\"layout\" type=\"checkbox\" value=\"1\" />";
			} else {
				echo "<input name=\"layout\" type=\"checkbox\" value=\"1\" />";
			}
			echo get_string("arrangebuttonshorizontally", "languagelesson")."\n";
			echo "</center></td></tr>\n";
			echo "<tr><td><b>".get_string("branchtable", "languagelesson")."</b> \n";
			echo "</td></tr>\n";
			break;
		case LL_CLUSTER :
			echo "<input type=\"hidden\" name=\"qtype\" value=\"$page->qtype\" />\n";
			echo "<tr><td><b>".get_string("clustertitle", "languagelesson")."</b> \n";
			echo "</td></tr>\n";
			break;                
		case LL_ENDOFCLUSTER :
			echo "<input type=\"hidden\" name=\"qtype\" value=\"$page->qtype\" />\n";
			echo "<tr><td><b>".get_string("endofclustertitle", "languagelesson")."</b> \n";
			echo "</td></tr>\n";
			break;                            
		case LL_ENDOFBRANCH :
			echo "<input type=\"hidden\" name=\"qtype\" value=\"$page->qtype\" />\n";
			echo "<tr><td><b>".get_string("endofbranch", "languagelesson")."</b> \n";
			echo "</td></tr>\n";
			break;
		default :
			break;             
	}



////////////////////////////////////////////////
////////////////////////////////////////////////







////////////////////////////////////////////////
// ANSWERS
//////////////////////////////////////////////// 

	// get the answers in a set order, the id order

	////////////////////////////////////////////////
	// Boxes for pre-existing answers
	if ($answers = get_records("languagelesson_answers", "pageid", $page->id, "id")) {

		// if this is a CLOZE or MATCHING type, feedbacks are stored as their own answers, so init the feedbacks array
		if ($page->qtype == LL_CLOZE || $page->qtype == LL_MATCHING) { $feedbacks = array(); }

		foreach ($answers as $answer) {
			// if the answer or response has been updated before adding new questions, we should print that instead, so check for it
			// here; similarly, update jumpto and score vals if needed
			if (isset($data->answer[$n]) && $data->answer[$n] != $answer->answer) { $answer->answer = $data->answer[$n]; }
			if (isset($data->response[$n]) && $data->response[$n] != $answer->response) { $answer->response = $data->response[$n]; }
			if (isset($data->jumpto[$n]) && $data->jumpto[$n] != $answer->jumpto) { $answer->jumpto = $data->jumpto[$n]; }
			if (isset($data->score[$n]) && $data->score[$n] != $answer->score) { $answer->score = $data->score[$n]; }

			$flags = intval($answer->flags); // force into an integer
			$nplus1 = $n + 1;
			echo "<input type=\"hidden\" name=\"answerid[$n]\" value=\"$answer->id\" />\n";

			////////////////////////////////////////////////
			// answer box
			switch ($page->qtype) {
				case LL_MATCHING:
					// if the record has an answer field but does NOT have a response, it's a feedback, so save it 
					if (isset($answer->answer) && !isset($answer->response)) {
						$feedbacks[] = $answer;
					// print actual matchings
					} else {
						$ncorrected = $n - 1;
						echo '<tr><td><table><tr><td class="answerrow_cell">';
						echo "<b><label for=\"edit-answer[$n]\">".get_string('answer',
							'languagelesson')." $ncorrected:</label></b><br />\n";
						print_textarea(false, 1, 40, 0, 0, "answer[$n]", $answer->answer);
						echo "</td><td class=\"answerrow_cell\">\n";
						echo "<b><label for=\"edit-response[$n]\">".get_string('matchesanswer', 'languagelesson')."
							$ncorrected:</label></b><br />\n";
						print_textarea(false, 1, 40, 0, 0, "response[$n]", $answer->response);
						echo '</td></tr></table>';
						echo "</td></tr>\n";
					}
					break;

				case LL_TRUEFALSE:
				case LL_MULTICHOICE:
				case LL_SHORTANSWER:
				//case LL_NUMERICAL:                    
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					// print the answer field
					echo "<b><label for=\"edit-answer[$n]\">".get_string('answer', 'languagelesson')." $nplus1:</label></b><br
							/>\n";
					print_textarea(false, 1, 30, 0, 0, "answer[$n]", $answer->answer);
					echo '</td><td class="answerrow_cell">';
					// print the score field
					echo '<b>'.get_string("score", "languagelesson")." $nplus1:</b><br /><input type=\"text\" name=\"score[$n]\"
						value=\"$answer->score\" size=\"5\" />";
					echo '</td><td class="answerrow_cell">';
					// print the response field
					echo "<b><label for=\"edit-response[$n]\">".get_string('response', 'languagelesson')."
						$nplus1:</label></b><br />\n";
					print_textarea(false, 1, 30, 0, 0, "response[$n]", $answer->response);
					echo '</td><td class="answerrow_cell">';
					// print the jump field
					echo "<b>".get_string("jump", "languagelesson")." $nplus1:</b> \n";
					choose_from_menu($jump, "jumpto[$n]", $answer->jumpto, "");
					helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
					echo "</td></tr></table></td></tr>";
					break;

				case LL_CLOZE:
					// if this is a response (answer record with empty answer attribute), save it for later
					if (empty($answer->answer)) {
						$feedbacks[] = $answer;
						// we don't want to run any of the rest of the code in this block for a response (in particular, we don't want
						// to increment the number of previous answers seen), so continue
						continue 2; // continuing the outer-level foreach loop
					}
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					// print the answer box
					echo "<b><label for=\"edit-answer[$n]\">".get_string('answer', 'languagelesson')." $nplus1:</label>
							</b><br />";
					// get rid of the ordering numeral at the start of the answer
					$atext = explode('|', $answer->answer);
					$atext = $atext[1];
					print_textarea(false, 1, 30, 0, 0, "answer[$n]", $atext);
					echo '</td><td class="answerrow_cell">';
					// print the drop-down checkbox
					$a->number = $nplus1;
					echo "<label for=\"dropdown[$n]\">".get_string('usedropdown', 'languagelesson', $a)."</label>";
					echo "<input type=\"checkbox\" id=\"dropdown[$n]\" name=\"dropdown[$n]\" value=\"1\" ".($flags ?
							'checked="yes"' : '') . " />";
					echo '</td><td class="answerrow_cell">';
					// print the score field
					echo '<b>'.get_string("score", "languagelesson")." $nplus1:</b><br />";
					echo "<input type=\"text\" name=\"score[$n]\" value=\"$answer->score\" size=\"5\" />";
					echo '</td></tr></table></td></tr>';
					break;

				case LL_AUDIO:
				case LL_VIDEO:
				case LL_ESSAY:
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					// print score field
					echo '<b>'.get_string("score", "languagelesson").":</b><br />";
					echo "<input type=\"text\" name=\"score[$n]\" value=\"$answer->score\" size=\"5\" />";
					echo '</td><td class="answerrow_cell">';
					// print jump field
					echo "<b>".get_string("jump", "languagelesson").":</b><br />\n";
					choose_from_menu($jump, "jumpto[$n]", $answer->jumpto, "");
					helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
					echo "</td></tr></table></td></tr>";
					break;

				case LL_BRANCHTABLE:
				case LL_ENDOFBRANCH:
				case LL_CLUSTER:
				case LL_ENDOFCLUSTER:
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					echo "<b><label for=\"edit-answer[$n]\">".get_string("description", "languagelesson")."
						$nplus1:</label></b><br />\n";
					print_textarea(false, 1, 40, 0, 0, "answer[$n]", $answer->answer);
					echo '</td><td class="answerrow_cell">';
					echo "<b>".get_string("jump", "languagelesson")." $nplus1:</b><br />\n";
					choose_from_menu($jump, "jumpto[$n]", $answer->jumpto, "");
					helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
					echo "</td></tr></table></td></tr>\n";
					break;
				default :
					break;
			}

			$n++;
		}
	}


	////////////////////////////////////////////////
	// Additional (empty) answer boxes
	if ($page->qtype != LL_ENDOFBRANCH && $page->qtype != LL_CLUSTER && $page->qtype != LL_ENDOFCLUSTER) {
		// set $maxanswers appropriately here
		if (!isset($data->maxanswers)) {
			if ($n > 4) {
				$maxanswers = $n;
			} else {
				$maxanswers = 4;
			}
		} else {
			$maxanswers = $data->maxanswers + 4;
		}
		if ($page->qtype == LL_MATCHING) {
			$maxanswers += 2;
		}
		for ($i = $n; $i < $maxanswers; $i++) {
			if ($page->qtype == LL_TRUEFALSE && $i > 1) {
				break; // stop printing answers... only need two for true/false
			}
			$iplus1 = $i + 1;
			echo "<input type=\"hidden\" name=\"answerid[$i]\" value=\"0\" />\n";

			////////////////////////////////////////////////
			// answer box
			switch ($page->qtype) {
				case LL_MATCHING:
					$icorrected = $i - 1;
					echo "<tr><td><table>";
					echo '<tr><td class="answerrow_cell"<b>'.get_string("answer", "languagelesson")." $icorrected:</b><br />\n";
					print_textarea(false, 1, 40, 0, 0, "answer[$i]");
					echo '</td><td class="answerrow_cell">';
					echo "<b>".get_string("matchesanswer", "languagelesson")." $icorrected:</b><br />\n";
					print_textarea(false, 1, 40, 0, 0, "response[$i]");
					echo "</td></tr><table>";
					echo '</td></tr>';
					break;
				case LL_CLOZE:
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					// print the answer box
					echo "<b>".get_string("answer", "languagelesson")." $iplus1:</b><br />\n";
					print_textarea(false, 1, 30, 0, 0, "answer[$i]");
					echo '</td><td class="answerrow_cell">';
					// print the drop-down checkbox
					$a->number = $iplus1;
					echo "<label for=\"dropdown[$i]\">".get_string('usedropdown', 'languagelesson', $a)."</label>";
					echo "<input type=\"checkbox\" id=\"dropdown[$i]\" name=\"dropdown[$i]\" value=\"1\" />";
					echo '</td><td class="answerrow_cell">';
					// print the score field
					echo '<b>'.get_string("score", "languagelesson")." $iplus1:</b><br />";
					echo "<input type=\"text\" name=\"score[$i]\" value=\"$answer->score\" size=\"5\" />";
					echo '</td></tr></table></td></tr>';
					break;
				case LL_TRUEFALSE:
				case LL_MULTICHOICE:
				case LL_SHORTANSWER:
				//case LL_NUMERICAL:
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					// print answer field
					echo "<b>".get_string("answer", "languagelesson")." $iplus1:</b><br />\n";
					print_textarea(false, 1, 30, 0, 0, "answer[$i]");
					echo '</td><td class="answerrow_cell">';
					// print score field
					echo '<b>'.get_string("score", "languagelesson")." $iplus1:</b><br />";
					echo "<input type=\"text\" name=\"score[$i]\" value=\"0\" size=\"5\" />";
					echo '</td><td class="answerrow_cell">';
					// print feedback field
					echo "<b>".get_string("response", "languagelesson")." $iplus1:</b><br />\n";
					print_textarea(false, 1, 30, 0, 0, "response[$i]");
					echo '</td><td class="answerrow_cell">';
					// print jump field
					echo "<b>".get_string("jump", "languagelesson")." $iplus1:</b> \n";
					choose_from_menu($jump, "jumpto[$i]", 0, "");
					helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
					echo "</td></tr></table></td></tr>";
					break;
				case LL_BRANCHTABLE:
					echo '<tr><td><table><tr><td class="answerrow_cell">';
					echo "<b>".get_string("description", "languagelesson")." $iplus1:</b><br />\n";
					print_textarea(false, 1, 40, 0, 0, "answer[$i]");
					echo '</td><td class="answerrow_cell">';
					echo "<b>".get_string("jump", "languagelesson")." $iplus1:</b><br />\n";
					choose_from_menu($jump, "jumpto[$i]", 0, "");
					helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
					echo "</td></tr></table></td></tr>\n";
					break;
				default :
					break;
			}
		}

		// if it's a CLOZE, print out the feedback areas as well
		if ($page->qtype == LL_CLOZE) {
			$correctresponse = '';
			$wrongresponse = '';
			$correctjump = LL_NEXTPAGE;
			$wrongjump = 0;
			foreach ($feedbacks as $fb) {
				if ($fb->score > 0) {
					$correctresponse = $fb->response;
					$correctjump = $fb->jumpto;
					echo "<input type=\"hidden\" name=\"correctresponseid\" value=\"$fb->id\" />\n";
				} else {
					$wrongresponse = $fb->response;
					$wrongjump = $fb->jumpto;
					echo "<input type=\"hidden\" name=\"wrongresponseid\" value=\"$fb->id\" />\n";
				}
			}

			// print an empty spacing row
			echo '<tr><td></td></tr>';

			echo '<tr><td><table><tr><td class="answerrow_cell">';
			// print the correct response editor
			echo "<b><label for=\"edit-correctresponse\">".get_string('correctresponse', 'languagelesson')."
				:</label></b><br />\n";
			print_textarea(false, 1, 50, 0, 0, "correctresponse", $correctresponse);
			echo "<input type=\"hidden\" name=\"correctresponsescore\" value=\"1\" />";
			echo '</td><td class="answerrow_cell">';
			// print correct jump editor
			echo "<b>".get_string("correctanswerjump", "languagelesson").":</b><br />\n";
			choose_from_menu($jump, "correctanswerjump", $correctjump, "");
			helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
			echo "</td></tr></table></td></tr>";

			echo '<tr><td><table><tr><td class="answerrow_cell">';
			// print the wrong response editor
			echo "<b><label for=\"edit-wrongresponse\">".get_string('wrongresponse', 'languagelesson')."
				:</label></b><br />\n";
			print_textarea(false, 1, 50, 0, 0, "wrongresponse", $wrongresponse);
			echo "<input type=\"hidden\" name=\"correctresponsescore\" value=\"1\" />";
			echo '</td><td class="answerrow_cell">';
			// print wrong jump editor
			echo "<b>".get_string("wronganswerjump", "languagelesson").":</b><br />\n";
			choose_from_menu($jump, "wronganswerjump", $wrongjump, "");
			helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
			echo "</td></tr></table></td></tr>";
		}

		// if it's a MATCHING, print out customized feedback areas here
		// TODO: merge this with the above CLOZE code, once MATCHING feedbacks look more like CLOZE feedbacks
		if ($page->qtype == LL_MATCHING) {
			// print empty spacing row
			echo '<tr><td></td></tr>';

			$correctfeedback = '';
			$wrongfeedback = '';
			$correctscore = get_field('languagelesson', 'defaultpoints', 'id', $answer->lessonid);
			$wrongscore = 0;
			$correctjump = LL_NEXTPAGE;
			$wrongjump = 0;

			switch(count($feedbacks)) {
				case 2:
					if ($feedbacks[0]->score > $feedbacks[1]->score) {
						$correct = $feedbacks[0];
						$wrong = $feedbacks[1];
						$correctindex = count($answers) - 2;
						$wrongindex = count($answers) - 1;
					} else {
						$correct = $feedbacks[1];
						$wrong = $feedbacks[0];
						$correctindex = count($answers) - 1;
						$wrongindex = count($answers) - 2;
					}
					$correctfeedback = $correct->answer;
					$wrongfeedback = $wrong->answer;
					$correctscore = $correct->score;
					$wrongscore = $wrong->score;
					$correctjump = $correct->jumpto;
					$wrongjump = $wrong->jumpto;
					break;
				case 1:
					if ($feedbacks[0]->score) {
						$correctfeedback = $feedbacks[0]->answer;
						$correctscore = $feedbacks[0]->score;
						$correctjump = $feedbacks[0]->jumpto;
						$correctindex = count($answers) - 1;
						$wrongindex = count($answers);
					} else {
						$wrongfeedback = $feedbacks[0]->answer;
						$wrongscore = $feedbacks[0]->score;
						$wrongjump = $feedbacks[0]->jumpto;
						$correctindex = count($answers);
						$wrongindex = count($answers) - 1;
					}
					break;
				default:
					$correctindex = count($answers);
					$wrongindex = count($answers) + 1;
					break;
			}


			// print correct response area
			echo "<tr><td><table>";
			echo '<tr><td class="answerrow_cell"><b>'."<label for=\"edit-answer[$correctindex]\">".get_string('correctresponse',
					'languagelesson').":</label></b><br />\n";
			print_textarea(false, 1, 35, 0, 0, "answer[$correctindex]", $correctfeedback);
			echo '</td><td class="answerrow_cell">';
			// print correct score field
			echo '<b>'.get_string("correctanswerscore", "languagelesson").":<br /><input type=\"text\" name=\"score[$correctindex]\"
				value=\"$correctscore\" size=\"5\" />";
			echo '</td><td class="answerrow_cell">';
			// print correct jump field
			echo "<b>".get_string("correctanswerjump", "languagelesson").":</b><br />\n";
			choose_from_menu($jump, "jumpto[$correctindex]", $correctjump, "");
			helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
			echo "</td></tr></table>\n";
			echo '</td></tr>';

			// print wrong response area
			echo "<tr><td><table>";
			echo "<tr><td class=\"answerrow_cell\"><b><label for=\"edit-answer[$wrongindex]\">".get_string('wrongresponse',
					'languagelesson').":</label></b><br />\n";
			print_textarea(false, 1, 35, 0, 0, "answer[$wrongindex]", $wrongfeedback);
			echo '</td><td class="answerrow_cell">';
			// print wrong score field
			echo '<b>'.get_string("wronganswerscore", "languagelesson").":<br /><input type=\"text\" name=\"score[$wrongindex]\"
				value=\"$wrongscore\" size=\"5\" />";
			echo '</td><td class="answerrow_cell">';
			// print wrong jump field
			echo "<b>".get_string("wronganswerjump", "languagelesson").":</b><br />\n";
			choose_from_menu($jump, "jumpto[$wrongindex]", $wrongjump, "");
			helpbutton("jumpto", get_string("jump", "languagelesson"), "languagelesson");
			echo "</td></tr></table>\n";
			echo '</td></tr>';
		}
	}
	// close table and form
	?>
	</table><br />
	<!-- print out a hidden input for adding more answer fields -->
	<input type="hidden" name="maxanswers" value="<?php echo $maxanswers; ?>" />
	<script type="text/javascript">
		var actionInput = document.getElementById('actioninput');
		function setAction(action) {
			actionInput.value = action;
		}
	</script>
	<?php if ($page->qtype != LL_TRUEFALSE
				&& $page->qtype != LL_AUDIO
				&& $page->qtype != LL_VIDEO
				&& $page->qtype != LL_ESSAY) { ?>
	<input type="submit" onclick="setAction('editpage')" value="<?php print_string('add4moreanswerfields', 'languagelesson'); ?>" />
	<?php } ?>
	<br /><br />
	<input type="button" onclick="setAction('updatepage');" value="<?php print_string("redisplaypage", "lesson") ?>" 
		onclick="getElementById('editpage').redisplay.value=1;getElementById('editpage').submit();" />
	<input type="submit" onclick="setAction('updatepage');" value="<?php print_string("savepage", "lesson"); ?>" />
	<input type="submit" onclick="setAction('updatepage');" name="cancel" value="<?php print_string("cancel"); ?>" />
	</center>
	</fieldset>
	</form>
