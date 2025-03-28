<?php

namespace App\Http\Controllers;

use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AmoCrmAuthController extends Controller
{
    public function redirectToAmoCRM()
    {
        $apiClient = new AmoCRMApiClient(
            config('services.amocrm.client_id'),
            config('services.amocrm.client_secret'),
            config('services.amocrm.redirect_uri')
        );

        $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
            'state' => bin2hex(random_bytes(16))
        ]);

        return redirect($authorizationUrl);
    }

    public function handleCallback(Request $request)
    {
        if ($request->has('code')) {
            $apiClient = new AmoCRMApiClient(
                config('services.amocrm.client_id'),
                config('services.amocrm.client_secret'),
                config('services.amocrm.redirect_uri')
            );

            $apiClient->setAccountBaseDomain(config('services.amocrm.subdomain') . '.amocrm.ru');


            $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($request->get('code'));

            Session::put('amocrm_token', $accessToken->getToken());
            cache()->put('amocrm_token', serialize($accessToken), now()->addHours(23));

            return response()->json(['message' => 'Авторизация успешна!']);
        }

        return response()->json(['error' => 'Ошибка авторизации!'], 400);
    }
}
