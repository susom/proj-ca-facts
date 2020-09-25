<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

$xps_client_id      = $module->getProjectSetting('xpsship-client-id');;
$xps_integration_id = $module->getProjectSetting('xpsship-integration-id');;

if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "getHouseHoldId":
            $record_id  = $_POST["record_id"] ?? null;
            $qrscan     = $_POST["qrscan"] ?? null;
            $testpeople = $_POST["testpeople"] ?? null;
            $addy1      = $_POST["addy1"] ?? null;
            $addy2      = $_POST["addy2"] ?? null;
            $city       = $_POST["city"] ?? null;
            $state      = $_POST["state"] ?? null;
            $zip        = $_POST["zip"] ?? null;

            $result     = $module->getHouseHoldId($qrscan);
            $hh_id      = $result["household_id"];
            $part_id    = $result["survey_id"]; // THIS SHOULD BE THE HEAD OF HOUSEHOLD i hope  //

            if($hh_id){
                // TODO THIS IS WHERE I FAKE IT UNTIL WE GO LIVE?
                // be careful here, XPS wont let reuse even of canceled orders
                // $fake_hh_id = "2234567895";
                // $hh_id      = $fake_hh_id;

                $shipping_addy  = array(
                    "name" => "CA-FACTS Participant"
                    ,"address1" => $addy1
                    ,"address2" => $addy2
                    ,"city" => $city
                    ,"state" => $state
                    ,"zip" => $zip 
                    ,"recordid" => $record_id
                );
                $shipping_data  = $module->xpsData($hh_id, $testpeople, $shipping_addy);
                $result["xps_put"] = $module->xpsCurl("https://xpsshipper.com/restapi/v1/customers/$xps_client_id/integrations/$xps_integration_id/orders/$hh_id", "PUT", json_encode($shipping_data) );
                $module->emDebug("xps api call to PUT an ORDEr", "https://xpsshipper.com/restapi/v1/customers/$xps_client_id/integrations/$xps_integration_id/orders/$hh_id");

                // SAVE TO REDCAP
                $data   = array(
                    "record_id"             => $record_id,
                    "kit_qr_code"           => $qrscan,
                    "kit_household_code"    => $hh_id,
                    "hhd_participant_id"    => $part_id,
                    "xps_booknumber"        => "pending"
                );
                $r      = \REDCap::saveData($pid, 'json', json_encode(array($data)) );

                // Pre Generates Records in Kit Submission Project
                $module->linkKits($record_id, $testpeople, $hh_id, $part_id);
            } 
        break;

        case "printLabel":
            $record_id      = $_POST["record_id"] ?? null;
            $booknumber     = $_POST["booknumber"] ?? null;
            $result         = $module->xpsCurl("https://xpsshipper.com/restapi/v1/customers/$xps_client_id/shipments/$booknumber/label/PDF");
            $module->emDebug("Ok this returns a PDF directly... do i have to save it temp?");

            exit( base64_encode($result) );
        break;

        case "printReturnLabel":
            $record_id  = $_POST["record_id"] ?? null;
            $result     = array();
            if($record_id){
                $fields     = array("record_id","kit_household_code",  "address_1" ,"address_2","city", "state", "zip");
                $q          = \REDCap::getData('json', array($record_id) , $fields);
                $results    = json_decode($q,true);
                $record     = current($results);
                
                $result     = $module->uspsReturnLabel($record["kit_household_code"], $record);
                $data   = array(
                    "record_id"                 => $record_id,
                    "return_tracking_number"    => $result["TrackingNumber"]
                );
                $r      = \REDCap::saveData('json', json_encode(array($data)) );
            }
        break;

        default:
            $record_id  = $_POST["record_id"] ?? null;
            $result     = array();
            if($record_id){
                // kitComplete
                $data   = array(
                    "record_id"                 => $record_id,
                    "kit_shipped_date"          => Date("Y-m-d")
                );
                $r      = \REDCap::saveData('json', json_encode(array($data)) );
            }
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

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
        <br> <b>kit_household_code</b> is <em>empty</em>
        <br> <b>xps_booknumber</b> is <em>empty</em></p>
    <br>
    <br>

    <?php
        $lang_pretty    = array("English", "Spanish", "Vietnamese", "Chinese");
        $lang_suffix    = array("","_s","_v","_m");

        


        $qrscan_src = $module->getUrl("docs/images/fpo_qr_bar.png");
        $label_src  = $module->getUrl("docs/images/ico_printlabel.png");
        $pending    = $module->getPendingInvites();
        $dumphtml   = array();
        $dumphtml[] = "<tbody class='table-striped' id='pending_invites'>";
        foreach($pending as $invite){
            $booknumber = $invite["xps_booknumber"];
            $addy_top   = $invite["address_1"];
            if( !empty($invite["address_2"]) ){
                $addy_top .= "<br>" . $invite["address_2"];
            }

            $language       = $invite["language"];
            $smartphone_l   = "smartphone" . $lang_suffix[$language-1];
            $paper_yn       = $invite[$smartphone_l] == 1 ? "NO" : "YES";

            $testpeople_lang = "testpeople" . $lang_suffix[$language-1];

            $addy_bot   = $invite["city"] . ", " . $invite["state"] . " " . $invite["zip"];
            $dumphtml[] = "<tr>";
            $dumphtml[] = "<td class='record_id'><a href='https://redcap.stanford.edu/redcap_v10.2.1/DataEntry/index.php?pid=19070&page=shipping&id=".$invite["record_id"]."&event_id=114529'><b>". $invite["record_id"] ."</b></a></td>";
            $dumphtml[] = "<td class='ac'>". $invite["code"] ."</td>";
            $dumphtml[] = "<td class='addy'>". $addy_top . "<br>" . $addy_bot ."</td>";
            $dumphtml[] = "<td class='lang $paper_yn'><b>$paper_yn</b> (". $lang_pretty[$language-1] .")</td>";
            $dumphtml[] = "<td class='numkits'>". $invite[$testpeople_lang] ."</td>";
            $dumphtml[] = "<td class='qrscan'>";
            if(!empty($booknumber)){
                //if just intaked, wont have booking number
                if($booknumber == "pending"){
                    $search_data = array( "keyword" => $invite["kit_household_code"] );
                    $xps_return  = $module->xpsCurl("https://xpsshipper.com/restapi/v1/customers/$xps_client_id/searchShipments", "POST", json_encode($search_data) );
                    $xps_json    = json_decode($xps_return,1);
                    
                    if(!empty($xps_json["shipments"])){
                        $booked_shipment_info = current($xps_json["shipments"]);
                        
                        if(!empty($booked_shipment_info["bookNumber"])){
                            $booknumber = $booked_shipment_info["bookNumber"];
                            // UPDATE RECORD IN REDCAP
                            $data   = array(
                                "record_id"                 => $invite["record_id"],
                                "xps_booknumber"            => $booknumber,
                                "outgoing_tracking_number"  => $booked_shipment_info["trackingNumber"],
                                "shipping_service"          => $booked_shipment_info["carrierCode"]
                            );
                            $r      = \REDCap::saveData($pid, 'json', json_encode(array($data)) );
                        }
                    }else{
                        $dumphtml[] = '<strong>Household ID : '.$invite["kit_household_code"].'</strong><a href="https://xpsshipper.com/ec/#/batch" class="xps" target="_blank">Process booking numbers on XPSship.com</a>';
                    }
                }
                
                if($booknumber != "pending"){
                    $xps_return  = $module->xpsCurl("https://xpsshipper.com/restapi/v1/customers/$xps_client_id/shipments/$booknumber/label/PDF");
                    $dumphtml[] = '<a href="#" class="printlabel" data-recordid='.$invite["record_id"].' data-booknumber='.$booknumber.'>Print Label</a>';
                    $dumphtml[] = '<a href="#" class="printReturnlabel" data-recordid='.$invite["record_id"].'>Print Return Label</a>';
                }
            }else{
                $dumphtml[] = "<input type='text' name='kit_qr_code' 
                data-addy1='".$invite["address_1"]."' data-addy2='".$invite["address_2"]."' data-city='".$invite["city"]."' data-state='".$invite["state"]."' data-zip='".$invite["zip"]."'
                data-numkits='". $invite[$testpeople_lang] ."' data-recordid='".$invite["record_id"]."' id='record_".$invite["record_id"]."'/><label for='record_".$invite["record_id"]."'></label>";
            }
            $dumphtml[] = "</td>";
            $dumphtml[] = "<td class='kit_complete'><input type='checkbox' class='kit_complete' data-recordid='".$invite["record_id"]."' /></td>";
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
            position:relative;
        }
        #pending_invites .qrscan input[name='kit_qr_code'] {

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

        #pending_invites .qrscan input.loading{
            color:#ccc;
        }
        #pending_invites .qrscan input.failed{
            color:red;
        }
        #pending_invites .qrscan input.success{
            color:green;
        }

        #pending_invites .qrscan input:focus + label{
            display:none;
        }

        a.xps,a.xps:visited {
            color:blue;
            cursor:pointer;
        }
       
        .qrscan .printlabel,
        .qrscan .printReturnlabel{
            text-decoration:none;
            display:block;
            margin-left: 20px;
            margin-bottom:6px;
            cursor:pointer;
        }
        .qrscan .printlabel:before,
        .qrscan .printReturnlabel:before {
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

        .lang.YES{
            color:deeppink
        }
        .lang.NO{
            color:#999;
        }
    </style>
    <table class="table table-bordered">
        <thead>
        <tr class='table-info'>
        <th>Record Id</th>
        <th>Access Code</th>
        <th>Shipping Address</th>
        <th>Include Paper Questionaire? Language</th>
        <th># of Kits</th>
        <th>CLick and scan appropriate KitQR to obtain Household ID</th>
        <th>Complete?</th>
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
                $(this).addClass("loading");

                var record_id   = $(this).data("recordid");
                var qrscan      = $(this).val();
                var testpeople  = $(this).data("numkits");
                var addy1       = $(this).data("addy1");
                var addy2       = $(this).data("addy2");
                var city        = $(this).data("city");
                var state       = $(this).data("state");
                var zip         = $(this).data("zip");

                var _el = $(this);

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "getHouseHoldId",
                            "record_id" : record_id,
                            "qrscan"    : qrscan,
                            "testpeople": testpeople,
                            "addy1": addy1,
                            "addy2": addy2,
                            "city": city,
                            "state": state,
                            "zip": zip
                    },
                    dataType: 'json'
                }).done(function (result) {
                    _el.removeClass("loading");
                    console.log("whats up", result);

                    var hh_id;
                    if(hh_id = result["household_id"]){
                        _el.addClass("success");
                        var par = _el.parent();
                        par.empty();
                        var hhid_span   = $("<strong>").text("Household ID : " + hh_id);
                        par.append(hhid_span);

                        var book_on_xps = $("<a>").attr("href","https://xpsshipper.com/ec/#/batch").addClass("xps").attr("target", "_blank").text("Process booking numbers on XPSship.com");
                        par.append(book_on_xps);
                        // var printlabel  = $("<a>").attr("href","#").addClass("printlabel").attr("data-recordid",record_id).text("Print Label");
                        // par.append(printlabel); 
                    }else{
                        _el.addClass("failed");
                    }
                }).fail(function () {
                    console.log("something failed");
                });
            });

            // PRINT LABEL
            $(".qrscan").on("click", ".printlabel", function(e){
                e.preventDefault();
                var record_id   = $(this).data("recordid");
                var booknumber  = $(this).data("booknumber");

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "printLabel",
                            "record_id"     : record_id,
                            "booknumber"    : booknumber
                    }
                }).done(function (result) {
                    var base64_return_label = result;
                    console.log("label pdf", base64_return_label);

                    let pdfWindow = window.open("")
                    pdfWindow.document.write(
                        "<iframe width='100%' height='100%' src='data:application/pdf;base64, " +
                        encodeURI(base64_return_label) + "'></iframe>"
                    )
                

                    // var pdf_url = '<?= $pdf_printlabel_url ?>' + "&" + $.param(result["address"][0]);
                    // var w = 600;
                    // var h = 300;
                    // var left = Number((screen.width/2)-(w/2));
                    // var tops = Number((screen.height/2)-(h/2));
			        // var pu = window.open(pdf_url, '', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=1, copyhistory=no, width='+w+', height='+h+', top='+tops+', left='+left);
                    // pu.focus();
                }).fail(function () {
                    console.log("something failed");
                });
            });

            // PRINT LABEL
            $(".qrscan").on("click", ".printReturnlabel", function(e){
                e.preventDefault();
                var record_id = $(this).data("recordid");

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "printReturnLabel",
                            "record_id" : record_id
                    },
                    dataType: 'json'
                }).done(function (result) {
                    var base64_return_label = result["ReturnLabel"];
                    // console.log("label pdf", base64_return_label);

                    let pdfWindow = window.open("")
                    pdfWindow.document.write(
                        "<iframe width='100%' height='100%' src='data:application/pdf;base64, " +
                        encodeURI(base64_return_label) + "'></iframe>"
                    )
                }).fail(function () {
                    console.log("something failed");
                });
            });

            $(".kit_complete").click(function(){
                if($(this).is(":checked")){
                    var record_id   = $(this).data("recordid");
                    var _this       = $(this);
                    // CHECKING WILL SET THE SHIPPED DATE 
                    $.ajax({
                        method: 'POST',
                        data: {
                                "action"    : "kitComplete",
                                "record_id" : record_id
                        },
                        dataType: 'json'
                    }).done(function (result) {
                        console.log("done, remove from UI");
                        _this.closest('tr').fadeOut("medium", function(){
                            $(this).remove();
                        });
                    }).fail(function () {
                        console.log("something failed");
                    });
                }
            });
        });
    </script>
</div>
<?php } 
?>
