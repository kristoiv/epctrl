<?php

use Phinx\Migration\AbstractMigration;

class AddLanguageFieldToUserTable extends AbstractMigration
{
    /**
     * Change.
     */
    public function change()
    {
        $this->table('User')->addColumn('language', 'string', array('default' => 'en'))->save();
    }
}
