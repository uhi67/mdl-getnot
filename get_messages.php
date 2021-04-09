<?php

use uhi67\envhelper\EnvHelper;

require __DIR__ . '/vendor/autoload.php';

$config = [
	'db' => EnvHelper::getEnv('db', [
		'dsn' => 'mysql:host=localhost;dbname=credit',
		'username' => 'credit',
		'password' => null,
		'charset' => 'utf8',
		'prefix' => 'mdl_',
	]),
];

$db_config = $config['db'];
$db = new PDO($db_config['dsn'], $db_config['username'], $db_config['password']);
$prefix = $db_config['prefix'];

$userid = 19; // 13=Kr

$statement = $db->prepare("select count(id) from {$prefix}notifications where useridto=:userid and timeread is null");
$statement->execute([':userid'=>$userid]);
$notifications = $statement->fetchColumn(0);

// select max(lastlogin, currentlogin) from {$prefix}user where username=:uid
// select count(id) from {$prefix}messages where timecreated > :lastlogin

echo json_encode(['notifications'=>$notifications]);
