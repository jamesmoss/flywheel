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

    public function query()
    {
        return new Query($this);
    }

    public function findAll()
    {
        $files     = glob($this->path . '/*.json') or array();
        $documents = array();
        
        foreach ($files as $file) {
            $documents[] = new Document(json_decode(file_get_contents($file)));
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
        if(!isset($document->id)) {
            $document->id = $this->generateID();
        }

        $path    = $this->getPath($document->id);
        $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : null;
        $data    = json_encode((array)$document, $options);

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

    public function getFilename($id)
    {
        return $id . '_' . sha1($id) . '.json';
    }

    protected function generateID()
    {
        //openssl_random_pseudo_bytes
        $num = str_replace(' ', '', microtime());
        $id  = gmp_strval(gmp_init($num, 10), 62);

        return $id;
    }


}