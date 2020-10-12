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
                $module->parseCSVtoDB($file);
            }
        break;

        case "updateUPC":
            $field_type  = $_POST['field_type'];
            if($field_type == "file"){
                $file       = current($_FILES);
                $module->parseCSVtoDB_generic($file);
            }
        break;

        default:
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$em_mode = $module->getProjectSetting("em-mode");
if($em_mode = "kit_submission"){
    $CSV_EXAMPLE = $module->getUrl("docs/CAFACTSKITSUBMISSION_ImportTemplate_2020-08-29.csv");
?>
<div style='margin:20px 40px 0 0;'>
    <h4>TEST RESULTS - BULK UPLOAD</h4>

    <p>Upload .CSV file using this <a href="<?=$CSV_EXAMPLE?>">[TEMPLATE.csv]</a></p>
    
    <br>
    <br>

    <?php
        $loading            = $module->getUrl("docs/images/icon_loading.gif");
        $loaded             = $module->getUrl("docs/images/icon_loaded.png");
        $qrscan_src         = $module->getUrl("docs/images/fpo_qr_bar.png");
        $doublearrow_src    = $module->getUrl("docs/images/icon_doublearrow.png");
        $link_kit_upc       = $module->getUrl("pages/link_kit_upc.php");
    ?>

    <style>
        #pending_invites div{
            display:inline-block;
        }
        #pending_invites input{
            font-size: 20px;
            padding:10px;
            border-radius: 3px;
            border: 1px solid #ccc;
            display:inline-block;
            cursor:pointer;
            width:800px;
            color:#999;
        }
        #pending_invites .qrscan{
            position:relative;
            cursor:pointer;
        }

        #pending_invites .upcscan label{
            width: 142px;
            background-position-X:-82px;
        }
        #pending_invites .upcscan{
            margin-left:200px;
            position:relative;
        }
        #pending_invites .upcscan:before{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:-130px;
            background:url(<?=$doublearrow_src?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan.loading:before{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:-130px;
            background:url(<?=$loading?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan.link_loading:after{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:102%;
            background:url(<?=$loading?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan.link_loaded:after{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            left:102%;
            background:url(<?=$loaded?>) no-repeat;
            background-size:contain;
        }


        #pending_invites h6{
            color:#999;
        }

        #pending_invites h6.next_step{
            color:#000;
            font-weight:bold;
        }
        
        #pending_invites h6.step_used{
            color:#999;
            font-weight:bold;
        }

        a.btn:visited {
            text-decoration:none;
            color:#fff;
        }
    </style>
    
    <section id="pending_invites">

        <div class='qrscan'>
            <h6 class="next_step">Upload CSV Here</h6>
            <form method="post" enctype="multipart/form-data">
            <label for='upload_csv'></label><input type='file' name='upload_csv' id='upload_csv' placeholder="Test Results CSV"/>
            </form>
        </div>
    </section>

    <br><br>
    <a href="<?=$link_kit_upc?>" id="upload_btn" type="button" class="btn btn-lg btn-primary">Upload and Process File</a>


    

    <hr>
    *one time use
    <div class='qrscan'>
        <h6 class="next_step">Upload Temp CSV Here</h6>
        <form method="post" enctype="multipart/form-data">
        <label for='upload_csv'></label><input type='file' name='upload_csv' id='upload_csv_2' placeholder="Test Results CSV"/>
        </form>
    </div>
    <br><br>
    <a href="<?=$link_kit_upc?>" id="upload_btn_2" type="button" class="btn btn-lg btn-primary">Upload and Process File</a>


    
    <script>
        $(document).ready(function(){
            $("#upload_btn").click(function(){
                var file =  $("#upload_csv").prop('files')[0];

                if(file){
                    ajaxlikeFormUpload($("#upload_csv"));
                }

                return false;
            });

            $("#upload_btn_2").click(function(){
                var file =  $("#upload_csv_2").prop('files')[0];

                if(file){
                    ajaxlikeFormUpload($("#upload_csv_2"));
                }

                return false;
            });
            
            function ajaxlikeFormUpload(el){
                // create temp hidden iframe for submitting from/to;
                if($('iframe[name=iframeTarget]').length < 1){
                    var iframe = document.createElement('iframe');
                    $(iframe).css('display','none');
                    $(iframe).attr('src','#');
                    $(iframe).attr('name','iframeTarget');
                    $('body').append(iframe);
                }

                var input_field     = el.attr("name");
                var field_type      = el.attr("type");
                var file            = el.prop('files')[0];

                el.parent().attr("target","iframeTarget");
                el.parent().append($("<input type='hidden'>").attr("name","action").val("updateUPC"));
                el.parent().append($("<input type='hidden'>").attr("name","field_type").val(field_type));
                el.parent().append($("<input type='hidden'>").attr("name","input_field").val(input_field));
                el.parent().trigger("submit");
            }
        });
    </script>
</div>
<?php } ?>

<?php

exit; 
/*
// parse CSV
$files = glob($redcap_temp . "*.csv");
foreach($files as $filepath) {
    if ($handle = fopen($filepath, "r")) {
        $module->parseCSVtoDB( $filepath , $exclude_columns );

        echo "<pre>";
        print_r($filepath);
        echo "</pre>";
    }
}

public function parseCSVtoDB($filename, $exclude_columns){
    //Path of the file stored under pathinfo 
    $filepath = pathinfo($filename); 
    $basename =  $filepath['basename']; 

    //HOW MANY POSSIBLE INSITUTIONS?
    $this->institution = strpos(strtoupper($basename), "UCSF") !== false ? "UCSF" : "STANFORD";

    $sql 	= "SELECT * FROM  track_covid_result_match WHERE csv_file = '$basename'" ;
    $q 		= $this->query($sql, array());

    if($q->num_rows){
        //CSV's DATA alreay in DB so USE THAT
        while ($data = db_fetch_assoc($q)) {
            //push all row data into array in mem
            $new_row = new \Stanford\TrackCovidConsolidator\CSVRecord($data,$this->institution);
            $this->CSVRecords[]=  $new_row;
        }
    }else{
        //LOAD CSV TO DB
        $header_row  = true;
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $sql_value_array 	= array();
            $all_values	 		= array();
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if($header_row){
                    // prep headers as sql column headers
                    foreach($exclude_columns as $exclude_key){
                        unset($data[$exclude_key]);
                    }

                    // adding extra column to determine which file the data came from
                    array_push($data, "csv_file");

                    $headers 	= implode(",",$data);
                    print_r($headers);
                    $header_row = false;
                }else{
                    // Data
                    foreach($exclude_columns as $exclude_key){
                        unset($data[$exclude_key]);
                    }

                    // adding extra column to determine which csv file the data came from
                    array_push($data, $basename);

                    // prep data for SQL INSERT
                    array_push($sql_value_array, '("'. implode('","', $data) . '")');
                    
                    //push all row data into array in mem
                    $new_row = new \Stanford\TrackCovidConsolidator\CSVRecord($data,$this->institution);
                    $this->CSVRecords[]=  $new_row;
                }
            }

            // STUFF THIS CSV INTO TEMPORARY RC DB TABLE 'track_covid_result_match'
            try {
                $sql = "INSERT INTO track_covid_result_match (".$headers.") VALUES " . implode(',',$sql_value_array) . " ON DUPLICATE KEY UPDATE TRACKCOVID_ID=TRACKCOVID_ID" ;
                $q = $this->query($sql, array());

                $this->discardCSV($filename);

                return true;
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $this->emDebug($msg);
                throw $e;
            }
            fclose($handle);
        }
    }
    
    return;
}
*/
?>