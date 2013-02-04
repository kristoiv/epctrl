<?php

class Model_DbTable_Favourites extends Zend_Db_Table_Abstract
{
    protected $_name = 'Favourite';
    protected $_primary = array('user_id', 'directory');
    protected $_rowClass = 'Model_Favourite';

    public function getFavourites(Model_User $user, Model_Index $index)
    {
        $shows = array();
        $rowset = $this->fetchAll( $this->select()->where('`user_id` = ?', $user->id)->order('directory ASC') );
        foreach( $rowset as $row ) $shows[] = $row->getShow($index);
        return $shows;
    }
}

