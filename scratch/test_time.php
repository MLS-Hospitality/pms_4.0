<?php
$sign_in = "10:00:00 pm";
$sign_out = "06:00:00 am";

$in = new DateTime($sign_in);
$Out = new DateTime($sign_out);

if ($Out < $in) {
    echo "Out is before In\n";
    $Out->modify('+1 day');
}

$interval = $in->diff($Out);
echo "Stay time: " . $interval->format('%H:%I:%S') . "\n";
