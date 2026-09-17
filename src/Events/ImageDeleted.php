<?php

declare(strict_types=1);

namespace Webfox\BlockEditor\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ImageDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $path,
        public readonly string $disk = 'public',
    ) {}
}
