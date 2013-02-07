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

    protected function _initTranslations()
    {
        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return;

        $user = $auth->user;
        $locale = $user->language;

        if( $locale == 'en-us' ) return;

        // Setup translations
        if( is_file(APPLICATION_PATH.'/translations/'.$locale.'.csv') ) {
            $translate = new Zend_Translate(array(
                'adapter' => 'csv',
                'content' => APPLICATION_PATH . '/translations/' . $locale . '.csv',
                'locale'  => $locale,
            ));
            Zend_Registry::set('Zend_Translate', $translate);
        }
    }
    /*protected function _initCookies()
    {
        $config = new Zend_Config($this->getOptions());
        Zend_Session::rememberMe($config->portal->login->expire);
    }*/
}

