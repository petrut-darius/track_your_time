<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

class FileUploaderService
{
    public function __construct(private string $targetDirectory, private SluggerInterface $slugger, private LoggerInterface $logger)
    {
    }

    public function upload(UploadedFile $file): string
    {
        if(!in_array($file->getMimeType(), ["image/jpeg", "image/png", "image/webp"], true)) {
            $this->logger->error("The photo hasn't been moved cause it's type is not good.", [
                "mimeType" => $file->getMimeType(),
                "file" => $file->getClientOriginalName(),
            ]);

            throw new FileException("Unsupported file type.");
        }

        //file uploaded size check

        $originalFilename = pathinfo($file->getClientOriginalname(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . "=" . uniqid() . "." . $file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        }catch(FileException $e) {
            $this->logger->error("The photo($fileName) hasn't been moved", [
                "error" => $e->getMessage(),
                "file" => $file->getClientOriginalName(),
            ]);

            throw $e;
        }

        return $fileName;
    }

    private function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}

/*

Set a max file size limit — you have none right now, and an unauthenticated or authenticated endpoint with no size cap is a free disk-fill DoS vector.
Store uploaded files with no execute permission and ideally outside the document root, or if they must be public, make sure your webserver config refuses to execute anything in /public/uploads/ regardless of extension (e.g., an Nginx location block that disables PHP execution for that path). This is the actual fix — MIME validation alone is defense in depth, not the primary control. If your server will execute a .php file dropped into /public/uploads/car/, no amount of MIME checking saves you from a determined

*/