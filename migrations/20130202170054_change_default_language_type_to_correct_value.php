<?php

use Phinx\Migration\AbstractMigration;

class ChangeDefaultLanguageTypeToCorrectValue extends AbstractMigration
{
    /**
     * Change.
     */
    public function change()
    {
        $this->table('User')->changeColumn('language', 'string', array('default' => 'en-us'))->save();
    }
}
