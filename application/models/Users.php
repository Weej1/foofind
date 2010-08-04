<?php

class Model_Users 
{

    function  __construct()
    {
        $conf = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production'  );
        $connection = new Mongo($conf->mongo->server);
        $this->db = $connection->foofind;
    }

    public function fillCommentsUsers(array &$comments)
    {
        $idusers = $users = array();
        foreach ($comments as $comment) {
            $idusers []= new MongoId(strstr($comment['_id'], "_"));

           
        }

        $cursor = $this->db->users->find(array('_id'=>array('$in'=>$idusers)));
        foreach ($cursor as $user) {
            $users[$user['_id']->__toString()] = $user;
        }

        foreach ($comments as $key=>$comment) {
            $comments[$key]['u'] = $users[strstr($comment['_id'], "_")];
        }
    }
    public function getUserComments($idUser)
    {
        $comments = array();
        $cursor = $this->db->comment->find(array('_id'=>new MongoRegex("/^$idUser/")));
        foreach ($cursor as $comment) {
            $comments []= $comment;
        }
        return $comments;
    }

    public function getFileComments($idFile, $lang)
    {
        $comments = array();
        $cursor = $this->db->comment->find(array('f'=>new MongoId($idFile), 'l'=>$lang))->sort(array('d'=>-1));
        foreach ($cursor as $comment) {
            $comments[$comment['_id']] =$comment;
        }
        return $comments;
    }

    public function getFileCommentsSum($idFile)
    {
        $langs = $this->db->comment->group( array("l"=>1),
                                    array("c" => 0),
                                    new MongoCode("function (obj, prev) { prev.c++; }"),
                                    array('f'=>new MongoId($idFile)) );
        
        $res = array();
        foreach ($langs['retval'] as $lang) {
            $res[$lang['l']] = $lang['c'];
        }
        return $res;
    }

    public function getFileVotesSum($idFile, $idUser)
    {
        $map = new MongoCode("function() {
                emit(this.l, {k:this.k, c:new Array((this.k>0)?1:0, (this.k<0)?1:0),
                                        s:new Array((this.k>0)?this.k:0, (this.k<0)?this.k:0),
                                        u:(this.u=='$idUser')?this.k:0 }); }");
        $reduce = new MongoCode("function(lang, vals) { ".
                                    "var c = new Array(0,0);".
                                    "var s = new Array(0,0);".
                                    "var u = 0;".
                                    "for (var i in vals) {".
                                        "c[0] += vals[i].c[0]; c[1] += vals[i].c[1];".
                                        "s[0] += vals[i].s[0]; s[1] += vals[i].s[1];".
                                        "u += vals[i].u;".
                                    "}".
                                    "t = Math.atan((s[0]*c[0]+s[1]*c[1])/(c[0]+c[1]))/Math.PI+0.5;".
                                    "return {t:t, c:c, s:s, u:u}; }");

        $votes = $this->db->command(array("mapreduce" => "vote", "map" => $map, "reduce" => $reduce,
                             "query" => array('_id'=>new MongoRegex("/^$idFile/"))));
        $langs = $this->db->selectCollection($votes['result'])->find();

        $res = array();
        $u = 0;
        foreach ($langs as $lang=>$vals) {
            $u += $vals['value']['u'];
            unset($vals['value']['u']);
            $res[$lang]=$vals['value'];
        }
        $res['user'] = $u;
        return $res;
    }

    public function getUserFileVotes($idFile, $idUser)
    {
        $cursor = $this->db->comment_vote->find(array('f'=>new MongoId($idFile), 'u'=>$idUser));
        foreach ($cursor as $vote) {
            $id = $vote['_id'];
            $pos = strrpos($id, '_');
            $votes[substr($id, 0, $pos)] = $vote;
        }
        return $votes;
    }

    public function getCommentVotesSum($idComment, $idUser)
    {
        $map = new MongoCode("function() {
                pos = this._id.lastIndexOf('_');
                emit('1', {k:this.k, c:new Array((this.k>0)?1:0, (this.k<0)?1:0),
                                        s:new Array((this.k>0)?this.k:0, (this.k<0)?this.k:0),
                                        u:(this.u=='$idUser')?this.k:0 }); }");
        $reduce = new MongoCode("function(lang, vals) { ".
                                    "var c = new Array(0,0);".
                                    "var s = new Array(0,0);".
                                    "var u = 0;".
                                    "for (var i in vals) {".
                                        "c[0] += vals[i].c[0]; c[1] += vals[i].c[1];".
                                        "s[0] += vals[i].s[0]; s[1] += vals[i].s[1];".
                                        "u += vals[i].u;".
                                    "}".
                                    "t = Math.atan((s[0]*c[0]+s[1]*c[1])/(c[0]+c[1]))/Math.PI+0.5;".
                                    "return {t:t, c:c, s:s, u:u}; }");

        $votes = $this->db->command(array("mapreduce" => "comment_vote", "map" => $map, "reduce" => $reduce,
                             "query" => array('_id'=>new MongoRegex("/^$idComment/"))));
        $vals = $this->db->selectCollection($votes['result'])->findOne();
        return $vals['value'];
    }

    public function getComment($id)
    {
        return $this->db->comment->findOne(array('_id'=>$id));
    }
    
    public function saveVote(array $data)
    {
        $this->db->vote->save($data);
    }
    public function saveComment(array $data)
    {
        $this->db->comment->save($data);
    }

    public function saveCommentVote(array $data)
    {
        $this->db->comment_vote->save($data);
    }

    public function saveUser(array $data)
    {
        $data ['created'] = date ( 'Y-m-d H:i:s' );
        $data ['token'] = md5 ( uniqid ( rand (), 1 ) );
        $data['password'] = hash('sha256', $data['password'], FALSE);
        $data['karma'] = 0.2;

        $safe_insert = true;
        return $this->db->users->insert($data, $safe_insert);
    }

    public function updateUser( $username, array $data)
    {
        return $this->db->users->update(array("username" => $username), array('$set' => $data));
    }

    public function updateCommentVotes($id, $votes)
    {
        $this->db->comment->update( array("_id" =>$id), array('$set' => array( 'vs' => $votes ) ) );
    }

    function deleteUser($username)
    {
        $filter = array('username' => $username);
        $this->db->users->remove($filter);
    }

    public function checkUserLogin($email, $password)
    {
        return $this->db->users->findOne( array('email' =>$email,'password' =>$password, 'active' => "1" ) );
    }

    public function getUserToken($email)
    {
       $user = $this->db->users->findOne( array('email' =>$email), array('token') );
       return $user['token'];
    }

    public function fetchUserByToken($token)
    {
        return $this->db->users->findOne( array('token' =>$token) );
    }

    public function fetchUser($id)
    {
       return $this->db->users->findOne( array('IdUser' =>$id) );
    }

    public function fetchUserByUsername($username)
    {
      return $this->db->users->findOne( array('username' =>$username) );
    }
    
    public function fetchUserByEmail($email)
    {
       $user = $this->db->users->findOne( array('email' =>$email) );
       return $user;
    }
}

