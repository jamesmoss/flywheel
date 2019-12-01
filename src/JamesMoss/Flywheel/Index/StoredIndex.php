<?php

namespace JamesMoss\Flywheel\Index;

use JamesMoss\Flywheel\Index\IndexInterface;
use JamesMoss\Flywheel\Formatter\FormatInterface;
use JamesMoss\Flywheel\Predicate;
use JamesMoss\Flywheel\QueryExecuter;
use JamesMoss\Flywheel\Repository;
use stdClass;

abstract class StoredIndex implements IndexInterface
{
    /** @var mixed $data                content of the index */
    protected $data = null;

    /** @var string $field              name of the indexed field */
    protected $field;

    /** @var FormatInterface $formatter used to store data */
    protected $formatter;

    /** @var Repository $repository     repository of the index */
    protected $repository;

    /** @var string $path               where the data is stored */
    protected $path;

    /**
     * Constructor
     *
     * @param string $field the field to index.
     * @param string $path the directory where to store the index file.
     * @param FormatInterface $formatter the formatter used to store the data.
     * @param Repository $repository the repository of this index.
     */
    public function __construct($field, $path, $formatter, $repository)
    {
        $this->field = $field;
        $this->formatter = $formatter;
        $this->repository = $repository;
        $this->path = $path . DIRECTORY_SEPARATOR . "$field." . $formatter->getFileExtension();
    }

    /**
     * @inheritdoc
     */
    abstract public function isOperatorCompatible($operator);

    /**
     * @inheritdoc
     */
    public function get($value, $operator)
    {
        $this->needsData();
        return $this->getEntries($value, $operator);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $new, $old)
    {
        if ($new === $old) {
            return;
        }
        $this->needsData();
        $this->updateEntry($id, $new, $old);
        $this->flush();
    }

    /**
     * Lazyloading data initializer.
     *
     * @return void
     */
    protected function needsData()
    {
        if (isset($this->data)) {
            return;
        }
        $this->data = new stdClass();
        if (file_exists($this->path)) {
            $fp       = fopen($this->path, 'r');
            $contents = fread($fp, filesize($this->path));
            fclose($fp);
            $this->data = $this->formatter->decode($contents);
        } else {
            $field = $this->field;
            $predicate = new Predicate();
            $qe = new QueryExecuter($this->repository, $predicate->where($field, '=='), array(), array());
            foreach ($this->repository->findAll() as $doc) {
                $docVal = $qe->getFieldValue($doc, $field, $found);
        
                if (!$found) {
                    continue;
                }
                $this->addEntry($doc->getId(), $docVal);
            }
            $this->flush();
        }
    }

    /**
     * Write back the data on the filesystem.
     *
     * @return bool succeded.
     */
    protected function flush()
    {
        $contents = $this->formatter->encode(get_object_vars($this->data));
        $fp = fopen($this->path, 'w');
        if (!flock($fp, LOCK_EX)) {
            return false;
        }
        $result = fwrite($fp, $contents);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result !== false;
    }

    /**
     * Get entries from the index
     *
     * @param string $value
     * @param string $operator
     *
     * @return array<int,string> array of ids
     */
    abstract protected function getEntries($value, $operator);

    /**
     * Removes an entry from the index
     *
     * @param string $id
     * @param string $value
     */
    abstract protected function updateEntry($id, $new, $old);
}
