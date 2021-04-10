<?php
use uhi67\envhelper\EnvHelper;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @noinspection PhpUnhandledExceptionInspection */
$debug = EnvHelper::getEnv('debug', 0);
if($debug) ini_set('display_errors', 1);
$computed = null;
$now = null;

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
	if($ts<$now-$delta || $ts > $now+$delta) throw new Exception("Query is outdated");

	$computed = hash('sha512', "$uid,$ts,$secret");
	if($token != $computed) throw new Exception("Unauthorized");

	$db = new PDO($db_config['dsn'], $db_config['username'], $db_config['password']);
	$prefix = $db_config['prefix'];

	$s = $db->prepare(/** @lang */ "select id from {$prefix}user where username=:uid and deleted=0 and suspended=0");
	$userid = $s->execute([':uid' => $uid]) ? $s->fetchColumn() : false;

	if(!$userid) throw new Exception("User not found");

	// Unread notifications
	$sql = /** @lang */"SELECT count(id)
               FROM {$prefix}notifications
              WHERE id IN (SELECT notificationid FROM {$prefix}message_popup_notifications)
                AND useridto = ?
                AND timeread is NULL";

	$s = $db->prepare($sql);
	$notifications = $s->execute([$userid]) ? $s->fetchColumn() : false;

	// Unread messages
	$sql = /** @lang */"SELECT COUNT(DISTINCT(m.conversationid))
                  FROM {$prefix}messages m
            INNER JOIN {$prefix}message_conversations mc
                    ON m.conversationid = mc.id
            INNER JOIN {$prefix}message_conversation_members mcm
                    ON mc.id = mcm.conversationid
             LEFT JOIN {$prefix}message_user_actions mua
                    ON (mua.messageid = m.id AND mua.userid = ? AND mua.action = ?)
                 WHERE mcm.userid = ?
                   AND mc.enabled = ?
                   AND mcm.userid != m.useridfrom
                   AND mua.id is NULL";

	$s = $db->prepare($sql);
	$messages = $s->execute([$userid, 1, $userid, 1]) ? $s->fetchColumn() : false;

	// Contact requests
	$sql = /** @lang */"SELECT COUNT(mcr.id)
                  FROM {$prefix}message_contact_requests mcr
             LEFT JOIN {$prefix}message_users_blocked mub
                    ON mub.userid = mcr.requesteduserid AND mub.blockeduserid = mcr.userid
                 WHERE mcr.requesteduserid = :requesteduserid
                   AND mub.id IS NULL";
	$s = $db->prepare($sql);
	$requests = $s->execute(['requesteduserid' => $userid]) ? $s->fetchColumn() : false;

	echo json_encode([
		'status'=>'success',
		'notifications' => $notifications,
		'messages' => $messages,
		'requests' => $requests
	]);
}
catch(Throwable $e) {
	$response = ['status' => 'error', 'error'=>$e->getMessage()];
	if($debug) {
		$response['now'] = $now;
		if($computed) $response['token'] = $computed;
	}
	echo json_encode($response);
}
