<?php

use Airtime\MediaItem\AudioFileQuery;

use Airtime\PlayoutHistory\CcPlayoutHistory;
use Airtime\PlayoutHistory\CcPlayoutHistoryPeer;
use Airtime\PlayoutHistory\CcPlayoutHistoryQuery;
use Airtime\PlayoutHistory\CcPlayoutHistoryMetaData;
use Airtime\PlayoutHistory\CcPlayoutHistoryTemplate;
use Airtime\PlayoutHistory\CcPlayoutHistoryTemplatePeer;
use Airtime\PlayoutHistory\CcPlayoutHistoryTemplateQuery;
use Airtime\PlayoutHistory\CcPlayoutHistoryTemplateField;
use Airtime\PlayoutHistory\CcPlayoutHistoryTemplateFieldPeer;

use Airtime\CcShowHostsQuery;
use Airtime\CcScheduleQuery;
use Airtime\CcShowInstancesQuery;

class Application_Service_HistoryService
{
	private $con;
	private $timezone;

	const TEMPLATE_TYPE_ITEM = "item";
	const TEMPLATE_TYPE_FILE = "file";

	public function __construct()
	{
		$this->con = isset($con) ? $con : Propel::getConnection(CcPlayoutHistoryPeer::DATABASE_NAME);
		$this->timezone = Application_Model_Preference::GetTimezone();
	}

	public function getSupportedTemplateTypes()
	{
		return array(self::TEMPLATE_TYPE_ITEM, self::TEMPLATE_TYPE_FILE);
	}

	private function getNeededFileMetadataColumns()
	{
		$template = $this->getConfiguredFileTemplate();
		$fields = $template["fields"];
		$cols = array();
		$notCols = array(HISTORY_ITEM_PLAYED);

		foreach ($fields as $field) {
			$name = $field["name"];

			if (!in_array($name, $notCols)) {
				$cols[$name] = null;
			}
		}

		return $cols;
	}

	private function getNeededItemMetadataColumns()
	{
		$template = $this->getConfiguredItemTemplate();
		$fields = $template["fields"];
		$cols = array();
		$notCols = array(HISTORY_ITEM_STARTS, HISTORY_ITEM_ENDS);

		foreach ($fields as $field) {
			$name = $field["name"];

			if (!in_array($name, $notCols)) {
				$cols[$name] = null;
			}
		}

		return $cols;
	}

	//opts is from datatables.
	 public function getPlayedItemData($startDT, $endDT, $opts, $instanceId=null)
	 {
	 	$this->con->beginTransaction();

	 	//LIMIT OFFSET statements
	 	$limit = intval($opts["iDisplayLength"]);
	 	$offset = intval($opts["iDisplayStart"]);

	 	$query = CcPlayoutHistoryQuery::create()
		 	->_if(isset($instanceId))
		 		->filterByDbInstanceId($instanceId)
		 	->_endif()
		 	->_if(is_null($instanceId))
		 		->filterByDbStarts($startDT, Criteria::GREATER_EQUAL)
	 			->filterByDbStarts($endDT, Criteria::LESS_EQUAL)
		 	->_endif();

	 	$totalCount = $query->count($this->con);

		$items = $query
		 	->orderByDbStarts()
	 		->_if($limit !== -1) //Datatables ALL
		 		->limit($limit)
		 		->offset($offset)
	 		->_endif()
	 		->find($this->con);

		$items->populateRelation('CcPlayoutHistoryMetaData');

		$this->con->commit();

		$timezoneUTC = new DateTimeZone("UTC");
		$timezoneLocal = new DateTimeZone($this->timezone);

		$neededColumns = $this->getNeededItemMetadataColumns();
		$neededMetadata = array_keys($neededColumns);
		$datatables = array();
		foreach($items as $item) {
			$row = $neededColumns;

			//need to display the results in the station's timezone.
			$start = $item->getDbStarts(null);
			$start->setTimezone($timezoneLocal);
			$row[HISTORY_ITEM_STARTS] = $start->format("Y-m-d H:i:s");

			$end = $item->getDbEnds(null);
			//if ends is null we don't want it to default to "now"
			if (isset($end)) {
				$end->setTimezone($timezoneLocal);
				$row[HISTORY_ITEM_ENDS] = $end->format("Y-m-d H:i:s");
			}
			else {
				$row[HISTORY_ITEM_ENDS] = null;
			}

			$metadata = $item->getCcPlayoutHistoryMetaDatas(null, $this->con);
			foreach ($metadata as $m) {
				$key = $m->getDbKey();

				if (in_array($key, $neededMetadata)) {
					$row[$key] = $m->getDbValue();
				}
			}

			$row["history_id"] = $item->getDbId();
			$row["checkbox"] = "";

			$datatables[] = $row;
		}

	 	return array(
 			"sEcho" => intval($opts["sEcho"]),
 			"iTotalDisplayRecords" => intval($totalCount),
 			"iTotalRecords" => intval($totalCount),
 			"history" => $datatables
	 	);
	 }

	public function getFileSummaryData($startDT, $endDT, $opts)
	{
		Logging::enablePropelLogging();

		$fieldMap = array(
			MDATA_KEY_TITLE => "track_title",
			MDATA_KEY_CREATOR => "artist_name",
			MDATA_KEY_SOURCE => "album_title",
			MDATA_KEY_GENRE => "genre",
			MDATA_KEY_MOOD => "mood",
			MDATA_KEY_LABEL => "label",
			MDATA_KEY_COMPOSER => "composer",
			MDATA_KEY_ISRC => "isrc_number",
			MDATA_KEY_COPYRIGHT => "copyright",
			MDATA_KEY_YEAR => "year",
			MDATA_KEY_TRACKNUMBER => "track_number",
			MDATA_KEY_CONDUCTOR => "conductor",
			MDATA_KEY_LANGUAGE => "language",
			MDATA_KEY_DURATION => "length",
			HISTORY_ITEM_PLAYED => "played"
		);

		$select = array (
			"summary.played AS \"".HISTORY_ITEM_PLAYED."\"",
			"summary.media_id",
			"summary.{$fieldMap[MDATA_KEY_TITLE]} AS \"".MDATA_KEY_TITLE."\"",
			"summary.{$fieldMap[MDATA_KEY_CREATOR]} AS \"".MDATA_KEY_CREATOR."\""
		);

		$mainSqlQuery = "";
		$paramMap = array();
		$start = $startDT->format("Y-m-d H:i:s");
		$end = $endDT->format("Y-m-d H:i:s");

		$paramMap["starts"] = $start;
		$paramMap["ends"] = $end;

		$template = $this->getConfiguredFileTemplate();
		$fields = $template["fields"];
		$required = $this->mandatoryFileFields();

		foreach ($fields as $index=>$field) {

			$key = $field["name"];

			if (in_array($field["name"], $required)) {
				continue;
			}

			$select[] = "summary.{$fieldMap[$key]} AS \"{$key}\"";
		}

		$fileSummaryTable = "((
			SELECT COUNT(history.media_id) as played, history.media_id as media_id
			FROM cc_playout_history AS history
			WHERE history.starts >= :starts AND history.starts < :ends
			GROUP BY history.media_id
		) AS playout
		JOIN media_audiofile AS audiofile ON (audiofile.id = playout.media_id)) AS summary";

		$mainSqlQuery.=
		"SELECT ".join(", ", $select).
		" FROM {$fileSummaryTable}";

		//-------------------------------------------------------------------------
		//need to count the total rows to tell Datatables.
		$stmt = $this->con->prepare($mainSqlQuery);
		foreach ($paramMap as $param => $v) {
			$stmt->bindValue($param, $v);
		}

		if ($stmt->execute()) {
			$totalRows = $stmt->rowCount();
		}
		else {
			$msg = implode(',', $stmt->errorInfo());
			throw new Exception("Error: $msg");
		}

		//------------------------------------------------------------------------
		//Using Datatables parameters to sort the data.

		$numOrderColumns = $opts["iSortingCols"];
		$orderBys = array();

		for ($i = 0; $i < $numOrderColumns; $i++) {

			$colNum = $opts["iSortCol_".$i];
			$key = $opts["mDataProp_".$colNum];
			$sortDir = $opts["sSortDir_".$i];

			$orderBys[] = "summary.{$fieldMap[$key]} {$sortDir}";
		}

		if ($numOrderColumns > 0) {

			$orders = join(", ", $orderBys);

			$mainSqlQuery.=
			" ORDER BY {$orders}";
		}

		//------------------------------------------------------------
		//using datatables params to add limits/offsets
		$displayLength = intval($opts["iDisplayLength"]);
		if ($displayLength !== -1) {
			$mainSqlQuery.=
			" OFFSET :offset LIMIT :limit";

			$paramMap["offset"] = $opts["iDisplayStart"];
			$paramMap["limit"] = $displayLength;
		}

		$stmt = $this->con->prepare($mainSqlQuery);
		foreach ($paramMap as $param => $v) {
			$stmt->bindValue($param, $v);
		}

		$rows = array();
		if ($stmt->execute()) {
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		else {
			$msg = implode(',', $stmt->errorInfo());
			throw new Exception("Error: $msg");
		}

		//-----------------------------------------------------------------
		//processing the results
		foreach ($rows as &$row) {
			if (isset($row[MDATA_KEY_DURATION])) {
				$formatter = new Format_HHMMSSULength($row[MDATA_KEY_DURATION]);
				$row[MDATA_KEY_DURATION] = $formatter->format();
			}
		}

		Logging::disablePropelLogging();

		return array(
			"sEcho" => intval($opts["sEcho"]),
			"iTotalDisplayRecords" => intval($totalRows),
			"iTotalRecords" => intval($totalRows),
			"history" => $rows
		);
	}

	public function getShowList($startDT, $endDT, $userId = null)
	{
        if (empty($userId)) {
		    $user = Application_Model_User::getCurrentUser();
        } else {
            $user = new Application_Model_User($userId);
        }
		$shows = Application_Model_Show::getShows($startDT, $endDT);

		Logging::info($startDT->format("Y-m-d H:i:s"));
		Logging::info($endDT->format("Y-m-d H:i:s"));

		Logging::info($shows);

		//need to filter the list to only their shows
		if ((!empty($user)) && ($user->isHost())) {

			$showIds = array();

			foreach ($shows as $show) {
				$showIds[] = $show["show_id"];
			}

			$showIds = array_unique($showIds);
			Logging::info($showIds);

			$hostRecords = CcShowHostsQuery::create()
				->filterByDbHost($user->getId())
				->filterByDbShow($showIds)
				->find($this->con);

			$filteredShowIds = array();

			foreach($hostRecords as $record) {
				$filteredShowIds[] = $record->getDbShow();
			}

			Logging::info($filteredShowIds);

			$filteredShows = array();

			foreach($shows as $show) {
				if (in_array($show["show_id"], $filteredShowIds)) {
					$filteredShows[] = $show;
				}
			}
		}
		else {
			$filteredShows = $shows;
		}

		$timezoneUTC = new DateTimeZone("UTC");
		$timezoneLocal = new DateTimeZone($this->timezone);

		foreach ($filteredShows as &$result) {

			//need to display the results in the station's timezone.
			$dateTime = new DateTime($result["starts"], $timezoneUTC);
			$dateTime->setTimezone($timezoneLocal);
			$result["starts"] = $dateTime->format("Y-m-d H:i:s");

			$dateTime = new DateTime($result["ends"], $timezoneUTC);
			$dateTime->setTimezone($timezoneLocal);
			$result["ends"] = $dateTime->format("Y-m-d H:i:s");

		}

		return $filteredShows;
	}

	public function insertHistoryItem($schedId, $opts = array()) {

		$this->con->beginTransaction();

		try {
			
			// we need to update 'broadcasted' column as well
			// check the current switch status
			$live_dj = Application_Model_Preference::GetSourceSwitchStatus('live_dj') == 'on';
			$master_dj = Application_Model_Preference::GetSourceSwitchStatus('master_dj') == 'on';
			$scheduled_play = Application_Model_Preference::GetSourceSwitchStatus('scheduled_play') == 'on';
			
			$scheduledItem = CcScheduleQuery::create()
				->filterByPrimaryKey($schedId)
				->joinWith("MediaItem", Criteria::LEFT_JOIN)
				->findOne($this->con);

			if (isset($scheduledItem)) {

				$scheduledItem->setDbMediaItemPlayed(true);
					
				//not sure how well this broadcasted column actually is at doing anything.
				if (!$live_dj && !$master_dj && $scheduled_play) {
					$scheduledItem->setDbBroadcasted(1);
					
					$mediaItem = $scheduledItem->getMediaItem($this->con);
						
					//set a 'last played' timestamp for media item
					$utcNow = new DateTime("now", new DateTimeZone("UTC"));
					$mediaItem->setLastPlayedTime($utcNow);
					
					$playcount = $mediaItem->getPlayCount();
					$mediaItem->setPlayCount($playcount + 1);
					
					$mediaItem->save($this->con);
					$scheduledItem->save($this->con);
					
					$type = $mediaItem->getType();
					$strategy = "Strategy_{$type}HistoryItem";
					
					$insertStrategy = new $strategy();
					$insertStrategy->insertHistoryItem($schedId, $this->con, $opts);
				}	
			}

			$this->con->commit();
		}
		catch (Exception $e) {
			$this->con->rollback();
		}
	}

	/* id is an id in cc_playout_history */
	public function makeHistoryItemForm($id, $populate=false) {

		$fieldMap = array(
			HISTORY_ITEM_STARTS => "DbStarts",
			HISTORY_ITEM_ENDS => "DbEnds"
		);

		try {
			$form = new Application_Form_EditHistoryItem();
			$template = $this->getConfiguredItemTemplate();
			$required = $this->mandatoryItemFields();
			$form->createFromTemplate($template["fields"], $required);

			if ($populate) {
				$formValues = array();

				$historyRecord = CcPlayoutHistoryQuery::create()->findPk($id, $this->con);
				$instance = $historyRecord->getCcShowInstances($this->con);

				if (isset($instance)) {
				    $show = $instance->getCcShow($this->con);
				    $selOpts = array();
				    $instance_id = $instance->getDbId();
				    $selOpts[$instance_id] = $show->getDbName();
				    $form->populateShowInstances($selOpts, $instance_id);
				}

				$metadata = array();
				$mds = $historyRecord->getCcPlayoutHistoryMetaDatas();
				foreach ($mds as $md) {
					$metadata[$md->getDbKey()] = $md->getDbValue();
				}

				$prefix = Application_Form_EditHistoryItem::ID_PREFIX;
				$formValues["{$prefix}id"] = $id;

				foreach($template["fields"] as $index => $field) {

					$key = $field["name"];
					$value = "";

					if (in_array($key, $required)) {

						$method = "get".$fieldMap[$key];
						$value = $historyRecord->$method();
					}
					else if (isset($metadata[$key])) {
						$value = $metadata[$key];
					}

					//need to convert to the station's local time first.
					if ($field["type"] == TEMPLATE_DATETIME && !is_null($value)) {
						$timezoneUTC = new DateTimeZone("UTC");
						$timezoneLocal = new DateTimeZone($this->timezone);

						$dateTime = new DateTime($value, $timezoneUTC);
						$dateTime->setTimezone($timezoneLocal);
						$value = $dateTime->format("Y-m-d H:i:s");
					}

					$formValues["$prefix{$key}"] = $value;
				}

				$form->populate($formValues);
			}

			return $form;
		}
		catch (Exception $e) {
			Logging::info($e);
			throw $e;
		}
	}

	/* id is an id in cc_files */
	public function makeHistoryFileForm($id) {

	    try {
		    $form = new Application_Form_EditHistoryFile();
			$template = $this->getConfiguredFileTemplate();
			$required = $this->mandatoryFileFields();
			$form->createFromTemplate($template["fields"], $required);

		    $file = AudioFileQuery::create()->findPK($id, $this->con);
		    $md = $file->getMetadata();

		    $prefix = Application_Form_EditHistoryFile::ID_PREFIX;
		    $formValues = array();
		    $formValues["{$prefix}id"] = $id;

		    foreach($template["fields"] as $index => $field) {

		    	$key = $field["name"];

		    	if (in_array($key, $required)) {
		    		continue;
		    	}

		    	$value = $md[$key];
		    	$formValues["$prefix{$key}"] = $value;
		    }

		    $form->populate($formValues);

		    return $form;
	    }
	    catch (Exception $e) {
	        Logging::info($e);
	        throw $e;
	    }
	}

	public function populateTemplateFile($values, $id) {

		$this->con->beginTransaction();

		try {

			$file = AudioFileQuery::create()->findPk($id, $this->con);

			$prefix = Application_Form_EditHistoryFile::ID_PREFIX;
			$prefix_len = strlen($prefix);
			$templateValues = $values[$prefix."template"];

			$md = array();

			foreach ($templateValues as $index => $value) {

				$key = substr($index, $prefix_len);
				$md[$key] = $value;
			}

			Logging::info($md);

			$file->setMetadata($md);
			$file->save($this->con);

			$this->con->commit();
		}
		catch (Exception $e) {
    		$this->con->rollback();
    		throw $e;
    	}
	}

	public function populateTemplateItem($values, $id=null, $instance_id=null) {

		$this->con->beginTransaction();

		try {
		    $template = $this->getConfiguredItemTemplate();
		    $prefix = Application_Form_EditHistoryItem::ID_PREFIX;

		    if (isset($id)) {
		    	$historyRecord = CcPlayoutHistoryQuery::create()->findPk($id, $this->con);
		    }
		    else {
		    	$historyRecord = new CcPlayoutHistory();
		    }

		    if (isset($instance_id)) {
		    	$historyRecord->setDbInstanceId($instance_id);
		    }

		    $timezoneUTC = new DateTimeZone("UTC");
		    $timezoneLocal = new DateTimeZone($this->timezone);

	    	$dateTime = new DateTime($values[$prefix.HISTORY_ITEM_STARTS], $timezoneLocal);
	    	$dateTime->setTimezone($timezoneUTC);
	    	$historyRecord->setDbStarts($dateTime->format("Y-m-d H:i:s"));

	    	$dateTime = new DateTime($values[$prefix.HISTORY_ITEM_ENDS], $timezoneLocal);
	    	$dateTime->setTimezone($timezoneUTC);
	    	$historyRecord->setDbEnds($dateTime->format("Y-m-d H:i:s"));

	    	$templateValues = $values[$prefix."template"];

	    	$file = $historyRecord->getMediaItem($this->con);

	    	$md = array();
	    	$metadata = array();
	    	$fields = $template["fields"];
	    	$required = $this->mandatoryItemFields();
	    	$phpCasts = $this->getPhpCasts();

	    	for ($i = 0, $len = count($fields); $i < $len; $i++) {

	    	    $field = $fields[$i];
	    	    $key = $field["name"];

	    	    //required is delt with before this loop.
	    	    if (in_array($key, $required)) {
	    	    	continue;
	    	    }

	    	    $entry = $phpCasts[$field["type"]]($templateValues[$prefix.$key]);
	    	    Logging::info("adding metadata for {$key} = {$entry}");
                $metadata[$key] = $entry;
	    	}

	    	//Use this array to update existing values.
	    	$mds = $historyRecord->getCcPlayoutHistoryMetaDatas();
	    	foreach ($mds as $md) {
	    		$prevmd[$md->getDbKey()] = $md;
	    	}
	    	foreach ($metadata as $key => $val) {

	    		if (isset($prevmd[$key])) {
	    			$meta = $prevmd[$key];
	    			$meta->setDbValue($val);
	    		}
	    		else {
	    			$meta = new CcPlayoutHistoryMetaData();
	    			$meta->setDbKey($key);
	    			$meta->setDbValue($val);

	    			$historyRecord->addCcPlayoutHistoryMetaData($meta);
	    		}
	    	}

	    	$historyRecord->save($this->con);
	    	$this->con->commit();
    	}
    	catch (Exception $e) {
    		$this->con->rollback();
    		throw $e;
    	}
	}

	//start,end timestamp strings in local timezone.
	public function populateShowInstances($start, $end) {
		$timezoneLocal = new DateTimeZone($this->timezone);

		$startDT = new DateTime($start, $timezoneLocal);
		$endDT = new DateTime($end, $timezoneLocal);

		$shows = $this->getShowList($startDT, $endDT);

		$select = array();

		foreach ($shows as &$show) {
			$select[$show["instance_id"]] = $show["name"];
		}

		return $select;
	}

	private function validateHistoryItem($instanceId, $form) {

	    $valid = true;

	    $recordStartsEl = $form->getElement("his_item_".HISTORY_ITEM_STARTS);
	    $recordStarts = $recordStartsEl->getValue();
	    $recordEndsEl = $form->getElement("his_item_".HISTORY_ITEM_ENDS);
	    $recordEnds = $recordEndsEl->getValue();

	    $timezoneLocal = new DateTimeZone($this->timezone);

	    $startDT = new DateTime($recordStarts, $timezoneLocal);
	    $endDT = new DateTime($recordEnds, $timezoneLocal);

	    if ($recordStarts > $recordEnds) {
	        $valid = false;
	        $recordEndsEl->addErrorMessage("End time must be after start time");
	    }

	    if (isset($instanceId)) {

	        $instance = CcShowInstancesQuery::create()->findPk($instanceId, $this->con);
	        $inStartsDT = $instance->getDbStarts(null);
	        $inEndsDT = $instance->getDbEnds(null);

	        if ($startDT < $inStartsDT) {
	            $valid = false;
	            $form->addErrorMessage("History item begins before show.");
	        }
	        else if ($startDT > $inEndsDT) {
	            $valid = false;
	            $form->addErrorMessage("History item begins after show.");
	        }
	    }

	    return $valid;
	}

	public function createPlayedItem($data) {

		try {
			$form = $this->makeHistoryItemForm(null);
			$history_id = $form->getElement("his_item_id");
			$instanceId = isset($data["instance_id"]) ? $data["instance_id"] : null;
			$json = array();

	        if ($form->isValid($data) && $this->validateHistoryItem($instanceId, $form)) {

	        	$history_id->setIgnore(true);
	        	$values = $form->getValues();

	        	$this->populateTemplateItem($values, null, $instanceId);
	        }
	        else {
	        	$json["form"] = $form;
	        }

	        return $json;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/* id is an id in cc_playout_history */
	public function editPlayedItem($data) {

		try {
			$id = $data["his_item_id"];
			$instanceId = isset($data["instance_id"]) ? $data["instance_id"] : null;
			$form = $this->makeHistoryItemForm($id);
			$history_id = $form->getElement("his_item_id");
			$history_id->setRequired(true);

			$json = array();

			if ($form->isValid($data) && $this->validateHistoryItem($instanceId, $form)) {

			    $history_id->setIgnore(true);
	        	$values = $form->getValues();
	        	$this->populateTemplateItem($values, $id, $instanceId);
	        }
	        else {
	        	$json["form"] = $form;
	        }

	        return $json;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/* id is an id in cc_files */
	public function editPlayedFile($data) {

		try {
			$id = $data["his_file_id"];
	        $form = $form = $this->makeHistoryFileForm($id);
	        $history_id = $form->getElement("his_file_id");
	        $history_id->setRequired(true);

			$json = array();

	        if ($form->isValid($data)) {
	        	$history_id->setIgnore(true);
	        	$values = $form->getValues();

	            $this->populateTemplateFile($values, $id);
	        }
	        else {
	        	$json["error"] = $msgs;
	        }

	        return $json;

	        $this->con->commit();
		}
		catch (Exception $e) {
			$this->con->rollback();
			Logging::info($e);
			throw $e;
		}

        return $json;
	}

	/* id is an id in cc_playout_history */
	public function deletePlayedItem($id) {

		$this->con->beginTransaction();

		try {

			$record = CcPlayoutHistoryQuery::create()->findPk($id, $this->con);
			$record->delete($this->con);

			$this->con->commit();
		}
		catch (Exception $e) {
			$this->con->rollback();
			Logging::info($e);
			throw $e;
		}
	}

	/* id is an id in cc_playout_history */
	public function deletePlayedItems($ids) {

		$this->con->beginTransaction();

		try {

			$records = CcPlayoutHistoryQuery::create()->findPks($ids, $this->con);
			$records->delete($this->con);

			$this->con->commit();
		}
		catch (Exception $e) {
			$this->con->rollback();
			Logging::info($e);
			throw $e;
		}
	}


	//---------------- Following code is for History Templates --------------------------//

	public function getFieldTypes() {

	    $fields = array(
    	    //TEMPLATE_DATE,
    	    //TEMPLATE_TIME,
    	    //TEMPLATE_DATETIME,
    	    TEMPLATE_STRING,
    	    TEMPLATE_BOOLEAN,
    	    TEMPLATE_INT,
    	    TEMPLATE_FLOAT,
	    );

	    return $fields;
	}

	private function getPhpCasts() {

		$fields = array(
			TEMPLATE_DATE => "strval",
			TEMPLATE_TIME => "strval",
			TEMPLATE_DATETIME => "strval",
			TEMPLATE_STRING => "strval",
			TEMPLATE_BOOLEAN => "intval", //boolval only exists in php 5.5+
			TEMPLATE_INT => "intval",
			TEMPLATE_FLOAT => "floatval",
		);

		return $fields;
	}

	private function getSqlTypes() {

		$fields = array(
			TEMPLATE_DATE => "date",
			TEMPLATE_TIME => "time",
			TEMPLATE_DATETIME => "datetime",
			TEMPLATE_STRING => "text",
			TEMPLATE_BOOLEAN => "boolean",
			TEMPLATE_INT => "integer",
			TEMPLATE_FLOAT => "float",
		);

		return $fields;
	}

	public function getFileMetadataTypes() {

		$fileMD = array(
			array("name"=> MDATA_KEY_TITLE, "label"=> _("Title"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_CREATOR, "label"=> _("Creator"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_SOURCE, "label"=> _("Album"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_DURATION, "label"=> _("Length"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_GENRE, "label"=> _("Genre"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_MOOD, "label"=> _("Mood"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_LABEL, "label"=> _("Label"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_COMPOSER, "label"=> _("Composer"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_ISRC, "label"=> _("ISRC"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_COPYRIGHT, "label"=> _("Copyright"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_YEAR, "label"=> _("Year"), "type"=> TEMPLATE_INT),
			array("name"=> MDATA_KEY_TRACKNUMBER, "label"=> _("Track"), "type"=> TEMPLATE_INT),
			array("name"=> MDATA_KEY_CONDUCTOR, "label"=> _("Conductor"), "type"=> TEMPLATE_STRING),
			array("name"=> MDATA_KEY_LANGUAGE, "label"=> _("Language"), "type"=> TEMPLATE_STRING),
		);

		return $fileMD;
	}

	public function mandatoryItemFields() {

	    $fields = array(HISTORY_ITEM_STARTS, HISTORY_ITEM_ENDS);

	    return $fields;
	}

	public function mandatoryFileFields() {

		$fields = array(HISTORY_ITEM_PLAYED);

		return $fields;
	}

	private function defaultItemTemplate() {

		$template = array();
		$fields = array();

		$fields[] = array("name" => HISTORY_ITEM_STARTS, "label"=> _("Start Time"),"type" => TEMPLATE_DATETIME, "isFileMd" => false);
		$fields[] = array("name" => HISTORY_ITEM_ENDS, "label"=> _("End Time"), "type" => TEMPLATE_DATETIME, "isFileMd" => false);
		$fields[] = array("name" => MDATA_KEY_TITLE, "label"=> _("Title"), "type" => TEMPLATE_STRING, "isFileMd" => true); //these fields can be populated from an associated file.
		$fields[] = array("name" => MDATA_KEY_CREATOR, "label"=> _("Creator"), "type" => TEMPLATE_STRING, "isFileMd" => true);

		$template["name"] = "Log Sheet ".gmdate("c")." Template";
		$template["fields"] = $fields;

		return $template;
	}

	/*
	 * Default File Summary Template. Taken from The Czech radio requirements (customer requested this in the past).
	 */
	private function defaultFileTemplate() {

		$template = array();
		$fields = array();

		$fields[] = array("name" => MDATA_KEY_TITLE, "label"=> _("Title"), "type" => TEMPLATE_STRING, "isFileMd" => true);
		$fields[] = array("name" => MDATA_KEY_CREATOR, "label"=> _("Creator"), "type" => TEMPLATE_STRING, "isFileMd" => true);
		$fields[] = array("name" => HISTORY_ITEM_PLAYED, "label"=> _("Played"), "type" => TEMPLATE_INT, "isFileMd" => false);
		$fields[] = array("name" => MDATA_KEY_DURATION, "label"=> _("Length"), "type" => TEMPLATE_STRING, "isFileMd" => true);
		$fields[] = array("name" => MDATA_KEY_COMPOSER, "label"=> _("Composer"), "type" => TEMPLATE_STRING, "isFileMd" => true);
		$fields[] = array("name" => MDATA_KEY_COPYRIGHT, "label"=> _("Copyright"), "type" => TEMPLATE_STRING, "isFileMd" => true);

		$template["name"] = "File Summary ".gmdate("c")." Template";
		$template["fields"] = $fields;

		return $template;
	}

	public function loadTemplate($id) {

		try {

			if (!is_numeric($id)) {
				throw new Exception("Error: $id is not numeric.");
			}

			$template = CcPlayoutHistoryTemplateQuery::create()->findPk($id, $this->con);

			if (empty($template)) {
				throw new Exception("Error: Template $id does not exist.");
			}

			$c = new Criteria();
			$c->addAscendingOrderByColumn(CcPlayoutHistoryTemplateFieldPeer::POSITION);
			$config = $template->getCcPlayoutHistoryTemplateFields($c, $this->con);
			$fields = array();

			foreach ($config as $item) {

				$fields[] = array(
					"name" => $item->getDbName(),
					"label" => $item->getDbLabel(),
					"type" => $item->getDbType(),
					"isFileMd" => $item->getDbIsFileMD(),
					"id" => $item->getDbId()
				);
			}

			$data = array();
			$data["id"] = $template->getDbId();
			$data["name"] = $template->getDbName();
			$data["fields"] = $fields;
			$data["type"] = $template->getDbType();

			return $data;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getItemTemplate($id) {

		if (is_numeric($id)) {
			Logging::info("template id is: $id");
			$template = $this->loadTemplate($id);
		}
		else {
			Logging::info("Using default template");
			$template = $this->defaultItemTemplate();
		}

		return $template;
	}

	public function getTemplates($type) {

		$list = array();

		try {

			$query =  CcPlayoutHistoryTemplateQuery::create()
				->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);

			if (isset($type)) {
				$templates = $query->findByDbType($type);
			}
			else {
				$templates = $query->find();
			}

			foreach ($templates as $template) {
				$list[$template->getDbId()] = $template->getDbName();
			}

			return $list;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getListItemTemplates() {
		return $this->getTemplates(self::TEMPLATE_TYPE_ITEM);
	}

	public function getFileTemplates() {
		return $this->getTemplates(self::TEMPLATE_TYPE_FILE);
	}

	private function datatablesColumns($fields, $sortable=false) {

		$columns = array();

		foreach ($fields as $field) {

			$label = $field["label"];
			$key = $field["name"];

			$columns[] = array(
				"sTitle"=> $label,
				"mDataProp"=> $key,
				"sClass"=> "his_{$key}",
				"sDataType"=> $field["type"],
				"bSortable"=> $sortable
			);
		}

		return $columns;
	}

	public function getDatatablesLogSheetColumns() {

	    //need to prepend a checkbox column.
	    $checkbox = array(
            "sTitle"=> "",
			"mDataProp"=> "checkbox",
			"sClass"=> "his_checkbox",
	        "bSortable"=> false
	    );

		try {
			$template = $this->getConfiguredItemTemplate();
			$fields = $template["fields"];

			$columns = $this->datatablesColumns($fields);
			array_unshift($columns, $checkbox);

			return $columns;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getDatatablesFileSummaryColumns() {

		try {
			$template = $this->getConfiguredFileTemplate();
			return $this->datatablesColumns($template["fields"], true);
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getConfiguredItemTemplate() {

		try {
			$id = Application_Model_Preference::GetHistoryItemTemplate();

			if (is_numeric($id)) {
				$template = $this->loadTemplate($id);
			}
			else {
				$template = $this->defaultItemTemplate();
			}
			return $template;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function setConfiguredItemTemplate($id) {
		try {
			Application_Model_Preference::SetHistoryItemTemplate($id);
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getConfiguredFileTemplate() {

		try {
			$id = Application_Model_Preference::GetHistoryFileTemplate();

			if (is_numeric($id)) {
				$template = $this->loadTemplate($id);
			}
			else {
				$template = $this->defaultFileTemplate();
			}
			return $template;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function setConfiguredFileTemplate($id) {
		try {
			Application_Model_Preference::SetHistoryFileTemplate($id);
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function setConfiguredTemplate($id) {
		try {

			$template = $this->loadTemplate($id);
			$type = $template["type"];

			$setTemplate = "setConfigured".ucfirst($type)."Template";

			$this->$setTemplate($id);
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getConfiguredTemplateIds() {

		try {
			$id = Application_Model_Preference::GetHistoryItemTemplate();
			$id2 = Application_Model_Preference::GetHistoryFileTemplate();

			$configured = array();

			if (is_numeric($id)) {
				$configured[] = $id;
			}

			if (is_numeric($id2)) {
				$configured[] = $id2;
			}

			return $configured;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function createTemplate($config) {

		$this->con->beginTransaction();

		try {

			$type = $config["type"];

			$method = "default".ucfirst($type)."Template";
			$default = $this->$method();

			$name = isset($config["name"]) ? $config["name"] : $default["name"];
			$fields = isset($config["fields"]) ? $config["fields"] : $default["fields"];

			$doSetDefault = isset($config['setDefault']) ? $config['setDefault'] : false;

			$template = new CcPlayoutHistoryTemplate();
			$template->setDbName($name);
			$template->setDbType($type);

			foreach ($fields as $index=>$field) {

				$isMd = ($field["isFileMd"] == 'true') ? true : false;

				$templateField = new CcPlayoutHistoryTemplateField();
				$templateField->setDbName($field["name"]);
				$templateField->setDbLabel($field["label"]);
				$templateField->setDbType($field["type"]);
				$templateField->setDbIsFileMD($isMd);
				$templateField->setDbPosition($index);

				$template->addCcPlayoutHistoryTemplateField($templateField);
			}

			$template->save($this->con);

			if ($doSetDefault) {
				$this->setConfiguredItemTemplate($template->getDbid());
			}

			$this->con->commit();

			return $template->getDbid();
		}
		catch (Exception $e) {
			$this->con->rollback();
			throw $e;
		}
	}

	public function updateItemTemplate($id, $name, $fields, $doSetDefault=false) {

		$this->con->beginTransaction();

		try {

			$template = CcPlayoutHistoryTemplateQuery::create()->findPk($id, $this->con);
			$template->setDbName($name);

			if (count($fields) === 0) {
				$t = $this->defaultItemTemplate();
				$fields = $t["fields"];
			}

			$template->getCcPlayoutHistoryTemplateFields()->delete($this->con);

			foreach ($fields as $index=>$field) {

				$isMd = ($field["isFileMd"] == 'true') ? true : false;

				$templateField = new CcPlayoutHistoryTemplateField();
				$templateField->setDbName($field["name"]);
				$templateField->setDbType($field["type"]);
				$templateField->setDbLabel($field["label"]);
				$templateField->setDbIsFileMD($isMd);
				$templateField->setDbPosition($index);

				$template->addCcPlayoutHistoryTemplateField($templateField);
			}

			$template->save($this->con);

			if ($doSetDefault) {
				$this->setConfiguredItemTemplate($template->getDbid());
			}

			$this->con->commit();
		}
		catch (Exception $e) {
			$this->con->rollback();
			throw $e;
		}
	}

	public function deleteTemplate($id) {

		$this->con->beginTransaction();

		try {

			$template = CcPlayoutHistoryTemplateQuery::create()->findPk($id, $this->con);
			$template->delete($this->con);

		    $this->con->commit();
		}
		catch (Exception $e) {
			$this->con->rollback();
			throw $e;
		}
	}
}
