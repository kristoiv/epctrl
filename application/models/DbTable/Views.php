<?php

class Model_DbTable_Views extends Zend_Db_Table_Abstract
{
    protected $_name = 'View';
    protected $_primary = array('user_id', 'directory', 'number');
    protected $_rowClass = 'Model_View';
    protected $_sequence = false;

    public function isViewed(Model_Episode $episode, Model_User $user)
    {
        $adapter = $this->getAdapter();
        $view = $this->fetchRow( $adapter->quoteInto('`user_id` = ?', $user->id) . $adapter->quoteInto(' AND `directory` = ?', $episode->getShow()->getDirectory()) . $adapter->quoteInto(' AND `number` = ?', $episode->getNumber()) );
        return $view != false;
    }

    public function markAsViewed(Model_Episode $episode, Model_User $user)
    {
        if( $this->isViewed($episode, $user) ) return;

        $row = $this->createRow();
        $row->user_id = $user->id;
        $row->directory = $episode->getShow()->getDirectory();
        $row->number = $episode->getNumber();
        $row->time = time();
        $row->save();
    }

    public function unmarkAsViewed(Model_Episode $episode, Model_User $user)
    {
        $adapter = $this->getAdapter();
        $this->delete( $adapter->quoteInto('`user_id` = ?', $user->id) . $adapter->quoteInto(' AND `directory` = ?', $episode->getShow()->getDirectory()) . $adapter->quoteInto(' AND `number` = ?', $episode->getNumber()) );
    }
}
