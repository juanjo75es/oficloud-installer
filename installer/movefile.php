<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Cache-Control: post-check=0, pre-check=0", false);

header("Pragma: no-cache");



header("Content-Type: application/json; charset=UTF-8");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include_once("./db.php");
include_once("./inc-log.php");

$con = mysql_connect($g_db_server, $g_db_user, $g_db_password) or die("Error connecting to database");

mysql_select_db($g_db_name, $con) or die("Error selecting database");



include('inc_permisos.php');



set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');

include('Crypt/RSA.php');

include('Crypt/AES.php');

include('Crypt/Random.php');



function mi_mysql_real_escape_string($s)

{

	if(isset($s))

		return mysql_real_escape_string($s);

		return $s;

}



$userid=mi_mysql_real_escape_string($_REQUEST['userid']);

$certificadoA = mi_mysql_real_escape_string($_REQUEST['cert']);



$rsa = new Crypt_RSA();



$certificadoA=base64_decode($certificadoA);

$acertificadoA=explode('@#@#@',$certificadoA);

$certificado_encriptado=$acertificadoA[0];

$firma=$acertificadoA[1];



$sql="SELECT pubkey_signing,account FROM usuarios WHERE id=$userid";

$res=mysql_query($sql);

$row=mysql_fetch_assoc($res);

$pubkey=$row["pubkey_signing"];

$account=$row["account"];



$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";

$res=mysql_query($sql);

$row=mysql_fetch_assoc($res);

$privkey=$row["privkey"];

$privkey_signing=$row["privkey_signing"];

$pubkey_signing=$row["pubkey_signing"];





$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$rsa->loadKey($pubkey);

$rsa->setHash("sha256");

if(!@$rsa->verify($certificado_encriptado, $firma))

{

	echo "{";

	echo "\"e\":\"KSERROR signature verification\",";

	echo "\"cert\":\"\"";

	echo "}";

	die;

}





$acertA=explode("#@@##",$certificado_encriptado);

$encriptado=$acertA[0];

$clave_encriptada=$acertA[1];

$iv=$acertA[2];

$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);

$rsa->setHash("sha1");

$rsa->loadKey($privkey); // private key

$clave=@$rsa->decrypt($clave_encriptada);



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

	global $userid;

	$badmin=obtener_permiso($p_id,"directory","write",$userid);

	if(!$badmin)

		return "Permission denied (4)";

	

	/*$sql="SELECT * FROM keyshares f, directorios d WHERE d.parent=$p_id OR (f.directory=$p_id AND f.estado>0)";

	$res=mysql_query($sql);

	$n=mysql_num_rows($res);

	if($n>0)

		return "Directory is not empty.";*/

	

	$sql="SELECT * FROM directorios WHERE id=$p_id";

	$res=mysql_query($sql);

	if(!($row=mysql_fetch_assoc($res)))

		return("Directory doesn't exist");

	$sql="SELECT * FROM directorios WHERE id=$p_dir";

	$res=mysql_query($sql);

	if(!($row=mysql_fetch_assoc($res)))

		return("Directory doesn't exist");

		

	$sql="SELECT parent FROM directorios WHERE id=$p_id";

	$res=mysql_query($sql);

	$row=mysql_fetch_row($res);

	$parent=$row[0];

	

	

	

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$parent";

	//echo "$sql<br>";

	mysql_query($sql);

		

	$sql="UPDATE directorios SET parent=$p_dir WHERE id=$p_id";

	//echo "$sql<br>";

	mysql_query($sql);

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$p_dir";

	//echo "$sql<br>";

	mysql_query($sql);

	

	return "OK";

}



function mover_fichero($p_id,$p_dir)

{

	global $userid;

	$badmin=obtener_permiso($p_id,"nodirectory","write",$userid);

	if(!$badmin)

		return "KSPermission denied (5)";

	

	$sql="SELECT * FROM keyshares WHERE fileid=$p_id";

	//echo "$sql<br>";

	$res=mysql_query($sql);

	if($row=mysql_fetch_assoc($res))

	{

	}

	else

		return("File doesn't exist");

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=(SELECT directorio FROM ficheros WHERE id=$p_id)";

	mysql_query($sql);

		

	$sql="UPDATE keyshares SET directory=$p_dir,estado=1 WHERE fileid=$p_id";

	//echo "$sql<br>";

	mysql_query($sql);

	

	$sql="UPDATE directorios SET last_change=now() WHERE id=$p_dir";

	//echo "$sql<br>";

	mysql_query($sql);

	

	return "OK";

}





$outp="{";

$outp.="\"errors\":[";

$nerrores=0;



foreach ($ficheros as $fichero)

{

	

	$id=$fichero->id;

	$tipo=$fichero->tipo;

	$nombre=utf8_encode($fichero->nombre);

	$nombre=mysql_real_escape_string($nombre);

	

	$nerrores_file=0;



	if($id==-2) //desde app electron -> se pasa nombre y directorio pero no id

	{

		$directorio=$fichero->dir;

		$sql="SELECT * FROM keyshares WHERE name='$nombre' AND directory=$directorio AND estado=1";

		//echo $sql;

		$res=mysql_query($sql);

		if(!$row=mysql_fetch_assoc($res))

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



$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key

$cert=$certA;//"$fileid#$fileName#$dirid#$size#";

$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$rsa->setHash("sha256");

$signature = $rsa->sign($cert);





if($nerrores==0)

	$outp.="\"e\":\"OK\",";

else

	$outp.="\"e\":\"errors\",";

$outp.="\"cert\":\"".base64_encode($signature)."\"";

$outp.="}";

echo($outp);



?>