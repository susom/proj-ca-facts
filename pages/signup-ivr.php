<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


$module->emLog($_REQUEST, "Incoming Request - IVR");


if (! $module->parseIVRInput()) {
    $module->returnError("Invalid Request Parameters - check your syntax");
}

// Response is handled by $module
$module->IVRHandler();


// This is the IVR endpoint for Twilio

/*

V1: Enter code
V2: Enter your ZIP

--
Q1. Please select a language:
    1. English,
    2. Spanish
    3. Viet
    4. Chinese

Q2. Y/N Can ypou prick blood

Q3. How many people

Q4. Do you have a phone or computer?

if (yes)

Q5. Can you send you SMS messages on a phone number?
if (yes)

Q6. Enter Phone Number

 */
