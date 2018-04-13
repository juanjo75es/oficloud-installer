<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Cache-Control: post-check=0, pre-check=0", false);

header("Pragma: no-cache");



header("Content-Type: application/json; charset=UTF-8");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include_once("./db.php");
include_once("./inc-log.php");


$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or die("Error connecting to database");



include('inc_permisos.php');



set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');
//include('Crypt/RSA.php');
include('Crypt/AES.php');
include('Crypt/Random.php');






$userid=$con->real_escape_string($_REQUEST['userid']);

$certificadoA = $con->real_escape_string($_REQUEST['cert']);



//$rsa = new Crypt_RSA();



$certificadoA=base64_decode($certificadoA);
$acertificadoA=explode('@#@#@',$certificadoA);
$certificado_encriptado=$acertificadoA[0];
$firma=$acertificadoA[1];



$sql="SELECT pubkey_signing,account FROM usuarios WHERE id=$userid";
//echo $sql;
$res=$con->query($sql);
$row=$res->fetch_assoc();
$pubkey=$row["pubkey_signing"];
$account=$row["account"];



$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";

$res=$con->query($sql);

$row=$res->fetch_assoc();

$privkey=$row["privkey"];

$privkey_signing=$row["privkey_signing"];

$pubkey_signing=$row["pubkey_signing"];





/*$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$rsa->loadKey($pubkey);
$rsa->setHash("sha256");
if(!@$rsa->verify($certificado_encriptado, $firma))
{
	echo "{";
	echo "\"e\":\"KSERROR signature verification\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}*/

if(!openssl_verify($certificado_encriptado,$firma,$pubkey,OPENSSL_ALGO_SHA256))
{
	echo "{";
	echo "\"message\":\"KSERROR signature verification\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}




$acertA=explode("#@@##",$certificado_encriptado);
$encriptado=$acertA[0];
$clave_encriptada=$acertA[1];
$iv=$acertA[2];

/*$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);
$rsa->setHash("sha1");
$rsa->loadKey($privkey); // private key
$clave=$rsa->decrypt($clave_encriptada);*/

openssl_private_decrypt($clave_encriptada,$clave,$privkey,OPENSSL_PKCS1_OAEP_PADDING);


$cipher = new Crypt_AES(); // could use CRYPT_AES_MODE_CBC

$cipher->setKeyLength(256);

$cipher->setKey($clave);

$cipher->setIV($iv);

$certA=$cipher->decrypt($encriptado);

sqllog($userid,$certA);
$certAutf8=utf8_encode($certA);
$o_certA=json_decode($certAutf8);



$op=$o_certA->op;

$id=$o_certA->id;

$tipo=$o_certA->tipo;

$nombre=$o_certA->nombre;



if($op!="rename")

{

	$outp="{";

	$outp.="\"e\":\"Wrong operation\"";

	$outp .="}";

	echo($outp);

	die;

}





$badmin=obtener_permiso($id,$tipo,"write",$userid);



if(!$badmin)

{

	$outp="{";

	$outp.="\"e\":\"Permission denied\",";

	$outp.="\"desc\":\"You don't have write permission for this item.\"";	

	$outp .="}";

	echo($outp);

	die;

}





function renombrar_directorio($p_id,$p_nombre)

{
	global $userid;
	global $account;	
	global $con;
	

	$sql="SELECT * FROM directorios WHERE id=$p_id";

	$res=$con->query($sql);

	if(!($row=$res->fetch_assoc()))

		return(["Directory doesn't exist","",""]);

	$parent=$row["parent"];

	$nombre=$row["nombre"];

	

	if($nombre==$p_nombre)

		return(["Directory name not modified","",""]);

			

	$sql="SELECT * FROM directorios WHERE nombre='$p_nombre' AND parent=$parent";

	$res=$con->query($sql);

	if(($row=$res->fetch_assoc()))

		return(["Name already exists in this folder","",""]);

	

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$parent";

	//echo "$sql<br>";

	$con->query($sql);

		

	$sql="UPDATE directorios SET nombre='$p_nombre' WHERE id=$p_id";

	//echo "$sql<br>";

	$con->query($sql);

	

	

	return "OK";

}



function renombrar_fichero($p_id,$p_nombre)

{

	global $userid;
	global $account;	
	global $con;

	

	$sql="SELECT * FROM keyshares WHERE fileid=$p_id";

	$res=$con->query($sql);

	if(!($row=$res->fetch_assoc()))

		return(["File doesn't exist","",""]);

	$parent=$row["directory"];

	$nombre=$row["name"];

	

	if($nombre==$p_nombre)

		return(["File name not modified","",""]);

			

	$sql="SELECT * FROM keyshares WHERE name='$p_nombre' AND directory=$parent";

	$res=$con->query($sql);

	if(($row=$res->fetch_assoc()))

		return(["Name already exists in this folder","",""]);

	

	

		

	$sql="UPDATE keyshares SET name='$p_nombre' WHERE fileid=$p_id";

	//echo "$sql<br>";

	$con->query($sql);

	

	return "OK";

}



$outp="{";

$outp.="\"errors\":[";

$nerrores=0;



if($tipo!="directory")

{

	$r=renombrar_fichero($id,$nombre);

	if($r!="OK")

	{

		$err=$r[0];

		$desc=$r[1];

		$url=$r[2];

		$outp="{";

		$outp.="\"id\":\"$id\",";

		$outp.="\"nombre\":\"$nombre\",";

		$outp.="\"tipo\":\"$tipo\",";

		$outp.="\"error\":\"$err\",";

		$outp.="\"desc\":\"$desc\",";

		$outp.="\"url\":\"$url\"";

		$outp.="}";		

		echo $outp;

		die;

	}

}

else if($tipo=="directory")

{

	$r=renombrar_directorio($id,$nombre);

	if($r!="OK")

	{

		$err=$r[0];

		$desc=$r[1];

		$url=$r[2];

		$outp="{";

		$outp.="\"id\":\"$id\",";

		$outp.="\"nombre\":\"$nombre\",";

		$outp.="\"tipo\":\"$tipo\",";

		$outp.="\"error\":\"$err\",";

		$outp.="\"desc\":\"$desc\",";

		$outp.="\"url\":\"$url\"";

		$outp.="}";			

		echo $outp;

		die;

	}

}



$cert=$certA;

/*$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key
$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$rsa->setHash("sha256");
$signature = $rsa->sign($cert);*/

openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);
$signature = base64_encode($signature);



$outp="{";

$outp.="\"e\":\"OK\",";

$outp.="\"cert\":\"".$signature."\"";

$outp.="}";

echo($outp);



?>