<?php
include('phpghbot.php');
include('config.php');
$obj = new phpghbot();
$stream=$obj->connect($login,$password);
echo "\n\n-----------------------------------\n\n".$stream[1]."\n\n-----------------------------------\n\n";
$obj->send_message($stream[0],$stream[1],$jid,'hi! my jid='.$stream[1]);
$i=0;
while(TRUE){
  sleep(3);
  $xmlout=$obj->getxml($stream[0]);
  if($xmlout) var_dump($xmlout);
  if($i>10) {
    $obj->write_to_stream($stream[0], '<presence><show></show><status>online</status><priority>10</priority></presence>');
    $i=0;
  }
  $i++;
}