<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

if(!empty($_POST["action"])){
    $action = $_POST["action"];
    $result = array();
    switch($action){
        case "kitReturned":
            $usps_track     = $_POST["usps_track"] ?? null;
            $usps_track     = str_replace("420943055102","",$usps_track);

            if($usps_track){
                // find record with usps_track = usps_track
                $record_id = $module->findMainRecordByTracking($usps_track);

                if($record_id){
                    // update kit_returned_date
                    $module->datestampKitReturn($record_id);
                    $result = array("success" => "carry on");
                }else{
                    $result = array("error" => "action not taken");
                }
            }
        break;

        default:
            $result = array("error" => "why are you here?");
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$em_mode = $module->getProjectSetting("em-mode");
?>
<div style='margin:20px 40px 0 0;'>
    <h4>Scan USPS Return Label barcodes</h4>
    <p>Will datestamp the [kit_returned_date] field in the main project</p>
    
    <br>
    <br>

    <?php
        $loading            = $module->getUrl("docs/images/icon_loading.gif");
        $loaded             = $module->getUrl("docs/images/icon_loaded.png");
        $qrscan_src         = $module->getUrl("docs/images/fpo_qr_bar.png");
        $doublearrow_src    = $module->getUrl("docs/images/icon_doublearrow.png");
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
            width:230px;
            color:#999;
        }
        #pending_invites label{
            display:inline-block;
            vertical-align:top;
            width: 58px;
            height: 50px;
            background: url(<?php echo $qrscan_src ?>) no-repeat;
            background-size:cover;
            z-index: 1;
            cursor:pointer;
        }

        #pending_invites .upcscan label{
            width: 142px;
            background-position-X:-82px;
        }
        #pending_invites .upcscan{
            position:relative;
        }
        #pending_invites .upcscan.loading:before{
            position:absolute;
            content:"";
            height:50px; width:140px;
            top:25px;
            right:-150px;
            background:url(<?=$loading?>) no-repeat;
            background-size:contain;
        }
        #pending_invites .upcscan input.error{
            color:red;
        } 
        #pending_invites .upcscan input.done{
            color:green;
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
  
        <div class='upcscan'>
            <h6>Scan UPC barcode on USPS return label</h6>
            <label for='test_kit_upc'></label><input type='text' name='kit_upc_code' id='test_kit_upc' placeholder="Scan UPC barcode"/>
        </div>
    </section>

    <br><br>
    <hr>
    <br><br>

    <a href="<?=$module->getUrl("pages/kit_return_scan.php")?>" id="reset_link_upc" type="button" class="btn btn-lg btn-primary">Scan another package</a>

    <script>
        $(document).ready(function(){
            // UI UX 
            $("input[name='kit_upc_code']").on("input", function(){
                var usps_track      = $(this).val();
                var _this           = $(this);
                _this.parent(".upcscan").addClass("loading");

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"        : "kitReturned",
                            "usps_track"    : usps_track
                    },
                    dataType: 'json'
                }).done(function (result) {
                    console.log("done",result);
                    
                    if(result.hasOwnProperty("error")){
                        _this.addClass("error");
                    }else{
                        _this.addClass("done");
                    }

                    setTimeout(function(){
                        location.reload();
                    },250);
                }).fail(function () {
                    console.log("something failed");
                });
            });

            //be here when the page loads
            $("input[name='kit_upc_code']").focus();
        });
    </script>
</div>

