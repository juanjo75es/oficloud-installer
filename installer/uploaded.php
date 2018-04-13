<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include_once("./db.php");
include_once("./inc-log.php");

//$before = microtime(true);

$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or die("Error connecting to database");
$con->query("START TRANSACTION");

include('inc_permisos.php');



set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');
//include('Crypt/RSA.php');
include('Crypt/AES.php');
include('Crypt/Random.php');


$userid = $con->real_escape_string($_REQUEST['userid']);
$fileid = $con->real_escape_string($_REQUEST['id']);
$certificadoA = $con->real_escape_string($_REQUEST['cert']);
$certificadoThumbnailA = $con->real_escape_string($_REQUEST['certThumbnail']);
$fileName = $con->real_escape_string(utf8_decode($_REQUEST['nombre']));
$dirid = $con->real_escape_string($_REQUEST['dirid']);
$size= $con->real_escape_string($_REQUEST['size']);

//echo "$dirid - $userid";die;

$permiso=comprobar_permiso_heredado_dir($dirid,"write",$userid);
if($permiso=="0")
{
	echo "{";
	echo "\"message\":\"KSERROR permission denied\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}


//$rsa = new Crypt_RSA();


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


$certificadoA=base64_decode($certificadoA);
$acertificadoA=explode('@#@#@',$certificadoA);
$certificado_encriptado=$acertificadoA[0];
$firma=$acertificadoA[1];

/*$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$rsa->loadKey($pubkey);
$rsa->setHash("sha256");

if(!@$rsa->verify($certificado_encriptado, $firma))
{
	echo "{";
	echo "\"message\":\"KSERROR signature verification \",";
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

$share_certA=$o_certA->share;


if($certificadoThumbnailA)
{

	$certificadoThumbnailA=base64_decode($certificadoThumbnailA);
	$acertificadoThumbnailA=explode('@#@#@$$',$certificadoThumbnailA);
	
	$certificadoThumbnail_encriptado=$acertificadoThumbnailA[0];
	$firma=$acertificadoThumbnailA[1];

	/*$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$rsa->loadKey($pubkey);
	$rsa->setHash("sha256");
	
	if(!@$rsa->verify($certificadoThumbnail_encriptado, $firma))*/
	if(!openssl_verify($certificadoThumbnail_encriptado,$firma,$pubkey,OPENSSL_ALGO_SHA256))
	{
		echo "{";
		echo "\"message\":\"KSERROR thumbnail signature verification $firma\",";
		echo "\"cert\":\"\"";
		echo "}";
		die;
	}
	
	$acertA=explode("#@@##",$certificadoThumbnail_encriptado);
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
	$certThumbnailA=@$cipher->decrypt($encriptado);
	sqllog($userid,$certA);
	$certThumbnailAutf8=utf8_encode($certThumbnailA);
	$o_certThumbnailA=json_decode($certThumbnailAutf8);
	
	$shareThumbnail=$o_certThumbnailA->share2;
	$filenameThumbnail=$o_certThumbnailA->filename;
	$diridThumbnail=$o_certThumbnailA->dirid;
		
}

$share=$share_certA;


if(sizeof($share)<1)
{
	echo "{";
	echo "\"message\":\"KSERROR error in decrypt $certificadoA\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}


$cert=$certA;//"$fileid#$fileName#$dirid#$size#";
/*$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key
$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$rsa->setHash("sha256");
$signature = $rsa->sign($cert);*/

/*$a=openssl_get_md_methods();
print_r($a);*/

openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);

$sql="SELECT fileid FROM keyshares WHERE estado=1 AND name='$fileName' AND directory=$dirid AND account=$account";

//echo $sql;

//echo "$fileName $dirid $account<br>";

$res=$con->query($sql);
$row=$res->fetch_assoc();
if($row)
{
	$fid=$row["fileid"];
	//$sql="UPDATE keyshares SET share='$share',size='$size' WHERE fileid=$fid";
	$sql="UPDATE keyshares SET estado=-1,directory=-200 WHERE fileid=$fid";
	//echo $sql;
	$con->query($sql);
	
}

{
	$sql="INSERT INTO keyshares(fileid,estado,name,size,directory,share,account) VALUES($fileid,1,'$fileName',$size,$dirid,'$share',$account)";
	//echo $sql;
	$con->query($sql);

	$sql="INSERT INTO blobs(tipo,owner_id,share) VALUES('thumbnail',$fileid,'$shareThumbnail')";
	//echo $sql;
	$con->query($sql);
}



//!!aqui habria que actualiazr permisos o borrar anteriores

$sql="INSERT INTO permisos(id,is_directory,`read`,`write`,exec,admin,user) VALUES($fileid,0,1,1,1,1,$userid)";
//echo "$sql<br>";
$con->query($sql);

$con->query("COMMIT");

//$after = microtime(true);

echo "{";
echo "\"message\":\"OK\",";
//echo "\"tspent\":\"".($after-$before)."\",";
echo "\"cert\":\"".base64_encode($signature)."\"";
echo "}";

?>