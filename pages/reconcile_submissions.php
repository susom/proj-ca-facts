<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "saveField":
            $field_type  = $_POST['field_type'];
            if($field_type == "file"){
                $file       = current($_FILES);
                $result     = $module->parseResultsSentCSV($file);
            }

            echo "<p id='upload_results'>".json_encode($result)."</p>";
            exit;
        break;

        default:
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$main_records = $module->linkKits();
?>
<div style='margin:20px 40px 0 0;'>
    <h4>Reconcile Main Household Records with Kit Submissions Database</h4>
    <p>This will match any kit submission surveys to their main record in this project and link them via record_id</p>
  
    <br>
    <h4>Kit Submission - Linking Main Record Id</h4>
    <pre><?php print_r($main_records["save_data_submission"]) ?></pre>

    <br>
    <h4>Kit Order - Matching and Linking Kit Submission Records(s)</h4>
    <pre><?php print_r($main_records["save_data_submission"]) ?></pre>

    <hr>
    <br>
    <h4>Kit Order - No Matching Kit Submission Record(s)</h4>
    <pre><?php print_r($main_records["no_match_mp"]) ?></pre>

    <br>
    <h4>Kit Submission - No Matching Main Record?</h4>
    <pre><?php print_r($main_records["no_match_ks"]) ?></pre>    
</div>
