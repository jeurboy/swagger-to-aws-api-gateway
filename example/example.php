<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


$JSON1 = json_decode(file_get_contents('test1.json'), TRUE);

$uri = 'http://AWS_URL';

$swagToAws = new \Jeurboy\SwaggerToAwsAPIGateway\SwaggerToAwsAPIGateway($JSON1);
$swagToAws->setAwsUri($uri);

print_r(($swagToAws->getOutput()));
print_r(json_encode($swagToAws->getOutput()));

print "\nEnd\n";