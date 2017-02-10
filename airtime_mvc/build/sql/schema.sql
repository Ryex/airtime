
-----------------------------------------------------------------------
-- cc_music_dirs
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_music_dirs" CASCADE;

CREATE TABLE "cc_music_dirs"
(
    "id" serial NOT NULL,
    "directory" TEXT,
    "type" VARCHAR(255),
    "exists" BOOLEAN DEFAULT 't',
    "watched" BOOLEAN DEFAULT 't',
    PRIMARY KEY ("id"),
    CONSTRAINT "cc_music_dir_unique" UNIQUE ("directory")
);

-----------------------------------------------------------------------
-- cc_show
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_show" CASCADE;

CREATE TABLE "cc_show"
(
    "id" serial NOT NULL,
    "name" VARCHAR(255) DEFAULT '' NOT NULL,
    "url" VARCHAR(255) DEFAULT '',
    "genre" VARCHAR(255) DEFAULT '',
    "description" VARCHAR(512),
    "color" VARCHAR(6),
    "background_color" VARCHAR(6),
    "live_stream_using_airtime_auth" BOOLEAN DEFAULT 'f',
    "live_stream_using_custom_auth" BOOLEAN DEFAULT 'f',
    "live_stream_user" VARCHAR(255),
    "live_stream_pass" VARCHAR(255),
    "linked" BOOLEAN DEFAULT 'f' NOT NULL,
    "is_linkable" BOOLEAN DEFAULT 't' NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_show_instances
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_show_instances" CASCADE;

CREATE TABLE "cc_show_instances"
(
    "id" serial NOT NULL,
    "starts" TIMESTAMP NOT NULL,
    "ends" TIMESTAMP NOT NULL,
    "show_id" INTEGER NOT NULL,
    "record" INT2 DEFAULT 0,
    "rebroadcast" INT2 DEFAULT 0,
    "instance_id" INTEGER,
    "media_id" INTEGER,
    "time_filled" interval DEFAULT '00:00:00',
    "created" TIMESTAMP NOT NULL,
    "last_scheduled" TIMESTAMP,
    "modified_instance" BOOLEAN DEFAULT 'f' NOT NULL,
    "unrolled" BOOLEAN DEFAULT 'f' NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "show_instance_original_show_idx" ON "cc_show_instances" ("instance_id");

CREATE INDEX "show_instance_starts_idx" ON "cc_show_instances" ("starts");

CREATE INDEX "show_instance_ends_idx" ON "cc_show_instances" ("ends");

CREATE INDEX "show_instance_modified_idx" ON "cc_show_instances" ("modified_instance");

-----------------------------------------------------------------------
-- cc_show_days
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_show_days" CASCADE;

CREATE TABLE "cc_show_days"
(
    "id" serial NOT NULL,
    "first_show" DATE NOT NULL,
    "last_show" DATE,
    "start_time" TIME NOT NULL,
    "timezone" VARCHAR NOT NULL,
    "duration" VARCHAR NOT NULL,
    "day" INT2,
    "repeat_type" INT2 NOT NULL,
    "next_pop_date" DATE,
    "show_id" INTEGER NOT NULL,
    "record" INT2 DEFAULT 0,
    PRIMARY KEY ("id")
);

CREATE INDEX "show_days_show_id_idx" ON "cc_show_days" ("show_id");

-----------------------------------------------------------------------
-- cc_show_rebroadcast
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_show_rebroadcast" CASCADE;

CREATE TABLE "cc_show_rebroadcast"
(
    "id" serial NOT NULL,
    "day_offset" VARCHAR NOT NULL,
    "start_time" TIME NOT NULL,
    "show_id" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "rebroadcast_show_id_idx" ON "cc_show_rebroadcast" ("show_id");

-----------------------------------------------------------------------
-- cc_show_hosts
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_show_hosts" CASCADE;

CREATE TABLE "cc_show_hosts"
(
    "id" serial NOT NULL,
    "show_id" INTEGER NOT NULL,
    "subjs_id" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "hosts_show_id_idx" ON "cc_show_hosts" ("show_id");

CREATE INDEX "hosts_user_id_idx" ON "cc_show_hosts" ("subjs_id");

-----------------------------------------------------------------------
-- cc_pref
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_pref" CASCADE;

CREATE TABLE "cc_pref"
(
    "id" serial NOT NULL,
    "subjid" INTEGER,
    "keystr" VARCHAR(255),
    "valstr" TEXT,
    PRIMARY KEY ("id"),
    CONSTRAINT "cc_pref_id_idx" UNIQUE ("id"),
    CONSTRAINT "cc_pref_subj_key_idx" UNIQUE ("subjid","keystr")
);

CREATE INDEX "cc_pref_subjid_idx" ON "cc_pref" ("subjid");

-----------------------------------------------------------------------
-- cc_schedule
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_schedule" CASCADE;

CREATE TABLE "cc_schedule"
(
    "id" serial NOT NULL,
    "starts" TIMESTAMP NOT NULL,
    "ends" TIMESTAMP NOT NULL,
    "media_id" INTEGER,
    "clip_length" interval DEFAULT '00:00:00',
    "fade_in" DECIMAL DEFAULT 0,
    "fade_out" DECIMAL DEFAULT 0,
    "cue_in" interval NOT NULL,
    "cue_out" interval NOT NULL,
    "media_item_played" BOOLEAN DEFAULT 'f',
    "instance_id" INTEGER NOT NULL,
    "playout_status" INT2 DEFAULT 1 NOT NULL,
    "broadcasted" INT2 DEFAULT 0 NOT NULL,
    "position" INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "cc_schedule_instance_id_idx" ON "cc_schedule" ("instance_id");

CREATE INDEX "cc_schedule_starts_idx" ON "cc_schedule" ("starts");

CREATE INDEX "cc_schedule_ends_idx" ON "cc_schedule" ("ends");

CREATE INDEX "cc_schedule_playout_status_idx" ON "cc_schedule" ("playout_status");

-----------------------------------------------------------------------
-- cc_subjs
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_subjs" CASCADE;

CREATE TABLE "cc_subjs"
(
    "id" serial NOT NULL,
    "login" VARCHAR(255) DEFAULT '' NOT NULL,
    "pass" VARCHAR(255) DEFAULT '' NOT NULL,
    "type" CHAR(1) DEFAULT 'U' NOT NULL,
    "first_name" VARCHAR(255) DEFAULT '' NOT NULL,
    "last_name" VARCHAR(255) DEFAULT '' NOT NULL,
    "lastlogin" TIMESTAMP,
    "lastfail" TIMESTAMP,
    "skype_contact" VARCHAR,
    "jabber_contact" VARCHAR,
    "email" VARCHAR,
    "cell_phone" VARCHAR,
    "login_attempts" INTEGER DEFAULT 0,
    PRIMARY KEY ("id"),
    CONSTRAINT "cc_subjs_id_idx" UNIQUE ("id"),
    CONSTRAINT "cc_subjs_login_idx" UNIQUE ("login")
);

-----------------------------------------------------------------------
-- cc_subjs_token
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_subjs_token" CASCADE;

CREATE TABLE "cc_subjs_token"
(
    "id" serial NOT NULL,
    "user_id" INTEGER NOT NULL,
    "action" VARCHAR(255) NOT NULL,
    "token" VARCHAR(40) NOT NULL,
    "created" TIMESTAMP NOT NULL,
    PRIMARY KEY ("id"),
    CONSTRAINT "cc_subjs_token_idx" UNIQUE ("token")
);

-----------------------------------------------------------------------
-- cc_country
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_country" CASCADE;

CREATE TABLE "cc_country"
(
    "isocode" CHAR(3) NOT NULL,
    "name" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("isocode")
);

-----------------------------------------------------------------------
-- cc_stream_setting
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_stream_setting" CASCADE;

CREATE TABLE "cc_stream_setting"
(
    "keyname" VARCHAR(64) NOT NULL,
    "value" VARCHAR(255),
    "type" VARCHAR(16) NOT NULL,
    PRIMARY KEY ("keyname")
);

-----------------------------------------------------------------------
-- cc_login_attempts
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_login_attempts" CASCADE;

CREATE TABLE "cc_login_attempts"
(
    "ip" VARCHAR(32) NOT NULL,
    "attempts" INTEGER DEFAULT 0,
    PRIMARY KEY ("ip")
);

-----------------------------------------------------------------------
-- cc_service_register
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_service_register" CASCADE;

CREATE TABLE "cc_service_register"
(
    "name" VARCHAR(32) NOT NULL,
    "ip" VARCHAR(18) NOT NULL,
    PRIMARY KEY ("name")
);

-----------------------------------------------------------------------
-- cc_live_log
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_live_log" CASCADE;

CREATE TABLE "cc_live_log"
(
    "id" serial NOT NULL,
    "state" VARCHAR(32) NOT NULL,
    "start_time" TIMESTAMP NOT NULL,
    "end_time" TIMESTAMP,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_mount_name
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_mount_name" CASCADE;

CREATE TABLE "cc_mount_name"
(
    "id" serial NOT NULL,
    "mount_name" VARCHAR NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_timestamp
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_timestamp" CASCADE;

CREATE TABLE "cc_timestamp"
(
    "id" serial NOT NULL,
    "timestamp" TIMESTAMP NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_listener_count
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_listener_count" CASCADE;

CREATE TABLE "cc_listener_count"
(
    "id" serial NOT NULL,
    "timestamp_id" INTEGER NOT NULL,
    "mount_name_id" INTEGER NOT NULL,
    "listener_count" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_locale
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_locale" CASCADE;

CREATE TABLE "cc_locale"
(
    "id" serial NOT NULL,
    "locale_code" VARCHAR(16) NOT NULL,
    "locale_lang" VARCHAR(128) NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_playout_history
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_playout_history" CASCADE;

CREATE TABLE "cc_playout_history"
(
    "id" serial NOT NULL,
    "media_id" INTEGER,
    "starts" TIMESTAMP NOT NULL,
    "ends" TIMESTAMP,
    "instance_id" INTEGER,
    PRIMARY KEY ("id")
);

CREATE INDEX "history_item_starts_index" ON "cc_playout_history" ("starts");

-----------------------------------------------------------------------
-- cc_playout_history_metadata
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_playout_history_metadata" CASCADE;

CREATE TABLE "cc_playout_history_metadata"
(
    "id" serial NOT NULL,
    "history_id" INTEGER NOT NULL,
    "key" VARCHAR(128) NOT NULL,
    "value" VARCHAR(128) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "playout_history_metadata_idx" ON "cc_playout_history_metadata" ("history_id");

-----------------------------------------------------------------------
-- cc_playout_history_template
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_playout_history_template" CASCADE;

CREATE TABLE "cc_playout_history_template"
(
    "id" serial NOT NULL,
    "name" VARCHAR(128) NOT NULL,
    "type" VARCHAR(35) NOT NULL,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- cc_playout_history_template_field
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "cc_playout_history_template_field" CASCADE;

CREATE TABLE "cc_playout_history_template_field"
(
    "id" serial NOT NULL,
    "template_id" INTEGER NOT NULL,
    "name" VARCHAR(128) NOT NULL,
    "label" VARCHAR(128) NOT NULL,
    "type" VARCHAR(128) NOT NULL,
    "is_file_md" BOOLEAN DEFAULT 'f' NOT NULL,
    "position" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

CREATE INDEX "playout_history_template_field_i" ON "cc_playout_history_template_field" ("template_id");

-----------------------------------------------------------------------
-- media_item
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "media_item" CASCADE;

CREATE TABLE "media_item"
(
    "id" serial NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    "descendant_class" VARCHAR(100),
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- media_audiofile
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "media_audiofile" CASCADE;

CREATE TABLE "media_audiofile"
(
    "directory" INTEGER,
    "filepath" TEXT DEFAULT '',
    "track_title" VARCHAR(512),
    "artist_name" VARCHAR(512),
    "bit_rate" INTEGER,
    "sample_rate" INTEGER,
    "album_title" VARCHAR(512),
    "genre" VARCHAR(64),
    "comments" TEXT,
    "year" INTEGER,
    "track_number" INTEGER,
    "channels" INTEGER,
    "bpm" INTEGER,
    "encoded_by" VARCHAR(255),
    "mood" VARCHAR(64),
    "label" VARCHAR(512),
    "composer" VARCHAR(512),
    "copyright" VARCHAR(512),
    "conductor" VARCHAR(512),
    "isrc_number" VARCHAR(512),
    "info_url" VARCHAR(512),
    "language" VARCHAR(512),
    "replay_gain" NUMERIC,
    "cuein" interval DEFAULT '00:00:00',
    "cueout" interval DEFAULT '00:00:00',
    "silan_check" BOOLEAN DEFAULT 'f',
    "file_exists" BOOLEAN DEFAULT 't',
    "hidden" BOOLEAN DEFAULT 'f',
    "import_status" INTEGER DEFAULT 1 NOT NULL,
    "id" INTEGER NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE INDEX "audiofile_directory_idx" ON "media_audiofile" ("directory");

CREATE INDEX "audiofile_filepath_idx" ON "media_audiofile" ("filepath");

-----------------------------------------------------------------------
-- media_webstream
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "media_webstream" CASCADE;

CREATE TABLE "media_webstream"
(
    "url" VARCHAR(512) NOT NULL,
    "id" INTEGER NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- media_playlist
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "media_playlist" CASCADE;

CREATE TABLE "media_playlist"
(
    "class_key" INTEGER,
    "rules" text DEFAULT '' NOT NULL,
    "id" INTEGER NOT NULL,
    "name" VARCHAR(512),
    "creator" VARCHAR(512),
    "source" VARCHAR(512),
    "owner_id" INTEGER,
    "description" VARCHAR(512),
    "last_played" TIMESTAMP(6),
    "play_count" INTEGER DEFAULT 0,
    "length" interval DEFAULT '00:00:00',
    "mime" VARCHAR,
    "created_at" TIMESTAMP,
    "updated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

-----------------------------------------------------------------------
-- media_content
-----------------------------------------------------------------------

DROP TABLE IF EXISTS "media_content" CASCADE;

CREATE TABLE "media_content"
(
    "id" serial NOT NULL,
    "playlist_id" INTEGER,
    "media_id" INTEGER,
    "position" INTEGER,
    "trackoffset" interval DEFAULT '00:00:00' NOT NULL,
    "cliplength" interval DEFAULT '00:00:00' NOT NULL,
    "cuein" interval DEFAULT '00:00:00',
    "cueout" interval DEFAULT '00:00:00',
    "fadein" DECIMAL DEFAULT 0,
    "fadeout" DECIMAL DEFAULT 0,
    PRIMARY KEY ("id")
);

CREATE INDEX "media_content_playlist_idx" ON "media_content" ("playlist_id");

CREATE INDEX "media_content_media_idx" ON "media_content" ("media_id");

ALTER TABLE "cc_show_instances" ADD CONSTRAINT "cc_show_fkey"
    FOREIGN KEY ("show_id")
    REFERENCES "cc_show" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_show_instances" ADD CONSTRAINT "cc_original_show_instance_fkey"
    FOREIGN KEY ("instance_id")
    REFERENCES "cc_show_instances" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_show_instances" ADD CONSTRAINT "cc_recorded_media_item_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_show_days" ADD CONSTRAINT "cc_show_fkey"
    FOREIGN KEY ("show_id")
    REFERENCES "cc_show" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_show_rebroadcast" ADD CONSTRAINT "cc_show_fkey"
    FOREIGN KEY ("show_id")
    REFERENCES "cc_show" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_show_hosts" ADD CONSTRAINT "cc_perm_show_fkey"
    FOREIGN KEY ("show_id")
    REFERENCES "cc_show" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_show_hosts" ADD CONSTRAINT "cc_perm_host_fkey"
    FOREIGN KEY ("subjs_id")
    REFERENCES "cc_subjs" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_pref" ADD CONSTRAINT "cc_pref_subjid_fkey"
    FOREIGN KEY ("subjid")
    REFERENCES "cc_subjs" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_schedule" ADD CONSTRAINT "cc_show_inst_fkey"
    FOREIGN KEY ("instance_id")
    REFERENCES "cc_show_instances" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_schedule" ADD CONSTRAINT "media_item_sched_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_subjs_token" ADD CONSTRAINT "cc_subjs_token_userid_fkey"
    FOREIGN KEY ("user_id")
    REFERENCES "cc_subjs" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_listener_count" ADD CONSTRAINT "cc_timestamp_inst_fkey"
    FOREIGN KEY ("timestamp_id")
    REFERENCES "cc_timestamp" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_listener_count" ADD CONSTRAINT "cc_mount_name_inst_fkey"
    FOREIGN KEY ("mount_name_id")
    REFERENCES "cc_mount_name" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_playout_history" ADD CONSTRAINT "media_item_history_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE SET NULL;

ALTER TABLE "cc_playout_history" ADD CONSTRAINT "cc_his_item_inst_fkey"
    FOREIGN KEY ("instance_id")
    REFERENCES "cc_show_instances" ("id")
    ON DELETE SET NULL;

ALTER TABLE "cc_playout_history_metadata" ADD CONSTRAINT "cc_playout_history_metadata_entry_fkey"
    FOREIGN KEY ("history_id")
    REFERENCES "cc_playout_history" ("id")
    ON DELETE CASCADE;

ALTER TABLE "cc_playout_history_template_field" ADD CONSTRAINT "cc_playout_history_template_template_fkey"
    FOREIGN KEY ("template_id")
    REFERENCES "cc_playout_history_template" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_item" ADD CONSTRAINT "media_item_owner_fkey"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_audiofile" ADD CONSTRAINT "audio_file_music_dir_fkey"
    FOREIGN KEY ("directory")
    REFERENCES "cc_music_dirs" ("id");

ALTER TABLE "media_audiofile" ADD CONSTRAINT "media_audiofile_FK_2"
    FOREIGN KEY ("id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_audiofile" ADD CONSTRAINT "media_audiofile_FK_3"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_webstream" ADD CONSTRAINT "media_webstream_FK_1"
    FOREIGN KEY ("id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_webstream" ADD CONSTRAINT "media_webstream_FK_2"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_playlist" ADD CONSTRAINT "media_playlist_FK_1"
    FOREIGN KEY ("id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_playlist" ADD CONSTRAINT "media_playlist_FK_2"
    FOREIGN KEY ("owner_id")
    REFERENCES "cc_subjs" ("id");

ALTER TABLE "media_content" ADD CONSTRAINT "media_content_playlist_fkey"
    FOREIGN KEY ("playlist_id")
    REFERENCES "media_playlist" ("id")
    ON DELETE CASCADE;

ALTER TABLE "media_content" ADD CONSTRAINT "media_content_media_fkey"
    FOREIGN KEY ("media_id")
    REFERENCES "media_item" ("id")
    ON DELETE CASCADE;
