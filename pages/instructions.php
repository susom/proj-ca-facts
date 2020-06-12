<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$XML_AC_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSACCESSDBTEST_2020-06-10_1338.REDCap.xml");
$XML_KO_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSACCESSDBTEST_2020-06-10_1338.REDCap.xml");
$XML_KS_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSACCESSDBTEST_2020-06-10_1338.REDCap.xml");
?>

<h4>Download CA-FACTS Project XML Templates:</h4>
<ul>
<li><?php echo "<a href='$XML_AC_PROJECT_TEMPLATE'>CA-FACTS Access Code XML project template</a>" ?></li>
<li><?php echo "<a href='$XML_KO_PROJECT_TEMPLATE'>CA-FACTS Kit Order XML project template</a>" ?></li>
<li><?php echo "<a href='$XML_KS_PROJECT_TEMPLATE'>CA-FACTS Kit Submission XML project template</a>" ?></li>
</ul>
