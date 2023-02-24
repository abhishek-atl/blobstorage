<?php

declare(strict_types=1);

namespace AzurePHP\Service;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ContainerACL;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Psr\Http\Message\UploadedFileInterface;

class AzureBlobService
{
    public const ACL_NONE = '';
    public const ACL_BLOB = 'blob';
    public const ACL_CONTAINER = 'container';

    private BlobRestProxy $blobClient;

    /**
     * @param BlobRestProxy $blobClient
     */
    public function __construct(BlobRestProxy $blobClient)
    {
        $this->blobClient = $blobClient;
    }

    public function addBlobContainer(string $containerName): void
    {
        $this->blobClient->createContainer(strtolower($containerName));
    }

    public function setBlobContainerAcl(string $containerName, string $acl = self::ACL_BLOB): bool
    {
        if (!in_array($acl, [self::ACL_NONE, self::ACL_BLOB, self::ACL_CONTAINER])) {
            return false;
        }
        $blobAcl = new ContainerACL();
        $blobAcl->setPublicAccess($acl);
        $this->blobClient->setContainerAcl(
            strtolower($containerName),
            $blobAcl
        );
        return true;
    }

    public function uploadBlob(string $containerName, array $uploadedFile, string $prefix = ''): string
    {
        $contents = file_get_contents($uploadedFile['tmp_name']);
        $blobName = $uploadedFile['name'];
        if ('' !== $prefix) {
            $blobName = sprintf(
                '%s/%s',
                rtrim($prefix, '/'),
                $blobName
            );
        }
        $this->blobClient->createBlockBlob(strtolower($containerName), $blobName, $contents);
        $blobOptions = new SetBlobPropertiesOptions();
        $blobOptions->setContentType($uploadedFile['type']);
        $this->blobClient->setBlobProperties(
            strtolower($containerName),
            $blobName,
            $blobOptions
        );
        return $blobName;
    }

    public function uploadLocalBlob(string $containerName, $uploadedFile, string $prefix = ''): string
    {
        $contents = file_get_contents($uploadedFile);
        $blobName = $uploadedFile;
        if ('' !== $prefix) {
            $blobName = sprintf('%s/%s', rtrim($prefix, '/'), $blobName);
        }
        $this->blobClient->createBlockBlob(strtolower($containerName), $blobName, $contents);
        $blobOptions = new SetBlobPropertiesOptions();
        //$blobOptions->setContentType($uploadedFile['type']);
        $this->blobClient->setBlobProperties(
            strtolower($containerName),
            $blobName,
            $blobOptions
        );
        return $blobName;
    }

    function listBlobsSample($containerName, $blobClient)
    {
        try {
            // List blobs.
            $listBlobsOptions = new ListBlobsOptions();
            //$listBlobsOptions->setPrefix("myblob");

            // Setting max result to 1 is just to demonstrate the continuation token.
            // It is not the recommended value in a product environment.
            $listBlobsOptions->setMaxResults(1);

            do {
                $blob_list = $blobClient->listBlobs($containerName, $listBlobsOptions);
                foreach ($blob_list->getBlobs() as $blob) {
                    echo $blob->getName() . ": " . $blob->getUrl() . PHP_EOL;
                }

                $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
            } while ($blob_list->getContinuationToken());
        } catch (ServiceException $e) {
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . PHP_EOL;
        }
    }

    public function deleteContainer($containerName, $blobClient)
    {
        $blobClient->deleteContainer(strtolower($containerName));
    }
}
