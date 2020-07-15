<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

//https://www.ca-facts.org/
header("Access-Control-Allow-Origin: https://staging.ca-facts.org");

$module->emLog($_REQUEST, "Incoming Request - Form Post expecting Access Code + Zip Code" . " " . __DIR__);
if (!$module->parseFormInput()) {
    $module->returnError("Invalid Request Parameters - check your syntax");
}

// Response is handled by $module
$module->formHandler();

/*

1) Get code + ZIP from post
2) ajax call to this page to verify code and zip match - if so, then create a new record with the code and redirect to survey

 */
