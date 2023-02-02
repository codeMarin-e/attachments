<?php
namespace Database\Seeders\Packages\Attachments;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MarinarAttachmentsSeeder extends Seeder {

    public function run() {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::upsert([
            ['guard_name' => 'admin', 'name' => 'attachments.view'],
            ['guard_name' => 'admin', 'name' => 'attachment.create'],
            ['guard_name' => 'admin', 'name' => 'attachment.update'],
            ['guard_name' => 'admin', 'name' => 'attachment.delete'],
        ], ['guard_name','name']);
    }
}
