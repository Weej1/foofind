<?php

class Model_Users extends Zend_Db_Table_Abstract

{

    public function init()
    {
       $connection = new Mongo();
       $db = $connection->foofy;
       $this->collection = $db->users;

       
    }



    public function getFileComments($idUser, $idFile, $lang)
    {
        $table = new ff_comment();
        $select = $table->select()->from("ff_comment")->setIntegrityCheck(false)
                ->joinLeft("ff_comment_vote", "ff_comment.idcomment=ff_comment_vote.idcomment", array("sum(voteType=1) pos", "sum(voteType=2) neg", "max(if(ff_comment.iduser=$idUser,votetype,0)) myvote"))
                ->join("ff_users", "ff_users.IdUser=ff_comment.IdUser", array("username", "location"))
                ->where("IdFile=?",$idFile)->where("ff_comment.lang=?", $lang)->order("date desc")
                ->group("IdComment");
        return $table->fetchAll($select);
    }


    public function getUserComments($idUser, $limit)
    {
        $table = new ff_comment();
        $select = $table->select()->from("ff_comment")
                ->where("IdUser=?", $idUser)
                ->order("date desc")
                ->limit( $limit );
                
        return $table->fetchAll($select);
    }

    public function getUserVote($idUser, $idFile)
    {
        $table = new ff_vote();
        $select = $table->select()->where("IdFile=?", $idFile)->where("IdUser=?", $idUser);
        return $table->fetchAll($select);
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

    public function getFilesComments($where)
    {
        $table = new ff_comment();
        $select = $table->select()->from("ff_comment", "IdFile, count(*) c")->where($where)->group(array("IdFile"));
        return $table->fetchAll($select);

    }

    public function deleteVote($idFile, $idUser, $type)
    {
        $table = new ff_vote();
        return $table->delete("IdFile=$idFile and IdUser=$idUser and VoteType=$type");
    }

    public function deleteCommentVote($idComment, $idUser, $type)
    {
        $table = new ff_comment_vote();
        return $table->delete("IdComment=$idComment and IdUser=$idUser and VoteType=$type");
    }

    public function saveCommentVote(array $data)
    {
        return $this->save(new ff_comment_vote(), $data);
    }

    public function saveComment(array $data)
    {
        return $this->save(new ff_comment(), $data);
    }

    public function saveVote(array $data)
    {
        return $this->save(new ff_vote(), $data);
    }

    public function saveUser(array $data)
    {
        return $this->save(new ff_users(), $data);
    }

    public function save($table, array $data)
    {
        $fields = $table->info ( Zend_Db_Table_Abstract::COLS );
        foreach ( $data as $field => $value )
        {
            if (! in_array ( $field, $fields )) unset ( $data [$field] );
        }
        return $table->insert ( $data );
    }

    public function updateUser(array $data)
    {
        $table = new ff_users();
        $where = $table->getAdapter ()->quoteInto ( 'IdUser = ?', $data ['IdUser'] );
        $table->update ( $data, $where );
    }


    function deleteUser($id)
    {
        $table = new ff_users();
        $table->delete('IdUser =' . (int)$id);
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
        $table = new ff_users();
        return $table->fetchRow ( $table->select()->where(  'email = ?', $email ))->token;
    }

    public function validateUserToken($token)
    {
        $table = new ff_users();
        return $table->fetchRow (  $table->select()->where( 'token = ?', $token ));
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
       $user = $this->collection->findOne( array('IdUser' =>$id) );
       return $user;
    }

     public function fetchUserByUsername($username)
    {
       $user = $this->collection->findOne( array('username' =>$username) );
        return $user;
    }
    


}

class ff_users extends Zend_Db_Table
{
    protected $_primary = 'IdUser';

    public function insert(array $data)
    {
        $data ['created'] = date ( 'Y-m-d H:i:s' );
        $data ['token'] = md5 ( uniqid ( rand (), 1 ) );
        $data['password'] = hash('sha256', $data['password'], TRUE);
        return parent::insert ( $data );
    }



}

class ff_vote extends Zend_Db_Table
{
    protected $_primary = array('IdFile', 'IdFilename', 'IdUser', 'VoteType');
}

class ff_comment extends Zend_Db_Table
{
    protected $_primary = 'IdComment';
}

class ff_comment_vote extends Zend_Db_Table
{
    protected $_primary = array('IdComment', 'IdUser', 'VoteType');
}
