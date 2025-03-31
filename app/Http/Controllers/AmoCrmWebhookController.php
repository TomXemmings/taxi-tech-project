<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;

class AmoCrmWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Получен вебхук от amoCRM', $request->all());

        $leadData = $request->input('leads.update.0');

        if ($leadData) {
            $leadId = $leadData['id'] ?? null;
            $status = $leadData['status_id'] ?? null;

            if ($leadId && $status) {
                if ($status == 142) {
                    $updated = Driver::where('lead_id', $leadId)->update(['active' => 1]);

                    if ($updated) {
                        Log::info("Водитель с lead_id {$leadId} активирован.");
                    } else {
                        Log::warning("Водитель с lead_id {$leadId} не найден.");
                    }
                } else {
                    Log::info("Лид {$leadId} не в нужном статусе ({$status}).");
                }
            } else {
                Log::warning("Не удалось получить lead_id или status_id.");
            }
        } else {
            Log::warning("Вебхук не содержит нужных данных.");
        }

        return response()->json(['status' => 'ok']);
    }
}
