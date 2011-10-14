<?php // $Id$
/**
 *  Action for adding a question page.  Prints an HTML form.
 *
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package languagelesson
 **/
    $CFG->pagepath = 'mod/languagelesson/addpage';
    
    // first get the preceding page
    $pageid = required_param('pageid', PARAM_INT);
    $qtype = optional_param('qtype', LL_MULTICHOICE, PARAM_INT);
    
    // set of jump array
    $jump = array();
    $jump[0] = get_string("thispage", "languagelesson");
    $jump[LL_NEXTPAGE] = get_string("nextpage", "languagelesson");
    $jump[LL_PREVIOUSPAGE] = get_string("previouspage", "languagelesson");
    $jump[LL_EOL] = get_string("endoflesson", "languagelesson");
    if(languagelesson_display_branch_jumps($lesson->id, $pageid)) {
        $jump[LL_UNSEENBRANCHPAGE] = get_string("unseenpageinbranch", "languagelesson");
        $jump[LL_RANDOMPAGE] = get_string("randompageinbranch", "languagelesson");
    }
    if(languagelesson_display_cluster_jump($lesson->id, $pageid)) {
        $jump[LL_CLUSTERJUMP] = get_string("clusterjump", "languagelesson");
    }
    if (!optional_param('firstpage', 0, PARAM_INT)) {
        $linkadd = "";      
        $apageid = get_field("languagelesson_pages", "id", "lessonid", $lesson->id, "prevpageid", 0);
        
        while (true) {
            if ($apageid) {
                $title = get_field("languagelesson_pages", "title", "id", $apageid);
                $jump[$apageid] = strip_tags(format_string($title,true));
                $apageid = get_field("languagelesson_pages", "nextpageid", "id", $apageid);
            } else {
                // last page reached
                break;
            }
        }
    } else {
        $linkadd = "&amp;firstpage=1";
    }

	// they may have switched question types or added answer fields, so pull anything that was submitted
    $data = data_submitted();

	// set the number of answer fields according to the submitted data
	// defaults to 4
	if (isset($data->maxanswers)) {
		$maxanswers = $data->maxanswers + 4;
	} else {
		$maxanswers = 4;
	}

    // give teacher a blank proforma
    print_heading_with_help(get_string("addaquestionpage", "languagelesson"), "overview", "lesson");
    ?>
    <form id="form" method="post" action="lesson.php" class="addform">
    <fieldset class="invisiblefieldset fieldsetfix">
    <input type="hidden" name="id" value="<?php echo $cm->id ?>" />
    <input type="hidden" name="action" id="actioninput" />
    <input type="hidden" name="pageid" value="<?php echo $pageid ?>" />
	<!-- print out a hidden input for adding more answer fields -->
	<input type="hidden" name="maxanswers" value="<?php echo $maxanswers; ?>" />
    <input type="hidden" name="sesskey" value="<?php echo $USER->sesskey ?>" />
      <?php
        echo '<b>'.get_string("questiontype", "languagelesson").":</b> \n";
        echo helpbutton("questiontypes", get_string("questiontype", "languagelesson"), "lesson")."<br />";
        languagelesson_qtype_menu($LL_QUESTION_TYPE, $qtype, 
                          "lesson.php?id=$cm->id&amp;action=addpage&amp;pageid=".$pageid.$linkadd);

		// display the qoption checkbox for those question types that require it
        if ( $qtype == LL_SHORTANSWER
				|| $qtype == LL_MULTICHOICE 
				|| $qtype == LL_CLOZE ) {
            echo '<p>';
            if ($qtype == LL_SHORTANSWER) {
                $qoptionstr = get_string('casesensitive', 'languagelesson');
            } else if ($qtype == LL_MULTICHOICE) {
                $qoptionstr = get_string('multianswer', 'languagelesson');
            } else {
				$qoptionstr = get_string('casesensitive', 'languagelesson');
			}
            echo "<label for=\"qoption\"><strong>$qoptionstr</strong></label>";
			echo "<input type=\"checkbox\" id=\"qoption\" name=\"qoption\" value=\"1\" "
				. ((isset($data->qoption) && $data->qoption) ? "checked=\"checked\" " : '') . "/>";
            helpbutton("questionoption", get_string("questionoption", "languagelesson"), "lesson");
            echo '</p>';
        }
    ?>
    <table cellpadding="5" class="generalbox boxaligncenter" border="1">
    <tr valign="top">
    <td><b><label for="title"><?php print_string("pagetitle", "lesson"); ?>:</label></b><br />
    <input type="text" id="title" name="title" size="80" value="<?php if (isset($data->title)) { echo $data->title; } ?>" /></td></tr>
    <?php
    echo "<tr><td><b>";
    echo get_string("pagecontents", "languagelesson").":</b><br />\n";
    print_textarea($usehtmleditor, 25,70, 630, 400, "contents", ((isset($data->contents)) ? $data->contents : ''));
    if ($usehtmleditor) {
        use_html_editor("contents");
    }
    echo "</td></tr>\n";
    switch ($qtype) {
        case LL_TRUEFALSE :
            for ($i = 0; $i < 2; $i++) {
                $iplus1 = $i + 1;
                echo "<tr><td><b>".get_string("answer", "languagelesson")." $iplus1:</b><br />\n";
                print_textarea(false, 6, 70, 630, 300, "answer[$i]", ((isset($data->answer[$i]) ? $data->answer[$i] : '')));
                echo "</td></tr>\n";
                echo "<tr><td><b>".get_string("response", "languagelesson")." $iplus1:</b><br />\n";
                print_textarea(false, 6, 70, 630, 300, "response[$i]", ((isset($data->response[$i]) ? $data->response[$i] : '')));
                echo "</td></tr>\n";
                echo "<tr><td><b>".get_string("jump", "languagelesson")." $iplus1:</b> \n";
                if ($i) {
                    // answers 2, 3, 4... jumpto this page
                    choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : 0), "");
                } else {
                    // answer 1 jumpto next page
                    choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : LL_NEXTPAGE), "");
                }
                helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
                if ($i) {
                    echo get_string("score", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
						."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '0')."\" size=\"5\" />";
                } else {
                    echo get_string("score", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
						."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '1')."\" size=\"5\" />";
                }
                echo "</td></tr>\n";
            }
            break;
        case LL_AUDIO :
        case LL_VIDEO :
        case LL_ESSAY :
                echo "<tr><td><b>".get_string("jump", "languagelesson").":</b> \n";
                choose_from_menu($jump, "jumpto[0]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : LL_NEXTPAGE), "");
                helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
				echo get_string("score", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
					."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '1')."\" size=\"5\" />";
                echo "</td></tr>\n";
            break;
        case LL_MATCHING :
            for ($i = 0; $i < $maxanswers+2; $i++) {
                $icorrected = $i + 1;
                //if ($i == 0) {
                if ($i == $maxanswers) {
                	echo "<tr><td style=\"height:25px;\"></td></tr>";
                
                    echo "<tr><td><b>".get_string("correctresponse", "languagelesson").":</b><br />\n";
                    print_textarea(false, 6, 70, 630, 300, "answer[$i]", ((isset($data->answer[$i]) ? $data->answer[$i] : '')));
                    echo "</td></tr>\n";
                    
                    echo "<tr><td><b>".get_string("correctanswerjump", "languagelesson").":</b> \n";
                    choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : LL_NEXTPAGE), "");
                    helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
					echo get_string("correctanswerscore", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
						."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '1')."\" size=\"5\" />";
                    echo "</td></tr>\n";
                //} elseif ($i == 1) {
                } elseif ($i == $maxanswers+1) {
                    echo "<tr><td><b>".get_string("wrongresponse", "languagelesson").":</b><br />\n";
                    print_textarea(false, 6, 70, 630, 300, "answer[$i]", ((isset($data->answer[$i]) ? $data->answer[$i] : '')));
                    echo "</td></tr>\n";
                    
                    echo "<tr><td><b>".get_string("wronganswerjump", "languagelesson").":</b> \n";
                    choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : 0), "");
                    helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
					echo get_string("wronganswerscore", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
						."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '0')."\" size=\"5\" />";
                    echo "</td></tr>\n";
                    
                } else {                                                
                    echo "<tr><td><b>".get_string("answer", "languagelesson")." $icorrected:</b><br />\n";
                    print_textarea(false, 6, 70, 630, 300, "answer[$i]", ((isset($data->answer[$i]) ? $data->answer[$i] : '')));
                    echo "</td></tr>\n";
                    echo "<tr><td><b>".get_string("matchesanswer", "languagelesson")." $icorrected:</b><br />\n";
                    print_textarea(false, 6, 70, 630, 300, "response[$i]", ((isset($data->response[$i]) ? $data->response[$i] : '')));
                    echo "</td></tr>\n";
                }
            }
            break;
        case LL_SHORTANSWER :
        //case LL_NUMERICAL :
        case LL_MULTICHOICE :
            // default code
            for ($i = 0; $i < $maxanswers; $i++) {
                $iplus1 = $i + 1;
                echo "<tr><td><b>".get_string("answer", "languagelesson")." $iplus1:</b><br />\n";
                print_textarea(false, 6, 70, 630, 300, "answer[$i]", ((isset($data->answer[$i]) ? $data->answer[$i] : '')));
                echo "</td></tr>\n";
                echo "<tr><td><b>".get_string("response", "languagelesson")." $iplus1:</b><br />\n";
                print_textarea(false, 6, 70, 630, 300, "response[$i]", ((isset($data->response[$i]) ? $data->response[$i] : '')));
                echo "</td></tr>\n";
                echo "<tr><td><b>".get_string("jump", "languagelesson")." $iplus1:</b> \n";
                if ($i) {
                    // answers 2, 3, 4... jumpto this page
                    choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : 0), "");
                } else {
                    // answer 1 jumpto next page
                    choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : LL_NEXTPAGE), "");
                }
                helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
                if ($i) {
					echo get_string("score", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
						."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '0')."\" size=\"5\" />";
                } else {
					echo get_string("score", "languagelesson")." $iplus1: <input type=\"text\" name=\"score[$i]\""
						."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '1')."\" size=\"5\" />";
                }
                echo "</td></tr>\n";
            }
            break;
		case LL_CLOZE :
			for ($i = 0; $i < $maxanswers; $i++) {
				$iplus1 = $i + 1;
				echo "<tr><td>";

				// print the drop-down checkbox
				$a->number = $iplus1;
				echo "<label for=\"dropdown[$i]\">".get_string('usedropdown', 'languagelesson', $a)."</label>";
				echo "<input type=\"checkbox\" id=\"dropdown[$i]\" name=\"dropdown[$i]\" value=\"1\" />";
				echo "</td></tr>";

				// print the actual answer input area
				echo "<tr><td><b>".get_string("answer", "languagelesson")." $iplus1:</b><br />\n";
				print_textarea(false, 6, 70, 630, 300, "answer[$i]", ((isset($data->answer[$i]) ? $data->answer[$i] : '')));
				echo "</td></tr>\n";

				// print the score input
				echo "<tr><td>";
				echo get_string("score", "languagelesson")." $iplus1: "
					."<input type=\"text\" name=\"score[$i]\" size=\"5\" "
					."value=\"".((isset($data->score[$i])) ? $data->score[$i] : '1')."\" />";
				echo "</td></tr>";
			}


			echo "<tr><td style=\"height:25px;\"></td></tr>";
		
			echo "<tr><td><b>".get_string("correctresponse", "languagelesson").":</b><br />\n";
			print_textarea(false, 6, 70, 630, 300, "correctresponse", ((isset($data->correctresponse) ? $data->correctresponse : '')));
			echo "<input type=\"hidden\" name=\"correctresponsescore\" value=\"1\" />";
			echo "</td></tr>\n";
			
			echo "<tr><td><b>".get_string("correctanswerjump", "languagelesson").":</b> \n";
			choose_from_menu($jump, "correctanswerjump", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : LL_NEXTPAGE), "");
			helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
			echo "</td></tr>\n";

			$i++;

			echo "<tr><td><b>".get_string("wrongresponse", "languagelesson").":</b><br />\n";
			print_textarea(false, 6, 70, 630, 300, "wrongresponse", ((isset($data->wrongresponse) ? $data->wrongresponse : '')));
			echo "<input type=\"hidden\" name=\"wrongresponsescore\" value=\"0\" />";
			echo "</td></tr>\n";
			
			echo "<tr><td><b>".get_string("wronganswerjump", "languagelesson").":</b> \n";
			choose_from_menu($jump, "wronganswerjump", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : 0), "");
			helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
			echo "</td></tr>\n";
			break;

		default :
			break;
    }
    // close table and form
    ?>
    </table><br />
	<script type="text/javascript">
		var actionInput = document.getElementById('actioninput');
		function setAction(action) {
			actionInput.value = action;
		}
	</script>
	<input type="submit" onclick="setAction('addpage');" value="<?php print_string('add4moreanswerfields', 'languagelesson'); ?>" />
	<br /><br />
    <input type="submit" onclick="setAction('insertpage');" value="<?php print_string("addaquestionpage", "languagelesson") ?>" />
    <input type="submit" onclick="setAction('insertpage');" name="cancel" value="<?php print_string("cancel") ?>" />
    </fieldset>
    </form>
