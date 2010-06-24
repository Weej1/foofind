<?php

class Model_Users 
{

    function  __construct()
    {
            $connection = new Mongo("mongo.users.foofind.com:27017");
             //$connection = new Mongo();
            $db = $connection->foofind;
            $this->collection = $db->users;       
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



    public function deleteVote($idFile, $idUser, $type)
    {
        $table = new ff_vote();
        return $table->delete("IdFile=$idFile and IdUser=$idUser and VoteType=$type");
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
        return $this->collection->insert($data, $safe_insert);

    }

   

    public function updateUser( $username, array $data)
    {
        $newdata = array('$set' => $data);
        return $this->collection->update(array("username" => $username), $newdata);
    }


    function deleteUser($username)
    {
        $filter = array('username' => $username);
        $this->collection->remove($filter);
    }

    function deleteUserComments ($id)
    {
        $table = new ff_comment();
        $table->delete('IdUser =' . (int)$id);
    }

    function deleteUserCommentsVotes ($id)
    {
        $table = new ff_comment_vote();
        $table->delete('IdUser =' . (int)$id);
    }

    function deleteUserVotes ($id)
    {
        $table = new ff_vote();
        $table->delete('IdUser =' . (int)$id);
    }



     public function checkUserLogin( $email, $password )
    {
        return  $this->collection->findOne(  array('email' =>$email,'password' =>$password, 'active' => "1" ) );
       
    }



    public function checkUserEmail($email)
    {
       $user = $this->collection->findOne( array('email' =>$email) );
       return $user;

    }

    public function checkUsername($username)
    {
       $user = $this->collection->findOne( array('username' =>$username) );
       return $user;
    }

    public function getUserToken($email)
    {
       $user = $this->collection->findOne( array('email' =>$email), array('token') );
       return $user['token'];
    }

    public function validateUserToken($token)
    {
        return $this->collection->findOne( array('token' =>$token) );

    }

    public function checkUserIsLocked($id)
    {
        $table = new ff_users();
        return $table->fetchRow (  $table->select()->where( 'IdUser = ?', $id ))->locked;
    }

    public function checkUserType($id)
    {
        $table = new ff_users();
        return $table->fetchRow (  $table->select()->where( 'IdUser = ?', $id ))->locked;
    }


    public function fetchUser($id)
    {
       return $this->collection->findOne( array('IdUser' =>$id) );
    }

     public function fetchUserByUsername($username)
    {

      return $this->collection->findOne( array('username' =>$username) );

    }
    

}

