<?php

namespace App\Console\Commands;

use HeadlessChromium\Page;
use Illuminate\Console\Command;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Dom\Node;
use Illuminate\Support\Facades\Storage;
use HeadlessChromium\Exception\ElementNotFoundException;

class YandexFetchCookies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:yandex-fetch-cookies {--headful : If need to see Chrome window}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Getting DOM elements
     *
     * @param  Page   $page
     * @param  string $selector
     * @param  int    $timeoutMs
     * @return Node
     */
    private function waitForSelector(\HeadlessChromium\Page $page, string $selector, int $timeoutMs = 30000): Node
    {
        $start   = microtime(true);
        $timeout = $timeoutMs / 1000;

        while (true) {
            $node = $page->dom()->querySelector($selector);
            if ($node !== null) {
                return $node;
            }

            if (microtime(true) - $start > $timeout) {
                throw new \RuntimeException("DOM not found with selector: {$selector} in {$timeoutMs} ms");
            }
            usleep(300_000);
        }
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        # Start browser
        $browser = (new BrowserFactory(
            env('CHROME_BIN', 'C:\Program Files\Chromium\chromium.exe')
        ))->createBrowser([
            'headless'   => ! $this->option('headful'),
            'noSandbox'  => true,
            'windowSize' => [1280, 800],
        ]);

        try {
            # Create page
            $page = $browser->createPage();
            $page->navigate('https://fleet.yandex.ru/')->waitForNavigation();
            $this->info('Страница открыта');
            $this->waitForSelector($page, 'a')->click();
            $page->waitForReload('load', 30000);
            $this->info('Страница авторизации открыта');

            # Login
            $this->waitForSelector($page, '#passp-field-login')->click();
            $page->keyboard()->typeText(env('YANDEX_LOGIN'));

            # Password
            $this->waitForSelector($page, 'button[id="passp:sign-in"]')->click();
            $this->info('огин введён и кнопка «Войти» нажата');
            $this->waitForSelector($page, '#passp-field-passwd', 25000)->click();
            $page->keyboard()->typeText(env('YANDEX_PASSWORD'));
            $this->waitForSelector($page, 'button[id="passp:sign-in"]')->click();
            $this->info('Пароль введён');

            # SMS
            $this->waitForSelector($page, 'button[data-t="button:action"')->click();
            $smsInput = $this->waitForSelector($page, '#passp-field-phoneCode', 60000);
            $smsCode  = $this->ask('Введите SMS-код Яндекса');
            $smsInput->click();
            $page->keyboard()->typeText($smsCode)->press('Enter');
            $this->info('SMS-код отправлен, ждём подтверждение…');
            $page->waitForReload('load', 30000);
            $this->info('Открыт https://fleet.yandex.ru/');

            # Save cookies
            $cookies = $page->getAllCookies();
            Storage::disk('local')->put(
                'yandex_cookies.json',
                json_encode($cookies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->info('Cookies сохранены: storage/app/yandex_cookies.json');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return self::FAILURE;

        } finally {
            $browser?->close();
        }
    }
}


