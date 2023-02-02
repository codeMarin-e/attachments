<?php
	return [
		'install' => [
            'php artisan db:seed --class="\Marinar\Attachments\Database\Seeders\MarinarAttachmentsInstallSeeder"',
		],
        'remove' => [
            'php artisan db:seed --class="\Marinar\Attachments\Database\Seeders\MarinarAttachmentsRemoveSeeder"',
        ]
	];
