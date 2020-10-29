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
                $result     = $module->parseResultsCSVtoDB($file);
            }

            echo "<p id='upload_results'>".json_encode($result)."</p>";
            exit;
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
        $failed             = $module->getUrl("docs/images/icon_fail.png");
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
            /* padding:10px;
            border-radius: 3px;
            border: 1px solid #ccc; */
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
    
    <section id="pending_invites">

        <div class='qrscan'>
            <h6 class="next_step">Upload CSV Here</h6>
            <em>Takes 1+ seconds per record</em>
            <br><br>
            <form method="post" enctype="multipart/form-data">
            <label for='upload_csv'></label><input type='file' name='upload_csv' id='upload_csv' placeholder="Test Results CSV"/>
            </form>
            <h6 id="result_msg" class="d-block my-3"></h6>
        </div>
    </section>
    <a href="<?=$link_kit_upc?>" id="upload_btn" type="button" class="btn btn-lg btn-primary">Upload and Process File</a>



    
    <script>
        $(document).ready(function(){
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
                            $("#result_msg").addClass("loaded").html("Success " + success_records + " of <b>" + total_rows + "</b> records successfully updated");

                            var failed = $("<textarea>").attr("id","failed_rowids").val("UPC not found in main project:\r\n" + fail_records.join("\r\n"));
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
        });
    </script>
</div>
<?php } ?>