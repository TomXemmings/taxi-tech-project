<?php

namespace App\Http\Controllers;

use App\Jobs\FetchYandexCookies;
use App\Models\YandexAuthTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class YandexController extends Controller
{
    /**
     * Auth on fleet.yandex
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function auth(Request $request)
    {
        $data = $request->validate([
            'data.login'    => 'required|string',
            'data.password' => 'required|string',
            'data.phone'    => 'required|string'
        ]);

        $task = YandexAuthTask::create();
        FetchYandexCookies::dispatch(
            $task,
            $data['data']['login'],
            $data['data']['password'],
            $data['data']['phone']
        );

        return response()->json([
            'response' => [
                'uuid' => $task->id
            ]], 202);
    }

    /**
     * Get cookies
     *
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getCookies(Request $request)
    {
        $data = $request->validate([
            'data.uuid' => 'required|string',
        ]);

        $task = YandexAuthTask::findOrFail($data['data']['uuid']);

        return match ($task->status) {
            'ready'  => response()->json([
                'response' => [
                    'cookies' => collect($task->cookies ?? [])
                        ->map(fn ($cookie) =>
                        Arr::only($cookie, ['name', 'value', 'domain', 'path'])
                        )
                        ->values(),
                ],
            ]),
            'failed' => response()->json([
                'error'  => $task->error,
                'status' => 'failed',
            ], 500),
            default  => response()->json([
                'status' => $task->status,
            ], 202),
        };
    }
}
