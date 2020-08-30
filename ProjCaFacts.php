<?php
namespace Stanford\ProjCaFacts;

require_once "emLoggerTrait.php";

class ProjCaFacts extends \ExternalModules\AbstractExternalModule {
    use emLoggerTrait;

    // Fields in ACCESS CODE Project
    const FIELD_ACCESS_CODE         = 'access_code';
    const FIELD_ZIP                 = 'zip';
    const HOUSEHOLD_ID              = 'household_id';
    const QR_INPUT                  = 'qr_input';
    const TESTKIT_NUMBER            = 'participant_id';
    const FIELD_USED_ID             = 'participant_used_id';
    const FIELD_USED_DATE           = 'participant_used_date';
    const FIELD_USAGE_ATTEMPTS      = 'usage_attempts';

    // Fields in MAIN project
    const FIELD_KIT_HOUSEHOLD_CODE  = 'kit_household_code';
    const FIELD_KIT_SHIPPED_DATE    = 'kit_shipped_date';

    const FIELD_HHD_COMPLETE_DATE   = 'hhd_complete_date';
    const FIELD_HHD_RECORD_ID       = 'hhd_record_id';
    const FIELD_HHD_PARTICIPANT_ID  = 'hhd_participant_id';

    const FIELD_DEP1_COMPLETE_DATE  = 'dep_1_complete_date';
    const FIELD_DEP1_RECORD_ID      = 'dep_1_record_id';
    const FIELD_DEP1_PARTICIPANT_ID = 'dep_1_participant_id';

    const FIELD_DEP2_COMPLETE_DATE  = 'dep_2_complete_date';
    const FIELD_DEP2_RECORD_ID      = 'dep_2_record_id';
    const FIELD_DEP2_PARTICIPANT_ID = 'dep_2_participant_id';

    private   $access_code
            , $zip_code
            , $household_id
            , $testkit_number
            , $enabledProjects
            , $main_project_record
            , $main_project
            , $access_code_record
            , $access_code_project
            , $kit_submission_record
            , $kit_submission_project;

    // This em is enabled on more than one project so you set the mode depending on the project
    static $MODE;  // access_code_db, kit_order, kit_submission

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	    if (defined(PROJECT_ID)) {
	        // Get the mode of the module
	        self::$MODE = $this->getProjectSetting('em-mode');
	        $this->emDebug("In mode " . self::$MODE);
        }
        
        // put the proper project ids into class vars
        $this->getAllSupportProjects();
    }

    /**
     * Print all enabled projects with this EM
     */
    public function displayEnabledProjects($creation_xml_array) {
        // Scan
        $this->getEnabledProjects();
        ?>
        <table class="table table-striped table-bordered" style="width:100%">
            <tr>
                <th>EM Mode</th>
                <th>Project ID</th>
                <th>Project Name</th>
            </tr>
            <?php
            $modes = array("access_code_db", "kit_order", "kit_submission");
            foreach($modes as $mode){
                $pid    = isset($this->enabledProjects[$mode]) ? "<a target='_BLANK' href='" . $this->enabledProjects[$mode]['url'] . "'>" . $this->enabledProjects[$mode]['pid'] . "</a>" : "N/A";
                $pname  = isset($this->enabledProjects[$mode]) ?  $this->enabledProjects[$mode]['name'] : "<a href='".$creation_xml_array[$mode]."' target='_BLANK'>Create project [XML Template]</a>";
                echo "<tr>
                        <th>$mode</th>
                        <th>$pid</th>
                        <th>$pname</th>
                    </tr>";
            }
            ?>
        </table>
        <?php
    }

    /**
     * Load all enabled projects with this EM
     */
    public function getEnabledProjects() {
        $enabledProjects    = array();
        $projects           = \ExternalModules\ExternalModules::getEnabledProjects($this->PREFIX);
        while($project = db_fetch_assoc($projects)){
            $pid  = $project['project_id'];
            $name = $project['name'];
            $url  = APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $project['project_id'];
            $mode = $this->getProjectSetting("em-mode", $pid);
            
            $enabledProjects[$mode] = array(
                'pid'   => $pid,
                'name'  => $name,
                'url'   => $url,
                'mode'  => $mode
            );
            
        }

        $this->enabledProjects = $enabledProjects;
        // $this->emDebug($this->enabledProjects, "Enabled Projects");
    }

    /**
     * FIND SUPPORT PROJECTS AND THEIR PIDs
     * @return bool
     */
    public function getAllSupportProjects(){
        $this->getEnabledProjects();
        foreach($this->enabledProjects as $project){
            $pid            = $project["pid"];
            $project_mode   = $project["mode"];
            switch($project_mode){
                case "access_code_db":
                    $this->access_code_project = $pid;
                break;

                case "kit_order":
                    $this->main_project = $pid;
                break;

                case "kit_submission":
                    $this->kit_submission_project = $pid;
                break;
            }
        }
    }

    /**
     * Parses request and sets up object
     * @return bool request valid
     */
    public function parseFormInput() {
        $this->emDebug("Incoming POST AC + Zip: ", $_POST);
        
        if (empty($_POST)){
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
        $this->access_code   = isset($_POST[self::FIELD_ACCESS_CODE]) ? strtoupper(trim(filter_var($_POST[self::FIELD_ACCESS_CODE], FILTER_SANITIZE_NUMBER_INT))) : NULL ;
        $this->zip_code      = isset($_POST[self::FIELD_ZIP])         ? trim(filter_var($_POST[self::FIELD_ZIP], FILTER_SANITIZE_NUMBER_INT)) : NULL ;
        
        $valid               = (is_null($this->access_code) || is_null($this->zip_code)) ? false : true;
        return $valid;
    }

    /**
     * Verifies the invitation access code and marks it as used, and creates a record in the main project and returns a public survey URL 
     * @return bool survey url link
     */
    public function formHandler() {
        // Match INCOMING AccessCode Attempt and Verify ZipCode , find the record in the AC DB 
        $address_data = $this->getTertProjectData("access_code_db");
        if (!$address_data){
            $this->returnError("Error, no matching AC/ZIP combination found");
        }
        
        //AT THIS POINT WE HAVE THE ACCESS CODE RECORD, IT HASNT BEEN ABUSED, IT HASNT YET BEEN CLAIMED
        //0.  GET NEXT AVAIL ID IN MAIN PROJECT
        $next_id = $this->getNextAvailableRecordId($this->main_project);

        //1.  CREATE NEW RECORD, POPULATE these 2 fields
        $data = array(
            "record_id" => $next_id,
            "code"      => $this->access_code
        );
        if($address_data){
            foreach($address_data as $k => $v){
                if(in_array($k, array("record_id","participant_used_id","participant_used_date","usage_attempts","ca_facts_access_codes_complete"))){
                    continue;
                }
                $data[$k] = $v;
            }
        }
        $r    = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );

        //2.  UPDATE AC DB record with time stamp and "claimed" main record project
        $data = array(
            "record_id"             => $this->access_code_record,
            "participant_used_id"   => $next_id,
            "participant_used_date" => date("Y-m-d H:i:s")
        );
        $r    = \REDCap::saveData($this->access_code_project, 'json', json_encode(array($data)) );

        //3.  GET PUBLIC SURVEY URL WITH FIELDS LINKED
        $survey_link = \REDCap::getSurveyLink($record=$next_id, $instrument='invitation_questionnaire', $event_id='', $instance=1, $project_id=$this->main_project);

        // Return result
        header("Content-type: application/json");
        echo json_encode(array("survey_url" => $survey_link));
    }

    /**
     * Verifies the invitation access code and marks it as used, and creates a record in the main project with all the answers supplied via voice 
     * @return bool survey url link
     */
    public function IVRHandler($call_vars) {
        // $call_vars = array(
        //     [lang] => en
        //     [speaker] => Polly.Joanna
        //     [accent] => en-US
        //     [action] => invitation-phone
        //     [language] => 1
        //     [code] => 123456
        //     [zip] => 94123
        //     [fingerprick] => 1
        //     [testpeople] => 3
        //     [smartphone] => 1
        //     [sms] => 1
        //     [phone] => 14158469192
        // )

        $this->access_code   = $call_vars["code"];
        $this->zip_code      = $call_vars["zip"];

        // Match INCOMING AccessCode Attempt and Verify ZipCode , find the record in the AC DB 
        $address_data = $this->getTertProjectData("access_code_db");
        if (!$address_data){
            $this->returnError("Error, no matching AC/ZIP combination found");
        }
        
        //AT THIS POINT WE HAVE THE ACCESS CODE RECORD, IT HASNT BEEN ABUSED, IT HASNT YET BEEN CLAIMED
        //0.  GET NEXT AVAIL ID IN MAIN PROJECT
        $next_id = $this->getNextAvailableRecordId($this->main_project);

        //1.  CREATE NEW RECORD, POPULATE these 2 fields
        $data = array(
            "record_id" => $next_id
        );
        foreach($call_vars as $rc_var => $rc_val){
            if(in_array($rc_var, array("lang","speaker","accent","action","zip"))){
                continue;
            }
            $data[$rc_var] = $rc_val;
        }
        if($address_data){
            foreach($address_data as $k => $v){
                if(in_array($k, array("record_id","participant_used_id","participant_used_date","usage_attempts","ca_facts_access_codes_complete"))){
                    continue;
                }
                $data[$k] = $v;
            }
        }
        $r    = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
        $this->emDebug("DID IT SAVE???", $r, $data);

        //2.  UPDATE AC DB record with time stamp and "claimed" main record project
        $data = array(
            "record_id"             => $this->access_code_record,
            "participant_used_id"   => $next_id,
            "participant_used_date" => date("Y-m-d H:i:s")
        );
        $r    = \REDCap::saveData($this->access_code_project, 'json', json_encode(array($data)) );

        return false;
    }

    /**
     * Parses request and sets up object
     * @return bool request valid
     */
    public function parseKitQRInput() {
        if (empty($_POST)){
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
        $this->household_id     = isset($_POST[self::HOUSEHOLD_ID])     ? strtoupper(trim(filter_var($_POST[self::HOUSEHOLD_ID], FILTER_SANITIZE_STRING))) : NULL ;
        $this->testkit_number   = isset($_POST[self::TESTKIT_NUMBER])   ? trim(filter_var($_POST[self::TESTKIT_NUMBER], FILTER_SANITIZE_NUMBER_INT)) : NULL ;
        $this->qr_input         = isset($_POST[self::QR_INPUT])         ? trim(filter_var($_POST[self::QR_INPUT], FILTER_SANITIZE_STRING)) : NULL ;
        $valid                  = (is_null($this->household_id) || is_null($this->testkit_number)) ? false : true;
        $this->emDebug($valid);

        return $valid;
    }

    /**
     * Processes the KIT submission from the QR
     * @return bool survey url link
     */
    public function KitSubmitHandler() {
        $instrument = 'cafacts_surveys';

        // Match INCOMING HOUSEHOLD ID + TEST KIT #
        $kit_submit_record_id = $this->getTertProjectData("kit_submission");
        if (!$kit_submit_record_id){
            $this->returnError("Error, no matching household id found");
        }
        
        // AT THIS POINT WE SHOULD HAVE THE RECORD_ID OF THE KITSUBMISSION THAT MATCHES THE INPUT

        //SAVE THE QR INPUT FOR THIS TO USE LATER FOR INHOUSE MATCHING
        $data = array(
            "record_id"              => $kit_submit_record_id,
            "kit_qr_input"           => $this->qr_input 
        );
        $r    = \REDCap::saveData($this->kit_submission_project, 'json', json_encode(array($data)) );

        //GET PUBLIC SURVEY URL FOR THAT RECORD TO SEND BACK TO GAUSS TO DISPLAY TO THE USER
        $survey_link = \REDCap::getSurveyLink($kit_submit_record_id, $instrument, $event_id='', $instance=1, $project_id=$this->kit_submission_project);

        // Return result
        header("Content-type: application/json");
        echo json_encode(array("survey_url" => $survey_link));
    }

    /**
     * GET the KIT submission Record
     * @return bool record_id
     */
    public function getKitSubmissionId($qrscan) {
        $houseid    = $this->getHouseHoldId($qrscan);
        $this->emDebug("Got the HHID + SURVEYID FROM qrscan", $qrscan, $houseid);

        if(!empty($houseid)){
            $part_id    = $houseid["survey_id"];

            $filter     = "[household_id] = '" . $houseid["household_id"] . "'";
            $fields     = array("household_record_id","kit_upc_code","household_id","participant_id","record_id");
            $q          = \REDCap::getData($this->kit_submission_project, 'json', null , $fields  , null, null, false, false, false, $filter);
            $results    = json_decode($q,true);

            $this->emDebug("found the kit_submission_records", $results);

            //NOW FIND A MATCH OR FIRST AVAILABLE SLOT
            $unused_slots   = array();
            $matched_result = null;
            $found_match    = false;
            foreach ($results as $result) {
                $record_id  = $result["record_id"];
                $main_id    = $result["household_record_id"];

                if($result["participant_id"] == $part_id){
                    // found a match;
                    // break here and move on
                    $matched_result = $result;
                    $found_match    = true;
                    break;
                }

                if(empty($result["participant_id"])){
                    array_push($unused_slots, $result);
                }
            }

            if(!$found_match && !empty($unused_slots)){
                // made it here means no match, use first available slot
                $matched_result = array_shift($unused_slots);
            }

            //FIRSt SAVE TO KITSUBMISSION (participant_id) THEN SAVE BACK TO MAIN PROJECT available slot
            //have kitsubmit record_id, need to see if it matched dep_1_record_id, or dep_2_record_id
            if($matched_result){
                $kit_sub_id = $matched_result["record_id"];
                $main_id    = $matched_result["household_record_id"];
    
                if(empty($matched_result["participant_id"])){
                    //save to kit_submission_record
                    $data   = array(
                        "record_id"         => $kit_sub_id ,
                        "participant_id"    => $part_id
                    );
                    $result = \REDCap::saveData($this->kit_submission_project, 'json', json_encode(array($data)) );
                    $matched_result["participant_id"] = $part_id;
                }

                // now save this to the main record
                $fields     = array("hhd_record_id","hhd_participant_id","dep_1_record_id", "dep_1_participant_id", "dep_2_record_id" ,"dep_2_participant_id");
                $q          = \REDCap::getData($this->main_project, 'json', array($main_id) , $fields);
                $result     = current(json_decode($q,true));

                $check_ids  = array("hhd_record_id","dep_1_record_id","dep_2_record_id");
                $part_vars  = array("hhd_participant_id","dep_1_participant_id","dep_2_participant_id");
                $matching_var = null;
                foreach($check_ids as $idx => $check_id){
                    if($result[$check_id] == $kit_sub_id){
                        $matching_var = $part_vars[$idx];
                        break;
                    }
                }
                
                if($matching_var){
                    // SAVE TO REDCAP
                    $data   = array(
                        "record_id"        => $main_id ,
                        $matching_var      => $part_id
                    );
                    $result = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
                }
            }
            
            return $matched_result;  // can be null
        }
        return false;
    }
    
    /**
     * Get records of completed invitation questionaires that have not had kits shipped yet
     * @return array of records
     */
    public function getPendingInvites(){
        $fields     = array("record_id","testpeople", "code", "address_1" ,"address_2","city", "state", "zip");
        $filter     = "[code] != '' AND [kit_household_code] = '' AND [testpeople] <> ''";
        $q          = \REDCap::getData('json', null , $fields  , null, null, false, false, false, $filter);
        $results    = json_decode($q,true);
        return $results;
    }

    /**
     * Once household_id is obtained, need to pregenerate records in kit_submission project
     * @return array of recordids created
     */
    public function linkKits($main_record_id, $number_of_kits, $household_id, $head_of_household_id){
        // Set all appropriate project IDs
        $this->getAllSupportProjects();

        $new_ids = array();
        for ($i =1 ; $i <= $number_of_kits; $i++){
            $next_id = $this->getNextAvailableRecordId($this->kit_submission_project);

            $part_id = "";
            if($i == 1){
                $part_id    = $head_of_household_id;
                $link_var   = "hhd_record_id";
            }
            if($i == 2){
                $link_var   = "dep_1_record_id";
            }
            if($i == 3){
                $link_var   = "dep_2_record_id";
            }

            // SAVE TO REDCAP
            $data   = array(
                "record_id"             => $next_id,
                "household_record_id"   => $main_record_id,
                "participant_id"        => $part_id,
                "household_id"          => $household_id
            );
            
            $r = \REDCap::saveData($this->kit_submission_project, 'json', json_encode(array($data)) );

            // TODO THIS MAY NOT BE NECESSARY
            // Save Submission ID ?  // NOT SURE HOW THE ACTUAL PART ID GONNA LINK UP FROM GAUSS END
            $data   = array(
                "record_id"             => $main_record_id,
                $link_var               => $next_id
            );
            $r = \REDCap::saveData($this->main_project, 'json', json_encode(array($data)) );
        }
        return $new_ids;
    }  
    /**
     * Takes the result of a scan and sends off to Gauss to return ID
     * @return varchar unique house hold id
     */

    public function getHouseHoldId($qrscan){
        // remove URL SCheme 
        //TODO FIX THIS BETTER LATER
        $qrscan = preg_replace( "#^[^:/.]*[:/]+#i", "", $qrscan );
        $qrscan = str_replace("/?","?",$qrscan);
        $temp   = explode("#",$qrscan);
        $qrscan = $temp[0];
        // MIGHT HAVE TO DO SOME MORE CLEANING AFTER THE "#" 

        $url        = "https://c19.gauss.com/artemis/decryptqr";
        $key        = "hRDauDM9We2B3YfQSMzRA7WowaHaOhv98b54LStQ";
        $headers    = array( "x-api-key: " . $key );
        $data       = "encrypted_qrcode_data=$qrscan";

        try {
            // $resp = requests.post(url, data = data, headers = headers)
            $process = curl_init($url);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($process, CURLOPT_HTTPHEADER, $headers );
            curl_setopt($process, CURLOPT_POSTFIELDS, $data);
            
            $curlinfo   = curl_getinfo($process);
            $curlerror  = curl_error($process);
            $result     = curl_exec($process);
            curl_close($process);
            
        } catch (Exception $e) {
            exit( 'Decrypt API request failed: ' . $e->getMessage() );
        }

        $j = json_decode($result,1);
        return array("survey_id" => $j['survey_id'] , "household_id" => $j['household_id']);
    }

     /**
     * GET DATA FROM PROJECT DATA TIED TO THIS EM
     * @return bool
     */
    public function getTertProjectData($p_type) {
        foreach ($this->enabledProjects as $project_mode => $project_data) {
            $pid = $project_data["pid"];
            if($project_mode == $p_type){
                if($p_type == "access_code_db"){
                    $filter     = "[access_code] = '" . $this->access_code . "'"; //AND [zip] = '". $this->zip_code ."'
                    $q          = \REDCap::getData($pid, 'json', null , null  , null, null, false, false, false, $filter);
                    $results    = json_decode($q,true);

                    foreach ($results as $result) {
                        $ac_code_record             = $result["record_id"];
                        $current_attempt            = $result["usage_attempts"] ?? 0;
                        $redeemed_participant_id    = $result["participant_used_id"];
                        $redeemed_participant_date  = $result["participant_used_date"];


                        // LIMIT ATTEMPTS
                        if($current_attempt > 5){
                            $this->emDebug("Too many attempts to redeem this Access Code.", $this->access_code, $this->zip_code);
                            return false;
                        }

                        //INCREMENT USAGE ATTEMPTS
                        $data   = array(
                            "record_id"      => $ac_code_record,
                            "usage_attempts" => $current_attempt + 1
                        );
                        $r      = \REDCap::saveData($pid, 'json', json_encode(array($data)) );

                        //VERIFIY THAT THE CODE USED MATCHES ZIPCODE OF ADDRESS FOR IT
                        if($result['zip'] == $this->zip_code){
                            if(!empty($redeemed_participant_id) && !empty($redeemed_participant_date)){
                                $this->emDebug("This Access Code has already been claimed on ", $this->redeemed_participant_date);
                                return false;
                            }

                            $this->emDebug("Found a matching AC/ZIP for: ", $this->access_code, $this->zip_code);
                            $this->access_code_record   = $ac_code_record;
                            $this->access_code_project  = $pid;
                            return $result;
                        }
                    }

                    $this->emDebug("No match found for in Access Code DB for : ", $this->access_code );
                }

                if($p_type == "kit_submission"){
                    $filter     = "[household_id] = '" . $this->household_id . "' AND [participant_id] = '". $this->testkit_number ."'";
                    $q          = \REDCap::getData($pid, 'json', null , null  , null, null, false, false, false, $filter);
                    $results    = json_decode($q,true);

                    foreach ($results as $result) {
                        $record_id = $result["record_id"];
                        return $record_id;
                    }
                    
                    $this->emDebug("No match found for in HouseHold Id : ", $this->household_id );
                }
            }
        }
        return false;
    }

    /**
     * Set Temp Store Proj Settings
     * @param $key $val pare
     */
    public function setTempStorage($storekey, $k, $v) {
        $temp = $this->getTempStorage($storekey);
        $temp[$k] = $v;

        // THIS IS CAUSING TWILIO TO FAIL WHY? 
        $this->setProjectSetting($storekey, json_encode($temp));
        return; 
    }

    /**
     * Get Temp Store Proj Settings
     * @param $key $val pare
     */
    public function getTempStorage($storekey) {
        $temp = $this->getProjectSetting($storekey);
        $temp = empty($temp) ? array() : json_decode($temp,1);
        return $temp;
    }

    /**
     * rEMOVE Temp Store Proj Settings
     * @param $key $val pare
     */
    public function removeTempStorage($storekey) {
        $this->removeProjectSetting($storekey);
        return;
    }
    
    /**
     * Make a new redirect Action url, NOT IN USE NOW, BUT COULD POSSILBY BE USEFUL LATER
     * @param $action
     */
    public function makeActionUrl($action){
        $scheme             = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://");
        $curURL             = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url          = parse_url($curURL);
        $qsarr              = explode("&", urldecode($parse_url["query"]) );
        if(isset($_GET["action"]) ){
            foreach($qsarr as $i => $str){
                if(strpos($str,"action") > -1){
                    $this->emDebug("found action, remove it:", $str);
                    unset($qsarr[$i]);
                    break;
                }
            }
            array_unshift($qsarr,"action=".$action);
        }
        return $scheme . $parse_url["host"] . $parse_url["path"] . "?" . implode("&",$qsarr);
    }

    /**
     * Parse IVR Script + Translations
     * @param $filename
     */
    public function parseTextLanguages($filename) {
        $file       = fopen($filename, 'r');
        $dict       = array();
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $var_key    = trim($data[0]);
            $en_val     = trim($data[1]);
            $sp_val     = trim($data[2]);
            $zh_val     = trim($data[3]);
            $vi_val     = trim($data[4]);
            
            $dict[$var_key] = array(
                "en" => $en_val,
                "es" => $sp_val,
                "zh" => $zh_val,
                "vi" => $vi_val
            );
        }
        fclose($file);
        return $dict;
    }

    /**
     * GET Next available RecordId in a project
     * @return bool
     */
    public function getNextAvailableRecordId($pid){
        $pro                = new \Project($pid);
        $primary_record_var = $pro->table_pk;

        $q          = \REDCap::getData($pid, 'json', null, $primary_record_var );
        $results    = json_decode($q,true);
        if(empty($results)){
            $next_id = 1;
        }else{
            $last_entry = array_pop($results);
            $next_id    = $last_entry[$primary_record_var] + 1;
        }

        return $next_id;
    }

    /*
        Pull static files from within EM dir Structure
    */
    function getAssetUrl($audiofile = "v_languageselect.mp3", $hard_domain = "https://7fa27a8e30a1.ngrok.io"){
        $audio_file = $this->framework->getUrl("getAsset.php?file=".$audiofile."&ts=". $this->getLastModified() , true, true);
        $audio_file = str_replace("http://localhost",$hard_domain, $audio_file);

        $this->emDebug("The NO AUTH URL FOR AUDIO FILE", $audio_file); 
        return $audio_file;
    }
    
    function setLastModified(){
        $ts = time();
        $this->setSystemSetting("last_modified",$ts);
        $this->LAST_MODIFIED = $ts;
    }

    function getLastModified(){
        return time();

        if(empty($this->LAST_MODIFIED)){
	        $ts = $this->getSystemSetting("last_modified");
	        if(empty($ts)){
                $this->setLastModified();
            }else{
                $this->LAST_MODIFIED = $ts;
            }
        }

	    return $this->LAST_MODIFIED;
    }

    /**
     * Return an error
     * @param $msg
     */
    public function returnError($msg) {
        $this->emDebug($msg);
        header("Content-type: application/json");
        echo json_encode(array("error" => $msg));
        exit();
    }

    /*
        USE mail func
    */
    public function sendEmail($subject, $msg, $from="Twilio VM", $to="ca-factstudy@stanford.edu"){
        //boundary
        $semi_rand = md5(time());
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

        //headers for attachment
        //header for sender info
        $headers = "From: "." <".$from.">";
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

        //multipart boundary
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
            "Content-Transfer-Encoding: 7bit\n\n" . $msg . "\n\n";

        if (!mail($to, $subject, $message, $headers)) {
            $this->emDebug("Email NOT sent");
            return false;
        }
        $this->emDebug("Email sent");
        return true;
    }

    /* 
        Parse CSV to batchupload test Results
    */
    public function parseCSVtoDB($file){
        $this->emDebug("im in the parseCSV vfucnitoin", $file);

        $header_row = true;
        $file       = fopen($file['tmp_name'], 'r');

        $headers    = array();
        $results    = Array();

        if($file){
            while (($line = fgetcsv($file)) !== FALSE) {
                if($header_row){
                    // adding extra column to determine which file the data came from
                    $headers 	= $line;
                    $header_row = false;
                }else{
                    // adding extra column to determine which csv file the data came from
                    array_push($results, $line);
                }
            }
            fclose($file);
        }
        

        $main_data_buffer   = array();
        $main_data_update   = array();
        $kit_data_update    = array();
        foreach($results as $result){
            $upc            = $result[0];
            $test_result    = $result[1];
            $date_complete  = $result[2];

            //FIRST FIND THE kit_submission_record by UPC
            $filter     = "[kit_upc_code] = '" . $upc . "'";
            $fields     = array("household_record_id","household_id","participant_id","record_id");
            $q          = \REDCap::getData($this->kit_submission_project, 'json', null , $fields  , null, null, false, false, false, $filter);
            $results    = json_decode($q,true);
            if(!empty($results)){
                $result         = current($results);

                $kit_sub_id     = $result["record_id"];
                $main_id        = $result["household_record_id"];
                $part_id        = $result["participant_id"];
                $household_id   = $result["household_id"];
                
                //UPDATE the [test_result] in Kit_submission record
                $data[] = array(
                    "record_id"     => $kit_sub_id,
                    "test_result"   => $test_result
                );
                
                // UPDATE the date completed in the main project
                // get main Record from RC
                if(array_key_exists($main_id, $main_data_buffer)){
                    $main_result    = $main_data_buffer[$main_id];
                }else{
                    $fields         = array("hhd_record_id","hhd_participant_id","dep_1_record_id", "dep_1_participant_id", "dep_2_record_id" ,"dep_2_participant_id");
                    $q              = \REDCap::getData($this->main_project, 'json', array($main_id) , $fields);
                    $results        = json_decode($q,true);
                    $main_result    = !empty($results) ? current($results) : array();
                    $main_data_buffer[$main_id] = $main_result;
                }
                
                if(!empty($main_result)){
                    $check_ids  = array("hhd_record_id","dep_1_record_id","dep_2_record_id");
                    $date_vars  = array("hhd_complete_date","dep_1_complete_date","dep_2_complete_date");
                    $matching_var = null;
                    foreach($check_ids as $idx => $check_id){
                        if($main_result[$check_id] == $kit_sub_id){
                            $matching_var = $date_vars[$idx];
                            break;
                        }
                    }
                    
                    if($matching_var){
                        // SAVE TO REDCAP
                        if(isset($main_data_update[$main_id])){
                            $main_data_update[$main_id][$matching_var ] = $date_complete;
                        }else{
                            $main_data_update[$main_id]   = array(
                                "record_id"             => $main_id,
                                $matching_var           => $date_complete
                            );
                        }
                    }
                }
            }else{
                //DO SOMETHING WITH RECORDS NOT FOUND?
            }
        }        
        $r  = \REDCap::saveData($this->main_project, 'json', json_encode($main_data_update) );
        $this->emDebug("did it not save to main?", $main_data_update);
        $r  = \REDCap::saveData($this->kit_submission_project, 'json', json_encode($data) );
        return;
    }
}
?>