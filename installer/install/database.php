<?php 



include_once("../db.php");



$error=0;



$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or $error=-1;

if($con==null)

	$error=-3;


$res=$con->query("SELECT * FROM config");
if($res && $res->num_rows>0) 
{
	$error=-5;
}


if($error<0)

{

	

}

else

{



	if(isset($_REQUEST["name"]))

	{

		$name=$_REQUEST["name"];

		$host=$_REQUEST["host"];

		$user=$_REQUEST["user"];

		$password=$_REQUEST["password"];

		

		

		// Name of the file

		$filename = 'cloud_keyserver-dev.sql';

		// MySQL host

		$mysql_host = $host;

		// MySQL username

		$mysql_username = $user;

		// MySQL password

		$mysql_password = $password;

		// Database name

		$mysql_database = $name;

		

		

		// Temporary variable, used to store current query

		$templine = '';

		// Read in entire file

		$lines = file($filename);

		// Loop through each line

		foreach ($lines as $num => $line)

		{

			//echo "Línea #<b>{$num}</b> : " . htmlspecialchars($line) . "<br />\n";



			// Skip it if it's a comment

			if (substr($line, 0, 2) == '--' || $line == '')

				continue;

		

				// Add this line to the current segment

				$templine .= $line;

				// If it has a semicolon at the end, it's the end of the query

				if (substr(trim($line), -1, 1) == ';')

				{

					// Perform the query

					$con->query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . $con->error . '<br /><br />');

					// Reset temp variable to empty

					$templine = '';

				}

		}

		/*$sql=file_get_contents($filename);

		mysql_query($sql) or print('Error performing query \'<strong>' . $sql . '\': ' . mysql_error() . '<br /><br />');*/



		header("Location:getkeys.php");

		

		die;

	}

}

?>

<!DOCTYPE html>

<html lang="en">



<head>



    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="">

    <meta name="author" content="">



    <title>

    	Oficloud Installer

    </title>



    <!-- Bootstrap Core CSS -->

    <link href="/css/bootstrap.min.css" rel="stylesheet">



<link href="css/estilo.css" rel="stylesheet">



<style type="text/css">

.myinput

{

	width:100%;

}



.red

{

	color:#de0000;

}



.green

{

	color:#00de00;

}



</style>

 

</head>

<body>

<div id="mainwindow" class="ventana">

<div class="titulo">Oficloud installer</div>

<div class="contenido">

	<h2>Database</h2>

	Please check the data to access your MySQL database. If something is wrong edit db.php and reload this page.<br>



	<form id="miform" method="POST" action="">

	Host: <input type="text" class="myinput" name="host" readonly value="<?=$g_db_server?>"/>

	Database name: <input type="text" class="myinput" name="name" readonly value="<?=$g_db_name?>"/>

	User: <input type="text" class="myinput" name="user" readonly value="<?=$g_db_user?>"/>

	Password: <input type="text" class="myinput" name="password" readonly value="<?=$g_db_password?>"/>

	</form>

	

	<?php 

	if($error==0)

	{

		echo "<span class=\"green\">Access to $g_db_name database granted</span>";

	}

	elseif ($error==-1)

		echo "<span class=\"red\">Could not connect to $host!</span>";

	elseif ($error==-2)

		echo "<span class=\"red\">Could not connect to $g_db_name database!</span>";

	elseif ($error==-3)

		echo "<span class=\"red\">Access denied for the specified user/password combination!</span>";
	
	elseif ($error==-5)

		echo "<span class=\"red\">A keysharing server is already configured on this machine. Installing a new one could mean losing all data previously administered by this server. Contact us.</span>";
		

	?>

</div>

<button class="btn" style="float: left;" onclick="location.href='check-php.php'">&lt;&lt;&lt;Previous</button>



<button class="btn" onclick="next();" <?php if($error!=0) echo "disabled"?>>Continue&gt;&gt;&gt;</button>

<button class="btn" style="float: right; margin-right:20px;" onclick="location.reload()">Reload</button>

</div>



<script>

	function next()

	{

		var e=document.getElementById('miform');

		e.submit();

	}

</script>



</body>

</html>