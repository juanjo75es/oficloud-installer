<?php 

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include_once("./db.php");
include_once("./inc-log.php");

$con = mysql_connect($g_db_server, $g_db_user, $g_db_password) or die("Error connecting to database");

mysql_select_db($g_db_name, $con) or die("Error selecting database");



$duser_id=mysql_real_escape_string($_REQUEST["duser"]);

$droot_id=mysql_real_escape_string($_REQUEST["droot"]);

$dusers_id=mysql_real_escape_string($_REQUEST["dusers"]);

$dpublic_id=mysql_real_escape_string($_REQUEST["dpublic"]);

$email=mysql_real_escape_string($_REQUEST["user"]);

$userid=mysql_real_escape_string($_REQUEST["userid"]);

$accountid=mysql_real_escape_string($_REQUEST["accountid"]);

$password=mysql_real_escape_string($_REQUEST["password"]);

$privkey=mysql_real_escape_string($_REQUEST["privkey"]);

$pubkey=$_REQUEST["pubkey"];

$privkey_signing=mysql_real_escape_string($_REQUEST["privkey_signing"]);

$pubkey_signing=$_REQUEST["pubkey_signing"];



/*$pubkey=urldecode($pubkey);

$pubkey_signing=urldecode($pubkey_signing);*/

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

	$sql="SELECT * FROM usuarios WHERE email='$email'";

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

		$sql="INSERT INTO usuarios(id,nick,password_hash,pubkey,encrypted_privkey,pubkey_signing,encrypted_privkey_signing,account,email,estado) VALUES($userid,'$email',SHA('$password'),'$pubkey','$privkey','$pubkey_signing','$privkey_signing',-1,'$email',0)";

		$res=mysql_query($sql);		

		

		$sql="INSERT INTO cuentas(id,owner_user_id,email) VALUES($accountid,$userid,'$email')";

		$res=mysql_query($sql);		

		



		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($droot_id,'root',-1,now(),$accountid)";

		mysql_query($sql);

		$root_directory=mysql_insert_id();



		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$root_directory,1,1,1,1,1)";

		mysql_query($sql);

				

		//directorio users

		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dusers_id,'users',$root_directory,now(),$accountid)";

		mysql_query($sql);

		$users_directory=mysql_insert_id();

		

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$users_directory,1,-1,-1,-1,1)";

		mysql_query($sql);

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$users_directory,1,1,0,1,0)";

		mysql_query($sql);

		

		//directorio public

		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dpublic_id,'public',$root_directory,now(),$accountid)";

		mysql_query($sql);

		$public_directory=mysql_insert_id();

				

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$public_directory,1,-1,-1,-1,1)";

		mysql_query($sql);

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$public_directory,1,1,1,1,0)";

		mysql_query($sql);

		

		//directorio de usuario en users

		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($duser_id,'$email',$users_directory,now(),$accountid)";

		mysql_query($sql);

		$user_directory=mysql_insert_id();



		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$user_directory,1,1,1,1,1)";

		mysql_query($sql);

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$user_directory,1,0,0,0,0)";

		mysql_query($sql);

		

				

		$sql="UPDATE usuarios SET estado=1,account=$accountid WHERE id=$userid";

		mysql_query($sql);

			

		echo "{";

		echo "\"message\":\"OK\",";

		echo "\"cert\":\"\"";

		echo "}";

	}

}

	

?>

