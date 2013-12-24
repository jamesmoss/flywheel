<?php

namespace JamesMoss\Flywheel;

class Query
{
    protected $repo;
    protected $limit   = false;
    protected $orderBy = false;
    protected $where   = false;

    protected $operators = array(
        '>', '>=', '<', '<=', '==', '===',
    );

    public function __construct(Repository $repository)
    {
        $this->repo = $repository;
    }

    public function limit($count, $offset)
    {
        $this->limit = array((int)$count, (int)$offset);

        return $this;
    }

    public function orderBy($fields)
    {
        $this->orderBy = (array)$fields;

        return $this;
    }

    public function where($field, $operator, $value)
    {
        // todo, validate these args
        $this->where = array($field, $operator, $value);

        return $this;
    }

    public function execute()
    {
        $documents = $this->repo->findAll();

        if($this->where) {
            list($field, $operator, $predicate) = $this->where;
            $documents = array_filter($documents, function($doc) use ($field, $operator, $predicate) {
                $value = $doc->{$field};

                switch(true) {
                    case ($operator === '==' && $value == $predicate): return true;
                    case ($operator === '===' && $value === $predicate): return true;
                    case ($operator === '>'  && $value >  $predicate): return true;
                    case ($operator === '>=' && $value >= $predicate): return true;
                    case ($operator === '<'  && $value <  $predicate): return true;
                    case ($operator === '>=' && $value >= $predicate): return true;
                }
               
                return false;
            }); 
        }

        if($this->orderBy) {
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

        if($this->limit) {
            list($count, $offset) = $this->limit;
            $documents = array_slice($documents, $offset, $count);
        }

        return $documents; 
    }

    protected function sort($array, $args) {
        $c = count($args);

        usort($array, function($a, $b) use($args, $c) {
            $i   = 0;     
            $cmp = 0;
            while($cmp == 0 && $i < $c) {
                $valueA = $a->{$args[$i][0]};
                $valueB = $b->{$args[$i][0]};

                if(is_string($valueA)) {
                    $cmp = strcmp($valueA, $valueB);
                } else if(is_bool($valueA)) {
                    $cmp = $valueA - $valueB;
                } else {
                    $cmp = ($valueA == $valueB) ? 0 : ($valueA > $valueB) ? -1 : 1; 
                }
                
                if($args[$i][1] === SORT_DESC) {
                    $cmp *= -1;
                }
                $i++; 
            }

            return $cmp;
        });

        return $array;
    }
}