<?php // $Id: addbranchtable.php 676 2011-09-16 19:53:22Z griffisd $
/**
 *  Action for adding a branch table.  Prints an HTML form.
 *
 * @version $Id: addbranchtable.php 676 2011-09-16 19:53:22Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package languagelesson
 **/

    $CFG->pagepath = 'mod/languagelesson/addbranchtable';
    
	
	// branch jump options include pages fitting all of the following conditions
	// - following where this BT is getting inserted
	// - if this BT is nested (inserted inside a branch of another BT), only include the pages in the same branch
	// - the jump options for branch n must follow the page chosen as the jump for branch n-1 (dynamically determined)
	// - if no options fit all of these conditions for a given branch, its only option is '--', which puts it at the end of the
	// lesson/branch containing the BT


    // first get the preceding page
    $pageid = required_param('pageid', PARAM_INT);
	// now use that to pull the list of pages meeting the conditions:
	// - higher ordering value
	// - if the preceding page is in a branch:
	//   - in the same branch
	//   - not the ENDOFBRANCH page
    if (!$firstpage = optional_param('firstpage', 0, PARAM_INT)) {
		$prevPage = get_record('languagelesson_pages', 'id', $pageid);
		$pages = get_records_select('languagelesson_pages', "ordering > $prevPage->ordering and lessonid=$lesson->id " .
															(($prevPage->branchid) ? "and branchid = $prevPage->branchid
															 						  and qtype != ".LL_ENDOFBRANCH : ''),
									'ordering');
	}

	// now use this to generate a Javascript base array, chunks of which can then be selected to dynamically update later branching
	// options as earlier branches are chosen
	?>
	<script type="text/javascript">

	var basePages = array();
	basePages[0] = '--';
	<?php
	foreach ($pages as $page) {
		echo "basePages[$page->id] = '$page->title';\n";
	}
	?>

	</script>
	<?php

	$pageid = required_param('pageid', PARAM_INT);
	$firstpage = optional_param('firstpage', 0, PARAM_INT);
    
    // set of jump array
    $jump = array();
	// init to include only the default endoflesson choice
    $jump[0] = '--';
	// now, if there are pages after where this BT is getting created, populate the list with them
	if (!$firstpage && $pages) {
		//$pages = get_records('languagelesson_pages', 'lessonid', $lesson->id, 'ordering');
		foreach ($pages as $apageid => $apage) {
			$jump[$apageid] = $apage->title;
		}
    }

	// they may have added answer fields, so pull anything that was submitted
    $data = data_submitted();

	// set the number of answer fields according to the submitted data
	// defaults to 4
	if (isset($data->maxanswers)) {
		$maxanswers = $data->maxanswers + 4;
	} else {
		$maxanswers = 4;
	}

    // give teacher a blank proforma
    print_heading_with_help(get_string("addabranchtable", "languagelesson"), "overview", "lesson");
    ?>
    <form id="form" method="post" action="lesson.php" class="addform">
    <fieldset class="invisiblefieldset fieldsetfix">
    <input type="hidden" name="id" value="<?PHP echo $cm->id ?>" />
    <input type="hidden" name="action" id="actioninput" value="insertpage" />
    <input type="hidden" name="pageid" value="<?PHP echo $pageid ?>" />
    <input type="hidden" name="qtype" value="<?PHP echo LL_BRANCHTABLE ?>" />
	<!-- print out a hidden input for adding more answer fields -->
	<input type="hidden" name="maxanswers" value="<?php echo $maxanswers; ?>" />
    <input type="hidden" name="sesskey" value="<?PHP echo $USER->sesskey ?>" />
    <table class="generalbox boxaligncenter" cellpadding="5" border="1">
    <tr valign="top">
    <td><strong><label for="title"><?php print_string("pagetitle", "lesson"); ?>:</label></strong><br />
    <input type="text" id="title" name="title" size="80" value="<?php echo ((isset($data->title)) ? $data->title : ''); ?>" /></td></tr>
    <?php
    echo "<tr><td><strong>";
    echo get_string("pagecontents", "languagelesson").":</strong><br />\n";
    print_textarea($usehtmleditor, 25,70, 630, 400, "contents", ((isset($data->contents)) ? $data->contents : ''));
    if ($usehtmleditor) {
        use_html_editor("contents");
    }
    echo "</td></tr>\n";
    echo "<tr><td>\n";
    echo "<div class=\"boxaligncenter addform\"><input name=\"layout\" type=\"checkbox\" value=\"1\""
		. ((isset($data->layout) && !($data->layout)) ? '' : "checked=\"checked\"") . " />";
    echo get_string("arrangebuttonshorizontally", "languagelesson")."\n";
    echo "</div>\n";
    echo "</td></tr></table>\n";
	?>

    <table class="generalbox boxaligncenter" cellpadding="5" border="1">
	<?php
    for ($i = 0; $i < $maxanswers; $i++) {
        $iplus1 = $i + 1;
        echo '<tr><td><table><tr><td class="answerrow_cell">';
		echo "<b>".get_string("branchtitle", "languagelesson")." $iplus1:</b><br />\n";
        print_textarea(false, 1, 40, 0, 0, "answer[$i]", ((isset($data->answer[$i])) ? $data->answer[$i] : '')); 
		echo '</td><td class="answerrow_cell">';
        echo "<b>".get_string("jump", "languagelesson")." $iplus1:</b><br />\n";
		choose_from_menu($jump, "jumpto[$i]", ((isset($data->jumpto[$i])) ? $data->jumpto[$i] : LL_NEXTPAGE), "");
        helpbutton("jumpto", get_string("jump", "languagelesson"), "lesson");
        echo "</td></tr></table></td></tr>\n";
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
	<input type="submit" onclick="setAction('addbranchtable');" value="<?php print_string('add4moreanswerfields', 'languagelesson');
		?>" />
	<?php if ($firstpage) { ?><input type="hidden" name="firstpage" value="1" /><?php } ?>
	<br /><br />
    <input type="submit" value="<?php  print_string("addabranchtable", "lesson") ?>" />
    <input type="submit" name="cancel" value="<?php  print_string("cancel") ?>" />
    </fieldset>
    </form>
