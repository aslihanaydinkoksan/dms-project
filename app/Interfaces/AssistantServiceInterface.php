<?php

namespace App\Interfaces;

use App\Models\User;

interface AssistantServiceInterface
{
    /**
     * Gelen mesajı işler ve [reply, link, link_text] dizisi döner.
     */
    public function ask(string $message, User $user): array;
}
