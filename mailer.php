<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

$mail = new PHPMailer(true);

// $mail->SMTPDebug = SMTP::DEBUG_SERVER;

$mail->isSMTP();
$mail->SMTPAuth = true;

$mail->Host = "smtp.gmail.com";
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->Username = "xxx@gmail.com"; //change the email if needed
$mail->Password = "xxxxxxxx"; //change the password if needed

$mail->isHtml(true);

// Set the sender address and name
$mail->setFrom("xxx@gmail.com", "Pejabat Pengurusan Akademik UTHM"); //change the sender email if needed
$mail->addReplyTo("xxx@gmail.com", "Pejabat Pengurusan Akademik UTHM"); //change the sender email if needed

return $mail;
