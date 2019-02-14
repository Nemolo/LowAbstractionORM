<?php
return [
	'settings' => [
		'displayErrorDetails' => false,
		'dev_mode'            => empty( boolval( getenv( 'devmode' ) ) ) ? true : getenv( 'devmode' ),
		'db'            => [
			'driver'   => 'pdo_mysql',
			'host'     => empty( getenv( 'dbhost' ) ) ? 'localhost' : getenv( 'dbhost' ),
			'dbname'   => empty( getenv( 'dbname' ) ) ? 'michele_per_tutt' : getenv( 'dbname' ),
			'user'     => empty( getenv( 'dbuser' ) ) ? 'root' : getenv( 'dbuser' ),
			'password' => empty( getenv( 'dbpass' ) ) ? '' : getenv( 'dbpass' ),
		],
		'auth' => [
			'privateKey' => __DIR__.'/jwtRS256.key',
			'publicKey' => __DIR__.'/jwtRS256.key.pub'
		]
	]
];