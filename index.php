<?php

declare(strict_types=1);

use AzurePHP\Service\AzureBlobService;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

require_once 'vendor/autoload.php';
require_once 'Services/AzureBlobService.php';

// create a config file and define connection string
$config = include_once 'config.php';


$connectionString = $config['connection_string'];
if ('' === $connectionString) {
    throw new InvalidArgumentException(
        'Please set the environment variable STORAGE_CONN_STRING with the Azure Blob Connection String'
    );
}
$blobClient = BlobRestProxy::createBlobService($connectionString);
$blobService = new AzureBlobService($blobClient);

$containerName = 'azurephpdemo';
//$containerName = '2023-04-test';
try {
    $blobService->addBlobContainer($containerName);
    $blobService->setBlobContainerAcl($containerName, AzureBlobService::ACL_BLOB);
} catch (ServiceException $serviceException) {
    $serviceException->getMessage();
}

/*********************************************************/
// upload a file through form
/*********************************************************/
// try {
//     $fileName = $blobService->uploadBlob($containerName, $_FILES['blob']);
// } catch (ServiceException $serviceException) {
//     $serviceException->getMessage();
// }

try {
    $fileName = $blobService->uploadLocalBlob($containerName, 'samples/test.txt');
} catch (ServiceException $serviceException) {
    $serviceException->getMessage();
}


$blobs = $blobService->listBlobsSample($containerName, $blobClient);

//$blobService->deleteContainer($containerName, $blobClient);



$fileLink = sprintf(
    '%s/%s/%s',
    'https://azurephpstorage.blob.core.windows.net',
    strtolower($containerName),
    $fileName
);
echo sprintf(
    'Find the uploaded file at <a href="%s" target="_blank">%s</a>.',
    $fileLink,
    $fileLink
);
echo '<br><a href="/">Reset</a>';
