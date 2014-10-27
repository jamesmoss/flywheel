<?php

namespace JamesMoss\Flywheel;

/**
 * Query
 *
 * Builds an executes a query whichs searches and sorts documents from a
 * repository.
 */
class Query
{
    protected $repo;
    protected $limit   = false;
    protected $orderBy = false;

    /** @var QueryWhere */
    protected $where;

    /**
     * Constructor
     *
     * @param Repository $repository The repo this query will run against.
     */
    public function __construct(Repository $repository)
    {
        $this->repo = $repository;
        $this->where = new QueryWhere;
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
    public function limit($count, $offset)
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
     * Sets the predicates for this query,
     *
     * @param string $field    The name of the field to match.
     * @param string $operator An operator from the allowed list.
     * @param string $value    The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function where($field, $operator = null, $value = null)
    {
        $this->where->whereAnd($field, $operator, $value);
        return $this;
    }

    /**
     * Add the predicates for this query,
     *
     * @param string $field    The name of the field to match.
     * @param string $operator An operator from the allowed list.
     * @param string $value    The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function whereAnd($field, $operator, $value)
    {
        $this->where($field, $operator, $value);
        return $this;
    }

    /**
     * Add the predicates for this query,
     *
     * @param string $field    The name of the field to match.
     * @param string $operator An operator from the allowed list.
     * @param string $value    The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function whereOr($field, $operator, $value)
    {
        $this->where->whereOr($field, $operator, $value);
        return $this;
    }

    /**
     * Runs the query.
     *
     * @return Result The documents returned from this query.
     */
    public function execute()
    {
        $documents = $this->repo->findAll();

        $documents = array_filter($documents, array(new QueryFilter($this->where), 'filter'));

        if ($this->orderBy) {
            $sorts = array();
            foreach ($this->orderBy as $order) {
                $parts = explode(' ', $order, 2);
                // TODO - validate parts
                $sorts[] = array(
                    $parts[0],
                    isset($parts[1]) && $parts[1] == 'DESC' ? SORT_DESC : SORT_ASC
                );
            }

            $documents = $this->sort($documents, $sorts);
        }

        $totalCount = count($documents);

        if ($this->limit) {
            list($count, $offset) = $this->limit;
            $documents = array_slice($documents, $offset, $count);
        }

        return new Result($documents, $totalCount);
    }

    /**
     * Sorts an array of documents by multiple fields if needed.
     *
     * @param array $array An array of Documents.
     * @param array $args  The fields to sort by.
     *
     * @return array The sorted array of documents.
     */
    protected function sort(array $array, array $args)
    {
        $c = count($args);

        usort($array, function ($a, $b) use ($args, $c) {
            $i   = 0;
            $cmp = 0;
            while ($cmp == 0 && $i < $c) {
                $keyName = $args[$i][0];
                if($keyName == 'id') {
                    $valueA = $a->getId();
                    $valueB = $b->getId();
                } else {
                    $valueA = isset($a->{$keyName}) ? $a->{$keyName} : null;
                    $valueB = isset($b->{$keyName}) ? $b->{$keyName} : null; 
                }

                if (is_string($valueA)) {
                    $cmp = strcmp($valueA, $valueB);
                } elseif (is_bool($valueA)) {
                    $cmp = $valueA - $valueB;
                } else {
                    $cmp = ($valueA == $valueB) ? 0 : (($valueA > $valueB) ? -1 : 1);
                }

                if ($args[$i][1] === SORT_DESC) {
                    $cmp *= -1;
                }
                $i++;
            }

            return $cmp;
        });

        return $array;
    }
}
