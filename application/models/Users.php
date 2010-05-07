<?php

class Model_Users extends Zend_Db_Table_Abstract
{
    public function getComments($idFile, $lang)
    {
        $table = new ff_comment();
        $select = $table->select()->from("ff_comment")->setIntegrityCheck(false)->join("ff_users", "ff_users.IdUser=ff_comment.IdUser", "username")->where("IdFile=?",$idFile)->where("ff_comment.lang=?", $lang)->order("date desc");
        return $table->fetchAll($select);
    }

    public function getUserVote($idUser, $idFile)
    {
        $table = new ff_vote();
        $select = $table->select()->where("IdFile=$idFile and IdUser=$idUser");
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

    public function deleteVote($idFile, $idUser, $type)
    {
        $table = new ff_vote();
        return $table->delete("IdFile=$idFile and IdUser=$idUser and VoteType=$type");
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
        $table = new ff_users();
        return $table-> fetchRow ( $table->select()->where( 'email = ?', $email ) );
    }

    public function checkUsername($username)
    {
        $table = new ff_users();
        return $table->fetchRow ( $table->select()->where( 'username = ?', $username ));
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

    public function fetchUser($id)
    {
        $table = new ff_users();
        return $table->fetchRow(  $table->select()->where( 'IdUser = ?', $id ) );
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
