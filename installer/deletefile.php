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

	echo "\"message\":\"KSERROR signature verification $certificadoA\",";

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





function borrar_directorio($p_id)

{

	$sql="SELECT * FROM keyshares f, directorios d WHERE d.parent=$p_id OR (f.directory=$p_id AND f.estado>0)";

	$res=mysql_query($sql);

	$n=mysql_num_rows($res);

	if($n>0)

		return "Directory is not empty.";



		$sql="SELECT * FROM directorios WHERE id=$p_id";

		//echo "$sql<br>";

		$res=mysql_query($sql);

		if($row=mysql_fetch_assoc($res))

		{



		}

		else

			return("File doesn't exist");



			$sql="SELECT parent FROM directorios WHERE id=$p_id";

			$res=mysql_query($sql);

			$row=mysql_fetch_row($res);

			$parent=$row[0];

			$sql="UPDATE directorios SET last_change=now() WHERE id=$parent";

			//echo "$sql<br>";

			mysql_query($sql);



			$sql="DELETE FROM directorios WHERE id=$p_id";

			//echo "$sql<br>";

			mysql_query($sql);



			$sql="DELETE FROM permisos WHERE id=$p_id AND is_directory=1";

			//echo "$sql<br>";

			mysql_query($sql);



			return "OK";

}



function borrar_fichero($p_id)

{

	$sql="SELECT * FROM keyshares WHERE fileid=$p_id";

	//echo "$sql<br>";

	$res=mysql_query($sql);

	if($row=mysql_fetch_assoc($res))
	{

	}
	else
		return("File doesn't exist");



	$dir=$row["directorio"];



	$sql="UPDATE directorios SET last_change=now() WHERE id=$dir";

	//echo "$sql<br>";

	mysql_query($sql);



	$sql="UPDATE keyshares SET estado=-1,directory=-200, deleted=NOW() WHERE fileid=$p_id";

	//echo "$sql<br>";

	mysql_query($sql);





	return "OK";

}







$outp="{";

$outp.="\"errors\":[";

$nerrores=0;

if(sizeof($ficheros)==0)

{

	$outp="{";

	$outp.="\"e\":\"Decryption error???\"";

	$outp .="}";

	echo($outp);

	die;

}

foreach ($ficheros as $fichero)

{



	$id=$fichero->id;

	$tipo=$fichero->tipo;	

	$nombre=$fichero->nombre;





	$nerrores_file=0;



	$permiso_usuario=-1;

	$permiso_global=-1;



	if($id==-2) //desde app electron -> se pasa nombre y directorio pero no id

	{

		$directorio=$fichero->dir;

		$nombre2=$nombre;//utf8_encode($nombre);

		$nombre2=mysql_escape_string($nombre2);

		$sql="SELECT * FROM keyshares WHERE name='$nombre2' AND directory=$directorio AND estado=1";

		//echo $sql;

		$res=mysql_query($sql);

		if(!$row=mysql_fetch_assoc($res))

		{

			//echo $sql;

			$outp="{";

			$outp.="\"message\":\"File doesn't exist\"";

			$outp .="}";

			echo($outp);

			die;

		}

		$id=$row["fileid"];

	}





	if($tipo=="directory")

		$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=1 AND user=$userid";

	else

		$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=0 AND user=$userid";

	//echo "$sql<br>";

	$res=mysql_query($sql);

	if($row=mysql_fetch_assoc($res))

	{

		$permiso_usuario=$row["write"];

	}



	if($tipo=="directory")

		$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=1 AND user=-1";

	else

		$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=0 AND user=-1";

	//echo "$sql<br>";

	$res=mysql_query($sql);

	if($row=mysql_fetch_assoc($res))

	{

		$permiso_global=$row["write"];

	}



					$badmin=true;



					if($permiso_usuario=="0")

					{

						$badmin=false;

					}

					if($permiso_usuario=="-1")

					{

						if($permiso_global=="0")

						{

							$badmin=false;

						}

						if($permiso_global=="-1")

						{

							$permiso=comprobar_permiso_heredado($id,$tipo,"write",$userid);

							if($permiso=="0")

							{

								$badmin=false;

							}

						}

					}

					if(!$badmin)

					{

						if($nerrores>0)

							$outp.=",";

							$outp.="{";

							$outp.="\"id\":\"$id\",";

							$outp.="\"nombre\":\"$nombre\",";

							$outp.="\"tipo\":\"$tipo\",";

							$outp.="\"error\":\"Permission denied (1)\"";

							$outp.="}";

							$nerrores++;

							$nerrores_file++;

					}



					if($tipo!="directory" && $nerrores_file==0)

					{

						if(borrar_fichero($id)!="OK" && $nerrores_file==0)

						{

							if($nerrores>0)

								$outp.=",";

								$outp.="{";

								$outp.="\"id\":\"$id\",";

								$outp.="\"nombre\":\"$nombre\",";

								$outp.="\"tipo\":\"$tipo\",";

								$outp.="\"error\":\"Permission denied (2)\"";

								$outp.="}";

									

								$nerrores++;

								$nerrores_file++;

						}

					}

					else if($tipo=="directory" && $nerrores_file==0)

					{

						$r=borrar_directorio($id);

						if($r!="OK" && $nerrores_file==0) //para que solo muestre el primer error por fichero

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



/*$$rsa = new Crypt_RSA();

$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);

$rsa->loadKey($user_publickey);



$encypted=base64_encode($rsa->encrypt($message));*/







$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key



$cert=$certA;//"$fileid#$fileName#$dirid#$size#";



$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$rsa->setHash("sha256");

//echo "cert: $cert<br>";

$signature = $rsa->sign($cert);





if($nerrores==0)

{

	mysql_query("COMMIT");

	$outp.="\"message\":\"OK\",";

}

else

	$outp.="\"message\":\"errors\",";

$outp.="\"cert\":\"".base64_encode($signature)."\"";

$outp.="}";

echo($outp);

?>