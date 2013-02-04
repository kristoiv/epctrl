<?php

class Model_Index
{
    protected $_config;
    protected $_cache;
    protected $_shows;

    public function __construct(Model_User $user, $config, $cache)
    {
        $this->_config = $config;
        $this->_cache = $cache;

        if( ($index = $cache->load('sys_index')) === false ) {

            $csv = trim(file_get_contents($config->portal->epguides->allshows));

            $header = trim(substr($csv, 0, strpos($csv, PHP_EOL)));
            $csv = substr($csv, strpos($csv, PHP_EOL)+1);
            
            $keys = explode(',', $header);

            $index = array();

            foreach( explode(PHP_EOL, $csv) as $line ) {
                
                $array = str_getcsv($line, ',', '"', '\\');
                $show = array();

                if( count($array) == count($keys) ) for($i = 0; $i < count($keys); $i++) $show[$keys[$i]] = $array[$i];

                $index[$show['directory']] = $show;

            }

            $cache->save($index, 'sys_index');

        }

        foreach( $index as $directory => $details ) $this->_shows[$directory] = new Model_Show($directory, new Zend_Config($details, true), $user, $config, $cache);
    }

    public function search($query)
    {
        $results = array();

        $expressions = explode(' ', $query);

        foreach( $this->getShows() as $show ) {

            $title = $show->getTitle();
            $directory = $show->getDirectory();

            $match = false;

            foreach( $expressions as $expression ) {
                $matchTitle = stripos($title, $expression) !== false;
                $matchDirectory = stripos($directory, $expression) !== false;
                $match = $matchTitle || $matchDirectory;
            }

            if( $match ) array_push($results, $show);

        }

        return $results;
    }

    public function getShow($directory)
    {
        if( !$this->hasShow($directory) ) throw new Exception('There is no such show in the index');
        return $this->_shows[$directory];
    }

    public function getShows()
    {
        return $this->_shows;
    }

    public function hasShow($directory)
    {
        return isset($this->_shows[$directory]);
    }
}

