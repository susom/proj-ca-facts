<?php
namespace Stanford\ProjCaFacts;

require_once "emLoggerTrait.php";

class ProjCaFacts extends \ExternalModules\AbstractExternalModule {
    use emLoggerTrait;

    // Fields in ACCESS CODE Project
    const FIELD_ACCESS_CODE         = 'access_code';
    const FIELD_ZIP                 = 'zip';
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
    }

    /**
     * Print all enabled projects with this EM
     */
    public function displayEnabledProjects() {
        // Scan
        $this->getEnabledProjects();
        ?>
        <table class="table table-striped table-bordered">
            <tr>
                <th>Project ID</th>
                <th>Project Name</th>
            </tr>
            <?php
            foreach ($this->enabledProjects as $project) {
                echo "<tr><td><a target='_BLANK' href='" . $project['url'] . "'>" . $project['pid'] . "</a></td><td>" . $project['name'] . "</td></tr>";
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

            $enabledProjects[$pid] = array(
                'pid'   => $pid,
                'name'  => $name,
                'url'   => $url
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
            $pid = $project["pid"];
            $project_mode = $this->getProjectSetting('em-mode', $pid);
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
        
        // TODO add filter VAR
        if (empty($_POST)){
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
        $this->access_code   = isset($_POST[self::FIELD_ACCESS_CODE]) ? strtoupper(trim($_POST[self::FIELD_ACCESS_CODE])) : NULL ;
        $this->zip_code      = isset($_POST[self::FIELD_ZIP])         ? trim($_POST[self::FIELD_ZIP]) : NULL ;
        
        $valid               = (is_null($this->access_code) || is_null($this->zip_code)) ? false : true;
        $this->emDebug($valid);

        return $valid;
    }

    public function formHandler() {
        // GET ALL projects with this EM installed
        $this->getAllSupportProjects();

        // Match INCOMING AccessCode Attempt and Verify ZipCode , find the record in the AC DB 
        if (!$this->getTertProjectData("access_code_db")){
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
     * Set Temp Store Proj Settings
     * @param $key $val pare
     */
    public function setTempStorage($storekey, $k, $v) {
        $temp = $this->getTempStorage($storekey);
        $temp[$k] = $v;

        // THIS IS CAUSING TWILIO TO FAIL WHY? 
        // $this->setSystemSetting($storekey, json_encode($temp));
        return; 
    }

    /**
     * Get Temp Store Proj Settings
     * @param $key $val pare
     */
    public function getTempStorage($storekey) {
        $temp = $this->getSystemSetting($storekey);
        $temp = empty($temp) ? array() : json_decode($temp,1);
        return $temp;
    }

    /**
     * Make a new redirect Action url
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
     * GET DATA FROM PROJECT DATA TIED TO THIS EM
     * @return bool
     */
    public function getTertProjectData($p_type) {
        foreach ($this->enabledProjects as $pid => $project_data) {
            $project_mode   = $this->getProjectSetting('em-mode', $pid);
            $this->emDebug("em_mode project id" ,$project_mode, $pid);
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
                        if($current_attempt > 5 && 1==2){
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
                            return true;
                        }
                    }

                    $this->emDebug("No match found for in Access Code DB for : ", $this->access_code );
                }

                if($p_type == "kit_submission"){
                    //TODO
                    return true;
                }
            }
        }

        return false;
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
    function getAssetUrl($file){
        $this->emDebug("sup getAssetURL");

        // return "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3";

        return "http://ff2e6ed49db1.ngrok.io/modules-local/proj_ca_facts_v9.9.9/docs/audio/v_calltype.mp3";

	    return $this->framework->getUrl("getAsset.php?file=".$file."&ts=". $this->getLastModified() , true, true);
    }
    
    function setLastModified(){
        $ts = time();
        $this->setSystemSetting("last_modified",$ts);
        $this->LAST_MODIFIED = $ts;
    }

    function getLastModified(){
        return 123456;

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

    function emLog() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "INFO");
    }

    function emDebug() {
        // Check if debug enabled
        if ( $this->getSystemSetting('enable-system-debug-logging') || ( !empty($_GET['pid']) && $this->getProjectSetting('enable-project-debug-logging'))) {
            $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
            $emLogger->emLog($this->PREFIX, func_get_args(), "DEBUG");
        }
    }

    function emError() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "ERROR");
    }
}
