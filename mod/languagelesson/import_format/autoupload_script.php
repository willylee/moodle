<?php

require_once("../../../config.php");
require_once("$CFG->dirroot/lib/moodlelib.php");
require_once("$CFG->dirroot/lib/uploadlib.php");


//print_r($_FILES);

$filecount = $_POST['filecount'];

if ($filecount != count($_FILES)) {
	echo "<br />filecount is $filecount<br />";
	echo "count(_FILES) is ".count($_FILES)."<br />";
	error('The expected number of files is not present.');
}

/// pull the course module ID for proper redirecting
$cmid = $_POST['cmid'];

/// pull the comma-separated string list of filenames that were
/// expected as uploads for checking against the actually-uploaded
/// files
$expected_fnames = $_POST['expected_filenames'];

/// pull the upload destination string
$destination = $_POST['destination'];


$expected_fnames = explode(',', $expected_fnames);
$actual_fnames = array();
foreach ($_FILES as $file) {
	$actual_fnames[] = $file['name'];
}

$missing_fnames = array();
$extra_fnames = array();
foreach ($expected_fnames as $fname) {
	if (! in_array($fname, $actual_fnames)) {
		$missing_fnames[] = $fname;
	}
}
foreach ($actual_fnames as $fname) {
	if (! in_array($fname, $expected_fnames)) {
		$extra_fnames[] = $fname;
	}
}

$flag = false;
if (count($missing_fnames) > 0 || count($extra_fnames) > 0) {
	$flag = true;
}


if ($flag) {
	echo "Fnames didn't match.";
	//die('');
}


$um = new upload_manager('',true,false,$cid,false);
if (! $um->process_file_uploads($destination)) {
	echo "OH SHIT";
	error_log("File upload failed.");
	die('');
}





function print_continue_options($cmid) {
	
	$lessonid = get_lessonid_from_cmid($cmid);
	
	$delete = '<form action="autoupload_script.php" method="post">';
	$delete .= '<input type="hidden" name="lessonid" value="' . $lessonid . '" />';
	$delete .= '<input type="hidden" name="cmid" value="' . $cmid . '" />';
	$delete .= '<input type="hidden" name="upload_folder" value="' . $destination . '" />';
	$delete .= 'Click <input type="submit" value="here" /> to delete everything uploaded
				for this lesson and return to the import screen.';
	$delete .= '</form>';
	
	$continue = '<form action="../view.php" method="get">';
	$continue .= '<input type="hidden" name="id" value="' . $cmid . '" />';
	$continue .= '<input type="submit" value="Continue" />';
	$continue .= '</form>';
	
	
	echo $delete . "<br /><br />" . $continue;

}







//$um->print_upload_log();


redirect("../view.php?id=$cmid");


?>