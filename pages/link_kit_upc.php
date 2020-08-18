<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "getSubmissionId":
            $qrscan     = $_POST["qrscan"] ?? null;
            $result     = $module->getKitSubmissionId($qrscan);
            if($result){
                $result     = array("error" => false, "record_id" => $result);
            }else{
                $result     = array("error" => true);
            }
        break;

        case "linkUPC":
            $upcscan        = $_POST["upcscan"] ?? null;
            $record_id      = $_POST["record_id"] ?? null;

            // SAVE TO REDCAP
            $data   = array(
                "record_id"         => $record_id,
                "kit_upc_code"      => $upcscan
            );

            $result = \REDCap::saveData($pid, 'json', json_encode(array($data)) );
        break;

        default:
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$em_mode = $module->getProjectSetting("em-mode");
if($em_mode != "kit_submission"){
    ?>
<div style='margin:20px 0;'>
    <h4>Test Kit / Testtube UPC Linkage</h4>
    <p>Please open this report in the Kit Submission Project.</p>

    <br>
    <br>

    <h4>Enabled Projects (3 Required)</h4>
    <div>
        <?php echo $module->displayEnabledProjects(array("access_code_db" => $XML_AC_PROJECT_TEMPLATE, "kit_order" => $XML_KO_PROJECT_TEMPLATE, "kit_submission" => $XML_KS_PROJECT_TEMPLATE)  ) ?>
    </div>
</div>
    <?php
}else{
?>
<div style='margin:20px 40px 0 0;'>
    <h4>Test Kit / Testtube UPC Linkage</h4>
    <p>To link the returned Test Kit to a Test Tube UPC:</p>
    
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
            width:230px;
            color:#999;
        }
        #pending_invites .qrscan{
            position:relative;
            cursor:pointer;
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
            <h6 class="next_step">1. Click input and scan QR Code</h6>
            <label for='test_kit_qr'></label><input type='text' name='kit_qr_code' id='test_kit_qr' placeholder="Scan Test Kit QR"/>
        </div>

        <div class='upcscan'>
            <h6>2. scan test tube UPC</h6>
            <label for='test_kit_upc'></label><input type='text' name='kit_upc_code' id='test_kit_upc' placeholder="Scan Test Tube UPC"/>
        </div>



    </section>

    <br><br>
    <hr>
    <br><br>

    <a href="<?=$link_kit_upc?>" id="reset_link_upc" type="button" class="btn btn-lg btn-primary">Scan/Link a new Test Kit</a>

    <br><br><br><br><br><br>
    <br><br><br><br><br><br>

    <textarea>artemis.gauss.com?c=110f18709d39b9e683916de0dd5f9b283a2835bcef332d4ece5ca2e7af43f9b0f1af5a7e6c2081175fef333dbf506337298677dc5c8a7cd642f16ed8c43dadd890e359491d207f18ff8f2bd9b79c81082a9609d30380983</textarea>
    <textarea>1234567890</textarea>
    <script>
        $(document).ready(function(){
            // UI UX 

            // TAKING SCAN INPUT AND GETTING houshold id
            $("input[name='kit_qr_code']").on("input", function(){
                var qrscan          = $(this).val();
                var _el = $(this);

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "getSubmissionId",
                            "qrscan"    : qrscan
                    },
                    dataType: 'json'
                }).done(function (result) {
                    // need not found condition?
                    $(".upcscan").addClass("loading");

                    if(result["error"]){
                        _el.css("color","red");
                        _el.val("");
                        _el.focus();
                        $(".upcscan").removeClass("loading");
                        return;
                    }

                    setTimeout(function(){
                        _el.attr("disabled","disabled");
                        _el.css("color","green");
                        $("#test_kit_upc").focus();
                        $(".upcscan").removeClass("loading");

                        $(".qrscan h6").addClass("step_used");
                        
                        var kit_record_id   = result["record_id"];
                        $("input[name='kit_upc_code']").attr("data-kitrecordid",kit_record_id);

                        $(".upcscan h6").addClass("next_step");
                    },1000);
                }).fail(function () {
                    console.log("something failed");
                    _el.css("color","red");
                    _el.val("");
                    _el.attr("placeholder","No Match, Scan Again");
                    _el.focus();
                });
            });

            $("input[name='kit_upc_code']").on("input", function(){
                var upcscan         = $(this).val();
                var kit_record_id   = $(this).attr("data-kitrecordid");

                var _el = $(this);
                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "linkUPC",
                            "upcscan"    : upcscan,
                            "record_id"     : kit_record_id
                    },
                    dataType: 'json'
                }).done(function (result) {
                    $(".upcscan").addClass("link_loading");

                    // MAKE THE UI TO SHOW SUCCESS
                    setTimeout(function(){
                        _el.css("color","green");
                        $(".upcscan").removeClass("link_loading");
                        $(".upcscan").addClass("link_loaded");
                        $(".upcscan h6").addClass("step_used");

                        setTimeout(function(){
                            location.reload();
                        },250);
                    },1000);
                    

                }).fail(function () {
                    console.log("something failed");
                    _el.css("color","red");
                    _el.val("");
                    _el.attr("placeholder","Error, Scan Again");
                    _el.focus();
                });
            });

            //be here when the page loads
            $("input[name='kit_qr_code']").focus();
        });
    </script>
</div>
<?php } ?>
