<?php
    namespace Marinar\Attachments\Database\Seeders;

    use Illuminate\Database\Seeder;
    use Marinar\Attachments\MarinarAttachments;

    class MarinarAttachmentsRemoveSeeder extends Seeder {

        use \Marinar\Marinar\Traits\MarinarSeedersTrait;

        public static function configure() {
            static::$packageName = 'marinar_attachments';
            static::$packageDir = MarinarAttachments::getPackageMainDir();
        }

        public function run() {
            if(!in_array(env('APP_ENV'), ['dev', 'local'])) return;

            $this->autoRemove();

            $this->refComponents->info("Done!");
        }

        public function clearDB() {
            $this->refComponents->task("Clear DB", function() {
                Permission::whereIn('name', [
                    'attachment.create',
                    'attachment.view',
                    'attachment.update',
                    'attachment.delete',
                ])
                    ->where('guard_name', 'admin')
                    ->delete();
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                return true;
            });
        }
    }
