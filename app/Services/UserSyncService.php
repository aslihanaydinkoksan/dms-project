<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class UserSyncService
{
    protected MysApiService $mysApi;

    public function __construct(MysApiService $mysApi)
    {
        $this->mysApi = $mysApi;
    }

    /**
     * Kullanıcıları MYS üzerinden senkronize eder.
     */
    public function sync(): int
    {
        set_time_limit(300);

        $users = $this->mysApi->getAllUsers();
        $syncedCount = 0;
        $dummyPassword = Hash::make(Str::random(16));

        DB::transaction(function () use ($users, &$syncedCount, $dummyPassword) {
            foreach ($users as $userData) {
                $user = null;
                $incomingEmail = $userData['email'] ?? null;
                $incomingName = $userData['name'] ?? null;
                $incomingTc = $userData['tc_no'] ?? null;
                $incomingRegNo = $userData['registration_no'] ?? null;

                if (!$incomingEmail) continue;

                // A. MYS'den gelen e-posta aslında TC numarası mı? (Örn: 42355231364@koksan.com)
                $isIncomingTcEmail = preg_match('/^[0-9]{10,11}@/', $incomingEmail);

                // Eğer MYS tc_no'yu null gönderiyorsa ama e-postaya TC yazmışsa, onu akıllıca TC olarak kabul edelim
                if ($isIncomingTcEmail && empty($incomingTc)) {
                    $incomingTc = explode('@', $incomingEmail)[0];
                }

                // --- 1. AŞAMA: KESİN EŞLEŞTİRME (TC VEYA SİCİL NO) ---
                if (!empty($incomingTc)) {
                    $user = User::withTrashed()->where('tc_no', $incomingTc)->first();
                }
                if (!$user && !empty($incomingRegNo)) {
                    $user = User::withTrashed()->where('registration_no', $incomingRegNo)->first();
                }

                // --- 2. AŞAMA: E-POSTA İLE EŞLEŞTİRME ---
                if (!$user && !empty($incomingEmail)) {
                    $user = User::withTrashed()->where('email', $incomingEmail)->first();
                }

                // --- 3. AŞAMA: İSİM İLE SEZGİSEL EŞLEŞTİRME (WORKFLOW MANTIĞI) ---
                // TC boşsa ve e-posta değişmişse son çare olarak isme bakar.
                if (!$user && !empty($incomingName)) {
                    $potentialUsers = User::withTrashed()->where('name', $incomingName)->get();
                    if ($potentialUsers->count() === 1) {
                        $user = $potentialUsers->first();
                    }
                }

                // --- 4. AŞAMA: HİÇBİR ŞEKİLDE BULUNAMADIYSA YENİ KAYIT ---
                if (!$user) {
                    $user = new User();
                    $user->password = $dummyPassword;
                }

                // --- ÇAKIŞMA ÇÖZÜCÜ (UNIQUE KORUMASI) ---
                if (!empty($incomingTc) && $user->tc_no !== $incomingTc) {
                    $conflictTcUser = User::withTrashed()->where('tc_no', $incomingTc)->where('id', '!=', $user->id)->first();
                    if ($conflictTcUser) $conflictTcUser->update(['tc_no' => null]);
                }

                if (!empty($incomingRegNo) && $user->registration_no !== $incomingRegNo) {
                    $conflictRegUser = User::withTrashed()->where('registration_no', $incomingRegNo)->where('id', '!=', $user->id)->first();
                    if ($conflictRegUser) $conflictRegUser->update(['registration_no' => null]);
                }

                if (!empty($incomingEmail) && $user->email !== $incomingEmail) {
                    $conflictEmailUser = User::withTrashed()->where('email', $incomingEmail)->where('id', '!=', $user->id)->first();
                    if ($conflictEmailUser) $conflictEmailUser->update(['email' => 'conflict_' . time() . '_' . $conflictEmailUser->email]);
                }

                // --- BİLGİLERİ GÜNCELLE ---
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->name = $incomingName;
                $user->tc_no = $incomingTc; // Veritabanına işlenir, bir sonraki sefer isme gerek kalmadan 1. Aşamadan bulunur!
                $user->registration_no = $incomingRegNo;
                $user->is_active = $userData['is_active'] ?? true;

                // --- E-POSTA KALKANI ---
                // Eğer MYS çöp bir TC e-postası (123@koksan) gönderiyorsa VE kullanıcının zaten düzgün bir e-postası (yusuf.dasgin@) varsa; 
                // Asla düzgün e-postayı ezme!
                $isExistingProperEmail = !empty($user->email) && !preg_match('/^[0-9]{10,11}@/', $user->email);

                if (!($isIncomingTcEmail && $isExistingProperEmail)) {
                    $user->email = $incomingEmail;
                }

                if (!empty($userData['department'])) {
                    $user->department_id = $userData['department']['id'] ?? null;
                }

                $user->save();
                $syncedCount++;
            }
        });

        return $syncedCount;
    }
}
