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
        if (str_starts_with($key, 'files:')) {
            $root = \Contao\System::getContainer()->getParameter('kernel.project_dir');
            \Contao\Dbafs::addResource(substr($target, strlen(rtrim($root, '/')) + 1));
        }
    } 

    public function path(string $key):string
    {
        if (str_starts_with($key, 'files:')) {
            if (!preg_match('~^files:([a-f0-9-]{36})/([a-f0-9]{32}\\.[a-z0-9]+)$~D', $key, $parts)) {
                throw new \InvalidArgumentException('Invalid storage key.');
            }
            return self::resolveFolder($parts[1]).'/'.$parts[2];
        }
        $filename = basename($key);
        $directory = dirname($key);
        $directory = $directory === '.' ? '' : $directory;
        self::validateDirectory($directory);
        if (!preg_match('/^[a-f0-9]{32}\\.[a-z0-9]+$/D', $filename)) {
            throw new \InvalidArgumentException('Invalid storage key.');
        }
        return rtrim($this->storageDir, '/').($directory !== '' ? '/'.$directory : '').'/'.substr($filename, 0, 2).'/'.$filename;
    }

    public static function resolveFolder(string $uuid): string
    {
        $folder = \Contao\FilesModel::findByUuid($uuid);
        if (!$folder || $folder->type !== 'folder' || !str_starts_with($folder->path, 'files/')) {
            throw new \InvalidArgumentException('Bitte einen vorhandenen Ordner unter files auswählen.');
        }
        if ((new \Contao\Folder($folder->path))->isUnprotected()) {
            throw new \InvalidArgumentException('Bitte einen geschützten Ordner für Ticket-Anhänge auswählen.');
        }
        $root = \Contao\System::getContainer()->getParameter('kernel.project_dir');
        $path = realpath($root.'/'.$folder->path);
        $files = realpath($root.'/files');
        if (!$path || !$files || !str_starts_with($path, $files.DIRECTORY_SEPARATOR) || !is_dir($path)) {
            throw new \InvalidArgumentException('Der ausgewählte Ordner ist nicht verfügbar.');
        }
        return $path;
    }

    public static function validateDirectory(string $directory): void
    {
        if (strlen($directory) > 160 || ($directory !== '' && !preg_match('~^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$~D', $directory))) {
            throw new \InvalidArgumentException('Bitte ein relatives Unterverzeichnis mit Buchstaben, Zahlen, Bindestrichen oder Unterstrichen angeben.');
        }
    }

    public function delete(string $key):void
    {
        $p=$this->path($key);
        if(is_file($p))
            unlink($p);
    } 
}
