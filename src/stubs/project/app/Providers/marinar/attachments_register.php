<?php

use App\Models\Attachment;
use App\Policies\AttachmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::model('chAttachment', Attachment::class);
Gate::policy(Attachment::class, AttachmentPolicy::class);

