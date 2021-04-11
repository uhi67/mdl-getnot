Moodle user notifications API
=============================

With this API you can query received notifications and new messages of any user (by userName) on back channel.
The API needs direct access to the Moodle database.
Tested with Moodle v3.9

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

- uid: username (e.g. eduPersonPrincipalName) of the user to query 
- ts: unix timestamp of query creation (valid for delta seconds) (UTC)
- token: hash('sha512', "$uid,$ts,$secret") where secret is the configured shared secret

Returns JSON object with fields:
- status: 'success' or 'error'
- error: exists only if status is 'error', the error message
- notifications: integer, the number of the unread notifications 
- messages: integer, the number of the unread messages
- requests: integer, the number of the received contact requests 

Example 1
---------

`http://mdl-getnot.test/get_messages.php?uid=uhi@pte.hu&ts=1617994451&token=2264836edb030066264e1f06683c9c1c752ba609275a47cc2b94f7b890a55392f6a488d058c4f86dce189297ba51bd59bdc0789b681b6741babd1f692112e26e`

results

`{"status":"succes","notifications":"0","messages":"1","requests":"0"}`

Example 2
---------

Calling from another SAML-authenticated php application to get the notifications of the user currently logged in.

```php
        $authSource = 'default-ps'; // ... or whatever configured
        $saml = new \SimpleSAML\Auth\Simple($authSource); // SimpleSAMLphp SP >= 1.18.8 is required
	    $uidAttribute = 'eduPersonPrincipalName';
	    $secret = 'xxxx'; 
		if($saml->isAuthenticated()) {
		    $attributes = $saml->getAttributes();
		    if(isset($attributes[$uidAttribute]) {
		        $uid =  $attributes[$uidAttribute];
                if(is_array($uid)) $uid = $uid[0];
                $ts = time();
                $query = [
                    'uid' => $uid,
                    'ts' => $ts,
                    'token' => hash('sha512', "$uid,$ts,$secret"),
                ];
                $mdl_response = json_decode(file_get_contents($this->url.'?'.http_build_query($query)), JSON_OBJECT_AS_ARRAY);
		    }
		}
```
