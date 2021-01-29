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
    <h4>Kit Order - Has UPC / No Matching Kit Submission Record(s)</h4>
    <style>
        .no_matches th:first-child,
        .no_matches td:first-child{
            text-align:center;
        }
        .no_matches td ul{ 
            margin:0;
            padding:0;
            list-style:none;
        }
        .no_matches td,
        .no_matches th { padding:10px; }
        .no_matches td td { padding:3px; }
    </style>
    <table class='no_matches' border="1" width="98%">
    <thead>
    <tr>
        <th>Record Id</th>
        <th>Participants</th>
    </tr>
    </thead>
    <tbody>
        <?php 
            foreach($main_records["no_match_mp"] as $rec_id => $main_record){
                echo "<tr>";
                echo "<td>$rec_id</td><td><table width='40%'>";
                foreach($main_record as $part){
                    $part_id    = $part["participant_id"];
                    $upc        = $part["test_upc"];
                    $who        = $part["participant"];
                    echo "<tr><td><b>$upc</b></td><td>$who</td><td>$part_id</td></tr>";
                }
                echo "</table></td>";
                echo "</tr>";
            }
        ?>
    </tbody>
    </table>
    
    <br>
    <h4>Kit Submission - No Matching Main Record?</h4>
    <table class='no_matches' border="1" width="98%">
    <thead>
    <tr>
        <th>Record Id</th>
        <th>Participant Id</th>
        <th>Household Id</th>
        <th>Head of household?</th>
    </tr>
    </thead>
    <tbody>
        <?php 
            foreach($main_records["no_match_ks"] as $rec_id => $main_record){
                $part_id    = $main_record["participant_id"];
                $hhd        = ($main_record["head_of_household"]) ? "Yes" : "No"; 
                $hhd_id     = $main_record["household_id"];
                echo "<tr>";
                echo "<td>$rec_id</td>";
                echo "<td>$hhd_id</td>";
                echo "<td>$part_id</td>";
                echo "<td>$hhd</td>";

                echo "</tr>";
            }
        ?>
    </tbody>
    </table>   
</div>
