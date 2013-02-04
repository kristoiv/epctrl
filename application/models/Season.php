<?php

class Model_Season
{
    protected $_episodes;

    protected $_show;
    protected $_seasonNumber;

    public function __construct($show, $seasonNumber)
    {
        $this->_episodes = new Zend_Config(array(), true);

        $this->_show = $show;
        $this->_seasonNumber = $seasonNumber;
    }

    public function getShow()
    {
        return $this->_show;
    }

    public function getSeasonNumber()
    {
        return $this->_seasonNumber;
    }

    public function addEpisode($episode)
    {
        $episodeNumber = $episode->getEpisodeNumber();
        $this->_episodes->$episodeNumber = $episode;
    }

    public function getEpisode($episodeNumber)
    {
        if( !$this->hasEpisode($episodeNumber) ) throw new Exception('There is no such episode in this season');
        return $this->_episodes->$episodeNumber;
    }

    public function hasEpisode($episodeNumber)
    {
        return isset($this->_episodes->$episodeNumber);
    }

    public function getEpisodeByNumber($number)
    {
        foreach( $this->getEpisodes() as $episode ) if( $episode->getNumber() == $number ) return $episode;
        throw new Exception('There is no episode with that number in this season');
    }

    public function hasEpisodeByNumber($number)
    {
        foreach( $this->getEpisodes() as $episode ) if( $episode->getNumber() == $number ) return true;
        return false;
    }

    public function getEpisodes()
    {
        return $this->_episodes;
    }

    public function getEpisodesReversed()
    {
        $reversed = array();
        foreach( $this->getEpisodes() as $episodeNumber => $episode ) $reversed[$episodeNumber] = $episode;
        return new Zend_Config(array_reverse($reversed), true);
    }

    public function markSeasonAsViewed(Model_User $user)
    {
        foreach( $this->getEpisodes() as $episode ) $episode->markAsViewed($user);
    }

    public function unmarkSeasonAsViewed(Model_User $user)
    {
        foreach( $this->getEpisodes() as $episode ) $episode->unmarkAsViewed($user);
    }

    public function isViewed(Model_User $user)
    {
        foreach( $this->getEpisodes() as $episode ) if( !$episode->isViewed($user) && $episode->isAired() ) return false;
        return true;
    }
}

