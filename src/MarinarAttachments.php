<?php
    namespace Marinar\Attachments;

    use Marinar\Attachments\Database\Seeders\MarinarAttachmentsInstallSeeder;

    class MarinarAttachments {

        public static function getPackageMainDir() {
            return __DIR__;
        }

        public static function injects() {
            return MarinarAttachmentsInstallSeeder::class;
        }
    }
