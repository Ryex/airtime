<?php

use Airtime\CcSubjsPeer;
use Airtime\MediaItem\WebstreamPeer;
use Airtime\MediaItem\PlaylistPeer;
use Airtime\MediaItem\AudioFilePeer;

use Airtime\MediaItem\AudioFileQuery;
use Airtime\MediaItem\WebstreamQuery;
use Airtime\MediaItem\PlaylistQuery;
use Airtime\MediaItemQuery;

class Application_Service_DatatableAudioFileService extends Application_Service_DatatableService
{
	protected $columns; 
	
	protected $order = array (
		"IsScheduled",
		"IsPlaylist",
		"TrackTitle",
		"ArtistName",
		"AlbumTitle",
		"BitRate",
		"Bpm",
		"Composer",
		"Conductor",
		"Copyright",
		"Cuein",
		"Cueout",
		"EncodedBy",
		"Genre",
		"IsrcNumber",
		"Label",
		"Language",
		"UpdatedAt",
		"LastPlayedTime",
		"CueLength", //this is a custom function in AudioFile
		"Mime",
		"Mood",
		"CcSubjs.DbLogin",
		"ReplayGain",
		"SampleRate",
		"TrackNumber",
		"CreatedAt",
		"InfoUrl",
		"Year",
	);
	
	protected $aliases = array(
		"CueLength",
	);
	
	public function __construct() {
		
		parent::__construct();
	}
	
	protected function getSettings() {
		return Application_Model_Preference::getAudioTableSetting();
	}
	
	protected function getColumns() {

		return array(
			"Id" => array(
				"isColumn" => false,
				"advancedSearch" => array(
					"type" => null
				)
			),
			"IsScheduled" => array(
				"isColumn" => true,
				"title" => _("Scheduled"),
				"width" => "90px",
				"class" => "library_is_scheduled",
				"searchable" => false,
				"advancedSearch" => array(
					"type" => "checkbox"
				)
			),
			"IsPlaylist" => array(
				"isColumn" => true,
				"title" => _("Playlist"),
				"width" => "90px",
				"class" => "library_is_playlist",
				"searchable" => false,
				"advancedSearch" => array(
					"type" => "checkbox"
				)
			),
			"TrackTitle" => array(
				"isColumn" => true,
				"title" => _("Title"),
				"width" => "170px",
				"class" => "library_title",
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"ArtistName" => array(
				"isColumn" => true,
				"title" => _("Creator"),
				"width" => "160px",
				"class" => "library_creator",
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"AlbumTitle" => array(
				"isColumn" => true,
				"title" => _("Album"),
				"width" => "150px",
				"class" => "library_album",
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"BitRate" => array(
				"isColumn" => true,
				"title" => _("Bit Rate"),
				"width" => "80px",
				"class" => "library_bitrate",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "number-range"
				)
			),
			"Bpm" => array(
				"isColumn" => true,
				"title" => _("BPM"),
				"width" => "50px",
				"class" => "library_bpm",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "number-range"
				)
			),
			"Composer" => array(
				"isColumn" => true,
				"title" => _("Composer"),
				"width" => "150px",
				"class" => "library_composer",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Conductor" => array(
				"isColumn" => true,
				"title" => _("Conductor"),
				"width" => "125px",
				"class" => "library_conductor",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Copyright" => array(
				"isColumn" => true,
				"title" => _("Copyright"),
				"width" => "125px",
				"class" => "library_copyright",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Cuein" => array(
				"isColumn" => true,
				"title" => _("Cue In"),
				"width" => "80px",
				"class" => "library_length",
				"visible" => false,
				"searchable" => false,
				"advancedSearch" => array(
					"type" => null
				)
			),
			"Cueout" => array(
				"isColumn" => true,
				"title" => _("Cue Out"),
				"width" => "80px",
				"class" => "library_length",
				"visible" => false,
				"searchable" => false,
				"advancedSearch" => array(
					"type" => null
				)
			),
			"EncodedBy" => array(
				"isColumn" => true,
				"title" => _("Encoded By"),
				"width" => "150px",
				"class" => "library_encoded",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Genre" => array(
				"isColumn" => true,
				"title" => _("Genre"),
				"width" => "100px",
				"class" => "library_genre",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"IsrcNumber" => array(
				"isColumn" => true,
				"title" => _("ISRC"),
				"width" => "150px",
				"class" => "library_isrc",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Label" => array(
				"isColumn" => true,
				"title" => _("Label"),
				"width" => "125px",
				"class" => "library_label",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Language" => array(
				"isColumn" => true,
				"title" => _("Language"),
				"width" => "125px",
				"class" => "library_language",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"UpdatedAt" => array(
				"isColumn" => true,
				"title" => _("Last Modified"),
				"width" => "125px",
				"class" => "library_modified_time",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "date-range"
				)
			),
			"LastPlayedTime" => array(
				"isColumn" => true,
				"title" => _("Last Played"),
				"width" => "125px",
				"class" => "library_modified_time",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "date-range"
				)
			),
			"CueLength" => array(
				"isColumn" => true,
				"title" => _("Length"),
				"width" => "80px",
				"class" => "library_length",
				"searchable" => false,
				"advancedSearch" => array(
					"type" => null
				)
			),
			"Mime" => array(
				"isColumn" => true,
				"title" => _("Mime"),
				"width" => "80px",
				"class" => "library_mime",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"Mood" => array(
				"isColumn" => true,
				"title" => _("Mood"),
				"width" => "70px",
				"class" => "library_mood",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"CcSubjs.DbLogin" => array(
				"isColumn" => true,
				"title" => _("Owner"),
				"width" => "125px",
				"class" => "library_owner",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)
			),
			"ReplayGain" => array(
				"isColumn" => true,
				"title" => _("Replay Gain"),
				"width" => "80px",
				"class" => "library_replay_gain",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "number-range"
				)
			),
			"SampleRate" => array(
				"isColumn" => true,
				"title" => _("Sample Rate"),
				"width" => "80px",
				"class" => "library_sr",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "number-range"
				)
			),
			"TrackNumber" => array(
				"isColumn" => true,
				"title" => _("Track number"),
				"width" => "65px",
				"class" => "library_track",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "number-range"
				)
			),
			"CreatedAt" => array(
				"isColumn" => true,
				"title" => _("Uploaded"),
				"width" => "125px",
				"class" => "library_upload_time",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "date-range"
				)
			),
			"InfoUrl" => array(
				"isColumn" => true,
				"title" => _("Website"),
				"width" => "150px",
				"class" => "library_url",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "text"
				)

			),
			"Year" => array(
				"isColumn" => true,
				"title" => _("Year"),
				"width" => "60px",
				"class" => "library_year",
				"visible" => false,
				"advancedSearch" => array(
					"type" => "number-range"
				)
			),
		);
	}
	
	public function getDatatables($params) {
	
		Logging::enablePropelLogging();
	
		$q = AudioFileQuery::create();
	
		$m = $q->getModelName();
		$q->withColumn("({$m}.Cueout - {$m}.Cuein)", "cuelength");
		$q->joinWith("CcSubjs");
	
		$results = self::buildQuery($q, $params);
	
		Logging::disablePropelLogging();
	
		return array(
			"count" => $results["count"],
			"totalCount" => $results["totalCount"],
			"records" => $this->createOutput($results["media"], $this->columnKeys)
		);
	}
}