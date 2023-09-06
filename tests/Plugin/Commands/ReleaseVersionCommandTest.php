<?php

namespace SP\Composer\Project\Plugin\Commands;

use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\Project;
use SP\Composer\Project\ReleaseManagement;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \SP\Composer\Project\Plugin\Commands\ReleaseVersionCommand
 */
class ReleaseVersionCommandTest extends TestCase
{
    public function testCommand(): void
    {

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $application = new Application();

        $command = new ReleaseVersionCommand();
        $project = $this->createStub(Project::class);
        $project->method('getNextReleaseVersion')->willReturn('1.0.0');

        $command->setProject($project);

        $application->add($command);

        $command = $application->find('project:releaseVersion');

        $commandTester = new CommandTester($command);

        $success = $commandTester->execute([
            'command' => 'project:releaseVersion'
        ]);

        $this->assertEquals(0, $success);
    }
}
