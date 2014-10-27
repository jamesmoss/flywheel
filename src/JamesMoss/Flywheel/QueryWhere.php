<?php
namespace JamesMoss\Flywheel;

/**
 * Sub-query
 */
class QueryWhere
{
    const TYPE_AND = 'and';
    const TYPE_OR = 'or';

    protected $where = array();

    /**
     * Sets the predicates for this query,
     *
     * @param string|callable $field The name of the field to match OR callback for sub-query
     * @param string $operator An operator from the allowed list.
     * @param string $value The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function where($field, $operator = null, $value = null)
    {
        $this->addWhere(self::TYPE_AND, $field, $operator, $value);
        return $this;
    }

    /**
     * Add the predicates for this query,
     *
     * @param string|callable $field The name of the field to match OR callback for sub-query
     * @param string $operator An operator from the allowed list.
     * @param string $value The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function whereAnd($field, $operator, $value)
    {
        $this->addWhere(self::TYPE_AND, $field, $operator, $value);
        return $this;
    }

    /**
     * Add the predicates for this query,
     *
     * @param string|callable $field The name of the field to match OR callback for sub-query
     * @param string $operator An operator from the allowed list.
     * @param string $value The value to compare against.
     *
     * @return Query The same instance of this class.
     */
    public function whereOr($field, $operator, $value)
    {
        $this->addWhere(self::TYPE_OR, $field, $operator, $value);
        return $this;
    }

    protected function addWhere($type, $field, $operator, $value)
    {
        if ($type !== self::TYPE_AND && $type !== self::TYPE_OR) {
            throw new \LogicException('Invalid where type');
        }

        // todo, validate these args
        $this->where[] = array($type, $field, $operator, $value);

        return $this;
    }

    /**
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }
}
