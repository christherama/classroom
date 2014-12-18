<?php
session_start();
extract($_SESSION);
session_destroy();
$alert_class = $error ? 'danger' : 'success';
$icon = $error ? 'warning-sign' : 'ok-circle';
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="install.css">

	<title>Classroom Install Results</title>
</head>
<body>
	<div class="container">
		<header>
			<h1>Classroom Install</h1>
		</header>
		<div role="alert" class="alert alert-<?php echo $alert_class ?>">
			<span class="glyphicon glyphicon-<?php echo $icon ?>" aria-hidden="true"></span>
			<?php echo $message ?>
		</div>
	</div>
</body>
</html>