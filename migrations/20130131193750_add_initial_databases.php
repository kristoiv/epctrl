<?php

use Phinx\Migration\AbstractMigration;

class AddInitialDatabases extends AbstractMigration
{
    /**
     * Change.
     */
    public function change()
    {
        $user = $this->table('User');
        $user->addColumn('email', 'string')
             ->addColumn('password', 'string')
             ->addIndex(array('email'), array('unique' => true))
             ->create();

        $favourite = $this->table('Favourite', array('id' => false));
        $favourite->addColumn('user_id', 'integer')
                  ->addColumn('directory', 'string')
                  ->addIndex(array('user_id', 'directory'), array('unique' => true))
                  ->create();

        $viewed = $this->table('View', array('id' => false));
        $viewed->addColumn('user_id', 'integer')
               ->addColumn('directory', 'string')
               ->addColumn('number', 'integer')
               ->addColumn('time', 'biginteger')
               ->addIndex(array('user_id', 'directory', 'number'), array('unique' => true))
               ->create();

        $logins = $this->table('Login', array('id' => false));
        $logins->addColumn('user_id', 'integer')
               ->addColumn('time', 'biginteger')
               ->addColumn('ip', 'string')
               ->addIndex(array('user_id', 'time'), array('unique' => true))
               ->create();
    }
}
