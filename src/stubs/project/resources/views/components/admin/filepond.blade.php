@pushonce('above_css')
<link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" />
<link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet" />
<style> .filepond--item { width: calc(15% - 0.5em); }</style>
@endpushonce
@pushonce('below_js')
<!-- https://pqina.nl/filepond/docs/api/plugins/file-validate-type/ -->
<script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
<!-- https://pqina.nl/filepond/docs/api/plugins/image-exif-orientation/ -->
<script src="https://unpkg.com/filepond-plugin-image-exif-orientation/dist/filepond-plugin-image-exif-orientation.js"></script>
<!-- https://pqina.nl/filepond/docs/api/plugins/file-validate-size/ -->
<script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
<!-- https://pqina.nl/filepond/docs/api/plugins/image-preview/ -->
<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
<!-- https://pqina.nl/filepond/docs/api/instance/properties/ -->
<script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
@endpushonce
@php
    $init = $init?? true;
    $showAttachments = $init?? true;
    $oldInputBag = $oldInputBag?? $inputBag;
    $querySelectorID = $querySelectorID?? 'js_filepond';
    $inputName = isset($inputBag)? "{$inputBag}[{$type}]" : $type;
    $session_attachments =  \App\Models\Attachment::where([
        'attachable_type' => null,
        'attachable_id' => null,
        'session_id' => session()->getId(),
        'type' => $inputName,
    ])->orderBy('ord', 'ASC')->get()->keyBy('id');
    $edit_attachments = isset($attachable)?
        $attachable->attachments()->where('type', $type)->get()->keyBy('id') : collect();
    if($oldOrder = old("{$oldInputBag}.{$type}")) {
        $attachments = collect();
        foreach($oldOrder as $attachSource) {
            $attachId = (int)str_replace([$inputName."_", $type."_",], '', $attachSource);
            if(isset($session_attachments[$attachId])) {
                $attachments->push($session_attachments[$attachId]);
                continue;
            }
            if(isset($edit_attachments[$attachId])) {
                $attachments->push($edit_attachments[$attachId]);
                continue;
            }
        }
    } else {
        $attachments = $session_attachments->union($edit_attachments);
    }
    $translationsDefault = trans('admin/attachments/filepond');
    $langs = isset($translations)?
            transOrOther($translations, 'admin/attachments/filepond', array_keys($translationsDefault)) : $translationsDefault;
@endphp
@pushonceOnReady('below_js_on_ready')
<script>
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginFileValidateSize,
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateType,
    );
    var domElement = document.querySelector('#{{$querySelectorID}}');

    var initiateFilePond = function(domElement) {
        // Create a FilePond instance
        FilePond.create(domElement, {
            name: domElement.querySelector('input').getAttribute('name') ,
            credits: false,
            styleButtonRemoveItemPosition: 'right',
            styleItemPanelAspectRatio: 0.7,
            imagePreviewHeight: 150,
            maxParallelUploads: 1, //for now - fix later
            allowReorder: true,
            storeAsFile: true,
            @foreach($langs as $key => $value) '{{$key}}': '{{$value}}', @endforeach
                @if(isset($multiple) && $multiple) allowMultiple: true, @endif
                @isset($accept)
            acceptedFileTypes: {!! $accept !!},
            @endisset
                @isset($maxFileSize)
            maxFileSize: '{{$maxFileSize}}', //5MB //1KB
            @endisset
            beforeRemoveFile: function(item) {
                if(!item.serverId) return true;
                if(!confirm("{{$langs['remove_ask']}}")) return false;
            },
            onerror: function(error, file, status) {
                console.log(error);
            },
            onactivatefile: function(file) {
                if(!file.serverId) return;
                var url = '{{route("{$routeNamespace}.attach.preview", ['__TYPE__', '__ATTACH_ID__'])}}'.replace('__TYPE_____ATTACH_ID__', '');
                window.open(url+file.serverId, '_blank');
            },
            {{--            @isset($attachments)--}}
                {{--            files: [--}}
                {{--                    @foreach($attachments as $attach)--}}
                {{--                    @php $sourceType = $attach->session_id? $inputName : $type; @endphp--}}
                {{--                {--}}
                {{--                    // the server file reference--}}
                {{--                    // source: 'test1.jpg',--}}
                {{--                    source: '{{$sourceType}}_{{$attach->id}}',--}}
                {{--                    // set type to local to indicate an already uploaded file--}}
                {{--                    options: {--}}
                {{--                        type: 'local',--}}
                {{--                    },--}}
                {{--                }@if(!$loop->last), @endif--}}
                {{--                @endforeach--}}
                {{--            ],--}}
                {{--            @endisset--}}
            server: {
                timeout: 7000,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                process: {
                    url: '{{route("{$routeNamespace}.attach.process")}}',
                    method: 'POST',
                    withCredentials: false,
                    onload: null,
                    onerror: null,
                    ondata: (formData) => {
                        formData.append('__input_name__', '{{$inputName}}');
                        @isset($disk)
                        formData.append('__disk__', '{{$disk}}');
                        @endisset
                        @isset($site)
                        formData.append('__site__', '{{$site}}');
                        @endisset
                            return formData;
                    },
                },
                revert: '{{route("{$routeNamespace}.attach.revert")}}',
                load: '{{route("{$routeNamespace}.attach.load", ['__TYPE__', '__ATTACH_ID__'])}}'.replace('__TYPE_____ATTACH_ID__', ''), //it's last anyway
                remove: function(source, load, error) {
                    $.ajax({
                        url: '{{route("{$routeNamespace}.attach.revert")}}',
                        method: "DELETE",
                        cache: false,
                        timeout: 7000,
                        contentType: "application/json",
                        data: source,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        error: function(error) {
                            console.log(error);
                        },
                        success: function(response) {
                            load();
                        }
                    });

                },
            },
        })
    }
    @if($init)
    initiateFilePond(domElement);
    @endif
</script>
@endpushonceOnReady

<fieldset>
    <legend>{{$langs['label']}}</legend>
    <div class="form-group" id="{{$querySelectorID}}">
        {{$slot}}
        @if($showAttachments && $attachments->count())
            <ul class="d-none">
                @foreach($attachments as $attach)
                    @php $sourceType = $attach->session_id? $inputName : $type; @endphp
                    <li>
                        <label>
                            <input value='{{$sourceType}}_{{$attach->id}}' data-type="local" checked type="checkbox" />
                        </label>
                    </li>
                @endforeach
            </ul>
        @endif
        <input type="file"
               name="{{$inputName}}@if(isset($multiple) && $multiple)[]@endif"
               @if(isset($multiple) && $multiple) multiple @endif
               @if(isset($disabled) && $disabled) disabled @endif
        />
    </div>
</fieldset>
