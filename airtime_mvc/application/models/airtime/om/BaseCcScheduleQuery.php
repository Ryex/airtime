<?php

namespace Airtime\om;

use \Criteria;
use \Exception;
use \ModelCriteria;
use \ModelJoin;
use \PDO;
use \Propel;
use \PropelCollection;
use \PropelException;
use \PropelObjectCollection;
use \PropelPDO;
use Airtime\CcSchedule;
use Airtime\CcSchedulePeer;
use Airtime\CcScheduleQuery;
use Airtime\CcShowInstances;
use Airtime\MediaItem;

/**
 * Base class that represents a query for the 'cc_schedule' table.
 *
 *
 *
 * @method CcScheduleQuery orderByDbId($order = Criteria::ASC) Order by the id column
 * @method CcScheduleQuery orderByDbStarts($order = Criteria::ASC) Order by the starts column
 * @method CcScheduleQuery orderByDbEnds($order = Criteria::ASC) Order by the ends column
 * @method CcScheduleQuery orderByDbMediaId($order = Criteria::ASC) Order by the media_id column
 * @method CcScheduleQuery orderByDbClipLength($order = Criteria::ASC) Order by the clip_length column
 * @method CcScheduleQuery orderByDbFadeIn($order = Criteria::ASC) Order by the fade_in column
 * @method CcScheduleQuery orderByDbFadeOut($order = Criteria::ASC) Order by the fade_out column
 * @method CcScheduleQuery orderByDbCueIn($order = Criteria::ASC) Order by the cue_in column
 * @method CcScheduleQuery orderByDbCueOut($order = Criteria::ASC) Order by the cue_out column
 * @method CcScheduleQuery orderByDbMediaItemPlayed($order = Criteria::ASC) Order by the media_item_played column
 * @method CcScheduleQuery orderByDbInstanceId($order = Criteria::ASC) Order by the instance_id column
 * @method CcScheduleQuery orderByDbPlayoutStatus($order = Criteria::ASC) Order by the playout_status column
 * @method CcScheduleQuery orderByDbBroadcasted($order = Criteria::ASC) Order by the broadcasted column
 * @method CcScheduleQuery orderByDbPosition($order = Criteria::ASC) Order by the position column
 *
 * @method CcScheduleQuery groupByDbId() Group by the id column
 * @method CcScheduleQuery groupByDbStarts() Group by the starts column
 * @method CcScheduleQuery groupByDbEnds() Group by the ends column
 * @method CcScheduleQuery groupByDbMediaId() Group by the media_id column
 * @method CcScheduleQuery groupByDbClipLength() Group by the clip_length column
 * @method CcScheduleQuery groupByDbFadeIn() Group by the fade_in column
 * @method CcScheduleQuery groupByDbFadeOut() Group by the fade_out column
 * @method CcScheduleQuery groupByDbCueIn() Group by the cue_in column
 * @method CcScheduleQuery groupByDbCueOut() Group by the cue_out column
 * @method CcScheduleQuery groupByDbMediaItemPlayed() Group by the media_item_played column
 * @method CcScheduleQuery groupByDbInstanceId() Group by the instance_id column
 * @method CcScheduleQuery groupByDbPlayoutStatus() Group by the playout_status column
 * @method CcScheduleQuery groupByDbBroadcasted() Group by the broadcasted column
 * @method CcScheduleQuery groupByDbPosition() Group by the position column
 *
 * @method CcScheduleQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method CcScheduleQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method CcScheduleQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method CcScheduleQuery leftJoinCcShowInstances($relationAlias = null) Adds a LEFT JOIN clause to the query using the CcShowInstances relation
 * @method CcScheduleQuery rightJoinCcShowInstances($relationAlias = null) Adds a RIGHT JOIN clause to the query using the CcShowInstances relation
 * @method CcScheduleQuery innerJoinCcShowInstances($relationAlias = null) Adds a INNER JOIN clause to the query using the CcShowInstances relation
 *
 * @method CcScheduleQuery leftJoinMediaItem($relationAlias = null) Adds a LEFT JOIN clause to the query using the MediaItem relation
 * @method CcScheduleQuery rightJoinMediaItem($relationAlias = null) Adds a RIGHT JOIN clause to the query using the MediaItem relation
 * @method CcScheduleQuery innerJoinMediaItem($relationAlias = null) Adds a INNER JOIN clause to the query using the MediaItem relation
 *
 * @method CcSchedule findOne(PropelPDO $con = null) Return the first CcSchedule matching the query
 * @method CcSchedule findOneOrCreate(PropelPDO $con = null) Return the first CcSchedule matching the query, or a new CcSchedule object populated from the query conditions when no match is found
 *
 * @method CcSchedule findOneByDbStarts(string $starts) Return the first CcSchedule filtered by the starts column
 * @method CcSchedule findOneByDbEnds(string $ends) Return the first CcSchedule filtered by the ends column
 * @method CcSchedule findOneByDbMediaId(int $media_id) Return the first CcSchedule filtered by the media_id column
 * @method CcSchedule findOneByDbClipLength(string $clip_length) Return the first CcSchedule filtered by the clip_length column
 * @method CcSchedule findOneByDbFadeIn(string $fade_in) Return the first CcSchedule filtered by the fade_in column
 * @method CcSchedule findOneByDbFadeOut(string $fade_out) Return the first CcSchedule filtered by the fade_out column
 * @method CcSchedule findOneByDbCueIn(string $cue_in) Return the first CcSchedule filtered by the cue_in column
 * @method CcSchedule findOneByDbCueOut(string $cue_out) Return the first CcSchedule filtered by the cue_out column
 * @method CcSchedule findOneByDbMediaItemPlayed(boolean $media_item_played) Return the first CcSchedule filtered by the media_item_played column
 * @method CcSchedule findOneByDbInstanceId(int $instance_id) Return the first CcSchedule filtered by the instance_id column
 * @method CcSchedule findOneByDbPlayoutStatus(int $playout_status) Return the first CcSchedule filtered by the playout_status column
 * @method CcSchedule findOneByDbBroadcasted(int $broadcasted) Return the first CcSchedule filtered by the broadcasted column
 * @method CcSchedule findOneByDbPosition(int $position) Return the first CcSchedule filtered by the position column
 *
 * @method array findByDbId(int $id) Return CcSchedule objects filtered by the id column
 * @method array findByDbStarts(string $starts) Return CcSchedule objects filtered by the starts column
 * @method array findByDbEnds(string $ends) Return CcSchedule objects filtered by the ends column
 * @method array findByDbMediaId(int $media_id) Return CcSchedule objects filtered by the media_id column
 * @method array findByDbClipLength(string $clip_length) Return CcSchedule objects filtered by the clip_length column
 * @method array findByDbFadeIn(string $fade_in) Return CcSchedule objects filtered by the fade_in column
 * @method array findByDbFadeOut(string $fade_out) Return CcSchedule objects filtered by the fade_out column
 * @method array findByDbCueIn(string $cue_in) Return CcSchedule objects filtered by the cue_in column
 * @method array findByDbCueOut(string $cue_out) Return CcSchedule objects filtered by the cue_out column
 * @method array findByDbMediaItemPlayed(boolean $media_item_played) Return CcSchedule objects filtered by the media_item_played column
 * @method array findByDbInstanceId(int $instance_id) Return CcSchedule objects filtered by the instance_id column
 * @method array findByDbPlayoutStatus(int $playout_status) Return CcSchedule objects filtered by the playout_status column
 * @method array findByDbBroadcasted(int $broadcasted) Return CcSchedule objects filtered by the broadcasted column
 * @method array findByDbPosition(int $position) Return CcSchedule objects filtered by the position column
 *
 * @package    propel.generator.airtime.om
 */
abstract class BaseCcScheduleQuery extends ModelCriteria
{
    /**
     * Initializes internal state of BaseCcScheduleQuery object.
     *
     * @param     string $dbName The dabase name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = null, $modelName = null, $modelAlias = null)
    {
        if (null === $dbName) {
            $dbName = 'airtime';
        }
        if (null === $modelName) {
            $modelName = 'Airtime\\CcSchedule';
        }
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new CcScheduleQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param   CcScheduleQuery|Criteria $criteria Optional Criteria to build the query from
     *
     * @return CcScheduleQuery
     */
    public static function create($modelAlias = null, $criteria = null)
    {
        if ($criteria instanceof CcScheduleQuery) {
            return $criteria;
        }
        $query = new CcScheduleQuery(null, null, $modelAlias);

        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param     PropelPDO $con an optional connection object
     *
     * @return   CcSchedule|CcSchedule[]|mixed the result, formatted by the current formatter
     */
    public function findPk($key, $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = CcSchedulePeer::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getConnection(CcSchedulePeer::DATABASE_NAME, Propel::CONNECTION_READ);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Alias of findPk to use instance pooling
     *
     * @param     mixed $key Primary key to use for the query
     * @param     PropelPDO $con A connection object
     *
     * @return                 CcSchedule A model object, or null if the key is not found
     * @throws PropelException
     */
     public function findOneByDbId($key, $con = null)
     {
        return $this->findPk($key, $con);
     }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     PropelPDO $con A connection object
     *
     * @return                 CcSchedule A model object, or null if the key is not found
     * @throws PropelException
     */
    protected function findPkSimple($key, $con)
    {
        $sql = 'SELECT "id", "starts", "ends", "media_id", "clip_length", "fade_in", "fade_out", "cue_in", "cue_out", "media_item_played", "instance_id", "playout_status", "broadcasted", "position" FROM "cc_schedule" WHERE "id" = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $obj = new CcSchedule();
            $obj->hydrate($row);
            CcSchedulePeer::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     PropelPDO $con A connection object
     *
     * @return CcSchedule|CcSchedule[]|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $stmt = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($stmt);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     PropelPDO $con an optional connection object
     *
     * @return PropelObjectCollection|CcSchedule[]|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, $con = null)
    {
        if ($con === null) {
            $con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_READ);
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $stmt = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($stmt);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(CcSchedulePeer::ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(CcSchedulePeer::ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterByDbId(1234); // WHERE id = 1234
     * $query->filterByDbId(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterByDbId(array('min' => 12)); // WHERE id >= 12
     * $query->filterByDbId(array('max' => 12)); // WHERE id <= 12
     * </code>
     *
     * @param     mixed $dbId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbId($dbId = null, $comparison = null)
    {
        if (is_array($dbId)) {
            $useMinMax = false;
            if (isset($dbId['min'])) {
                $this->addUsingAlias(CcSchedulePeer::ID, $dbId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbId['max'])) {
                $this->addUsingAlias(CcSchedulePeer::ID, $dbId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::ID, $dbId, $comparison);
    }

    /**
     * Filter the query on the starts column
     *
     * Example usage:
     * <code>
     * $query->filterByDbStarts('2011-03-14'); // WHERE starts = '2011-03-14'
     * $query->filterByDbStarts('now'); // WHERE starts = '2011-03-14'
     * $query->filterByDbStarts(array('max' => 'yesterday')); // WHERE starts < '2011-03-13'
     * </code>
     *
     * @param     mixed $dbStarts The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbStarts($dbStarts = null, $comparison = null)
    {
        if (is_array($dbStarts)) {
            $useMinMax = false;
            if (isset($dbStarts['min'])) {
                $this->addUsingAlias(CcSchedulePeer::STARTS, $dbStarts['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbStarts['max'])) {
                $this->addUsingAlias(CcSchedulePeer::STARTS, $dbStarts['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::STARTS, $dbStarts, $comparison);
    }

    /**
     * Filter the query on the ends column
     *
     * Example usage:
     * <code>
     * $query->filterByDbEnds('2011-03-14'); // WHERE ends = '2011-03-14'
     * $query->filterByDbEnds('now'); // WHERE ends = '2011-03-14'
     * $query->filterByDbEnds(array('max' => 'yesterday')); // WHERE ends < '2011-03-13'
     * </code>
     *
     * @param     mixed $dbEnds The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbEnds($dbEnds = null, $comparison = null)
    {
        if (is_array($dbEnds)) {
            $useMinMax = false;
            if (isset($dbEnds['min'])) {
                $this->addUsingAlias(CcSchedulePeer::ENDS, $dbEnds['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbEnds['max'])) {
                $this->addUsingAlias(CcSchedulePeer::ENDS, $dbEnds['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::ENDS, $dbEnds, $comparison);
    }

    /**
     * Filter the query on the media_id column
     *
     * Example usage:
     * <code>
     * $query->filterByDbMediaId(1234); // WHERE media_id = 1234
     * $query->filterByDbMediaId(array(12, 34)); // WHERE media_id IN (12, 34)
     * $query->filterByDbMediaId(array('min' => 12)); // WHERE media_id >= 12
     * $query->filterByDbMediaId(array('max' => 12)); // WHERE media_id <= 12
     * </code>
     *
     * @see       filterByMediaItem()
     *
     * @param     mixed $dbMediaId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbMediaId($dbMediaId = null, $comparison = null)
    {
        if (is_array($dbMediaId)) {
            $useMinMax = false;
            if (isset($dbMediaId['min'])) {
                $this->addUsingAlias(CcSchedulePeer::MEDIA_ID, $dbMediaId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbMediaId['max'])) {
                $this->addUsingAlias(CcSchedulePeer::MEDIA_ID, $dbMediaId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::MEDIA_ID, $dbMediaId, $comparison);
    }

    /**
     * Filter the query on the clip_length column
     *
     * Example usage:
     * <code>
     * $query->filterByDbClipLength('fooValue');   // WHERE clip_length = 'fooValue'
     * $query->filterByDbClipLength('%fooValue%'); // WHERE clip_length LIKE '%fooValue%'
     * </code>
     *
     * @param     string $dbClipLength The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbClipLength($dbClipLength = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($dbClipLength)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $dbClipLength)) {
                $dbClipLength = str_replace('*', '%', $dbClipLength);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::CLIP_LENGTH, $dbClipLength, $comparison);
    }

    /**
     * Filter the query on the fade_in column
     *
     * Example usage:
     * <code>
     * $query->filterByDbFadeIn(1234); // WHERE fade_in = 1234
     * $query->filterByDbFadeIn(array(12, 34)); // WHERE fade_in IN (12, 34)
     * $query->filterByDbFadeIn(array('min' => 12)); // WHERE fade_in >= 12
     * $query->filterByDbFadeIn(array('max' => 12)); // WHERE fade_in <= 12
     * </code>
     *
     * @param     mixed $dbFadeIn The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbFadeIn($dbFadeIn = null, $comparison = null)
    {
        if (is_array($dbFadeIn)) {
            $useMinMax = false;
            if (isset($dbFadeIn['min'])) {
                $this->addUsingAlias(CcSchedulePeer::FADE_IN, $dbFadeIn['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbFadeIn['max'])) {
                $this->addUsingAlias(CcSchedulePeer::FADE_IN, $dbFadeIn['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::FADE_IN, $dbFadeIn, $comparison);
    }

    /**
     * Filter the query on the fade_out column
     *
     * Example usage:
     * <code>
     * $query->filterByDbFadeOut(1234); // WHERE fade_out = 1234
     * $query->filterByDbFadeOut(array(12, 34)); // WHERE fade_out IN (12, 34)
     * $query->filterByDbFadeOut(array('min' => 12)); // WHERE fade_out >= 12
     * $query->filterByDbFadeOut(array('max' => 12)); // WHERE fade_out <= 12
     * </code>
     *
     * @param     mixed $dbFadeOut The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbFadeOut($dbFadeOut = null, $comparison = null)
    {
        if (is_array($dbFadeOut)) {
            $useMinMax = false;
            if (isset($dbFadeOut['min'])) {
                $this->addUsingAlias(CcSchedulePeer::FADE_OUT, $dbFadeOut['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbFadeOut['max'])) {
                $this->addUsingAlias(CcSchedulePeer::FADE_OUT, $dbFadeOut['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::FADE_OUT, $dbFadeOut, $comparison);
    }

    /**
     * Filter the query on the cue_in column
     *
     * Example usage:
     * <code>
     * $query->filterByDbCueIn('fooValue');   // WHERE cue_in = 'fooValue'
     * $query->filterByDbCueIn('%fooValue%'); // WHERE cue_in LIKE '%fooValue%'
     * </code>
     *
     * @param     string $dbCueIn The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbCueIn($dbCueIn = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($dbCueIn)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $dbCueIn)) {
                $dbCueIn = str_replace('*', '%', $dbCueIn);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::CUE_IN, $dbCueIn, $comparison);
    }

    /**
     * Filter the query on the cue_out column
     *
     * Example usage:
     * <code>
     * $query->filterByDbCueOut('fooValue');   // WHERE cue_out = 'fooValue'
     * $query->filterByDbCueOut('%fooValue%'); // WHERE cue_out LIKE '%fooValue%'
     * </code>
     *
     * @param     string $dbCueOut The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbCueOut($dbCueOut = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($dbCueOut)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $dbCueOut)) {
                $dbCueOut = str_replace('*', '%', $dbCueOut);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::CUE_OUT, $dbCueOut, $comparison);
    }

    /**
     * Filter the query on the media_item_played column
     *
     * Example usage:
     * <code>
     * $query->filterByDbMediaItemPlayed(true); // WHERE media_item_played = true
     * $query->filterByDbMediaItemPlayed('yes'); // WHERE media_item_played = true
     * </code>
     *
     * @param     boolean|string $dbMediaItemPlayed The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbMediaItemPlayed($dbMediaItemPlayed = null, $comparison = null)
    {
        if (is_string($dbMediaItemPlayed)) {
            $dbMediaItemPlayed = in_array(strtolower($dbMediaItemPlayed), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(CcSchedulePeer::MEDIA_ITEM_PLAYED, $dbMediaItemPlayed, $comparison);
    }

    /**
     * Filter the query on the instance_id column
     *
     * Example usage:
     * <code>
     * $query->filterByDbInstanceId(1234); // WHERE instance_id = 1234
     * $query->filterByDbInstanceId(array(12, 34)); // WHERE instance_id IN (12, 34)
     * $query->filterByDbInstanceId(array('min' => 12)); // WHERE instance_id >= 12
     * $query->filterByDbInstanceId(array('max' => 12)); // WHERE instance_id <= 12
     * </code>
     *
     * @see       filterByCcShowInstances()
     *
     * @param     mixed $dbInstanceId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbInstanceId($dbInstanceId = null, $comparison = null)
    {
        if (is_array($dbInstanceId)) {
            $useMinMax = false;
            if (isset($dbInstanceId['min'])) {
                $this->addUsingAlias(CcSchedulePeer::INSTANCE_ID, $dbInstanceId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbInstanceId['max'])) {
                $this->addUsingAlias(CcSchedulePeer::INSTANCE_ID, $dbInstanceId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::INSTANCE_ID, $dbInstanceId, $comparison);
    }

    /**
     * Filter the query on the playout_status column
     *
     * Example usage:
     * <code>
     * $query->filterByDbPlayoutStatus(1234); // WHERE playout_status = 1234
     * $query->filterByDbPlayoutStatus(array(12, 34)); // WHERE playout_status IN (12, 34)
     * $query->filterByDbPlayoutStatus(array('min' => 12)); // WHERE playout_status >= 12
     * $query->filterByDbPlayoutStatus(array('max' => 12)); // WHERE playout_status <= 12
     * </code>
     *
     * @param     mixed $dbPlayoutStatus The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbPlayoutStatus($dbPlayoutStatus = null, $comparison = null)
    {
        if (is_array($dbPlayoutStatus)) {
            $useMinMax = false;
            if (isset($dbPlayoutStatus['min'])) {
                $this->addUsingAlias(CcSchedulePeer::PLAYOUT_STATUS, $dbPlayoutStatus['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbPlayoutStatus['max'])) {
                $this->addUsingAlias(CcSchedulePeer::PLAYOUT_STATUS, $dbPlayoutStatus['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::PLAYOUT_STATUS, $dbPlayoutStatus, $comparison);
    }

    /**
     * Filter the query on the broadcasted column
     *
     * Example usage:
     * <code>
     * $query->filterByDbBroadcasted(1234); // WHERE broadcasted = 1234
     * $query->filterByDbBroadcasted(array(12, 34)); // WHERE broadcasted IN (12, 34)
     * $query->filterByDbBroadcasted(array('min' => 12)); // WHERE broadcasted >= 12
     * $query->filterByDbBroadcasted(array('max' => 12)); // WHERE broadcasted <= 12
     * </code>
     *
     * @param     mixed $dbBroadcasted The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbBroadcasted($dbBroadcasted = null, $comparison = null)
    {
        if (is_array($dbBroadcasted)) {
            $useMinMax = false;
            if (isset($dbBroadcasted['min'])) {
                $this->addUsingAlias(CcSchedulePeer::BROADCASTED, $dbBroadcasted['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbBroadcasted['max'])) {
                $this->addUsingAlias(CcSchedulePeer::BROADCASTED, $dbBroadcasted['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::BROADCASTED, $dbBroadcasted, $comparison);
    }

    /**
     * Filter the query on the position column
     *
     * Example usage:
     * <code>
     * $query->filterByDbPosition(1234); // WHERE position = 1234
     * $query->filterByDbPosition(array(12, 34)); // WHERE position IN (12, 34)
     * $query->filterByDbPosition(array('min' => 12)); // WHERE position >= 12
     * $query->filterByDbPosition(array('max' => 12)); // WHERE position <= 12
     * </code>
     *
     * @param     mixed $dbPosition The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function filterByDbPosition($dbPosition = null, $comparison = null)
    {
        if (is_array($dbPosition)) {
            $useMinMax = false;
            if (isset($dbPosition['min'])) {
                $this->addUsingAlias(CcSchedulePeer::POSITION, $dbPosition['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbPosition['max'])) {
                $this->addUsingAlias(CcSchedulePeer::POSITION, $dbPosition['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CcSchedulePeer::POSITION, $dbPosition, $comparison);
    }

    /**
     * Filter the query by a related CcShowInstances object
     *
     * @param   CcShowInstances|PropelObjectCollection $ccShowInstances The related object(s) to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return                 CcScheduleQuery The current query, for fluid interface
     * @throws PropelException - if the provided filter is invalid.
     */
    public function filterByCcShowInstances($ccShowInstances, $comparison = null)
    {
        if ($ccShowInstances instanceof CcShowInstances) {
            return $this
                ->addUsingAlias(CcSchedulePeer::INSTANCE_ID, $ccShowInstances->getDbId(), $comparison);
        } elseif ($ccShowInstances instanceof PropelObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(CcSchedulePeer::INSTANCE_ID, $ccShowInstances->toKeyValue('PrimaryKey', 'DbId'), $comparison);
        } else {
            throw new PropelException('filterByCcShowInstances() only accepts arguments of type CcShowInstances or PropelCollection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the CcShowInstances relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function joinCcShowInstances($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('CcShowInstances');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'CcShowInstances');
        }

        return $this;
    }

    /**
     * Use the CcShowInstances relation CcShowInstances object
     *
     * @see       useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \Airtime\CcShowInstancesQuery A secondary query class using the current class as primary query
     */
    public function useCcShowInstancesQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinCcShowInstances($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'CcShowInstances', '\Airtime\CcShowInstancesQuery');
    }

    /**
     * Filter the query by a related MediaItem object
     *
     * @param   MediaItem|PropelObjectCollection $mediaItem The related object(s) to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return                 CcScheduleQuery The current query, for fluid interface
     * @throws PropelException - if the provided filter is invalid.
     */
    public function filterByMediaItem($mediaItem, $comparison = null)
    {
        if ($mediaItem instanceof MediaItem) {
            return $this
                ->addUsingAlias(CcSchedulePeer::MEDIA_ID, $mediaItem->getId(), $comparison);
        } elseif ($mediaItem instanceof PropelObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(CcSchedulePeer::MEDIA_ID, $mediaItem->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByMediaItem() only accepts arguments of type MediaItem or PropelCollection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the MediaItem relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function joinMediaItem($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('MediaItem');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'MediaItem');
        }

        return $this;
    }

    /**
     * Use the MediaItem relation MediaItem object
     *
     * @see       useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \Airtime\MediaItemQuery A secondary query class using the current class as primary query
     */
    public function useMediaItemQuery($relationAlias = null, $joinType = Criteria::LEFT_JOIN)
    {
        return $this
            ->joinMediaItem($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'MediaItem', '\Airtime\MediaItemQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   CcSchedule $ccSchedule Object to remove from the list of results
     *
     * @return CcScheduleQuery The current query, for fluid interface
     */
    public function prune($ccSchedule = null)
    {
        if ($ccSchedule) {
            $this->addUsingAlias(CcSchedulePeer::ID, $ccSchedule->getDbId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

}
