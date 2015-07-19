<?php

use Airtime\MediaItem\PlaylistQuery;
use Airtime\MediaItem\Playlist;
use Airtime\MediaItem\PlaylistPeer;

class PlaylistController extends Zend_Controller_Action
{

    public function init()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('add-items', 'json')
                    ->addActionContext('move-items', 'json')
                    ->addActionContext('delete-items', 'json')
                    ->addActionContext('set-fade', 'json')
                    ->addActionContext('set-crossfade', 'json')
                    ->addActionContext('set-cue', 'json')
                    ->addActionContext('new', 'json')
                    ->addActionContext('edit', 'json')
                    ->addActionContext('delete', 'json')
                    ->addActionContext('close-playlist', 'json')
                    ->addActionContext('play', 'json')
                    ->addActionContext('set-playlist-fades', 'json')
                    ->addActionContext('get-playlist-fades', 'json')
                    ->addActionContext('set-playlist-name', 'json')
                    ->addActionContext('set-playlist-description', 'json')
                    ->addActionContext('playlist-preview', 'json')
                    ->addActionContext('get-playlist', 'json')
                    ->addActionContext('save', 'json')
                    ->addActionContext('smart-block-generate', 'json')
                    ->addActionContext('smart-block-shuffle', 'json')
                    ->addActionContext('get-block-info', 'json')
                    ->addActionContext('shuffle', 'json')
                    ->addActionContext('empty-content', 'json')
                    ->initContext();

    }

    private function getPlaylist($p_type)
    {
        $obj = null;
        $objInfo = Application_Model_Library::getObjInfo($p_type);

        $obj_sess = new Zend_Session_Namespace(UI_PLAYLISTCONTROLLER_OBJ_SESSNAME);
        if (isset($obj_sess->id)) {
            $obj = new $objInfo['className']($obj_sess->id);

            $modified = $this->_getParam('modified', null);
            if ($obj->getLastModified("U") !== $modified) {
                $this->createFullResponse($obj);
                throw new PlaylistOutDatedException(sprintf(_("You are viewing an older version of %s"), $obj->getName()));
            }
        }

        return $obj;
    }

    private function createUpdateResponse($obj)
    {
        $formatter = new LengthFormatter($obj->getLength());
        $this->view->length = $formatter->format();

        $this->view->obj = $obj;
        $this->view->contents = $obj->getContents();
        $this->view->html = $this->view->render('playlist/update.phtml');
        $this->view->name = $obj->getName();
        $this->view->description = $obj->getDescription();
        $this->view->modified = $obj->getLastModified("U");

        unset($this->view->obj);
    }

    private function createFullResponse($obj = null, $isJson = false,
        $formIsValid = false)
    {
        $isBlock = false;
        $viewPath = 'playlist/playlist.phtml';
        if ($obj instanceof Application_Model_Block) {
            $isBlock = true;
            $viewPath = 'playlist/smart-block.phtml';
        }
        if (isset($obj)) {
            $formatter = new LengthFormatter($obj->getLength());
            $this->view->length = $formatter->format();

            if ($isBlock) {
                $form = new Application_Form_SmartBlockCriteria();
                $form->removeDecorator('DtDdWrapper');
                $form->startForm($obj->getId(), $formIsValid);

                $this->view->form = $form;
                $this->view->obj = $obj;
                $this->view->id = $obj->getId();

                if ($isJson) {
                    return $this->view->render($viewPath);
                } else {
                    $this->view->html = $this->view->render($viewPath);
                }
            } else {
                $this->view->obj = $obj;
                $this->view->id = $obj->getId();
                if ($isJson) {
                    return $this->view->html = $this->view->render($viewPath);
                } else {
                    $this->view->html = $this->view->render($viewPath);
                }
                unset($this->view->obj);
            }
        } else {
            if ($isJson) {
                return $this->view->render($viewPath);
            } else {
                $this->view->html = $this->view->render($viewPath);
            }
        }
    }

    private function playlistOutdated($e)
    {
        $this->view->error = $e->getMessage();
    }

    private function blockDynamic($obj)
    {
        $this->view->error = _("You cannot add tracks to dynamic blocks.");
        $this->createFullResponse($obj);
    }

    private function playlistNotFound($p_type, $p_isJson = false)
    {
        $p_type = ucfirst($p_type);
        $this->view->error = sprintf(_("%s not found"), $p_type);

        Logging::info("{$p_type} not found");
        Application_Model_Library::changePlaylist(null, $p_type);


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

        try {
            $obj = new $objInfo['className']($id);
            $this->createFullResponse($obj);
        } catch (PlaylistNotFoundException $e) {
            $this->playlistNotFound($type);
        } catch (Exception $e) {
            $this->playlistUnknownError($e);
        }
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
        $id1 = $this->_getParam('id1', null);
        $id2 = $this->_getParam('id2', null);
        $type = $this->_getParam('type');
        $fadeIn = $this->_getParam('fadeIn', 0);
        $fadeOut = $this->_getParam('fadeOut', 0);
        $offset = $this->_getParam('offset', 0);

        try {
            $obj = $this->getPlaylist($type);
            $response = $obj->createCrossfade($id1, $fadeOut, $id2, $fadeIn, $offset);

            if (!isset($response["error"])) {
                $this->createUpdateResponse($obj);
            } else {
                $this->view->error = $response["error"];
            }
        } catch (PlaylistOutDatedException $e) {
            $this->playlistOutdated($e);
        } catch (PlaylistNotFoundException $e) {
            $this->playlistNotFound($type);
        } catch (Exception $e) {
            $this->playlistUnknownError($e);
        }
    }

    public function addItemsAction()
    {
    	$content = $this->_getParam('content');

    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();

    	try {
    		$playlist = $this->getPlaylist();
    		$playlist->savePlaylistContent($con, $content);
    		$this->createUpdateResponse($playlist);

    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
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

    		if (isset($rules["criteria"])) {
    			$form->buildCriteriaOptions($rules["criteria"]);
    		}

    		$criteriaFields = $form->getPopulateHelp();

    		$playlistRules = array(
    			"pl_repeat_tracks" => $rules[Playlist::RULE_REPEAT_TRACKS],
    			"pl_my_tracks" => $rules[Playlist::RULE_USERS_TRACKS_ONLY],
    			"pl_order_column" => $rules[Playlist::RULE_ORDER][Playlist::RULE_ORDER_COLUMN],
    			"pl_order_direction" => $rules[Playlist::RULE_ORDER][Playlist::RULE_ORDER_DIRECTION],
    			"pl_limit_value" => $rules["limit"]["value"],
    			"pl_limit_options" => $rules["limit"]["unit"]
    		);

    		$data = array_merge($criteriaFields, $playlistRules);

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
