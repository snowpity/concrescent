<?php

namespace App\Lib\Hook;

use App\Config\Module\Cloudflare as CloudflareConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class CloudflareApi
{
    private HttpClientInterface $client;

    public function __construct(
        private CloudflareConfig $cloudflareConfig,
        private LoggerInterface $loggerCloudflare,
        ?HttpClientInterface $client = null,
    ) {
        $this->client = $client ?? HttpClient::create();
    }

    private function runPurge(array $files): void
    {
        try {
            $bearer = $this->cloudflareConfig->bearerToken;
            $zoneId = $this->cloudflareConfig->purge?->zoneId;

            if (empty($files) || !$bearer || !$zoneId) {
                return;
            }

            $response = $this->client->request(
                'POST',
                "https://api.cloudflare.com/client/v4/zones/$zoneId/purge_cache",
                [
                    'headers' => [
                        'authorization' => "Bearer $bearer",
                    ],
                    'json' => [
                        'files' => $files,
                    ],
                ]
            );

            $this->loggerCloudflare->info(
                'Purging Cloudflare zone {zoneId} for URLs {files} resulted in ' . $response->getContent(),
                ['sub' => 'cloudflare', 'zoneId' => $zoneId, 'files' => $files]
            );
        } catch (\Throwable $e) {
            $this->loggerCloudflare->error(
                'Failed to execute Cloudflare purge : '. $e->getMessage(),
                ['sub' => 'cloudflare']
            );
        }
    }

    public function purgeSponsors(): void
    {
        $this->runPurge($this->cloudflareConfig->purge?->sponsorFiles ?? []);
    }

    public function purgeSchedule(): void
    {
        $this->runPurge($this->cloudflareConfig->purge?->scheduleFiles ?? []);
    }
}
