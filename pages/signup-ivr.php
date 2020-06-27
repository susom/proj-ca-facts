<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

require $module->getModulePath().'vendor/autoload.php';
use Twilio\TwiML\VoiceResponse;

$module->getAllSupportProjects();

// Load the text/languages INTO SESSION BUT SESSION DOESNT WORK!
$lang_file	= $module->getModulePath() . "pages/ivr-phrases.csv";	
$dict 		= $module->parseTextLanguages($lang_file);

// BEGIN THE VOICE RESPONSE SCRIPT
$response 	= new VoiceResponse;

// IF SESSION DOESNT WORK, THEN WILL HAVE TO PASS THESE VARS IN THE GET FROM REQUEST TO REQUEST
$action 	= isset($_GET["action"]) 	? $_GET["action"] 	: null;
$speaker 	= isset($_GET["speaker"]) 	? $_GET["speaker"] 	: "Polly.Joanna";
$lang 		= isset($_GET["lang"]) 		? $_GET["lang"] 	: "en";
$accent 	= isset($_GET["accent"]) 	? $_GET["accent"] 	: "en-US";
$choice 	= isset($_POST["Digits"]) 	? $_POST["Digits"] 	: null;
$temp_call_storage_key = $_POST["CallSid"];

switch($lang){
	case "es":
		$lang_modifier = "_s";
	break;
	case "vi":
		$lang_modifier = "_v";
	break;
	case "zh":
		$lang_modifier = "_m";
	break;
	
	default:
		$lang_modifier = ""; 
	break;
}

// FIRST CONTACT 
if(isset($_POST["CallStatus"]) && $_POST["CallStatus"] == "ringing"){
	$module->emDebug("Incoming Twilio _POST:", $_POST);

	// Say Welcome
	$response->say($dict["welcome"][$lang], array('voice' => $speaker, 'language' => $accent));
	$response->pause(['length' => 1]);

	// Use the <Gather> verb to collect user input
	$gather 	= $response->gather(['numDigits' => 1]); 
	foreach($dict["language-select"] as $lang => $prompt){
		switch($lang){
			case "es": 
				$accent 	= "es-MX";
				$speaker 	= "Polly.Conchita";
			break;

			case "zh": 
				$accent 	= "zh-TW";
				$speaker 	= "alice";
			break;

			case "vi": 
				// will need to play a recording for 
				$accent 	= "zh-HK";
				$speaker 	= "alice";
			break;

			default:
				$accent 	= "en-US";
				$speaker 	= "Polly.Joanna";
			break;
		}

		// use the <Say> verb to request input from the user
		$gather->say($prompt, ['voice' => $speaker, 'language' => $accent] );
	}
}

// ALL SUBSEQUENT RESPONSES WILL HIT THIS SAME ENDPOINT , DIFFERENTIATE ON "action"
// TODO ONCE GET FLOW IN, NEED TO TIDY/ORGANIZE IT NEATLY
if(isset($_POST["CallStatus"]) && $_POST["CallStatus"] == "in-progress"){
	$response->pause(['length' => 1]);

	$module->emDebug("GET", $_GET);
	if($action == "interest-thanks"){
		$response->say($dict["interest-thanks"][$lang], ['voice' => $speaker, 'language' => $accent]);
		$response->pause(['length' => 1]);
		switch($choice){
			case 2:
				// questions path
				$action_url = $module->makeActionUrl("questions-haveAC");
				$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 6, 'finishOnKey' => '#']); 
				$gather->say($dict["questions-haveAC"][$lang], ['voice' => $speaker, 'language' => $accent] );
			break;

			default:
				// 1, invitation path
				$action_url = $module->makeActionUrl("invitation-code");
				$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 6]); 
				$gather->say($dict["invitation-code"][$lang], ['voice' => $speaker, 'language' => $accent] );
			break;
		}
	}elseif($action == "questions-haveAC"){
		switch($choice){
			case 0:
				// NO ACCESS CODE, ASK THEM TO LEAVE A VM WITH CONTACTS
				$response->say($dict["questions-leaveInfo"][$lang], ['voice' => $speaker, 'language' => $accent]);
				$response->pause(['length' => 1]);

				// RECORD MESSAGE AFTER BEEP
				$action_url = $module->makeActionUrl("questions-thanks");
				$response->record(['action' => $action_url, 'timeout' => 10, 'maxLength' => 15, 'transcribe' => 'true']); //transcribeCallback = [URL for ASYNC HIT WHEN DONE]
			break;

			default:
				// 123456, INPUT ACCESS CODE, REDIRECT TO INVITATION FLOW
				$action_url = $module->makeActionUrl("invitation-zip");
				$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 5]); 
				$gather->say($dict["invitation-zip"][$lang], ['voice' => $speaker, 'language' => $accent] );
			break;
		}
	}elseif($action == "questions-thanks"){
		// ALL DONE QUESTIONS PATH, SAY GOODBYE AND HANG UP
		// [RecordingDuration] => 20
		// [RecordingSid] => REcff30a178d6ac306c1535e30931fa406
		// [RecordingUrl] => https://api.twilio.com/2010-04-01/Accounts/ACacac91f9bd6f40e13e4a4a838c8dffce/Recordings/REcff30a178d6ac306c1535e30931fa406
		// TODO, SAVE THIS FILE? or JUST FGET SAVE THE RECORDING?
		$module->emDebug("THE RECORDING VM???", $_POST["RecordingUrl"]);
		$response->pause(['length' => 1]);
		$response->say($dict["questions-thanks"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}elseif($action == "invitation-code"){

		$action_url = $module->makeActionUrl("invitation-zip");
		$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 5]); 
		$gather->say($dict["invitation-zip"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}elseif($action == "invitation-zip"){
		$action_url = $module->makeActionUrl("invitation-finger");
		$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 1]); 
		$gather->say($dict["invitation-finger"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}elseif($action == "invitation-finger"){
		switch($choice){
			case 2:
				//NO
				$response->say($dict["invitation-nofinger"][$lang], ['voice' => $speaker, 'language' => $accent] );
			break;

			default:
				// 1, YES
				$rc_var = "fingerprick" . $lang_modifier;
				$rc_val = $choice;
				$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );

				$action_url = $module->makeActionUrl("invitation-testpeople");
				$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 1]); 
				$gather->say($dict["invitation-testpeople"][$lang], ['voice' => $speaker, 'language' => $accent] );
			break;
		}
	}elseif($action == "invitation-testpeople"){
		switch($choice){
			case 3:
			case 2:
			case 1:
				$rc_var = "testpeople" . $lang_modifier;
				$rc_val = $choice;
				$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );
			break;

			default:
				// # other than 4 , repeat previous step
				$action_url = $module->makeActionUrl("invitation-testpeople");
				$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 1]); 
				$gather->say($dict["invitation-testpeople"][$lang], ['voice' => $speaker, 'language' => $accent] );
			break;
		}
		$action_url = $module->makeActionUrl("invitation-smartphone");
		$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 1]); 
		$gather->say($dict["invitation-smartphone"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}elseif($action == "invitation-smartphone"){
		switch($choice){
			case 2:
				//NO
				$choice = 0;
			default:
				//1 YES
				$rc_var = "smartphone" . $lang_modifier;
				$rc_val = $choice;
				$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );
			break;
		}
		$action_url = $module->makeActionUrl("invitation-sms");
		$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 1]); 
		$gather->say($dict["invitation-sms"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}elseif($action == "invitation-sms"){
		switch($choice){
			case 2:
				//NO
				$choice = 0;
			default:
				//1 YES
				$rc_var = "sms" . $lang_modifier;
				$rc_val = $choice;
				$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );
			break;
		}
		$action_url = $module->makeActionUrl("invitation-phone");
		$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 10]); 
		$gather->say($dict["invitation-phone"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}elseif($action == "invitation-phone"){
		$phonenum 	= $choice;
		$module->emDebug("THE PHONENUMBER!", $phonenum);

		$rc_var = "phone" . $lang_modifier;
		$rc_val = $phonenum;
		$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );
		
		$temp 	= $module->getTempStorage($temp_call_storage_key);
		$module->emDebug( "HERE IS THE COMPLETE TEMPSTORAGE", $temp );

		// ALL DONE INVITATION PATH, SAY GOODBYE AND HANG UP
		$response->say($dict["invitation-done"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}else{
		// SET LANGUAGE (into SESSION) AND PROMPT FOR Kit Order / Questions
		switch($_POST["Digits"]){
			case 2:
				$lang 		= "es";
				$accent		= "es-MX";
				$speaker	= "Polly.Conchita";
				$rc_val 	= 2;
			break;

			case 3:
				$lang 		= "zh";
				$accent		= "zh-TW";
				$speaker	= "alice";
				$rc_val 	= 4;
			break;

			case 4:
				$lang 		= "vi";
				$accent		= "zh-TW";
				$speaker	= "alice";
				$rc_val 	= 3;
			break;

			default:
				$lang 		= "en";
				$accent		= "en-US";
				$speaker	=  "Polly.Joanna";
				$rc_val 	= 1;
			break;
		}

		$module->setTempStorage($temp_call_storage_key , "language", $rc_val );
		
		// FIRST TIME BUILD THE action URL MANUALLY, this will let all subsequent requests have memory of language choice, future calls, will alter the action in the url
		$scheme             = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
        $curURL             = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url          = parse_url($curURL);
		$qsarr              = explode("&", urldecode($parse_url["query"]) );
		array_unshift($qsarr,"lang=".$lang);
        array_unshift($qsarr,"speaker=".$speaker);
        array_unshift($qsarr,"accent=".$accent);
        array_unshift($qsarr,"action=interest-thanks");

        $action_url = $scheme . $parse_url["host"] . $parse_url["path"] . "?" . implode("&",$qsarr);
		$gather 	= $response->gather(['action' => $action_url, 'numDigits' => 1]); 
		$gather->say($dict["call-type"][$lang], ['voice' => $speaker, 'language' => $accent] );
	}
}

print($response);
$response->pause(['length' => 1]);
$response->hangup();
exit();