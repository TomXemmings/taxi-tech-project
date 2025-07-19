<?php

namespace App\Jobs;

use App\Models\YandexAuthTask;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Dom\Node;
use HeadlessChromium\Page;
use HeadlessChromium\Cookies\Cookie;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class FetchYandexCookies implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public YandexAuthTask $task, public string $login, public string $password, public string $phone)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->task->update(['status' => 'running']);

        try {
            # Start browser
            $browser = (new BrowserFactory(
                env('CHROME_BIN', 'C:\Program Files\Chromium\chromium.exe')
            ))->createBrowser([]);

            # Create page
            $page = $browser->createPage();
            $page->navigate('https://fleet.yandex.ru/')->waitForNavigation();

            $this->waitForSelector($page, 'a')->click();
            $page->waitForReload('load', 100000);

            # Login
            $this->waitForSelector($page, '#passp-field-login')->click();
            $page->keyboard()->typeText($this->login);

            # Password
            $this->waitForSelector($page, 'button[id="passp:sign-in"]')->click();

            $this->waitForSelector($page, '#passp-field-passwd', 25000)->click();
            $page->keyboard()->typeText($this->password);
            $this->waitForSelector($page, 'button[id="passp:sign-in"]')->click();

            # SMS
            $this->waitForSelector($page, 'button[data-t="button:action"')->click();

            # Wait until load DOM
            sleep(10);
            $element = $page->dom()->querySelector('h1');
            $text    = $element->getText();

            # If there is ask other way to auth
            if ($text == 'Введите последние 6&nbsp;цифр входящего номера' or 'Подтвердите кодом из&nbsp;сообщения в&nbsp;Telegram') {
                # Wait until can ask other ways to auth
                sleep(70);
                $this->waitForSelector($page, 'button[data-t="button:pseudo"]')->click();

                $page->evaluate(<<<'JS'
                    (() => {
                        const span = document.querySelector('span.ButtonWithOptions-icon.icon-sms');
                        if (span) {
                            span.closest('button')?.click();
                        }
                    })();
                JS);
            }

            # SMS input
            $smsInput = $this->waitForSelector($page, '#passp-field-phoneCode', 60000);
            $smsCode  = $this->getSms($this->phone);
            $this->task->update([
                'cookies' => $smsCode
            ]);
            $smsInput->click();
            $page->keyboard()->typeText($smsCode)->press('Enter');

            # Wait until page reload
            sleep(10);

            # Save cookies
            $cookies = $page->getAllCookies();

            $cookiesArray = array_map(
                static fn ($cookie) => iterator_to_array($cookie),
                iterator_to_array($cookies)
            );

            $this->task->update([
                'status'  => 'ready',
                'cookies' => $cookiesArray
            ]);
        } catch (\Throwable $e) {
            $this->task->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
        } finally {
            $browser?->close();
        }
    }

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
     * @param  string              $phone
     * @return mixed
     * @throws RequestException
     * @throws ConnectionException
     */
    private function getSms(string $phone)
    {
        $response = Http::acceptJson()
            ->timeout(10)
            ->post(config('services.taxitech.get_sms_url'), [
                'data' => [
                    'auth'  => config('services.taxitech.api_key'),
                    'phone' => $phone,
                ],
            ])
            ->throw()
            ->json('response');

        if (($response['status'] ?? 0) !== 1) {
            $reason = $response['message'] ?? 'Unknown error';
            throw new \RuntimeException("SMS API error: {$reason}");
        }

        return $response['sms_message'];
    }
}
