<?php

class Model_Episode
{
    protected $_season;

    protected $_number;
    protected $_episode;
    protected $_title;
    protected $_date;
    protected $_isAired;
    protected $_isSpecial;

    public function __construct($season, $episode)
    {
        $this->_season = $season;

        $this->_number = $episode->number;
        $this->_episode = $episode->episode;
        $this->_title = $episode->title;

        $date = DateTime::createFromFormat('d/M/y', $episode->airdate);
        $this->_date = $date;

        if( $date ) $this->_isAired = $date->format('Y-m-d') < date('Y-m-d');
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
        return $this->_isAired;
    }

    public function isToday()
    {
        $date = $this->getAirdate();
        if( is_null($date) ) return false;

        return $this->getAirdate()->format('Y-m-d') == date('Y-m-d');
    }

    public function isSpecial()
    {
        return $this->_isSpecial;
    }

    public function daysUntilAirdate()
    {
        $date = $this->getAirdate();
        if( is_null($date) ) return null;

        return floor( ($date->getTimestamp()-time()) / 60 / 60 / 24 ) +1;
    }

    public function isViewed(Model_User $user)
    {
        $viewTable = new Model_DbTable_Views();
        return $viewTable->isViewed($this, $user);
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

