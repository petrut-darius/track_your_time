<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FileUploaderService
{
    public function __construct(#[Autowire("%kernel.project_dir%/public/uploads/car")] private string $targetDirectory, private SluggerInterface $slugger, private LoggerInterface $logger)
    {
    }

    public function upload(UploadedFile $file): string
    {
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