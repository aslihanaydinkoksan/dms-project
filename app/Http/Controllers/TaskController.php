<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTaskRequest;
use App\Services\TaskService;
use App\Models\ProcessTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class TaskController extends Controller
{
    use AuthorizesRequests;
    protected TaskService $taskService;

    // Dependency Injection (SOLID: Bağımlılık Enjeksiyonu)
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * BPM OPERASYON MERKEZİ: Dinamik Kanban Tahtası (Index)
     * ENTEGRE: Local Scopes ile Gelişmiş Filtreleme ve ABAC Veri Gizleme (Data Stealth)
     */
    public function index(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();

        if ($templates->isEmpty()) {
            return view('tasks.index', [
                'templates' => collect(),
                'selectedTemplate' => null,
                'stages' => collect(),
                'calendarEvents' => [],
                'currentView' => 'kanban',
                'filters' => []
            ]);
        }

        $templateId = $request->query('template_id', $templates->first()->id);
        $selectedTemplate = ProcessTemplate::findOrFail($templateId);
        $currentView = $request->query('view', 'kanban');

        $filters = $request->only(['search', 'date_start', 'date_end']);

        // ====================================================================
        // KANBAN VERİ ÇEKİMİ (GÜVENLİK ZIRHI ENJEKTE EDİLDİ)
        // ====================================================================
        $stages = \App\Models\ProcessStage::where('process_template_id', $selectedTemplate->id)
            ->with(['tasks' => function ($query) use ($filters) {
                $query->with(['creator', 'users'])
                    ->visibleTo(Auth::user()) // KRİTİK ZIRH: Kullanıcının göremediği süreçler SQL seviyesinde gizlenir
                    ->filter($filters)          // Arama ve Tarih Filtreleme Kalkanı
                    ->where(function ($q) {
                        $q->whereIn('status', ['active', 'pending_closure_approval'])
                            ->orWhere(function ($sq) {
                                $sq->where('status', 'completed')
                                    ->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(14));
                            });
                    })
                    ->orderBy('updated_at', 'desc');
            }])->orderBy('sort_order')->get();

        // AJANDA (CALENDAR) İÇİN DİNAMİK EVENT ÜRETİCİ
        $calendarEvents = [];
        if ($currentView === 'calendar') {
            foreach ($stages as $stage) {
                // Not: $stage->tasks koleksiyonu zaten yukarıdaki 'visibleTo' zırhından 
                // geçtiği için, Ajanda görünümüne de sadece yetkili görevler yansıyacaktır!
                foreach ($stage->tasks as $task) {
                    $endDate = null;
                    if (is_array($selectedTemplate->fields)) {
                        foreach ($selectedTemplate->fields as $field) {
                            if (($field['type'] ?? '') === 'date' && !empty($task->custom_data[$field['name']])) {
                                $endDate = $task->custom_data[$field['name']];
                                break;
                            }
                        }
                    }

                    $calendarEvents[] = [
                        'id' => $task->id,
                        'title' => 'TASK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT) . ' | ' . $task->title,
                        'start' => $task->created_at->format('Y-m-d'),
                        'end' => $endDate ? \Carbon\Carbon::parse($endDate)->addDay()->format('Y-m-d') : null,
                        'backgroundColor' => $stage->color ?? '#3b82f6',
                        'borderColor' => $stage->color ?? '#3b82f6',
                        'textColor' => '#ffffff',
                        'url' => route('tasks.show', $task->id),
                    ];
                }
            }
        }

        return view('tasks.index', compact('templates', 'selectedTemplate', 'stages', 'currentView', 'calendarEvents', 'filters'));
    }

    /**
     * GÖREV DETAY EKRANI (Arşivden veya Kanban'dan tıklanınca açılır)
     */
    public function show(\App\Models\Task $task)
    {
        // YENİ: Policy kalkanı entegre edildi
        $this->authorize('view', $task);

        $task->load(['template.department', 'creator', 'users', 'stage']);
        $logs = $task->logs()->with('user')->latest()->get();

        return view('tasks.show', compact('task', 'logs'));
    }

    /**
     * AJAX ENDPOINT: Kartı sürükleyip bıraktığımızda veritabanını günceller
     */
    public function updateStage(Request $request, \App\Models\Task $task): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'current_stage_id' => 'required|exists:process_stages,id',
            'transition_note'  => 'nullable|string|max:500',
            'attachment'       => 'nullable|file|extensions:pdf,xls,xlsx,jpg,jpeg,udf|max:20480'
        ]);

        // YENİ: Kanban taşıma yetkisi Policy üzerinden kontrol ediliyor
        $this->authorize('move', $task);

        try {
            $targetStage = \App\Models\ProcessStage::findOrFail($validated['current_stage_id']);

            if ($targetStage->process_template_id !== $task->process_template_id) {
                return response()->json(['success' => false, 'message' => 'Geçersiz süreç aşaması eşleşmesi!'], 400);
            }

            $stages = \App\Models\ProcessStage::where('process_template_id', $task->process_template_id)
                ->orderBy('sort_order', 'asc')->pluck('id')->toArray();

            $originalIndex = array_search($task->current_stage_id, $stages);
            $targetIndex = array_search($targetStage->id, $stages);

            if ($originalIndex !== false && $targetIndex !== false && abs($targetIndex - $originalIndex) > 1) {
                return response()->json(['error' => 'Kurumsal iş akışı kuralları gereği aşama atlanamaz. Lütfen süreci adım adım ilerletin.'], 422);
            }

            $oldStageName = $task->stage->name ?? 'Bilinmeyen Aşama';
            $attachmentLogText = '';

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeName = time() . '_' . \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

                $path = $file->storeAs("tasks/attachments/{$task->id}", $safeName, 'local');

                $attachment = \App\Models\TaskAttachment::create([
                    'task_id'          => $task->id,
                    'process_stage_id' => $targetStage->id,
                    'user_id'          => Auth::id(),
                    'file_name'        => $originalName,
                    'file_path'        => $path,
                    'file_size'        => $file->getSize(),
                    'extension'        => $extension,
                ]);

                $fileUrl = route('tasks.attachments.download', $attachment->id);
                $attachmentLogText = "<br><br><span style='color: #0284c7; font-size: 0.85rem; font-weight:600; display:flex; align-items:center; gap:5px;'><i data-lucide='paperclip' style='width:14px;'></i> Ek Belge Yüklendi: <a href='{$fileUrl}' target='_blank' style='color:#2563eb; text-decoration:underline;'>{$originalName}</a></span>";
            }

            $task->update(['current_stage_id' => $targetStage->id]);

            $logDescription = "İşlem yeni aşamaya taşındı: <strong>{$targetStage->name}</strong>.";
            if (!empty($validated['transition_note'])) {
                $logDescription .= "<br><br><span style='color: var(--text-muted); font-style: italic;'>Kullanıcı Notu: " . strip_tags($validated['transition_note']) . "</span>";
            }
            $logDescription .= $attachmentLogText;

            \App\Models\TaskLog::create([
                'task_id'     => $task->id,
                'user_id'     => Auth::id(),
                'action'      => 'stage_transition',
                'description' => $logDescription,
                'old_data'    => ['stage' => $oldStageName],
                'new_data'    => ['stage' => $targetStage->name],
                'ip_address'  => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => "📌 İş başarıyla '{$targetStage->name}' aşamasına taşındı."
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * İş Oluşturma Ekranını Aç (Şablon seçilerek gelinecek)
     */
    public function create(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();
        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = ProcessTemplate::with('department')->find($request->query('template_id'));
        }

        return view('tasks.create', compact('templates', 'selectedTemplate'));
    }

    /**
     * Dinamik İş Kaydı
     */
    public function store(StoreTaskRequest $request)
    {
        // YENİ: Doğrudan Policy katmanına bağladık (Request içindeki kontrole ek olarak güvenlik kalkanı)
        $this->authorize('create', \App\Models\Task::class);

        $validated = $request->validated();
        $task = $this->taskService->createTask($validated, $request->user());

        return redirect()->route('tasks.index')
            ->with('success', 'İş başarıyla başlatıldı ve proje ekibi atandı.');
    }

    public function searchUsers(Request $request)
    {
        $search = $request->query('q');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->where('is_active', true)
            ->with('department:id,name')
            ->limit(15)
            ->get(['id', 'name', 'department_id'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'department' => $user->department ? $user->department->name : 'Birim Yok'
                ];
            });

        return response()->json($users);
    }

    public function getTemplateFields(int $id)
    {
        $template = ProcessTemplate::with(['mandatoryGroup.members.department'])->findOrFail($id);

        $mandatoryGroupData = null;
        if ($template->mandatoryGroup && $template->mandatoryGroup->is_active) {
            $members = $template->mandatoryGroup->members->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'department' => $m->department->name ?? 'Birim Yok',
                    'role' => $m->pivot->role
                ];
            });

            $mandatoryGroupData = [
                'name' => $template->mandatoryGroup->name,
                'allow_ad_hoc' => (bool)$template->allow_ad_hoc_members,
                'member_ids' => $template->mandatoryGroup->members->pluck('id')->toArray(),
                'members' => $members
            ];
        }

        return response()->json([
            'fields' => $template->fields ?? [],
            'mandatory_group' => $mandatoryGroupData
        ]);
    }

    /**
     * BPM ARŞİV MERKEZİ: Tamamlanan İşlerin Listesi
     * ENTEGRE: Local Scopes ile Gelişmiş İzolasyon ve ABAC Veri Gizleme (Data Stealth)
     */
    public function archive(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();

        $query = \App\Models\Task::with(['template.department', 'creator', 'users'])
            ->visibleTo(Auth::user()) 
            ->where('status', 'completed');

        // Şablona göre filtreleme yapıldıysa (Orijinal mantık aynen korunur)
        if ($request->filled('template_id')) {
            $query->where('process_template_id', $request->query('template_id'));
        }

        // Sayfalama (Pagination) ile N+1'siz güvenli veri çekimi
        $tasks = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('tasks.archive', compact('tasks', 'templates'));
    }

    /**
     * Görev Düzenleme Ekranı (Form)
     */
    public function edit(\App\Models\Task $task)
    {
        // YENİ: Hardcoded fonksiyon kaldırıldı, standart Policy kalkanı devrede!
        $this->authorize('update', $task);

        $task->load(['template.mandatoryGroup.members.department', 'users.department']);
        $users = User::where('is_active', true)->with('department:id,name')->orderBy('name')->get();

        $mandatoryUserIds = $task->template->mandatoryGroup ? $task->template->mandatoryGroup->members->pluck('id')->toArray() : [];
        $existingAdHocMembers = $task->users->filter(function ($u) use ($mandatoryUserIds, $task) {
            return !in_array($u->id, $mandatoryUserIds) && $u->id !== $task->creator_id;
        })->values();

        return view('tasks.edit', compact('task', 'users', 'mandatoryUserIds', 'existingAdHocMembers'));
    }

    /**
     * Görev Güncelleme İşlemi (POST/PUT)
     */
    public function update(Request $request, \App\Models\Task $task)
    {
        // YENİ: Hardcoded fonksiyon kaldırıldı, standart Policy kalkanı devrede!
        $this->authorize('update', $task);

        $rules = [
            'title' => 'required|string|max:255',
            'team_members' => 'nullable|array',
            'team_members.*.user_id' => 'required|exists:users,id',
            'team_members.*.role' => 'required|in:manager,member',
            'custom_data' => 'nullable|array',
        ];

        $fields = $task->template->fields ?? [];
        foreach ($fields as $field) {
            $rule = [];
            $rule[] = !empty($field['required']) ? 'required' : 'nullable';

            if ($field['type'] === 'number') {
                $rule[] = 'numeric';
            } elseif ($field['type'] === 'date') {
                $rule[] = 'date';
            } else {
                $rule[] = 'string';
            }

            $rules["custom_data.{$field['name']}"] = implode('|', $rule);
        }

        $validatedData = $request->validate($rules);
        $this->taskService->updateTask($task, $validatedData);

        return redirect()->route('tasks.show', $task->id)->with('success', '✨ Süreç bilgileri başarıyla güncellendi.');
    }

    /**
     * GÜVENLİ DOSYA İNDİRME MERKEZİ (Secure File Serve)
     */
    public function downloadAttachment(\App\Models\TaskAttachment $attachment)
    {
        $task = $attachment->task;

        // YENİ: Karmaşık if/else blokları tamamen kaldırıldı. 
        // Vekalet sistemini tanıyan 'view' yetkisi buraya eklendi.
        $this->authorize('view', $task);

        $filePath = storage_path('app/' . $attachment->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'Dosya sunucuda bulunamadı veya silinmiş.');
        }

        return response()->download($filePath, $attachment->file_name);
    }
}
