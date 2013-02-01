<?php

require_once(APPLICATION_PATH . '/../library/Phpass/PasswordHash.php');

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

        // Load tv-series index from (and possibly into) cache.
        $index = $this->_index = $this->view->index = $this->_getIndex();

        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return $this->_forward('signin');

        $user = $this->_user = $this->view->user = $auth->user;
    }

    public function signinAction()
    {
        $config = $this->_config;
        $this->view->title = $config->portal->title;

        if( $this->getRequest()->isPost() ) {

            $type = $this->_getParam('type', '');

            if( $type == 'signin' ) {

                $email = $this->_getParam('email', '');
                $password = $this->_getParam('password', '');

                $userTable = new Zend_Db_Table('User');
                $user = $userTable->fetchRow( $userTable->getAdapter()->quoteInto('email = ?', $email) );

                $passwordHash = new PasswordHash(8, false);

                if( !$user || !$passwordHash->CheckPassword($password, $user->password) ) {
                    $this->view->signinError = true;
                    $this->view->humanReadableError = $this->view->translate('Invalid credentials, please try again.');
                    return;
                }

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

            }else if( $type == 'signup' ) {

                $email = $this->view->signupEmail = $this->_getParam('signupEmail', '');
                $password = $this->_getParam('signupPassword', '');
                $passwordAgain = $this->_getParam('signupPasswordAgain', '');

                if( $password != $passwordAgain ) {
                    $this->view->signupError = true;
                    $this->view->humanReadableError = $this->view->translate('Passwords didn\'t match, try again.');
                    return;
                }

                $userTable = new Zend_Db_Table('User');
                $user = $userTable->fetchRow( $userTable->getAdapter()->quoteInto('email = ?', $email) );

                $validator = new Zend_Validate_EmailAddress();
                if( !$validator->isValid($email) ) {
                    $this->view->signupError = true;
                    $this->view->humanReadableError = $this->view->translate('Invalid email address, please try again.');
                    return;
                }else if( $user ) {
                    $this->view->signupError = true;
                    $this->view->humanReadableError = $this->view->translate('The email address is already in use on the service.');
                    return;
                }

                $passwordHash = new PasswordHash($config->portal->password->iteration_count, $config->portal->password->portable);

                $user_id = $userTable->insert(array(
                    'email' => $email,
                    'password' => $passwordHash->HashPassword($password),
                ));
                $user = $userTable->fetchRow( $userTable->getAdapter()->quoteInto('`id` = ?', $user_id) );

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
        // Get favourites
        $favouriteTable = new Zend_Db_Table('Favourite');
        $favourites = $this->view->favourites = $favouriteTable->fetchAll( $favouriteTable->select()->where('user_id = ?', $this->_user->id)->order('directory ASC') );

        if( $this->getRequest()->isPost() ) {

            $type = $this->_getParam('type', '');

            if( $type == 'feedback' ) {

                $subject = trim($this->_getParam('subject', ''));
                $message = trim($this->_getParam('message', ''));

                if( $subject == '' && $message == '' ) {
                    $this->view->feedbackError = true;
                    $this->view->humanReadableError = $this->view->translate('Please write a message before you try to send it.');
                    return;
                }

                $to = Zend_Mail::getDefaultFrom();

                $mail = new Zend_Mail('UTF-8');
                $mail->setFrom('kristoffer.a.iversen@gmail.com', 'EpCtrl.com');
                $mail->addTo($to['email'], $to['name']);
                $mail->setReplyTo($this->_user->email);
                $mail->setSubject('EpCtrl.com – ' . $subject);
                $mail->setBodyText($message);
                $mail->send();

                return $this->_redirect('/');

            }else throw new Exception('Invalid post type');
        }
    }

    public function showAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $show     = $this->view->show     = new Zend_Config($this->_index[$directory]);
        $episodes = $this->view->episodes = new Zend_Config($this->_getEpisodes($directory));

        // Get favourites
        $favouriteTable = new Zend_Db_Table('Favourite');
        $favourites = $this->view->favourites = $favouriteTable->fetchAll( $favouriteTable->select()->where('user_id = ?', $this->_user->id)->order('directory ASC') );
        $this->view->favourited = false;
        foreach( $favourites as $favourite ) if( $favourite->directory == $show->directory ) {
            $this->view->favourited = true;
            break;
        }

        // Get list of viewed episodes
        $viewTable = new Zend_Db_Table('View');
        $rowset = $viewTable->fetchAll( $viewTable->select()->from('View', 'number')->where('user_id = ?', $this->_user->id)->where('directory = ?', $directory) );
        $viewed = array();
        foreach( $rowset as $view ) $viewed[] = $view['number'];
        $this->view->viewed = $viewed;

        if( $this->getRequest()->isPost() ) {

            $type = $this->_getParam('type', '');

            if( $type == 'feedback' ) {

                $subject = trim($this->_getParam('subject', ''));
                $message = trim($this->_getParam('message', ''));

                if( $subject == '' && $message == '' ) {
                    $this->view->feedbackError = true;
                    $this->view->humanReadableError = $this->view->translate('Please write a message before you try to send it.');
                    return;
                }

                $to = Zend_Mail::getDefaultFrom();

                $mail = new Zend_Mail('UTF-8');
                $mail->setFrom('kristoffer.a.iversen@gmail.com', 'EpCtrl.com');
                $mail->addTo($to['email'], $to['name']);
                $mail->setReplyTo($this->_user->email);
                $mail->setSubject('EpCtrl.com – ' . $subject);
                $mail->setBodyText($message);
                $mail->send();

                return $this->_redirect('/');

            }else throw new Exception('Invalid post type');
        }
    }

    public function favouriteAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $favouriteTable = new Zend_Db_Table('Favourite');
        $favouriteTable->insert(array(
            'user_id' => $this->_user->id,
            'directory' => $directory,
        ));

        return $this->_redirect('/index/show/directory/' . $directory);
    }

    public function unfavouriteAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $favouriteTable = new Zend_Db_Table('Favourite');
        $favouriteTable->delete( $favouriteTable->getAdapter()->quoteInto('user_id = ?', $this->_user->id) . $favouriteTable->getAdapter()->quoteInto(' AND directory = ?', $directory) );

        return $this->_redirect('/index/show/directory/' . $directory);
    }

    public function markviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $number = $this->_getParam('number', false);
        if( $number === false || !is_numeric($number) ) throw new Exception('Missing or invalid parameter for number');

        $viewTable = new Zend_Db_Table('View');
        $viewTable->insert(array(
            'user_id' => $this->_user->id,
            'directory' => $directory,
            'number' => $number,
            'time' => time(),
        ));

        return $this->_redirect('/index/show/directory/' . $directory);
    }

    public function unmarkviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $number = $this->_getParam('number', false);
        if( $number === false || !is_numeric($number) ) throw new Exception('Missing or invalid parameter for number');

        $viewTable = new Zend_Db_Table('View');
        $viewTable->delete( $viewTable->getAdapter()->quoteInto('user_id = ?', $this->_user->id) . $viewTable->getAdapter()->quoteInto(' AND directory = ?', $directory) . $viewTable->getAdapter()->quoteInto(' AND number = ?', $number) );

        return $this->_redirect('/index/show/directory/' . $directory);
    }

    public function markallviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $episodes = new Zend_Config($this->_getEpisodes($directory));

        // Get list of viewed episodes
        $viewTable = new Zend_Db_Table('View');
        $rowset = $viewTable->fetchAll( $viewTable->select()->from('View', 'number')->where('user_id = ?', $this->_user->id)->where('directory = ?', $directory) );
        $viewed = array();
        foreach( $rowset as $view ) $viewed[] = $view['number'];

        $viewAdapter = $viewTable->getAdapter();

        $viewAdapter->beginTransaction();

        try {

            foreach( $episodes as $episode ) {

                if( !$episode->aired || $episode->today || in_array($episode->number, $viewed) ) continue;

                $viewTable->insert(array(
                    'user_id' => $this->_user->id,
                    'directory' => $directory,
                    'number' => $episode->number,
                    'time' => time(),
                ));

            }
            
            $viewAdapter->commit();

        }catch( Exception $e ) {
            $viewAdapter->rollBack();
        }

        return $this->_redirect('/index/show/directory/' . $directory);
    }

    public function unmarkallviewedAction()
    {
        $directory = $this->_getParam('directory', false);
        if( $directory === false || !isset($this->_index[$directory]) ) throw new Exception('Directory not found in index');

        $viewTable = new Zend_Db_Table('View');
        $viewTable->delete( $viewTable->getAdapter()->quoteInto('user_id = ?', $this->_user->id) . $viewTable->getAdapter()->quoteInto(' AND directory = ?', $directory) );

        return $this->_redirect('/index/show/directory/' . $directory);
    }

    public function ajaxupdatesettingsAction()
    {
        header('Content-type: application/json');

        $config = $this->_config;

        $language = strtolower($this->_getParam('language', 'en-us'));
        $password = trim($this->_getParam('password', ''));
        $passwordAgain = trim($this->_getParam('passwordAgain', ''));

        if( $password != '' && $password != $passwordAgain ) die(json_encode(array('error' => true, 'exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Passwords did not match, please try again.'))));

        $languages = array();
        foreach( scandir(APPLICATION_PATH . '/translations/') as $lang ) {
            if( !is_file(APPLICATION_PATH . '/translations/' . $lang) ) continue;
            $languages[] = strtok($lang, '.');
        }

        if( !in_array($language, $languages) ) die(json_encode(array('error' => true, 'exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('No such language.'))));

        $user = $this->_user;

        // Reconnect offline row
        $user->setTable(new Zend_Db_Table('User'));

        $user->language = $language;
        
        if( $password != '' ) {
            $passwordHash = new PasswordHash($config->portal->password->iteration_count, $config->portal->password->portable);
            $user->password = $passwordHash->HashPassword($password);
        }

        $user->save();

        die(json_encode(array('success' => true, 'exclaim' => $this->view->translate('Success'), 'humanReadable' => $this->view->translate('Account updated.'))));
    }

    public function ajaxremoveaccountAction()
    {
        header('Content-type: application/json');

        $user = $this->_user;

        $config = $this->_config;
        $password = trim($this->_getParam('password', ''));

        $passwordHash = new PasswordHash($config->portal->password->iteration_count, $config->portal->password->portable);
        if( !$passwordHash->CheckPassword($password, $user->password) ) die(json_encode(array('error' => true, 'exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Wrong password, couldn\'t remove user.'))));

        $userTable = new Zend_Db_Table('User');
        $adapter = $userTable->getAdapter();

        $adapter->beginTransaction();

        try {

            // Reconnect offline row
            $user->setTable($userTable);

            // Remove all the users favourites
            $favouriteTable = new Zend_Db_Table('Favourite');
            $favouriteTable->delete( $adapter->quoteInto('user_id = ?', $user->id) );

            // Remove all the logged logins
            $loginTable = new Zend_Db_Table('Login');
            $loginTable->delete( $adapter->quoteInto('user_id = ?', $user->id) );

            // Remove all the episodes marked as viewed by user
            $viewTable = new Zend_Db_Table('View');
            $viewTable->delete( $adapter->quoteInto('user_id = ?', $user->id) );

            // Remove user
            $user->delete();

            // Commit changes to database and end transaction
            $adapter->commit();

        }catch( Exception $e ) {
            // Something went wrong, abort and display error.
            $adapter->rollBack();
            die(json_encode(array('error' => true, 'exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Something went wrong, try again later.'))));
        }

        Zend_Session::namespaceUnset('Auth');

        die(json_encode(array('success' => true, 'exclaim' => $this->view->translate('Success'), 'humanReadable' => $this->view->translate('Account successfully removed.'))));
    }

    public function queryAction()
    {
        $query = $this->_getParam('q', '');
        $results = array();

        $keys = array_keys($this->_index);
        
        foreach( $keys as $show ) {
            if( stripos($show, $query) !== false ) {
                $results[] = $show;
            }
        }

        // We do the following because a search like "House" has so many results that you wouldn't find it
        // even if you wrote out the complete name. We do strtolower so that we get a case-insensitive
        // array_search.
        if( ($key = array_search(strtolower($query), array_map('strtolower', $results))) !== false ) {
            $item = $results[$key];
            unset($results[$key]);
            $results = array_merge(array($item), $results);
        }

        // Set content-type to json so that jquery accepts it.
        header('Content-type: application/json');

        die(json_encode($results));
    }

    protected function _getIndex()
    {
        $config = $this->_config;
        $cache = $this->_cache;

        if( ($index = $cache->load('index')) === false ) {
            
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

            $cache->save($index, 'index');

        }

        return $index;
    }

    protected function _getEpisodes($directory)
    {
        if( !isset($this->_index[$directory]) ) throw new Exception('No such series found in the index');
        
        $config = $this->_config;
        $cache = $this->_cache;
        
        if( ($episodes = $cache->load($directory)) === false ) {
            
            $csv = file_get_contents($config->portal->epguides->episodes . $this->_index[$directory]['tvrage']);
            $csv = trim(substr($csv, strpos($csv, '<pre>')+5, strpos($csv, '</pre>')-5-strpos($csv, '<pre>')));

            $header = trim(substr($csv, 0, strpos($csv, "\n")));
            $csv = substr($csv, strpos($csv, "\n")+1);
            
            $keys = explode(',', $header);

            $episodes = array();

            foreach( explode(PHP_EOL, $csv) as $line ) {
                
                $array = str_getcsv($line, ',', '"', '\\');
                $episode = array();

                if( count($array) == count($keys) ) for($i = 0; $i < count($keys); $i++) $episode[$keys[$i]] = $array[$i];

                $date = DateTime::createFromFormat('d/M/y', $episode['airdate']);
                
                if( $date != null ) {
                    $episode['airdate'] = $date->format('Y-m-d') . ' 00:00:00';
                    $episode['aired'] = $date->format('Y-m-d') <= date('Y-m-d');
                    $episode['today'] = $date->format('Y-m-d') == date('Y-m-d');
                }


                $episode['special'] = $episode['special?'] != 'n';
                unset($episode['special?']);

                if( is_numeric($episode['number']) ) $episodes[$episode['number']] = $episode;

            }

            $cache->save($episodes, $directory);

        }

        return $episodes;
    }
}

