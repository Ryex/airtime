<?php

namespace Airtime\MediaItem;

use Airtime\MediaItemQuery;

use \Logging;
use \Propel;
use \PropelPDO;
use \Criteria;
use Airtime\MediaItem\MediaContentQuery;


/**
 * Skeleton subclass for representing a row from one of the subclasses of the 'media_playlist' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.airtime
 */
class PlaylistStatic extends Playlist {

    /**
     * Constructs a new PlaylistStatic class, setting the class_key column to PlaylistPeer::CLASSKEY_0.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setClassKey(PlaylistPeer::CLASSKEY_0);
    }
    
    public function buildContentItem($mediaItem, $position, $cuein=null, $cueout=null, $fadein=null, $fadeout=null) {
    	$item = new MediaContent();
    	$defaultCrossfade = \Application_Model_Preference::GetDefaultCrossfadeDuration();
    	
    	$cue = (isset($cuein)) ? $cuein : $mediaItem->getSchedulingCueIn();
    	$item->setCuein($cue);
    	
    	$cue = (isset($cueout)) ? $cueout : $mediaItem->getSchedulingCueOut();
    	$item->setCueout($cue);

    	$fade = (isset($fadein)) ? $fadein : $mediaItem->getSchedulingFadeIn();
    	$item->setFadein($fade);
    
    	$fade = (isset($fadeout)) ? $fadeout : $mediaItem->getSchedulingFadeOut();
    	$item->setFadeout($fade);
    
    	$item->generateCliplength();
    
    	//need trackoffset to be zero for the first item.
    	if ($position !== 0) {
    		$item->setTrackOffset($defaultCrossfade);
    	}
    
    	$item->setMediaItem($mediaItem);
    	$item->setPosition($position);
    
    	return $item;
    }

    /*
     * returns a list of media contents.
    */
    public function getContents(PropelPDO $con = null) {
    	
    	if (is_null($con)) {
    		$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	}
    
    	$q = MediaContentQuery::create();
    	$m = $q->getModelName();
    
    	//use a window function to calculate offsets for the playlist.
    	return $q
	    	->withColumn("SUM({$m}.Cliplength)  OVER(ORDER BY {$m}.Position) -
	    	SUM({$m}.TrackOffset) OVER(ORDER BY {$m}.Position)", "offset")
	    	->filterByPlaylist($this)
	    	->joinWith('MediaItem', Criteria::LEFT_JOIN)
	    	->joinWith("MediaItem.AudioFile", Criteria::LEFT_JOIN)
	    	->joinWith("MediaItem.Webstream", Criteria::LEFT_JOIN)
	    	->joinWith("MediaItem.Playlist", Criteria::LEFT_JOIN)
	    	->find($con);
    }
    
    /**
     * Computes the value of the aggregate column length *
     * @param PropelPDO $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeLength(PropelPDO $con)
    {
    	//have to subtract the track offsets (crossfade times)
    	$stmt = $con->prepare('SELECT SUM(cliplength) - SUM(trackoffset)
    			FROM "media_content" WHERE media_content.playlist_id = :p1');
    	$stmt->bindValue(':p1', $this->getId());
    	$stmt->execute();
    
    	return $stmt->fetchColumn();
    }
    
    /**
     * Updates the aggregate column length *
     * @param PropelPDO $con A connection object
     */
    public function updateLength(PropelPDO $con)
    {
    	$length = $this->computeLength($con);
    
    	//update both tables (inheritance) for this playlist
    	$stmt = $con->prepare('UPDATE media_playlist SET length = :p1 WHERE media_playlist.id = :p2');
    	$stmt->bindValue(':p1', $length);
    	$stmt->bindValue(':p2', $this->getId());
    	$stmt->execute();
    
    	$stmt = $con->prepare('UPDATE media_item SET length = :p1 WHERE media_item.id = :p2');
    	$stmt->bindValue(':p1', $length);
    	$stmt->bindValue(':p2', $this->getId());
    	$stmt->execute();
    
    	//need to make the object aware of the change.
    	//for last modified
    	if ($this->length != $length) {
    		$this->modifiedColumns[] = PlaylistPeer::LENGTH;
    	}
    	$this->length = $length;
    }
    
    public function getLength()
    {
    	if (is_null($this->length)) {
    		$this->length = "00:00:00";
    	}
    
    	return $this->length;
    }
    
    //if this returns false when creating a new object it seems ONLY a media item row is created
    //and nothing in the playlist table. seems like a bug...
    public function preSave(PropelPDO $con = null)
    {
    	//run through positions to close gaps if any.
    
    	$this->updateLength($con);
    
    	return true;
    }
    
    public function shuffleContent(PropelPDO $con) {

    	$con->beginTransaction();
    	
    	try {
    		$contents = $this->getMediaContents(null, $con);
    		$count = count($contents);
    		$order = array();
    			
    		for ($i = 0; $i < $count; $i++) {
    			$order[] = $i;
    		}
    		shuffle($order);
    			
    		$i = 0;
    		foreach ($contents as $content) {
    			$content->setPosition($order[$i]);
    			$i++;
    		}
    			
    		$this->setMediaContents($contents, $con);
    		$this->save($con);
    	
    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::error($e->getMessage());
    		throw $e;
    	}
    }
    
    public function clearContent(PropelPDO $con) {

    	$con->beginTransaction();
    	
    	try {
    		MediaContentQuery::create(null, $con)
	    		->filterByPlaylist($this)
	    		->delete($con);
    			
    		$this->save($con);
    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::error($e->getMessage());
    		throw $e;
    	}
    }
    
   /*
    * @param $ids list of media ids to add to the end of the playlist.
    */
    public function addMedia(PropelPDO $con, $ids, $afterId = null) {

    	$con->beginTransaction();
    	
    	Logging::info("Adding to playlist after id ".$afterId);

    	try {
    		
    		if (is_null($afterId)) {
    			$position = 0;
    		}
    		else {
    			$afterItem = MediaContentQuery::create()->findPk($afterId, $con);
    			$position = $afterItem->getPosition() + 1;
    		}
    		
    		Logging::info("Adding to position: ".$position);
    		
    		$numInserts = count($ids);
    		
    		//create the gap in positions for the new items.
    		
    		$table = MediaContentPeer::TABLE_NAME;
    		$positionCol = "position";
    		$playlistIdCol = "playlist_id";
    		
    		$stmt = $con->prepare("UPDATE {$table} SET {$positionCol} = {$positionCol} + {$numInserts}
    			WHERE {$playlistIdCol} = :p1 AND {$positionCol} >= {$position}");
    		$stmt->bindValue(':p1', $this->getId());
    		$stmt->execute();
    		
    		//run this just for the single query.
    		$mediaToAdd = MediaItemQuery::create()->findPks($ids, $con);
    		
    		//need to maintain the order of the id, objects will be preloaded.
    		foreach ($ids as $id) {
    			
    			$mediaItem = MediaItemQuery::create()->findPk($id, $con);
    			$info = $mediaItem->getSchedulingInfo();
    
    			$cuein = $info["cuein"];
    			$cueout = $info["cueout"];
    			$fadein = $info["fadein"];
    			$fadeout = $info["fadeout"];
    			
    			$mediaContent = $this->buildContentItem($mediaItem, $position, $cuein, $cueout, $fadein, $fadeout);
    			$mediaContent->setPlaylist($this);
    			$mediaContent->save($con);
    
    			$position++;
    		}

    		$this->save($con);
    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::error($e->getMessage());
    		throw $e;
    	}
    
    	Logging::disablePropelLogging();
    }
    
    public function savePlaylistContent(PropelPDO $con, $content)
    {
    	$con->beginTransaction();
    
    	try {
    			
    		$m = array();
    		$currentContent = $this->getMediaContents(null, $con);
    		$currentContent->delete($con);
    		$position = 0;
    			
    		foreach ($content as $item) {
    
    			$mediaId = $item["id"];
    			$cuein = isset($item["cuein"]) ? $item["cuein"] : null;
    			$cueout = isset($item["cueout"]) ? $item["cueout"] : null;
    			$fadein = isset($item["fadein"]) ? $item["fadein"] : null;
    			$fadeout = isset($item["fadeout"]) ? $item["fadeout"] : null;
    
    			$mediaItem = MediaItemQuery::create()->findPK($mediaId, $con);
    			$mediaContent = $this->buildContentItem($mediaItem, $position, $cuein, $cueout, $fadein, $fadeout);
    			$mediaContent->setPlaylist($this);
    
    			$res = $mediaContent->validate();
    			if ($res === true) {
    				$m[] = $mediaContent;
    			}
    			else {
    				Logging::info($res);
    				throw new Exception("invalid media content");
    			}
    
    			$position++;
    
    			//save each content item in the transaction
    			//first so that Playlist preSave can calculate
    			//the new playlist length properly.
    			$mediaContent->save($con);
    		}
	
    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::error($e->getMessage());
    		throw $e;
    	}
    }
    
    public function getScheduledContent(PropelPDO $con) {
    	
    	$contents = self::getContents($con);
    	$scheduled = array();
    	
    	foreach ($contents as $content) {
    		
    		$media = $content->getMediaItem()->getChildObject();
    		
    		if (substr($media->getType(), 0, 8) == "Playlist") {
    			
    			$scheduled = array_merge($scheduled, $media->getScheduledContent($con));
    		}
    		else {
    			$scheduled[] = array (
    				"id" => $content->getMediaId(),
    				"cliplength" => $content->getCliplength(),
    				"cuein" => $content->getCuein(),
    				"cueout" => $content->getCueout(),
    				"fadein" => $content->getFadein(),
    				"fadeout" => $content->getFadeout(),
    			);
    		}	
    	}
    	
    	return $scheduled;
    }
    
} // PlaylistStatic
