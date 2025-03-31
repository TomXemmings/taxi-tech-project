<?php

namespace App\Console\Commands;

use App\Services\AmoCrmAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Driver;

class ClearAmoCrmAndDrivers extends Command
{
    protected $signature = 'app:clear-amocrm-drivers';
    protected $description = 'Удаляет все лиды в amoCRM и очищает базу drivers';

    public function handle()
    {
        $this->info('Начинаем очистку...');

        $this->clearAmoCrmLeads();

        $this->clearDriversTable();

        $this->info('Очистка завершена.');
    }

    private function clearAmoCrmLeads()
    {
        $amoCrmAuthService = app(AmoCrmAuthService::class);
        $accessToken = $amoCrmAuthService->getAccessToken();

        $apiUrl = 'https://tomxemmings.amocrm.ru/api/v4/leads';
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ];

        $response = Http::withHeaders($headers)->get($apiUrl, ['limit' => 250]);
        if (!$response->successful()) {
            $this->error('Ошибка получения лидов из amoCRM: ' . $response->body());
            return;
        }

        $leads = $response->json()['_embedded']['leads'] ?? [];
        if (empty($leads)) {
            $this->info('Лидов в amoCRM не найдено.');
            return;
        }

        $leadIds = array_column($leads, 'id');
        $deleteResponse = Http::withHeaders($headers)->delete($apiUrl, $leadIds);

        if ($deleteResponse->successful()) {
            $this->info('Лиды в amoCRM успешно удалены.');
        } else {
            $this->error('Ошибка удаления лидов: ' . $deleteResponse->body());
        }
    }

    private function clearDriversTable()
    {
        Driver::truncate();
        $this->info('Все записи в таблице drivers удалены.');
    }
}
