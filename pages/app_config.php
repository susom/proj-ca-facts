<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


require APP_PATH_DOCROOT . "ControlCenter/header.php";

$XML_AC_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSACCESSDBTEST_2020-06-10_1338.REDCap.xml");
$XML_KO_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSMAINPROJECTKi_2020-06-23_1523.REDCap.xml");
$XML_KS_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSKITSUBMISSION_2020-06-23_1523.REDCap.xml");
?>
<div style='margin:20px;'>

	<h3>CA-FACTS Project EM Requirements</h3>
	<p>This EM will coordinate between <b>3 REDcap projects</b> to intake and track conversions of direct mail invitations for participation in home COVID testing.</p>
	<p>Each of the 3 projects will need to have the <b>CA Facts Project EM</b> installed.</p>
	<p>One of each of the following modes should be set for the 3 respective project's' EM configurations:</p>
	<ul>
		<li>Access Code DB Project - <?php echo "<a href='$XML_AC_PROJECT_TEMPLATE'>XML project creation template</a>" ?></li>
		<li>Kit Order Project - <?php echo "<a href='$XML_KO_PROJECT_TEMPLATE'>XML project creation template</a>" ?></li>
		<li>Kit Submission Project - <?php echo "<a href='$XML_KS_PROJECT_TEMPLATE'>XML project creation template</a>" ?></li>
	</ul>

	<br>
	<br>
	
	<h4>Enabled Projects (3 Required)</h4>
	<div>
		<?php echo $module->displayEnabledProjects( array("access_code_db" => $XML_AC_PROJECT_TEMPLATE, "kit_order" => $XML_KO_PROJECT_TEMPLATE, "kit_submission" => $XML_KS_PROJECT_TEMPLATE)  ) ?>
	</div>
	
</div>

