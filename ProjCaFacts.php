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
        $this->emDebug($this->enabledProjects, "Enabled Projects");
    }

    /**
     * Parses request and sets up object
     * @return bool request valid
     */
    public function parseFormInput() {
        $this->emDebug("Incoming POST AC + Zip: ", $_POST);
        
        // TODO add filter VAR
        $this->access_code   = isset($_POST[self::FIELD_ACCESS_CODE]) ? strtoupper(trim($_POST[self::FIELD_ACCESS_CODE])) : NULL ;
        $this->zip_code      = isset($_POST[self::FIELD_ZIP])         ? trim($_POST[self::FIELD_ZIP]) : NULL ;
        
        //TODO any other data?

        $valid               = (is_null($this->access_code) || is_null($this->zip_code)) ? false : true;
        $this->emDebug($valid);

        return $valid;
    }

    public function formHandler() {

        // GET ALL tagged projects with this EM 
        $this->getEnabledProjects();

        // MAKE SURE THERE IS A PROJECT CONTAINING THE ACCESS CODES 
        if (!$this->getAccessCodeProject())   $this->returnError("Unable to find an active project holding access codes");

        //TODO WHAT ACTIONS GO HERE?  REDIRECT TO RC SURVEY?

        //AT THIS POINT WE HAVE THE ACCESS CODE RECORD, 
        //WHAT DO I DO WITH IT? 
        $result = $access_code_record;
        // or get the PUBLIC SURVEY URL and redirect there?
        // need to do some linking from kit_submission?
        // so after survey complete hook?

        //0.  GET NEXT AVAIL ID
        //1.  CREATE NEW RECORD
        //2.  POPULATE those 2 fields
        //3.  REDIRECT TO THAT SURVEY URL

        //FOR NOW? JUST RETURN THE ENTIRE POST FOR NOW?
        $result = $_POST;

        // Return result
        header("Content-type: application/json");
        echo json_encode($result);
    }

    /**
     * Parses IVR request and sets up object
     * @return bool request valid
     */
    public function parseIVRInput() {
        $this->emDebug("Incoming IVR POST: ", $_POST);

        //TODO need to figure out what is coming from IVR

        $valid = 1 ? false : true;
        $this->emDebug($valid);

        return $valid;
    }

    public function IVRHandler() {
        //TODO figure out what is coming from twilio
        $result = array();

        // Return result
        header("Content-type: application/json");
        echo json_encode($result);
    }

    /**
     * Finds the currentProject for the user and passcode
     * @return bool
     */
    public function getAccessCodeProject() {
        foreach ($this->enabledProjects as $pid => $project_data) {
            // TODO NEED TO CHECK WHICH MODE IS PROJECT 
            // Look FOR ACCESS CODE
            $project_mode   = $this->getProjectSetting('em-mode', $pid);
            if($project_mode == "access_code_db"){
                $filter     = "[access_code] = '" . $this->acess_code . "' AND [zip] = '". $this->zip_code ."'";
                $q          = \REDCap::getData($pid, 'json', null , null, null, null, false, false, false, $filter);
                $results    = json_decode($q,true);
                foreach ($results as $result) {
                    if ($result['access_code'] == $this->access_code && $result['zip'] == $this->zip_code) {
                        $this->emDebug("Found a match access code match for ", $this->access_code, $this->zip_code);
                        $this->access_code_record   = $result;
                        $this->access_code_project  = $pid;
                        return true;
                    }
                }
            }
        }

        $this->emDebug("No match found for in Access Code DB for : ", $this->access_code, $this->zip_code);
        return false;
    }

    /**
     * Finds the currentProject for the user and passcode
     * @return bool
     */
    public function getKitSubmissionProject() {
        foreach ($this->enabledProjects as $pid => $project_data) {
            // TODO NEED TO CHECK WHICH MODE IS PROJECT 
            // Look FOR kit_submission
            $project_mode   = $this->getProjectSetting('em-mode', $pid);
            if($project_mode == "kit_submission"){
                $filter     = "[access_code] = '" . $this->acess_code . "' AND [zip] = '". $this->zip_code ."'";
                $q          = \REDCap::getData($pid, 'json', null , null, null, null, false, false, false, $filter);
                $results    = json_decode($q,true);
                foreach ($results as $result) {
                    if ($result['access_code'] == $this->access_code && $result['zip'] == $this->zip_code) {
                        $this->emDebug("Found a match access code match for ", $this->access_code, $this->zip_code);
                        $this->kit_submission_record   = $result;
                        $this->kit_submission_project  = $pid;
                        return true;
                    }
                }
            }
        }

        $this->emDebug("No match found for in Access Code DB for : ", $this->access_code, $this->zip_code);
        return false;
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
