<?php
    namespace Marinar\Attachments\Database\Seeders;

    use Illuminate\Database\Seeder;
    use Marinar\Attachments\MarinarAttachments;

    class MarinarAttachmentsInstallSeeder extends Seeder {

        use \Marinar\Marinar\Traits\MarinarSeedersTrait;

        public static function configure() {
            static::$packageName = 'marinar_attachments';
            static::$packageDir = MarinarAttachments::getPackageMainDir();
        }

        public function run() {
            if(!in_array(env('APP_ENV'), ['dev', 'local'])) return;

            $this->autoInstall();

            $this->refComponents->info("Done!");
        }

    }
