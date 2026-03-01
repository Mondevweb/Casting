<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Find an OrderLine and a MediaObject to test with
$orderLine = $em->getRepository(\App\Entity\OrderLine::class)->findOneBy([]);
$mediaObjects = $em->getRepository(\App\Entity\MediaObject::class)->findAll();
$mediaObject = count($mediaObjects) > 0 ? $mediaObjects[0] : null;

if (!$orderLine || !$mediaObject) {
    die("Need at least one OrderLine and one MediaObject in DB\n");
}

echo "Testing PATCH on OrderLine " . $orderLine->getId() . "\n";
echo "Adding MediaObject " . $mediaObject->getId() . "\n";

$payload = json_encode([
    'mediaObjects' => [
        '/api/media_objects/' . $mediaObject->getId()
    ]
]);

$request = Request::create(
    '/api/order_lines/' . $orderLine->getId(),
    'PATCH',
    [],
    [],
    [],
    [
        'CONTENT_TYPE' => 'application/merge-patch+json',
        'HTTP_ACCEPT' => 'application/ld+json'
    ],
    $payload
);

$response = $kernel->handle($request);

echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response content: \n" . $response->getContent() . "\n";
