<?php

namespace SP\Composer\Project\Plugin\Commands;

use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\ReleaseManagement;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SP\Composer\Project\Plugin\Commands\ReleaseCommand
 */
class ReleaseCommandTest extends TestCase
{
    public function testCommand(): void
    {

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $application = new Application();

        $command = new ReleaseCommand();
        $releaseManager = $this->createStub(ReleaseManagement::class);
        $releaseManager->method('release')->willReturn('1.0.0');

        $command->setReleaseManagement($releaseManager);

        $application->add($command);

        $command = $application->find('project:release');

        $commandTester = new CommandTester($command);

        $success = $commandTester->execute([
            'command' => 'project:release'
        ]);

        $this->assertEquals(0, $success);
    }
}
