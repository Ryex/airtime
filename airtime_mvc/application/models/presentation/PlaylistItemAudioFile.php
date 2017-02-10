<?php

class Presentation_PlaylistItemAudioFile extends Presentation_PlaylistItem
{
	
	public function canEditCues() {
		return true;
	}
	
	public function canEditFades() {
		return true;
	}
	
	public function canPreview() {
		return true;
	}
	
	public function getTitle() {
		return $this->item->getTrackTitle();
	}
	
	public function getCreator() {
		return $this->item->getCreator() ." - ". $this->item->getAlbumTitle();
	}
	
	public function getUrl() {
		return $this->item->getFileUrl();
	}
}