<?php
session_start();
require_once('../models/DataModel.php');
extract($_POST);

$create =	"CREATE TABLE IF NOT EXISTS `students` (
				`student_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`student_firstname` varchar(50) NOT NULL DEFAULT '',
				`student_lastname` varchar(50) NOT NULL DEFAULT '',
				`student_photo` blob NULL
);";

$create .=	"CREATE TABLE IF NOT EXISTS `classes` (
				`class_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`class_name` varchar(50) NOT NULL DEFAULT '',
				`class_period` tinyint NOT NULL
);";

$create .=	"CREATE TABLE IF NOT EXISTS `roster` (
				`roster_id` int(11) NOT NULL PRIMARY KEY  AUTO_INCREMENT,
				`roster_student` int(11) NOT NULL,
				`roster_class` int(11) NOT NULL,
				FOREIGN KEY (roster_student) REFERENCES students(student_id),
				FOREIGN KEY (roster_class) REFERENCES classes(class_id)
);";
$message = null;
$error = false;
try {
	$pdo = new PDO('mysql:dbname='.$db_name.';host=localhost',$db_username,$db_password);
	$stmt = $pdo->prepare($create);
	$result = $stmt->execute();
	if($result == null) {
		$error = true;
		$message = 'There is a SQL error that is preventing the installation from completing:<br/><br/>';
		$message .= '<code>'.DataModel::sqlError($stmt,false).'</code>';
	}
} catch(PDOException $e) {
	$error = true;
	$message = 'There was a problem completing the installation:<br/><br/>';
	$message .= '<code>'.DataModel::pdoError($e,$sql,false).'</code>';
}
if($message == null) {
	$message = 'The installation was successful!';
}

$_SESSION['message'] = $message;
$_SESSION['error'] = $error;
header('Location:result.php');