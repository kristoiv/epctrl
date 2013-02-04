<?php

class ActionController extends Zend_Controller_Action
{
    protected $_config;
    protected $_cache;
    protected $_index;
    protected $_user;
    protected $_redirector;

    public function preDispatch()
    {
        // Setup configuration and cache
        $this->_config = $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $this->_cache  = $cache  = Zend_Cache::factory('Core', 'File', $config->portal->cache->frontend->toArray(), $config->portal->cache->backend->toArray());

        $this->view->title = $config->portal->title;

        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return $this->_forward('signin', 'index');

        $user = $this->_user = $this->view->user = $auth->user;

        // Load tv-series index from (and possibly into) cache.
        $index = $this->_index = new Model_Index($user, $config, $cache);

        $this->_redirector = $this->_helper->getHelper('Redirector');
    }

    public function favouriteAction()
    {
        $directory = $this->_getParam('directory', '');
        if( !$this->_index->hasShow($directory) ) throw new Exception('Directory not found in index');

        $this->_index->getShow($directory)->favourite();

        return $this->_redirector->gotoRoute(array('directory' => $directory), 'show');
    }

    public function unfavouriteAction()
    {
        $directory = $this->_getParam('directory', false);
        if( !$this->_index->hasShow($directory) ) throw new Exception('Directory not found in index');

        $this->_index->getShow($directory)->unfavourite();

        return $this->_redirector->gotoRoute(array('directory' => $directory), 'show');
    }

    public function markallasviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( !$this->_index->hasShow($directory) ) throw new Exception('Directory not found in index');

        $this->_index->getShow($directory)->markShowAsViewed();

        return $this->_redirector->gotoRoute(array('directory' => $directory), 'show');
    }

    public function unmarkallasviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( !$this->_index->hasShow($directory) ) throw new Exception('Directory not found in index');

        $this->_index->getShow($directory)->unmarkShowAsViewed();

        return $this->_redirector->gotoRoute(array('directory' => $directory), 'show');
    }

    public function markrangeasviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( !$this->_index->hasShow($directory) ) throw new Exception('Directory not found in index');

        $from = $this->_getParam('from', 0);
        $to = $this->_getParam('to', 0);

        $this->_index->getShow($directory)->markRangeAsViewed($from, $to);

        return $this->_redirector->gotoRoute(array('directory' => $directory), 'show');
    }
}

