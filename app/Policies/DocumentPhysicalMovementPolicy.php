<?php

namespace App\Policies;

use App\Models\DocumentPhysicalMovement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPhysicalMovementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DocumentPhysicalMovement $documentPhysicalMovement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DocumentPhysicalMovement $documentPhysicalMovement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DocumentPhysicalMovement $documentPhysicalMovement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DocumentPhysicalMovement $documentPhysicalMovement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DocumentPhysicalMovement $documentPhysicalMovement): bool
    {
        return false;
    }
    /**
     * Kullanıcının bu fiziksel harekete yanıt verme (kabul/red) yetkisi var mı?
     */
    public function respond(User $user, DocumentPhysicalMovement $movement): bool
    {
        // Sadece evrakın zimmetlendiği alıcı (receiver) işlem yapabilir
        return $user->id === $movement->receiver_id;
    }
}
