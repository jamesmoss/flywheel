<?php

namespace JamesMoss\Flywheel;

/**
 * Query
 *
 * Builds an executes a query whichs searches and sorts documents from a
 * repository.
 *
 * @todo turn the limit and order by arrays into value objects
 */
class Query
{
    protected $repo;
    protected $predicate;
    protected $limit   = array();
    protected $orderBy = array();

    /**
     * Constructor
     *
     * @param Repository $repository The repo this query will run against.
     */
    public function __construct(Repository $repository)
    {
        $this->repo = $repository;
        $this->predicate = new Predicate();
    }

    /**
     * Set a limit on the number of documents returned. An offset from 0 can
     * also be specified.
     *
     * @param int $count  The number of documents to return.
     * @param int $offset The offset from which to return.
     *
     * @return Query The same instance of this class.
     */
    public function limit($count, $offset = 0)
    {
        $this->limit = array((int) $count, (int) $offset);

        return $this;
    }

    /**
     * Sets the fields to order the results by. They should be in the
     * the format 'fieldname ASC|DESC'. e.g 'dateAdded DESC'.
     *
     * @param mixed $fields An array comprising strings in the above format
     *                      (or a single string)
     *
     * @return Query The same instance of this class.
     */
    public function orderBy($fields)
    {
        $this->orderBy = (array) $fields;

        return $this;
    }

    /**
     * @see Query::andWhere
     *
     * @return Query The same instance of this class.
     */
    public function where($field, $operator = null, $value = null)
    {
        return $this->andWhere($field, $operator, $value);
    }

    /**
     * Adds a boolean AND predicate for this query,
     *
     * @param string|Closure $field    The name of the field to match or an anonymous
     *                                 function that will define sub predicates.
     * @param string         $operator An operator from the allowed list.
     * @param string         $value    The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function andWhere($field, $operator = null, $value = null)
    {
        $this->predicate->andWhere($field, $operator, $value);

        return $this;
    }

    /**
     * Adds a boolean OR predicate for this query,
     *
     * @param string|Closure $field    The name of the field to match or an anonymous
     *                                 function that will define sub predicates.
     * @param string         $operator An operator from the allowed list.
     * @param string         $value    The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function orWhere($field, $operator = null, $value = null)
    {
        $this->predicate->orWhere($field, $operator, $value);

        return $this;
    }

    /**
     * Runs the query.
     *
     * @return Result The documents returned from this query.
     */
    public function execute()
    {
        $qe = new QueryExecuter($this->repo, $this->predicate, $this->limit, $this->orderBy);

        return $qe->run();
    }
}
