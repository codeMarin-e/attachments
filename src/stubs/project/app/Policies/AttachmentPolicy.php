<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function before(User $user, $ability) {
        // @HOOK_POLICY_BEFORE
        if($user->hasRole('Super Admin', 'admin') )
            return true;
    }

    public function view(User $user) {
        // @HOOK_POLICY_VIEW
        return $user->hasPermissionTo('attachments.view', request()->whereIam());
    }

    public function create(User $user) {
        // @HOOK_POLICY_CREATE
        return $user->hasPermissionTo('attachment.create', request()->whereIam());
    }

    public function update(User $user, Attachment $chAttachment) {
        // @HOOK_POLICY_UPDATE
        if( !$user->hasPermissionTo('attachment.update', request()->whereIam()) )
            return false;
        $chAttachment->loadMissing('attachable');
        if($chAttachment->attachable && !$user->can('update', $chAttachment->attachable) )
            return false;
        if(!$chAttachment->attachable && $chAttachment->session_id != session()->getId())
            return false;
        return true;
    }

    public function delete(User $user, Attachment $chAttachment) {
        // @HOOK_POLICY_DELETE
        if( !$user->hasPermissionTo('attachment.delete', request()->whereIam()) )
            return false;
        $chAttachment->loadMissing('attachable');
        if($chAttachment->attachable && !$user->can('update', $chAttachment->attachable) )
            return false;
        if(!$chAttachment->attachable && $chAttachment->session_id != session()->getId())
            return false;
        return true;
    }

    // @HOOK_POLICY_END


}
