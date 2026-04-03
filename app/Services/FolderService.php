<?php

namespace App\Services;

use App\Models\Folder;
use Illuminate\Support\Collection;
use Exception;

class FolderService
{
    /**
     * Frontend (Blade/Vue) için n-derinlikte klasör ağacını getirir.
     * Kullanım Alanı: Sol menüdeki TreeView veya Liste Ekranı.
     */
    public function getFolderTree(): Collection
    {
        // Sadece Root (Ana) klasörleri al (parent_id = null)
        // Ve içindeki tüm alt klasörleri childrenRecursive ile Eager Loading (N+1 engellenerek) çek.
        return Folder::whereNull('parent_id')
            ->with('childrenRecursive')
            ->get();
    }

    /**
     * Frontend'deki <select> (Açılır Menü) için düzleştirilmiş (Flat) liste getirir.
     * Çıktı Örneği: "Yönetim Kurulu > Bilgi Teknolojileri > Yazılım Geliştirme"
     */
    public function getFlatFolderList(): array
    {
        $folders = Folder::with('parent')->get();
        $flatList = [];

        // IDE'ye bu döngüdeki elemanların Folder modeli olduğunu açıkça söylüyoruz
        /** @var \App\Models\Folder $folder */
        foreach ($folders as $folder) {
            $flatList[$folder->id] = $this->generateBreadcrumb($folder);
        }

        // Alfabetik sıralama yapıyoruz ki formlarda düzgün görünsün
        asort($flatList);
        return $flatList;
    }

    /**
     * Yeni bir klasör oluşturur.
     */
    public function createFolder(array $data): Folder
    {
        // Güvenlik: Eğer parent_id verilmişse, öyle bir klasör var mı kontrol et
        if (!empty($data['parent_id'])) {
            $parentExists = Folder::where('id', $data['parent_id'])->exists();
            if (!$parentExists) {
                throw new Exception("Seçilen üst klasör bulunamadı.");
            }
        }

        return Folder::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);
    }

    /**
     * Rekürsif Breadcrumb Üretici (Özel Yardımcı Metot)
     */
    private function generateBreadcrumb(Folder $folder): string
    {
        if ($folder->parent) {
            return $this->generateBreadcrumb($folder->parent) . ' > ' . $folder->name;
        }
        return $folder->name;
    }
}
