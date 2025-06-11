<?php

// src/Service/ArbitratorStorage.php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class ArbitratorStorage
{
    private string $storagePath;

    public function __construct(string $projectDir)
    {
        $this->storagePath = $projectDir . '/var/storage/arbitrators.json';
        $this->ensureStorageFileExists();
    }

    private function ensureStorageFileExists(): void
    {
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->storagePath)) {
            $filesystem->dumpFile($this->storagePath, '{}');
        }
    }

    public function addArbitrator(string $username, string $password, int $tournamentId): void
    {
        $arbitrators = $this->getAllArbitrators();
        $arbitrators[$username] = [
            'password' => $password,
            'tournamentId' => $tournamentId,
        ];
        file_put_contents($this->storagePath, json_encode($arbitrators, JSON_PRETTY_PRINT));
    }

    public function getAllArbitrators(): array
    {
        $content = file_get_contents($this->storagePath);
        return $content ? json_decode($content, true) : [];
    }

    public function findArbitrator(string $username): ?array
    {
        $arbitrators = $this->getAllArbitrators();
        return $arbitrators[$username] ?? null;
    }
}
