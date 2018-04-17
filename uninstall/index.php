<?php 



$host='//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);

include_once("../config.php");

$error="";

if(isset($_REQUEST["token"]))
{
	include_once("../db.php");
	$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or $error=-1;
	

	$token=$con->real_escape_string($_REQUEST["token"]);

	
	$sql="SELECT fileid,share FROM keyshares";
	$res=$con->query($sql);
	$a=[];
	while($row=$res->fetch_assoc())
	{
		$a[]=[$row["fileid"],$row["share"]];
	}

	$postdata = http_build_query(
			array(
					'token' => $token,
					'shares' => $a
			)
			);

	$opts = array('http' =>
			array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => $postdata
			)
	);


	$context  = stream_context_create($opts);

	@$respuesta= file_get_contents("https:$server/uninstall_keysharing_server.php", false, $context);
	if($respuesta===FALSE)
	{
		$error="The Oficloud server ($server) is not responding. Please try later or check your Internet connection.";
	}
	else 
	{
		$p=json_decode($respuesta);
		if($p->e=="OK")
		{	
			$sql="TRUNCATE TABLE config";
			$con->query($sql);
			//delte database?
			header("Location:confirmed.php");
		}
		else
		{
			echo $respuesta;
			$error=$p->e;
		}
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

    	Oficloud Uninstaller

    </title>



    <!-- Bootstrap Core CSS -->

    <link href="/css/bootstrap.min.css" rel="stylesheet">



<link href="../install/css/estilo.css" rel="stylesheet">



<style>

.myinput

{

	width:100%;

	height:80px;

}

</style>



</head>

<body>

<div id="mainwindow" class="ventana">

<div class="titulo">Oficloud uninstaller</div>

<div class="contenido">

	<form id="miform" action="" method="POST">

	<h2>Insert the token-key obtained from your account</h2>

	<input type="text" name="token" style="width:100%; margin:0px 0px;"/>


	</form>

	<span style="color:#de0000"><?=$error?></span>

</div>

<button class="btn" onclick="next();">Uninstall</button>

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