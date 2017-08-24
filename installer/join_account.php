<?php 

include_once("./db.php");
include_once("./inc-log.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$con = mysql_connect($g_db_server, $g_db_user, $g_db_password) or die("Error connecting to database");

mysql_select_db($g_db_name, $con) or die("Error selecting database");



$email=mysql_real_escape_string($_REQUEST["user"]);

$userid=mysql_real_escape_string($_REQUEST["userid"]);

$dirid=mysql_real_escape_string($_REQUEST["dirid"]);

$accountid=mysql_real_escape_string($_REQUEST["accountid"]);

$password=mysql_real_escape_string($_REQUEST["password"]);

$privkey=mysql_real_escape_string($_REQUEST["privkey"]);

$pubkey=$_REQUEST["pubkey"];

$privkey_signing=mysql_real_escape_string($_REQUEST["privkey_signing"]);

$pubkey_signing=$_REQUEST["pubkey_signing"];



	

$pubkey=str_replace( '\r', "\r", $pubkey);

$pubkey=str_replace( '\n', "\n", $pubkey);

$pubkey_signing=str_replace( '\r', "\r", $pubkey_signing);

$pubkey_signing=str_replace( '\n', "\n", $pubkey_signing);



$sql="SELECT * FROM cuentas WHERE email='$email'";

$res=mysql_query($sql);

if($row=mysql_fetch_assoc($res))

{

	echo "{";

	echo "\"message\":\"ERROR1\",";

	echo "\"cert\":\"\"";

	echo "}";

}

else

{

	$sql="SELECT * FROM usuarios WHERE email='$email' AND estado>=0";

	$res=mysql_query($sql);

	if($row=mysql_fetch_assoc($res))

	{

		echo "{";

		echo "\"message\":\"ERROR2\",";

		echo "\"cert\":\"\"";

		echo "}";

	}

	else 

	{		

		$sql="SELECT * FROM emails_importados WHERE email='$email' AND cuenta=$accountid";

		$res=mysql_query($sql);

		if(!$row=mysql_fetch_assoc($res))

		{

			echo "{";

			echo "\"message\":\"Unauthorized\",";

			echo "\"cert\":\"\"";

			echo "}";

			die;

		}		

		

		$sql="INSERT INTO usuarios(id,nick,password_hash,pubkey,encrypted_privkey,pubkey_signing,encrypted_privkey_signing,account,email,estado) VALUES($userid,'$email',SHA('$password'),'$pubkey','$privkey','$pubkey_signing','$privkey_signing',$accountid,'$email',1)";

		$res=mysql_query($sql);		

		

		$sql="SELECT id FROM directorios WHERE parent=-1 AND account=$accountid";

		//echo "$sql<br>";

		$res=mysql_query($sql);

		$row=mysql_fetch_row($res);

		$root_directory=$row[0];

		

		$sql="SELECT id FROM directorios WHERE nombre='users' AND parent=$root_directory AND account=$accountid";

		//echo "$sql<br>";

		$res=mysql_query($sql);

		$row=mysql_fetch_row($res);

		$users_directory=$row[0];

		

		//directorio de usuario en users

		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dirid,'$email',$users_directory,now(),$accountid)";

		mysql_query($sql);

		$user_directory=$dirid;
			

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$user_directory,1,1,1,1,1)";

		mysql_query($sql);

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$user_directory,1,0,0,0,0)";

		mysql_query($sql);

		

			

		echo "{";

		echo "\"message\":\"OK\",";

		echo "\"cert\":\"\"";

		echo "}";

	}

}

	

?>

