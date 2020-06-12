<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


require APP_PATH_DOCROOT . "ControlCenter/header.php";
?>

<h3>CA-FACTS invitation endpoint</h3>
<p>This is a system level API endpoint to allow an external entity to post data to a REDCap project that has this EM enabled.</p>
<p>While the <i>endpoint->project</i> pattern is generic, the project variables/dictionary will be specific to CA-FACTS</p>

<br>
<br>

<h4>Endpoint</h4>
<p>Please configure the external app to use the following url:</p>
<pre>
<?php echo $module->getUrl("pages/signup.php",true, true ) ?>
</pre>

<br>
<br>

<h4>Enabled Projects</h4>
<div>
	<?php echo $module->displayEnabledProjects() ?>
</div>
