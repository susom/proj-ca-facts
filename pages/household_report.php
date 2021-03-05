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
            if($name == "perhh_perpart"){
                array_push($data, implode("," , array("record_id", "access_code", "household_code", "participant_id", "household_member","test_upc", "test_result", "kit_submission_id","sex","age", "city", "census_tract","county")));
                $module->emDebug("nOw what the fuck you bitch?", $raw);
                
                foreach($raw as $i => $record){

                    $record_id      = $record[0];
                    $access_code    = $record[1];
                    $household_code = $record[2];
                    $part_id        = $record[3];
                    $is_hhd         = $record[4];
                    $part_upc       = $record[5];
                    $part_result    = $record[6];
                    $part_ks        = $record[7];
                    $part_sex       = $record[8];
                    $part_age       = $record[9];
                    $city           = $record[10];
                    $census_tract   = $record[11];
                    $county         = $record[12];

                    array_push($data, implode("," , array($record_id,$access_code,$household_code, $part_id,$is_hdd,$part_upc, $part_result, $part_ks, $part_sex, $part_age, $city, $census_tract, $county) ) );
                }                
            }

            $name = $name . "_" . Date("Y-m-d");
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

$records = $module->householdPerParticipantReport();
?>
<div style='margin:20px 40px 0 0;'>
    <h4>Per Household/ Per Participant Report</h4>
    <p>This will generate a table and .CSV that will stack all participants of all households in their own row</p>
  
    <hr>
    <br>
    <div class="row mb-2">
        <h4 class="col-sm-9">Total Records <?=count($records); ?> records</h4>
        <form class="col-sm-3 pr-5 text-right" method="POST">
            <input type="hidden" name="action" value="download"/>
            <input type="hidden" name="data" value='<?= json_encode($records) ?>'>
            <input type="hidden" name="filename" value="perhh_perpart">
            <button class="btn btn-info">Download .csv</button>
        </form>
    </div>
    <table class='no_matches mp' border="1" width="98%">
        <thead>
            <tr>
                <th>Record ID</th>
                <th>Access Code</th>
                <th>HouseHold Code</th>
                <th>Participant ID</th>
                <th>Head of Household?</th>
                <th>UPC</th>
                <th>Result</th>
                <th>KS ID</th>
                <th>Sex</th>
                <th>Age</th>
                <th>City</th>
                <th>Census Tract</th>
                <th>County</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                foreach($records as $i => $record){
                    $record_id      = $record[0];
                    $access_code    = $record[1];
                    $household_code = $record[2];
                    $part_id        = $record[3];
                    $is_hhd         = $record[4];
                    $part_upc       = $record[5];
                    $part_result    = $record[6];
                    $part_ks        = $record[7];
                    $part_sex       = $record[8];
                    $part_age       = $record[9];
                    $city           = $record[10];
                    $census_tract   = $record[11];
                    $county         = $record[12];

                    echo "<tr>";
                    echo "<td>$record_id</td>";
                    echo "<td>$access_code</td>";
                    echo "<td>$household_code</td>";
                    echo "<td>$part_id</td>";
                    echo "<td>$is_hhd</td>";
                    echo "<td>$part_upc</td>";
                    echo "<td>$part_result</td>";
                    echo "<td>$part_ks</td>";
                    echo "<td>$part_sex</td>";
                    echo "<td>$part_age</td>";
                    echo "<td>$city</td>";
                    echo "<td>$census_tract</td>";
                    echo "<td>$county</td>";
                    echo "</tr>";
                }
            ?>
        </tbody>
    </table>
</div>