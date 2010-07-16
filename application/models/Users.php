<?php

class Model_Users 
{

    function  __construct()
    {
        $connection = new Mongo("mongo.users.foofind.com:27017");
        $this->db = $connection->foofind;
    }

    public function getCommentVotes($idComment)
    {
        return $this->db->comment_vote->find(array('_id'=>new MongoRegex("/^$idComment/")));
    }

    public function getFileComments($idFile, $lang)
    {
        return $this->db->vote->find(array('f'=>$idFile));
    }

    public function getFileVotes($idFile)
    {
        return $this->db->vote->find(array('_id'=>new MongoRegex("/^$idFile/")));
    }

    public function saveVote(array $data)
    {
        return $this->db->vote->save($data);
    }

    public function saveComment(array $data)
    {
        return $this->db->comment->save($data);
    }

    public function saveCommentVote(array $data)
    {
        return $this->db->comment_vote->save($data);
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

