<?php

class Model_Episode
{
    protected $_season;

    protected $_number;
    protected $_episode;
    protected $_title;
    protected $_date;
    protected $_isSpecial;
    protected $_isViewed;

    public function __construct($season, $episode)
    {
        $this->_season = $season;

        $this->_number = $episode->number;
        $this->_episode = $episode->episode;
        $this->_title = $episode->title;

        $date = DateTime::createFromFormat('d/M/y', $episode->airdate);
        if( $date === false ) $this->_date = null;
        else $this->_date = $date;

        $this->_isSpecial = $episode->special !== 'n';
    }

    public function getSeason()
    {
        return $this->_season;
    }
    
    public function getShow()
    {
        return $this->getSeason()->getShow();
    }

    public function getNumber()
    {
        return $this->_number;
    }

    public function getSeasonNumber()
    {
        return $this->getSeason()->getSeasonNumber();
    }

    public function getEpisodeNumber()
    {
        return $this->_episode;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function getAirdate()
    {
        return $this->_date;
    }

    public function isAired()
    {
        if( !$this->getAirdate() instanceof DateTime ) return false;
        return $this->getAirdate()->format('Y-m-d') < date('Y-m-d');
    }

    public function isToday()
    {
        $date = $this->getAirdate();
        if( !$date instanceof DateTime ) return false;

        return $this->getAirdate()->format('Y-m-d') == date('Y-m-d');
    }

    public function isSpecial()
    {
        return $this->_isSpecial;
    }

    public function daysUntilAirdate()
    {
        $date = $this->getAirdate();
        if( !$date instanceof DateTime ) return null;

        return floor( ($date->getTimestamp()-time()) / 60 / 60 / 24 ) +1;
    }
    
    public function setViewed($bool)
    {
        $this->_isViewed = $bool;
    }

    public function isViewed(Model_User $user)
    {
        return $this->_isViewed;
        //$viewTable = new Model_DbTable_Views();
        //return $viewTable->isViewed($this, $user);
    }

    public function markAsViewed(Model_User $user)
    {
        if( !$this->isAired() ) return;
        $viewTable = new Model_DbTable_Views();
        $viewTable->markAsViewed($this, $user);
    }

    public function unmarkAsViewed(Model_User $user)
    {
        $viewTable = new Model_DbTable_Views();
        $viewTable->unmarkAsViewed($this, $user);
    }
}

