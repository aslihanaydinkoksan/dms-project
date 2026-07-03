<?php

namespace App\Contracts\AI;

interface LLMProviderInterface
{
    /**
     * Kullanıcının sorusunu, sistemin bulduğu yetkilendirilmiş doküman metinleri ışığında yanıtlar.
     * Model bağımsız olarak, yapay zekaya bir soru sormamızı ve vektör veritabanından bulduğumuz 
     * doküman metinlerini (context) modele beslememizi sağlayan sözleşmedir.
     *
     * @param string $question Kullanıcının sorduğu soru
     * @param array<string> $context Vektör araması sonucunda bulunan, kullanıcının okuma yetkisi olan metin parçaları
     * @return string Yapay zekanın ürettiği nihai cevap
     */
    public function askQuestion(string $question, array $context): string;
}