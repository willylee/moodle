<!-- Embed your revlets using code like this, to automatically guide the user to install the plugin, if it is not already installed -->
<div id="plugin" style="display:none">
<object classid="CLSID:B2EC94AF-4716-4300-824A-3314BF23664A" width=803 height=140>
	<param name="src" value="<?php echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']); ?>type/audio/LLAudio2.revlet"/>
	<param name="stack" value="LLAudio2"/>
	<param name="requestedName" value=""/>
	<param name="instanceID" value=""/>
	<embed type="application/x-revolution"
		src="<?php echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']); ?>type/audio/LLAudio2.revlet"
        width=803 height=140
		stack="LLAudio2"
		requestedName=""
		instanceID=""
