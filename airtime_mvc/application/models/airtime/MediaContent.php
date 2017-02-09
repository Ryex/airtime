<?php

namespace Airtime\MediaItem;

use Airtime\MediaItem\om\BaseMediaContent;
use \PropelException;


/**
 * Skeleton subclass for representing a row from the 'media_content' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.airtime
 */
class MediaContent extends BaseMediaContent
{
	public function generateCliplength() {
		
		$cuein = $this->getCuein();
		$cueout = $this->getCueout();
		
		$cueinSec = \Application_Common_DateHelper::playlistTimeToSeconds($cuein);
		$cueoutSec = \Application_Common_DateHelper::playlistTimeToSeconds($cueout);
		$lengthSec = bcsub($cueoutSec, $cueinSec, 6);
		
		$length = \Application_Common_DateHelper::secondsToPlaylistTime($lengthSec);
		
		if ($this->cliplength !== $length) {
			$this->cliplength = $length;
			$this->modifiedColumns[] = MediaContentPeer::CLIPLENGTH;
		}
		
		return $this;
	}
	
	public function setCliplength($v) {
		
		throw new PropelException("Cliplength must be generated from cuein & cueout.");
	}
	
	//given in seconds, convert to an interval.
	public function setTrackOffset($v) {
		
		if (is_numeric($v)) {
			$v = \Application_Common_DateHelper::secondsToPlaylistTime($v);
		}
		else {
			$v = "00:00:00";
		}
		
		parent::setTrackOffset($v);
		
		return $this;
	}
	
	public function getTrackOffset() {
		
		return \Application_Common_DateHelper::playlistTimeToSeconds(parent::getTrackOffset());
	}
}
