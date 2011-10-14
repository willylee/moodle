<!-- Embed your revlets using code like this, to automatically guide the user to install the plugin, if it is not already installed -->
<div <?php if (!$flag) { ?>id="<?php echo ((isset($qmodpluginID) && $qmodpluginID) ? $modpluginID : 'plugin'); ?>" style="display:none" <?php } else { ?> style="display:block" <?php } ?>>
<object classid="CLSID:B2EC94AF-4716-4300-824A-3314BF23664A" width=803 height=140>
	<param name="src" value="<?php //echo preg_replace('/[^\/]*\.php/', '', $_SERVER['SCRIPT_NAME']);
                                       echo $CFG->wwwroot; ?>/mod/languagelesson/runrev/feedback/player/LLFeedbackPlayer.revlet"/>
	<param name="stack" value="LLFeedbackPlayer"/>
	<param name="requestedName" value=""/>
	<param name="instanceID" value=""/>
	<embed type="application/x-revolution"
		src="<?php //echo preg_replace('/[^\/]*.php/', '', $_SERVER['SCRIPT_NAME']);
			echo $CFG->wwwroot; ?>/mod/languagelesson/runrev/feedback/player/LLFeedbackPlayer.revlet"
        width=803 height=140
		stack="LLFeedbackPlayer"
		requestedName=""
		instanceID=""
