<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>
    	Oficloud Installer
    </title>

    <!-- Bootstrap Core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

<link href="css/estilo.css" rel="stylesheet">
    
</head>
<body>
<div id="mainwindow" class="ventana">
<div class="titulo">Oficloud installer</div>
<div class="contenido">
	<h2>PHP configuration</h2>
	<?php 
	echo "PHP version: ".phpversion()."<br>";
	if (version_compare(PHP_VERSION, '5.5.0', '<')) {
		echo '<span style="color:orange">Warning: Recommended PHP version 5.5.0 or higher</span><br> ';
	}
	else 
		echo '<span style="color:green">PHP version is ok</span><br> ';
	/*ob_start();
	phpinfo(INFO_MODULES);
	$info = ob_get_contents();
	ob_end_clean();
	$info = stristr($info, 'Client API version');
	preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
	$gd = $match[0];
	echo 'MySQL version:  '.$gd.' <br>';
	if (version_compare($gd, '5.5.0', '<')) {
		echo '<span style="color:orange">Warning: Recommended MySQL version 5.5.0 or higher</span><br> ';
	}
	else
		echo '<span style="color:green">MySQL version is ok</span><br> ';*/
	
	?>
	<br>	
</div>
<button class="btn" style="float: left;" onclick="location.href='license.php'">&lt;&lt;&lt;Previous</button>
<button class="btn" onclick="location.href='database.php'">Continue&gt;&gt;&gt;</button>
</div>

<script type="text/javascript">
</script>

</body>
</html>