<?php // $Id$
/**
 * Capability definitions for the languagelesson module.
 *
 * For naming conventions, see lib/db/access.php.
 */
$mod_languagelesson_capabilities = array(

    'mod/languagelesson:edit' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/languagelesson:manage' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),
    
    'mod/languagelesson:submit' => array(
    
    	'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        	'teacher' => CAP_ALLOW,
        	'editingteacher' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	'mod/languagelesson:grade' => array(
		
		'riskbitmask' => RISK_SPAM,
		
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'admin' => CAP_ALLOW
		)
	)
);
