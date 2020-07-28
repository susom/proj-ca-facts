<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */

//https://www.ca-facts.org/
header("Access-Control-Allow-Origin: *");

$module->emLog($_REQUEST, "Incoming Request - QR FROM TEST => HOUSEHOLD ID + #1 (Head of Household), #2, or #3?" . __DIR__);
if (!$module->parseKitQRInput()) {
    $module->returnError("Invalid Request Parameters - check your syntax");
}

// Response is handled by $module
$module->KitSubmitHandler();

