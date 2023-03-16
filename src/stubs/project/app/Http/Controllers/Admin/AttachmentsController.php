<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class AttachmentsController extends Controller {
    public function __construct() {
        if(!request()->route()) return;
        // @HOOK_CONSTRUCT
    }

    public function load($type, $chAttachment) {
        if($chAttachment->type !== $type) {
            return response('Wrong data', 422);
        }
        if(!$chAttachment->session_id) {
            if(!auth()->user()->can('view', $chAttachment->attachable_type)) {
                return response('Wrong data', 422);
            }
        }
        if(!Storage::disk( $chAttachment->disk )->exists($chAttachment->getFilePath()))
            abort(404);
        return response(Storage::disk( $chAttachment->disk )->get($chAttachment->getFilePath()), 200, [
            "Content-Disposition" => 'inline; filename="'.basename($chAttachment->original_name).'"',
            "Content-Type" => Storage::disk( $chAttachment->disk )->mimeType($chAttachment->getFilePath())
        ]);
    }

    public function preview($type, $chAttachment) {
        if($chAttachment->type !== $type) {
            return response('Wrong data', 422);
        }
        if(!$chAttachment->session_id) {
            if(!auth()->user()->can('view', $chAttachment->attachable_type)) {
                return response('Wrong data', 422);
            }
        }
        return response()->redirectTo( $chAttachment->getUrl() );
    }

    public function process(Request $request) {
        $inputs = request()->all();
        if(empty($inputs)) {
            throw ValidationException::withMessages([
                'no_data' => trans('admin/attachments/attachments.validation.no_data'),
            ]);
        }
        $inputs = Arr::dot(request()->all());
        $mainMessages = Arr::dot((array)trans('admin/attachments/attachments.validation.for_files'));
        $messages = Arr::dot((array)trans('admin/attachments/attachments.validation.common'));
        foreach($inputs as $inputKey => $inputData) {
            if($inputKey ===  '__input_name__' ) {
                $rules[$inputKey] = ['required', 'string', 'max:255'];
                continue;
            }
            if($inputKey ===  '__disk__' ) {
                $rules[$inputKey] = ['nullable', 'string', 'max:255'];
                continue;
            }
            if($inputKey ===  '__site__' ) {
                $rules[$inputKey] = ['nullable', 'numeric'];
                continue;
            }
            $rules[$inputKey] = ['required', 'file', 'max:'.config('app.max_file_size')];
            $messages[$inputKey] = $mainMessages;
        }
        $validatedData = Validator::make(request()->all(), $rules, $messages)->validate();
        $validatedData['__input_name__'] = str_replace('[]', '', $validatedData['__input_name__']);
        $commonWhere = [
            'type' => $validatedData['__input_name__'],
            'disk' => $validatedData['__disk__']?? config('marinar_attachments.disk'),
            'site_id' => $validatedData['__site__']?? app()->make('Site')->id,
            'attachable_id' => null,
            'attachable_type' => null,
            'session_id' => session()->getId(),
            'dir' => Attachment::$attach_folder. DIRECTORY_SEPARATOR . $validatedData['__input_name__'],
        ];
        Attachment::storeAttachments($request->file(str_replace(['[', ']'], ['.', ''], $validatedData['__input_name__'])), $commonWhere);
        $attachData = Attachment::where($commonWhere)->latest()->select('id', 'type')->first();
        return response($attachData->type."_".$attachData->id, 200);
    }

    public function revert(Request $request) {
        $send = explode('_', $request->getContent());
        $attachment = false;
        Validator::make([
            'type' => $send[0]?? null,
            'id' => $send[1]?? null,
        ], [
            'id' => ['required', 'numeric', function($attribute, $value, $fail) use (&$attachment) {
                if(!($attachment = Attachment::find($value))) {
                    return $fail( trans('admin/attachments/attachments.validation.revert.id.required') );
                }
            },
                'type' => ['required', 'string', 'max:255', function($attribute, $value, $fail) use (&$attachment) {
                    if($attachment && $attachment->type != $value) {
                        return $fail( trans('admin/attachments/attachments.validation.revert.type.required') );
                    }
                }]]
        ], Arr::dot((array)trans('admin/attachments/attachments.validation.revert')))->validate();
        if(!auth()->user()->can('delete', $attachment)) abort(403);
        if(!$attachment->session_id) {
            $attachment->loadMissing('attachable');
            if(!auth()->user()->can('update', $attachment->attachable)) abort(403);
        }

        $attachment->delete();
        return response('success', 200);
    }


}
