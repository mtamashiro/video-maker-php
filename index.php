<?php
require "vendor/autoload.php";
include 'credentials/Credentials.php';
include "classes/Text.php";
include "classes/Image.php";
include "classes/Video.php";


if (isset($argv[1])) {

    $term = $argv[1];

    $text = new Text();
    $data = $text->process($term);
    $image = new Image($data);
    $data = $image->process($term);
    $video = new Video($data);
    $data = $video->process($term);




} else {
    echo 'digite o que será pesquisado' . PHP_EOL;
}


