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

	echo "\"e\":\"KSERROR signature verification $certificadoA\",";

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

$clave=$rsa->decrypt($clave_encriptada);



$cipher = new Crypt_AES(); // could use CRYPT_AES_MODE_CBC

$cipher->setKeyLength(256);

$cipher->setKey($clave);

$cipher->setIV($iv);

$certA=$cipher->decrypt($encriptado);

sqllog($userid,$certA);
$certAutf8=utf8_encode($certA);
$o_certA=json_decode($certAutf8);



//$account=$o_certA->account;

$id=$o_certA->id;

$tipo=$o_certA->tipo;

$permissions=json_decode($o_certA->permissions);

$n=sizeof($permissions);//!!





if($tipo=="directory")

	$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=1 AND user=$userid";

else 

	$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=0 AND user=$userid";

$res=mysql_query($sql);

if($row=mysql_fetch_assoc($res))

{

	$permiso=$row["admin"];

	if($permiso=="0")

	{

		$outp="{";

		$outp.="\"e\":\"Permission denied1\"";

		$outp .="}";

		echo($outp);

		die;

	}

	else if($permiso=="-1" && !comprobar_permiso_heredado($id,$tipo,"admin"))

	{

		$outp="{";

		$outp.="\"e\":\"Permission denied2\"";

		$outp .="}";

		echo($outp);

		die;

	}

}

else

{

	$outp="{";

	$outp.="\"e\":\"Unknown error\"";

	$outp .="}";

	echo($outp);

	die;

}





$isd=($tipo=="directory");



//$sqlacum="";

for($i=0;$i<$n;$i++)

{

	$o=$permissions[$i];

	$luserid=$o->user;

	$read=$o->read;

	$write=$o->write;

	$exec=$o->exec;

	$admin=$o->admin;

	if($isd)

		$sql="INSERT INTO permisos(id,user,is_directory,`read`,`write`,exec,admin) VALUES($id,$luserid,1,$read,$write,$exec,$admin) ON DUPLICATE KEY UPDATE `read`='$read',`write`='$write',exec='$exec',admin='$admin' ";

	else 

		$sql="INSERT INTO permisos(id,user,is_directory,`read`,`write`,exec,admin) VALUES($id,$luserid,0,$read,$write,$exec,$admin) ON DUPLICATE KEY UPDATE `read`='$read',`write`='$write',exec='$exec',admin='$admin' ";

	//$sqlacum.=$sql."\n";

	mysql_query($sql);

}



$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key

$certB="$certA";

$rsa->setHash("sha256");

$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

//echo $certB;

//$signature = base64_encode($rsa->sign($certB));

$signature = $rsa->sign($certB);





$outp="{";

$outp.="\"e\":\"OK\",";

$outp.="\"cert\":\"".base64_encode($signature)."\"";

$outp.="}";

echo($outp);



?>