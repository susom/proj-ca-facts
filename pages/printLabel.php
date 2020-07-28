<?php 
require '../vendor/autoload.php';


$qs = $_GET;

$address_1  = $qs["address_1"] ?? null;
$address_2  = $qs["address_2"] ?? null;
$city       = $qs["city"] ?? null;
$state      = $qs["state"] ?? null;
$zip        = $qs["zip"] ?? null;
$code       = $qs["code"] ?? null;
$testpeople = $qs["testpeople"] ?? null;

?>
<style>
#addy{
    width:600px; 
    margin:0 auto;
    overflow:hidden;
    padding-top:100px;
}
address{
    font-style:normal;
    display:inline-block; 
}
#send {
    float:right;
    width: 60%;
    margin-bottom:50px;
}
#note{
    clear:both;
    padding:20px; 
}
</style>
<div id='addy'>
    <div id="send">
        <address>
        <div>CA-FACTS Participant</div>
        <div>Code: <?= $code?></div>
        <div><?= $address_1?></div>
        <div><?= $address_2?></div>
        <div><?= $city?>, <?= $state?> <?= $zip?></div>
        </address>
    </div>

    <?php 
        if($testpeople){ 
            echo "<div id='note'>
                Kits Included : $testpeople
            </div>";
        } 
    ?>
</div>