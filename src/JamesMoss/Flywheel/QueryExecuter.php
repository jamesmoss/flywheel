<?php

namespace JamesMoss\Flywheel;

/**
 * Query
 *
 * Builds an executes a query whichs searches and sorts documents from a
 * repository.
 */
class QueryExecuter
{
    protected $repo;
    protected $predicate;
    protected $limit;
    protected $orderBy;

    /**
     * Constructor
     *
     * @param Repository $repo The repo to execute against
     * @param Predicate $pred The predicate to use.
     * @param array $limit The count and offset.
     * @param array $orderBy An array of field names to order by
     */
    public function __construct(Repository $repo, Predicate $pred, array $limit, array $orderBy)
    {
        $this->repo = $repo;
        $this->predicate = $pred;
        $this->limit = $limit;
        $this->orderBy = $orderBy;
    }

    /**
     * Runs the query.
     *
     * @return Result The documents returned from this query.
     */
    public function run()
    {
        $documents = $this->repo->findAll();
        $pred = $this->predicate->getAll();

        if ($pred) {
            list($discard, $field, $operator, $value) = $pred[0];

            $documents = array_filter($documents, function ($doc) use ($field, $operator, $value) {
                return $this->matchDocument($doc, $field, $operator, $value);
            });
        }

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

        return new Result(array_values($documents), $totalCount);
    }

    protected function getFieldValue($doc, $field, &$found = false)
    {
        $found = false;

        if ($field === '__id') {
            $found = true;

            return $doc->getId();
        }

        if (false !== strpos($field, '.')) {
            return $doc->getNestedProperty($field, $found);
        }

        if (!property_exists($doc, $field)) {
            return false;
        }

        $found = true;

        return $doc->{$field};
    }

    protected function matchDocument($doc, $field, $operator, $value)
    {
        $docVal = $this->getFieldValue($doc, $field, $found);

        if (!$found) {
            return false;
        }

        switch (true) {
            case ($operator === '==' && $docVal == $value): return true;
            case ($operator === '===' && $docVal === $value): return true;
            case ($operator === '!=' && $docVal != $value): return true;
            case ($operator === '!==' && $docVal !== $value): return true;
            case ($operator === '>'  && $docVal >  $value): return true;
            case ($operator === '>=' && $docVal >= $value): return true;
            case ($operator === '<'  && $docVal <  $value): return true;
            case ($operator === '>=' && $docVal >= $value): return true;
            case ($operator === 'IN' && in_array($docVal, (array)$value)): return true;
        }

        return false;
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
                if($keyName == 'id' || $keyName == '__id') {
                    $valueA = $a->getId();
                    $valueB = $b->getId();
                } else {
                    $valueA = $this->getFieldValue($a, $keyName, $found);
                    if ($found === false) {
                        $valueA = null;
                    }
                    $valueB = $this->getFieldValue($b, $keyName, $found);
                    if ($found === false) {
                        $valueB = null;
                    }
                }

                if (is_string($valueA)) {
                    $cmp = strcmp($valueA, $valueB);
                } elseif (is_bool($valueA)) {
                    $cmp = $valueA - $valueB;
                } else {
                    $cmp = ($valueA == $valueB) ? 0 : (($valueA < $valueB) ? -1 : 1);
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
