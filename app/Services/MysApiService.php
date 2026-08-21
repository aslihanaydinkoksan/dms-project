<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class MysApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // Workflow standartlarındaki config isimlerini kullanıyoruz
        $this->baseUrl = config('services.central_sso.url');
        $this->apiKey = config('services.central_sso.api_key');
    }

    /**
     * Ortak HTTP Client yapısı
     */
    protected function client()
    {
        return Http::withHeaders([
            'X-App-Key' => $this->apiKey,
        ])->baseUrl($this->baseUrl . '/api/internal'); // Endpoint ekini burada birleştiriyoruz
    }

    /**
     * MYS'den departmanları çeker
     */
    public function getDepartments(): array
    {
        $response = $this->client()->get('/departments');

        if ($response->failed()) {
            throw new Exception('MYS sisteminden departmanlar alınamadı. Hata: ' . $response->status());
        }

        return $response->json('departments') ?? [];
    }

    /**
     * MYS'den kullanıcıları çeker
     */
    public function getAllUsers(): array
    {
        $response = $this->client()->get('/users-all');

        if ($response->failed()) {
            throw new Exception('MYS sisteminden kullanıcılar alınamadı. Hata: ' . $response->status());
        }

        return $response->json('users') ?? [];
    }
}
