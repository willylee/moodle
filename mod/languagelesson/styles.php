/***
 *** General styles (scope: all of languagelesson)
 ***/
 
.mod-languagelesson .contents {
    text-align: left;
}

.mod-languagelesson #layout-table {
    width: 100%;
}

.mod-languagelesson .edit_buttons form,
.mod-languagelesson .edit_buttons input {
    display: inline;
}

.mod-languagelesson .clock .content {
    text-align: center;
}

.mod-languagelesson .addlinks {
    font-size: .8em;
}

.mod-languagelesson .userinfotable .cell,
.mod-languagelesson .userinfotable .userpicture {
    vertical-align: middle;
}

.mod-languagelesson .invisiblefieldset.fieldsetfix {
    display: block;
    text-align: center;
}

.mod-languagelesson .invisiblefieldset.fieldsetfix tr {
    text-align: left;
}

/***
 *** Style for view.php
 ***/

.mod-languagelesson .instructions {
	text-align:left;
}

.mod-languagelesson #answertable {
	width:100%;
}

.mod-languagelesson #branchtable {
	width:100%;
}

.mod-languagelesson #submissionfeedbacktable {
	width:100%;
}

.mod-languagelesson .greenbar {
	background-color:#AAFFAA;
}

.mod-languagelesson .redbar {
	background-color:#FFAAAA;
}

.mod-languagelesson .graybar {
	background-color:#CCCCCC;
}

.mod-languagelesson .feedbackbar {
	width:90%;
	padding:5px;
	margin-bottom:5px;
}

/***
 Feedback table styles
 ***/
.mod-languagelesson .feedbackCell {
	border:1px solid #cccccc;
	padding:5px;
}
.mod-languagelesson .teacherInfoCell {
	width:40%;
}
.mod-languagelesson .textFeedbackCell {
	width:60%;
}
.mod-languagelesson .teacherName {
	font-weight:bold;
}
.mod-languagelesson .submissionTime {
	font-size:0.8em;
}
.mod-languagelesson .feedbackTable {
	width: 100%;
}
.mod-languagelesson .feedbackTable .subheader {
	font-weight:bold;
}
.mod-languagelesson .singleFeedback {
	width: 100%;
}
.mod-languagelesson .revletInstructions {
	font-style:italic;
	font-size:.9em;
}
#mod-languagelesson-view .teacherPics {
	clear: left;
	float: left;
	list-style: none;
	height: 3em;
	margin: 0;
	padding: 0;
	position: relative;
	left: 50%;
	top: 2px;
	text-align: center;
}

#mod-languagelesson-view .teacherPics li {
	display: block;
	float: left;
	position: relative;
	right: 50%;
	height: 34px;
	padding: 5px 5px 7px;
	border-top: 2px solid #bbbbbb;
	border-left: 2px solid #bbbbbb;
	border-right: 2px solid #bbbbbb;
}

.mod-languagelesson .activePic {
	border-bottom: 2px solid #eeeeee;
}
.mod-languagelesson .inactivePic {
	background-color: #dddddd;
}

/***
 *** Style for essay.php
 ***/

#mod-languagelesson-essay .graded {
    color:#DF041E;
}

#mod-languagelesson-essay .sent {
    color:#006600;
}

#mod-languagelesson-essay .ungraded {
    color:#999999;
}

#mod-languagelesson-essay .gradetable {
    margin-bottom: 20px;
}

#mod-languagelesson-essay .buttons {
    text-align: center;
}

/***
 *** Style for responses
 ***/

/* .response style is applied for both .correct and .incorrect */
.mod-languagelesson .response {
    padding-top: 10px;
}

/* for correct responses (can override .response) */
.mod-languagelesson .correct {
    /*color: green;*/
}

/* for incorrect responses (can override .response) */
.mod-languagelesson .incorrect {
    /*color: red;*/
}

/* for highlighting matches in responses for short answer regular expression (can override .incorrect) */
.mod-languagelesson .matches {
    /*color: red;*/
}

/***
 *** Slide show Style
 ***/

/* NOTE: background color, height and width are set in the lesson settings */
.mod-languagelesson .slideshow {  
    overflow: auto;
    padding-right: 16px; /* for the benefit of macIE5 only */ 
    /* \ commented backslash hack - recover from macIE5 workarounds, it will ignore the following rule */
    padding-right: 0;
    padding: 15px;
}

/***
 *** Left Menu Styles
 ***/
.mod-languagelesson .menu .content {
    padding: 0px;
}

.mod-languagelesson .menu .menuwrapper {
    max-height: 400px;
    overflow: auto;
    vertical-align: top;
    margin-bottom: 10px;
}

.mod-languagelesson .menu ul {
    list-style: none;
    padding: 5px 0px 0px 5px;
    margin: 0px;
}

.mod-languagelesson .menu li {
    padding-bottom: 5px;
}

.mod-languagelesson .leftmenu_selected_link {
}

.mod-languagelesson .leftmenu_not_selected_link {
}

.mod-languagelesson .skip {
    position: absolute;
    left: -1000em;
    width: 20em;
}


.mod-languagelesson .leftmenu_autograde_correct {
	color:green;
	margin-right:5px;
}

.mod-languagelesson .leftmenu_autograde_incorrect {
	color:red;
	margin-right:5px;
}

.mod-languagelesson .leftmenu_manualgrade {
	color:gray;
	margin-right:5px;
}

.mod-languagelesson .leftmenu_attempted {
	color:gray;
	margin-right:5px;
}


/***
 *** Lesson Buttons
 ***/

.mod-languagelesson .lessonbutton a {
  padding-left:1em;
  padding-right:1em;
}

.mod-languagelesson .lessonbutton a:link,
.mod-languagelesson .lessonbutton a:visited, 
.mod-languagelesson .lessonbutton a:hover {
    color: #000;
    text-decoration: none;
}

.mod-languagelesson .lessonbutton a:link,
.mod-languagelesson .lessonbutton a:visited {
  border-top: 1px solid #cecece;
  border-bottom: 2px solid #4a4a4a;
  border-left: 1px solid #cecece;
  border-right: 2px solid #4a4a4a;
}

.mod-languagelesson .lessonbutton a:hover {
  border-bottom: 1px solid #cecece;
  border-top: 2px solid #4a4a4a;
  border-right: 1px solid #cecece;
  border-left: 2px solid #4a4a4a;
}

/* Branch table buttons when displayed horizontally */
.mod-languagelesson .branchbuttoncontainer.horizontal div,
.mod-languagelesson .branchbuttoncontainer.horizontal form {
    display: inline;
}

/* Branch table buttons when displayed vertically */
.mod-languagelesson .branchbuttoncontainer.vertical .lessonbutton {
    padding: 5px;
}

/***
 *** Lesson Progress Bar
 ***    Default styles for this are very basic right now.
 ***    User is supposed to configure this to their liking (like using pictures)
 ***/

.mod-languagelesson .progress_bar {
    padding: 20px;
}

.mod-languagelesson .progress_bar_table {
    width: 80%;
    padding: 0px;
    margin: 0px;
}

.mod-languagelesson .progress_bar_completed {
    /*  Example Use of Image
    background-image: url(<?php echo $CFG->wwwroot ?>/mod/languagelesson/completed.gif);
    background-position: center;
    background-repeat: repeat-x;
    */
    background-color: green;
    padding: 0px;
    margin: 0px;    
}

.mod-languagelesson .progress_bar_todo {
    /*  Example Use of Image
    background-image: url(<?php echo $CFG->wwwroot ?>/mod/languagelesson/todo.gif);
    background-repeat: repeat-x;
    background-position: center;
    */
    background-color: red;
    text-align: left;
    padding: 0px;
    margin: 0px;
}

.mod-languagelesson .progress_bar_token {
    /*  Example Use of Image
    background-image: url(<?php echo $CFG->wwwroot ?>/mod/languagelesson/token.gif);
    background-repeat: repeat-none;
    */
    background-color: #000000;
    height: 20px;
    width: 5px;
    padding: 0px;
    margin: 0px;
}





/***
 *** Lesson Grader
 ***/

.mod-languagelesson .grader {
	margin-left:auto;
	margin-right:auto;
	border-collapse:separate;
	border-spacing:10px 0px;
}

.mod-languagelesson .item_cell {
	border: 1px black solid;
	width: 20px;
}

.mod-languagelesson .header_cell {
	/*height: 100px;*/
	border-left: 1px black solid;
}

.mod-languagelesson .question_name {
	display:none;
}

.mod-languagelesson #assign_grade_column_header_cell {
	width: 50px;
	border-left: 0px;
}

.mod-languagelesson #saved_grade_column_header_cell {
	width: 40px;
}

.mod-languagelesson .rotate-text {
	/* text-rotation properties for different browsers */
	/*-webkit-transform: rotate(270deg); /* Safari & Chrome */
	/*-moz-transform: rotate(270deg); /* Mozilla */
	/*filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=2); /* Netscape (IE) */
	/*-o-transform: rotate(270deg); /* Opera */
	
	text-align:center;
	vertical-align:center;
}

.mod-languagelesson #student_column_header_cell {
	border: none;
}

.mod-languagelesson .stuname_cell {
	
}

.mod-languagelesson .eob_cell {
	width: 2px;
	background-color: black;
}

.mod-languagelesson holistic_grade_box {
	width: 50px;
}

.mod-languagelesson #sentemailsnotifier {
	color: blue;
	text-align:center;
}



/* Legend styles */
.mod-languagelesson .leg_table {
	margin-left:auto;
	margin-right:auto;
}

.mod-languagelesson .legend {
	font-size:.9em;
}

.mod-languagelesson #multimedia_header {
	font-size:1em;
	text-align:center;
	border-top:2px black solid;
}

.mod-languagelesson .leg_color_cell {
	width:40px;
}

.mod-languagelesson .leg_name_cell {
	width: 160px;
}

.mod-languagelesson .noselect {
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-o-user-select: none;
	user-select: none;
}



/*
Grader cell colors

For medium-sized hex chart, see: http://html-color-codes.com/
For hardcore hex chart, see: http://www.december.com/html/spec/colorhex.html
*/

.mod-languagelesson .autocorrect {
	background-color:green;
}

.mod-languagelesson .autowrong {
	background-color:red;
}

.mod-languagelesson .no_submission {
	background-color:gray;
}

.mod-languagelesson .new {
	background-color:orange;
}

.mod-languagelesson .graded {
	background-color:DeepSkyBlue;
}

.mod-languagelesson .commented {
	background-color:yellow;
}

.mod-languagelesson .resubmit {
	background-color:purple;
}





/***
 *** Lesson respond_window
 ***/

#mod-languagelesson-respond_window #grade_area {
	width:100%;
	text-align:right;
	padding-right:15px;
}

#mod-languagelesson-respond_window #grade {
	width: 50px;
}

#mod-languagelesson-respond_window #student_picture_area {
	padding: 10px;
}

#mod-languagelesson-respond_window #text_response_area .fixed_height_row {
	height: 225px;
}

/* the FB submit button container div */
#mod-languagelesson-respond_window #feedback_submit_container {
	text-align: center;
	margin-left:auto;
	margin-right:auto;
}

#mod-languagelesson-respond_window #submitted_feedback_file_container {
	height:70px;
}

#mod-languagelesson-respond_window #submit_button_container {
	width:100%;
	text-align:center;
}

#mod-languagelesson-respond_window .submit_form_button {
	background-color:#fff;
	width: 125px;
}

#mod-languagelesson-respond_window .submit_row_cell {
	width:25%;
}

#mod-languagelesson-respond_window .nav_button {
	width: 150px;
	margin-left:auto;
	margin-right:auto;
}

#mod-languagelesson-respond_window #nav_table {
	width: 100%;
}

#mod-languagelesson-respond_window .thiscell {
	text-align:center;
}

/** Feedback table styles **/
#mod-languagelesson-respond_window #feedback_area {
	height: 100%;
}

#mod-languagelesson-respond_window .feedbackTable {
	height: 100%;
	width: 100%;
}

#mod-languagelesson-respond_window .teacherPics {
	list-style: none;
	height: 3em;
	margin: 0;
	padding: 0;
	text-align: center;
}

#mod-languagelesson-respond_window .teacherPics li {
	display: block;
	float: left;
	position: relative;
	top: 3px;
	height: 34px;
	padding: 5px 5px 7px;
	border-top: 2px solid #bbbbbb;
	border-right: 2px solid #bbbbbb;
	border-left: 2px solid #bbbbbb;
}

#mod-languagelesson-respond_window #top_half {
	width: 100%;
	height: 300px;
}

#mod-languagelesson-respond_window .contentRow {
	border: 1px solid #cccccc;
	padding: 10px;
	height: 100%;
}

#mod-languagelesson-respond_window #audioSubmissionString {
	text-align: center;
	font-style: italic;
	font-size: 1.5em;
	width: 50%;
	margin-left:auto;
	margin-right:auto;
}

#mod-languagelesson-respond_window #top_half .halfcell {
	width: 50%;
}


/** submission table styles **/
#mod-languagelesson-respond_window #studentSubmissionCell {
	padding: 20px;
}

#mod-languagelesson-respond_window #studentSubmissionTable {
	margin-left: auto;
	margin-right: auto;
	width: 90%;
	height: 100%;
}

#mod-languagelesson-respond_window #studentSubmissionTable td {
	border: 1px solid #cccccc;
	padding: 5px 10px;
}

#mod-languagelesson-respond_window #studentPicture {
	text-align: center;
	padding: 5px;
	height: 15%;
}

#mod-languagelesson-respond_window .subheader {
	text-align: center;
	font-weight: bold;
	margin-left: auto;
	margin-right: auto;
	margin-bottom: 10px;
}


/** added to fix hovering over selected question type issue **/
.tabrow0 .here a:hover {
	background-image: url("pix/tab/left.gif");
}
