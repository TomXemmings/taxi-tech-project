<?php

namespace App\Console\Commands;

use App\Jobs\ProcessYandexDriver;
use Illuminate\Console\Command;
use App\Jobs\ProcessYandexDriversBatch;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

class FetchYandexDrivers extends Command
{
    protected $signature   = 'app:fetch-yandex-drivers';
    protected $description = 'Получение списка водителей из API Яндекса и отправка их в очередь';

    public function handle()
    {
        $offset = 0;
        $limit = 500;
        $totalDrivers = 0;

        do {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'X-API-Key'       => config('services.yandex.api_key'),
                    'Accept-Language' => 'ru',
                    'X-Client-ID'     => config('services.yandex.client_id'),
                ])
                ->post(config('services.yandex.api_url'), [
                    'query'      => [
                        'park' => ['id' => config('services.yandex.park_id')]
                    ],
                    'fields'     => [
                        'account'        => ['balance'],
                        'car'            => [
                            'color',
                            'model',
                            'brand',
                            'year'
                        ],
                        'current_status' => ['status'],
                        'driver_profile' => [
                            'id',
                            'first_name',
                            'last_name',
                            'phones'
                        ],
                        'updated_at'     => true
                    ],
                    'sort_order' => [['direction' => 'asc', 'field' => 'driver_profile.created_date']],
                    'limit'      => $limit,
                    'offset'     => $offset
                ]);

            if ($response->successful()) {
                $drivers = $response->json()['driver_profiles'] ?? [];
                $count = count($drivers);
                $totalDrivers += $count;

                if ($count > 0) {
                    ProcessYandexDriver::dispatch($drivers);
                }

                $offset += $limit;
            } else {
                $this->error('Ошибка при получении данных: ' . $response->body());
                break;
            }

        } while ($count > 0);

        $this->info("Всего водителей отправлено в очередь: {$totalDrivers}");
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyMinute();
    }
}
