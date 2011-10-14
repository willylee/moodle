<?php // Id: grader.php
/******
	This page prints a holistic grading interface for the current lesson.
	
	TODO: rewrite this page! Needs logical ordering of things, and proper use of HTML
		  w/ integrated PHP, not all PHP echoing HTML
 ******/
 
	require_once('../../config.php');
	require_once($CFG->dirroot.'/mod/languagelesson/locallib.php');
    require_once($CFG->dirroot.'/mod/languagelesson/lib.php');
	 
	$id      = required_param('id', PARAM_INT);             // Course Module ID
	list($cm, $course, $lesson) = languagelesson_get_basics($id);

	require_login($course->id, false, $cm);
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
//// If a student somehow pulls this page, bail
    if (!has_capability('mod/languagelesson:grade', $context)) {
    	error('You do not have permission to access this page.');
    }
    

/// check the optional 'mode' flag, which marks if this page is being
/// viewed from the desktop application; if so, flag $ondesktop
    $mode = optional_param('mode', null, PARAM_INT);
    
    if ($mode == 1) {
    	$ondesktop = true;
    } else {
    	$ondesktop = false;
    }
	
    
 
/// print the basic head stuff of the page
    languagelesson_print_header($cm, $course, $lesson, 'grader');

/// print out the javascript functions to handle mouseover tooltips for question names
	print_tooltip_javascript();
	
	print_stulist_javascript();
	print_stugrade_javascript();
	
	print_hidden_questionname_field();
	
	
/// if user is viewing with the desktop app, print out the desktop javascript
	if ($ondesktop) {
		print_desktop_app_javascript();
	}
	
	?>
	
	<form action="grader_actions.php" method="post">
	
	
	<script type="text/javascript">
	
		function toggle(ID) {
			var box = document.getElementById(ID);
			box.checked = !box.checked;
		}
	
	</script>
	
	<input type="checkbox" id="alwaysshowqnamebox" checked="checked" />
	<label class="noselect" unselectable="on" for='alwaysshowqnamebox' onclick="toggle('alwaysshowqnamebox');"><?php echo get_string('legendshowquestionnamebox', 'languagelesson'); ?></label>
	
	<input type="checkbox" id="toggleallstudentsbox" onclick="select_all_students(this);" />
	<label class="noselect" unselectable="on" for='toggleallstudentsbox' onclick="toggle('toggleallstudentsbox');"><?php echo get_string('legendselectallstudentsbox', 'languagelesson'); ?></label>
	
	<input type="checkbox" id="useHTMLbox" name="useHTML" checked="checked" />
	<label class="noselect" unselectable="on" for='useHTMLbox' onclick="toggle('useHTMLbox');"><?php echo get_string('legenduseHTMLbox', 'languagelesson'); ?></label>
	
	
	<?php
	

/// establish the form and initialize basic values
	echo "<input type=\"hidden\" name=\"cmid\" value=\"$cm->id\" />";
	echo "<input type=\"hidden\" name=\"students_toemail\" id=\"stuidlist\" />";
	echo '<input type="hidden" name="savegradesflag" id="savegradesflag" value="0" />';
	echo '<input type="hidden" name="students_grades" id="stugradelist" />';
	echo '<input type="hidden" name="mode" value="'.$mode.'" />';


/// start the lesson map table
	print_simple_box_start('center');
	
/// print out completed action messages
	$savedgrades = optional_param('savedgrades', 0, PARAM_INT);
	$sentemails = optional_param('sentemails', 0, PARAM_INT);
	
	if ($savedgrades) {
		$message = get_string('gradessaved', 'languagelesson');
	} else if ($sentemails) {
		$message = get_string('emailssent', 'languagelesson');
	}
	
	if (isset($message)) { ?>
		<p class="mod-languagelesson" id="sentemailsnotifier"><?php echo $message; ?></p>
	<?php }
	
	echo '<table id="lesson_map_table" class="grader">';
	

/// print out column headers, including names of each question
	print_table_headers($lesson->id);

	$numqs = get_numqs($lesson->id); //pull number of questions in complete lesson
	$students = languagelesson_get_students($course->id); //pull list of students enrolled in the course
	$pages = languagelesson_get_sorted_pages($lesson->id); //pull list of sorted page ids for lesson
	$grades = get_grades_by_userid($lesson->id); //pull array of grades, mapped to userid, for the lesson

	// sort the students by last name, first name
	if (!$students) { error("Could not retrieve students for $course->fullname"); }
	$names = array();
	$students_by_name = array();
	foreach ($students as $student) {
		$stuname = strtolower($student->lastname . $student->firstname);
		$names[] = $stuname;
		$students_by_name[$stuname] = $student;
	}
	sort($names);

	foreach ($names as $name)
	{
		$student = $students_by_name[$name];
		//$attempts = get_attempts($student->id, $lesson->id, $numqs);
		$attempts = languagelesson_get_most_recent_attempts_sorted($lesson->id, $student->id, true, false, true);
		print_row($student, $attempts, $pages, $grades);
	}
    
    print_submission_buttons_row(get_offset($pages));
    
    print_table_end();
    
    //echo "<input type=\"submit\" onclick=\"update_stulist_input();\" value=\"Notify students\" />";
    
    
    
    echo "</form>";
    
/// print the lesson map legend
    print_legend();

/// end the page
    print_footer($course);


function get_offset($pages) {
	global $LL_QUESTION_TYPE;
	
	$count = 0;
	
	foreach ($pages as $page) {
		if (in_array($page->qtype, array_keys($LL_QUESTION_TYPE))
			|| $page->qtype == LL_ENDOFBRANCH) //account for branch separator cells
		{
			$count += 1;
		}
	}
	
	return $count;
}

function print_submission_buttons_row($offset) {
	
	$colspan = $offset + 1; //account for student name column as well
	
	echo "<tr> <td colspan=\"$colspan\"></td>";
	echo '<td> <input type="submit" onclick="update_stugrade_input();" value="'.get_string('assigngradesbutton','languagelesson').'" /> </td>';
	echo '<td></td>';
	echo '<td> <input type="submit" onclick="update_stulist_input();" value="Notify students" /> </td>';
}



function get_grades_by_userid($lessonid) {
	$dict = array();
	if ($grades = get_records("languagelesson_grades", "lessonid", $lessonid)) {
		foreach ($grades as $grade) {
			$dict[$grade->userid] = $grade->grade;
		}
	}
	return $dict;
}



function print_tooltip_javascript() {

	echo "<script type=\"text/javascript\">
				function showtooltip(e,text) {
					//var tooltip = document.getElementById('question_name_' + qnum);
					var tooltip = document.getElementById('question_name_field');
					//alert(text);
					tooltip.innerHTML = text;
					tooltip.style.display = 'block';
					
					var st = Math.max(document.body.scrollTop,document.documentElement.scrollTop);
					if(navigator.userAgent.toLowerCase().indexOf('safari')>=0)st=0; 
					var leftPos = e.clientX+10;
					if(leftPos<0)leftPos = 0;
					tooltip.style.left = leftPos + 'px';
					tooltip.style.top = e.clientY-tooltip.offsetHeight-5+st+ 'px';
				}
				
				function showtooltip_itemcell(e,text) {
					check = document.getElementById('alwaysshowqnamebox');
					if (check.checked) {
						showtooltip(e,text);
					}
				}

				function hidetooltip() {
					var tooltip = document.getElementById('question_name_field');
					tooltip.style.display = 'none';
				}
			</script>";

}



function print_stulist_javascript() {
	echo "<script type=\"text/javascript\">
				var stulist = Array();
				
				function select_all_students(sender) {
					var objs = document.getElementsByClassName('email_student_checkbox');
					
					if (objs[0].checked) {
						var value = 0;
					} else {
						var value = 1;
					}
					
					sender.checked = value;
					
				  /// clear any stored values in the stulist array
					stulist = Array();
					
					for (var i in objs) {
						objs[i].checked = value;
						if (value) { stulist.push(objs[i].value); }
					}
				}
				
				function update_stulist(e, sender) {
					var obj = e.sender;
					if (sender.checked) {
						stulist.push(sender.value);
					} else {
						stulist = remove_from_array(sender.value, stulist);
					}
				}
				
				function remove_from_array(item, array) {
					var index = array.indexOf(item);
					if (index != -1) {
						array.splice(index, 1);
					}
					return array;
				}
				
				function update_stulist_input() {
					var input_list = document.getElementById('stuidlist');
					input_list.value = stulist;
				}
			</script>";
}

function print_stugrade_javascript() {
	echo "<script type=\"text/javascript\">
			var stuGrades = {};
			
			function update_holistic_grade_array(sender, id) {
				var grade = Number(sender.value);
				
				/// if the box was made (or left) empty, get rid of the grade	
				if (sender.value == '') {
					stuGrades.delete(id);
					return;
				}
				
				/// if it's not a number, less than 0 or more than 100, tell them it won't work
				if (isNaN(grade) || grade < 0 || grade > 100) {
					sender.value = '';
					alert('".get_string('improperholisticgrade', 'languagelesson')."');
					return;
				}
				
				/// otherwise, save it
				stuGrades[id] = grade;
			}
			
			var thestring = \"\";
			function update_stugrade_input() {
				
				thestring = '';
				for (var ID in stuGrades) {
					//thestring += ID + \",\" stuGrades[ID] + \"|\";
					thestring += ID;
					thestring += ',';
					thestring += stuGrades[ID];
					thestring += '|';
				}
				
				var input_list = document.getElementById('stugradelist');
				input_list.value = thestring;
				var input_flag = document.getElementById('savegradesflag');
				input_flag.value = 1;
			}
			
		</script>";
}




function print_desktop_app_javascript() {
	global $CFG, $USER, $cm, $lesson;
	
	echo "<script type=\"text/javascript\">
			//<!--[CDATA[
			"
		  /// general path variables
		  ."var courseid = $lesson->course;
			var lessonid = $lesson->id;
			
			"
		  /*
		   * uploadparams -- params fed into the uploadtarget
		   *
		   * @param@ id => context ID for the languagelesson
		   * @param@ pageid => ID of the languagelesson page being graded
		   * @param@ userid => ID of the user grading (the teacher)
		   * @param@ attemptid => ID of the attempt being graded
		   * @param@ stufilepath => path to the recorded student file
		   * @param@ sesskey => the Moodle session key -- used for validation in the upload script
		   * @param@ mode => OPTIONAL switch to mark this as uploading feedback
		   */
		   ."var id = $cm->id;
			 var pageid = -1;
			 var userid = $USER->id;
			 var attemptid = -1;
			 var stufilepath = '';
			 var sesskey = '".sesskey()."';
			 var mode = 1;
			
			
			
			
			function get_download_path() {
				
				var path = '$CFG->wwwroot/file.php';
				path += stufilepath;
				return path;
				
			}
			
			
			 
			 function update_uploadparams_vals(pid, attid, studentID, studentfname) {
				pageid = pid;
				attemptid = attid;
				stufilepath = '/' + courseid + '/moddata/languagelesson/' + lessonid
							  + '/' + pageid + '/' + studentID + '/' + studentfname;
			 }
			 
			 
			 function get_uploadparams() {
				
				var params = new Array();
				params['id'] = id;
				params['pageid'] = pageid;
				params['userid'] = userid;
				params['attemptid'] = attemptid;
				params['stufilepath'] = stufilepath;
				params['sesskey'] = sesskey;
				params['mode'] = mode;
				
				return params;
				
			 }
			
			//]]-->
		 </script>";

}











function get_numqs($lessonid)
{
	/* returns the number of pages for the input lessonid */
	global $CFG;
	$numqs = get_record_sql("select distinct count(id) as ct_id
											from {$CFG->prefix}languagelesson_pages
											where lessonid=$lessonid");
	return $numqs->ct_id;
}




function print_hidden_questionname_field()
{
	echo '<div id="question_name_field" style="display:none; position:absolute; background-color:#ffffff"></div>';
}



function print_table_start()
{
	print_simple_box_start('center');
	echo '<table id="lesson_map_table" class="grader">';
}



function print_table_headers($lessonid)
{
	echo "<tr>
				<td id=\"student_column_header_cell\" class=\"grader header_cell\"><span class=\"rotate-text\">" . get_string('graderstudentcolumnname','languagelesson') . "</span></td>\n";
	
	$questions = languagelesson_get_sorted_pages($lessonid);
	
	$i=1;
	foreach ($questions as $question) {
		switch ($question->qtype) {
			case LL_CLUSTER:
			case LL_ENDOFCLUSTER:
			case LL_BRANCHTABLE:
				break;
			case LL_ENDOFBRANCH:
				echo "<td class=\"grader eob_cell\" />\n";
				break;
			default:
				echo "<td class=\"grader header_cell question_title\" onmouseover=\"showtooltip(event,'" . get_field('languagelesson_pages', 'title', 'id', $question->id) . "')\" onmouseout=\"hidetooltip();\">
					<!--<span class=\"question_name\" id=\"question_name_$i\">" . get_field('languagelesson_pages', 'title', 'id', $question->id) . "</span>-->
					<span class=\"rotate-text\">$i</span>
				</td>\n";
				$i++;
				break;
		}
	}
	
	echo "<td class=\"grader\" id=\"assign_grade_column_header_cell\">".get_string("assigngradecolumnheader", 'languagelesson')."</td>";
	echo "<td class=\"grader\" id=\"saved_grade_column_header_cell\">".get_string("savedgradecolumnheader", 'languagelesson')."</td>";
	
	echo "</tr>";
}




function print_table_end()
{
	echo '</table>';
	print_simple_box_end();
}





function print_row($student, $attempts, $pages, $grades)
{
	global $colors, $CFG, $cm, $ondesktop;
	echo "<tr>
				<td class=\"grader stuname_cell\">$student->firstname $student->lastname</td>\n";
	
	$question_num = 0;
	foreach ($attempts as $attempt)
	{
		$page = $pages[$question_num];
		switch ($page->qtype) {
			case LL_CLUSTER:
			case LL_ENDOFCLUSTER:
			case LL_BRANCHTABLE:
				$question_num++;
				continue 2;
				break;
			case LL_ENDOFBRANCH:
				echo "<td class=\"grader eob_cell\" />\n";
				$question_num++;
				continue 2;
				break;
			default:
				break;
		}
		

		$onclick = false;
		
		$cellcontents = '';
		
	/// assign block classes and onclick values appropriately
		if ($attempt->correct === null) {
		//student has not attempted the question yet
			$class = get_class_str('none');
		/*} else if ($attempt->qtype == LL_ESSAY) {
			$useranswer = unserialize($attempt->useranswer); //pull stored answer data
			if ($useranswer->graded) {
				$class = get_class_str('graded');
			} else {
				$class = get_class_str('new');
			}
			$cellcontents = get_cell_contents('essay');
		*/
		} else if ($attempt->qtype == LL_AUDIO
				   || $attempt->qtype == LL_VIDEO
				   || $attempt->qtype == LL_ESSAY) {
			
		/// pull the corresponding manual attempt record
			if (! $manattempt = get_record('languagelesson_manattempts', 'attemptid', $attempt->id)) {
				error('Could not fetch manual attempt record');
			}
			
		/// if this is NOT being viewed by the desktop application, then set $onclick
		/// to call the web grading interface
			if (!$ondesktop) {
				$onclick = "window.open('{$CFG->wwwroot}/mod/languagelesson/"
						   . "respond_window.php?attemptid={$attempt->id}&cmid={$cm->id}'"
						   . ",'Grading Language Lesson','width=950,height=800,toolbar=no,scrollbars=1');";
			}
		/// if page is being viewed by the desktop application, set its onclick
		/// value to call the update_uploadparams_vals function, so that the correct
		/// values can be fed to the desktop app by the get_uploadparams function
			else {
				$stufname = $manattempt->fname;
				$onclick = "update_uploadparams_vals($attempt->pageid, $attempt->id, "
						   ."$attempt->userid, '$stufname');";
			}
			
			if ($manattempt->resubmit) {
				$class = get_class_str('resubmit');
			} else if ($manattempt->graded) {
				if ($feedbacks = get_records('languagelesson_feedback', 'manattemptid', $manattempt->id)) {
					$class = get_class_str('commented');
				} else {
					$class = get_class_str('graded');
				}
			} else {
				$class = get_class_str('new');
			}
			
			if ($attempt->qtype == LL_AUDIO) {
				$cellcontents = get_cell_contents('audio');
			} else if ($attempt->qtype == LL_VIDEO) {
				$cellcontents = get_cell_contents('video');
			} else {
				$cellcontents = get_cell_contents('essay');
			}
			
		} else {
		//dealing with automatically graded question, so check its correct value
			if ($attempt->correct) {
				$class = get_class_str('autocorrect');
			} else {
				$class = get_class_str('autowrong');
			}
		}
		
	  /// set the onclick script
		if ($onclick) {
			$onclickprint = "onclick=\"$onclick\"";
		} else {
			$onclickprint = "";
		}
		
	  /// set the tooltip
		if ($attempt->correct === null) {
			$showqnamescript = "onmouseover=\"showtooltip_itemcell(event,'" . get_field('languagelesson_pages', 'title', 'id', $pages[$question_num]->id) . "')\" onmouseout=\"hidetooltip();\"";
		} else {
			$showqnamescript = "onmouseover=\"showtooltip_itemcell(event,'" . get_field('languagelesson_pages', 'title', 'id', $attempt->pageid) . "')\" onmouseout=\"hidetooltip();\"";
		}
		
	  /// set the cell id
		if ($attempt->id === null) {
			$id = "attempt_cell_null";
		} else {
			$id = "attempt_cell_{$attempt->id}";
		}
		
		echo "<td class=\"grader item_cell $class\" id=\"$id\" "
			 . "$onclickprint $showqnamescript>$cellcontents</td>\n";
		
		$question_num++;
	}
	
	echo "<td class=\"grader assign_grade_cell\"><input type=\"text\" size=\"2\" class=\"holistic_grade_box\" onblur=\"update_holistic_grade_array(this, $student->id);\" name=\"holistic_grade_box_$student->id\" /></td>";
	
	echo "<td class=\"grader saved_grade_cell\">".(in_array($student->id, array_keys($grades)) ? sprintf("%1.2f%%",$grades[$student->id]) : '')."</td>";
	
	echo "<td class=\"grader email_cell\"><input type=\"checkbox\" class=\"email_student_checkbox\" onclick=\"update_stulist(event, this);\" value=\"$student->id\" /></td>";
	
	echo "</tr>";
}




function get_class_str($input) {
	return get_string("grader{$input}",'languagelesson');
}


function get_cell_contents($type) {
	return get_string("cellcontents_$type", 'languagelesson');
}




function print_legend() {
	
	print_simple_box_start('center');
	
	echo "<table id=\"legend_table\" class=\"legend leg_table\">
				<tr>
					<td class=\"legend leg_color_cell " . get_class_str('none') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendnone', 'languagelesson') . "</td>
				
					<td class=\"legend leg_color_cell " . get_class_str('autocorrect') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendautocorrect', 'languagelesson') . "</td>
					
					<td class=\"legend leg_color_cell " . get_class_str('autowrong') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendautowrong', 'languagelesson') . "</td>
					".
					/*<td class=\"legend leg_color_cell " . get_class_str('essaynew') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendessaynew', 'languagelesson') . "</td>
					
					<td class=\"legend leg_color_cell " . get_class_str('essaygraded') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendessaygraded', 'languagelesson') . "</td>
					*/
					"
					<td class=\"legend leg_color_cell " . get_class_str('new') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendnew', 'languagelesson') . "</td>
					
					<td class=\"legend leg_color_cell " . get_class_str('graded') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendgraded', 'languagelesson') . "</td>
					
					<td class=\"legend leg_color_cell " . get_class_str('commented') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendcommented', 'languagelesson') . "</td>
					
					<td class=\"legend leg_color_cell " . get_class_str('resubmit') . "\" />
					<td class=\"legend leg_name_cell\">" . get_string('legendresubmit', 'languagelesson') . "</td>
				</tr>
			</table>";	
	
	print_simple_box_end();
	
}

?>
