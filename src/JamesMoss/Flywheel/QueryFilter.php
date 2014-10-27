<?php
namespace JamesMoss\Flywheel;

/**
 * Provides callback to filter documents against the query
 */
class QueryFilter
{
    protected $queryWhere;

    public function __construct(QueryWhere $queryWhere)
    {
        $this->queryWhere = $queryWhere;
    }

    /**
     * Filter document against the query
     *
     * @param Document $doc
     * @return bool
     */
    public function filter(Document $doc)
    {
        $resultOverall = true;

        foreach ($this->queryWhere->getWhere() as $where) {
            list($type, $field, $operator, $predicate) = $where;

            $resultCondition = $this->filterCondition($doc, $field, $operator, $predicate);

            if ($type === QueryWhere::TYPE_AND) {
                $resultOverall = $resultOverall && $resultCondition;
            } elseif ($type === QueryWhere::TYPE_OR) {
                $resultOverall = $resultOverall || $resultCondition;
            } else {
                throw new \LogicException('Invalid type');
            }
        }

        return $resultOverall;
    }

    protected function filterCondition(Document $doc, $field, $operator, $predicate)
    {
        if (false === strpos($field, '.')) {
            $value = $doc->{$field};
        } else {
            //multi-dimensional process
            $field = explode('.', $field);
            $value = $doc;
            foreach ($field as $fieldPart) {
                if ((string)(int)$fieldPart === $fieldPart) {
                    if (!isset($value[$fieldPart])) {
                        return false;
                    }
                    $value = $value[$fieldPart];
                } else {
                    if (!isset($value->{$fieldPart})) {
                        return false;
                    }
                    $value = $value->{$fieldPart};
                }
            }
        }

        switch (true) {
            case ($operator === '==' && $value != $predicate):
                return false;
            case ($operator === '===' && $value !== $predicate):
                return false;
            case ($operator === '>' && $value <= $predicate):
                return false;
            case ($operator === '>=' && $value < $predicate):
                return false;
            case ($operator === '<' && $value >= $predicate):
                return false;
            case ($operator === '<=' && $value > $predicate):
                return false;
        }

        return true;
    }
}
