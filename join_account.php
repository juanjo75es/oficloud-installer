<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("./db.php");
include_once("./inc-log.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or die("Error connecting to database");


$email=$con->real_escape_string($_REQUEST["user"]);
$userid=$con->real_escape_string($_REQUEST["userid"]);
$dirid=$con->real_escape_string($_REQUEST["duser"]);
$dsocial_id=$con->real_escape_string($_REQUEST["dsocial"]);
$dinternet_id=$con->real_escape_string($_REQUEST["dinternet"]);
$dmessages_id=$con->real_escape_string($_REQUEST["dmessages"]);
$accountid=$con->real_escape_string($_REQUEST["accountid"]);
$password=$con->real_escape_string($_REQUEST["password"]);
$privkey=$con->real_escape_string($_REQUEST["privkey"]);
$pubkey=$_REQUEST["pubkey"];
$privkey_signing=$con->real_escape_string($_REQUEST["privkey_signing"]);
$pubkey_signing=$_REQUEST["pubkey_signing"];

	

$pubkey=str_replace( '\r', "\r", $pubkey);
$pubkey=str_replace( '\n', "\n", $pubkey);
$pubkey_signing=str_replace( '\r', "\r", $pubkey_signing);
$pubkey_signing=str_replace( '\n', "\n", $pubkey_signing);



$sql="SELECT * FROM cuentas WHERE email='$email'";
$res=$con->query($sql);
if($row=$res->fetch_assoc())
{
	echo "{";
	echo "\"message\":\"ERROR1\",";
	echo "\"cert\":\"\"";
	echo "}";
}
else
{
	$sql="SELECT * FROM usuarios WHERE email='$email' AND estado>=0";
	
	$res=$con->query($sql);
	if($row=$res->fetch_assoc())
	{
		$sql2=$con->real_escape_string($sql);
		echo "{";
		echo "\"message\":\"ERROR2: $sql2\",";
		echo "\"cert\":\"\"";
		echo "}";
	}
	else 
	{		
		$sql="SELECT * FROM emails_importados WHERE email='$email' AND cuenta=$accountid";
		$res=$con->query($sql);
		if(!$row=$res->fetch_assoc())
		{
			echo "{";
			echo "\"message\":\"Unauthorized\",";
			echo "\"cert\":\"\"";
			echo "}";
			die;
		}		

		

		$sql="INSERT INTO usuarios(id,nick,password_hash,pubkey,encrypted_privkey,pubkey_signing,encrypted_privkey_signing,account,email,estado) VALUES($userid,'$email',SHA('$password'),'$pubkey','$privkey','$pubkey_signing','$privkey_signing',$accountid,'$email',1)";

		$res=$con->query($sql);		

		

		$sql="SELECT id FROM directorios WHERE parent=-1 AND account=$accountid";
		//echo "$sql<br>";
		$res=$con->query($sql);
		$row=$res->fetch_row();
		$root_directory=$row[0];

		

		$sql="SELECT id FROM directorios WHERE nombre='users' AND parent=$root_directory AND account=$accountid";

		//echo "$sql<br>";

		$res=$con->query($sql);

		$row=$res->fetch_row();

		$users_directory=$row[0];

		

		//directorio de usuario en users

		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dirid,'$email',$users_directory,now(),$accountid)";
		$con->query($sql);
		$user_directory=$dirid;
			

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$user_directory,1,1,1,1,1)";
		$con->query($sql);
		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$user_directory,1,0,0,0,0)";
		$con->query($sql);

		
		//directorio _social
		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dsocial_id,'_social',$user_directory,now(),$accountid)";
		$con->query($sql);		

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$dsocial_id,1,1,1,1,1)";
		$con->query($sql);
		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$dsocial_id,1,1,0,0,0)";
		$con->query($sql);

		//directorio _internet
		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dinternet_id,'_internet',$dsocial_id,now(),$accountid)";
		$con->query($sql);		

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$dinternet_id,1,1,1,1,1)";
		$con->query($sql);
		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-2,$dinternet_id,1,1,0,0,0)";
		$con->query($sql);
			
		//directorio _internet
		$sql="INSERT INTO directorios(id,nombre,parent,fecha,account) VALUES($dmessages_id,'_internet',$dsocial_id,now(),$accountid)";
		$con->query($sql);		

		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid,$dmessages_id,1,1,1,1,1)";
		$con->query($sql);
		$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES(-1,$dmessages_id,1,1,0,0,0)";
		$con->query($sql);

		echo "{";

		echo "\"message\":\"OK\",";

		echo "\"cert\":\"\"";

		echo "}";

	}

}

	

?>

