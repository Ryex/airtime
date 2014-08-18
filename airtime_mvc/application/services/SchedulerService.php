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

    public static function fillNewLinkedInstances($ccShow)
    {
        /* In order to get the linked show's schedule we need to retrieve
         * every instance of the show, even if they are in the past in case
         * no new instances were generated past the 'shows_populated_until'
         * date in cc_pref - CC-5898
         * 
         * We retrieve the instances ids sorted by desc start date to ensure
         * we always use the most up to date schedule when filling the new
         * show instances with content
         */
        
        $instanceIds = $ccShow->getInstanceIdsSortedByMostRecentStartTime();
        $mostRecentInstanceId = $instanceIds[0];
        
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

        /* Find the show contents of just one of the instances. Because we
         * sorted the instances by desc order, we are using the most recent
         * instance, which will have the most up to date schedule.
         */
        $showStamp_sql = "SELECT * FROM cc_schedule ".
           "WHERE instance_id = $mostRecentInstanceId ".
           "ORDER BY starts";
        $showStamp = Application_Common_Database::prepareAndExecute(
           $showStamp_sql);
        //get time_filled so we can update cc_show_instances
        $timeFilled_sql = "SELECT time_filled FROM cc_show_instances ".
           "WHERE id = $mostRecentInstanceId";
        $timeFilled = Application_Common_Database::prepareAndExecute(
           $timeFilled_sql, array(), Application_Common_Database::COLUMN);
    
        //need to find out which linked instances are empty
        $values = array();
        $futureInstanceIds = $ccShow->getFutureInstanceIds();
        $con = Propel::getConnection();
        try {
            $con->beginTransaction();
            foreach ($futureInstanceIds as $id) 
            {
               $instanceSched_sql = "SELECT * FROM cc_schedule ".
                   "WHERE instance_id = {$id} ".
                   "ORDER by starts";

               $showInstanceContents = Application_Common_Database::prepareAndExecute(
                   $instanceSched_sql);

               /* If the show instance is empty OR it has different content than
                * the first instance, we need to fill/replace with the show stamp
                * (The show stamp is taken from the first show instance's content)
                */
               if (count($showInstanceContents) < 1 || 
                   self::replaceInstanceContentCheck($showInstanceContents, $showStamp, $id)) 
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
                   foreach ($showStamp as $item) {
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
           } //foreach linked instance

            //update time_filled in cc_show_instances
            $now = gmdate("Y-m-d H:i:s");
            $update_sql = "UPDATE cc_show_instances SET ".
                "time_filled = '{$timeFilled}', ".
                "last_scheduled = '{$now}' ".
                "WHERE show_id = {$ccShow->getDbId()}";
            Application_Common_Database::prepareAndExecute(
                $update_sql, array(), Application_Common_Database::EXECUTE);

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
