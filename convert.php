<?php
require 'vendor/autoload.php';

var_dump($argv);

$html_file = $argv[1];
$text_file = $argv[2];

$html_handle = fopen($html_file, "r") or die("Unable to open file!");
$text_handle = fopen($text_file, "w"); 

$lines = "";
while (($line = fgets($html_handle)) !== false) {
    $lines .= $line;
}
$txt = Html2Text\Html2Text::convert($lines);
fwrite($text_handle, $txt);

fclose($html_handle);
fclose($text_handle);

?>
