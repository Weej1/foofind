## Data ##
  * **foo**: _files_ {
    * **`_`id**: _main URI = SHA256 of type and URL of main source_ (ObjectId),
    * **c**: _random_,
    * **z**: _size_,
    * **ls**: _last seen date_,
    * **i**: _server images_ [
      * .. ] ,
    * **fs**: _first seen date_,
    * **ct**: _content type_,
    * **bl**: _0=ok, 1=blocked file, 3=unreachable file_,
    * **s**: _server where is located_,
    * **tt**: _times touched_,
    * **md**: _metadata_ {
      * _key_: _value_,
      * ...},
    * **fn**: _filenames_ {
      * _filename-crc_: { **n**: _filename_, **x**: _extension_},
      * ...},
    * **src**: _sources_ {
      * _URI = SHA256 of type and URL_: {
        * **t**: _type of source_,
        * **url**: _unique locator for file_,
        * **bl**: !=0 bloqued source,
        * **fn**: _filenames_ {
          * _filename-crc_:{ **l**:_last sources count_, **m**:_max sources count_},
          * ...},
        * **l**: _last sources count_,
        * **m**: _max sources count_ },
      * ...},
    * **vs**: _votes_ {
      * _language_: {**t**: _vote average_, **c**: `[` _pos votes count_, _neg votes count_ `]`, **s**: `[` _pos votes sum_, _neg votes sum_ `]` },
      * ...},
    * **cs**: _comments_ {
      * _language_: _count_,
      * ...} }


  * **indir**: _mappings URI `<->` file_ {
    * **`_`id**: _URI from_ (ObjectId),
    * **t**: _main URI pointed to_ (ObjectId),
    * **s**: _server where is located_ }

  * **server**: _data servers on the system_ {
    * **`_`id**: _server id_,
    * **lt**: _last touched_,
    * **ls**: _last sphinxed_,
    * **ss**: _touched since sphinx_,
    * **ip**: _IP address_,
    * **p**: _TCP port_,
    * **c**: _files count_,
    * **mc**: _max supported files count_ }

  * **server\_image**: _data servers of images on the system_ {
    * **`_`id**: _server id_,
    * **ip**: _IP address_,
    * **p**: _TCP port_,
    * **c**: _files count_,
    * **mc**: _max supported files count_ }

  * **users**: _web users_ {
    * **`_`id**,
    * **username**,
    * **email**,
    * **password**,
    * **lang**,
    * **location**,
    * **karma**,
    * **token**,
    * **created**,
    * **type** }

  * **comment**: _files comments_ {
    * **`_`id**: _idUser`_`timestamp_,
    * **f**: _idFile_ (index1),
    * **l**: _language_ (index1),
    * **d**: _date_ (index1),
    * **k**: _user karma_,
    * **t**: _comment text_ }

  * **comment\_vote**: _votes to comments_ {
    * **`_`id**: _idComment`_`idUser_,
    * **u**: _idUser_,
    * **f**: _idFile_,
    * **k**: _user karma_,
    * **d**: _date_ }

  * **vote**: _files votes_ {
    * **`_`id**: _idFile`_`idUser_,
    * **u**: _idUser_ (index1),
    * **k**: _user karma_,
    * **d**: _date_,
    * **l**: _language_ }

## Indexes ##
```
db.users.ensureIndex({'oauthid':1});
db.users.ensureIndex({'email':1});
db.users.ensureIndex({'token':1});
db.users.ensureIndex({'username':1});

db.comment.ensureIndex({'f':1, 'l':1, 'd':1});

db.vote.ensureIndex({'u':1});

db.comment_vote.ensureIndex({'u':1});
db.comment_vote.ensureIndex({'f':1});
```