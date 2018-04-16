<?php 

$host='//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);

include_once("../config.php");

/*Hacer campo para introducir la clave-token

Hacer que envie al servidor la clave publica (comprobar token)

Que el servidor envie un xml con los datos para que los importe
*/
if(isset($_REQUEST["privk1"]))
{
	include_once("../db.php");
	$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or die("Could not connect to database");
		
	$token=$_REQUEST["token"];
	//echo ($con->real_escape_string($_REQUEST["token"]));echo "---";
	echo "$token - ".$_REQUEST["token"];
	$privk1=$_REQUEST["privk1"];
	$privk2=$_REQUEST["privk2"];
	$pubk1=$_REQUEST["pubk1"];
	$pubk2=$_REQUEST["pubk2"];
	$host=$_REQUEST["host"];
	
	$con->query("START TRANSACTION");
	
	$sql="TRUNCATE TABLE config";
	$con->query($sql);
	//$sql="INSERT INTO config(privkey,pubkey,privkey_signing,pubkey_signing,tokenkey) VALUES('$privk1','$pubk1','$privk2','$pubk2','$token')";
	$sql="INSERT INTO config(privkey,pubkey,privkey_signing,pubkey_signing,tokenkey) VALUES(?,?,?,?,?)";
	//echo "$sql";
	$con->prepare($sql);
	$con->bind_param("sssss", $privk1,$pubk1,$privk2,$pubk2,$token);
	$con->execute();
	
	$postdata = http_build_query(
			array(
					'token' => $token,
					'pubk1' => $pubk1,
					'pubk2' => $pubk2,
					'host' => substr($host,0,-8)
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
	
	print_r($postdata);	echo "<br>"; echo "$server/set_keysharing_server.php";

	@$respuesta= file_get_contents("$server/set_keysharing_server.php", false, $context);
	if($respuesta===FALSE)
	{
		$error="The Oficloud server ($server) is not responding. Please try later or check your Internet connection.";
	}
	else 
	{
		$p=json_decode($respuesta);
		if($p->e=="OK")
		{	
			$mysqli = new mysqli("localhost", $g_db_user, $g_db_password, $g_db_name);
			foreach($p->dump as $query)
			{
			
				$query= str_replace("\\n","\r\n",urldecode($query));
				if ($mysqli->multi_query($query)) {
				do {
					/* almacenar primer juego de resultados */
					if ($result = $mysqli->store_result()) {
						while ($row = $result->fetch_row()) {
							
						}
						$result->free();
					}
					/* mostrar divisor */
					if ($mysqli->more_results()) {
						
					}
				} while ($mysqli->next_result());
				}
			}
			
			/* cerrar conexi�n */
			$mysqli->close();
			
			$con->query("COMMIT");
			
			header("Location:confirmed.php");
			die;
		}
		else 
			$error=$p->e;
	}
}

{
	set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');
	include('Crypt/RSA.php');
	include('Crypt/Random.php');
	
	$rsa = new Crypt_RSA();
	define('CRYPT_RSA_EXPONENT', 65537);
	$keypair = $rsa->createKey(2048);
	$keypair2 = $rsa->createKey(2048);
/*echo $keypair["privatekey"]."<br>";
	echo $keypair["publickey"]."<br>";
	die;*/
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
<div class="titulo">Oficloud installer</div>
<div class="contenido">
	<form id="miform" action="" method="POST">
	<h2>Insert the token-key obtained from your account</h2>
	<input type="text" name="token" style="width:100%; margin:0px 0px;"/>
	<h2>Generated public keys</h2>
	Public key 1: <textarea type="text" name="pubk1" id="pk1" class="myinput" readonly><?=$keypair["publickey"]?></textarea><br>
	Public key 2: <textarea type="text" name="pubk2" id="pk2" class="myinput" readonly><?=$keypair2["publickey"]?></textarea><br>
	<input type="hidden" id="privk1" name="privk1" value="<?=$keypair["privatekey"]?>"/>
	<input type="hidden" id="privk2" name="privk2" value="<?=$keypair2["privatekey"]?>"/>
	<input type="hidden" name="host" value="<?=$host?>"/>
	</form>
	<span style="color:#de0000"><?=$error?></span>
</div>
<button class="btn" style="float: left;" onclick="location.href='database.php'">&lt;&lt;&lt;Previous</button>
<button class="btn" onclick="next();">Continue&gt;&gt;&gt;</button>
</div>

<script>
	function next()
	{		
		var e=document.getElementById('miform');
		e.submit();
	}
</script>


</script>

</body>
</html>