<?php

namespace App\Support;

class DamagedProductName
{
    public const RULE = 'regex:/^(?=.*\p{L})[\p{L}\p{M}\p{N} .,&\x27"()\/+%®™-]+$/u';
}
