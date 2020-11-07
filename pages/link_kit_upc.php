<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

if(!empty($_POST["action"])){
    $action = $_POST["action"];
    switch($action){
        case "getSubmissionId":
            $qrscan     = $_POST["qrscan"] ?? null;
            $result     = $module->getKitSubmissionId($qrscan);
            if(isset($result["participant_id"])){
                $result     = array("error" => false, "record_id" => $result["record_id"], "participant_id" => $result["participant_id"], "main_id" => $result["main_id"], "all_matches" => $result["all_matches"]);
            }else{
                $result     = array("error" => true);
            }
        break;

        case "linkUPC":
            $upcscan        = $_POST["upcscan"] ?? null;
            $qrscan         = $_POST["qrscan"] ?? null;
            $records        = $_POST["records"] ?? array();
            $mainid         = $_POST["mainid"] ?? null;

            $record_ids     = explode(",",$records);
            foreach($record_ids as $record_id){

                // SAVE TO REDCAP
                $data   = array(
                    "record_id"         => $record_id,
                    "kit_upc_code"      => $upcscan,
                    "kit_qr_input"      => $qrscan,
                    "household_record_id" => $mainid
                );
                $result = \REDCap::saveData('json', json_encode(array($data)) );

                $module->emDebug("i need to add the main record", $data);
            }
        break;

        case "saveField":
            $field_type = $_POST['field_type'];
            if($field_type == "file"){
                $file   = current($_FILES);
                $result = $module->parseUPCLinkCSVtoDB($file);

                echo "<p id='upload_results'>".json_encode($result)."</p>";
                exit;
            }
        break;

        case "getHHID" :
            $qrscan = $_POST["qrscan"] ?? null;
            $result = $module->getHouseHoldId($qrscan);
        break;

        default:
        break;
    }

    echo json_encode($result);
    exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$em_mode = $module->getProjectSetting("em-mode");
?>
<div style='margin:20px 40px 0 0;'>
    

    <?php
        $loading            = $module->getUrl("docs/images/icon_loading.gif");
        $loaded             = $module->getUrl("docs/images/icon_loaded.png");
        $failed             = $module->getUrl("docs/images/icon_fail.png");
        $qrscan_src         = $module->getUrl("docs/images/fpo_qr_bar.png");
        $doublearrow_src    = $module->getUrl("docs/images/icon_doublearrow.png");
        $link_kit_upc       = $module->getUrl("pages/link_kit_upc.php");
    ?>
    <style>
        #resultjson {
            display:none;
        }
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

        #result_msg.good { color:green }
        #result_msg.bad { color:red }

        #result_msg{ 
            position:relative; 
            min-height:20px;
            padding-left:25px;    
        }
        #result_msg::before{
            content:"";
            position:absolute;
            left:0; top:0;
            width:20px;
            height:20px;
        }
        #result_msg.loading::before{
            background:url(<?=$loading?>) 50% no-repeat; 
            background-size:contain;
        }
        #result_msg.loaded::before{
            background:url(<?=$loaded?>) 50% no-repeat; 
            background-size:contain;
        }
        #result_msg.failed::before{
            background:url(<?=$failed?>) 50% no-repeat; 
            background-size:contain;
        }
        #failed_rowids{
             min-height:150px;
        }
    </style>
    
    
        <h4>Verify QR CODE (will return data if found in API)</h4>
        <section id="pending_invites">
        <div class='qrscan align-top'>
            <h6 class="next_step">Check Valid QR Code</h6>
            <input type='text' name='kit_qr_code' id="checkQR"/><label for='checkQR'></label> <button class="btn btn-lg btn-primary">search</button>
            <pre id='resultjson'></pre>
        </div>
    </section>
  
    <br><br>
    <hr>
    <br><br>


    <h4>Bulk Upload Test Kit QR to Test Tube UPC [CSV]</h4>
    <section id="bulk upc link csv upload">
        <div class='qrscan'>
            <h6 class="next_step">Upload CSV Here</h6>
            <em>Takes 1+ seconds per record</em>
            <br><br>
            <form method="post" enctype="multipart/form-data">
            <label for='upload_csv'></label><input type='file' name='upload_csv' id='upload_csv' placeholder="QR-UPC Link CSV"/>
            </form>
            <h6 id="result_msg" class="d-block my-3"></h6>
        </div>
    </section>
    <a href="<?=$link_kit_upc?>" id="upload_btn" type="button" class="btn btn-lg btn-primary">Upload and Process File</a>
    
    <script>
        $(document).ready(function(){
            // UI UX 

            // TAKING SCAN INPUT AND GETTING houshold id
            $("input[name='kit_qr_code']").on("focus", function(){
                $(this).val("");
                $(this).css("color","initial");
                $("#resultjson").hide();
            });
            
            $("input[name='kit_qr_code']").on("blur", function(){
                var _el = $(this);

                //give it a second for the input to populate.
                var qrscan = _el.val();

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"    : "getHHID",
                            "qrscan"    : qrscan
                    },
                    dataType: 'json'
                }).done(function (result) {
                    var hhid    = result["household_id"];
                    var partid  = result["survey_id"];

                    // need not found condition?
                    if(!hhid){
                        _el.css("color","red");
                        return;
                    }

                    _el.css("color","green");
                    var pretty  = JSON.stringify(result, undefined, 4);
                    $("#resultjson").text(pretty);
                    $("#resultjson").show();
                }).fail(function () {
                    _el.css("color","red");
                });
            });

            $("input[name='kit_upc_code']").on("input", function(){
                var _el = $(this);
                setTimeout(function(){
                    var upcscan         = _el.val();
                    var kit_records     = _el.attr("data-kitrecords");
                    var main_id         = _el.attr("data-mainrecordid");
                    var qrscan          = $("#test_kit_qr").val();
                    
                    $.ajax({
                        method: 'POST',
                        data: {
                                "action"    : "linkUPC",
                                "upcscan"    : upcscan,
                                "qrscan"    : qrscan,
                                "records"     : kit_records,
                                "mainid"    : main_id
                        },
                        dataType: 'json'
                    }).done(function (result) {
                        console.log("upc linked ", result);
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
                        },750);
                        

                    }).fail(function () {
                        console.log("something failed");
                        _el.css("color","red");
                        _el.val("");
                        _el.attr("placeholder","Error, Scan Again");
                        _el.focus();
                    });
                }, 1000);
            });

            $("#copytoclip").click(function(){
                $("#test_kit_qr").select();
                document.execCommand('copy');
                return false;
            });


            $("#upload_btn").click(function(){
                var file =  $("#upload_csv").prop('files')[0];

                if(file){
                    ajaxlikeFormUpload($("#upload_csv"));
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

                    $(iframe).on('load', function(e) {
                        // Handler for "load" called.
                        var innerDoc    = iframe.contentDocument || iframe.contentWindow.document;
                        var iframe_doc  = $(innerDoc);
                        var result      = iframe_doc.find("#upload_results").text();
                        var result      = $.parseJSON(result);

                        var success_records = result["success"];
                        var fail_records    = result["failed"];
                        var total_rows      = result["total"];

                        $("#result_msg").removeClass("loading");
                        if(success_records){
                            $("#result_msg").addClass("loaded").html( success_records + " of <b>" + total_rows + "</b> records updated");

                            var failed = $("<textarea>").attr("id","failed_rowids").val("QR not found:\r\n" + fail_records.join("\r\n"));
                            failed.insertAfter($("#result_msg"));
                        }else{
                            $("#result_msg").addClass("failed").html("Error : records not updated");
                        }
                    });

                }

                var input_field     = el.attr("name");
                var field_type      = el.attr("type");
                var file            = el.prop('files')[0];

                $("#result_msg").removeClass("loaded").removeClass("failed").removeClass("loading").addClass("loading").text("Processing data ...");
                $("#failed_rowids").remove();

                el.parent().attr("target","iframeTarget");
                el.parent().append($("<input type='hidden'>").attr("name","action").val("saveField"));
                el.parent().append($("<input type='hidden'>").attr("name","field_type").val(field_type));
                el.parent().append($("<input type='hidden'>").attr("name","input_field").val(input_field));
                el.parent().trigger("submit");
            }

            function uploadDone() { //Function will be called when iframe is loaded
                var ret = frames['upload_target'].document.getElementsByTagName("body")[0].innerHTML;
                var data = eval("("+ret+")"); //Parse JSON // Read the below explanations before passing judgment on me
                
                if(data.success) { //This part happens when the image gets uploaded.
                    document.getElementById("image_details").innerHTML = "<img src='image_uploads/" + data.file_name + "' /><br />Size: " + data.size + " KB";
                }
                else if(data.failure) { //Upload failed - show user the reason.
                    alert("Upload Failed: " + data.failure);
                }	
            }
        });
    </script>
</div>


