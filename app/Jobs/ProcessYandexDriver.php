<?php

namespace App\Jobs;

use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Events\DriverCreated;

class ProcessYandexDriver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $drivers;

    public function __construct(array $drivers)
    {
        $this->drivers = $drivers;
    }

    public function handle()
    {
        if (empty($this->drivers)) {
            Log::info('ProcessYandexDriver: Нет данных для обработки.');
            return;
        }

        $existingYandexIds = Driver::pluck('yandex_id')->toArray();
        $toInsert = [];

        foreach ($this->drivers as $driver) {
            $yandexId = $driver['driver_profile']['id'] ?? null;

            if (!$yandexId) {
                Log::warning('Пропущен водитель без yandex_id', ['driver' => $driver]);
                continue;
            }

            if (in_array($yandexId, $existingYandexIds)) {
                continue;
            }

            $toInsert[] = [
                'yandex_id'  => $yandexId,
                'name'       => $driver['driver_profile']['first_name'] ?? 'Неизвестно',
                'surname'    => $driver['driver_profile']['last_name'] ?? 'Неизвестно',
                'phone'      => json_encode($driver['driver_profile']['phones'] ?? []),
                'balance'    => $driver['account']['balance'] ?? 0.0,
                'car'        => json_encode($driver['car'] ?? []),
                'active'     => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($toInsert)) {
            Driver::insert($toInsert);
            Log::info('Создано новых водителей: ' . count($toInsert));

            foreach ($toInsert as $data) {
                if ($driver = Driver::where('yandex_id', $data['yandex_id'])->first()) {
                    event(new DriverCreated($driver));
                }
            }
        } else {
            Log::info('ProcessYandexDriver: Новых водителей нет.');
        }
    }
}
