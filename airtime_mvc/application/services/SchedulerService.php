<?php

use Airtime\CcSchedule;
use Airtime\CcSchedulePeer;
use Airtime\CcScheduleQuery;
use Airtime\CcShowInstancesQuery;
use Airtime\CcFilesPeer;
use Airtime\CcShowInstancesPeer;

class Application_Service_SchedulerService
{
    private $con;
    private $epochNow;
    private $nowDT;

    public function __construct()
    {
        $this->con = Propel::getConnection(CcSchedulePeer::DATABASE_NAME);

        //subtracting one because sometimes when we cancel a track, we set its end time
        //to epochNow and then send the new schedule to pypo. Sometimes the currently cancelled
        //track can still be included in the new schedule because it may have a few ms left to play.
        //subtracting 1 second from epochNow resolves this issue.
        $this->epochNow = microtime(true)-1;
        $this->nowDT = DateTime::createFromFormat("U.u", $this->epochNow, new DateTimeZone("UTC"));

        if ($this->nowDT === false) {
            // DateTime::createFromFormat does not support millisecond string formatting in PHP 5.3.2 (Ubuntu 10.04).
            // In PHP 5.3.3 (Ubuntu 10.10), this has been fixed.
            $this->nowDT = DateTime::createFromFormat("U", time(), new DateTimeZone("UTC"));
        }
    }

    /**
     * 
     * Applies the show start difference to any scheduled items
     * 
     * @param $instanceIds
     * @param $diff (integer, difference between unix epoch in seconds)
     */
    public static function updateScheduleStartTime($instanceIds, $diff)
    {
        $con = Propel::getConnection();
        if (count($instanceIds) > 0) {
            $showIdList = implode(",", $instanceIds);

            $ccSchedules = CcScheduleQuery::create()
                ->filterByDbInstanceId($instanceIds, Criteria::IN)
                ->find($con);

            $interval = new DateInterval("PT".abs($diff)."S");
            if ($diff < 0) {
                $interval->invert = 1;
            }
            foreach ($ccSchedules as $ccSchedule) {
                $start = $ccSchedule->getDbStarts(null);
                $newStart = $start->add($interval);
                $end = $ccSchedule->getDbEnds(null);
                $newEnd = $end->add($interval);
                $ccSchedule
                    ->setDbStarts($newStart)
                    ->setDbEnds($newEnd)
                    ->save($con);
            }
        }
    }

    /**
     * 
     * Removes any time gaps in shows
     * 
     * @param array $schedIds schedule ids to exclude
     */
    public function removeGaps($showId, $schedIds=null)
    {
        $ccShowInstances = CcShowInstancesQuery::create()->filterByDbShowId($showId)->find();

        foreach ($ccShowInstances as $instance) {
            Logging::info("Removing gaps from show instance #".$instance->getDbId());
            //DateTime object
            $itemStart = $instance->getDbStarts(null);

            $ccScheduleItems = CcScheduleQuery::create()
                ->filterByDbInstanceId($instance->getDbId())
                ->filterByDbId($schedIds, Criteria::NOT_IN)
                ->orderByDbStarts()
                ->find();

            foreach ($ccScheduleItems as $ccSchedule) {
                //DateTime object
                $itemEnd = $this->findEndTime($itemStart, $ccSchedule->getDbClipLength());

                $ccSchedule->setDbStarts($itemStart)
                    ->setDbEnds($itemEnd);

                $itemStart = $itemEnd;
            }
            $ccScheduleItems->save();
        }
    }

    /**
     * 
     * Enter description here ...
     * @param DateTime $instanceStart
     * @param string $clipLength
     */
    private static function findEndTime($instanceStart, $clipLength)
    {
        $startEpoch = $instanceStart->format("U.u");
        $durationSeconds = Application_Common_DateHelper::playlistTimeToSeconds($clipLength);

        //add two float numbers to 6 subsecond precision
        //DateTime::createFromFormat("U.u") will have a problem if there is no decimal in the resulting number.
        $endEpoch = bcadd($startEpoch , (string) $durationSeconds, 6);

        $dt = DateTime::createFromFormat("U.u", $endEpoch, new DateTimeZone("UTC"));

        if ($dt === false) {
            //PHP 5.3.2 problem
            $dt = DateTime::createFromFormat("U", intval($endEpoch), new DateTimeZone("UTC"));
        }

        return $dt;
    }

    private static function findTimeDifference($p_startDT, $p_seconds)
    {
    	$startEpoch = $p_startDT->format("U.u");
    	
    	//add two float numbers to 6 subsecond precision
    	//DateTime::createFromFormat("U.u") will have a problem if there is no decimal in the resulting number.
    	$newEpoch = bcsub($startEpoch , (string) $p_seconds, 6);
    
    	$dt = DateTime::createFromFormat("U.u", $newEpoch, new DateTimeZone("UTC"));
    
    	if ($dt === false) {
    		//PHP 5.3.2 problem
    		$dt = DateTime::createFromFormat("U", intval($newEpoch), new DateTimeZone("UTC"));
    	}
    
    	return $dt;
    }

    public static function getLinkedShowSchedule($showId, $instancsIdsToFill)
    {
        $showsPopulatedUntil = Application_Model_Preference::GetShowsPopulatedUntil();

        $showInstanceWithMostRecentSchedule = CcShowInstancesQuery::create()
            ->filterByDbShowId($showId)
            ->filterByDbStarts($showsPopulatedUntil->format("Y-m-d H:i:s"), Criteria::LESS_THAN)
            ->filterByDbId($instancsIdsToFill, Criteria::NOT_IN)
            ->orderByDbStarts(Criteria::DESC)
            ->limit(1)
            ->findOne();

        $con = Propel::getConnection();
        $instanceId = $showInstanceWithMostRecentSchedule->getDbId();
        $linkedShowSchedule_sql = $con->prepare(
            "select * from cc_schedule where instance_id = :instance_id ".
            "order by starts");
        $linkedShowSchedule_sql->bindParam(':instance_id', $instanceId);
        $linkedShowSchedule_sql->execute();
        
        return $linkedShowSchedule_sql->fetchAll();
    }
    
    /**
     * 
     * Enter description here ...
     * @param CcShow_type $ccShow
     * @param array $instanceIdsToFill ids of the new linked cc_show_instances that
     * were created and now need their schedules filled
     */
    public static function fillNewLinkedInstances($ccShow, $instanceIdsToFill)
    {
        //TODO can we remove the code until line 216 ??
        
        $instanceIds = $ccShow->getInstanceIdsSortedByMostRecentStartTime();
        if (count($instanceIds) == 0) {
            return;
        }

        /* First check if any linked instances have content
         * If all instances are empty then we don't need to fill
         * any other instances with content
         */
       $doesAnyShowInstanceHaveContent = false;
       foreach ($instanceIds as $instanceId)
       {
            $schedule_sql = "SELECT instance_id FROM cc_schedule ".
                "WHERE instance_id=$instanceId";//#IN (".implode($instanceIds, ",").")";
            $ccSchedules = Application_Common_Database::prepareAndExecute(
                $schedule_sql);
            if (count($ccSchedules) > 0) {
                $doesAnyShowInstanceHaveContent = true;
                break;
            }
       }
       //variable out of scope outside foreach loop
       unset($ccSchedules);

       if ($doesAnyShowInstanceHaveContent == false)
       {
            //The linked shows are all empty, so there's nothing for us to do.
            //(No content should be propagated to the other show instances...
            return;
       }

        $linkedShowSchedule = self::getLinkedShowSchedule($ccShow->getDbId(), $instanceIdsToFill);

        //get time_filled so we can update cc_show_instances
        if (!empty($linkedShowSchedule)) {
            $timeFilled_sql = "SELECT time_filled FROM cc_show_instances ".
               "WHERE id = {$linkedShowSchedule[0]["instance_id"]}";
            $timeFilled = Application_Common_Database::prepareAndExecute(
               $timeFilled_sql, array(), Application_Common_Database::COLUMN);
        } else {
            $timeFilled = "00:00:00";
        }
    
        //need to find out which linked instances are empty
        $values = array();
        //pass in new criteria object so propel doesn't return cached results
        //$futureInstanceIds = $ccShow->getFutureInstanceIds(new Criteria());

        $con = Propel::getConnection();
        try {
            $con->beginTransaction();
            //foreach ($futureInstanceIds as $id)
            foreach ($instanceIdsToFill as $id) 
            {
               $instanceSched_sql = "SELECT * FROM cc_schedule ".
                   "WHERE instance_id = {$id} ".
                   "ORDER by starts";

               $showInstanceContents = Application_Common_Database::prepareAndExecute(
                   $instanceSched_sql);

               /* If the show instance is empty OR it has different content than
                * the most recent instance, we need to fill/replace with the linked
                * show schedule
                */
               if (count($showInstanceContents) < 1 || 
                   self::replaceInstanceContentCheck($showInstanceContents, $linkedShowSchedule, $id)) 
                {

                   $instanceStart_sql = "SELECT starts FROM cc_show_instances ".
                       "WHERE id = {$id} ".
                       "ORDER BY starts";
                   $nextStartDT = new DateTime(
                       Application_Common_Database::prepareAndExecute(
                           $instanceStart_sql, array(), Application_Common_Database::COLUMN),
                       new DateTimeZone("UTC"));

                   $defaultCrossfadeDuration = Application_Model_Preference::GetDefaultCrossfadeDuration();
                   unset($values);
                   $values = array();
                   foreach ($linkedShowSchedule as $item) {
                       $endTimeDT = self::findEndTime($nextStartDT, $item["clip_length"]);

                       if (is_null($item["file_id"])) {
                           $item["file_id"] = "null";
                       } 
                       if (is_null($item["stream_id"])) {
                           $item["stream_id"] = "null";
                       }

                       $values[] = "(".
                           "'{$nextStartDT->format("Y-m-d H:i:s")}', ".
                           "'{$endTimeDT->format("Y-m-d H:i:s")}', ".
                           "'{$item["clip_length"]}', ".
                           "'{$item["fade_in"]}', ".
                           "'{$item["fade_out"]}', ".
                           "'{$item["cue_in"]}', ".
                           "'{$item["cue_out"]}', ".
                           "{$item["file_id"]}, ".
                           "{$item["stream_id"]}, ".
                           "{$id}, ".
                           "{$item["position"]})";

                       $nextStartDT = self::findTimeDifference($endTimeDT,
                           $defaultCrossfadeDuration);

                   } //foreach show item

                    if (!empty($values)) {
                        $insert_sql = "INSERT INTO cc_schedule (starts, ends, ".
                            "clip_length, fade_in, fade_out, cue_in, cue_out, ".
                            "file_id, stream_id, instance_id, position)  VALUES ".
                            implode($values, ",");
                        Application_Common_Database::prepareAndExecute(
                            $insert_sql, array(), Application_Common_Database::EXECUTE);
                    }
               }
               //update cc_schedule status column
               $instance = CcShowInstancesQuery::create()->findPk($id);
               $instance->updateScheduleStatus($con);
           } //foreach linked instance

            //update time_filled and last_scheduled in cc_show_instances
            $now = gmdate("Y-m-d H:i:s");
            $whereClause = new Criteria();
            $whereClause->add(CcShowInstancesPeer::ID, $instanceIdsToFill, Criteria::IN);
            
            $updateClause = new Criteria();
            $updateClause->add(CcShowInstancesPeer::TIME_FILLED, $timeFilled);
            $updateClause->add(CcShowInstancesPeer::LAST_SCHEDULED, $now);
            
            BasePeer::doUpdate($whereClause, $updateClause, $con);

           $con->commit();
           Logging::info("finished fill");
        } catch (Exception $e) {
            $con->rollback();
            Logging::info("Error filling linked shows: ".$e->getMessage());
            exit();
        }
    }

    public static function fillPreservedLinkedShowContent($ccShow, $showStamp)
    {
        $item = $showStamp->getFirst();
        $timeFilled = $item->getCcShowInstances()->getDbTimeFilled();

        foreach ($ccShow->getCcShowInstancess() as $ccShowInstance) {
            $ccSchedules = CcScheduleQuery::create()
                ->filterByDbInstanceId($ccShowInstance->getDbId())
                ->find();

            if ($ccSchedules->isEmpty()) {

                $nextStartDT = $ccShowInstance->getDbStarts(null);

                foreach ($showStamp as $item) {
                    $endTimeDT = self::findEndTime($nextStartDT, $item->getDbClipLength());

                    $ccSchedule = new CcSchedule();
                    $ccSchedule
                        ->setDbStarts($nextStartDT)
                        ->setDbEnds($endTimeDT)
                        ->setDbFileId($item->getDbFileId())
                        ->setDbStreamId($item->getDbStreamId())
                        ->setDbClipLength($item->getDbClipLength())
                        ->setDbFadeIn($item->getDbFadeIn())
                        ->setDbFadeOut($item->getDbFadeOut())
                        ->setDbCuein($item->getDbCueIn())
                        ->setDbCueOut($item->getDbCueOut())
                        ->setDbInstanceId($ccShowInstance->getDbId())
                        ->setDbPosition($item->getDbPosition())
                        ->save();

                    $nextStartDT = self::findTimeDifference($endTimeDT,
                        Application_Model_Preference::GetDefaultCrossfadeDuration());
                } //foreach show item

                $ccShowInstance
                    ->setDbTimeFilled($timeFilled)
                    ->setDbLastScheduled(gmdate("Y-m-d H:i:s"))
                    ->save();
            }
        }
    }

    private static function replaceInstanceContentCheck($currentShowStamp, $showStamp, $instance_id)
    {
        $counter = 0;
        $erraseShow = false;
        if (count($currentShowStamp) != count($showStamp)) {
            $erraseShow = true;
        } else {
            foreach ($showStamp as $item) {
                if ($item["file_id"] != $currentShowStamp[$counter]["file_id"] ||
                    $item["stream_id"] != $currentShowStamp[$counter]["stream_id"]) {
                        $erraseShow = true;
                        break;
                    /*CcScheduleQuery::create()
                        ->filterByDbInstanceId($ccShowInstance->getDbId())
                        ->delete();*/
                 }
                 $counter += 1;
            }
        }
        if ($erraseShow) {
            $delete_sql = "DELETE FROM cc_schedule ".
                    "WHERE instance_id = {$instance_id}";
            Application_Common_Database::prepareAndExecute(
            $delete_sql, array(), Application_Common_Database::EXECUTE);
            return true;
        }

        /* If we get here, the content in the show instance is the same
         * as what we want to replace it with, so we can leave as is
         */
        return false;
    }

    public function emptyShowContent($instanceId)
    {
        try {
            $ccShowInstance = CcShowInstancesQuery::create()->findPk($instanceId);

            $instances = array();
            $instanceIds = array();

            if ($ccShowInstance->getCcShow()->isLinked()) {
                foreach ($ccShowInstance->getCcShow()->getFutureCcShowInstancess() as $instance) {
                    $instanceIds[] = $instance->getDbId();
                    $instances[] = $instance;
                }
            } else {
                $instanceIds[] = $ccShowInstance->getDbId();
                $instances[] = $ccShowInstance;
            }

            /* Get the file ids of the tracks we are about to delete
             * from cc_schedule. We need these so we can update the
             * is_scheduled flag in cc_files
             */
            $ccSchedules = CcScheduleQuery::create()
                ->filterByDbInstanceId($instanceIds, Criteria::IN)
                ->setDistinct(CcSchedulePeer::FILE_ID)
                ->find();
            $fileIds = array();
            foreach ($ccSchedules as $ccSchedule) {
                $fileIds[] = $ccSchedule->getDbFileId();
            }

            /* Clear out the schedule */
            CcScheduleQuery::create()
                ->filterByDbInstanceId($instanceIds, Criteria::IN)
                ->delete();

            /* Now that the schedule has been cleared we need to make
             * sure we do not update the is_scheduled flag for tracks
             * that are scheduled in other shows
             */
            $futureScheduledFiles = Application_Model_Schedule::getAllFutureScheduledFiles();
            foreach ($fileIds as $k => $v) {
                if (in_array($v, $futureScheduledFiles)) {
                    unset($fileIds[$k]);
                }
            }

            $selectCriteria = new Criteria();
            $selectCriteria->add(CcFilesPeer::ID, $fileIds, Criteria::IN);
            $updateCriteria = new Criteria();
            $updateCriteria->add(CcFilesPeer::IS_SCHEDULED, false);
            BasePeer::doUpdate($selectCriteria, $updateCriteria, Propel::getConnection());

            Application_Model_RabbitMq::PushSchedule();
            $con = Propel::getConnection(CcShowInstancesPeer::DATABASE_NAME);
            foreach ($instances as $instance) {
                $instance->updateDbTimeFilled($con);
            }

            return true;
        } catch (Exception $e) {
            Logging::info($e->getMessage());
            return false;
        }
    }
    
    /*
     * TODO in the future this should probably support webstreams.
     */
    public function updateFutureIsScheduled($scheduleId, $status) 
    {
    	$sched = CcScheduleQuery::create()->findPk($scheduleId);
    	$redraw = false;
    	
    	if (isset($sched)) {
    		
    		$fileId = $sched->getDbFileId();
    		
    		if (isset($fileId)) {
    			$redraw = Application_Model_StoredFile::setIsScheduled($fileId, $status);
    		}
    	}
    	
    	return $redraw;
    }
}
