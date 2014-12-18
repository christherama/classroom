<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="install.css">

	<title>Classroom</title>
</head>
<body>
	<div class="container">
		<header>
			<h1>Classroom Install</h1>
		</header>
		<form role="form" method="post" action="install.php">
			<div class="form-group">
				<label for="db_username">MySQL Username</label>
				<input type="text" class="form-control" id="db_username" name="db_username" placeholder="username">
			</div>
			<div class="form-group">
				<label for="db_password">MySQL Password</label>
				<input type="text" class="form-control" id="db_password" name="db_password" placeholder="password">
			</div>
			<div class="form-group">
				<label for="db_name">Database Name</label>
				<input type="text" class="form-control" id="db_name" name="db_name" placeholder="db name">
			</div>
			<button type="submit" class="btn btn-primary btn-block">Install</button>
		</form>
	</div>
</body>
</html>