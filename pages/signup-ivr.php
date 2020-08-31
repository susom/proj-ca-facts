<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

require $module->getModulePath().'vendor/autoload.php';
use Twilio\TwiML\VoiceResponse;

// Set all appropriate project IDs
$module->getAllSupportProjects();

// Load the text/languages INTO SESSION BUT SESSION DOESNT WORK!
$lang_file	= $module->getModulePath() . "docs/ivr-phrases.csv";	
$dict 		= $module->parseTextLanguages($lang_file);

// BEGIN THE VOICE RESPONSE SCRIPT
$response 	= new VoiceResponse;

// POST FROM TWILIO
$temp_call_storage_key 	= trim(filter_var($_POST["CallSid"], FILTER_SANITIZE_STRING));
$choice 				= isset($_POST["Digits"]) 	? trim(filter_var($_POST["Digits"], FILTER_SANITIZE_STRING)) 	: null;

// CALL TEMP STORAGE - PERSISTS THROUGH OUT CALL
$module->emDebug("POST FROM TWILIO", $_POST);
$call_vars 	= $module->getTempStorage($temp_call_storage_key);
$action 	= isset($call_vars["action"]) 	? $call_vars["action"] 	: "n/a";
$speaker 	= isset($call_vars["speaker"]) 	? $call_vars["speaker"] : "Polly.Joanna";
$accent 	= isset($call_vars["accent"]) 	? $call_vars["accent"] 	: "en-US";
$lang 		= isset($call_vars["lang"]) 	? $call_vars["lang"] 	: "en";
$module->emDebug("Psuedo 'SESSION' functionality throughout call",$temp_call_storage_key, $call_vars);

// STRUCTURE OF REDCAP PRJECT WILL NEED TO MODIFY THE ENGLISH field_names for OTHER LANGUAGEs 
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

// MONITOR THE POST FOR [Recording Sid] , [RecordingUrl] and send an email if it is present
if(!empty($_POST["RecordingSid"]) && !empty($_POST["RecordingUrl"]) ){
	$languages 		= array("es" => "Spanish", "zh" => "Chinese", "vi" => "Vietnamese", "en" => "English");
	$language 		= isset($languages[$lang]) ? $languages[$lang] : "English";
	$recording_url 	= trim(filter_var($_POST["RecordingUrl"], FILTER_SANITIZE_STRING));
	$subject 		= "CA-FACTS IVR VM ($language)";
	$msg 	 		= "<a href='".$recording_url."'>Click to listen to voicemail.</a>";
	$module->sendEmail($subject, $msg);
	$module->emDebug("Got a Voicemail, emailing!");
}

// FIRST CONTACT 
if(isset($_POST["CallStatus"]) && $_POST["CallStatus"] == "ringing"){
	$module->emDebug("First Contact");

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
		if($lang == "vi"){
			$module->emDebug("language VIetnamese play mp3", $module->getAssetUrl("v_languageselect.mp3"));
			$gather->play($module->getAssetUrl("v_languageselect.mp3"));
		}else{
			$gather->say($prompt, ['voice' => $speaker, 'language' => $accent] );
		}
	}
}

// ALL SUBSEQUENT RESPONSES WILL HIT THIS SAME ENDPOINT , DIFFERENTIATE ON "action"
// TODO ONCE GET FLOW IN, NEED TO TIDY/ORGANIZE IT NEATLY
if(isset($_POST["CallStatus"]) && $_POST["CallStatus"] == "in-progress"){
	$response->pause(['length' => 1]);

	if($action == "interest-thanks"){
		$response->say($dict["interest-thanks"][$lang], ['voice' => $speaker, 'language' => $accent]);
		$response->pause(['length' => 1]);
		
		switch($choice){
			case 2:
				// questions path
				$module->setTempStorage($temp_call_storage_key , "action", "questions-haveAC" );
				$gather 	= $response->gather(['numDigits' => 6, 'finishOnKey' => '#']); 
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_q_haveAC.mp3"));
				}else{
					$gather->say($dict["questions-haveAC"][$lang], ['voice' => $speaker, 'language' => $accent] );
				}
			break;

			default:
				// 1, invitation path
				$module->setTempStorage($temp_call_storage_key , "action", "invitation-code" );
				$gather 	= $response->gather(['numDigits' => 6]); 
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_i_code.mp3"));
				}else{
					$gather->say($dict["invitation-code"][$lang], ['voice' => $speaker, 'language' => $accent] );
				}
			break;
		}
	}elseif($action == "questions-haveAC"){
		switch($choice){
			case 0:
				// NO ACCESS CODE, ASK THEM TO LEAVE A VM WITH CONTACTS
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_q_leaveinfo.mp3"));
				}else{
					$response->say($dict["questions-leaveInfo"][$lang], ['voice' => $speaker, 'language' => $accent]);
				}
				$response->pause(['length' => 1]);

				// RECORD MESSAGE AFTER BEEP
				$module->setTempStorage($temp_call_storage_key , "action", "questions-thanks" );

				//TODO IF YOU DONT LETTHE WHOLE TIME PLAY OUT IT WONT POST BACK??? WHAT THE HECK
				$response->record(['timeout' => 10, 'maxLength' => 15, 'transcribe' => 'true']); //transcribeCallback = [URL for ASYNC HIT WHEN DONE]
			break;

			default:
				// 123456, INPUT ACCESS CODE, REDIRECT TO INVITATION FLOW
				$rc_var = "code";
				$rc_val = $choice;
				$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );

				$module->setTempStorage($temp_call_storage_key , "action", "invitation-zip" );
				$gather 	= $response->gather(['numDigits' => 5]); 
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_i_zip.mp3"));
				}else{
					$gather->say($dict["invitation-zip"][$lang], ['voice' => $speaker, 'language' => $accent] );
				}	
			break;
		}
	}elseif($action == "questions-thanks"){
		// ALL DONE QUESTIONS PATH, SAY GOODBYE AND HANG UP
		// [RecordingDuration] => 20
		// [RecordingSid] => REcff30a178d6ac306c1535e30931fa406
		// [RecordingUrl] => https://api.twilio.com/2010-04-01/Accounts/ACacac91f9bd6f40e13e4a4a838c8dffce/Recordings/REcff30a178d6ac306c1535e30931fa406
		// MAIL THE VM TO CAFACTS EMAIL BOX
		// NEED TO MOVE THE SEND EMAIL PART OUT OF THIS FLOW BECAUSE a "hangup" IS CONSIDERED a "completed" call
		$response->pause(['length' => 1]);
		if($lang == "vi"){
			$gather->play($module->getAssetUrl("v_q_thanks.mp3"));
		}else{
			$response->say($dict["questions-thanks"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}
	}elseif($action == "invitation-code"){
		$rc_var = "code";
		$rc_val = $choice;
		$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );
		
		$module->setTempStorage($temp_call_storage_key , "action", "invitation-zip" );
		$gather 	= $response->gather(['numDigits' => 5]); 
		if($lang == "vi"){
			$gather->play($module->getAssetUrl("v_i_zip.mp3"));
		}else{
			$gather->say($dict["invitation-zip"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}
	}elseif($action == "invitation-zip"){
		$rc_var = "zip";
		$rc_val = $choice;
		$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );
		
		$module->setTempStorage($temp_call_storage_key , "action", "invitation-finger" );
		$gather 	= $response->gather(['numDigits' => 1]); 
		if($lang == "vi"){
			$gather->play($module->getAssetUrl("v_i_finger.mp3"));
		}else{
			$gather->say($dict["invitation-finger"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}
	}elseif($action == "invitation-finger"){
		switch($choice){
			case 2:
				//NO
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_i_nofinger.mp3"));
				}else{
					$response->say($dict["invitation-nofinger"][$lang], ['voice' => $speaker, 'language' => $accent] );
				}
			break;

			default:
				// 1, YES
				$rc_var = "fingerprick" . $lang_modifier;
				$rc_val = $choice;
				$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );

				$module->setTempStorage($temp_call_storage_key , "action", "invitation-testpeople" );
				$gather 	= $response->gather(['numDigits' => 1]); 
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_i_testpeople.mp3"));
				}else{
					$gather->say($dict["invitation-testpeople"][$lang], ['voice' => $speaker, 'language' => $accent] );
				}
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
				$module->setTempStorage($temp_call_storage_key , "action", "invitation-testpeople" );
				$gather 	= $response->gather(['numDigits' => 1]); 
				if($lang == "vi"){
					$gather->play($module->getAssetUrl("v_i_testpeople.mp3"));
				}else{
					$gather->say($dict["invitation-testpeople"][$lang], ['voice' => $speaker, 'language' => $accent] );
				}
			break;
		}
		$module->setTempStorage($temp_call_storage_key , "action", "invitation-smartphone" );
		$gather 	= $response->gather(['numDigits' => 1]); 
		if($lang == "vi"){
			$gather->play($module->getAssetUrl("v_i_smartphone.mp3"));
		}else{
			$gather->say($dict["invitation-smartphone"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}
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
		$module->setTempStorage($temp_call_storage_key , "action", "invitation-phone" );
		$gather 	= $response->gather(['numDigits' => 10]); 
		if($lang == "vi"){
			$gather->play($module->getAssetUrl("v_i_phone.mp3"));
		}else{
			$gather->say($dict["invitation-phone"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}
	}elseif($action == "invitation-phone"){
		$phonenum 	= $choice;

		$rc_var = "phone" . $lang_modifier;
		$rc_val = $phonenum;
		$module->setTempStorage($temp_call_storage_key , $rc_var, $rc_val );

		// ALL DONE INVITATION PATH, SAY GOODBYE AND HANG UP
		if($lang == "vi"){
			$response->play($module->getAssetUrl("v_i_done.mp3"));
		}else{
			$response->say($dict["invitation-done"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}

		// STORE THE FULL RECORD
		$all_vars = $module->getTempStorage($temp_call_storage_key);
		$module->emDebug( "HERE IS THE COMPLETE TEMPSTORAGE- SAVE TO REDCAP BOYEE", $all_vars );

		// DONT VERIFY AC OR ZIP UNTIL ALL THE WAY AT THE END?
		//TODO PROBABLY NEED TO VERIFY AC/ZIP AT THE TOP?
		$module->IVRHandler($all_vars);
		
		// DELETE THE TEMP STORAGE?
		$module->removeTempStorage($temp_call_storage_key);
	}else{
		// SET LANGUAGE (into SESSION) AND PROMPT FOR Kit Order / Questions
		switch($choice){
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
		
		// NEED TO START STORING VARS FOR DURATION OF THIS CALL
		$module->setTempStorage($temp_call_storage_key , "lang", $lang );
		$module->setTempStorage($temp_call_storage_key , "language", $rc_val );
		$module->setTempStorage($temp_call_storage_key , "speaker", $speaker );
		$module->setTempStorage($temp_call_storage_key , "accent", $accent );
		$module->setTempStorage($temp_call_storage_key , "action", "interest-thanks" );
		
		// GATHER RESPONSE FOR NEXT CALL/RESPONSE
		$gather 	= $response->gather(['numDigits' => 1]); 
		if($lang == "vi"){
			$module->emDebug("language VIetnamese play mp3");
			$gather->play($module->getAssetUrl("v_calltype.mp3"));
		}else{
			$gather->say($dict["call-type"][$lang], ['voice' => $speaker, 'language' => $accent] );
		}
	}
}

print($response);
$response->pause(['length' => 1]);
$response->hangup();
exit();