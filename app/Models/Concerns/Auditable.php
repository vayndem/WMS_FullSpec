<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::registerModelEvent($event, function ($model) use ($event) {
                $hidden = array_flip(array_merge($model->getHidden(), ['password', 'remember_token']));
                $old = $event === 'updated' ? array_diff_key($model->getOriginal(), $hidden) : null;
                $new = $event === 'deleted' ? null : array_diff_key($model->getAttributes(), $hidden);

                AuditLog::create([
                    'auditable_type' => $model::class,
                    'auditable_id' => $model->getKey(),
                    'event' => $event,
                    'old_values' => $old,
                    'new_values' => $new,
                    'metadata' => ['route' => request()?->route()?->getName()],
                    'user_id' => auth()->id(),
                    'ip_address' => request()?->ip(),
                ]);
            });
        }
    }
}
