<?php
return [
    implode(DIRECTORY_SEPARATOR, [ base_path(), 'app', 'Console', 'Commands', 'GarbageCollector.php']) => [
        "// @HOOK_CLEANING" => implode(DIRECTORY_SEPARATOR, [__DIR__, 'HOOK_CLEANING.php']),
    ],
    implode(DIRECTORY_SEPARATOR, [ base_path(), 'config', 'marinar.php']) => [
        "// @HOOK_MARINAR_CONFIG_ADDONS" => "\t\t\\Marinar\\Attachments\\MarinarAttachments::class, \n"
    ]
];
