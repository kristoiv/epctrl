<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initRoutes()
    {
        $config = new Zend_Config($this->getOptions());
        $router = new Zend_Controller_Router_Rewrite();
        $router->addConfig($config, 'routes');

        $front     = Zend_Controller_Front::getInstance();
        $front->setRouter($router);
    }
    /*protected function _initCookies()
    {
        $config = new Zend_Config($this->getOptions());
        Zend_Session::rememberMe($config->portal->login->expire);
    }*/
}

