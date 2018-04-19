<?php

return [
	'trees' => \DI\object(\hypeJunction\Trees\TreeService::class)
		->constructor(\DI\get('db')),
];