<?php
namespace Stanford\ProjCaFacts;
/** @var ProjCaFacts $module */

/*
2020-06-17 14:08:22	258.1	-	-	signup-ivr	7	require_once	[1/1]	ARR	Array
(
    [AccountSid] => AC40b3884912172e03b4b9c2c0ad8d2ae8
    [ApiVersion] => 2010-04-01
    [CallSid] => CAcffe80cb0064a145787cee6fa668fea2
    [CallStatus] => ringing
    [Called] => +16502036757
    [CalledCity] => 
    [CalledCountry] => US
    [CalledState] => CA
    [CalledZip] => 
    [Caller] => +16503803405
    [CallerCity] => PALO ALTO
    [CallerCountry] => US
    [CallerState] => CA
    [CallerZip] => 94304
    [Direction] => inbound
    [From] => +16503803405
    [FromCity] => PALO ALTO
    [FromCountry] => US
    [FromState] => CA
    [FromZip] => 94304
    [To] => +16502036757
    [ToCity] => 
    [ToCountry] => US
    [ToState] => CA
    [ToZip] => 
)
*/
$module->emDebug("Incoming Twilio Voice Call _POST:", $_POST);

require $module->getModulePath().'vendor/autoload.php';
use Twilio\TwiML\VoiceResponse;

// Load the text/languages
$filename   = $module->getModulePath() . "pages/ivr-phrases.csv";
$file       = fopen($filename, 'r');
$dict       = array();
while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
    $var_key    = trim($data[0]);
    $en_val     = trim($data[1]);
    $sp_val     = trim($data[2]);
    
    $dict[$var_key] = array(
        "en" => $en_val,
        "sp" => $sp_val
    );
}
fclose($file);
$module->emDebug($dict);

// BEGIN THE VOICE RESPONSE SCRIPT
$response = new VoiceResponse;
$response->say("Welcome to CA Facts", array('voice' => 'alice'));
print $response;

exit();

$module->emLog($_REQUEST, "Incoming Request - IVR" . " " . __DIR__);


if (! $module->parseIVRInput()) {
    $module->returnError("Invalid Request Parameters - check your syntax");
}

// Response is handled by $module
$module->IVRHandler();





// This is the IVR endpoint for Twilio

/*

V1: Enter code
V2: Enter your ZIP

--
Q1. Please select a language:
    1. English,
    2. Spanish
    3. Viet
    4. Chinese

Q2. Y/N Can ypou prick blood

Q3. How many people

Q4. Do you have a phone or computer?

if (yes)

Q5. Can you send you SMS messages on a phone number?
if (yes)

Q6. Enter Phone Number

 */
