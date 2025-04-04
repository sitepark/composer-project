<?php

namespace SP\Composer\Project\Plugin\Commands;

use Composer\Console\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\ReleaseManagement;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(StartHotfixCommand::class)]
class StartHotfixCommandTest extends TestCase
{
    public function testCommand(): void
    {

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $application = new Application();

        $command = new StartHotfixCommand();
        $releaseManager = $this->createStub(ReleaseManagement::class);
        $releaseManager->method('startHotfix')->willReturn('1.0.1');

        $command->setReleaseManagement($releaseManager);

        $application->add($command);

        $command = $application->find('project:startHotfix');

        $commandTester = new CommandTester($command);

        $success = $commandTester->execute([
            'command' => 'project:startHotfix',
            'tag' => '1.0.0'
        ]);

        $this->assertEquals(0, $success);
    }
}
