<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

if(!empty($_REQUEST["action"])){
    $action = $_REQUEST["action"];

    switch($action){
        case "download":
            $raw    = json_decode($_REQUEST["data"],1);
            $name   = $_REQUEST["filename"];

            $data = array();
            if($name == "main_has_upc_no_submission"){
                array_push($data, implode("," , array("record_id", "test_upc", "participant_id", "household_member")));
                foreach($raw as $rec_id => $hh){
                    foreach($hh as $part){
                        $participant    = $part["participant"];
                        $test_upc       = $part["test_upc"];
                        $participant_id = $part["participant_id"];

                        array_push($data, implode("," , array($rec_id,$test_upc,$participant_id, $participant)) );
                    }
                }                
            }else{
                //submission_no_matching_main
                // $module->emDebug(current($raw));
                array_push($data, implode("," , array("record_id", "participant_id", "household_id", "head_of_household?")));
                
                foreach($raw as $rec_id => $main_record){
                    $part_id    = $main_record["participant_id"];
                    $hhd        = $main_record["head_of_household"] ? "Yes" : "No"; 
                    $hhd_id     = $main_record["household_id"] ?? null;
                    array_push($data, implode("," , array($rec_id, $part_id, $hhd_id, $hhd)) );
                }
                // $module->emDebug($data);
            }

            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$name.'.csv"');
            
            $fp = fopen('php://output', 'wb');
            foreach ( $data as $line ) {
                $val = explode(",", $line);
                fputcsv($fp, $val);
            }
            fclose($fp);

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

    .no_matches.mp tbody, .no_matches.ks tbody{
        display:none;
    }
</style>
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
    <div class="row mb-2">
        <h4 class="col-sm-9">Kit Order - Has UPC / No Matching Kit Submission Record(s) <?=count($main_records["no_match_mp"])?> records <a data-tbo="no_matches.mp" class="showhide_tbody" href="#"><em>click to view</em></a></h4>
        <form class="col-sm-3 pr-5 text-right" method="POST">
            <input type="hidden" name="action" value="download"/>
            <input type="hidden" name="data" value='<?= json_encode($main_records["no_match_mp"]) ?>'>
            <input type="hidden" name="filename" value="main_has_upc_no_submission">
            <button class="btn btn-info">Download .csv</button>
        </form>
    </div>
    <table class='no_matches mp' border="1" width="98%">
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
                    echo "<td>$rec_id</td><td>";
                    echo "<table width='50%' '>";
                    foreach($main_record as $part){
                        $part_id    = $part["participant_id"];
                        $upc        = $part["test_upc"];
                        $who        = $part["participant"];
                        echo "<tr>
                        <td style='width:33%'><b>$who</b></td>
                        <td style='width:33%'>$part_id</td>
                        <td style='width:33%'>$upc</td>
                        </tr>";
                    }
                    echo "</table>";
                    echo "</td>";
                    echo "</tr>";
                }
            ?>
        </tbody>
    </table>
    
    <br>
    <div class="row mt-5 mb-2">
        <h4 class="col-sm-9">Kit Submission - No Matching Main Record? <?=count($main_records["no_match_ks"])?> records <a data-tbo="no_matches.ks" class="showhide_tbody" href="#"><em>click to view</em></a></h4>
        <form class="col-sm-3 pr-5 text-right"  method="POST">
            <input type="hidden" name="action" value="download"/>
            <input type="hidden" name="data" value='<?= json_encode($main_records["no_match_ks"]) ?>'>
            <input type="hidden" name="filename" value="submission_no_matching_main">
            <button class="btn btn-info">Download .csv</button>
        </form>
    </div>
    <table class='no_matches ks' border="1" width="98%">
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
                    echo "<td>$part_id</td>";
                    echo "<td>$hhd_id</td>";
                    echo "<td>$hhd</td>";

                    echo "</tr>";
                }
            ?>
        </tbody>
    </table>   
</div>
<script>
$(".showhide_tbody").click(function(){
    var tbo = $(this).data("tbo");
    console.log("what da fo",tbo);

    $("."+tbo).find("tbody").toggle();
    return false;
})
</script>