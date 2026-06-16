<?php

return [
    'modules' => [

        // 1. DOKÜMAN YÖNETİMİ MODÜLÜ
        'documents' => [
            'label' => 'Doküman Yönetimi',
            'model' => \App\Models\Document::class,
            'date_column' => 'created_at',
            'groupings' => [
                'status' => [
                    'label' => 'Statüye Göre',
                    'col' => 'status'
                ],
                'type' => [
                    'label' => 'Belge Tipine Göre',
                    'col' => 'document_type_id',
                    'relation' => 'documentType',
                    'display_col' => 'name'
                ]
            ]
        ],

        // 2. GÖREV VE SÜREÇLER MODÜLÜ
        'tasks' => [
            'label' => 'Süreç ve Görevler',
            'model' => \App\Models\Task::class,
            'date_column' => 'created_at',
            'groupings' => [
                'status' => [
                    'label' => 'Görev Durumuna Göre',
                    'col' => 'status'
                ],
                'template' => [
                    'label' => 'Süreç Şablonuna Göre',
                    'col' => 'process_template_id',
                    'relation' => 'template',
                    'display_col' => 'name'
                ]
            ]
        ]

    ]
];
