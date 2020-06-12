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

    private $access_code, $zip_code, $enabledProjects;

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
        
        $this->access_code   = isset($_POST[self::FIELD_ACCESS_CODE]) ? strtoupper(trim($_POST[self::FIELD_ACCESS_CODE])) : NULL ;
        $this->zip_code      = isset($_POST[self::FIELD_ZIP])         ? trim($_POST[self::FIELD_ZIP]) : NULL ;
        
        //TODO any other data?

        $valid               = (is_null($this->access_code) || is_null($this->zip_code)) ? false : true;
        $this->emDebug($valid);

        return $valid;
    }

    public function formHandler() {

        // Make sure user is valid and set project info
        $this->getEnabledProjects();
        if (! $this->getCurrentProject())   $this->returnError("Unable to find an active project for participant: " . $this->participant_id);

        //TODO WHAT ACTIONS GO HERE?  REDIRECT TO RC SURVEY?
        //WHAT TO SHOW BACK FOR NOW? THE ENTIRE POST?
        $result = $_POST;
        
        switch($this->action) {
            case "VERIFY":
                REDCap::logEvent($this->PREFIX, $this->action, NULL, $this->participant_id, NULL, $this->current_project);
                $result = $this->current_record;
                break;
            case "SAVEDATA":
                if (is_null($this->data)) $this->returnError("Missing data for" . $this->participant_id);
                $this->data['id']                       = $this->participant_id;
                $this->data['redcap_repeat_instrument'] = 'session_data';
                $this->data['redcap_repeat_instance']   = $this->getNextInstanceId();

                $this->emDebug("SaveData", $this->data);
                $result = REDCap::saveData($this->current_project, 'json', json_encode(array($this->data)));
                break;
            default:
                $result = array("error"=>"Unknown action: " . $this->action);
        }

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
    public function getCurrentProject() {
        foreach ($this->enabledProjects as $pid => $project_data) {

            $this->emDebug("enabled pid ", $pid);
            $this->emDebug("enabled project_data ", $project_data);

            $q = \REDCap::getData($pid, 'json', $this->participant_id, array('id','pw','alias','deactivate'));
            $results = json_decode($q,true);
            $this->emLog("Query for " . $this->participant_id . " in project " . $pid, $results);
            foreach ($results as $result) {
                if ($result['id'] == $this->participant_id && $result['pw'] == $this->passcode) {
                    $this->emDebug("Found a match", $this->participant_id, $this->passcode);
                    $this->current_record = $result;
                    $this->current_project = $pid;
                    return true;
                }
                $this->emDebug("Got a record, but didn't match", $this->participant_id, $this->passcode, $result);
            }
        }
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
