<?php

use Airtime\MediaItem\AudioFileQuery;
use \Criteria;

class PluploadController extends Zend_Controller_Action
{
    public function init()
    {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('upload', 'json')
                    ->addActionContext('recent-uploads', 'json')
                    ->initContext();
    }

    public function indexAction()
    {
        $CC_CONFIG = Config::getConfig();

        $baseUrl = Application_Common_OsPath::getBaseDir();
        $locale = Application_Model_Preference::GetLocale();

        $this->view->headScript()->appendFile($baseUrl.'js/datatables/js/jquery.dataTables.js?'.$CC_CONFIG['airtime_version'], 'text/javascript');
        $this->view->headScript()->appendFile($baseUrl.'js/plupload/plupload.full.min.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $this->view->headScript()->appendFile($baseUrl.'js/plupload/jquery.plupload.queue.min.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $this->view->headScript()->appendFile($baseUrl.'js/airtime/library/plupload.js?'.$CC_CONFIG['airtime_version'],'text/javascript');
        $this->view->headScript()->appendFile($baseUrl.'js/plupload/i18n/'.$locale.'.js?'.$CC_CONFIG['airtime_version'],'text/javascript');

        $this->view->headLink()->appendStylesheet($baseUrl.'css/plupload.queue.css?'.$CC_CONFIG['airtime_version']);
        $this->view->headLink()->appendStylesheet($baseUrl.'css/addmedia.css?'.$CC_CONFIG['airtime_version']);

        $this->view->quotaLimitReached = false;
        if (Application_Model_Systemstatus::isDiskOverQuota()) {
            $this->view->quotaLimitReached = true;
        }
    }

    public function recentUploadsAction()
    {

    	$request = $this->getRequest();
    	
        $filter = $request->getParam('uploadFilter', "all");
        $limit = intval($request->getParam('iDisplayLength', 10));
        $rowStart = intval($request->getParam('iDisplayStart', 0));
        
        $recentUploadsQuery = AudioFileQuery::create();
        
        $numTotalRecentUploads = $recentUploadsQuery->count();
        $numTotalDisplayUploads = $numTotalRecentUploads;
        
        if ($filter == "pending") {

            $recentUploadsQuery->filterByImportStatus(1);
            $numTotalDisplayUploads = $recentUploadsQuery->count();
        } else if ($filter == "failed") {
            $recentUploadsQuery->filterByImportStatus(2);
            $numTotalDisplayUploads = $recentUploadsQuery->count();
            //TODO: Consider using array('min' => 200)) or something if we have multiple errors codes for failure.
        }
        
        $recentUploads = $recentUploadsQuery
        	->orderByCreatedAt(Criteria::DESC)
        	->offset($rowStart)
        	->limit($limit)
        	->find();
        
        $uploadsArray = array();
        $utcTimezone = new DateTimeZone("UTC");
        $displayTimezone = new DateTimeZone(Application_Model_Preference::GetUserTimezone());
        
        foreach ($recentUploads as $upload)
        {
            $upload = $upload->toArray(BasePeer::TYPE_FIELDNAME);
            //TODO: $this->sanitizeResponse($upload));

            $upload['created_at'] = new DateTime($upload['created_at'], $utcTimezone);
            $upload['created_at']->setTimeZone($displayTimezone);
            $upload['created_at'] = $upload['created_at']->format('Y-m-d H:i:s');

            //TODO: Invoke sanitization here
            array_push($uploadsArray, $upload);
        }
        
        $this->view->sEcho = intval($request->getParam('sEcho'));
        $this->view->iTotalDisplayRecords = $numTotalDisplayUploads;
        $this->view->iTotalRecords = $numTotalRecentUploads;
        $this->view->files = $uploadsArray;
    }
}
