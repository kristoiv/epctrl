<?php

require_once(APPLICATION_PATH . '/../library/Phpass/PasswordHash.php');

class Model_User extends Zend_Db_Table_Row_Abstract
{
    public function checkPassword($password)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $passwordHash = new PasswordHash($config->portal->password->iteration_count, $config->portal->password->portable);
        return $passwordHash->CheckPassword($password, $this->password);
    }
}

