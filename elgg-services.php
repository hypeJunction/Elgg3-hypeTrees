<?php

return [
	'trees' => \DI\create(\hypeJunction\Trees\TreeService::class)
		->constructor(\DI\get('db')),
];
