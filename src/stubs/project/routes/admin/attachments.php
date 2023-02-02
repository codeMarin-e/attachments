<?php
use \App\Http\Controllers\Admin\AttachmentsController;
use App\Models\Attachment;

Route::group([
    'controller' => AttachmentsController::class,
    'middleware' => ['auth:admin', 'can:view,'.Attachment::class],
    'as' => 'attach.', //naming prefix
    'prefix' => 'attach', //for routes
], function() {
    Route::post('', 'process')->name('process')->middleware('can:create,'.Attachment::class);
    Route::delete('', 'revert')->name('revert');
    Route::get('{type}_{chAttachment}', 'load')->name('load');
    Route::get('preview/{type}_{chAttachment}', 'preview')->name('preview');
});
