<?php

$email_to =   'atomaka@gmail.com';
$name     =   'sc2mmr Site';  
$email    =   $_POST['contactemail'];  
$subject  =   'sc2mmr Feedback';  
$message  =   $_POST['contactcomment'];  

$headers = 'From: ' . $email . "\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion(); 
  
if(mail($email_to, $subject, $message, $headers)) {  
    echo 'sent'; 
} else {  
    echo 'failed'; 
}
?>