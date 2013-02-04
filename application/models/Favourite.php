<?php

class Model_Favourite extends Zend_Db_Table_Row_Abstract
{
    public function getShow(Model_Index $index)
    {
        return $index->getShow($this->directory);
    }
}

