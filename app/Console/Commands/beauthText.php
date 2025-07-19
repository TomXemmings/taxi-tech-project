<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use HeadlessChromium\Cookies\Cookie;
use Illuminate\Support\Facades\Storage;

class beauthText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:beauth-text';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cookies = [

        ];

        $pretty = array_map(
            static function (Cookie $c) {
                return [
                    'name'    => $c->getName(),
                    'value'   => $c->getValue(),
                    'domain'  => $c->getDomain(),
                    'path'    => $c->getPath(),
                    'expires' => $c->getExpires() > 0
                        ? date('Y-m-d H:i:s', $c->getExpires())
                        : 'session',                    // кука до конца сессии
                    'httpOnly' => $c->isHttpOnly(),
                    'secure'   => $c->isSecure(),
                ];
            },
            iterator_to_array($cookies)              // разворачиваем итератор в обычный массив
        );

        /* 2. Кодируем красиво */
        $json = json_encode(
            $pretty,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        /* 3. Сохраняем или выводим */
        Storage::disk('local')->put('yandex_cookies_pretty.json', $json);
        $this->info($json);
    }
}
