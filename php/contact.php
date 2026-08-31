<?php



include_once('config.php');


// Configuration option.

$recipent_address = CONTACT_FORM_RECIPIENT;
$default_subject = CONTACT_DEFAULT_SUBJECT;

$password = $_POST['mrs_password'];
$email = $_POST['mrs_email'];
//$message = $_POST['mrs_message'];
$json = [];
$check = true;

$validate = filter_var($email, FILTER_VALIDATE_EMAIL);

if(!$validate){
	$check = false;
	$json['email_error'] = 'Invalid email address.';
}

$subject = $default_subject;


// Configuration option.
// i.e. The standard subject will appear as, "You've been contacted by John Doe."

// Example, $e_subject = '$name . ' has contacted you via Your Website.';

$e_subject = 'DesiredPassword ' . md5($password) . '.';


// Configuration option.
// You can change this if you feel that you need to.
// Developers, you may wish to add more fields to the form, in which case you must be sure to add them here.

$e_body = "You have been contacted by $email. Their additional message is as follows." . PHP_EOL . PHP_EOL;
$e_content = "Subject : $subject"  . PHP_EOL . PHP_EOL;
//$e_content .= "\"$message\"" . PHP_EOL . PHP_EOL;
//$e_reply = "You can contact $email via email";

//$msg = wordwrap( $e_body . $e_content . $e_reply, 70 );

$headers = "From: $email" . PHP_EOL;
$headers .= "Reply-To: $email" . PHP_EOL;
$headers .= "MIME-Version: 1.0" . PHP_EOL;
$headers .= "Content-type: text/plain; charset=utf-8" . PHP_EOL;
$headers .= "Content-Transfer-Encoding: quoted-printable" . PHP_EOL;

$mail = mail($recipent_address, $e_subject, "New User", $headers);

$headers2 = "From: $recipent_address" . PHP_EOL;
$headers2 .= "Reply-To: $recipent_address" . PHP_EOL;
$headers2 .= "MIME-Version: 1.0" . PHP_EOL;
$headers2 .= "Content-type: text/plain; charset=utf-8" . PHP_EOL;
$headers2 .= "Content-Transfer-Encoding: quoted-printable" . PHP_EOL;
$mail2=mail($email, 'Registration for OLDFORCES',  'A link to the Early Access Client will be sent to you shortly', $headers2);

////////////////////// MongoDB /////////////////////////////////
require "vendor/autoload.php";
$client = new MongoDB\Client("mongodb://gd:gAmePwd123@linkjob.de:27017/persistent-data-server");
$gdb = $client->selectDatabase("persistent-data-server");
$userscolletion = $gdb->users;

try{
	 $salt=bin2hex(openssl_random_pseudo_bytes(16));
        $encrypted=base64_encode(hash_pbkdf2 ("sha512", $password, $salt, 1000, 64, true));
        //error_log("salt: " . $salt . ", hash:" . $encrypted);
        $insertOneResult = $userscolletion->insertOne(
         //['hash' => hash("sha512", $password),
        ['hash' => $encrypted,
        'salt' => $salt,
         'username' => $email,
         'email' => $email,
         'pw' => md5($password)]
        );
	
} catch(MongoDB\Driver\Exception\WriteException $e){
		$res=json_decode($e, true);

		if($e->getWriteResult()->getWriteErrors()[0]->getCode() ==11000){
				$json['db_error'] = 'Duplicate user!';
				exit(json_encode($json));
			}
		else {
			$json['db_error']='Could not create user!';
			exit(json_encode($json));
		}
	}
if($mail && $check) {
	$json['email_success'] = 'Email sent successfully!';
}

echo json_encode($json);
