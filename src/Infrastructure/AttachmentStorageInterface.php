<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Infrastructure;
interface AttachmentStorageInterface { public function store(string $source,string $key):void; public function path(string $key):string; public function delete(string $key):void; }
