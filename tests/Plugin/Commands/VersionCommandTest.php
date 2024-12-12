<?php

namespace SP\Composer\Project\Plugin\Commands;

use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\Project;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SP\Composer\Project\Plugin\Commands\VersionCommand
 */
class VersionCommandTest extends TestCase
{
    public function testCommand(): void
    {

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $application = new Application();

        $command = new VersionCommand();
        $project = $this->createStub(Project::class);
        $project->method('isDev')->willReturn(true);
        $project->method('getBranch')->willReturn('main');

        $command->setProject($project);

        $application->add($command);

        $command = $application->find('project:version');

        $commandTester = new CommandTester($command);

        $success = $commandTester->execute([
            'command' => 'project:version'
        ]);

        $this->assertEquals(0, $success);
    }
}
