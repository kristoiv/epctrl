<?php

class Model_DbTable_Logins extends Zend_Db_Table_Abstract
{
    protected $_name = 'Login';
    protected $_primary = 'user_id';
    protected $_rowClass = 'Model_Login';

    public function logLogin(Model_User $user)
    {
        $login = $this->createRow();
        $login->user_id = $user->id;
        $login->time = time();
        $login->ip = $_SERVER['REMOTE_ADDR'];
        $login->save();
    }
}

