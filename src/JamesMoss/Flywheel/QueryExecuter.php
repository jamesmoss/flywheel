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
    protected $indexes;

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
        $this->indexes = $repo->getIndexes();
    }

    /**
     * Runs the query.
     *
     * @return Result The documents returned from this query.
     */
    public function run()
    {
        /** @var array<int,Document> $documents */
        $documents;
        if ($predicates = $this->predicate->getAll()) {
            if ($this->isFullIndex($predicates)) {
                $documents = $this->findByIndex($predicates);
            } else {
                $documents = $this->repo->findAll();
                $documents = $this->filter($documents, $predicates);
            }
        } else {
            $documents = $this->repo->findAll();
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

    public function getFieldValue($doc, $field, &$found = false)
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

    public function matchDocument($doc, $field, $operator, $value)
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
     * Checks if the query can be executed with indexes only.
     *
     * @param array<int,array> $predicates the array of predicates.
     *
     * @return bool true if it can.
     */
    protected function isFullIndex($predicates)
    {
        foreach ($predicates as $p) {
            list($type, $field, $operator) = $p;
            if (!isset($this->indexes[$field]) || !$this->indexes[$field]->isOperatorCompatible($operator)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Filters an array of documents by the predicates.
     *
     * @param array<int,Document> $documents the array to filter.
     * @param array<int,array> $predicates the array of predicates.
     *
     * @return array<int,Document> the filtered array of documents.
     */
    protected function filter($documents, $predicates)
    {
        $result = array();
        $originalDocs = $documents;

        $andPredicates = array_filter($predicates, function($pred) {
            return $pred[0] !== Predicate::LOGICAL_OR;
        });

        $orPredicates = array_filter($predicates, function($pred) {
            return $pred[0] === Predicate::LOGICAL_OR;
        });

        // 5.3 hack for accessing $this inside closure.
        $self = $this;

        foreach($andPredicates as $predicate) {
            if (is_array($predicate[1])) {
                $documents = $this->filter($documents, $predicate[1]);
            } else {
                list($type, $field, $operator, $value) = $predicate;


                $documents = array_values(array_filter($documents, function ($doc) use ($self, $field, $operator, $value) {
                    return $self->matchDocument($doc, $field, $operator, $value);
                }));
            }

            $result = $documents;
        }

        foreach($orPredicates as $predicate) {
            if (is_array($predicate[1])) {
                $documents = $this->filter($originalDocs, $predicate[1]);
            } else {
                list($type, $field, $operator, $value) = $predicate;

                $documents = array_values(array_filter($originalDocs, function ($doc) use ($self, $field, $operator, $value) {
                    return $self->matchDocument($doc, $field, $operator, $value);
                }));
            }

            $result = array_unique(array_merge($result, $documents), SORT_REGULAR);
        }

        return $result;
    }

    /**
     * Find an array of documents from the predicates using the indexes.
     *
     * @param array<int,array> $predicates the array of predicates.
     *
     * @return array<int,Document> the filtered array of documents.
     */
    protected function findByIndex($predicates) {
        $result = array();
        $ids = array();

        $andPredicates = array_filter($predicates, function($pred) {
            return $pred[0] !== Predicate::LOGICAL_OR;
        });

        $orPredicates = array_filter($predicates, function($pred) {
            return $pred[0] === Predicate::LOGICAL_OR;
        });

        foreach($andPredicates as $predicate) {
            if (is_array($predicate[1])) {
                $ids = $this->findByIndex($predicate[1]);
            } else {
                list($type, $field, $operator, $value) = $predicate;
                $ids = $this->indexes[$field]->get($value, $operator);
            }

            $result = $ids;
        }

        foreach($orPredicates as $predicate) {
            if (is_array($predicate[1])) {
                $ids = $this->findByIndex($predicate[1]);
            } else {
                list($type, $field, $operator, $value) = $predicate;
                $ids = $this->indexes['$field']->get($value, $operator);
            }

            $result = array_unique(array_merge($result, $ids), SORT_REGULAR);
        }

        return $this->repo->findByIds($result);
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

        // PHP 5.3 hack
        $self = $this;

        usort($array, function ($a, $b) use ($self, $args, $c) {
            $i   = 0;
            $cmp = 0;
            while ($cmp == 0 && $i < $c) {
                $keyName = $args[$i][0];
                if($keyName == 'id' || $keyName == '__id') {
                    $valueA = $a->getId();
                    $valueB = $b->getId();
                } else {
                    $valueA = $self->getFieldValue($a, $keyName, $found);
                    if ($found === false) {
                        $valueA = null;
                    }
                    $valueB = $self->getFieldValue($b, $keyName, $found);
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
