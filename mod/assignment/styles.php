#mod-assignment-submissions .feedback .content,
#mod-assignment-submissions .feedback .topic,
#mod-assignment-submissions .feedback .picture
{
  padding: 10px;
  border-width:1px;
  border-style:solid;
  border-color:#DDDDDD;
}

#mod-assignment-submissions form#options div {
  text-align:right;
  margin-left:auto;
  margin-right:20px;
}

.mod-assignment .feedback .files {
  float: right;
  background-color: #EFEFEF;
  padding:5px;
}

.mod-assignment .feedback .grade,
.mod-assignment .feedback .outcome,
.mod-assignment .feedback .finalgrade {
  float: right;
}

.mod-assignment .feedback .disabledfeedback {
  width: 500px;
  height: 250px;
}

.mod-assignment .feedback .from {
  float: left;
}

.mod-assignment .feedback .time {
  font-size: 0.8em;
}

.mod-assignment .late {
  color: red;
}

.mod-assignment .files img {
  margin-right: 4px;
}

.mod-assignment .files a {
  white-space:nowrap;
}

#mod-assignment-submissions .generaltable .r1 {
  background-color: #FFFFFF;
}

#mod-assignment-submissions .header .commands {
  display: inline;
}

#mod-assignment-submissions .s0 {
  background: #FFD991;
}

#mod-assignment-submissions table.submissions td,
#mod-assignment-submissions table.submissions th
{
  border-width: 1px;
  border-style: solid;
  border-color: #DDDDDD;
  vertical-align: middle;
  padding-left: 5px;
  padding-right: 5px;
}

#mod-assignment-submissions .submissions .grade {
  text-align: right;
  font-weight:bold;
}

#mod-assignment-submissions .picture {
  width: 35px;
}

#mod-assignment-submissions .fullname {
  text-align: left;
}

#mod-assignment-submissions .timemodified,
#mod-assignment-submissions .timemarked
{
  text-align: left;
  font-size: 0.9em;
}

#mod-assignment-submissions .status {
  text-align: center;
}

#mod-assignment-submissions .submissions .outcome,
#mod-assignment-submissions .submissions .finalgrade {
  text-align: right;
}

#mod-assignment-view #online .singlebutton {
  text-align: center;
}

#mod-assignment-view #dates {
  font-size: 0.8em;
  margin-top: 30px;
  margin-bottom: 30px;
}

#mod-assignment-view #dates .c0{
  text-align:right;
  font-weight:bold;
}

#mod-assignment-view .feedback {
  border-width:1px;
  border-style:solid;
  border-color:#DDDDDD;
  margin-top: 15px;
  width: 80%;
  margin-left: 10%;
  margin-right: 10%;
}

#mod-assignment-view .feedback .topic {
  padding: 4px;
  border-style:solid;
  border-width: 0px;
  border-bottom-width: 1px;
  border-color:#DDDDDD;
}

#mod-assignment-view .feedback .fullname {
  font-weight: bold;
}

#mod-assignment-view .feedback .date {
  font-size: 0.8em;
}

#mod-assignment-view .feedback .content {
  padding: 4px;
}

#mod-assignment-view .feedback .grade {
  text-align: right;
  font-weight:bold;
}

#mod-assignment-view .feedback .left {
  width: 35px;
  padding: 4px;
  text-align: center;
  vertical-align: top;
}

#mod-assignment-submissions .qgprefs #optiontable {
  text-align:right;
  margin-left:auto;
}

#mod-assignment-submissions .fgcontrols {
  margin-top: 1em;
  text-align:center;
}

#mod-assignment-submissions .fgcontrols .fastgbutton{
  margin-top: 0.5em;
}




/*******************************************/
// ADDED MULTIMEDIA STYLES
#mod-assignment-view .teacherPic {
	display: inline;
	margin-left: 5px;
	margin-right: 5px;
	width: 16px;
	height: 16px;
}

#mod-assignment-view .teacherPic .inactivePic {
	border-style: outset;
	border-width: 3px;
	border-color: #dddddd;
}

#mod-assignment-view .teacherPic .activePic {
	border-style: inset;
	border-width: 3px;
	border-color: #0000ff;
}

#mod-assignment-view .teacherPics {
	clear: left;
	float: left;
	list-style: none;
	height: 3em;
	margin: 0;
	padding: 0;
	position: relative;
	left: 50%;
	text-align: center;
}

#mod-assignment-view .teacherPics li {
	display: block;
	float: left;
	margin-left: 5px;
	margin-right: 5px;
	position: relative;
	right: 50%;
	height: 34px;
	padding: 5px 5px 7px;
	border-top: 2px solid #bbbbbb;
	border-right: 2px solid #bbbbbb;
	border-left: 2px solid #bbbbbb;
}

#mod-assignment-view .teacherPics li .activePic {
	border-bottom: 2px solid #eeeeee;
}

#mod-assignment-view .teacherPics li .inactivePic {
	background-color: #dddddd;
}

#mod-assignment-view .feedbackBlock {
	border: 1px solid #cccccc;
	padding: 10px;
	width: 90%;
	margin-left: auto;
	margin-right: auto;
}

/*=======================*/
/* HACK */
/* Copying styles to no-cascading names, as behavior in loading the above styles is erratic, at best */
#mod-assignment-view .activePic {
	border-bottom: 2px solid #eeeeee;
}
#mod-assignment-view .inactivePic {
	background-color: #dddddd;
}
/*=======================*/

#mod-assignment-view .revletInstructions {
	font-style: italic;
	font-size: .8em;
}

/*******************************************/
