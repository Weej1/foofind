<?php

class Model_Users 
{
    private function prepareConnection()
    {
        if (!isset($this->db))
        {
            $db = Zend_Registry::get("db_users");
            if (!$db->connected) $db->connect();
            $this->db = $db->foofind;
        }
    }

    public function fillCommentsUsers(array &$comments)
    {
        $this->prepareConnection();
        
        $idusers = $users = array();
        foreach ($comments as $comment) {
            $idusers []= new MongoId(substr($comment['_id'], 0, strpos($comment['_id'], "_")));
        }

        $cursor = $this->db->users->find(array('_id'=>array('$in'=>$idusers)));
        foreach ($cursor as $user) {
            $users[$user['_id']->__toString()] = $user;
        }
        unset ($cursor);

        foreach ($comments as $key=>$comment) {
            $comments[$key]['u'] = $users[substr($comment['_id'], 0, strpos($comment['_id'], "_"))];
        }
    }
    
    public function getUserComments($idUser)
    {
        $this->prepareConnection();
        $comments = array();
        $cursor = $this->db->comment->find(array('_id'=>new MongoRegex("/^$idUser/")));
        foreach ($cursor as $comment) {
            $comments []= $comment;
        }
        unset ($cursor);
        return $comments;
    }

    public function getFileComments($idFile, $lang)
    {
        $this->prepareConnection();
        $comments = array();
        $cursor = $this->db->comment->find(array('f'=>new MongoId($idFile), 'l'=>$lang))->sort(array('d'=>-1));
        foreach ($cursor as $comment) {
            $comments[$comment['_id']] =$comment;
        }
        unset ($cursor);
        return $comments;
    }

    public function getFileCommentsSum($idFile)
    {
        $this->prepareConnection();

        $langs = $this->db->comment->group( array("l"=>1),
                                    array("c" => 0),
                                    new MongoCode("function (o, p) { p.c++; }"),
                                    array('f'=>new MongoId($idFile)) );
        
        $res = array();
        foreach ($langs['retval'] as $lang) {
            $res[$lang['l']] = $lang['c'];
        }
        return $res;
    }

    public function getFileVotesSum($idFile, $idUser)
    {
        $this->prepareConnection();

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
        $this->prepareConnection();
        $cursor = $this->db->comment_vote->find(array('f'=>new MongoId($idFile), 'u'=>$idUser));
        $votes=null;
        foreach ($cursor as $vote) {
            $id = $vote['_id'];
            $pos = strrpos($id, '_');
            $votes[substr($id, 0, $pos)] = $vote;
        }
        unset ($cursor);
        return $votes;
    }

    public function getCommentVotesSum($idComment, $idUser)
    {
        $this->prepareConnection();
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
        $this->prepareConnection();
        return $this->db->comment->findOne(array('_id'=>$id));
    }
    
    public function saveVote(array $data)
    {
        $this->prepareConnection();
        $this->db->vote->save($data);
    }

    public function saveComment(array $data)
    {
        $this->prepareConnection();
        $this->db->comment->save($data);
    }

    public function saveCommentVote(array $data)
    {
        $this->prepareConnection();
        $this->db->comment_vote->save($data);
    }

    public function saveUser(array $data)
    {
        $this->prepareConnection();

        $data ['created'] = new MongoDate();
        if (!isset($data["oauthid"])) {
            $data ['token'] = md5 ( uniqid ( rand (), 1 ) );
            $data['password'] = hash('sha256', $data['password'], FALSE);
        }
        $data['karma'] = 0.2;

        $safe_insert = true;
        return $this->db->users->insert($data, $safe_insert);
    }

    public function updateUser( $username, array $data)
    {
        $this->prepareConnection();

            var_dump($this->db->users);
        return $this->db->users->update(array("username" => $username), array('$set' => $data));
    }

    public function updateCommentVotes($id, $votes)
    {
        $this->prepareConnection();
        $this->db->comment->update( array("_id" =>$id), array('$set' => array( 'vs' => $votes ) ) );
    }

    function deleteUser($username)
    {
        $this->prepareConnection();
        $filter = array('username' => $username);
        $this->db->users->remove($filter);
    }

    public function checkUserLogin($email, $password)
    {
        $this->prepareConnection();
        return $this->db->users->findOne( array('email' =>$email,'password' =>$password, 'active' => 1 ) );
    }

    public function getUserToken($email)
    {
        $this->prepareConnection();
       $user = $this->db->users->findOne( array('email' =>$email), array('token') );
       return $user['token'];
    }

    public function fetchUserByToken($token)
    {
        $this->prepareConnection();
        return $this->db->users->findOne( array('token' =>$token) );
    }

    public function fetchUser($id)
    {
        $this->prepareConnection();
       return $this->db->users->findOne( array('IdUser' =>$id) );
    }

    public function fetchUserByUsername($username)
    {
        $this->prepareConnection();
        return $this->db->users->findOne( array('username' =>$username) );
    }

    public function fetchSimilarUsernames($username)
    {
        $this->prepareConnection();
        $cursor = $this->db->users->find( array('username' =>new MongoRegex("/^".$username."(\\d)*$/")), array('username') );
        $res = array();
        foreach ($cursor as $nick) {
            $res[$nick["username"]]=$nick["username"];
        }
        unset($cursor);
        return $res;
    }
    
    public function fetchUserByEmail($email)
    {
        $this->prepareConnection();
        $user = $this->db->users->findOne( array('email' =>$email) );
        return $user;
    }

    public function fetchUserByOauthid($oauthid)
    {
        $this->prepareConnection();
        $user = $this->db->users->findOne( array('oauthid' =>$oauthid) );
        return $user;
    }
}

