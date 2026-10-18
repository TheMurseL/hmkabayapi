<?php

declare(strict_types=1);

final class MessageRepository
{
    public function create(array $data): bool
    {
        $fields = ['name', 'email', 'phone', 'message', 'request_token', 'district', 'building_type', 'area', 'stage', 'source'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $data[$field] ?? null;
        }
        try {
            db()->prepare('INSERT INTO messages (' . implode(',', $fields) . ') VALUES (:' . implode(',:', $fields) . ')')->execute($values);
            return true;
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062 && !empty($values['request_token'])) {
                return false;
            }
            throw $exception;
        }
    }
}
