<?php

class AjaxController extends Zend_Controller_Action
{
    protected $_config;
    protected $_cache;
    protected $_index;
    protected $_user;

    public function preDispatch()
    {
        header('Content-Type: application/json');

        // Setup configuration and cache
        $this->_config = $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $this->_cache  = $cache  = Zend_Cache::factory('Core', 'File', $config->portal->cache->frontend->toArray(), $config->portal->cache->backend->toArray());

        // Load tv-series index from (and possibly into) cache.
        $index = $this->_index = new Model_Index($config, $cache);

        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return $this->_respond('not_authenticated');

        $user = $this->_user = $this->view->user = $auth->user;
    }

    public function searchAction()
    {
        $index = $this->_index;
        $query = $this->_getParam('q', '');

        $shows = array();
        foreach( $index->search($query) as $show ) $shows[] = $show->getDirectory();

        return $this->_respond('success', $shows);
    }

    public function markepisodeviewedAction()
    {
        $index = $this->_index;
        $user = $this->_user;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getEpisodeByNumber($number)->markAsViewed($user);
        return $this->_respond(200);
    }

    public function unmarkepisodeviewedAction()
    {
        $index = $this->_index;
        $user = $this->_user;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getEpisodeByNumber($number)->unmarkAsViewed($user);
        return $this->_respond(200);
    }

    public function markseasonviewedAction()
    {
        $index = $this->_index;
        $user = $this->_user;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getSeason($number)->markSeasonAsViewed($user);
        return $this->_respond(200);
    }

    public function unmarkseasonviewedAction()
    {
        $index = $this->_index;
        $user = $this->_user;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getSeason($number)->unmarkSeasonAsViewed($user);
        return $this->_respond(200);
    }

    protected function _respond($status, $parameters = array())
    {
        die(json_encode(array('status' => $status, 'parameters' => $parameters)));
    }
}

