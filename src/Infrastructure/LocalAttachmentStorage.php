<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Infrastructure;

final class LocalAttachmentStorage implements AttachmentStorageInterface 
{ 
    public function __construct(private readonly string $storageDir){} 
    
    public function store(string $source,string $key):void
    {
        $target=$this->path($key);
        if(!is_dir(dirname($target))&&!mkdir(dirname($target),0770,true)&&!is_dir(dirname($target)))
            throw new \RuntimeException('Storage directory could not be created.');
        if(!rename($source,$target))
            throw new \RuntimeException('File could not be stored.');
        chmod($target,0660);
    } 

    public function path(string $key):string
    {
        if(!preg_match('/^[a-f0-9]{32}\\.[a-z0-9]+$/D',$key))
            throw new \InvalidArgumentException('Invalid storage key.');
        return rtrim($this->storageDir,'/').'/'.substr($key,0,2).'/'.$key;
    } 

    public function delete(string $key):void
    {
        $p=$this->path($key);
        if(is_file($p))
            unlink($p);
    } 
}
