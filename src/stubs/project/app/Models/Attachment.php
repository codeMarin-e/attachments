<?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\DB;
    use App\Traits\MacroableModel;
    use Illuminate\Support\Facades\Storage;
    use Intervention\Image\Facades\Image;
    use App\Traits\Attachable;
    use App\Traits\Orderable;

    class Attachment extends Model {

        use MacroableModel;
        use Orderable;
        use Attachable;

        // @HOOK_TRAITS

        protected $guarded = [];

        public static $attach_folder = 'attachments';

        protected static function boot() {
            parent::boot();
            static::deleting( static::class.'@onDelete_storage' );

            // @HOOK_BOOT
        }

        public function orderableQryBld($qryBld = null) {
            $qryBld = $qryBld? clone $qryBld : $this;
            return $qryBld->where($this->sameQryWheres());
        }

        public function sameQryWheres () {
            return [
                'attachable_type' => $this->attachable_type,
                'attachable_id' => $this->attachable_id,
                'type' => $this->type,
                'site_id' => $this->site_id,
                'session_id' => $this->session_id,
            ];
        }

        public function getFilePath() {
            return $this->dir.DIRECTORY_SEPARATOR.$this->filename;
        }

        public function getUrl() {
            return Storage::disk( $this->disk )->url($this->getFilePath());
//            return config('app.url').'/'.static::$attach_folder.'/'.$this->dir.'/'.$this->filename;
        }

        public function attachable() {
            return $this->morphTo();
        }

        public static function onDelete_storage(self $attach) {
            $filePath = $attach->getFilePath();
            Storage::disk( $attach->disk )->delete( $filePath );
            $directory = dirname($filePath);
            if(empty(Storage::disk( $attach->disk )->files($directory))) {
                $subs = Storage::disk($attach->disk )->directories($directory);
                unset($subs[0], $subs[1]);
                if(empty($subs)) {
                    Storage::disk($attach->disk )->deleteDirectory($directory);
                }
            }
        }

        public function setMain($value) {
            if($this->main == $value) return;
            if($value) {
                static::query()
                    ->where($this->sameQryWheres())
                    ->addBinding([$this->id], 'join')
                    ->update([
                        'updated_at' => new \Datetime(),
                        'main' => DB::raw("CASE WHEN id = ? then 1 ELSE 0 END")
                    ]);
                return;
            }
            $this->main = (bool)$value;
            $this->save();
        }

        public function getThumbnail($size) {
            if($return = $this->attachments()->where([
                'disk' => $this->disk,
                'type' => 'thumb',
                'size' => $size,
                'site_id' => $this->site_id
            ])->first())
                return $return;
            if($this->extension === false) {
                return $this->extension = '_blank';
            }
            if(in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extIconFilePath = (!Storage::disk( $this->disk )->exists( $this->getFilePath() ))?
                    implode(DIRECTORY_SEPARATOR, array(
                        dirname( base_path() ), 'public_html', 'admin', 'ext_icons', '_blank.png'
                    )) : Storage::disk( $this->disk )->path( $this->getFilePath() );
            } else {
                $extIconFilePath = implode(DIRECTORY_SEPARATOR, array(
                    dirname( base_path() ), 'public_html', 'admin', 'ext_icons',  $this->extension.'.png'
                ));
                if(!realpath($extIconFilePath)) {
                    $extIconFilePath = implode(DIRECTORY_SEPARATOR, array(
                        dirname( base_path() ), 'public_html', 'admin', 'ext_icons', '_blank.png'
                    ));
                }
            }

//            $img = Image::make( public_path(static::$attach_folder.DIRECTORY_SEPARATOR.$this->dir.DIRECTORY_SEPARATOR.$this->filename) ); //open an image file
            $img = Image::make( $extIconFilePath );
            $dimensions = explode('x', $size);
            $width = (int)$dimensions[0];
            $height = (int)$dimensions[1];
            if(is_callable('exif_read_data')) {
                $img->orientate();
            }
            $img->resize( ($width? $width : null), ($height? $height : null), function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $fileNameParts = explode('.', $this->filename);
            $ext = array_pop( $fileNameParts );
            $thumbName = implode('.', $fileNameParts).'_thumb_'.$size.'.'.$ext;
//            $img->save( public_path(static::$attach_folder.DIRECTORY_SEPARATOR.$this->dir.DIRECTORY_SEPARATOR.$thumbName) );
            Storage::disk( $this->disk )
                ->put( $this->dir.'/'.$thumbName , (string)$img->encode() );
            $freeOrd = static::freeOrd(Attachment::where([
                'type' => 'thumb',
                'site_id' => $this->site_id,
                'disk' => $this->disk,
                'attachable_id' => $this->id,
                'attachable_type' => static::class,
            ]));
            return $this->attachments()->create([
                'disk' => $this->disk,
                'type' => 'thumb',
                'size' => $size,
                'original_name' => $this->original_name,
                'filename' => $thumbName,
                'dir' => $this->dir,
                'site_id' => $this->site_id,
                'ord' => $freeOrd,
                'extension' => $this->extension,
            ]);
        }

        public static function storeAttachments($uploadedFiles, $attachAttributes) {
            $qryBldWheres = [
                'type' => $attachAttributes['type'],
                'site_id' => isset($attachAttributes['site_id'])?? app()->make('Site')->id,
                'disk' => $attachAttributes['disk'],
                'attachable_id' => $attachAttributes['attachable_id'],
                'attachable_type' => $attachAttributes['attachable_type'],
                'session_id' => $attachAttributes['session_id']
            ];
            $freeOrd = Attachment::freeOrd(Attachment::where($qryBldWheres));
            if($uploadedFiles instanceof UploadedFile) {
                $uploadedFiles = [ $uploadedFiles ];
            }
            $attaches = [];
            foreach((array)$uploadedFiles as $uploadedFile) {
                $origName = config('marinar_attachments.keep_original_name')?
                    str_remove_emoji( $uploadedFile->getClientOriginalName() ) : $uploadedFile->hashName();
                $attachAttributes['filename'] = $uploadedFile->hashName();

                $attachPath = $uploadedFile->storeAs(
                    $attachAttributes['dir'],
                    $attachAttributes['filename'],
                    ['disk' => $attachAttributes['disk']]
                );
                $attaches[] = array_merge([
                    'dir' => $attachAttributes['dir'],
                    'filename' => $attachAttributes['filename'],
                    'original_name' => $origName,
                    'extension' => $uploadedFile->extension(),
                    'ord' => $freeOrd,
                ], $qryBldWheres, $attachAttributes);
                $freeOrd++;
            }
            Attachment::upsert($attaches, [
                'attachable_id','attachable_type',
                'site_id', 'disk', 'dir', 'filename', 'type'
            ], ['attachable_id']);
        }

        public function copyToOther($object, $change = []) {
            $class = get_class($object);
            $filePath = implode(DIRECTORY_SEPARATOR, [
                ($class)::$attach_folder, $object->id, basename($this->getFilePath())
            ]);
            Storage::disk($this->disk)->copy( $this->getFilePath(), $filePath);
            $fillData = array_merge([
                'attachable_id' => $object->id,
                'attachable_type' => $class,
                'session_id' => null,
                'dir' => dirname($filePath),
                'type' => $object->type,
            ], $change);
            $fillData['ord'] = static::freeOrd(Attachment::where($fillData));
            $attachment = $this->replicate()->fill($fillData);
            $attachment->save();
            return $attachment;
        }

        public function reAttach($attachable, $change = []) {
            $this->load('attachable');
            if($attachable->is($this->attachable)) return $this;
            $class = get_class($attachable);
            $directory = ($class)::$attach_folder.DIRECTORY_SEPARATOR.$attachable->id;
            Storage::disk($this->disk)->move( $this->getFilePath(), $directory.DIRECTORY_SEPARATOR.$this->filename);
            $change = array_merge([
                'attachable_id' => $attachable->id,
                'attachable_type' => $class,
                'session_id' => null,
                'dir' => $directory,
                'type' => $this->type,
            ], $change);
            $change['ord'] = static::freeOrd(Attachment::where($change));
            $this->update($change);
            return $this;
        }

        public function getSize() {
            $filePath = $this->getFilePath();
            if(!Storage::disk($this->disk)->exists($filePath)) {
                return false;
            }
            return Storage::disk($this->disk)->size($filePath);
        }


    }
