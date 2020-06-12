<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


require APP_PATH_DOCROOT . "ControlCenter/header.php";
?>

<h3>CA-FACTS invitation endpoint</h3>
<p>This is a system level API endpoint to allow an external entity to post data to a REDCap project.</p>
<p>While the <i>endpoint->project</i> pattern is generic, the projects variables/dictionary will be specific to CA-FACTS</p>
<p>Use of this EM requires 3 additional REDCap projects to be created with this EM also installed</p>
<ul>
	<li>Access Code DB Project</li>
	<li>Kit Order Project</li>
	<li>Kit Submission Project</li>
</ul>

<br>
<br>

<h4>Endpoint</h4>
<p>Please configure the external app to use the following url:</p>
<pre>
<?php echo $module->getUrl("pages/signup.php",true, true ) ?>
</pre>

<br>
<br>

<h4>Enabled Projects (there should be 3)</h4>
<div>
	<?php echo $module->displayEnabledProjects() ?>
</div>
