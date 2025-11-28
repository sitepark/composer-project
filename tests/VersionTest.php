<?php

namespace SP\Composer\Project;

use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\CoversClass;

#[CoversClass(Version::class)]
class VersionTest extends TestCase
{
    public function testEscapeVersionIdentifierForMaven(): void
    {
        $this->assertEquals("tölles_123_Feature", Version::escapeVersionIdentifierForMaven("!!!tölles-#123_Feature!!!"));
    }
}
