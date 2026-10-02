<?php

/*
| The address this site is actually being used on, worked out from the
| current request. Used for QR codes and links in emails, so a code scanned
| on a phone opens the live site and never "localhost".
|
|   site_url('evaluate.php?activity_id=5')
|       on XAMPP  -> http://localhost/eval/evaluate.php?activity_id=5
|       on the server -> https://isuilagan-extentrack.pitonmain.com/evaluate.php?activity_id=5
*/

function site_url($path = ''){

	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
		|| (($_SERVER['SERVER_PORT'] ?? '') == 443);

	$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

	// The folder this application lives in, found from the file doing the asking
	$root = str_replace('\\', '/', realpath(__DIR__));
	$docroot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

	$base = '';

	if($docroot && strpos($root, $docroot) === 0){
		$base = rtrim(substr($root, strlen($docroot)), '/');
	}

	return ($https ? 'https' : 'http').'://'.$host.$base.'/'.ltrim($path, '/');
}
