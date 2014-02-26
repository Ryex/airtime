<?php

use Airtime\MediaItem\Playlist;

use Airtime\MediaItem\PlaylistPeer;

class Presentation_Playlist {
	
	const LENGTH_FORMATTER_CLASS = "Format_HHMMSSULength";
	
	public function __construct($playlist) {
		
		$this->playlist = $playlist;
	}
	
	public function getId() {
		
		return $this->playlist->getId();
	}
	
	public function getName() {
		
		return $this->playlist->getName();
	}
	
	public function getDescription() {
		
		return $this->playlist->getDescription();
	}
	
	public function getLastModifiedEpoch() {
		
		return $this->playlist->getUpdatedAt("U");
	}
	
	public function getLength() {
		
		$length = $this->playlist->getLength();
		$class = self::LENGTH_FORMATTER_CLASS;
		$formatter = new $class($length);
		
		return $formatter->format(3);
	}
	
	public function hasContent() {
		
		$type = $this->playlist->getClassKey();
		
		return $type === PlaylistPeer::CLASSKEY_0 ? true: false;
	}
	
	public function getContent() {
		
		return $this->playlist->getContents();
	}
	
	public function getRules() {
		
		$form = new Application_Form_PlaylistRules();
		
		$rules = $this->playlist->getRules();
		
		$form->populate(array(
			"pl_repeat_tracks" => $rules[Playlist::RULE_REPEAT_TRACKS],
			"pl_my_tracks" => $rules[Playlist::RULE_USERS_TRACKS_ONLY]	
		));
		
		return $form;
	}
}