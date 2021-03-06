<?php

class Model_DbTable_Favourites extends Zend_Db_Table_Abstract
{
    protected $_name = 'Favourite';
    protected $_primary = array('user_id', 'directory');
    protected $_rowClass = 'Model_Favourite';

    public function addFavourite(Model_User $user, Model_Show $show)
    {
        $row = $this->createRow();
        $row->user_id = $user->id;
        $row->directory = $show->getDirectory();
        $row->save();
    }

    public function removeFavourite(Model_User $user, Model_Show $show)
    {
        $this->delete( $this->getAdapter()->quoteInto('user_id = ?', $user->id) . $this->getAdapter()->quoteInto(' AND directory = ?', $show->getDirectory()) );
    }

    public function getFavourites(Model_User $user, Model_Index $index)
    {
        $shows = array();
        $rowset = $this->fetchAll( $this->select()->where('`user_id` = ?', $user->id)->order('directory ASC') );
        foreach( $rowset as $row ) $shows[] = $row->getShow($index);
        return $shows;
    }

    public function isFavourited(Model_User $user, $directory)
    {
        $row = $this->fetchRow( $this->getAdapter()->quoteInto('`user_id` = ?', $user->id) . $this->getAdapter()->quoteInto(' AND `directory` = ?', $directory) );
        return $row != false;
    }
}

