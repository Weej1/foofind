<?php

class Model_Users extends Zend_Db_Table_Abstract
{
    public function getComments($idFile)
    {
        $table = new ff_comment();
        return $table->fetchAll("IdFile=$idFile");
    }

	/**
	 *	 * Save a new entry
	 * * @param  array $data
	 * * @return int|string
	 * */
	public function saveUser(array $data) {
		$table = new ff_user();
		$fields = $table->info ( Zend_Db_Table_Abstract::COLS );
		foreach ( $data as $field => $value ) {
			if (! in_array ( $field, $fields )) {
				unset ( $data [$field] );
			}
		}
		return $table->insert ( $data );
	}

	public function updateUser(array $data) {
		$table = new ff_user();
		$table->update ( $data,  'IdUser = ?', $data ['IdUser'] );

	}

	public function checkUserEmail($email) {
		$table = new ff_user();
		return $table->fetchRow ( 'email = ?', $email  );
	}

	public function checkUsername($username) {
		$table = new ff_user();
		return $table->fetchRow ( 'username = ?', $username );
	}

	public function getUserToken($email) {
		$table = new ff_user();
		return $table->fetchRow (  'email = ?', $email )->token;
	}

	public function validateUserToken($token) {
		$table = new ff_user();
		return $table->fetchRow (  'token = ?', $token );
	}

        public function checkUserIsLocked($id) {
		$table = new ff_user();
		return $table->fetchRow (  'IdUser = ?', $id )->locked;
	}

	public function fetchUser($id) {
		$table = new ff_user();
                return $table->fetchRow("IdUser=?", $id);
	}
}

class ff_users extends Zend_Db_Table
{
    protected $_primary = 'IdUser';
    	
        public function insert(array $data) {
            $data ['created'] = date ( 'Y-m-d H:i:s' );
            $data ['token'] = md5 ( uniqid ( rand (), 1 ) );

            return parent::insert ( $data );

	}
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
