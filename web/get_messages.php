<?php /** @noinspection PhpUnhandledExceptionInspection */

use uhi67\envhelper\EnvHelper;

require dirname(__DIR__) . '/vendor/autoload.php';

#ini_set('display_errors', 1);
try {
	$db_config = EnvHelper::getEnv('db', [
			'dsn' => 'mysql:host=localhost;dbname=credit',
			'username' => 'credit',
			'password' => null,
			'charset' => 'utf8',
			'prefix' => 'mdl_',
	]);
	$secret = EnvHelper::getEnv('secret');
	$delta = EnvHelper::getEnv('delta', 300);

	$uid = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : '';
	$ts = isset($_REQUEST['ts']) ? $_REQUEST['ts'] : '';
	$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';

	$now = time();
	if($ts<$now-$delta || $ts > $now+$delta) throw new Exception("Query is outdated ($now)");

	$computed = hash('sha512', "$uid,$ts,$secret");
	if($token != $computed) throw new Exception("Unauthorized ($computed)");

	$db = new PDO($db_config['dsn'], $db_config['username'], $db_config['password']);
	$prefix = $db_config['prefix'];

	$s = $db->prepare(/** @lang */ "select id from {$prefix}user where username=:uid and deleted=0 and suspended=0");
	$userid = $s->execute([':uid' => $uid]) ? $s->fetchColumn() : false;

	if(!$userid) throw new Exception("User not found");

	$s = $db->prepare(/** @lang */ "select count(id) from {$prefix}notifications where useridto=:userid and timeread is null");
	$notifications = $s->execute([':userid' => $userid]) ? $s->fetchColumn() : false;

	if($notifications===false) throw new Exception("Query unsuccesful");

// select max(lastlogin, currentlogin) from {$prefix}user where username=:uid
// select count(id) from {$prefix}messages where timecreated > :lastlogin

	echo json_encode(['status'=>'succes', 'notifications' => $notifications]);
}
catch(Throwable $e) {
	echo json_encode(['status' => 'error', 'error'=>$e->getMessage()]);
}