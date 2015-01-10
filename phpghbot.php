<?php
class phpghbot {
  function connect($login,$password) {
    $stream = fsockopen('xmpp.l.google.com',5222,$errorno,$errorstr,10);
    stream_set_blocking($stream,0);
    stream_set_timeout($stream,3600*24);
    $xml='<?xml version="1.0"?><stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="gmail.com" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
    $this->write_to_stream($stream,$xml);
    $this->write_to_stream($stream,'<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>');
    $this->getxml($stream);
    stream_set_blocking($stream, 1);
    stream_socket_enable_crypto($stream, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    stream_set_blocking($stream, 0);
    $xml = '<?xml version="1.0"?>';
    $xml.= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="gmail.com" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
    $this->write_to_stream($stream,$xml);
    $this->getxml($stream);
    $xml = '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN">';
    $xml.= base64_encode("\x00".$login."\x00".$password);
    $xml.= '</auth>';
    $this->write_to_stream($stream,$xml);
    $xml = '<?xml version="1.0"?>';
    $xml.= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" version="1.0" xmlns="jabber:client" to="gmail.com" xml:lang="en" xmlns:xml="http://www.w3.org/XML/1998/namespace">';
    $this->write_to_stream($stream,$xml);
    $this->write_to_stream($stream,'<iq type="set" id="2"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>phpghbot</resource></bind></iq>');
    $xmlout=$this->getxml($stream);
    preg_match('/(.*)jid>(.*)<\/jid(.*)/',$xmlout,$matches);
    $this->write_to_stream($stream,'<iq type="set" id="sess_2" to="gmail.com"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>');
    $arr[0]=$stream;
    $arr[1]=$matches[2];
    return $arr;
  }
  function getxml($stream){
    sleep(1);
    $xml='';
    $emptyLine=0;
    for($i=0;$i<1600;$i++){
      $line = fread($stream,2048);
      if(strlen($line)==0){
        $emptyLine++;
        if($emptyLine>10) break;
      }else{
        $xml.=$line;
      }
    }
    if(!$xml) return false;
    return $xml;
  }
  function write_to_stream($stream,$xml){
    fwrite($stream,$xml);
  }
  function send_message($stream,$botjid,$tojid,$message){
    $xml='<message type="chat" from="'.$botjid.'" to="'.$tojid.'" id="et5r"><body>'.$message.'</body></message>';
    fwrite($stream,$xml);
  }
}