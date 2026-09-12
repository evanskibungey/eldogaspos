<?php

namespace App\Console\Commands;

use App\Services\Sms\PhoneNumber;
use App\Services\Sms\TalkSasaClient;
use Illuminate\Console\Command;

/**
 * Sends one real message and prints the gateway's raw reply.
 *
 * The published TalkSasa docs sit behind a login, so the exact response
 * envelope and the accepted recipient format were inferred rather than read.
 * This command is how those two are confirmed against the live account: run it
 * once, and correct config/services.php or TalkSasaClient from what it prints.
 */
class SmsTest extends Command
{
    protected $signature = 'sms:test
                            {phone : Recipient, any format (0712..., +254712..., 254712...)}
                            {--message= : Override the default test text}';

    protected $description = 'Send one live test SMS and print the gateway response verbatim';

    public function handle(TalkSasaClient $client): int
    {
        $raw = (string) $this->argument('phone');
        $msisdn = PhoneNumber::normalise($raw);

        if ($msisdn === null) {
            $this->error("'{$raw}' is not a valid Kenyan mobile number.");

            return self::FAILURE;
        }

        $driver = config('services.talksasa.driver');
        $plusPrefix = (bool) config('services.talksasa.plus_prefix');
        $recipient = $plusPrefix ? '+' . $msisdn : $msisdn;

        $this->newLine();
        $this->line('  <options=bold>TalkSasa live test</>');
        $this->line('  ' . str_repeat('-', 60));
        $this->line('  driver:    <fg=cyan>' . $driver . '</>');
        $this->line('  base url:  <fg=cyan>' . config('services.talksasa.base_url') . '</>');
        $this->line('  endpoint:  <fg=cyan>' . config('services.talksasa.endpoints.send') . '</>');
        $this->line('  sender id: <fg=cyan>' . config('services.talksasa.sender_id') . '</>');
        $this->line('  token set: <fg=cyan>' . (empty(config('services.talksasa.token')) ? 'NO' : 'yes') . '</>');
        $this->line('  recipient: <fg=cyan>' . $recipient . '</> (normalised from ' . $raw . ')');
        $this->newLine();

        if ($driver === 'log') {
            $this->warn('  SMS_DRIVER is "log" - nothing will actually be sent.');
            $this->warn('  Set SMS_DRIVER=talksasa in .env to test the live gateway.');

            return self::FAILURE;
        }

        if (empty(config('services.talksasa.token'))) {
            $this->error('  TALKSASA_TOKEN is not set in .env.');

            return self::FAILURE;
        }

        if (!$this->confirm('  Send a real SMS to ' . $recipient . '? This will be billed.', false)) {
            $this->line('  Aborted.');

            return self::SUCCESS;
        }

        $message = (string) ($this->option('message')
            ?: 'EldoGas test message. If you received this, SMS is configured correctly.');

        $response = $client->send($recipient, $message);

        $this->newLine();
        $this->line('  <options=bold>Response</>');
        $this->line('  ' . str_repeat('-', 60));
        $this->line('  http status: ' . ($response->statusCode ?? 'n/a'));
        $this->line('  interpreted: ' . ($response->successful ? '<fg=green>success</>' : '<fg=red>failure</>'));

        if ($response->uid !== null) {
            $this->line('  message uid: ' . $response->uid);
        }

        if ($response->message !== null) {
            $this->line('  error:       <fg=red>' . $response->message . '</>');
        }

        $this->newLine();
        $this->line('  <options=bold>Raw body</> (use this to correct the client if needed)');
        $this->line('  ' . str_repeat('-', 60));
        $this->line(json_encode($response->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->newLine();

        if ($response->failed()) {
            $this->warn('  If this failed on an invalid recipient, try flipping');
            $this->warn('  TALKSASA_PLUS_PREFIX in .env and run again.');

            return self::FAILURE;
        }

        $this->info('  Sent. Confirm the handset actually received it.');

        return self::SUCCESS;
    }
}
