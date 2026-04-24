<?php

declare(strict_types=1);

return [
    'accepted' => 'The :attribute must be accepted.',
    'array' => 'The :attribute must be an array.',
    'boolean' => 'The :attribute field must be true or false.',
    'date' => 'The :attribute is not a valid date.',
    'email' => 'The :attribute must be a valid email address.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'image' => 'The :attribute must be an image.',
    'integer' => 'The :attribute must be an integer.',
    'max' => [
        'array' => 'The :attribute must not have more than :max items.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string' => 'The :attribute must not be greater than :max characters.',
    ],
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'attributes' => [
        'apartment_id' => 'apartment',
        'assigned_to' => 'assigned manager',
        'attachments' => 'attachments',
        'attachments.*' => 'attachment',
        'body' => 'comment',
        'building_id' => 'building',
        'content' => 'content',
        'description' => 'description',
        'email' => 'email',
        'locale' => 'language',
        'password' => 'password',
        'priority' => 'priority',
        'published_at' => 'publish at',
        'remember' => 'remember me',
        'status' => 'status',
        'status_note' => 'status note',
        'title' => 'title',
    ],
];