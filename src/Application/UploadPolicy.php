<?php
declare(strict_types=1); namespace Vendor\ContaoIssueServiceBundle\Application;
final class UploadPolicy { public function assertAllowed(string $extension,int $size,int $count,array $allowed,int $maxSize,int $maxCount):void{if(!in_array(strtolower($extension),array_map('strtolower',$allowed),true))throw new \InvalidArgumentException('File type not allowed.');if($size<1||$size>$maxSize)throw new \InvalidArgumentException('File too large.');if($count>=$maxCount)throw new \InvalidArgumentException('Maximum attachment count reached.');} }
