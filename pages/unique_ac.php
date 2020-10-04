<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

if(!empty($_POST["action"])){
    $action = $_POST["action"];
    $result = array();
    switch($action){
        case "generateAC":
            $min            = $_POST["range_start"];
            $max            = $_POST["range_end"];
            $quantity       = $_POST["quantity"];
            $result         = $module->UniqueRandomNumbersWithinRange($min, $max, $quantity);
        break;

        case "saveField":
            $field_type  = $_POST['field_type'];
            if($field_type == "file"){
                $file       = current($_FILES);
                $module->emDebug("killll me");
                $module->parseCSVtoDB_temp($file);
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
    <h4>Generate New Unique AC to paste into appropriately formatted import CSV</h4>
    
    <br>
    <br>
 
    <section id="pending_invites">
        <label>
        <span>Range Start:</span> <input type='number' name='range_start' min="100101" max="999999" step="1000" value="100001"/>
        </label>

        <label>
        <span>Range End:</span> <input type='number' name='range_end' min="100101" max="999999" step="1000" value="999999"/>
        </label>

        <label>
        <span>How Many?:</span> <input type='quantity' name='quantity' value="100"/>
        </label>

        <label>
        <span>Output:</span>
        <textarea id="copy_ac" >
        </textarea>
        <button id='copytoclip' class='btn btn-sm btn-secondary'>+ Copy to clipboard</button>
        </label>
    </section>

    <style>
        #pending_invites label{
            display:block;
            vertical-align:top;
            width:100%;
            margin-bottom:20px; 
        }
        #pending_invites label span {
            vertical-align:top;
            display:inline-block;
            width:100px; 
        }
        #pending_invites textarea {
            display:inline-block;
            vertical-align:top;
            width: 90px;
            height: 200px;
            overflow-y: scroll;
            -ms-overflow-style:scroll;
        }
        #copytoclip{
            display:inline-block;
        }
        #generate_ac {
            color:#fff;
        }
    </style>
    <br>
    <br>
    <a href="#" id="generate_ac" type="button" class="btn btn-lg btn-primary">Generate Unique Unused 6 digit Access Codes</a>

    <br>
    <br>

    <form method="post" enctype="multipart/form-data">
    <label for='upload_csv'></label><input type='file' name='upload_csv' id='upload_csv' placeholder="one time parse CSV"/>
    </form>
    <a href="#" id="upload_btn" type="button" class="btn btn-lg btn-light">Upload and Process File</a>
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
            $("#generate_ac").click(function(e){
                e.preventDefault();
                console.log("here goes");
                var range_start = $("input[name='range_start'").val();
                var range_end   = $("input[name='range_end'").val();
                var quantity    = $("input[name='quantity'").val();

                $.ajax({
                    method: 'POST',
                    data: {
                            "action"        : "generateAC",
                            "range_start"   : range_start,
                            "range_end"     : range_end,
                            "quantity"      : quantity
                    },
                    dataType: 'json'
                }).done(function (results) {
                    console.log("done",results);
                    var acs = results.join("\r\n");
                    $("#copy_ac").html(acs);
                }).fail(function () {
                    console.log("something failed");
                });
            });

            $("#copytoclip").click(function(){
                $("#copy_ac").select();
                document.execCommand('copy');
            });

            $("#upload_btn").click(function(){
                var file =  $("#upload_csv").prop('files')[0];

                if(file){
                    ajaxlikeFormUpload($("#upload_csv"));
                }

                return false;
            });
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
            el.parent().append($("<input type='hidden'>").attr("name","action").val("saveField"));
            el.parent().append($("<input type='hidden'>").attr("name","field_type").val(field_type));
            el.parent().append($("<input type='hidden'>").attr("name","input_field").val(input_field));
            el.parent().trigger("submit");
        }
    </script>
</div>

