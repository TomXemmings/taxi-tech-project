<?php

namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Illuminate\Support\Facades\Cache;

class AmoCrmAuthService
{
    protected string $tokenKey = 'amocrm_token';

    public function getAccessToken(): string
    {
        if (Cache::has($this->tokenKey)) {
            return Cache::get($this->tokenKey);
        }

        return $this->refreshAccessToken();
    }

    protected function refreshAccessToken(): string
    {
        $cachedToken = Cache::get($this->tokenKey);

        if (!$cachedToken) {
            throw new \Exception('Нет токена AmoCRM. Требуется новая авторизация.');
        }

        $accessToken = unserialize($cachedToken);

        if (!$accessToken->hasExpired()) {
            return $accessToken->getToken();
        }

        $apiClient = new AmoCRMApiClient(
            config('services.amocrm.client_id'),
            config('services.amocrm.client_secret'),
            config('services.amocrm.redirect_uri')
        );

        $apiClient->setAccountBaseDomain(config('services.amocrm.subdomain') . '.amocrm.ru');

        try {
            $newAccessToken = $apiClient->getOAuthClient()->getAccessTokenByRefreshToken($accessToken);

            Cache::put($this->tokenKey, serialize($newAccessToken), now()->addSeconds($newAccessToken->getExpires() - 60));

            return $newAccessToken->getToken();
        } catch (AmoCRMException $e) {
            throw new \Exception('Ошибка обновления токена AmoCRM: ' . $e->getMessage());
        }
    }
}
