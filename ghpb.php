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
$user='login'; // логин до '@'
$domain='gmail.com'; // домен после '@'
$pass='password'; // пароль
$host='xmpp.l.google.com'; // jabber сервер
$port=5222; // порт

// устанавливаем соединение с сервером
$stream = fsockopen($host,$port,$errorno,$errorstr,10);
// эти настройки необходимы, чтобы при получении данных из потока не было зависания.
// иначе при обнаружении пустой строки php зависнет в длительном ожидании
stream_set_blocking($stream,0);
stream_set_timeout($stream,3600*24);
// после соединения с сервером посылаем приветствие(все как писал ранее)
$xml='<?xml version="1.0"?><stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
fwrite($stream,$xml."\n"); // отправка данных на сервер в конце ставится перенос строки \n
$xmlin=getxml($stream); // получение ответа от сервера
// обрабатываем ответ сервера, узнаем может ли сервер работать в защищенном режиме,если может переходим в защищенный режим
// посылаем команду на переход в защищенный режим
$xml = '<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream); // получаем ответ
// если сервер подтвердил переводим поток в защищенный режим
stream_set_blocking($stream, 1); // сначала блокировку ставим в 1
stream_socket_enable_crypto($stream, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT); // переходим в защищенный режим
stream_set_blocking($stream, 0); // блокировку обратно ставим в 0
// после перехода в защищенный режим снова посылаем приветствие
$xml = '<?xml version="1.0"?>';
$xml .= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
fwrite($stream, $xml."\n");
$xmlin=getxml($stream); // получение ответа
// теперь проходим авторизацию
$xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN">';
$xml .= base64_encode("\x00".$user."\x00".$pass); // вот так кодируется логин пароль для этого типа авторизации
$xml .= '</auth>';
fwrite($stream, $xml."\n");
$xmlin=getxml($stream);
// после авторизации опять посылаем приветствие
$xml = '<?xml version="1.0"?>';
$xml .= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="'.$domain.'" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream);
// сейчас устанавливаем имя ресурса (расположение вашего клиента)
$xml = '<iq type="set" id="2"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>phpbot</resource></bind></iq>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream);
var_dump($xmlin);
//<iq id="2" type="result"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><jid>hangbangbong@gmail.com/phpbot2D97E29B</jid></bind></iq>
if(preg_match('/(.*)jid>(.*)<\/jid(.*)/',$xmlin,$matches)){
  var_dump($matches);
}
// пошла сессия
$xml = '<iq type="set" id="sess_2" to="'.$domain.'"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream);
// а теперь можно получить список контактов
//$xml = '<iq type="get" id="3"><query xmlns="jabber:iq:roster"/></iq>';
//fwrite($stream,$xml."\n");
//$xmlin=getxml($stream); // здесь сейчас список ваших контактов
// ну и теперь выходим в онлайн и становимся видимыми для ваших контактов
$xml = '<presence><show></show><status>online</status><priority>10</priority></presence>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream); // после выхода в онлайн здесь будут получены офлайн сообщения и дополнительная информация по статусам ваших контактов.
// теперь можно отправить сообщение например для контакта asd@asd.ru
// в поле from указываете полный JID вместе с ресурсом(он должен быть получен в ответе сервера при установке ресурса), в поле to - кому адресовано сообщение, если ресурс не известен, можно без указания ресурса.

$to='xxxxxxxxxxxxxxxxxxxxxxxxxx@public.talk.google.com'; 

$xml ='<message type="chat" from="'.$matches[2].'" to="'.$to.'" id="et5r">';
$xml.='<body>привет</body>';
$xml.='</message>';
fwrite($stream,$xml."\n");
$xmlin=getxml($stream);
var_dump($xmlin);

// Если есть необходимость, можно зациклить скрипт и оставаться подключенным и получать входящие данные
while(1){
  sleep(3); // ставим паузу в 3 секунды, чтобы не создавать большую нагрузку на php
  $xmlin=getxml($stream); // и раз в 3 секунды идет сбор данных из потока. тут будут приходить сообщения, информация о смене статусов ваших контактов и т.д
  if($xmlin) var_dump($xmlin);
  if(preg_match("/(.*)from=\"(.*)\/(.*)\" to(.*)code--(.*)--(.*)/",$xmlin,$match)){
    $xml='<message type="chat" from="'.$matches[2].'" to="'.$match[2].'" id="et5r"><body>'.system('date').'</body></message>';
    fwrite($stream,$xml."\n");
    $xmlin=getxml($stream);
  }
  if(preg_match("/(.*)from=\"(.*)\/(.*)\" to(.*)>temp<(.*)/",$xmlin,$match)){
    $yaweatherxml=simplexml_load_file('http://export.yandex.ru/weather-ng/forecasts/29570.xml');
    $xml='<message type="chat" from="'.$matches[2].'" to="'.$match[2].'" id="et5r"><body>'.$yaweatherxml->fact->temperature.'</body></message>';
    fwrite($stream,$xml."\n");
    $xmlin=getxml($stream);
  }


  $directory = './spool/';
  $scanned_directory = array_diff(scandir($directory), array('..', '.'));
  foreach ($scanned_directory as $key => $value){
    var_dump($scanned_directory[$key]);
    $file = file_get_contents('./spool/'.$scanned_directory[$key], true);
    $file=trim($file);
    $xml = '<message type="chat" from="'.$matches[2].'" to="'.$scanned_directory[$key].'" id="et5r"><body>'.$file.'</body></message>';
    fwrite($stream,$xml."\n");
    $xmlin=getxml($stream);
    echo $xml;
    unlink('./spool/'.$scanned_directory[$key]);
  }
  $xml = '<presence><show></show><status>online</status><priority>10</priority></presence>';
  fwrite($stream,$xml."\n");
}

echo "KAPUT";

?>
