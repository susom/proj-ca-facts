<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "getSubmissionId":
            $qrscan     = $_POST["qrscan"] ?? null;
            $result     = $module->getKitSubmissionId($qrscan);

            if(isset($result["record_id"])){
                $result     = array("error" => false, "record_id" => $result["record_id"], "participant_id" => $result["participant_id"], "main_id" => $result["household_record_id"]);
            }else{
                $result     = array("error" => true);
            }
        break;

        case "linkUPC":
            $upcscan        = $_POST["upcscan"] ?? null;
            $qrscan         = $_POST["qrscan"] ?? null;
            $record_id      = $_POST["record_id"] ?? null;

            // SAVE TO REDCAP
            $data   = array(
                "record_id"         => $record_id,
                "kit_upc_code"      => $upcscan,
                "kit_qr_input"      => $qrscan
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
    
    <h4>Main Head of HouseHold KIT QR?</h4>
    <textarea>https://artemis.gauss.com/?c=1e051d2667cd0dc13b73eac7df9fa9010ad43d568abd2708c6fba5d48bc71b569efbbf866f287643b9bf4e3798cd6b22cab5793a46e12bf64c5689cd18282459b425fd2f5dd7f7f23b5c87989a95e3cc99030f9b4e381bf#/partner-verification?c=1e051d2667cd0dc13b73eac7df9fa9010ad43d568abd2708c6fba5d48bc71b569efbbf866f287643b9bf4e3798cd6b22cab5793a46e12bf64c5689cd18282459b425fd2f5dd7f7f23b5c87989a95e3cc99030f9b4e381bf</textarea>
    
    <h4>Dependent 1</h4>
    <textarea>https://artemis.gauss.com/?c=19fb4b745315c68e80cbf3ea1d5686c884efca7a493787df19d72565d80f55ebf1fedb673344bf2b814486718ec00fdbe8090ba6371a9f2dca38c2dc2b98630b023faaf9f1c0314c9dc3c368fc6ea68cb95b0aafc89855b#/stanford/splash</textarea>

    <h4>Dependent 2</h4>
    <textarea>https://artemis.gauss.com/?c=15e1ae052c7b5cd4cfcbf6d9322c995a1a2a7e8dce32d88d0130f2a98a31f109a89b53c4fb72029715df6c8cd1020cb87dc3ab642776af9648a50172731981c493bae3a52a020c8e3a2e4da589b77afc4d314588c68ade9#/partner-verification?c=15e1ae052c7b5cd4cfcbf6d9322c995a1a2a7e8dce32d88d0130f2a98a31f109a89b53c4fb72029715df6c8cd1020cb87dc3ab642776af9648a50172731981c493bae3a52a020c8e3a2e4da589b77afc4d314588c68ade9</textarea>

    <h4>Pretend UPC</h4>
    <textarea>1234567890</textarea>


    <?php
    $shit[] = "hisp_type1___5";
    $shit[] = "race1___9";
    $shit[] = "hisp_type2___5";
    $shit[] = "race2___9";
    $shit[] = "hisp_type3___5";
    $shit[] = "race3___9";
    $shit[] = "hisp_type4___5";
    $shit[] = "race4___9";
    $shit[] = "hisp_type5___5";
    $shit[] = "race5___9";
    $shit[] = "hisp_type6___5";
    $shit[] = "race6___9";
    $shit[] = "hisp_type7___5";
    $shit[] = "race7___9";
    $shit[] = "hisp_type8___5";
    $shit[] = "race8___9";
    $shit[] = "hisp_type9___5";
    $shit[] = "race9___9";
    $shit[] = "hisp_type10___5";
    $shit[] = "race10___9";
    $shit[] = "computer___1";
    $shit[] = "computer___2";
    $shit[] = "computer___3";
    $shit[] = "computer___4";
    $shit[] = "computer___5";
    $shit[] = "internet___1";
    $shit[] = "internet___2";
    $shit[] = "internet___3";
    $shit[] = "internet___4";
    $shit[] = "internet___5";
    $shit[] = "hisp_type1_s___5";
    $shit[] = "race1_s___9";
    $shit[] = "hisp_type2_s___5";
    $shit[] = "race2_s___9";
    $shit[] = "hisp_type3_s___5";
    $shit[] = "race3_s___9";
    $shit[] = "hisp_type4_s___5";
    $shit[] = "race4_s___9";
    $shit[] = "hisp_type5_s___5";
    $shit[] = "race5_s___9";
    $shit[] = "hisp_type6_s___5";
    $shit[] = "race6_s___9";
    $shit[] = "hisp_type7_s___5";
    $shit[] = "race7_s___9";
    $shit[] = "hisp_type8_s___5";
    $shit[] = "race8_s___9";
    $shit[] = "hisp_type9_s___5";
    $shit[] = "race9_s___9";
    $shit[] = "hisp_type10_s___5";
    $shit[] = "race10_s___9";
    $shit[] = "computer_s___1";
    $shit[] = "computer_s___2";
    $shit[] = "computer_s___3";
    $shit[] = "computer_s___4";
    $shit[] = "computer_s___5";
    $shit[] = "internet_s___1";
    $shit[] = "internet_s___2";
    $shit[] = "internet_s___3";
    $shit[] = "internet_s___4";
    $shit[] = "internet_s___5";
    $shit[] = "hisp_type1_v___5";
    $shit[] = "race1_v___9";
    $shit[] = "hisp_type2_v___5";
    $shit[] = "race2_v___9";
    $shit[] = "hisp_type3_v___5";
    $shit[] = "race3_v___9";
    $shit[] = "hisp_type4_v___5";
    $shit[] = "race4_v___9";
    $shit[] = "hisp_type5_v___5";
    $shit[] = "race5_v___9";
    $shit[] = "hisp_type6_v___5";
    $shit[] = "race6_v___9";
    $shit[] = "hisp_type7_v___5";
    $shit[] = "race7_v___9";
    $shit[] = "hisp_type8_v___5";
    $shit[] = "race8_v___9";
    $shit[] = "hisp_type9_v___5";
    $shit[] = "race9_v___9";
    $shit[] = "hisp_type10_v___5";
    $shit[] = "race10_v___9";
    $shit[] = "computer_v___1";
    $shit[] = "computer_v___2";
    $shit[] = "computer_v___3";
    $shit[] = "computer_v___4";
    $shit[] = "computer_v___5";
    $shit[] = "internet_v___1";
    $shit[] = "internet_v___2";
    $shit[] = "internet_v___3";
    $shit[] = "internet_v___4";
    $shit[] = "internet_v___5";
    $shit[] = "hisp_type1_m___5";
    $shit[] = "race1_m___9";
    $shit[] = "hisp_type2_m___5";
    $shit[] = "race2_m___9";
    $shit[] = "hisp_type3_m___5";
    $shit[] = "race3_m___9";
    $shit[] = "hisp_type4_m___5";
    $shit[] = "race4_m___9";
    $shit[] = "hisp_type5_m___5";
    $shit[] = "race5_m___9";
    $shit[] = "hisp_type6_m___5";
    $shit[] = "race6_m___9";
    $shit[] = "hisp_type7_m___5";
    $shit[] = "race7_m___9";
    $shit[] = "hisp_type8_m___5";
    $shit[] = "race8_m___9";
    $shit[] = "hisp_type9_m___5";
    $shit[] = "race9_m___9";
    $shit[] = "hisp_type10_m___5";
    $shit[] = "race10_m___9";
    $shit[] = "computer_m___1";
    $shit[] = "computer_m___2";
    $shit[] = "computer_m___3";
    $shit[] = "computer_m___4";
    $shit[] = "computer_m___5";
    $shit[] = "internet_m___1";
    $shit[] = "internet_m___2";
    $shit[] = "internet_m___3";
    $shit[] = "internet_m___4";
    $shit[] = "internet_m___5";
    $shit[] = "covidwhen";
    $shit[] = "covidmonth";
    $shit[] = "sxcovid___14";
    $shit[] = "sxcovid___15";
    $shit[] = "knowcovid";
    $shit[] = "knowcovidwhen";
    $shit[] = "knowcovidmonth";
    $shit[] = "abtest";
    $shit[] = "abresult";
    $shit[] = "abmood";
    $shit[] = "abactivity";
    $shit[] = "mask";
    $shit[] = "hand";
    $shit[] = "activity";
    $shit[] = "nowsx___1";
    $shit[] = "nowsx___2";
    $shit[] = "nowsx___3";
    $shit[] = "nowsx___4";
    $shit[] = "nowsx___5";
    $shit[] = "nowsx___6";
    $shit[] = "nowsx___7";
    $shit[] = "nowsx___8";
    $shit[] = "nowsx___9";
    $shit[] = "nowsx___10";
    $shit[] = "nowsx___11";
    $shit[] = "nowsx___12";
    $shit[] = "nowsx___13";
    $shit[] = "nowsx___14";
    $shit[] = "nowsx___15";
    $shit[] = "txt";
    $shit[] = "covidwhen_s";
    $shit[] = "covidmonth_s";
    $shit[] = "sxcovid_s___14";
    $shit[] = "sxcovid_s___15";
    $shit[] = "knowcovid_s";
    $shit[] = "knowcovidwhen_s";
    $shit[] = "knowcovidmonth_s";
    $shit[] = "abtest_s";
    $shit[] = "abresult_s";
    $shit[] = "abmood_s";
    $shit[] = "abactivity_s";
    $shit[] = "mask_s";
    $shit[] = "hand_s";
    $shit[] = "activity_s";
    $shit[] = "nowsx_s___1";
    $shit[] = "nowsx_s___2";
    $shit[] = "nowsx_s___3";
    $shit[] = "nowsx_s___4";
    $shit[] = "nowsx_s___5";
    $shit[] = "nowsx_s___6";
    $shit[] = "nowsx_s___7";
    $shit[] = "nowsx_s___8";
    $shit[] = "nowsx_s___9";
    $shit[] = "nowsx_s___10";
    $shit[] = "nowsx_s___11";
    $shit[] = "nowsx_s___12";
    $shit[] = "nowsx_s___13";
    $shit[] = "nowsx_s___14";
    $shit[] = "nowsx_s___15";
    $shit[] = "txt_s";
    $shit[] = "covidwhen_v";
    $shit[] = "covidmonth_v";
    $shit[] = "sxcovid_v___14";
    $shit[] = "sxcovid_v___15";
    $shit[] = "knowcovid_v";
    $shit[] = "knowcovidwhen_v";
    $shit[] = "knowcovidmonth_v";
    $shit[] = "abtest_v";
    $shit[] = "abresult_v";
    $shit[] = "abmood_v";
    $shit[] = "abactivity_v";
    $shit[] = "mask_v";
    $shit[] = "hand_v";
    $shit[] = "activity_v";
    $shit[] = "nowsx_v___1";
    $shit[] = "nowsx_v___2";
    $shit[] = "nowsx_v___3";
    $shit[] = "nowsx_v___4";
    $shit[] = "nowsx_v___5";
    $shit[] = "nowsx_v___6";
    $shit[] = "nowsx_v___7";
    $shit[] = "nowsx_v___8";
    $shit[] = "nowsx_v___9";
    $shit[] = "nowsx_v___10";
    $shit[] = "nowsx_v___11";
    $shit[] = "nowsx_v___12";
    $shit[] = "nowsx_v___13";
    $shit[] = "nowsx_v___14";
    $shit[] = "nowsx_v___15";
    $shit[] = "txt_v";
    $shit[] = "covidwhen_m";
    $shit[] = "covidmonth_m";
    $shit[] = "sxcovid_m___14";
    $shit[] = "sxcovid_m___15";
    $shit[] = "knowcovid_m";
    $shit[] = "knowcovidwhen_m";
    $shit[] = "knowcovidmonth_m";
    $shit[] = "abtest_m";
    $shit[] = "abresult_m";
    $shit[] = "abmood_m";
    $shit[] = "abactivity_m";
    $shit[] = "mask_m";
    $shit[] = "hand_m";
    $shit[] = "activity_m";
    $shit[] = "nowsx_m___1";
    $shit[] = "nowsx_m___2";
    $shit[] = "nowsx_m___3";
    $shit[] = "nowsx_m___4";
    $shit[] = "nowsx_m___5";
    $shit[] = "nowsx_m___6";
    $shit[] = "nowsx_m___7";
    $shit[] = "nowsx_m___8";
    $shit[] = "nowsx_m___9";
    $shit[] = "nowsx_m___10";
    $shit[] = "nowsx_m___11";
    $shit[] = "nowsx_m___12";
    $shit[] = "nowsx_m___13";
    $shit[] = "nowsx_m___14";
    $shit[] = "nowsx_m___15";
    $shit[] = "txt_m";

    sort($shit);
    echo "<pre>";
    print_r($shit);
    ?>
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
                var qrscan          = $("#test_kit_qr").val();

                var _el = $(this);
                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "linkUPC",
                            "upcscan"    : upcscan,
                            "qrscan"    : qrscan,
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

