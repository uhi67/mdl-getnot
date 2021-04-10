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
SECRET          | seed for computing token  |
DELTA           | timestamp timeout in sec  | 300
DB_USERNAME     |                           | "moodle"
DB_DSN          |                           | "mysql:host=localhost;dbname=moodle"
DB_PASSWORD     |                           |
DB_PREFIX       |                           | "mdl_"
DEBUG           | set to 1 for development  | 0  

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
- requests: integer, the number of the received contact requests 

Example
-------

`http://mdl-getnot.test/get_messages.php?uid=uhi@pte.hu&ts=1617994451&token=2264836edb030066264e1f06683c9c1c752ba609275a47cc2b94f7b890a55392f6a488d058c4f86dce189297ba51bd59bdc0789b681b6741babd1f692112e26e`

results

`{"status":"succes","notifications":"0","messages":"1","requests":"0"}`
