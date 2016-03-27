<?php

class MediaController extends Zend_Controller_Action
{

    public function init()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext
        	->addActionContext('audio-file-feed', 'json')
        	->addActionContext('webstream-feed', 'json')
        	->addActionContext('playlist-feed', 'json')
            ->initContext();

    }

    public function audioFileFeedAction()
    {
    	$params = $this->getRequest()->getParams();
    	
    	Logging::info($params);
    	
    	$mediaService = new Application_Service_MediaService();
    	$r = $mediaService->getDatatablesAudioFiles($params);
    	
    	$this->view->sEcho = intval($params["sEcho"]);
    	$this->view->iTotalDisplayRecords = count($r);
    	$this->view->iTotalRecords = count($r);
    	$this->view->media = $r;
    }
    
    public function webstreamFeedAction()
    {
    	$params = $this->getRequest()->getParams();
    	 
    	Logging::info($params);
    	 
    	$mediaService = new Application_Service_MediaService();
    	$r = $mediaService->getDatatablesWebstreams($params);
    	 
    	$this->view->sEcho = intval($params["sEcho"]);
    	$this->view->iTotalDisplayRecords = count($r);
    	$this->view->iTotalRecords = count($r);
    	$this->view->media = $r;
    }
    
    public function playlistFeedAction()
    {
    	$params = $this->getRequest()->getParams();
    	 
    	Logging::info($params);
    	 
    	$mediaService = new Application_Service_MediaService();
    	$r = $mediaService->getDatatablesAudioFiles($params);
    	 
    	$this->view->sEcho = intval($params["sEcho"]);
    	$this->view->iTotalDisplayRecords = count($r);
    	$this->view->iTotalRecords = count($r);
    	$this->view->media = $r;
    }
}