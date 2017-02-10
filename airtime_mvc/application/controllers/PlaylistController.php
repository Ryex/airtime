<?php

use Airtime\MediaItem\PlaylistQuery;
use Airtime\MediaItem\Playlist;
use Airtime\MediaItem\PlaylistPeer;

class PlaylistController extends Zend_Controller_Action
{

    public function init()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext
        	->addActionContext('add-items', 'json')
            ->addActionContext('new', 'json')
            ->addActionContext('edit', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('close-playlist', 'json')
            ->addActionContext('save', 'json')
            ->addActionContext('shuffle', 'json')
            ->addActionContext('generate', 'json')
            ->addActionContext('clear', 'json')
            ->addActionContext('save-rules', 'json')
            ->initContext();


        $this->mediaService = new Application_Service_MediaService();
        $this->playlistService = new Application_Service_PlaylistService();
    }

    private function getPlaylist() {

    	return $this->mediaService->getSessionMediaObject();
    }

    private function createUpdateResponse($playlist)
    {
    	$obj = new Presentation_Playlist($playlist);

        $this->view->length = $obj->getLength();
        $this->view->contents = $obj->getContent();
        $this->view->modified = $obj->getLastModifiedEpoch();
        $this->view->html = $this->view->render('playlist/update.phtml');

        unset($this->view->contents);
    }

    private function createFullResponse($obj)
    {
    	if (isset($obj)) {
    		$this->view->obj = new Presentation_Playlist($obj);
    	}

    	$this->view->html = $this->view->render('playlist/playlist.phtml');
    	unset($this->view->obj);
    }

    public function newAction()
    {
    	$type = $this->_getParam('type');

    	$playlist = $this->playlistService->createPlaylist($type);
    	$playlist->save();

    	$this->mediaService->setSessionMediaObject($playlist);
    	$this->createFullResponse($playlist);
    }

    public function editAction()
    {
    	$id = $this->_getParam('id');

    	$playlist = PlaylistQuery::create()->findPK($id);

    	$this->mediaService->setSessionMediaObject($playlist);
    	$this->createFullResponse($playlist);
    }

    public function deleteAction()
    {
    	try {
    		$playlist = $this->getPlaylist();
    		$playlist->delete();
    		$this->mediaService->setSessionMediaObject(null);

    		$this->createFullResponse(null);
    	}
    	catch (Exception $e) {
    		$this->view->error = $e->getMessage();
    	}
    }

    public function clearAction()
    {
    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {
    		$playlist = $this->getPlaylist();
    		$playlist->clearContent($con);
    		$this->createUpdateResponse($playlist);

    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		$this->view->error = $e->getMessage();
    	}
    }

    public function generateAction()
    {
    	Logging::enablePropelLogging();

    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {

    		$playlist = $this->getPlaylist();
    		$playlist->clearContent($con);
    		$mediaIds = $playlist->generateContent($con);
    		$playlist->addMedia($con, $mediaIds);
    		$con->commit();

    		$this->createUpdateResponse($playlist);

    		Logging::disablePropelLogging();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::error($e->getFile().$e->getLine());
    		Logging::error($e->getMessage());
    		$this->view->error = $e->getMessage();
    	}
    }

    public function shuffleAction()
    {
    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {
    		$playlist = $this->getPlaylist();
    		$playlist->shuffleContent($con);
    		$this->createUpdateResponse($playlist);

    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		$this->view->error = $e->getMessage();
    	}
    }

    public function addItemsAction()
    {
    	$mediaIds = $this->_getParam('mediaIds');
    	$insertAfter = intval($this->_getParam('insertAfter'));

    	if ($insertAfter == 0) {
    		$insertAfter = null;
    	}

    	Logging::info($mediaIds);

    	Logging::enablePropelLogging();

    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {
    		$playlist = $this->getPlaylist();
    		$playlist->addMedia($con, $mediaIds, $insertAfter);
    		$playlist->save($con);
    		$this->createUpdateResponse($playlist);

    		$con->commit();

    		Logging::disablePropelLogging();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::disablePropelLogging();
    		Logging::error($e->getMessage());
    		$this->view->error = $e->getMessage();
    	}
    }

    public function saveRulesAction()
    {
    	$rules = $this->_getParam('rules');
    	Logging::info($rules);

    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {
    		$playlist = $this->getPlaylist();

    		$form = new Application_Form_PlaylistRules();
    		$data = $form->buildForm($playlist, $rules);

    		if ($form->isValid($data)) {
    			Logging::info("playlist rules are valid");
    			Logging::info($form->getValues());
    			$playlist->setRules($rules);
    			$playlist->save($con);
    		}
    		else {
    			Logging::info("invalid playlist rules");
    			Logging::info($form->getMessages());
    			$this->view->form = $form->render();
    		}

    		$con->commit();
    		
    		$this->createUpdateResponse($playlist);
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		$this->view->error = $e->getMessage();
    	}
    }

    public function saveAction()
    {
    	$info = $this->_getParam('serialized');
    	Logging::info($info);

    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {
    		$playlist = $this->getPlaylist();
    		$this->playlistService->savePlaylist($playlist, $info, $con);
    		$this->createUpdateResponse($playlist);

    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		$this->view->error = $e->getMessage();
    	}
    }
}
