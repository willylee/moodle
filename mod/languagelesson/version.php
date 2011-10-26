<?php // $Id: version.php 677 2011-10-12 18:38:45Z griffisd $
/**
 * Code fragment to define the version of languagelesson
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @version $Id: version.php 677 2011-10-12 18:38:45Z griffisd $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package languagelesson
 **/

$module->version  = 2011100501;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101509;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
