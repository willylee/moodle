<!-- Embed your revlets using code like this, to automatically guide the user to install the plugin, if it is not already installed -->
<div id="plugin" style="display:none">
<object classid="CLSID:B2EC94AF-4716-4300-824A-3314BF23664A" width=340 height=363>
	<param name="src" value="<?php echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']); ?>type/video/LLVideo.revlet"/>
	<param name="stack" value="LLVideo"/>
	<param name="requestedName" value=""/>
	<param name="instanceID" value=""/>
	<embed type="application/x-revolution"
		src="<?php echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']); ?>type/video/LLVideo.revlet"
        width=340 height=363
		xwidth=800 xheight=600
		stack="LLVideo"
		requestedName=""
		instanceID=""
