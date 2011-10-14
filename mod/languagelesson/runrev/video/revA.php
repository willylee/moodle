<!-- Embed your revlets using code like this, to automatically guide the user to install the plugin, if it is not already installed -->
<?php
$pathchunks = explode('/', preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']));
$serverroot = array();
$i = 0;
while ($pathchunks[$i] != 'mod') {
	$serverroot[] = $pathchunks[$i];
	$i++;
}
$serverroot = implode('/', $serverroot);
?>
<script defer="defer" src="<?php echo $serverroot; ?>/filter/mediaplugin/eolas_fix.js" type="text/javascript">// <![CDATA[ ]]></script>
<div id="plugin" style="display:none">
<object classid="CLSID:B2EC94AF-4716-4300-824A-3314BF23664A" width=340 height=363>
	<param name="src" value="<?php echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']); ?>runrev/video/LLVideo.revlet"/>
	<param name="stack" value="LLVideo"/>
	<param name="requestedName" value=""/>
	<param name="instanceID" value=""/>
	<embed type="application/x-revolution"
		src="<?php echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']); ?>runrev/video/LLVideo.revlet"
        width=340 height=363
		xwidth=800 xheight=600
		stack="LLVideo"
		requestedName=""
		instanceID=""
