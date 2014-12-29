#!/usr/bin/php
<?php
function getxml($stream){
  sleep(1); // перед получением информации дадим паузу, чтобы сервер успел отдать информацию
  $xml='';
  $emptyLine = 0;
  for($i=0;$i<1600;$i++){// запрашивать данные 1600 раз, но не более 15 пустых строк
    $line = fread($stream,2048);
    if(strlen($line)==0){
      $emptyLine++;
      if($emptyLine>10) break;
    }else{
      $xml .= $line;
    }
  }
  if(!$xml) return false;
  return $xml;
}
function googlelogin(){
  $user="LOGIN";
  $domain="gmail.com";
  $pass="PASSWORD";
  $host="xmpp.l.google.com";
  $port=5222; // порт
  global $botjid;

  $stream = fsockopen($host,$port,$errorno,$errorstr,10);
  stream_set_blocking($stream,0);
  stream_set_timeout($stream,3600*24);
  $xml='<?xml version="1.0"?><stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
  fwrite($stream,$xml."\n"); // отправка данных на сервер в конце ставится перенос строки \n
  $xmlin=getxml($stream); // получение ответа от сервера
  $xml = '<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>';
  fwrite($stream,$xml."\n");
  $xmlin=getxml($stream); // получаем ответ
  stream_set_blocking($stream, 1); // сначала блокировку ставим в 1
  stream_socket_enable_crypto($stream, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT); // переходим в защищенный режим
  stream_set_blocking($stream, 0); // блокировку обратно ставим в 0
  $xml = '<?xml version="1.0"?>';
  $xml.= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
  fwrite($stream, $xml."\n");
  $xmlin=getxml($stream); // получение ответа
  $xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN">';
  $xml.= base64_encode("\x00".$user."\x00".$pass); // вот так кодируется логин пароль для этого типа авторизации
  $xml.= '</auth>';
  fwrite($stream, $xml."\n");
  $xmlin=getxml($stream);
  $xml = '<?xml version="1.0"?>';
  $xml.= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
  fwrite($stream,$xml."\n");
  $xmlin=getxml($stream);
  $xml='<iq type="set" id="2"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>phpbot</resource></bind></iq>';
  fwrite($stream,$xml."\n");
  $xmlin=getxml($stream);
  if(preg_match('/(.*)jid>(.*)<\/jid(.*)/',$xmlin,$matches)){
  var_dump($matches);
}
$xml = '<iq type="set" id="sess_2" to="'.$domain.'"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream);
$xml = '<presence><show></show><status>online</status><priority>10</priority></presence>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream); // после выхода в онлайн здесь будут получены офлайн сообщения и дополнительная информация по статусам ваших контактов.
$botjid=$matches[2];

$xml ='<message type="chat" from="'.$botjid.'" to="2k5ixly149pkm3e4qne3dsm3jk@public.talk.google.com" id="et5r">';
$xml.='<body>jid= '.$botjid.'</body>';
$xml.='</message>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream);

return $stream;
}
$to='xxxxxxxxxxxxxxxxxxxxxxxxxx@public.talk.google.com';
$stream=googlelogin();
while(1){
  sleep(3); // ставим паузу в 3 секунды, чтобы не создавать большую нагрузку на php
  $xmlin=getxml($stream); // и раз в 3 секунды идет сбор данных из потока. тут будут приходить сообщения, информация о смене статусов ваших контактов и т.д
  if($xmlin) var_dump($xmlin);
  if(preg_match("/(.*)from=\"(.*)\/(.*)\" to(.*)code--(.*)--(.*)/",$xmlin,$match)){
    $xml='<message type="chat" from="'.$botjid.'" to="'.$to.'" id="et5r"><body>'.system('date').'</body></message>';
    fwrite($stream,$xml."\n");
    $xmlin=getxml($stream);
  }
  if(preg_match("/(.*)from=\"(.*)\/(.*)\" to(.*)>temp<(.*)/",$xmlin,$match)){
    $yaweatherxml=simplexml_load_file('http://export.yandex.ru/weather-ng/forecasts/29570.xml');
    $xml='<message type="chat" from="'.$botjid.'" to="'.$to.'" id="et5r"><body>'.$yaweatherxml->fact->temperature.'</body></message>';
    fwrite($stream,$xml."\n");
    $xmlin=getxml($stream);
  }
  if(is_file('./message')){
    $message=file_get_contents('./message');
    $xml='<message type="chat" from="'.$botjid.'" to="'.$to.'" id="et5r"><body>'.$message.'</body></message>';
    unlink('./message');
    fwrite($stream,$xml."\n");
    $xmlin=getxml($stream);
  }
  if(is_file('./send.xml')){
    $xml=file_get_contents('./send.xml');
    unlink('./send.xml');
    fwrite($stream,$xml."\n");
    var_dump($xml);
    $xmlin=getxml($stream);
    var_dump($xmlin);
  }
  $xml = '<presence><show></show><status>online</status><priority>10</priority></presence>';
  if(!fwrite($stream,$xml."\n")) {
    echo "RECONECT !!!";
    fclose($stream);
    $stream=googlelogin();
  }
}
?>
