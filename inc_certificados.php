<?php

function extraer_certificado($certificadoA,$pubkey,$privkey,&$certA=NULL)
{
	$acertificadoA=explode('@#@#@$$',$certificadoA);
	$certificado_encriptado=$acertificadoA[0];
	$firma=base64_decode($acertificadoA[1]);
	//$firma=$acertificadoA[1];

	if(!openssl_verify($certificado_encriptado,$firma,$pubkey,OPENSSL_ALGO_SHA256))
	{
		
		$s="{";
		$s.="\"e\":\"KSERROR signature verification\",";
		$s.="\"cert\":\"\"";
		$s.= "}";
		$o=json_decode($s);
		return $o;
	}
	
	
	
	$acertA=explode("#@@##",$certificado_encriptado);
	$encriptado=base64_decode($acertA[0]);
	$clave_encriptada=base64_decode($acertA[1]);
	$iv=$acertA[2];
	
	/*$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);
	$rsa->setHash("sha1");
	$rsa->loadKey($privkey); // private key
	$clave=@$rsa->decrypt($clave_encriptada);*/
	
	openssl_private_decrypt($clave_encriptada,$clave,$privkey,OPENSSL_PKCS1_OAEP_PADDING);
	//$clave="11111111222222223333333344444444";
	//$clave=$clave_encriptada;
	
	$cipher = new Crypt_AES(); // could use CRYPT_AES_MODE_CBC
	$cipher->setKeyLength(256);
	$cipher->setKey($clave);
	$cipher->setIV($iv);
	$certA=@$cipher->decrypt($encriptado);
	//$certA=@$cipher->decrypt($encriptado);
	
	sqllog($userid,$certA);
	$certAutf8=utf8_encode($certA);
	$o_certA=json_decode($certAutf8);
	
	return $o_certA;
}

?>