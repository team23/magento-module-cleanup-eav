<?php
declare(strict_types=1);

namespace Team23\CleanupEav\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use Team23\CleanupEav\Model\ResourceModel\Media as MediaResource;

/**
 * Class Media
 *
 * Handle operations for product images.
 */
class Media
{
    /**
     * @var int
     */
    private int $fileCount = 0;

    /**
     * @var int
     */
    private int $fileSize = 0;

    /**
     * @var string[]
     */
    private array $filesToDelete = [];

    /**
     * @var string[]
     */
    private array $filesToRemoveFromDb = [];

    /**
     * @var string|null
     */
    private ?string $imageDirectory = null;

    /**
     * Media constructor
     *
     * @param Filesystem $filesystem
     * @param File $fileDriver
     * @param LoggerInterface $logger
     * @param MediaResource $mediaResource
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly File $fileDriver,
        private readonly LoggerInterface $logger,
        private readonly MediaResource $mediaResource
    ) {
    }

    /**
     * Reset state
     *
     * @return void
     */
    public function reset(): void
    {
        $this->fileCount = 0;
        $this->fileSize = 0;
        $this->filesToDelete = [];
        $this->filesToRemoveFromDb = [];
    }

    /**
     * Find out files to remove and execute this task on disk and database
     *
     * Compare images on disk and database to find out which files to delete and compare database with images on disk
     * to find out which entries in DB to remove.
     *
     * @param bool $isDryRun
     * @return void
     */
    public function execute(bool $isDryRun = true): void
    {
        if (!$isDryRun) {
            $this->mediaResource->deleteOrphanedGalleryEntitiesWithoutValues();
        }
        $imagesInDatabase = array_unique($this->mediaResource->getImages());
        $imagesOnDisk = array_unique($this->getImagesOnDisk());
        $this->filesToDelete = array_udiff($imagesOnDisk, $imagesInDatabase, 'strcasecmp');
        $this->filesToRemoveFromDb = array_udiff($imagesInDatabase, $imagesOnDisk, 'strcasecmp');
        if ($this->filesToDelete !== []) {
            try {
                $this->unlinkFiles($isDryRun);
            } catch (FileSystemException $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
            }
        }
        if ($this->filesToRemoveFromDb !== [] && !$isDryRun) {
            $this->mediaResource->deleteImages($this->filesToRemoveFromDb);
        }
    }

    /**
     * Retrieve files to remove from database table
     *
     * @return string[]
     */
    public function getFilesToRemoveFromDb(): array
    {
        return $this->filesToRemoveFromDb;
    }

    /**
     * Retrieve files to unlink (delete)
     *
     * @return string[]
     */
    public function getFilesToDelete(): array
    {
        return $this->filesToDelete;
    }

    /**
     * Retrieve unlink (delete) file count
     *
     * @return int
     */
    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    /**
     * Retrieve unlink file size in MB
     *
     * Return the saved disk space in megabyte.
     *
     * @return string
     */
    public function getFileSize(): string
    {
        return number_format(($this->fileSize / 1024 / 1024), 2);
    }

    /**
     * Unlink files and track count and file size
     *
     * Remove the file from disk or pretend to do it, if it is a dry run.
     *
     * @param bool $isDryRun
     * @throws FileSystemException
     */
    private function unlinkFiles(bool $isDryRun): void
    {
        foreach ($this->filesToDelete as $file) {
            $realFile = $this->getImageDirectory() . $file;
            if (!$this->fileDriver->isExists($realFile)) {
                continue;
            }
            $fileStat = $this->fileDriver->stat($realFile);
            $fileSize = $fileStat['size'] ?? 0;
            $this->fileCount++;
            $this->fileSize += $fileSize;
            if (!$isDryRun) {
                $this->fileDriver->deleteFile($realFile);
            }
        }
    }

    /**
     * Retrieve all image files on disk
     *
     * Return a list product images which should be checked if they can be unlink (removed) from disk.
     *
     * @return string[]
     */
    private function getImagesOnDisk(): array
    {
        $imageDirectory = $this->getImageDirectory();
        $directoryIterator = new \RecursiveDirectoryIterator($imageDirectory);
        $result = [];
        foreach (new \RecursiveIteratorIterator($directoryIterator) as $fileObject) {
            try {
                if (!($file = $fileObject->getRealPath()) || !$this->validateFile($file)) {
                    continue;
                }
            } catch (FileSystemException $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
                continue;
            }
            $result[] = str_replace($imageDirectory, "", $file);
        }
        return $result;
    }

    /**
     * Validate if file can be used
     *
     * Check if image file can be processed or should not be handled.
     *
     * @param string $file
     * @return bool
     * @throws FileSystemException
     */
    private function validateFile(string $file): bool
    {
        if ($this->isInCachePath($file)) {
            return false;
        }

        if ($this->fileDriver->isDirectory($file) || !$this->fileDriver->isFile($file)) {
            return false;
        }

        $filePath = str_replace($this->getImageDirectory(), "", $file);
        if (empty($filePath)) {
            return false;
        }
        return true;
    }

    /**
     * Retrieve path to product images
     *
     * Return the absolute path to catalog product images (e.g. `/app/pub/media/catalog/images/`)
     * on the host system.
     *
     * @return string
     */
    private function getImageDirectory(): string
    {
        if ($this->imageDirectory === null) {
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->imageDirectory = rtrim(realpath($directory->getAbsolutePath()), "/")
                . DIRECTORY_SEPARATOR
                . 'catalog'
                . DIRECTORY_SEPARATOR
                . 'product';
        }
        return $this->imageDirectory;
    }

    /**
     * Check if file is in cache directory
     *
     * We don't need to handle cache directories, because there is already a command to cleanup this one.
     *
     * @param string|null $file
     * @return bool
     */
    private function isInCachePath(?string $file): bool
    {
        return str_contains($file, "/cache");
    }
}
