<?php

class Model_Users 
{

    function  __construct()
    {
            $connection = new Mongo("mongo.users.foofind.com:27017");
             //$connection = new Mongo();
            $this->db = $connection->foofind;
    }

    function users()
    {
        if (!$this->users) $this->users = $this->db->users;
        return $this->users;
    }

    function userfile()
    {
        if (!$this->userfile) $this->userfile = $this->db->userfile;
        return $this->userfile;
    }

    public function getUserComments($username, $limit)
    {

//        $table = new ff_comment();
//        $select = $table->select()->from("ff_comment")
//                ->where("IdUser=?", $idUser)
//                ->order("date desc")
//                ->limit( $limit );
//
//        return $table->fetchAll($select);


    }

    public function getUserVote($idUser, $idFile)
    {
//        $table = new ff_vote();
//        $select = $table->select()->where("IdFile=?", $idFile)->where("IdUser=?", $idUser);
//        return $table->fetchAll($select);
    }

    public function getCommentVotes($idComment)
    {
        $table = new ff_comment_vote();
        $select = $table->select()
                ->from("ff_comment_vote", "VoteType, count(*) c, sum(karma) k")
                ->where("IdComment=?", $idComment)
                ->group(array("IdComment", "VoteType"));
        return $table->fetchAll($select);
    }

    public function getVotes($idFile)
    {
        $table = new ff_vote();
        $select = $table->select()
                ->from("ff_vote", "VoteType, count(*) c, sum(karma) k")
                ->where("IdFile=?", $idFile)
                ->group(array("IdFile", "VoteType"));
        return $table->fetchAll($select);
    }

    public function getFilesVotes($where)
    {
        $table = new ff_vote();
        $select = $table->select()->from("ff_vote", "IdFile, count(*) c, VoteType")->where($where)->group(array("IdFile", "VoteType"));
        return $table->fetchAll($select);

    }

    public function saveVote(array $data)
    {
        return $this->save(new ff_vote(), $data);
    }

    public function saveUser(array $data)
    {
        $data ['created'] = date ( 'Y-m-d H:i:s' );
        $data ['token'] = md5 ( uniqid ( rand (), 1 ) );
        $data['password'] = hash('sha256', $data['password'], FALSE);
        $data['karma'] = 0.2;


        $safe_insert = true;
        return $this->users()->insert($data, $safe_insert);
    }

    public function updateUser( $username, array $data)
    {
        return $this->users()->update(array("username" => $username), array('$set' => $data));
    }

    function deleteUser($username)
    {
        $filter = array('username' => $username);
        $this->users()->remove($filter);
    }

    public function checkUserLogin($email, $password)
    {
        return $this->users()->findOne( array('email' =>$email,'password' =>$password, 'active' => "1" ) );
    }

    public function getUserToken($email)
    {
       $user = $this->users()->findOne( array('email' =>$email), array('token') );
       return $user['token'];
    }

    public function fetchUserByToken($token)
    {
        return $this->users()->findOne( array('token' =>$token) );
    }

    public function fetchUser($id)
    {
       return $this->users()->findOne( array('IdUser' =>$id) );
    }

    public function fetchUserByUsername($username)
    {
      return $this->users()->findOne( array('username' =>$username) );
    }
    
    public function fetchUserByEmail($email)
    {
       $user = $this->users()->findOne( array('email' =>$email) );
       return $user;
    }
}

