<?php

namespace Airtime\MediaItem;

use Airtime\MediaItemQuery;

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
    
    public function getScheduledContent() {
    
    	$contents = $this->getMediaContents();
    	$items = array();
    
    	foreach ($contents as $content) {
    		$data = array();
    		$data["id"] = $content->getMediaId();
    		$data["cliplength"] = $content->getCliplength();
    		$data["cuein"] = $content->getCuein();
    		$data["cueout"] = $content->getCueout();
    		$data["fadein"] = $content->getFadein();
    		$data["fadeout"] = $content->getFadeout();
    
    		$items[] = $data;
    	}
    
    	return $items;
    }
    
    public function generate() {
    	
    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
    	$con->beginTransaction();
    	 
    	try {
    		
    		$ruleSet = $this->getRules();
    		$criteria = isset($ruleSet["criteria"]) ? $ruleSet["criteria"] : array();
    		
    		$query = AudioFileQuery::create();
    		$criteriaRules = parent::getCriteriaRules($query);
    		
    		foreach ($criteria as $andBock) {
    			
    		}
    		 
    		$con->commit();
    	}
    	catch (Exception $e) {
    		$con->rollBack();
    		Logging::error($e->getMessage());
    		throw $e;
    	}
    }
    
    public function shuffle() {
    	
    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
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
    
    public function clear() {
    	
    	$con = Propel::getConnection(PlaylistPeer::DATABASE_NAME);
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
    
} // PlaylistStatic
