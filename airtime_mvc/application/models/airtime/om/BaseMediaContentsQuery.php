<?php

namespace Airtime\MediaItem\om;

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
use Airtime\MediaItem;
use Airtime\MediaItem\MediaContents;
use Airtime\MediaItem\MediaContentsPeer;
use Airtime\MediaItem\MediaContentsQuery;

/**
 * Base class that represents a query for the 'media_contents' table.
 *
 *
 *
 * @method MediaContentsQuery orderByDbId($order = Criteria::ASC) Order by the id column
 * @method MediaContentsQuery orderByMediaId($order = Criteria::ASC) Order by the media_id column
 * @method MediaContentsQuery orderByPosition($order = Criteria::ASC) Order by the position column
 * @method MediaContentsQuery orderByTrackOffset($order = Criteria::ASC) Order by the trackoffset column
 * @method MediaContentsQuery orderByCliplength($order = Criteria::ASC) Order by the cliplength column
 * @method MediaContentsQuery orderByCuein($order = Criteria::ASC) Order by the cuein column
 * @method MediaContentsQuery orderByCueout($order = Criteria::ASC) Order by the cueout column
 * @method MediaContentsQuery orderByFadein($order = Criteria::ASC) Order by the fadein column
 * @method MediaContentsQuery orderByFadeout($order = Criteria::ASC) Order by the fadeout column
 *
 * @method MediaContentsQuery groupByDbId() Group by the id column
 * @method MediaContentsQuery groupByMediaId() Group by the media_id column
 * @method MediaContentsQuery groupByPosition() Group by the position column
 * @method MediaContentsQuery groupByTrackOffset() Group by the trackoffset column
 * @method MediaContentsQuery groupByCliplength() Group by the cliplength column
 * @method MediaContentsQuery groupByCuein() Group by the cuein column
 * @method MediaContentsQuery groupByCueout() Group by the cueout column
 * @method MediaContentsQuery groupByFadein() Group by the fadein column
 * @method MediaContentsQuery groupByFadeout() Group by the fadeout column
 *
 * @method MediaContentsQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method MediaContentsQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method MediaContentsQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method MediaContentsQuery leftJoinMediaItem($relationAlias = null) Adds a LEFT JOIN clause to the query using the MediaItem relation
 * @method MediaContentsQuery rightJoinMediaItem($relationAlias = null) Adds a RIGHT JOIN clause to the query using the MediaItem relation
 * @method MediaContentsQuery innerJoinMediaItem($relationAlias = null) Adds a INNER JOIN clause to the query using the MediaItem relation
 *
 * @method MediaContents findOne(PropelPDO $con = null) Return the first MediaContents matching the query
 * @method MediaContents findOneOrCreate(PropelPDO $con = null) Return the first MediaContents matching the query, or a new MediaContents object populated from the query conditions when no match is found
 *
 * @method MediaContents findOneByMediaId(int $media_id) Return the first MediaContents filtered by the media_id column
 * @method MediaContents findOneByPosition(int $position) Return the first MediaContents filtered by the position column
 * @method MediaContents findOneByTrackOffset(double $trackoffset) Return the first MediaContents filtered by the trackoffset column
 * @method MediaContents findOneByCliplength(string $cliplength) Return the first MediaContents filtered by the cliplength column
 * @method MediaContents findOneByCuein(string $cuein) Return the first MediaContents filtered by the cuein column
 * @method MediaContents findOneByCueout(string $cueout) Return the first MediaContents filtered by the cueout column
 * @method MediaContents findOneByFadein(string $fadein) Return the first MediaContents filtered by the fadein column
 * @method MediaContents findOneByFadeout(string $fadeout) Return the first MediaContents filtered by the fadeout column
 *
 * @method array findByDbId(int $id) Return MediaContents objects filtered by the id column
 * @method array findByMediaId(int $media_id) Return MediaContents objects filtered by the media_id column
 * @method array findByPosition(int $position) Return MediaContents objects filtered by the position column
 * @method array findByTrackOffset(double $trackoffset) Return MediaContents objects filtered by the trackoffset column
 * @method array findByCliplength(string $cliplength) Return MediaContents objects filtered by the cliplength column
 * @method array findByCuein(string $cuein) Return MediaContents objects filtered by the cuein column
 * @method array findByCueout(string $cueout) Return MediaContents objects filtered by the cueout column
 * @method array findByFadein(string $fadein) Return MediaContents objects filtered by the fadein column
 * @method array findByFadeout(string $fadeout) Return MediaContents objects filtered by the fadeout column
 *
 * @package    propel.generator.airtime.om
 */
abstract class BaseMediaContentsQuery extends ModelCriteria
{
    /**
     * Initializes internal state of BaseMediaContentsQuery object.
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
            $modelName = 'Airtime\\MediaItem\\MediaContents';
        }
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new MediaContentsQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param   MediaContentsQuery|Criteria $criteria Optional Criteria to build the query from
     *
     * @return MediaContentsQuery
     */
    public static function create($modelAlias = null, $criteria = null)
    {
        if ($criteria instanceof MediaContentsQuery) {
            return $criteria;
        }
        $query = new MediaContentsQuery(null, null, $modelAlias);

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
     * @return   MediaContents|MediaContents[]|mixed the result, formatted by the current formatter
     */
    public function findPk($key, $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = MediaContentsPeer::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getConnection(MediaContentsPeer::DATABASE_NAME, Propel::CONNECTION_READ);
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
     * @return                 MediaContents A model object, or null if the key is not found
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
     * @return                 MediaContents A model object, or null if the key is not found
     * @throws PropelException
     */
    protected function findPkSimple($key, $con)
    {
        $sql = 'SELECT "id", "media_id", "position", "trackoffset", "cliplength", "cuein", "cueout", "fadein", "fadeout" FROM "media_contents" WHERE "id" = :p0';
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
            $obj = new MediaContents();
            $obj->hydrate($row);
            MediaContentsPeer::addInstanceToPool($obj, (string) $key);
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
     * @return MediaContents|MediaContents[]|mixed the result, formatted by the current formatter
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
     * @return PropelObjectCollection|MediaContents[]|mixed the list of results, formatted by the current formatter
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
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(MediaContentsPeer::ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(MediaContentsPeer::ID, $keys, Criteria::IN);
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
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByDbId($dbId = null, $comparison = null)
    {
        if (is_array($dbId)) {
            $useMinMax = false;
            if (isset($dbId['min'])) {
                $this->addUsingAlias(MediaContentsPeer::ID, $dbId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($dbId['max'])) {
                $this->addUsingAlias(MediaContentsPeer::ID, $dbId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::ID, $dbId, $comparison);
    }

    /**
     * Filter the query on the media_id column
     *
     * Example usage:
     * <code>
     * $query->filterByMediaId(1234); // WHERE media_id = 1234
     * $query->filterByMediaId(array(12, 34)); // WHERE media_id IN (12, 34)
     * $query->filterByMediaId(array('min' => 12)); // WHERE media_id >= 12
     * $query->filterByMediaId(array('max' => 12)); // WHERE media_id <= 12
     * </code>
     *
     * @see       filterByMediaItem()
     *
     * @param     mixed $mediaId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByMediaId($mediaId = null, $comparison = null)
    {
        if (is_array($mediaId)) {
            $useMinMax = false;
            if (isset($mediaId['min'])) {
                $this->addUsingAlias(MediaContentsPeer::MEDIA_ID, $mediaId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($mediaId['max'])) {
                $this->addUsingAlias(MediaContentsPeer::MEDIA_ID, $mediaId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::MEDIA_ID, $mediaId, $comparison);
    }

    /**
     * Filter the query on the position column
     *
     * Example usage:
     * <code>
     * $query->filterByPosition(1234); // WHERE position = 1234
     * $query->filterByPosition(array(12, 34)); // WHERE position IN (12, 34)
     * $query->filterByPosition(array('min' => 12)); // WHERE position >= 12
     * $query->filterByPosition(array('max' => 12)); // WHERE position <= 12
     * </code>
     *
     * @param     mixed $position The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByPosition($position = null, $comparison = null)
    {
        if (is_array($position)) {
            $useMinMax = false;
            if (isset($position['min'])) {
                $this->addUsingAlias(MediaContentsPeer::POSITION, $position['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($position['max'])) {
                $this->addUsingAlias(MediaContentsPeer::POSITION, $position['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::POSITION, $position, $comparison);
    }

    /**
     * Filter the query on the trackoffset column
     *
     * Example usage:
     * <code>
     * $query->filterByTrackOffset(1234); // WHERE trackoffset = 1234
     * $query->filterByTrackOffset(array(12, 34)); // WHERE trackoffset IN (12, 34)
     * $query->filterByTrackOffset(array('min' => 12)); // WHERE trackoffset >= 12
     * $query->filterByTrackOffset(array('max' => 12)); // WHERE trackoffset <= 12
     * </code>
     *
     * @param     mixed $trackOffset The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByTrackOffset($trackOffset = null, $comparison = null)
    {
        if (is_array($trackOffset)) {
            $useMinMax = false;
            if (isset($trackOffset['min'])) {
                $this->addUsingAlias(MediaContentsPeer::TRACKOFFSET, $trackOffset['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($trackOffset['max'])) {
                $this->addUsingAlias(MediaContentsPeer::TRACKOFFSET, $trackOffset['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::TRACKOFFSET, $trackOffset, $comparison);
    }

    /**
     * Filter the query on the cliplength column
     *
     * Example usage:
     * <code>
     * $query->filterByCliplength('fooValue');   // WHERE cliplength = 'fooValue'
     * $query->filterByCliplength('%fooValue%'); // WHERE cliplength LIKE '%fooValue%'
     * </code>
     *
     * @param     string $cliplength The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByCliplength($cliplength = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($cliplength)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $cliplength)) {
                $cliplength = str_replace('*', '%', $cliplength);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::CLIPLENGTH, $cliplength, $comparison);
    }

    /**
     * Filter the query on the cuein column
     *
     * Example usage:
     * <code>
     * $query->filterByCuein('fooValue');   // WHERE cuein = 'fooValue'
     * $query->filterByCuein('%fooValue%'); // WHERE cuein LIKE '%fooValue%'
     * </code>
     *
     * @param     string $cuein The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByCuein($cuein = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($cuein)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $cuein)) {
                $cuein = str_replace('*', '%', $cuein);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::CUEIN, $cuein, $comparison);
    }

    /**
     * Filter the query on the cueout column
     *
     * Example usage:
     * <code>
     * $query->filterByCueout('fooValue');   // WHERE cueout = 'fooValue'
     * $query->filterByCueout('%fooValue%'); // WHERE cueout LIKE '%fooValue%'
     * </code>
     *
     * @param     string $cueout The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByCueout($cueout = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($cueout)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $cueout)) {
                $cueout = str_replace('*', '%', $cueout);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::CUEOUT, $cueout, $comparison);
    }

    /**
     * Filter the query on the fadein column
     *
     * Example usage:
     * <code>
     * $query->filterByFadein('2011-03-14'); // WHERE fadein = '2011-03-14'
     * $query->filterByFadein('now'); // WHERE fadein = '2011-03-14'
     * $query->filterByFadein(array('max' => 'yesterday')); // WHERE fadein < '2011-03-13'
     * </code>
     *
     * @param     mixed $fadein The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByFadein($fadein = null, $comparison = null)
    {
        if (is_array($fadein)) {
            $useMinMax = false;
            if (isset($fadein['min'])) {
                $this->addUsingAlias(MediaContentsPeer::FADEIN, $fadein['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($fadein['max'])) {
                $this->addUsingAlias(MediaContentsPeer::FADEIN, $fadein['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::FADEIN, $fadein, $comparison);
    }

    /**
     * Filter the query on the fadeout column
     *
     * Example usage:
     * <code>
     * $query->filterByFadeout('2011-03-14'); // WHERE fadeout = '2011-03-14'
     * $query->filterByFadeout('now'); // WHERE fadeout = '2011-03-14'
     * $query->filterByFadeout(array('max' => 'yesterday')); // WHERE fadeout < '2011-03-13'
     * </code>
     *
     * @param     mixed $fadeout The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function filterByFadeout($fadeout = null, $comparison = null)
    {
        if (is_array($fadeout)) {
            $useMinMax = false;
            if (isset($fadeout['min'])) {
                $this->addUsingAlias(MediaContentsPeer::FADEOUT, $fadeout['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($fadeout['max'])) {
                $this->addUsingAlias(MediaContentsPeer::FADEOUT, $fadeout['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(MediaContentsPeer::FADEOUT, $fadeout, $comparison);
    }

    /**
     * Filter the query by a related MediaItem object
     *
     * @param   MediaItem|PropelObjectCollection $mediaItem The related object(s) to use as filter
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return                 MediaContentsQuery The current query, for fluid interface
     * @throws PropelException - if the provided filter is invalid.
     */
    public function filterByMediaItem($mediaItem, $comparison = null)
    {
        if ($mediaItem instanceof MediaItem) {
            return $this
                ->addUsingAlias(MediaContentsPeer::MEDIA_ID, $mediaItem->getId(), $comparison);
        } elseif ($mediaItem instanceof PropelObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(MediaContentsPeer::MEDIA_ID, $mediaItem->toKeyValue('PrimaryKey', 'Id'), $comparison);
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
     * @return MediaContentsQuery The current query, for fluid interface
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
     * @param   MediaContents $mediaContents Object to remove from the list of results
     *
     * @return MediaContentsQuery The current query, for fluid interface
     */
    public function prune($mediaContents = null)
    {
        if ($mediaContents) {
            $this->addUsingAlias(MediaContentsPeer::ID, $mediaContents->getDbId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

}
