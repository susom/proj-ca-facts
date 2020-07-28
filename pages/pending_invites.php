<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "getHouseHoldId":
            $record_id  = $_POST["record_id"] ?? null;
            $qrscan     = $_POST["qrscan"] ?? null;
            $testpeople = $_POST["testpeople"] ?? null;

            // example : artemis.gauss.com?c=110f18709d39b9e683916de0dd5f9b283a2835bcef332d4ece5ca2e7af43f9b0f1af5a7e6c2081175fef333dbf506337298677dc5c8a7cd642f16ed8c43dadd890e359491d207f18ff8f2bd9b79c81082a9609d30380983
            $result     = $module->getHouseHoldId($qrscan);
            $hh_id      = $result["household_id"];

            //TODO remove this bypass when done wiht DEMO
            if($hh_id){
                // SAVE TO REDCAP
                $data   = array(
                    "record_id"             => $record_id,
                    "kit_qr_code"           => $qrscan,
                    "kit_household_code"    => $hh_id
                );
                $r      = \REDCap::saveData($pid, 'json', json_encode(array($data)) );

                // Pre Generates Records in Kit Submission Project
                $module->linkKits($record_id, $testpeople, $hh_id);
            } 
        break;

        case "printLabel":
            $record_id  = $_POST["record_id"] ?? null;

            if($record_id){
                $fields     = array("record_id","testpeople", "code", "address_1" ,"address_2","city", "state", "zip");
                $q          = \REDCap::getData('json', array($record_id) , $fields);
                $results    = json_decode($q,true);
            }
            $result = array("address" => $results);
        break;

        default:
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$pdf_printlabel_url = $module->getUrl("pages/printLabel.php");

$em_mode = $module->getProjectSetting("em-mode");
if($em_mode != "kit_order"){
    ?>
<div style='margin:20px 0;'>
    <h4>Pending Invitations Report</h4>
    <p>Please open this report in the Main Project (kit_order).</p>

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
    <h4>Pending Invitations Report</h4>
    <p>Report of all complete invitation questionaires that require shipping</p>
    <p>Report Logic :
        <br> <b>code</b> is <em>not empty</em>
        <br> <b>testpeople</b> is <em>not empty</em>
        <br> <b>kit_household_code</b> is <em>empty</em></p>
    <br>
    <br>

    <?php
        $qrscan_src = $module->getUrl("docs/images/fpo_qr_bar.png");
        $label_src  = $module->getUrl("docs/images/ico_printlabel.png");
        $pending    = $module->getPendingInvites();
        $dumphtml   = array();
        $dumphtml[] = "<tbody class='table-striped' id='pending_invites'>";
        foreach($pending as $invite){
            $addy_top   = $invite["address_1"];
            if( !empty($invite["address_2"]) ){
                $addy_top .= "<br>" . $invite["address_2"];
            }
            $addy_bot   = $invite["city"] . ", " . $invite["state"] . " " . $invite["zip"];
            $dumphtml[] = "<tr>";
            $dumphtml[] = "<td class='record_id'>". $invite["record_id"] ."</td>";
            $dumphtml[] = "<td class='ac'>". $invite["code"] ."</td>";
            $dumphtml[] = "<td class='addy'>". $addy_top . "<br>" . $addy_bot ."</td>";
            $dumphtml[] = "<td class='numkits'>". $invite["testpeople"] ."</td>";
            $dumphtml[] = "<td class='qrscan'><input type='text' name='kit_qr_code' data-numkits='". $invite["testpeople"] ."' data-recordid='".$invite["record_id"]."' id='record_".$invite["record_id"]."'/><label for='record_".$invite["record_id"]."'></label></td>";
            $dumphtml[] = "</tr>";
        }
        $dumphtml[]     = "</tbody>";
    ?>
    <style>
        #pending_invites .numkits {
            text-align:center; 
            font-size:150%;
            color:deeppink;
            font-weight:bold; 
        }

        #pending_invites input[name='kit_qr_code']{
            font-size: 24px;
            border-radius: 3px;
            border: 1px solid #ccc;
            display:inline-block;
            cursor:pointer;
            display:none;
        }
        #pending_invites .qrscan{
            position:relative;
            cursor:pointer;
        }

        #pending_invites .qrscan input + label{
            display:inline-block;
            vertical-align:top;
            width: 150px;
            height: 30px;
            background: url(<?php echo $qrscan_src ?>) no-repeat;
            background-size:contain;
            z-index: 1;
            cursor:pointer;
        }

        #pending_invites .qrscan input:focus + label{
            display:none;
        }

        .qrscan .printlabel{
            text-decoration:none;
            margin-left: 20px;
        }
        .qrscan .printlabel:before {
            content:"";
            position:absolute;
            width:20px; height:20px;
            left:10px;
            background:url(<?php echo $label_src ?>) no-repeat;
            background-size:contain;
            vertical-align:top;
        }

        .qrscan strong{
            display: block;
        }
    </style>
    <table class="table table-bordered">
        <thead>
        <tr class='table-info'>
        <th>Record Id</th>
        <th>Access Code</th>
        <th>Shipping Address</th>
        <th># of Kits</th>
        <th>CLick and scan appropriate KitQR to obtain Household ID</th>
        </tr>
        </thead>
        <?php
            echo implode("\r\n", $dumphtml);
        ?>
    </table>
    <script>
        $(document).ready(function(){
            // UI UX 
            $("input[name='kit_qr_code']").blur(function(){
                $(this).hide();
            })
            $(".qrscan label").click(function(){
                var forid = $(this).attr("for");
                $("#"+forid).show().focus();
            });

            // TAKING SCAN INPUT AND GETTING houshold id
            $("input[name='kit_qr_code']").on("input", function(){
                var record_id   = $(this).data("recordid");
                var qrscan      = $(this).val();
                var testpeople  = $(this).data("numkits");

                var _el = $(this);

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "getHouseHoldId",
                            "record_id" : record_id,
                            "qrscan"    : qrscan,
                            "testpeople": testpeople
                    },
                    dataType: 'json'
                }).done(function (result) {
                    var hh_id = result["household_id"];
                    if(hh_id){
                        var par = _el.parent();
                        par.empty();
                        var hhid_span   = $("<strong>").text("Household ID : " + hh_id);
                        par.append(hhid_span);
                        var printlabel  = $("<a>").attr("href","#").addClass("printlabel").attr("data-recordid",record_id).text("Print Label");
                        par.append(printlabel); 
                    }
                }).fail(function () {
                    console.log("something failed");
                });
            });

            // PRINT LABEL
            $(".qrscan").on("click", ".printlabel", function(e){
                e.preventDefault();
                var record_id = $(this).data("recordid");

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "printLabel",
                            "record_id" : record_id
                    },
                    dataType: 'json'
                }).done(function (result) {

                    var pdf_url = '<?= $pdf_printlabel_url ?>' + "&" + $.param(result["address"][0]);
                    var w = 600;
                    var h = 300;
                    var left = Number((screen.width/2)-(w/2));
                    var tops = Number((screen.height/2)-(h/2));
			        var pu = window.open(pdf_url, '', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=1, copyhistory=no, width='+w+', height='+h+', top='+tops+', left='+left);
                    pu.focus();
                }).fail(function () {
                    console.log("something failed");
                });
            });
        });
    </script>
</div>
<? } ?>