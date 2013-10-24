<?php

namespace Airtime\map;

use \RelationMap;
use \TableMap;


/**
 * This class defines the structure of the 'cc_sess' table.
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
class CcSessTableMap extends TableMap
{

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'airtime.map.CcSessTableMap';

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
        $this->setName('cc_sess');
        $this->setPhpName('CcSess');
        $this->setClassname('Airtime\\CcSess');
        $this->setPackage('airtime');
        $this->setUseIdGenerator(false);
        // columns
        $this->addPrimaryKey('sessid', 'Sessid', 'CHAR', true, 32, null);
        $this->addForeignKey('userid', 'Userid', 'INTEGER', 'cc_subjs', 'id', false, null, null);
        $this->addColumn('login', 'Login', 'VARCHAR', false, 255, null);
        $this->addColumn('ts', 'Ts', 'TIMESTAMP', false, null, null);
        // validators
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('CcSubjs', 'Airtime\\CcSubjs', RelationMap::MANY_TO_ONE, array('userid' => 'id', ), 'CASCADE', null);
    } // buildRelations()

} // CcSessTableMap
