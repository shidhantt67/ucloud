<?php
/**
 * Copyright 2012-2014 Rackspace US, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require dirname(__DIR__) . '/../vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\ObjectStore\Resource\DataObject;

// 1. Instantiate a Rackspace client. You can replace {authUrl} with
// Rackspace::US_IDENTITY_ENDPOINT or similar
$client = new Rackspace('{authUrl}', array(
    'username' => '{username}',
    'apiKey'   => '{apiKey}',
));

// 2. Obtain an Object Store service object from the client.
$objectStoreService = $client->objectStoreService(null, '{region}');

// 3. Get container.
$container = $objectStoreService->getContainer('{containerName}');

// 4. Open local file
$fileData = fopen('{localFilePath}', 'r');

// 5. Specify any metadata you want your objects to have
$metadata = array('{key}' => '{value}');
$metadataHeaders = DataObject::stockHeaders($metadata);

// 6. Merge the metadata with any additional HTTP headers you want to set
$allHttpHeaders = array('Content-Type' => '{contentType}') + $metadataHeaders;

// 7. Upload it! Note that while we call fopen to open the file resource, we do
// not call fclose at the end. The file resource is automatically closed inside
// the uploadObject call.
$container->uploadObject('{remoteObjectName}', $fileData, $allHttpHeaders);
