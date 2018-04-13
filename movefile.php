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
$clave=@$rsa->decrypt($clave_encriptada);*/

openssl_private_decrypt($clave_encriptada,$clave,$privkey,OPENSSL_PKCS1_OAEP_PADDING);

$cipher = new Crypt_AES(); // could use CRYPT_AES_MODE_CBC

$cipher->setKeyLength(256);

$cipher->setKey($clave);

$cipher->setIV($iv);

$certA=@$cipher->decrypt($encriptado);

sqllog($userid,$certA);
$certAutf8=utf8_encode($certA);
$o_certA=json_decode($certAutf8);



if(sizeof($certA)<1)

{

	echo "{";

	echo "\"message\":\"KSERROR error in decrypt\",";

	echo "\"cert\":\"\"";

	echo "}";

	die;

}





$ficheros=json_decode($o_certA->ficheros);


$dir=$o_certA->dir;









$badmin=obtener_permiso($dir,"directory","write",$userid);



if(!$badmin)

{

	$outp="{";

	$outp.="\"e\":\"Permission denied (3)\"";

	$outp .="}";

	echo($outp);

	die;

}





function mover_directorio($p_id,$p_dir)

{
	global $con;
	global $userid;

	$badmin=obtener_permiso($p_id,"directory","write",$userid);

	if(!$badmin)

		return "Permission denied (4)";

	

	/*$sql="SELECT * FROM keyshares f, directorios d WHERE d.parent=$p_id OR (f.directory=$p_id AND f.estado>0)";

	$res=$con->query($sql);

	$n=mysql_num_rows($res);

	if($n>0)

		return "Directory is not empty.";*/

	

	$sql="SELECT * FROM directorios WHERE id=$p_id";

	$res=$con->query($sql);

	if(!($row=$res->fetch_assoc()))

		return("Directory doesn't exist");

	$sql="SELECT * FROM directorios WHERE id=$p_dir";

	$res=$con->query($sql);

	if(!($row=$res->fetch_assoc()))

		return("Directory doesn't exist");

		

	$sql="SELECT parent FROM directorios WHERE id=$p_id";

	$res=$con->query($sql);

	$row=$res->fetch_row();

	$parent=$row[0];

	

	

	

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$parent";

	//echo "$sql<br>";

	$con->query($sql);

		

	$sql="UPDATE directorios SET parent=$p_dir WHERE id=$p_id";

	//echo "$sql<br>";

	$con->query($sql);

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$p_dir";

	//echo "$sql<br>";

	$con->query($sql);

	

	return "OK";

}



function mover_fichero($p_id,$p_dir)

{
	global $con;
	global $userid;

	$badmin=obtener_permiso($p_id,"nodirectory","write",$userid);

	if(!$badmin)

		return "KSPermission denied (5)";

	

	$sql="SELECT * FROM keyshares WHERE fileid=$p_id";

	//echo "$sql<br>";

	$res=$con->query($sql);

	if($row=$res->fetch_assoc())

	{

	}

	else

		return("File doesn't exist");

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=(SELECT directorio FROM ficheros WHERE id=$p_id)";

	$con->query($sql);

		

	$sql="UPDATE keyshares SET directory=$p_dir,estado=1 WHERE fileid=$p_id";

	//echo "$sql<br>";

	$con->query($sql);

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$p_dir";

	//echo "$sql<br>";

	$con->query($sql);

	

	return "OK";

}





$outp="{";

$outp.="\"errors\":[";

$nerrores=0;



foreach ($ficheros as $fichero)

{

	global $con;

	$id=$fichero->id;

	$tipo=$fichero->tipo;

	$nombre=utf8_encode($fichero->nombre);

	$nombre=$con->real_escape_string($nombre);

	

	$nerrores_file=0;



	if($id==-2) //desde app electron -> se pasa nombre y directorio pero no id

	{

		$directorio=$fichero->dir;

		$sql="SELECT * FROM keyshares WHERE name='$nombre' AND directory=$directorio AND estado=1";

		//echo $sql;

		$res=$con->query($sql);

		if(!$row=$res->fetch_assoc())

		{

			$outp="{";

			$outp.="\"e\":\"File doesn't exist\"";

			$outp .="}";

			echo($outp);

			die;

		}

		$id=$row["fileid"];

	}





	if($tipo!="directory")

	{

		$r=mover_fichero($id,$dir);

		if($r!="OK")

		{

			if($nerrores>0)

				$outp.=",";

			$outp.="{";

			$outp.="\"id\":\"$id\",";

			$outp.="\"nombre\":\"$nombre\",";

			$outp.="\"tipo\":\"$tipo\",";

			$outp.="\"error\":\"$r\"";

			$outp.="}";

			

			$nerrores++;

			$nerrores_file++;

		}

	}

	else if($tipo=="directory")

	{

		$r=mover_directorio($id,$dir);

		if($r!="OK")

		{

			if($nerrores>0)

				$outp.=",";

			$outp.="{";

			$outp.="\"id\":\"$id\",";

			$outp.="\"nombre\":\"$nombre\",";

			$outp.="\"tipo\":\"$tipo\",";

			$outp.="\"error\":\"$r\"";

			$outp.="}";			

			$nerrores++;

			$nerrores_file++;		

		}

	}

}

$outp.="],";



$cert=$certA;//"$fileid#$fileName#$dirid#$size#";

/*$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key
$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$rsa->setHash("sha256");
$signature = $rsa->sign($cert);*/

openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);
$signature = base64_encode($signature);


if($nerrores==0)
	$outp.="\"e\":\"OK\",";
else
	$outp.="\"e\":\"errors\",";

$outp.="\"cert\":\"".$signature."\"";
$outp.="}";

echo($outp);



?>