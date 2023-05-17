<?php

namespace SP\Composer\Project\Plugin\Commands;

use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\ReleaseManagement;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SP\Composer\Project\Plugin\Commands\VerifyReleaseCommand
 */
class VerifyReleaseCommandTest extends TestCase
{
    public function testCommand(): void
    {

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $application = new Application();

        $command = new VerifyReleaseCommand();
        $releaseManager = $this->createStub(ReleaseManagement::class);
        $releaseManager->method('verifyRelease');

        $command->setReleaseManagement($releaseManager);

        $application->add($command);

        $command = $application->find('project:verifyRelease');

        $commandTester = new CommandTester($command);

        $success = $commandTester->execute([
            'command' => 'project:verifyRelease'
        ]);

        $this->assertEquals(0, $success);
    }
}
