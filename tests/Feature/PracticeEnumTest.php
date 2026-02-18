<?php

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;

test('practice direction enum has correct values', function () {
    expect(PracticeDirection::SOURCE_TO_TARGET->value)->toBe('source_to_target');
    expect(PracticeDirection::TARGET_TO_SOURCE->value)->toBe('target_to_source');
});

test('practice direction enum can be created from string', function () {
    $direction = PracticeDirection::from('source_to_target');
    expect($direction)->toBe(PracticeDirection::SOURCE_TO_TARGET);

    $direction = PracticeDirection::from('target_to_source');
    expect($direction)->toBe(PracticeDirection::TARGET_TO_SOURCE);
});

test('practice input method enum has correct values', function () {
    expect(PracticeInputMethod::TYPING->value)->toBe('typing');
    expect(PracticeInputMethod::MULTIPLE_CHOICE->value)->toBe('multiple_choice');
});

test('practice input method enum can be created from string', function () {
    $method = PracticeInputMethod::from('typing');
    expect($method)->toBe(PracticeInputMethod::TYPING);

    $method = PracticeInputMethod::from('multiple_choice');
    expect($method)->toBe(PracticeInputMethod::MULTIPLE_CHOICE);
});
