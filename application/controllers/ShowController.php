<?php

class ShowController extends Zend_Controller_Action
{
    protected $_config;
    protected $_cache;
    protected $_index;
    protected $_user;

    public function preDispatch()
    {
        // Setup configuration and cache
        $this->_config = $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $this->_cache  = $cache  = Zend_Cache::factory('Core', 'File', $config->portal->cache->frontend->toArray(), $config->portal->cache->backend->toArray());

        $this->view->title = $config->portal->title;

        // Load tv-series index from (and possibly into) cache.
        $index = $this->_index = new Model_Index($config, $cache);

        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return $this->_forward('signin', 'index');

        $user = $this->_user = $this->view->user = $auth->user;
    }

    public function indexAction()
    {
        $directory = $this->_getParam('directory', '');
        $user = $this->_user;

        $show = $this->view->show = $this->_index->getShow($directory);
        $next = $this->view->next = $show->getNextAvailableEpisode($user);

        $favouriteTable = new Model_DbTable_Favourites();
        $favourites = $this->view->favourites = $favouriteTable->getFavourites($user, $this->_index);
        $this->view->favourited = in_array($directory, $favourites);
    }
}
