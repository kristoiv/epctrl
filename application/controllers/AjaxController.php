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

        $auth = new Zend_Session_Namespace('Auth');
        if( !$auth->isAuthenticated ) return $this->_respond(401);

        $user = $this->_user = $this->view->user = $auth->user;

        // Load tv-series index from (and possibly into) cache.
        $index = $this->_index = new Model_Index($user, $config, $cache);
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
        $episode = $index->getShow($directory)->getEpisodeByNumber($number);

        if( !$episode->isAired() ) return $this->_respond(400);

        $episode->markAsViewed($user);

        $data = array();
        $lastEpisode = false;

        $episode = $index->getShow($directory)->getNextAvailableEpisode($user);
        if( !is_null($episode) ) {
            $data = array(
                'showTitle' => $episode->getShow()->getTitle(),
                'episodeTitle' => $episode->getTitle(),
                'number' => $episode->getNumber(),
                'season' => $episode->getSeasonNumber(),
                'episode' => $episode->getEpisodeNumber(),
                'class' => ($episode->isToday() ? 'label-today' : ($episode->isAired() ? 'label-success' : 'label-inverse')),
                'label' => ($episode->isToday() ? $this->view->translate('Today') : ($episode->isAired() ? $this->view->translate('Available') : $episode->daysUntilAirdate() . ' ' . $this->view->translate('days left'))),
            );
        }else $lastEpisode = true;

        if( $lastEpisode ) return $this->_respond(204);
        return $this->_respond(200, $data);
    }

    public function unmarkepisodeviewedAction()
    {
        $index = $this->_index;
        $user = $this->_user;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getEpisodeByNumber($number)->unmarkAsViewed($user);

        $data = array();
        $lastEpisode = false;

        $episode = $index->getShow($directory)->getNextAvailableEpisode($user);
        if( !is_null($episode) ) {
            $data = array(
                'showTitle' => $episode->getShow()->getTitle(),
                'episodeTitle' => $episode->getTitle(),
                'number' => $episode->getNumber(),
                'season' => $episode->getSeasonNumber(),
                'episode' => $episode->getEpisodeNumber(),
                'class' => ($episode->isToday() ? 'label-today' : ($episode->isAired() ? 'label-success' : 'label-inverse')),
                'label' => ($episode->isToday() ? $this->view->translate('Today') : ($episode->isAired() ? $this->view->translate('Available') : $episode->daysUntilAirdate() . ' ' . $this->view->translate('days left'))),
            );
        }else $lastEpisode = true;

        if( $lastEpisode ) return $this->_respond(204);
        return $this->_respond(200, $data);
    }

    public function markseasonviewedAction()
    {
        $user = $this->_user;
        $index = $this->_index;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getSeason($number)->markSeasonAsViewed();

        $data = array();
        $lastEpisode = false;

        $episode = $index->getShow($directory)->getNextAvailableEpisode($user);
        if( !is_null($episode) ) {
            $data = array(
                'showTitle' => $episode->getShow()->getTitle(),
                'episodeTitle' => $episode->getTitle(),
                'number' => $episode->getNumber(),
                'season' => $episode->getSeasonNumber(),
                'episode' => $episode->getEpisodeNumber(),
                'class' => ($episode->isToday() ? 'label-today' : ($episode->isAired() ? 'label-success' : 'label-inverse')),
                'label' => ($episode->isToday() ? $this->view->translate('Today') : ($episode->isAired() ? $this->view->translate('Available') : $episode->daysUntilAirdate() . ' ' . $this->view->translate('days left'))),
            );
        }else $lastEpisode = true;

        if( $lastEpisode ) return $this->_respond(204);
        return $this->_respond(200, $data);
    }

    public function unmarkseasonviewedAction()
    {
        $user = $this->_user;
        $index = $this->_index;
        $directory = $this->_getParam('directory', '');
        $number = $this->_getParam('number', '');
        $index->getShow($directory)->getSeason($number)->unmarkSeasonAsViewed();

        $data = array();
        $lastEpisode = false;

        $episode = $index->getShow($directory)->getNextAvailableEpisode($user);
        if( !is_null($episode) ) {
            $data = array(
                'showTitle' => $episode->getShow()->getTitle(),
                'episodeTitle' => $episode->getTitle(),
                'number' => $episode->getNumber(),
                'season' => $episode->getSeasonNumber(),
                'episode' => $episode->getEpisodeNumber(),
                'class' => ($episode->isToday() ? 'label-today' : ($episode->isAired() ? 'label-success' : 'label-inverse')),
                'label' => ($episode->isToday() ? $this->view->translate('Today') : ($episode->isAired() ? $this->view->translate('Available') : $episode->daysUntilAirdate() . ' ' . $this->view->translate('days left'))),
            );
        }else $lastEpisode = true;

        if( $lastEpisode ) return $this->_respond(204);
        return $this->_respond(200, $data);
    }

    public function feedbackAction()
    {
        $subject = trim($this->_getParam('subject', ''));
        $message = trim($this->_getParam('message', ''));

        if( $subject == '' && $message == '' ) {
            return $this->_respond(400, array('exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Please write a message before you try to send it.')));
        }

        $to = Zend_Mail::getDefaultFrom();

        $mail = new Zend_Mail('UTF-8');
        $mail->setFrom('kristoffer.a.iversen@gmail.com', 'EpCtrl.com');
        $mail->addTo($to['email'], $to['name']);
        $mail->setReplyTo($this->_user->email);
        $mail->setSubject($this->_config->portal->title . ' – ' . $subject);
        $mail->setBodyText($message);
        $mail->send();

        return $this->_respond(200, array('exclaim' => $this->view->translate('Success'), 'humanReadable' => $this->view->translate('Thank you for you feedback')));
    }

    public function updatesettingsAction()
    {
        $language = trim($this->_getParam('language', 'en-us'));
        $password = $this->_getParam('password', '');
        $passwordAgain = $this->_getParam('passwordAgain', '');

        if( $password != '' && $password != $passwordAgain ) return $this->_respond(400, array('exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Passwords did not match, please try again.')));

        $languages = array();
        foreach( scandir(APPLICATION_PATH . '/translations/') as $lang ) {
            if( !is_file(APPLICATION_PATH . '/translations/' . $lang) ) continue;
            $languages[] = strtok($lang, '.');
        }
        if( !in_array($language, $languages) ) return $this->_respond(400, array('exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('No such language.')));

        $user = $this->_user;

        // Reconnect offline row
        $user->setTable(new Model_DbTable_Users());

        $user->language = $language;
        
        if( $password != '' ) {
            $user->setPassword($password);
        }

        $user->save();

        return $this->_respond(200, array('exclaim' => $this->view->translate('Success'), 'humanReadable' => $this->view->translate('Account has been updated.')));
    }

    public function removeaccountAction()
    {
        $user = $this->_user;

        $config = $this->_config;
        $password = trim($this->_getParam('password', ''));

        if( !$user->checkPassword($password) ) return $this->_respond(400, array('exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Wrong password, couldn\'t remove user.')));

        $userTable = new Model_DbTable_Users();
        $adapter = $userTable->getAdapter();

        $adapter->beginTransaction();

        try {

            // Reconnect offline row
            $user->setTable($userTable);

            // Remove all the users favourites
            $favouriteTable = new Model_DbTable_Favourites();
            $favouriteTable->delete( $adapter->quoteInto('user_id = ?', $user->id) );

            // Remove all the logged logins
            $loginTable = new Model_DbTable_Logins();
            $loginTable->delete( $adapter->quoteInto('user_id = ?', $user->id) );

            // Remove all the episodes marked as viewed by user
            $viewTable = new Model_DbTable_Views();
            $viewTable->delete( $adapter->quoteInto('user_id = ?', $user->id) );

            // Remove user
            $user->delete();

            // Commit changes to database and end transaction
            $adapter->commit();

        }catch( Exception $e ) {
            // Something went wrong, abort and display error.
            $adapter->rollBack();
            return $this->_respond(400, array('exclaim' => $this->view->translate('Failure'), 'humanReadable' => $this->view->translate('Something went wrong, try again later.')));
        }

        Zend_Session::namespaceUnset('Auth');

        return $this->_respond(200, array('exclaim' => $this->view->translate('Success'), 'humanReadable' => $this->view->translate('Account successfully removed.')));
    }

    protected function _respond($status, $parameters = array())
    {
        die(json_encode(array('status' => $status, 'parameters' => $parameters)));
    }
}

