<?php
/**
 * Shortcore, a small url shortener service
 *   (c) 2009 Florian Anderiasch, <fa at art dash core dot org>
 *   BSD-licenced
 */

if( $_SERVER['SERVER_ADDR'] == "127.0.0.1" )
{
	$dbfile = "/home/mathieu/Projets/current/winks-shortcore/db/shortcore.db";
	$home = "http://127.0.0.1/shortcore/";
}
else
{
	$dbfile = "/kunden/homepages/db/shortcore.db";
	$home = "http://domain.invalid/shortcore/";
}



$cfg = array(
    'dbfile' => $dbfile,
    'table' => 'shortcore',
    'DEBUG' => false,
    'home' => $home,
    'tpl_body' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>%s</title>
<link rel="stylesheet" href="./css/styles.css" media="screen" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
<h2>shortcore</h2>
<span id="baseline">a small url shortener service</span>
<div id="page">
	<div class="title"><h1>%s</h1></div>
	<div class="table">
		%s
	</div>
</div>
</body>
</html>'
);
?>
