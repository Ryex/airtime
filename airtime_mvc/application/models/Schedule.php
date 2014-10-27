<?php

use Airtime\CcScheduleQuery;
use Airtime\CcShowInstancesQuery;

class Application_Model_Schedule
{

    /**
     * Return TRUE if file is going to be played in the future.
     *
     * @param string $p_fileId
     */
    public static function IsFileScheduledInTheFuture($p_fileId)
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM cc_schedule
WHERE file_id = :file_id
  AND ends > NOW() AT TIME ZONE 'UTC'
SQL;
        $count = Application_Common_Database::prepareAndExecute( $sql, array(
            ':file_id'=>$p_fileId), 'column');
        return (is_numeric($count) && ($count != '0'));
    }

    public static function getAllFutureScheduledFiles($instanceId=null)
    {
        $sql = <<<SQL
SELECT distinct(file_id)
FROM cc_schedule
WHERE ends > now() AT TIME ZONE 'UTC'
AND file_id is not null
SQL;

        $files = Application_Common_Database::prepareAndExecute( $sql, array());

        $real_files = array();
        foreach ($files as $f) {
            $real_files[] = $f['file_id'];
        }

        return $real_files;
    }

    public static function getAllFutureScheduledWebstreams()
    {
        $sql = <<<SQL
SELECT distinct(stream_id)
FROM cc_schedule
WHERE ends > now() AT TIME ZONE 'UTC'
AND stream_id is not null
SQL;
        $streams = Application_Common_Database::prepareAndExecute( $sql, array());

        $real_streams = array();
        foreach ($streams as $s) {
            $real_streams[] = $s['stream_id'];
        }

        return $real_streams;
    }

    /**
     * Returns data related to the scheduled items.
     */
    public static function GetPlayOrderRange($utcTimeEnd = null, $showsToRetrieve = 5)
    {
        // Everything in this function must be done in UTC. You will get a swift kick in the pants if you mess that up.

        // when timeEnd is unspecified, return to the default behaviour - set a range of 48 hours from current time
        if (!$utcTimeEnd) {
            $end = new DateTime();
            $end->add(new DateInterval("P2D")); // Add 2 days
            $end->setTimezone(new DateTimeZone("UTC"));
            $utcTimeEnd = $end->format("Y-m-d H:i:s");
        }

        $utcNow = new DateTime("now", new DateTimeZone("UTC"));

        $shows = Application_Model_Show::getPrevCurrentNext($utcNow, $utcTimeEnd, $showsToRetrieve);
        $previousShowID = count($shows['previousShow'])>0?$shows['previousShow'][0]['instance_id']:null;
        $currentShowID = count($shows['currentShow'])>0?$shows['currentShow']['instance_id']:null;
        $nextShowID = count($shows['nextShow'])>0?$shows['nextShow'][0]['instance_id']:null;
        $results = self::GetPrevCurrentNext($previousShowID, $currentShowID, $nextShowID, $utcNow);

        $range = array(
            "station" => array (
                "env"           => APPLICATION_ENV,
                "schedulerTime" => $utcNow->format("Y-m-d H:i:s")
            ),
            //Previous, current, next songs!
            "tracks" => array(
                "previous"  => $results['previous'],
                "current"   => $results['current'],
                "next"      => $results['next']
            ),
            //Current and next shows
            "shows" => array (
                "previous"  => $shows['previousShow'],
                "current"   => $shows['currentShow'],
                "next"      => $shows['nextShow']
            )
        );

        return $range;
    }

    /**
     * Old version of the function for backwards compatibility
     * @deprecated
     */
    public static function GetPlayOrderRangeOld()
    {
        // Everything in this function must be done in UTC. You will get a swift kick in the pants if you mess that up.

        $utcNow = new DateTime("now", new DateTimeZone("UTC"));

        $shows = Application_Model_Show::getPrevCurrentNextOld($utcNow);
        $previousShowID = count($shows['previousShow'])>0?$shows['previousShow'][0]['instance_id']:null;
        $currentShowID = count($shows['currentShow'])>0?$shows['currentShow'][0]['instance_id']:null;
        $nextShowID = count($shows['nextShow'])>0?$shows['nextShow'][0]['instance_id']:null;
        $results = self::GetPrevCurrentNext($previousShowID, $currentShowID, $nextShowID, $utcNow);

        $range = array(
                "env" => APPLICATION_ENV,
                "schedulerTime" => $utcNow->format("Y-m-d H:i:s"),
                //Previous, current, next songs!
                "previous"=>$results['previous'] !=null?$results['previous']:(count($shows['previousShow'])>0?$shows['previousShow'][0]:null),
                "current"=>$results['current'] !=null?$results['current']:((count($shows['currentShow'])>0 && $shows['currentShow'][0]['record'] == 1)?$shows['currentShow'][0]:null),
                "next"=> $results['next'] !=null?$results['next']:(count($shows['nextShow'])>0?$shows['nextShow'][0]:null),
                //Current and next shows
                "currentShow"=>$shows['currentShow'],
                "nextShow"=>$shows['nextShow']
        );

        return $range;
    }

    /**
     * Queries the database for the set of schedules one hour before
     * and after the given time. If a show starts and ends within that
     * time that is considered the current show. Then the scheduled item
     * before it is the previous show, and the scheduled item after it
     * is the next show. This way the dashboard getCurrentPlaylist is
     * very fast. But if any one of the three show types are not found
     * through this mechanism a call is made to the old way of querying
     * the database to find the track info.
    **/
    public static function GetPrevCurrentNext($p_previousShowID, $p_currentShowID, $p_nextShowID, $utcNow)
    {
        $timeZone = new DateTimeZone("UTC"); //This function works entirely in UTC.
        assert(get_class($utcNow) === "DateTime");
        assert($utcNow->getTimeZone() == $timeZone);

        if ($p_previousShowID == null && $p_currentShowID == null && $p_nextShowID == null) {
            return;
        }

        $sql = "SELECT %%columns%% st.starts as starts, st.ends as ends,
            st.media_item_played as media_item_played, si.ends as show_ends
            %%tables%% WHERE ";

        $fileColumns = "ft.artist_name, ft.track_title, ";
        $fileJoin = "FROM cc_schedule st JOIN cc_files ft ON st.file_id = ft.id
            LEFT JOIN cc_show_instances si ON st.instance_id = si.id";

        $streamColumns = "ws.name AS artist_name, wm.liquidsoap_data AS track_title, ";
        $streamJoin = <<<SQL
FROM cc_schedule AS st
JOIN cc_webstream ws ON st.stream_id = ws.id
LEFT JOIN cc_show_instances AS si ON st.instance_id = si.id
LEFT JOIN cc_subjs AS sub ON sub.id = ws.creator_id
LEFT JOIN
  (SELECT *
   FROM cc_webstream_metadata
   ORDER BY start_time DESC LIMIT 1) AS wm ON st.id = wm.instance_id
SQL;

        $predicateArr = array();
        $paramMap = array();
        if (isset($p_previousShowID)) {
            $predicateArr[] = 'st.instance_id = :previousShowId';
            $paramMap[':previousShowId'] = $p_previousShowID;
        }
        if (isset($p_currentShowID)) {
            $predicateArr[] = 'st.instance_id = :currentShowId';
            $paramMap[':currentShowId'] = $p_currentShowID;
        }
        if (isset($p_nextShowID)) {
            $predicateArr[] = 'st.instance_id = :nextShowId';
            $paramMap[':nextShowId'] = $p_nextShowID;
        }

        $sql .= " (".implode(" OR ", $predicateArr).") ";
        $sql .= ' AND st.playout_status > 0 ORDER BY st.starts';

        $filesSql = str_replace("%%columns%%", $fileColumns, $sql);
        $filesSql = str_replace("%%tables%%", $fileJoin, $filesSql);

        $streamSql = str_replace("%%columns%%", $streamColumns, $sql);
        $streamSql = str_replace("%%tables%%", $streamJoin, $streamSql);

        $sql = "SELECT * FROM (($filesSql) UNION ($streamSql)) AS unioned ORDER BY starts";

        $rows = Application_Common_Database::prepareAndExecute($sql, $paramMap);
        $numberOfRows = count($rows);

        $results['previous'] = null;
        $results['current']  = null;
        $results['next']     = null;

        for ($i = 0; $i < $numberOfRows; ++$i) {

            // if the show is overbooked, then update the track end time to the end of the show time.
            if ($rows[$i]['ends'] > $rows[$i]["show_ends"]) {
                $rows[$i]['ends'] = $rows[$i]["show_ends"];
            }

            $curShowStartTime = new DateTime($rows[$i]['starts'], $timeZone);
            $curShowEndTime   = new DateTime($rows[$i]['ends'], $timeZone);

            if (($curShowStartTime <= $utcNow) && ($curShowEndTime >= $utcNow)) {
                if ($i - 1 >= 0) {
                    $results['previous'] = array("name"=>$rows[$i-1]["artist_name"]." - ".$rows[$i-1]["track_title"],
                            "starts"=>$rows[$i-1]["starts"],
                            "ends"=>$rows[$i-1]["ends"],
                            "type"=>'track');
                }
                 $results['current'] =  array("name"=>$rows[$i]["artist_name"]." - ".$rows[$i]["track_title"],
                            "starts"=>$rows[$i]["starts"],
                            "ends"=> (($rows[$i]["ends"] > $rows[$i]["show_ends"]) ? $rows[$i]["show_ends"]: $rows[$i]["ends"]),
                            "media_item_played"=>$rows[$i]["media_item_played"],
                            "record"=>0,
                            "type"=>'track');
                if (isset($rows[$i+1])) {
                    $results['next'] =  array("name"=>$rows[$i+1]["artist_name"]." - ".$rows[$i+1]["track_title"],
                            "starts"=>$rows[$i+1]["starts"],
                            "ends"=>$rows[$i+1]["ends"],
                            "type"=>'track');
                }
                break;
            }
            if ($curShowEndTime < $utcNow ) {
                $previousIndex = $i;
            }
            if ($curShowStartTime > $utcNow) {
                $results['next'] = array("name"=>$rows[$i]["artist_name"]." - ".$rows[$i]["track_title"],
                            "starts"=>$rows[$i]["starts"],
                            "ends"=>$rows[$i]["ends"],
                            "type"=>'track');
                break;
            }
        }
        //If we didn't find a a current show because the time didn't fit we may still have
        //found a previous show so use it.
        if ($results['previous'] === null && isset($previousIndex)) {
                $results['previous'] = array("name"=>$rows[$previousIndex]["artist_name"]." - ".$rows[$previousIndex]["track_title"],
                            "starts"=>$rows[$previousIndex]["starts"],
                            "ends"=>$rows[$previousIndex]["ends"]);;
        }

        return $results;
    }

    public static function GetLastScheduleItem($p_timeNow)
    {
        $sql = <<<SQL
SELECT ft.artist_name,
       ft.track_title,
       st.starts AS starts,
       st.ends AS ends
FROM cc_schedule st
LEFT JOIN cc_files ft ON st.file_id = ft.id
LEFT JOIN cc_show_instances sit ON st.instance_id = sit.id
-- this and the next line are necessary since we can overbook shows.
WHERE st.ends < TIMESTAMP :timeNow

  AND st.starts >= sit.starts
  AND st.starts < sit.ends
ORDER BY st.ends DESC LIMIT 1;
SQL;
        $row = Application_Common_Database::prepareAndExecute($sql, array(':timeNow'=>$p_timeNow));

        return $row;
    }

    public static function GetCurrentScheduleItem($p_timeNow, $p_instanceId)
    {
        /* Note that usually there will be one result returned. In some
         * rare cases two songs are returned. This happens when a track
         * that was overbooked from a previous show appears as if it
         * hasnt ended yet (track end time hasn't been reached yet). For
         * this reason,  we need to get the track that starts later, as
         * this is the *real* track that is currently playing. So this
         * is why we are ordering by track start time. */
        $sql = "SELECT *"
        ." FROM cc_schedule st"
        ." LEFT JOIN cc_files ft"
        ." ON st.file_id = ft.id"
        ." WHERE st.starts <= TIMESTAMP :timeNow1"
        ." AND st.instance_id = :instanceId"
        ." AND st.ends > TIMESTAMP :timeNow2"
        ." ORDER BY st.starts DESC"
        ." LIMIT 1";

        $row = Application_Common_Database::prepareAndExecute($sql, array(':timeNow1'=>$p_timeNow, ':instanceId'=>$p_instanceId, ':timeNow2'=>$p_timeNow,));

        return $row;
    }

    public static function GetNextScheduleItem($p_timeNow)
    {
        $sql = "SELECT"
        ." ft.artist_name, ft.track_title,"
        ." st.starts as starts, st.ends as ends"
        ." FROM cc_schedule st"
        ." LEFT JOIN cc_files ft"
        ." ON st.file_id = ft.id"
        ." LEFT JOIN cc_show_instances sit"
        ." ON st.instance_id = sit.id"
        ." WHERE st.starts > TIMESTAMP :timeNow"
        ." AND st.starts >= sit.starts" //this and the next line are necessary since we can overbook shows.
        ." AND st.starts < sit.ends"
        ." ORDER BY st.starts"
        ." LIMIT 1";

        $row = Application_Common_Database::prepareAndExecute($sql, array(':timeNow'=>$p_timeNow));

        return $row;
    }
  
    /*
     *
     * @param DateTime $start in UTC timezone
     * @param DateTime $end in UTC timezone
     *
     * @return array $scheduledItems
     *
     */
    public static function GetScheduleDetailItems($start, $end, $getOnlyPlayable = false,
    		$showIds = array(), $showInstanceIds = array())
    {
        $p_start_str = $p_start->format("Y-m-d H:i:s");
        $p_end_str = $p_end->format("Y-m-d H:i:s");

        //We need to search 48 hours before and after the show times so that that we
        //capture all of the show's contents.
        $p_track_start= $p_start->sub(new DateInterval("PT48H"))->format("Y-m-d H:i:s");
        $p_track_end = $p_end->add(new DateInterval("PT48H"))->format("Y-m-d H:i:s");

        $templateSql = <<<SQL
SELECT DISTINCT sched.starts AS sched_starts,
                sched.ends AS sched_ends,
                sched.id AS sched_id,
                sched.cue_in AS cue_in,
                sched.cue_out AS cue_out,
                sched.fade_in AS fade_in,
                sched.fade_out AS fade_out,
                sched.playout_status AS playout_status,
                sched.instance_id AS sched_instance_id,

                %%columns%%
                FROM (%%join%%)
SQL;

        $filesColumns = <<<SQL
                ft.track_title AS file_track_title,
                ft.artist_name AS file_artist_name,
                ft.album_title AS file_album_title,
                ft.length AS file_length,
                ft.file_exists AS file_exists,
                ft.mime AS file_mime,
                ft.soundcloud_id AS soundcloud_id
SQL;
        $filesJoin = <<<SQL
       cc_schedule AS sched
       JOIN cc_files AS ft ON (sched.file_id = ft.id
           AND ((sched.starts >= :fj_ts_1
               AND sched.starts < :fj_ts_2)
               OR (sched.ends > :fj_ts_3
               AND sched.ends <= :fj_ts_4)
               OR (sched.starts <= :fj_ts_5
               AND sched.ends >= :fj_ts_6))
        )
SQL;
        $paramMap = array(
        	":fj_ts_1" => $p_track_start,
        	":fj_ts_2" => $p_track_end,
        	":fj_ts_3" => $p_track_start,
        	":fj_ts_4" => $p_track_end,
        	":fj_ts_5" => $p_track_start,
        	":fj_ts_6" => $p_track_end,
        );

        $filesSql = str_replace("%%columns%%",
            $filesColumns,
            $templateSql);
        $filesSql= str_replace("%%join%%",
            $filesJoin,
            $filesSql);

        $streamColumns = <<<SQL
                ws.name AS file_track_title,
                sub.login AS file_artist_name,
                ws.description AS file_album_title,
                ws.length AS file_length,
                't'::BOOL AS file_exists,
                ws.mime AS file_mime,
                (SELECT NULL::integer AS soundcloud_id)
SQL;
        $streamJoin = <<<SQL
      cc_schedule AS sched
      JOIN cc_webstream AS ws ON (sched.stream_id = ws.id
          AND ((sched.starts >= :sj_ts_1
               AND sched.starts < :sj_ts_2)
               OR (sched.ends > :sj_ts_3
               AND sched.ends <= :sj_ts_4)
               OR (sched.starts <= :sj_ts_5
               AND sched.ends >= :sj_ts_6))
      )
      LEFT JOIN cc_subjs AS sub ON (ws.creator_id = sub.id)
SQL;
        $map = array(
        	":sj_ts_1" => $p_track_start,
        	":sj_ts_2" => $p_track_end,
        	":sj_ts_3" => $p_track_start,
        	":sj_ts_4" => $p_track_end,
        	":sj_ts_5" => $p_track_start,
        	":sj_ts_6" => $p_track_end,
        );
        $paramMap = $paramMap + $map;

        $streamSql = str_replace("%%columns%%",
            $streamColumns,
            $templateSql);
        $streamSql = str_replace("%%join%%",
            $streamJoin,
            $streamSql);


        $showPredicate = "";
        if (count($p_shows) > 0) {

            $params = array();
            $map = array();

            for ($i = 0, $len = count($p_shows); $i < $len; $i++) {
            	$holder = ":show_".$i;

            	$params[] = $holder;
            	$map[$holder] = $p_shows[$i];
            }

            $showPredicate = " AND show_id IN (".implode(",", $params).")";
            $paramMap = $paramMap + $map;
        } else if (count($p_show_instances) > 0) {
            $showPredicate = " AND si.id IN (".implode(",", $p_show_instances).")";
        }

        $sql = <<<SQL
SELECT showt.name AS show_name,
       showt.color AS show_color,
       showt.background_color AS show_background_color,
       showt.id AS show_id,
       showt.linked AS linked,
       si.starts AS si_starts,
       si.ends AS si_ends,
       si.time_filled AS si_time_filled,
       si.record AS si_record,
       si.rebroadcast AS si_rebroadcast,
       si.instance_id AS parent_show,
       si.id AS si_id,
       si.last_scheduled AS si_last_scheduled,
       si.file_id AS si_file_id,
       *
       FROM (($filesSql) UNION ($streamSql)) as temp
       RIGHT JOIN cc_show_instances AS si ON (si.id = sched_instance_id)
JOIN cc_show AS showt ON (showt.id = si.show_id)
WHERE si.modified_instance = FALSE
  $showPredicate
  AND ((si.starts >= :ts_1
       AND si.starts < :ts_2)
  OR (si.ends > :ts_3
      AND si.ends <= :ts_4)
  OR (si.starts <= :ts_5
      AND si.ends >= :ts_6))
ORDER BY si_starts,
         sched_starts;
SQL;

        $map = array(
        	":ts_1" => $p_start_str,
        	":ts_2" => $p_end_str,
        	":ts_3" => $p_start_str,
        	":ts_4" => $p_end_str,
        	":ts_5" => $p_start_str,
        	":ts_6" => $p_end_str,
        );
        $paramMap = $paramMap + $map;

        $rows = Application_Common_Database::prepareAndExecute(
        	$sql,
        	$paramMap,
        	Application_Common_Database::ALL
        );

        return $rows;
    }

    public static function UpdateMediaPlayedStatus($p_id)
    {
        $sql = "UPDATE cc_schedule"
                ." SET media_item_played=TRUE";
        // we need to update 'broadcasted' column as well
        // check the current switch status
        $live_dj        = Application_Model_Preference::GetSourceSwitchStatus('live_dj')        == 'on';
        $master_dj      = Application_Model_Preference::GetSourceSwitchStatus('master_dj')      == 'on';
        $scheduled_play = Application_Model_Preference::GetSourceSwitchStatus('scheduled_play') == 'on';

        if (!$live_dj && !$master_dj && $scheduled_play) {
            $sql .= ", broadcasted=1";
        }

        $sql .= " WHERE id=:pid";
        $map = array(":pid" => $p_id);

        Application_Common_Database::prepareAndExecute($sql, $map,
            Application_Common_Database::EXECUTE);
    }

    public static function UpdateBrodcastedStatus($dateTime, $value)
    {
        $now = $dateTime->format("Y-m-d H:i:s");

        $sql = <<<SQL
UPDATE cc_schedule
SET broadcasted=:broadcastedValue
WHERE starts <= :starts::TIMESTAMP
  AND ends >= :ends::TIMESTAMP
SQL;

        $retVal = Application_Common_Database::prepareAndExecute($sql, array(
            ':broadcastedValue' => $value,
            ':starts' => $now,
            ':ends' => $now), 'execute');
        return $retVal;
    }

    public static function getSchduledPlaylistCount()
    {
        $sql = "SELECT count(*) as cnt FROM cc_schedule";

        $res = Application_Common_Database::prepareAndExecute($sql, array(),
        		Application_Common_Database::COLUMN);

        return $res;
    }

    /**
     * Convert a time string in the format "YYYY-MM-DD HH:mm:SS"
     * to "YYYY-MM-DD-HH-mm-SS".
     *
     * @param  string $p_time
     * @return string
     */
    public static function AirtimeTimeToPypoTime($p_time)
    {
        $p_time = substr($p_time, 0, 19);
        $p_time = str_replace(" ", "-", $p_time);
        $p_time = str_replace(":", "-", $p_time);

        return $p_time;
    }

    private static function createInputHarborKickTimes(&$data, $range_start, $range_end)
    {
        $utcTimeZone = new DateTimeZone("UTC");
        $kick_times = Application_Model_ShowInstance::GetEndTimeOfNextShowWithLiveDJ($range_start, $range_end);
        foreach ($kick_times as $kick_time_info) {
            $kick_time = $kick_time_info['ends'];
            $temp = explode('.', Application_Model_Preference::GetDefaultTransitionFade());
            // we round down transition time since PHP cannot handle millisecond. We need to
            // handle this better in the future
            $transition_time   = intval($temp[0]);
            $switchOffDataTime = new DateTime($kick_time, $utcTimeZone);
            $switch_off_time   = $switchOffDataTime->sub(new DateInterval('PT'.$transition_time.'S'));
            $switch_off_time   = $switch_off_time->format("Y-m-d H:i:s");

            $kick_start = self::AirtimeTimeToPypoTime($kick_time);
            $data["media"][$kick_start]['start'] = $kick_start;
            $data["media"][$kick_start]['end'] = $kick_start;
            $data["media"][$kick_start]['event_type'] = "kick_out";
            $data["media"][$kick_start]['type'] = "event";
            $data["media"][$kick_start]['independent_event'] = true;

            if ($kick_time !== $switch_off_time) {
                $switch_start = self::AirtimeTimeToPypoTime($switch_off_time);
                $data["media"][$switch_start]['start'] = $switch_start;
                $data["media"][$switch_start]['end'] = $switch_start;
                $data["media"][$switch_start]['event_type'] = "switch_off";
                $data["media"][$switch_start]['type'] = "event";
                $data["media"][$switch_start]['independent_event'] = true;
            }
        }
    }

    private static function getRangeStartAndEnd($p_fromDateTime, $p_toDateTime)
    {
        $CC_CONFIG = Config::getConfig();

        $utcTimeZone = new DateTimeZone('UTC');

        /* if $p_fromDateTime and $p_toDateTime function parameters are null,
            then set range * from "now" to "now + cache_ahead_hours". */
        if (is_null($p_fromDateTime)) {
            $p_fromDateTime = new DateTime("now", $utcTimeZone);
        }
        else {
        	$p_fromDateTime->setTimezone($utcTimeZone);
        }
        if (is_null($p_toDateTime)) {
            $p_toDateTime = clone $p_fromDateTime;

            $cache_ahead_hours = $CC_CONFIG["cache_ahead_hours"];

            if (is_numeric($cache_ahead_hours)) {
                //make sure we are not dealing with a float
                $cache_ahead_hours = intval($cache_ahead_hours);
            }
            else {
                $cache_ahead_hours = 1;
            }

            $p_toDateTime->add(new DateInterval("PT".$cache_ahead_hours."H"));
        }
        else {
        	$p_toDateTime->setTimezone($utcTimeZone);
        }

        return array($p_fromDateTime, $p_toDateTime);
    }


    /*
     * @param array $data output array for events, contains key "media"
     * @param DateTime $startDT UTC start of schedule range
     * @param DateTime $endDT UTC end of schedule range
     */
    private static function createScheduledEvents(&$data, $startDT, $endDT)
    {
        $showInstances = self::GetScheduleDetailItems($startDT, $endDT, true);

        //Logging::info($showInstances);

        foreach ($showInstances as $showInstance) {

        	foreach($showInstance->getCcSchedules() as $scheduleItem) {

        		$event = $scheduleItem->createScheduleEvent($data);
        	}
        }
    }

    public static function getSchedule($p_fromDateTime = null, $p_toDateTime = null)
    {
        //generate repeating shows if we are fetching the schedule
        //for days beyond the shows_populated_until value in cc_pref
        $needScheduleUntil = $p_toDateTime;
        if (is_null($needScheduleUntil)) {
            $needScheduleUntil = new DateTime("now", new DateTimeZone("UTC"));
            $needScheduleUntil->add(new DateInterval("P1D"));
        }

        Application_Model_Show::createAndFillShowInstancesPastPopulatedUntilDate($needScheduleUntil);

        list($range_start, $range_end) = self::getRangeStartAndEnd($p_fromDateTime, $p_toDateTime);

        $data = array();
        $data["media"] = array();

        //Harbor kick times *MUST* be ahead of schedule events, so that pypo
        //executes them first.
        self::createInputHarborKickTimes($data, $startDT->format("Y-m-d H:i:s"), $endDT->format("Y-m-d H:i:s"));
        self::createScheduledEvents($data, $startDT, $endDT);

        //Logging::disablePropelLogging();

        return $data;
    }

    public static function checkOverlappingShows($show_start, $show_end,
        $update=false, $instanceId=null, $showId=null)
    {
        //if the show instance does not exist or was deleted, return false
        if (!is_null($showId)) {
            $ccShowInstance = CcShowInstancesQuery::create()
                ->filterByDbShowId($showId)
                ->filterByDbStarts($show_start->format("Y-m-d H:i:s"))
                ->findOne();
        } elseif (!is_null($instanceId)) {
            $ccShowInstance = CcShowInstancesQuery::create()
                ->filterByDbId($instanceId)
                ->findOne();
        }
        if ($update && ($ccShowInstance && $ccShowInstance->getDbModifiedInstance() == true)) {
            return false;
        }

        $overlapping = false;

        $params = array(
            ':show_end1'  => $show_end->format('Y-m-d H:i:s'),
            ':show_end2'  => $show_end->format('Y-m-d H:i:s'),
            ':show_end3'  => $show_end->format('Y-m-d H:i:s')
        );


        /* If a show is being edited, exclude it from the query
         * In both cases (new and edit) we only grab shows that
         * are scheduled 2 days prior
         */
        if ($update) {
            $sql = <<<SQL
SELECT id,
       starts,
       ends
FROM cc_show_instances
WHERE (ends <= :show_end1
       OR starts <= :show_end2)
  AND date(starts) >= (date(:show_end3) - INTERVAL '2 days')
  AND modified_instance = FALSE
SQL;
            if (is_null($showId)) {
                $sql .= <<<SQL
  AND id != :instanceId
ORDER BY ends
SQL;
                $params[':instanceId'] = $instanceId;
            } else {
                $sql .= <<<SQL
  AND show_id != :showId
ORDER BY ends
SQL;
                $params[':showId'] = $showId;
            }
            $rows = Application_Common_Database::prepareAndExecute($sql, $params, 'all');
        } else {
            $sql = <<<SQL
SELECT id,
       starts,
       ends
FROM cc_show_instances
WHERE (ends <= :show_end1
       OR starts <= :show_end2)
  AND date(starts) >= (date(:show_end3) - INTERVAL '2 days')
  AND modified_instance = FALSE
ORDER BY ends
SQL;

            $rows = Application_Common_Database::prepareAndExecute($sql, array(
                ':show_end1' => $show_end->format('Y-m-d H:i:s'),
                ':show_end2' => $show_end->format('Y-m-d H:i:s'),
                ':show_end3' => $show_end->format('Y-m-d H:i:s')), 'all');
        }

        foreach ($rows as $row) {
            $start = new DateTime($row["starts"], new DateTimeZone('UTC'));
            $end   = new DateTime($row["ends"], new DateTimeZone('UTC'));

            if ($show_start->getTimestamp() < $end->getTimestamp() &&
                $show_end->getTimestamp() > $start->getTimestamp()) {
                $overlapping = true;
                break;
            }
        }

        return $overlapping;
    }

    private static function makeDashboardItemOutput(&$row)
    {
    	if (empty($row["item_start"])) {
    		return null;
    	}

    	return array(
    		"name"=> $row["media_title"] ." - ".$row["media_creator"],
    		"starts" => $row["item_start"],
    		"ends" => (($row["item_end"] > $row["show_end"]) ? $row["show_end"]: $row["item_end"]),
    		"media_item_played" => (boolean) $row["media_item_played"],
    		"record" => 0,
    		"type" => 'track'
    	);
    }

    private static function makeDashboardShowOutput(&$row)
    {
    	return array(
        	"id" => $row['show_id'],
            "instance_id" => $row['instance_id'],
            "name" => $row['show_name'],
            "url" => $row['show_url'],
            "start_timestamp" => $row['show_start'],
            "end_timestamp" => $row['show_end'],
            "starts" => $row['show_start'],
            "ends" => $row['show_end'],
            "record" => $row['is_recorded'],
            "type" => "show"
    	);
    }

    public static function getDashboardInfo()
    {

    	//TODO better to split this into 2 queries, 1 for shows, 1 for items.

    	$sql = <<<SQL
select

npItems.media_title,
npItems.media_creator,
npItems.item_start,
npItems.item_end,
npItems.show_start,
npItems.show_end,
npItems.show_id,
npItems.instance_id,
npItems.is_recorded,
npItems.media_item_played,
show.name as show_name,
show.url as show_url

from
(

select

pcnItems.media_title,
pcnItems.media_creator,
pcnItems.starts as item_start,
pcnItems.ends as item_end,
pcnItems.media_item_played,
pcnShows.starts as show_start,
pcnShows.ends as show_end,
pcnShows.show_id,
pcnShows.instance_id,
pcnShows.is_recorded

from
(

select

preCurrNextShows.starts,
preCurrNextShows.ends,
preCurrNextShows.show_id,
preCurrNextShows.id as instance_id,
preCurrNextShows.record as is_recorded

from
(

select * from
(
select * from cc_show_instances instance
where
instance.modified_instance = false
and instance.starts <= (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
and instance.ends > (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
)

as currInstance

union

select * from
(
select * from cc_show_instances instance
where
instance.modified_instance = false
and instance.starts > (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
order by instance.starts
limit 1
)

as nextInstance

union

select * from
(
select * from cc_show_instances instance
where
instance.modified_instance = false
and instance.ends < (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
order by instance.ends desc
limit 1
)

as prevInstance

)

as preCurrNextShows
)

as pcnShows

full outer join

(

select
preCurrNextItem.starts,
preCurrNextItem.ends,
preCurrNextItem.show_id,
preCurrNextItem.media_item_played,
media.name as media_title,
media.creator as media_creator


from
(

select * from

(select currentItem.starts, currentItem.ends, currentItem.media_id, currentItem.media_item_played, showInstance.show_id from
(select sched.starts, sched.ends, sched.instance_id, sched.media_id, sched.media_item_played from cc_schedule sched
where
sched.playout_status > 0
and sched.starts <= (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
and sched.ends > (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC'))
as currentItem
left join cc_show_instances showInstance on currentItem.instance_id = showInstance.id
where showInstance.modified_instance = false
and showInstance.starts <= (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
and showInstance.ends > (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC'))

as cItem


union

select * from
(select nextItem.starts, nextItem.ends, nextItem.media_id, nextItem.media_item_played, showInstance.show_id from
(select sched.starts, sched.ends, sched.instance_id, sched.media_id, sched.media_item_played from cc_schedule sched
where
sched.playout_status > 0
and sched.starts > (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
order by sched.starts
limit 1)
as nextItem
left join cc_show_instances showInstance on nextItem.instance_id = showInstance.id
where showInstance.modified_instance = false)

as nItem

union

select * from
(select prevItem.starts, prevItem.ends, prevItem.media_id, prevItem.media_item_played, showInstance.show_id from
(select sched.starts, sched.ends, sched.instance_id, sched.media_id, sched.media_item_played from cc_schedule sched
where
sched.playout_status > 0
and sched.ends < (select CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
order by sched.ends desc
limit 1)
as prevItem
left join cc_show_instances showInstance on prevItem.instance_id = showInstance.id
where showInstance.modified_instance = false)

as pItem

)
as preCurrNextItem

left join media_item media on preCurrNextItem.media_id = media.id
)

as pcnItems

using(show_id)

)

as npItems

left join cc_show show on npItems.show_id = show.id

where npItems.show_id is not null

order by
npItems.show_start,
npItems.item_start
SQL;


    	// extra rows can be created here from combining prev/curr/next items
    	// with prev/curr/next shows.
    	//this happens from recorded shows, or any kind of show that does not have a cc_schedule entry associated with it.
    	//at most 5 items will be returned, need to find the proper prev/curr/next.
    	$rows = Application_Common_Database::prepareAndExecute($sql);

    	//Logging::info($rows);

    	$prev = null;
    	$curr = null;
    	$next = null;

    	$utcTimezone = new DateTimeZone("UTC");
    	$utcNow = new DateTime("now", $utcTimezone);

    	for ($i = 0, $len = count($rows); $i < $len; $i++) {

    		$start = $rows[$i]["show_start"];
    		$end = $rows[$i]["show_end"];

    		$startDT = new DateTime($start, $utcTimezone);
    		$endDT = new DateTime($end, $utcTimezone);

    		if ($endDT < $utcNow) {
    			$prev = $rows[$i];
    		}
    		else if ($startDT <= $utcNow && $endDT > $utcNow) {
    			$curr = $rows[$i];
    		}
    		else {
    			$next = $rows[$i];
    		}
    	}

    	$prevShow = isset($prev) ? self::makeDashboardShowOutput($prev) : null;
    	$currShow = isset($curr) ? self::makeDashboardShowOutput($curr) : null;
    	$nextShow = isset($next) ? self::makeDashboardShowOutput($next) : null;

    	//start again to find items.
    	$prev = null;
    	$curr = null;
    	$next = null;

    	for ($i = 0, $len = count($rows); $i < $len; $i++) {

    		if (empty($rows[$i]["item_start"])) {
    			continue;
    		}

    		$start = $rows[$i]["item_start"];
    		$end = $rows[$i]["item_end"];

    		$startDT = new DateTime($start, $utcTimezone);
    		$endDT = new DateTime($end, $utcTimezone);

    		if ($endDT < $utcNow) {
    			$prev = $rows[$i];
    		}
    		else if ($startDT <= $utcNow && $endDT > $utcNow) {
    			$curr = $rows[$i];
    			//could theoretically have 2 currents with crossfades,
    			//need to update the previous here just incase.
    			//items are ordered by starts so we can assume this.
    			if ($i > 0) {
    				$prev = $rows[$i - 1];
    			}
    		}
    		else {
    			$next = $rows[$i];
    			//need to exit as extra rows can occur from future empty shows.
    			break;
    		}
    	}

    	$prevItem = isset($prev) ? self::makeDashboardItemOutput($prev) : null;
    	$currItem = isset($curr) ? self::makeDashboardItemOutput($curr) : null;
    	$nextItem = isset($next) ? self::makeDashboardItemOutput($next) : null;

    	$range = array("env"=>APPLICATION_ENV,
    		"schedulerTime"=> $utcNow->format("Y-m-d H:i:s"),
    		//Previous, current, next songs!
    		"previous"=> isset($prevItem) ? $prevItem : $prevShow,
    		//only pass back the current show as the current item if it's recording.
    		"current"=> isset($currItem) ? $currItem : (($currShow["record"] == 1) ? $currShow: null),
    		"next"=> isset($nextItem) ? $nextItem : $nextShow,
    		//Current and next shows
    		//TODO this is lame that they're sent back in an array instead of just an object.
    		//dashboard.js needs to be fixed up for this though.
    		"currentShow"=> isset($currShow) ? array($currShow) : array(),
    		"nextShow"=> isset($nextShow) ? array($nextShow) : array()
    	);

    	return $range;
    }
}
