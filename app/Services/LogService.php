<?php

namespace App\Services;

use App\Models\Log;

class LogService
{
    /**
     * Create a log entry.
     *
     * @param int|null $userId
     * @param string $action
     * @param string|null $resourceType
     * @param int|null $resourceId
     * @param array|null $data
     * @return Log
     */
    public function create(
        ?int $userId,
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $data = null
    ): Log {
        return Log::create([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'data' => $data ? json_encode($data) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a model action.
     *
     * @param string $action
     * @param object $model
     * @param array|null $data
     * @return Log
     */
    public function logModelAction(string $action, object $model, ?array $data = null): Log
    {
        return $this->create(
            auth()->id(),
            $action,
            get_class($model),
            $model->id,
            $data
        );
    }
}

