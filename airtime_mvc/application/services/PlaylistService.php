<?php

use Airtime\MediaItem\MediaContentQuery;
use Airtime\MediaItem\MediaContent;
use Airtime\MediaItem\PlaylistPeer;
use Airtime\MediaItem\Playlist;
use Airtime\MediaItemQuery;

class Application_Service_PlaylistService
{
	public function createContextMenu($playlist) {
	
		$id = $playlist->getId();
		
		$service = new Application_Service_UserService();
		$user = $service->getCurrentUser();
		
		$menu = array();
	
		if ($playlist->isStatic()) {
			$menu["preview"] = array(
				"name" => _("Preview"),
				"icon" => "play",
				"id" => $id,
				"callback" => "previewItem"
			);
		}
		
		if ($user->isAdmin() || $user->getId() === $mediaItem->getOwnerId()) {

			$menu["edit"] = array(
				"name"=> _("Edit"),
				"icon" => "edit",
				"id" => $id,
				"callback" => "openPlaylist"
			);
		
			$menu["delete"] = array(
				"name" => _("Delete"),
				"icon" => "delete",
				"id" => $id,
				"callback" => "deleteItem"
			);
		}
	
		return $menu;
	}
	
	public function createPlaylist($type) {
		
		switch($type) {
			case PlaylistPeer::CLASSKEY_0:
				$class = PlaylistPeer::CLASSNAME_0;
				return new $class();
				break;
			case PlaylistPeer::CLASSKEY_1:
			default:
				$class = PlaylistPeer::CLASSNAME_1;
				return new $class();
				break;
				
		}
	}
	
	public function savePlaylist($playlist, $info, $con) {
		
		$con->beginTransaction();
		 
		try {
			if (isset($info["name"])) {
				$playlist->setName($info["name"]);
			}
			
			if (isset($info["description"])) {
				$playlist->setDescription($info["description"]);
			}
			
			//only save content for static playlists
			if ($playlist->isStatic()) {
				$content = isset($info["content"]) ? $info["content"] : array();
				$playlist->savePlaylistContent($con, $content, true);
			}
			
			$playlist->save($con); 
			$con->commit();
		}
		catch (Exception $e) {
			$con->rollBack();
			throw $e;
		}	
	}
}