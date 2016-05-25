<?php
/*
Plugin Name: SendPress Contact Form 7
Plugin URI: https://github.com/brewlabs/sendpress-contact-form-7
Description: Add a SendPress signup field to your Contact Form 7 forms
Author: Josh Lyford
Text Domain: spnlcf7
Domain Path: /languages/
Version: 1.0
*/

// require the mailpoet signup field module
include( 'modules'. DIRECTORY_SEPARATOR .'sendpress-signup.php' );


/**
 * Get an array of MailPoet lists
 *
 * @return mixed
 */
function wpcf7_spnl_get_lists() {

	// make sure we have the class loaded
	if ( ! ( class_exists( 'SendPress' ) ) ) {
		return;
	}

	// get MailPoet / Wysija lists
	$lists = SendPress_Data::get_lists();

	return $lists->posts;
}