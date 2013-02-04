<?php

class Model_DbTable_Users extends Zend_Db_Table_Abstract
{
    protected $_name = 'User';
    protected $_primary = 'id';
    protected $_rowClass = 'Model_User';

    public function login($email, $password)
    {
        $user = $this->fetchRow( $this->getAdapter()->quoteInto('`email` = ?', $email) );
        if( !$user ) return false;

        if( $user->checkPassword($password) ) {
            $loginTable = new Model_DbTable_Logins();
            $loginTable->logLogin($user);
            return $user;
        }

        return false;
    }

    public function signup($email, $password)
    {
        $row = $this->createRow();
        $row->email = $email;
        $row->setPassword($password);
        $row->save();
        return $row;
    }
}

