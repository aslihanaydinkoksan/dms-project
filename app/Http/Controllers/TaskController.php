<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTaskRequest;
use App\Services\TaskService;
use App\Models\ProcessTemplate;
use App\Models\User;

class TaskController extends Controller
{
    protected TaskService $taskService;

    // Dependency Injection (SOLID: Bağımlılık Enjeksiyonu)
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }
    /**
     * BPM OPERASYON MERKEZİ: Dinamik Kanban Tahtası (Index)
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
                'currentView' => 'kanban'
            ]);
        }

        $templateId = $request->query('template_id', $templates->first()->id);
        $selectedTemplate = ProcessTemplate::findOrFail($templateId);

        // Kullanıcının tercih ettiği görünüm (kanban veya calendar)
        $currentView = $request->query('view', 'kanban');

        // Kanban İçin Aşamalar ve Veriler
        $stages = \App\Models\ProcessStage::where('process_template_id', $selectedTemplate->id)
            ->with(['tasks' => function ($query) {
                $query->with(['creator', 'users'])
                    ->where(function ($q) {
                        // Aktif ve Onay Bekleyenleri her zaman getir
                        $q->whereIn('status', ['active', 'pending_closure_approval'])
                            // Tamamlananları ise sadece SON 14 GÜN içinde güncellenmişse getir (Performans Kalkanı)
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
                foreach ($stage->tasks as $task) {

                    // Dinamik "Bitiş" tarihini şablondaki date tipindeki ilk alandan bul
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
                        'start' => $task->created_at->format('Y-m-d'), // Başlangıç = İşi açtığı gün
                        'end' => $endDate ? \Carbon\Carbon::parse($endDate)->addDay()->format('Y-m-d') : null, // Bitiş = JSON'daki dinamik tarih
                        'backgroundColor' => $stage->color ?? '#3b82f6', // Kanban sütununun rengiyle %100 uyum!
                        'borderColor' => $stage->color ?? '#3b82f6',
                        'textColor' => '#ffffff',
                        'url' => route('tasks.show', $task->id), // Tıklanınca direk detaya gider
                    ];
                }
            }
        }

        return view('tasks.index', compact('templates', 'selectedTemplate', 'stages', 'currentView', 'calendarEvents'));
    }
    /**
     * GÖREV DETAY EKRANI (Arşivden veya Kanban'dan tıklanınca açılır)
     */
    public function show(\App\Models\Task $task)
    {
        $task->load(['template.department', 'creator', 'users', 'stage']);

        // En yeni log en üstte olacak şekilde çekiyoruz
        $logs = $task->logs()->with('user')->latest()->get();

        return view('tasks.show', compact('task', 'logs'));
    }
    /**
     * AJAX ENDPOINT: Kartı sürükleyip bıraktığımızda veritabanını günceller
     */
    public function updateStage(Request $request, \App\Models\Task $task): \Illuminate\Http\JsonResponse
    {
        // Güvenlik: Gelen aşama (sütun) ID'si gerçekten mevcut mu?
        $validated = $request->validate([
            'current_stage_id' => 'required|exists:process_stages,id'
        ]);

        try {
            // Zırh: Kullanıcı kartı başka bir sürecin sütununa fırlatmaya çalışırsa engelle
            $targetStage = \App\Models\ProcessStage::findOrFail($validated['current_stage_id']);
            if ($targetStage->process_template_id !== $task->process_template_id) {
                return response()->json(['success' => false, 'message' => 'Geçersiz süreç aşaması eşleşmesi!'], 400);
            }

            // Veritabanını sessizce (Observer'ları tetiklemeden veya loglayarak) güncelle
            $task->update([
                'current_stage_id' => $targetStage->id
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

        // Kullanıcı önceden bir şablon seçerek URL'den (?template_id=3) geldiyse:
        $selectedTemplate = null;
        if ($request->filled('template_id')) {
            $selectedTemplate = ProcessTemplate::with('department')->find($request->query('template_id'));
        }

        return view('tasks.create', compact('templates', 'selectedTemplate'));
    }

    /**
     * Dinamik İş Kaydı (Fat Controller Yasak!)
     */
    public function store(StoreTaskRequest $request)
    {
        // 1. Zeki Validasyon'dan geçen tertemiz veriyi al
        $validated = $request->validated();

        // 2. İşi tamamen Servis Uzmanına devret
        $task = $this->taskService->createTask($validated, $request->user());

        // 3. Başarılı dönüş (Sonra Kanban board'a yönlendirilecek)
        return redirect()->route('tasks.index')
            ->with('success', 'İş başarıyla başlatıldı ve proje ekibi atandı.');
    }

    /**
     * AJAX (Tom Select): Yeni iş açarken hızlıca Ad-Hoc ekip üyesi arama API'si
     */
    public function searchUsers(Request $request)
    {
        $search = $request->query('q');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->where('is_active', true)
            ->with('department:id,name') // N+1 için Eager Load
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
    /**
     * AJAX (Fetch API): Şablon seçildiğinde dinamik alanlarını ve ZORUNLU GRUBU döndürür
     */
    public function getTemplateFields(int $id)
    {
        $template = ProcessTemplate::with(['mandatoryGroup.members.department'])->findOrFail($id);

        // Zorunlu Grubu ve Üyelerini JSON Formatına Hazırla
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
     */
    public function archive(Request $request)
    {
        $templates = ProcessTemplate::orderBy('name')->get();

        // Sadece 'completed' olanları (Kapananları) çekiyoruz
        $query = \App\Models\Task::with(['template.department', 'creator', 'users'])
            ->where('status', 'completed');

        // Şablona göre filtreleme yapıldıysa
        if ($request->filled('template_id')) {
            $query->where('process_template_id', $request->query('template_id'));
        }

        // Sayfalama (Pagination) ile N+1'siz veri çekimi
        $tasks = $query->latest('updated_at')->paginate(15)->withQueryString();

        return view('tasks.archive', compact('tasks', 'templates'));
    }
}
