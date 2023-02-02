<?php
        static::$cleaning['session_attachments'] = function($command, \Closure $next) {
            $command->components->task("Cleaning session attachments", function() use ($command){
                $files = \Symfony\Component\Finder\Finder::create()
                    ->in(config('session.files'))
                    ->files()
                    ->ignoreDotFiles(true);
                $sessionIds = [];
                foreach ($files as $file) {
                    $sessionIds[] = basename($file->getRealPath());
                }
                $expiredAttachs = \App\Models\Attachment::whereNotIn('session_id', $sessionIds)
                    ->whereNull('attachable_type')
                    ->whereNull('attachable_id')
                    ->whereNotNull('session_id')
                    ->get();
                foreach($expiredAttachs as $attach) {
                    $attach->delete();
                }
                return true;
            });
            return $next($command);
        };
