<?php


/** 
 * Bu dosya; sistemin hangi yapay zeka modelini kullanacağını, 
 * metinlerin kaçar kelimelik/tokenlık parçalara bölüneceğini ve 
 * vektör veritabanı bağlantı ayarlarını tek bir merkezden yönetmemizi sağlar. 
 * Kodun hiçbir yerinde API key veya model ismi hard-coded kalmayacak.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Varsayılan Sağlayıcılar (Strategy Pattern Altyapısı)
    |--------------------------------------------------------------------------
    | Sistemin şu anda hangi LLM (Büyük Dil Modeli) ve Embedding 
    | sağlayıcısını kullandığını belirler. İleride 'azure' veya 'ollama' eklenebilir.
    */
    'default' => [
        'llm' => env('AI_DEFAULT_LLM', 'google'), 
        'embedding' => env('AI_DEFAULT_EMBEDDING', 'google'), 
        'vector_db' => env('AI_DEFAULT_VECTOR_DB', 'qdrant'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metin Parçalama (Chunking) Ayarları
    |--------------------------------------------------------------------------
    | Dokümanlar vektörleştirilmeden önce bu ayarlara göre parçalara bölünür.
    | Overlap (kesişim), cümlenin ortadan bölünmesi durumunda anlam bütünlüğünün
    | kaybolmamasını sağlar.
    */
    'chunking' => [
        'max_tokens' => env('AI_CHUNK_MAX_TOKENS', 1000), // Her bir parça maksimum kaç token olacak
        'overlap'    => env('AI_CHUNK_OVERLAP', 200),     // Parçalar arası kesişim token sayısı
    ],

    /*
    |--------------------------------------------------------------------------
    | Sağlayıcı (Provider) Ayarları
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'models' => [
                'completion' => env('OPENAI_COMPLETION_MODEL', 'gpt-4-turbo'),
                'embedding'  => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            ],
            'timeout' => env('OPENAI_TIMEOUT', 30),
        ],
        'google' => [
            'api_key' => env('GOOGLE_GEMINI_API_KEY'),
            'models' => [
                'completion' => env('GOOGLE_COMPLETION_MODEL', 'gemini-1.5-flash'),
                'embedding'  => env('GOOGLE_EMBEDDING_MODEL', 'text-embedding-004'),
            ],
            'timeout' => env('GOOGLE_TIMEOUT', 30),
        ],
        // Yarın Claude, Azure veya Local Ollama eklemek istersen buraya tanımlayacağız.
    ],

    /*
    |--------------------------------------------------------------------------
    | Vektör Veritabanı Ayarları
    |--------------------------------------------------------------------------
    */
    'vector_dbs' => [
        'qdrant' => [
            'url'        => env('QDRANT_URL', 'http://localhost:6333'),
            'api_key'    => env('QDRANT_API_KEY', null),
            'collection' => env('QDRANT_COLLECTION', 'dms_documents'),
        ],
    ],
];