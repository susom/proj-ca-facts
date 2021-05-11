<?php
namespace Stanford\ProjCaFacts;
/** @var \Stanford\ProjCaFacts\ProjCaFacts $module */


$return = $module->sendOneMonthFollowUps();

echo "<pre>";
print_r($return);