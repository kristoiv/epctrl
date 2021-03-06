<?php

class Model_Show
{
    protected $_seasons;

    protected $_user;
    protected $_config;
    protected $_cache;

    protected $_tvrage;
    protected $_directory;
    protected $_title;

    protected $_isPrepared;

    public function __construct($directory, $details, Model_User $user, $config, $cache)
    {
        $this->_seasons = new Zend_Config(array(), true);

        $this->_user = $user;
        $this->_config = $config;
        $this->_cache = $cache;

        $this->_directory = $directory;

        $this->_tvrage = $details->tvrage;
        $this->_title = $details->title;

        $this->_isPrepared = false;
    }

    public function getTvrage()
    {
        return $this->_tvrage;
    }

    public function getDirectory()
    {
        return $this->_directory;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function getSeason($number)
    {
        // make sure show is loaded
        $this->_prepare();

        if( !$this->hasSeason($number) ) throw new Exception('There is no such season');

        return $this->_seasons->$number;
    }

    public function hasSeason($number)
    {
        // make sure show is loaded
        $this->_prepare();

        return isset($this->_seasons->$number);
    }

    public function getSeasons()
    {
        // make sure show is loaded
        $this->_prepare();

        return $this->_seasons;
    }

    public function getSeasonsReversed()
    {
        // make sure show is loaded
        $this->_prepare();

        $reversed = array();
        foreach( $this->getSeasons() as $number => $season ) $reversed[$number] = $season;
        return new Zend_Config(array_reverse($reversed), true);
    }

    public function getEpisodeByNumber($number)
    {
        $episode = null;

        foreach( $this->getSeasons() as $season ) {
            try {
                $episode = $season->getEpisodeByNumber($number);
                return $episode;
            }catch( Exception $e ) {
            }
        }

        throw new Exception('No such episode in this show');
    }

    public function favourite()
    {
        $favouriteTable = new Model_DbTable_Favourites();
        $favouriteTable->addFavourite($this->_user, $this);
    }

    public function unfavourite()
    {
        $favouriteTable = new Model_DbTable_Favourites();
        $favouriteTable->removeFavourite($this->_user, $this);
    }
    
    public function markShowAsViewed()
    {
        // Make sure show is loaded
        $this->_prepare();

        foreach( $this->getSeasons() as $season ) $season->markSeasonAsViewed();
    }
    
    public function unmarkShowAsViewed()
    {
        // Make sure show is loaded
        $this->_prepare();

        foreach( $this->getSeasons() as $season ) $season->unmarkSeasonAsViewed();
    }

    public function markRangeAsViewed($fromNumber, $toNumber)
    {
        // Make sure show is loaded
        $this->_prepare();

        $range = range($fromNumber, $toNumber);
        foreach( $this->getSeasons() as $season )
            foreach( $season->getEpisodes() as $episode )
                if( in_array($episode->getNumber(), $range) ) {
                    $episode->markAsViewed($this->_user);
                }
    }

    public function getNextAvailableEpisode(Model_User $user)
    {
        $directory = $this->getDirectory();

        // Get list of viewed episodes
        $viewTable = new Model_DbTable_Views();
        $rowset = $viewTable->fetchAll( $viewTable->select()->from('View', 'number')->where('user_id = ?', $user->id)->where('directory = ?', $directory) );
        $viewed = array();
        foreach( $rowset as $view ) $viewed[] = $view['number'];

        $potensialNext = array();
        foreach( $this->getSeasons() as $season ) {
            foreach( $season->getEpisodes() as $episode ) if( !in_array($episode->getNumber(), $viewed) ) {
                if( !is_null($episode->isAired()) && $episode->isAired() ) return $episode;
                $potensialNext[] = $episode;
            }
        }

        if( count($potensialNext) != 0 ) {
            $episode = null;
            foreach( $potensialNext as $ep ) if( $episode == null || (!is_null($ep->daysUntilAirdate()) && is_null($episode->daysUntilAirdate())) || (!is_null($episode->daysUntilAirdate()) && !is_null($ep->daysUntilAirdate())) && ($episode->daysUntilAirdate() > $ep->daysUntilAirdate()) ) $episode = $ep;
            return $episode;
        }

        return null;
    }

    protected function _prepare()
    {
        // Only prepare once per request
        if( $this->_isPrepared ) return;

        $config = $this->_config;
        $cache = $this->_cache;
        $directory = $this->getDirectory();

        if( ($seasons = $cache->load('show_' . $directory)) === false ) {
            
            $csv = file_get_contents($config->portal->epguides->episodes . $this->getTvrage());
            $csv = trim(substr($csv, strpos($csv, '<pre>')+5, strpos($csv, '</pre>')-5-strpos($csv, '<pre>')));

            $header = trim(substr($csv, 0, strpos($csv, "\n")));
            $csv = substr($csv, strpos($csv, "\n")+1);
            
            $keys = explode(',', $header);

            $episodes = array();

            foreach( explode(PHP_EOL, $csv) as $line ) {
                
                $array = str_getcsv($line, ',', '"', '\\');
                $episode = array();

                if( count($array) == count($keys) ) for($i = 0; $i < count($keys); $i++) $episode[$keys[$i]] = $array[$i];

                $episode['special'] = $episode['special?'];
                unset($episode['special?']);

                if( is_numeric($episode['number']) ) $episodes[$episode['number']] = $episode;

            }

            $seasons = new Zend_Config(array(), true);
            foreach($episodes as $episode ) {
                if( !isset($seasons->$episode['season']) ) $seasons->$episode['season'] = new Model_Season($this, $episode['season']);
                $seasons->$episode['season']->addEpisode(new Model_Episode($seasons->$episode['season'], new Zend_Config($episode, true)));
            }

            $cache->save($seasons, 'show_' . $directory);

        }

        $viewTable = new Model_DbTable_Views();
        $views = $viewTable->getShowViews($this->_user, $this);

        foreach( $seasons as $season ) {
            foreach( $season->getEpisodes() as $episode ) {
                $episode->setViewed( in_Array($episode->getNumber(), $views) );
            }
            $season->setUser($this->_user);
        }

        $this->_seasons = $seasons;
        $this->_isPrepared = true;
    }
}

