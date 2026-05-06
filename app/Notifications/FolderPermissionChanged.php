<?php

namespace App\Notifications;

use App\Models\Folder;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class FolderPermissionChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected Folder $folder;
    protected string $level;
    protected User $assigner;

    public function __construct(Folder $folder, string $level, User $assigner)
    {
        $this->folder = $folder;
        $this->level = $this->translateLevel($level);
        $this->assigner = $assigner;
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $template = SystemSetting::getByKey(
            'mail_template_folder_permission',
            "Sayın {user_name}, {assigner_name} tarafından size '{folder_name}' klasörü için '{permission_level}' yetkisi tanımlanmıştır."
        );

        $message = str_replace(
            ['{user_name}', '{folder_name}', '{permission_level}', '{assigner_name}'],
            [$notifiable->name, $this->folder->name, $this->level, $this->assigner->name],
            $template
        );

        return (new MailMessage)
            ->subject('Yeni Klasör Yetkisi Tanımlandı')
            ->greeting('Merhaba ' . $notifiable->name . ',')
            ->line($message)
            ->action('Klasörü Görüntüle', route('folders.show', $this->folder->id))
            ->line('İyi çalışmalar.');
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'folder_id' => $this->folder->id,
            'folder_name' => $this->folder->name,
            'message' => "{$this->assigner->name} size '{$this->folder->name}' klasörü için '{$this->level}' yetkisi verdi.",
            'action_url' => route('folders.show', $this->folder->id),
            'type' => 'permission_update'
        ];
    }

    private function translateLevel(string $level): string
    {
        return match ($level) {
            'read', 'can_view' => 'Görüntüleme',
            'upload', 'can_upload' => 'Yükleme',
            'manage', 'can_manage' => 'Yönetim',
            'can_create_subfolder' => 'Alt Klasör Oluşturma',
            default => $level
        };
    }
}
