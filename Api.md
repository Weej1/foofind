## Foofind  Api usage ##
The foofind API uses a REST-like interface. This means that our method calls are made over the internet by sending HTTP GET requests to the Foofind REST API server.

The server response is xml format.

## Methods ##

### getSearch ###

**this method retrieves search results with the following params:**

  * q (query) Example q=centos
  * lang (language) there are 2 languages available lang=es or lang=en
  * src (sources)  w:web, g:gnutella, t:torrent, f:ftp, e:ed2k
  * type (file type) (Audio,Video,Image,Document,Software,Archive)
  * year (year)
  * brate (bitrate)
  * results (number of results) max 40 - default 10


#### Method getSearch request sample: ####
http://foofind.com/api/?method=getSearch&q=centos&lang=es&src=wftge&opt=&type=&size=&year=&brate=&results=

#### Method getSearch response sample: ####
```
<SearchrestServer generator="zend" version="1.0">
 <getSearch>
  <key_0>
   <size>0</size>
   <type>Audio</type>
   <dlink><![CDATA[http://foofind.com/en/download/5013076/CentOS]]></dlink>
   <md>
    <album>Mandrake</album>
    <title><b>CentOS</b></title>
    <seconds>410.0</seconds>
    <year>2009</year>
    <genre>Blues</genre>
    <artist>Zodio</artist>
   </md>
  </key_0>

  <key_1>
   <size>4000606340</size>
   <type>Document</type>
   <dlink><![CDATA[http://foofind.com/en/download/5282897/CentOS-5_4-i386-bin-DVD_torrent]]</dlink>
  </key_1>

  <key_2>
   <size>3718250992</size>
   <type>Archive</type>
   <dlink><![CDATA[http://foofind.com/en/download/3165961/CentOS-5_0-i386-bin-DVD_torrent]]></dlink>
  </key_2>

  <key_3>
   <size>4294967295</size>
   <type>Archive</type>
   <dlink><![CDATA[http://foofind.com/en/download/9094860/CentOS-5_4-x86_64-bin-DVD_torrent]]></dlink>
  </key_3>

  <key_4>
   <size>663783424</size>
   <type/>
  <dlink><![CDATA[http://foofind.com/en/download/19062115/[peerates_net] - LINUX - 2007 - CentOS -5_0 - i386 - bin - CD4of6_iso]]></dlink>
  </key_4>

  <key_5>
   <size>723449856</size>
   <type>Software</type>
   <dlink><![CDATA[http://foofind.com/en/download/5900802/CentOS-5_4-i386-LiveCD_iso]]></dlink>
  </key_5>

   <key_6>
    <size>730816512</size>
    <type>Software</type>
    <dlink><![CDATA[http://foofind.com/en/download/15901777/CentOS-5_2-i386-LiveCD_iso]]></dlink>
   </key_6>

   <key_7>
    <size>20844412</size>
    <type>Software</type>
    <dlink><![CDATA[http://foofind.com/en/download/15033969/linux_instalacion CentOS_rar]]></dlink>
   </key_7>

  <status>success</status>
 </getSearch>
</SearchrestServer>
```

#### Status request ####

All the requests will be completed with a status message , with the following values:
  * success (when the request was successful)
  * failed (if you get this status request , you could fetch the response-message value from the xml to know more about the error.

```
<response>
<message>No Method Specified.</message>
</response>
<status>failed</status>
```


---

#### PHP Basic usage sample ####

```
<?php 
 $html =  file_get_contents('http://foofind.com/api/?method=getSearch&q=centos&lang=es&src=wftge&opt=&type=&size=&year=&brate=&results=');
  $xml = new SimpleXMLElement($html);
  var_dump($xml);

?>
```