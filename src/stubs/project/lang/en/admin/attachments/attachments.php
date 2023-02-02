<?php
return [
    'validation.no_data' => 'There is no data',
    'validation.for_files' => \Illuminate\Support\Arr::undot([
        'required' => 'File should be uploaded, too',
        'file' => 'It is not a file',
        'max' => 'The file is too, big',
    ]),
    'validation.common' => \Illuminate\Support\Arr::undot([
        '__input_name__.required' => 'Input name is required',
        '__input_name__.string' => 'Input name should be string',
        '__input_name__.max' => 'Input name is too long',
        '__disk__.string' => 'Input name should be string',
        '__disk__.max' => 'Input name is too long',
        '__site__.string' => 'Input name should be string',
        '__site__.max' => 'Input name is too long',
    ]),
    'validation.revert' => \Illuminate\Support\Arr::undot([
        'id.required' => 'ID is required',
        'id.numeric' => 'ID should be numeric',
        'type.required' => 'Type is required',
        'type.string' => 'Type should be string',
        'type.max' => 'Type is too long',
    ])
];
