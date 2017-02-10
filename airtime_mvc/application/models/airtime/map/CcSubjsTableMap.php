<?php

namespace Airtime\map;

use \RelationMap;
use \TableMap;


/**
 * This class defines the structure of the 'cc_subjs' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 * @package    propel.generator.airtime.map
 */
class CcSubjsTableMap extends TableMap
{

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'airtime.map.CcSubjsTableMap';

    /**
     * Initialize the table attributes, columns and validators
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('cc_subjs');
        $this->setPhpName('CcSubjs');
        $this->setClassname('Airtime\\CcSubjs');
        $this->setPackage('airtime');
        $this->setUseIdGenerator(true);
        $this->setPrimaryKeyMethodInfo('cc_subjs_id_seq');
        // columns
        $this->addPrimaryKey('id', 'DbId', 'INTEGER', true, null, null);
        $this->addColumn('login', 'DbLogin', 'VARCHAR', true, 255, '');
        $this->addColumn('pass', 'DbPass', 'VARCHAR', true, 255, '');
        $this->addColumn('type', 'DbType', 'CHAR', true, 1, 'U');
        $this->addColumn('first_name', 'DbFirstName', 'VARCHAR', true, 255, '');
        $this->addColumn('last_name', 'DbLastName', 'VARCHAR', true, 255, '');
        $this->addColumn('lastlogin', 'DbLastlogin', 'TIMESTAMP', false, null, null);
        $this->addColumn('lastfail', 'DbLastfail', 'TIMESTAMP', false, null, null);
        $this->addColumn('skype_contact', 'DbSkypeContact', 'VARCHAR', false, null, null);
        $this->addColumn('jabber_contact', 'DbJabberContact', 'VARCHAR', false, null, null);
        $this->addColumn('email', 'DbEmail', 'VARCHAR', false, null, null);
        $this->addColumn('cell_phone', 'DbCellPhone', 'VARCHAR', false, null, null);
        $this->addColumn('login_attempts', 'DbLoginAttempts', 'INTEGER', false, null, 0);
        // validators
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('CcFilesRelatedByDbOwnerId', 'Airtime\\CcFiles', RelationMap::ONE_TO_MANY, array('id' => 'owner_id', ), null, null, 'CcFilessRelatedByDbOwnerId');
        $this->addRelation('CcFilesRelatedByDbEditedby', 'Airtime\\CcFiles', RelationMap::ONE_TO_MANY, array('id' => 'editedby', ), null, null, 'CcFilessRelatedByDbEditedby');
        $this->addRelation('CcShowHosts', 'Airtime\\CcShowHosts', RelationMap::ONE_TO_MANY, array('id' => 'subjs_id', ), 'CASCADE', null, 'CcShowHostss');
        $this->addRelation('CcPlaylist', 'Airtime\\CcPlaylist', RelationMap::ONE_TO_MANY, array('id' => 'creator_id', ), 'CASCADE', null, 'CcPlaylists');
        $this->addRelation('CcBlock', 'Airtime\\CcBlock', RelationMap::ONE_TO_MANY, array('id' => 'creator_id', ), 'CASCADE', null, 'CcBlocks');
        $this->addRelation('CcPref', 'Airtime\\CcPref', RelationMap::ONE_TO_MANY, array('id' => 'subjid', ), 'CASCADE', null, 'CcPrefs');
        $this->addRelation('CcSubjsToken', 'Airtime\\CcSubjsToken', RelationMap::ONE_TO_MANY, array('id' => 'user_id', ), 'CASCADE', null, 'CcSubjsTokens');
        $this->addRelation('MediaItem', 'Airtime\\MediaItem', RelationMap::ONE_TO_MANY, array('id' => 'owner_id', ), null, null, 'MediaItems');
        $this->addRelation('AudioFile', 'Airtime\\MediaItem\\AudioFile', RelationMap::ONE_TO_MANY, array('id' => 'owner_id', ), null, null, 'AudioFiles');
        $this->addRelation('Webstream', 'Airtime\\MediaItem\\Webstream', RelationMap::ONE_TO_MANY, array('id' => 'owner_id', ), null, null, 'Webstreams');
        $this->addRelation('Playlist', 'Airtime\\MediaItem\\Playlist', RelationMap::ONE_TO_MANY, array('id' => 'owner_id', ), null, null, 'Playlists');
        $this->addRelation('Block', 'Airtime\\MediaItem\\Block', RelationMap::ONE_TO_MANY, array('id' => 'owner_id', ), null, null, 'Blocks');
    } // buildRelations()

} // CcSubjsTableMap
