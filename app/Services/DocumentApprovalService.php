<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\WorkflowActionRequired; // Bildirim sınıfımızı import ettik
use Illuminate\Support\Facades\DB;
use Exception;

class DocumentApprovalService
{
    /**
     * 1. AKIŞI BAŞLAT: Belge için sıralı ve paralel onaycıları atar.
     */
    public function startWorkflow(Document $document, array $approvers, int $userId, string $ip, string $userAgent): void
    {
        // Bildirim atılacak ilk adım kullanıcılarını transaction dışında toplamak için değişken:
        $firstStepUsers = [];

        DB::transaction(function () use ($document, $approvers, $userId, $ip, $userAgent, &$firstStepUsers) {
            $document->approvals()->delete();

            foreach ($approvers as $approver) {
                DocumentApproval::create([
                    'document_id' => $document->id,
                    'user_id' => $approver['user_id'],
                    'step_order' => $approver['step_order'],
                    'status' => 'pending'
                ]);
            }

            $document->update(['status' => 'pending_approval']);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'workflow_started',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'new_values' => ['approvers_count' => count($approvers)],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            $minStep = collect($approvers)->min('step_order');
            $firstStepUserIds = collect($approvers)->where('step_order', $minStep)->pluck('user_id');
            $firstStepUsers = User::whereIn('id', $firstStepUserIds)->get();
        });

        // TRANSACTION BİTTİKTEN SONRA BİLDİRİM FIRLAT:
        foreach ($firstStepUsers as $user) {
            /** @var \App\Models\User $user */
            $user->notify(new WorkflowActionRequired($document, 'pending_your_approval'));
        }
    }

    /**
     * 2. ONAYLAMA İŞLEMİ: Kademeli yetki kontrolü ile belgeyi onaylar.
     */
    public function approveDocument(Document $document, int $userId, string $ip, string $userAgent): void
    {
        // Bildirim senaryolarını transaction sonrasında çalıştırabilmek için bayraklar (flags)
        $isFullyApproved = false;
        $nextStepUsers = [];

        DB::transaction(function () use ($document, $userId, $ip, $userAgent, &$isFullyApproved, &$nextStepUsers) {
            // --- VEKALET ZIRHI BAŞLANGICI ---
            $user = User::find($userId);
            $proxyForIds = $user->getActiveDelegatorIds();
            $allIdsToCheck = array_merge([$userId], $proxyForIds); // Kendi ID'm + Vekili Olduğum Kişilerin ID'leri

            // where yerine whereIn kullanıyoruz ki vekalet edilen kişilerin belgelerini de yakalasın
            $approval = DocumentApproval::where('document_id', $document->id)
                ->whereIn('user_id', $allIdsToCheck)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new Exception("Bu belge için bekleyen bir onay göreviniz (veya vekaletiniz) bulunmamaktadır.");
            }

            $unapprovedPreviousSteps = DocumentApproval::where('document_id', $document->id)
                ->where('step_order', '<', $approval->step_order)
                ->where('status', '!=', 'approved')
                ->exists();

            if ($unapprovedPreviousSteps) {
                throw new Exception("Önceki onay adımları tamamlanmadan bu belgeyi onaylayamazsınız.");
            }

            $comment = null;
            if ($approval->user_id !== $userId) {
                // Eğer onayı bekleyen asıl kişi ben değilsem, demek ki vekilim!
                $delegator = User::find($approval->user_id);
                $comment = "[VEKALETEN ONAY] {$user->name}, bu işlemi izinde olan {$delegator->name} adına gerçekleştirmiştir.";
            }

            $approval->update([
                'status' => 'approved',
                'comment' => $comment, // Otomatik vekalet notu buraya yazılır
                'action_date' => now()
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'document_approved',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'new_values' => ['step' => $approval->step_order],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            $pendingApprovalsExist = DocumentApproval::where('document_id', $document->id)
                ->where('status', '!=', 'approved')
                ->exists();

            if (!$pendingApprovalsExist) {
                $document->update(['status' => 'published', 'is_locked' => false, 'locked_by' => null]);
                /** @var \App\Models\DocumentVersion|null $latestVersion */
                $latestVersion = $document->versions()->orderBy('version_number', 'desc')->first();
                if ($latestVersion && !$latestVersion->is_current) {
                    $document->versions()->update(['is_current' => false]); // Diğerlerini kapat
                    $latestVersion->update(['is_current' => true]); // Yeniyi parlat!
                }

                AuditLog::create([
                    'user_id' => null,
                    'event' => 'document_published',
                    'auditable_type' => Document::class,
                    'auditable_id' => $document->id,
                ]);

                $isFullyApproved = true; // Bayrağı kaldır
            } else {
                $currentStepPending = DocumentApproval::where('document_id', $document->id)
                    ->where('step_order', $approval->step_order)
                    ->where('status', '!=', 'approved')
                    ->exists();

                if (!$currentStepPending) {
                    $nextStep = DocumentApproval::where('document_id', $document->id)
                        ->where('step_order', '>', $approval->step_order)
                        ->min('step_order');

                    if ($nextStep) {
                        $nextApprovers = DocumentApproval::with('user')
                            ->where('document_id', $document->id)
                            ->where('step_order', $nextStep)
                            ->get();

                        foreach ($nextApprovers as $nextApproval) {
                            if ($nextApproval->user) {
                                $nextStepUsers[] = $nextApproval->user; // Bildirim atılacakları topla
                            }
                        }
                    }
                }
            }
        });

        // TRANSACTION BİTTİKTEN SONRA BİLDİRİMLERİ FIRLAT:
        if ($isFullyApproved) {
            $owner = $document->currentVersion?->createdBy;
            if ($owner) {
                $owner->notify(new WorkflowActionRequired($document, 'approved'));
            }
        } elseif (count($nextStepUsers) > 0) {
            foreach ($nextStepUsers as $nextUser) {
                $nextUser->notify(new WorkflowActionRequired($document, 'pending_your_approval'));
            }
        }
    }

    /**
     * 3. REDDETME İŞLEMİ: Akışı durdurur ve belgeyi taslağa geri gönderir.
     */
    public function rejectDocument(Document $document, int $userId, string $comment, string $ip, string $userAgent): void
    {
        $owner = null;

        DB::transaction(function () use ($document, $userId, $comment, $ip, $userAgent, &$owner) {
            $user = User::find($userId);
            $proxyForIds = $user->getActiveDelegatorIds();
            $allIdsToCheck = array_merge([$userId], $proxyForIds);

            $approval = DocumentApproval::where('document_id', $document->id)
                ->whereIn('user_id', $allIdsToCheck)
                ->where('status', 'pending')
                ->firstOrFail();

            // --- VEKALET NOTUNU YORUMA EKLE ---
            if ($approval->user_id !== $userId) {
                $delegator = User::find($approval->user_id);
                $comment = "[VEKALETEN RED] {$user->name}, bu işlemi {$delegator->name} adına reddetti.\nSebep: " . $comment;
            }

            $approval->update([
                'status' => 'rejected',
                'comment' => $comment,
                'action_date' => now()
            ]);

            $document->update(['status' => 'rejected']);

            AuditLog::create([
                'user_id' => $userId,
                'event' => 'document_rejected',
                'auditable_type' => Document::class,
                'auditable_id' => $document->id,
                'new_values' => ['comment' => $comment],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            $owner = $document->currentVersion?->createdBy;
        });

        // TRANSACTION BİTTİKTEN SONRA BİLDİRİM FIRLAT:
        if ($owner) {
            $owner->notify(new WorkflowActionRequired($document, 'rejected', $comment));
        }
    }
}
