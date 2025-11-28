<?php

declare(strict_types=1);

namespace SP\Composer\Project;

final class Version
{
    public static function escapeVersionIdentifierForMaven(string $identifier): string
    {
        $invalidCharsRegex = '/[^a-zA-Z0-9äöüÄÖÜß]+/';
        $leadingUnderscoreRegex = '/(^_+)/';
        $trailingUnderscoreRegex = '/(_+$)/';

        $escape = preg_replace($invalidCharsRegex, '_', $identifier);
        $escape = preg_replace($leadingUnderscoreRegex, '', $escape);
        $escape = preg_replace($trailingUnderscoreRegex, '', $escape);

        return trim($escape);
    }
}
