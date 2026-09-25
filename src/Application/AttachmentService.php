<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Application;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Diversworld\ContaoIssueServiceBundle\Infrastructure\AttachmentStorageInterface;
use Diversworld\ContaoIssueServiceBundle\Application\SettingsService;

final class AttachmentService 
{ 
    public function __construct(private readonly Connection $db,private readonly SettingsService $settings,private readonly AttachmentStorageInterface $storage, private readonly AttachmentConstraints $constraints, private readonly \Symfony\Component\Validator\Validator\ValidatorInterface $validator)
    {} 
    
    public function upload(int $issueId,UploadedFile $file,string $uploaderType,?int $uploaderId,?int $commentId=null,string $directory=''):int
    {
        $profileId = (int) $this->db->fetchOne('SELECT profile_id FROM tl_issue WHERE id=:id', ['id' => $issueId]);
        $settings = $this->settings->forProfile($profileId);
        $violations = $this->validator->validate($file, $this->constraints->forProfile($profileId)->file());
        if (count($violations) > 0) {
            throw new \InvalidArgumentException((string) $violations[0]->getMessage());
        }
        $ext = strtolower($file->getClientOriginalExtension());

        $count=(int)$this->db->fetchOne('SELECT COUNT(*) FROM tl_issue_attachment WHERE issue_id=:i',['i'=>$issueId]);
        
        if($count>=$settings->int('max_files_per_issue',5))
            throw new \InvalidArgumentException('Maximum attachment count reached.');
        
        if (str_starts_with($directory, 'files:')) {
            \Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage::resolveFolder(substr($directory, 6));
        } else {
            \Diversworld\ContaoIssueServiceBundle\Infrastructure\LocalAttachmentStorage::validateDirectory($directory);
        }
        $key=str_replace('-','',Uuid::v7()->toRfc4122()).'.'.$ext;
        if ($directory !== '') {
            $key = $directory.'/'.$key;
        }
        $tmp=$file->getRealPath();
        if(false===$tmp)
            throw new \RuntimeException('Temporary file unavailable.');
        // The storage adapter moves the temporary file; read metadata first.
        $mimeType = (string) $file->getMimeType();
        $fileSize = $file->getSize();
        $sha=hash_file('sha256',$tmp);
        $this->storage->store($tmp,$key);
        $this->db->insert('tl_issue_attachment',[
            'tstamp'=>time(),
            'issue_id'=>$issueId,
            'comment_id'=>$commentId,
            'storage_key'=>$key,
            'original_name'=>basename($file->getClientOriginalName()),
            'mime_type'=>$mimeType,
            'file_size'=>$fileSize,
            'sha256'=>$sha,
            'uploaded_by_type'=>$uploaderType,
            'uploaded_by_id'=>$uploaderId,
            'scan_status'=>'not_scanned',
            'created_at'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id):?array
    {
        $r=$this->db->fetchAssociative('SELECT * FROM tl_issue_attachment WHERE id=:id',['id'=>$id]);
        return false===$r?null:$r;
    }

    public function path(string $key):string
    {
        return $this->storage->path($key);
    }
}
