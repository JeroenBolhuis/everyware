<?php

use App\Models\ContactInformationSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('encrypts and decrypts contact fields transparently', function () {
    $submission = new ContactInformationSubmission([
        'name' => 'Jamie Jansen',
        'email' => 'jamie@example.com',
        'phone' => '+31612345678',
    ]);

    expect($submission->getRawOriginal('name'))->not->toBe('Jamie Jansen');
    expect($submission->getRawOriginal('email'))->not->toBe('jamie@example.com');
    expect($submission->getRawOriginal('phone'))->not->toBe('+31612345678');

    expect($submission->name)->toBe('Jamie Jansen');
    expect($submission->email)->toBe('jamie@example.com');
    expect($submission->phone)->toBe('+31612345678');
});

it('stores contact submissions on the personal database connection', function () {
    $submission = new ContactInformationSubmission();

    expect($submission->getConnectionName())->toBe('personal');
});

it('stores empty contact values as null', function () {
    $submission = new ContactInformationSubmission([
        'name' => '',
        'email' => null,
        'phone' => '   ',
    ]);

    expect($submission->getRawOriginal('name'))->toBeNull();
    expect($submission->getRawOriginal('email'))->toBeNull();
    expect($submission->getRawOriginal('phone'))->toBeNull();
});

it('returns null when legacy contact data cannot be decrypted', function () {
    $submission = new ContactInformationSubmission();
    $submission->setRawAttributes([
        'name' => 'plain-text',
        'email' => 'also-plain-text',
        'phone' => null,
    ]);

    expect($submission->name)->toBeNull();
    expect($submission->email)->toBeNull();
    expect($submission->phone)->toBeNull();
});
