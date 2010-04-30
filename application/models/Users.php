<?php

class Model_Users extends Zend_Db_Table_Abstract
{
    public function getComments($id)
    {
        $table = new ff_comment();
        return $table->fetchAll("IdFile=$id");
    }
}

class ff_users extends Zend_Db_Table
{
    protected $_primary = 'IdUser';
}

class ff_vote extends Zend_Db_Table
{
    protected $_primary = array('IdFile','IdFilename', 'IdUser', 'VoteType');
}

class ff_comment extends Zend_Db_Table
{
    protected $_primary = 'IdComment';
}

class ff_comment_vote extends Zend_Db_Table
{
    protected $_primary = array('IdComment','IdUser');
}
