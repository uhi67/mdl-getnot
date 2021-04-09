Moodle user notifications API
=============================

Installation
------------

1. `git clone ...` project from the git repository
2. `composer install`
3. make `/web` accessible to webserver
5. set up environment variables in apache config or .env file.

Environment variables
---------------------

Set environment variables in apache config or docker-compose.yml

Variable        | Description               | Default value or example
----------------|---------------------------|--------------------------
SECRET          | seed for computing token 
DELTA           | timestamp timeout in sec
DB_USERNAME     |                           | "moodle"
DB_DSN          |                           | "mysql:host=localhost;dbname=moodle"
DB_PASSWORD     |                           |
DB_PREFIX       |                           | "mdl_"

Usage
-----

Call `http://mdl-getnot.test/get_messages.php` with the following GET parameters:

- uid: username (eduPersonPrincipalName) of the user to query 
- ts: unix timestamp of query creation (valid for delta seconds) (UTC)
- token: hash('sha512', "$uid,$ts,$secret") wheresecret is the configured shared secret

Returns JSON with fields:
- status: 'success' or 'error'
- error: exists only if status is 'error', the error message
- notifications: integer, the number of the unread notifications 
- messages: integer, the number of the unread messages 
