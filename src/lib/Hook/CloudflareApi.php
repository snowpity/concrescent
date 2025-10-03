<?php

namespace App\Lib\Hook;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class CloudflareApi
{
    private HttpClientInterface $client;

    public function __construct(
        private LoggerInterface $loggerCloudflare,
        ?HttpClientInterface $client = null
    ) {
        $this->client = $client ?? HttpClient::create();
    }

    private function runPurge(?array $files = null): void
    {
        try {
            global $cm_config;

            $bearer = $cm_config['cloudflare']['bearer_token'] ?? null;
            $zoneId = $cm_config['cloudflare']['purge']['zone_id'] ?? null;

            if ($bearer === null || empty($files) || $zoneId === null) {
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
        global $cm_config;
        $this->runPurge(
            $cm_config['cloudflare']['purge']['sponsor_files'] ??
            $cm_config['cloudflare']['purge']['files'] ??
            null
        );
    }

    public function purgeSchedule(): void
    {
        global $cm_config;
        $this->runPurge(
            $cm_config['cloudflare']['purge']['schedule_files'] ??
            null
        );
    }
}
