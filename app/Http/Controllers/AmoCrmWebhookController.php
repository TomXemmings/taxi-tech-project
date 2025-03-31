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

        $leadId = $request->input('leads.status.0.id');
        $status = $request->input('leads.status.0.status_id');

        if ($leadId && $status) {
            if ($status == 142) {
                $updated = Driver::where('lead_id', $leadId)->update(['active' => 1]);

                if ($updated) {
                    Log::info("Водитель с lead_id {$leadId} активирован.");
                } else {
                    Log::warning("Водитель с lead_id {$leadId} не найден.");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

}
