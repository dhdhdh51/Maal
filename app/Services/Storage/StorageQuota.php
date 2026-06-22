<?php

namespace App\Services\Storage;

use App\Models\Video;
use App\Models\VideoFile;

class StorageQuota
{
    /**
     * Configured maximum quota in bytes (0 = unlimited).
     */
    public function maxBytes(): int
    {
        $gb = (int) (setting('max_storage_quota_gb', config('streaming.uploads.max_quota_gb', 0)));

        return $gb > 0 ? $gb * (1024 ** 3) : 0;
    }

    /**
     * Total bytes currently consumed by originals + renditions.
     */
    public function usedBytes(): int
    {
        return (int) Video::sum('file_size') + (int) VideoFile::sum('file_size');
    }

    public function remainingBytes(): ?int
    {
        $max = $this->maxBytes();

        if ($max === 0) {
            return null; // unlimited
        }

        return max(0, $max - $this->usedBytes());
    }

    /**
     * Whether an upload of the given size would fit within the quota.
     */
    public function canFit(int $bytes): bool
    {
        $remaining = $this->remainingBytes();

        return $remaining === null || $bytes <= $remaining;
    }
}
