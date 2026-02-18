<?php

namespace App\Enums;

enum PracticeDirection: string
{
    case SOURCE_TO_TARGET = 'source_to_target';
    case TARGET_TO_SOURCE = 'target_to_source';
}
