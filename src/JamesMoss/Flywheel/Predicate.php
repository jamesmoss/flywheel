<?php

namespace JamesMoss\Flywheel;

/**
 * Query
 *
 * Builds an executes a query whichs searches and sorts documents from a
 * repository.
 */
class Predicate
{
    const LOGICAL_AND = 'and';
    const LOGICAL_OR = 'or';

    protected $predicates = array();
    protected $operators = array(
        '>', '>=', '<', '<=', '==', '===', '!=', '!==', 'IN', 'CONTAINS',
    );

    public function getAll()
    {
        return $this->predicates;
    }

    public function where($field, $operator = null, $value = null)
    {
        return $this->andWhere($field, $operator, $value);
    }

    public function andWhere($field, $operator = null, $value = null)
    {
        $this->addPredicate(self::LOGICAL_AND, $field, $operator, $value);

        return $this;
    }

    public function orWhere($field, $operator = null, $value = null)
    {
        $this->addPredicate(self::LOGICAL_OR, $field, $operator, $value);

        return $this;
    }

    protected function addPredicate($type, $field, $operator = null, $value = null)
    {
        if (!$this->predicates) {
            $type = false;
        }

        if ($field instanceof \Closure) {
            $sub = new self();
            call_user_func($field, $sub);

            $this->predicates[] = array($type, $sub->getAll());

            return $this;
        }

        $field = trim($field);

        if ($field == '') {
            throw new \InvalidArgumentException('Field name cannot be empty.');
        }

        if (!in_array($operator, $this->operators)) {
            throw new \InvalidArgumentException('Unknown operator `'.$operator.'`.');
        }

        $this->predicates[] = array($type, $field, $operator, $value);

        return $this;
    }
}
