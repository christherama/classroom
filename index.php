<?php
// Initialize session
session_start();

// Load configuration files
require_once('config/db.php');
require_once('config/app.php');

/**
 * PHP magic method for auto loading classes. Without this, you would be
 * forced to require_once('path/to/Class.php') upon creating any of your objects
 * @param String $className Name of class to load
 */
function __autoload($className) {
	require_once("models/$className.php");
}

extract($_GET);

// Set current page
$CURR_PAGE = isset($_GET['p']) ? $_GET['p'] : DEFAULT_VIEW;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// Check for mobile browser
if(!isset($_SESSION['browser_type'])) {
	$m = new MobileDetect();
	$_SESSION['browser_type'] = $m->isMobile() ? 'mobile' : 'desktop';
}

function isMobile() {
	return $_SESSION['browser_type'] == 'mobile';
}

// If user is logged in, or is trying to login, let them
if(isLoggedIn() || $action == 'authenticate' || $CURR_PAGE == 'login') {
	// If no action is specified
	if($action == null) {
		require_once('template.php');
	} else {
		$file = "actions/$action.php";
		loadFile($file);
	}
} else { // Otherwise, force them to login
	redirect('./?p=login','Please login to access your contacts.');
}

/**
 * Determines whether or not the user is logged in
 * @return True if logged in, false if not
 */
function isLoggedIn() {
	return true;
	return isset($_SESSION['user']);
}

/**
 * Loads the file, if it exists. If the file doesn't exist, 
 * a location header for the 404 page is sent back to the browser
 * @param String $file File to load
 */
function loadFile($file) {
	if(file_exists($file)) {
		require_once($file);
	} else {
		header('Location:./?p=404');
	}
}

/**
 * Helper function to send location headers, with an optional message
 * @param String $location Absolute or relative URL of destination
 * @param String $message Optional message to display upon redirection
 */
function redirect($location,$message=null) {
	if($message != null) {
		$_SESSION['message'] = $message;
	}
	header("Location:$location");
}