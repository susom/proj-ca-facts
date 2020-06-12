<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$XML_PROJECT_TEMPLATE = $module->getUrl("docs/CAFACTSACCESSDBTEST_2020-06-10_1338.REDCap.xml");
?>

<h4>Download CA-FACTS instrument:</h4>
<p>
    <?php echo "<a href='$XML_PROJECT_TEMPLATE'>CA-FACTS XML project template</a>" ?>
</p>

