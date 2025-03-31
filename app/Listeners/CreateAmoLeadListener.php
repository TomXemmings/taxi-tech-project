<?php

namespace App\Listeners;

use AmoCRM\Models\LeadModel;
use App\Events\DriverCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Services\AmoCrmAuthService;

class CreateAmoLeadListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DriverCreated $event)
    {
        $driver = $event->driver;

        $apiClient = new AmoCRMApiClient(
            config('services.amocrm.client_id'),
            config('services.amocrm.client_secret'),
            config('services.amocrm.redirect_uri')
        );

        $amoCrmAuthService = app(AmoCrmAuthService::class);
        $accessToken = $amoCrmAuthService->getAccessToken();


        if ($accessToken) {
            $accessToken = unserialize($accessToken);
        }

        if (!$accessToken) {
            Log::error('Ошибка: Нет токена для amoCRM');
            return;
        }
        $apiClient->setAccessToken($accessToken)->setAccountBaseDomain(config('services.amocrm.subdomain') . '.amocrm.ru');

        $lead = new LeadModel();
        $lead->setName("Новый водитель: {$driver->name}")
            ->setPrice(0);

        $leadsService = $apiClient->leads();
        try {
            $createdLead = $leadsService->addOne($lead);
            $leadId      = $createdLead->getId();

            if ($leadId) {
                $driver->lead_id = $leadId;
                $driver->save();
            } else {
                Log::warning("Лид создан, но ID не получен.");
            }
        } catch (\Exception $e) {
            Log::error("Ошибка создания лида в amoCRM: " . $e->getMessage());
        }

    }

}
