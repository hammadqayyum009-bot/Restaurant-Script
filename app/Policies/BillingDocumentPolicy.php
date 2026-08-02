<?php

namespace App\Policies;

use App\Models\BillingDocument;
use App\Models\User;

/**
 * Every ability here still requires the manage-billing Gate — this policy
 * only adds the immutability rule on top: once a document has left 'draft',
 * update/delete/issue are permanently unavailable. There is no route or UI
 * path around that; the policy is the single place it's enforced.
 */
class BillingDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-billing');
    }

    public function view(User $user, BillingDocument $document): bool
    {
        return $user->can('manage-billing');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-billing');
    }

    public function update(User $user, BillingDocument $document): bool
    {
        return $user->can('manage-billing') && $document->isDraft();
    }

    public function delete(User $user, BillingDocument $document): bool
    {
        return $user->can('manage-billing') && $document->isDraft();
    }

    public function issue(User $user, BillingDocument $document): bool
    {
        return $user->can('manage-billing') && $document->isDraft();
    }

    public function archive(User $user, BillingDocument $document): bool
    {
        return $user->can('manage-billing');
    }
}
