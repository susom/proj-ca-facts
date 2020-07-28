<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$XML_AC_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSACCESSDBTEST_2020-06-10_1338.REDCap.xml");
$XML_KO_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSMAINPROJECTKi_2020-06-23_1523.REDCap.xml");
$XML_KS_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSKITSUBMISSION_2020-06-23_1523.REDCap.xml");
?>

<div style='margin:20px 0;'>
    <h4>CA-FACTS EM Requirements</h4>
    <p>This EM will coordinate between <b>3 REDcap projects</b> to intake and track conversions of direct mail invitations for participation in home COVID testing.</p>
    <p>Once created, all three projects must have the <b>CA Facts Project EM</b> installed and configured to be identified as<br> <b>[ACCESS CODE DB], [KIT ORDER (MAIN)], and [KIT SUBMISSION]</b> respectively</p>

    <br>
    <br>

    <h5>Download CA-FACTS Project XML Templates:</h5>
    <ul>
    <li><?php echo "<a href='$XML_AC_PROJECT_TEMPLATE'>CA-FACTS Access Code XML project template</a>" ?></li>
    <li><?php echo "<a href='$XML_KO_PROJECT_TEMPLATE'>CA-FACTS Kit Order XML project template</a>" ?></li>
    <li><?php echo "<a href='$XML_KS_PROJECT_TEMPLATE'>CA-FACTS Kit Submission XML project template</a>" ?></li>
    </ul>

    <br>
    <br>

    <h4>Enabled Projects (3 Required)</h4>
    <div>
        <?php echo $module->displayEnabledProjects(array("access_code_db" => $XML_AC_PROJECT_TEMPLATE, "kit_order" => $XML_KO_PROJECT_TEMPLATE, "kit_submission" => $XML_KS_PROJECT_TEMPLATE)  ) ?>
    </div>

    <br>
    <br>
    
    <?php
        if($module->getProjectSetting("em-mode") == "kit_order"){
    ?>
        <h4>Form Sign Up Endpoint</h4>
        <p>Please configure the external app to use the following url:</p>
        <pre><?php echo $module->getUrl("pages/signup.php",true, true ) ?></pre>

        <br>
        <br>

        <h4>Twilio Callback Endpoint</h4>
        <p>Please configure the twilio phone number to callback the following url:</p>
        <pre><?php echo $module->getUrl("pages/signup-ivr.php",true, true ) ?></pre>
    <?php
        }else{
    ?>
        <h4>Form Sign Up Endpoint</h4>
        <p>To view this endpoint URL please go to the EM instructions for the main project (kit_order)</p>
        
        <br>
        <br>

        <h4>Twilio Callback Endpoint</h4>
        <p>To view this endpoint URL please go to the EM instructions for the main project (kit_order)</p>
    <?php
        }
    ?>
</div>