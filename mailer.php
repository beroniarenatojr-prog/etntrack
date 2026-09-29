<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sends an email through the Gmail account in mail_config.php.
// Returns true on success, otherwise the error message.
function send_mail($to, $subject, $html, $text){
	$config_file = __DIR__ . '/mail_config.php';
	if(!file_exists($config_file)){
		return 'mail_config.php is missing on the server.';
	}
	$config = require $config_file;

	$mail = new PHPMailer(true);
	try {
		$mail->isSMTP();
		$mail->Host = 'smtp.gmail.com';
		$mail->SMTPAuth = true;
		$mail->Username = $config['username'];
		$mail->Password = $config['password'];
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mail->Port = 587;
		$mail->CharSet = 'UTF-8';
		$mail->Timeout = 20;

		$mail->setFrom($config['username'], $config['from_name']);
		$mail->addAddress($to);
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body = $html;
		$mail->AltBody = $text;

		$mail->send();
		return true;
	} catch (Exception $e) {
		$error = $mail->ErrorInfo ?: $e->getMessage();
		if(stripos($error, 'authenticate') !== false){
			return 'Gmail rejected the login. Check the App Password in mail_config.php.';
		}
		return $error;
	}
}
