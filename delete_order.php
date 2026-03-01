<?php
require __DIR__.'/vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$order = $em->getRepository(App\Entity\Order::class)->findOneBy(['reference' => 'ORD-2026-U23AIJH7']);
if ($order) {
    $em->remove($order);
    $em->flush();
    echo "Order deleted";
} else {
    echo "Order not found";
}
