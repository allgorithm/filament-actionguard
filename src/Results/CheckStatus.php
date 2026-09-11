<?php

namespace Allgorithm\FilamentActionGuard\Results;

enum CheckStatus: string
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case ERROR = 'error';
}
