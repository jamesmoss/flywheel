<?php

namespace JamesMoss\Flywheel;

class Repository
{
    protected $name;

    public function __construct(Config $config, $name)
    {
        $this->validateName($name);
        $this->name = $name;
        $this->path = $config->getPath() . '/' . $name;

        // ensure directory exists and we can write there
        if(!file_exists($this->path)) {
            mkdir($this->path);
            chmod($this->path, 0777);
        } 
    }

    public function getName()
    {
        return $this->name;
    }

    public function findAll()
    {
        $files     = glob($this->path . '/*.json') or array();
        $documents = array();
        
        foreach ($files as $file) {
            $documents[] = new Document(file_get_contents($file));
        }

        return $documents;
    }

    protected function validateName($name)
    {
        if(!preg_match('/^[0-9A-Za-z\_\-]{1,63}$/', $name)) {
            throw new \Exception(sprintf('`%s` is not a valid repository name.', $name));
        }

        return true;
    }

    public function store(Document $document)
    {
        $path = $this->getPath($document->id);
        $data = json_encode((array)$document);

        return file_put_contents($path, $data);
    }

    public function delete($id)
    {
        $path = $this->getPath($id);
        unlink($path);
    }

    public function getPath($id)
    {
        return $this->path . '/' . $this->getFilename($id);
    }

    protected function getFilename($id)
    {
        return $id . '_' . sha1($id) . '.json';
    }


}