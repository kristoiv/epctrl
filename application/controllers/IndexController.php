<?php

class IndexController extends Zend_Controller_Action
{
    protected $_config;
    protected $_cache;
    protected $_index;
    protected $_user;

    public function init()
    {
        // Setup configuration and cache
        $this->_config = $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        $this->_cache  = $cache  = Zend_Cache::factory('Core', 'File', $config->portal->cache->frontend->toArray(), $config->portal->cache->backend->toArray());

        $this->view->title = $config->portal->title;
        $this->view->copyright = $config->portal->copyright;

        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return $this->_forward('signin');

        $user = $this->_user = $this->view->user = $auth->user;

        // Load tv-series index from (and possibly into) cache.
        $index = $this->_index = new Model_Index($user, $config, $cache);
    }

    public function signinAction()
    {
        $config = $this->_config;

        if( $this->getRequest()->isPost() ) {

            $type = $this->_getParam('type', '');

            if( $type == 'signin' ) {

                $email = $this->_getParam('email', '');
                $password = $this->_getParam('password', '');

                $userTable = new Model_DbTable_Users();

                if( !($user = $userTable->login($email, $password)) ) {
                    $this->view->signinError = true;
                    $this->view->humanReadableError = $this->view->translate('Invalid credentials, please try again.');
                    return;
                }

                $auth = new Zend_Session_Namespace('Auth');
                $auth->setExpirationSeconds($config->portal->login->expire);
                $auth->isAuthenticated = true;
                $auth->user = $user;

                return $this->_redirect( $this->getRequest()->getServer('HTTP_REFERER') );

            }else if( $type == 'signup' ) {

                $email = $this->view->signupEmail = $this->_getParam('signupEmail', '');
                $password = $this->_getParam('signupPassword', '');
                $passwordAgain = $this->_getParam('signupPasswordAgain', '');

                if( $password != $passwordAgain ) {
                    $this->view->signupError = true;
                    $this->view->humanReadableError = $this->view->translate('Passwords didn\'t match, try again.');
                    return;
                }

                $validator = new Zend_Validate_EmailAddress();
                if( !$validator->isValid($email) ) {
                    $this->view->signupError = true;
                    $this->view->humanReadableError = $this->view->translate('Invalid email address, please try again.');
                    return;
                }

                $userTable = new Model_DbTable_Users();
                $user = $userTable->fetchRow( $userTable->getAdapter()->quoteInto('email = ?', $email) );
                if( $user ) {
                    $this->view->signupError = true;
                    $this->view->humanReadableError = $this->view->translate('The email address is already in use on the service.');
                    return;
                }

                $user = $userTable->signup($email, $password);

                // Log the login
                $loginTable = new Zend_Db_Table('Login');
                $loginTable->insert(array(
                    'user_id'   => $user->id,
                    'time'      => time(),
                    'ip'        => $_SERVER['REMOTE_ADDR'],
                ));

                $auth = new Zend_Session_Namespace('Auth');
                $auth->setExpirationSeconds($config->portal->login->expire);
                $auth->isAuthenticated = true;
                $auth->user = $user;

                return $this->_redirect('/');

            }else throw new Exception('Invalid post parameters');

        }
    }

    public function signoutAction()
    {
        Zend_Session::namespaceUnset('Auth');
        return $this->_redirect('/');
    }

    public function indexAction()
    {
        $user = $this->_user;

        // Get favourites
        $favouriteTable = new Model_DbTable_Favourites();
        $favourites = $this->view->favourites = $favouriteTable->getFavourites($user, $this->_index);

        $availableEpisodes = array();
        foreach( $favourites as $favourite ) {
            $episode = $favourite->getNextAvailableEpisode($user);
            if( $episode != null ) $availableEpisodes[$favourite->getDirectory()] = $episode;
        }
        $this->view->availableEpisodes = $availableEpisodes;
    }
}

