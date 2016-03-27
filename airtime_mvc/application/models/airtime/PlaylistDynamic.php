<?php

namespace Airtime\MediaItem;

use \PropelException;

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
class PlaylistDynamic extends Playlist {

    /**
     * Constructs a new PlaylistDynamic class, setting the class_key column to PlaylistPeer::CLASSKEY_1.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setClassKey(PlaylistPeer::CLASSKEY_1);
    }
    
    public function buildContentItem($mediaId, $position, $cuein=null, $cueout=null, $fadein=null, $fadeout=null)
    {
    	throw new PropelException("Dynamic playlist does not have content");
    }
    
    //TODO get this based on the rule.
    public function getLength()
    {
    	if (is_null($this->length)) {
    		$this->length = "00:00:00";
    	}
    
    	return $this->length;
    }
    
    public function getContents(PropelPDO $con = null) {
    	throw new PropelException("Dynamic playlist does not have content");
    }
    
    public function getScheduledContent() {
    	//TODO generate content based on the saved rules.
    }
    
    public function generate() {
    	throw new PropelException("Cannot generate a dynamic playlist");
    }
    
    public function shuffle() {
    	throw new PropelException("Cannot shuffle a dynamic playlist");
    }
    
    public function clear() {
    	throw new PropelException("Cannot clear a dynamic playlist");
    }

} // PlaylistDynamic
