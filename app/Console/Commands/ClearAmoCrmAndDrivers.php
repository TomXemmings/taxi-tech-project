<?php

namespace App\Console\Commands;

use App\Models\Driver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\AmoCrmAuthService;

class ClearAmoCrmAndDrivers extends Command
{
    protected $signature = 'app:clear-amocrm-drivers';
    protected $description = 'Удаляет все лиды из amoCRM и очищает таблицу drivers';

    public function handle()
    {
        $this->info('Начинаем очистку amoCRM и таблицы drivers...');

        $this->clearAmoCrmLeads();
        $this->clearDriversTable();

        $this->info('Очистка завершена.');
    }

    private function clearAmoCrmLeads()
    {
        $amoCrmAuthService = app(AmoCrmAuthService::class);
        $accessToken = $amoCrmAuthService->getAccessToken();

        if (!$accessToken) {
            $this->error('Токен доступа не найден.');
            return;
        }

        $accessToken = unserialize($accessToken);

        $domain = config('services.amocrm.subdomain') . '.amocrm.ru';
        $baseUrl = "https://{$domain}/api/v4/leads";
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken->getToken(),
            'Content-Type'  => 'application/json',
        ];

        $page = 1;
        $limit = 250;
        $totalDeleted = 0;

        do {
            $response = Http::withHeaders($headers)->get($baseUrl, [
                'limit' => $limit,
                'page'  => $page,
            ]);

            if (!$response->successful()) {
                $this->error('Ошибка при получении лидов: ' . $response->body());
                break;
            }

            $leads = $response->json()['_embedded']['leads'] ?? [];

            if (empty($leads)) {
                break;
            }

            $payload = collect($leads)->map(fn ($lead) => [
                'id' => $lead['id'],
                '_delete' => true,
            ])->values()->toArray();

            $deleteResponse = Http::withHeaders($headers)->patch($baseUrl, $payload);

            if ($deleteResponse->successful()) {
                $this->info("Удалено " . count($payload) . " лидов на странице {$page}.");
                $totalDeleted += count($payload);
            } else {
                $this->error("Ошибка удаления лидов на странице {$page}: " . $deleteResponse->body());
                break;
            }

            $page++;
        } while (count($leads) === $limit);

        $this->info("Всего удалено лидов: {$totalDeleted}");
    }

    private function clearDriversTable()
    {
        Driver::truncate();
        $this->info('Таблица drivers очищена.');
    }
}
