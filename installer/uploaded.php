<?php



header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Cache-Control: post-check=0, pre-check=0", false);

header("Pragma: no-cache");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include_once("./db.php");
include_once("./inc-log.php");

$con = mysql_connect($g_db_server, $g_db_user, $g_db_password) or die("Error connecting to database");

mysql_select_db($g_db_name, $con) or die("Error selecting database");



mysql_query("START TRANSACTION");



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



$userid = mi_mysql_real_escape_string($_REQUEST['userid']);

$fileid = mi_mysql_real_escape_string($_REQUEST['id']);

$certificadoA = mi_mysql_real_escape_string($_REQUEST['cert']);

$fileName = mi_mysql_real_escape_string(utf8_decode($_REQUEST['nombre']));

$dirid = mi_mysql_real_escape_string($_REQUEST['dirid']);

$size= mi_mysql_real_escape_string($_REQUEST['size']);



$permiso=comprobar_permiso_heredado_dir($dirid,"write",$userid);

if($permiso=="0")

{

	echo "{";

	echo "\"message\":\"KSERROR permission denied\",";

	echo "\"cert\":\"\"";

	echo "}";

	die;

}





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

	echo "\"message\":\"KSERROR signature verification \",";

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

$share_certA=$o_certA->share;





$share=$share_certA;



if(sizeof($share)<1)

{

	echo "{";

	echo "\"message\":\"KSERROR error in decrypt $certificadoA\",";

	echo "\"cert\":\"\"";

	echo "}";

	die;

}





$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key

$cert=$certA;//"$fileid#$fileName#$dirid#$size#";

$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$rsa->setHash("sha256");

$signature = $rsa->sign($cert);



$sql="SELECT fileid FROM keyshares WHERE estado=1 AND name='$fileName' AND directory=$dirid AND account=$account";

//echo $sql;

//echo "$fileName $dirid $account<br>";

$res=mysql_query($sql);

$row=mysql_fetch_assoc($res);

if($row)

{

	$fid=$row["fileid"];

	//$sql="UPDATE keyshares SET share='$share',size='$size' WHERE fileid=$fid";

	$sql="UPDATE keyshares SET estado=-1,directory=-200 WHERE fileid=$fid";

	//echo $sql;

	mysql_query($sql);

}



{

	$sql="INSERT INTO keyshares(fileid,estado,name,size,directory,share,account) VALUES($fileid,1,'$fileName',$size,$dirid,'$share',$account)";

	//echo $sql;

	mysql_query($sql);

}



//!!aqui habria que actualiazr permisos o borrar anteriores



$sql="INSERT INTO permisos(id,is_directory,`read`,`write`,exec,admin,user) VALUES($fileid,0,1,1,1,1,$userid)";

//echo "$sql<br>";

mysql_query($sql);



mysql_query("COMMIT");



echo "{";

echo "\"message\":\"OK\",";

echo "\"cert\":\"".base64_encode($signature)."\"";

echo "}";



?>