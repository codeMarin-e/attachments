<?php
    namespace Marinar\Attachments\Traits;

    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;
    use League\Flysystem\Util;
    use App\Models\Attachment;


    /**
     * Used in Marinar\Attachments\Mixins\AttachableMixin
     * When changed change there as well!
     */
    trait Attachable {

        public static function bootAttachable() {
            static::deleting( static::class.'@onDeleting_attachments' );
        }

        public function addAttachments($uploadedFiles, $type = '', $attachAttributes = []) {
            Attachment::storeAttachments($uploadedFiles, [
                'disk' => isset($attachAttributes['disk'])??
                    (property_exists(static::class, 'attach_disk')?
                        static::$attach_disk : config('marinar_attachments.disk') //IF null will use default
                    ),
                'type' => $type,
                'site_id' => $attachAttributes['site_id']?? app()->make('Site')->id,
                'attachable_id' => $this->id,
                'attachable_type' => static::class,
                'session_id' => null,
                'folder' => static::$attach_folder. DIRECTORY_SEPARATOR . $this->id,
            ]);
        }

        public function attachments() {
            return $this->morphMany( Attachment::class, 'attachable');
        }

        public function getMainAttachment($type = null) {
            $qryBld = $this->attachments()->orderByDesc('main');
            if($type) $qryBld->where('type', $type);
            return $qryBld->first();
        }

        public function onDeleting_attachments($model) {
            $model->loadMissing('attachments');
            foreach($model->attachments as $attach) {
                $attach->delete();
            }
        }

        public function reAttachAndOrder($attachments, $type, $changes = [], $orderAfter = 0) {
            if(!is_iterable($attachments)) return false;
            if(is_array($attachments)) $attachments = collect($attachments);
            if($attachments->isEmpty()) return $attachments;
            $class = get_class($this);
            $directory = ($class)::$attach_folder.DIRECTORY_SEPARATOR.$this->id;
            $attachIds = [];
            $ordRawDB = '';
            foreach($attachments as $index => $attach) {
                $ord = $orderAfter+$index+1;
                $attachIds[] = $attach->id;
                Storage::disk($this->disk)->move( $attach->getFilePath(), $directory.DIRECTORY_SEPARATOR.$attach->filename);
                $ordRawDB .= " WHEN id={$attach->id} THEN {$ord}";
            }
            Attachment::whereIn('id', $attachIds)
                ->update(array_merge([
                    'attachable_id' => $this->id,
                    'attachable_type' => $class,
                    'session_id' => null,
                    'type' => $type,
                    'dir' => $directory,
                    'ord' => DB::raw("CASE" . $ordRawDB . ' END')
                ], $changes));
            return Attachment::whereIn('id', $attachIds)->get();
        }

    }
