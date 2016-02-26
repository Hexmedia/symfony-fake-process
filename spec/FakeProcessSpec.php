<?php

namespace spec\Hexmedia\Symfony\FakeProcess;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Input\ArrayInput;

class FakeProcessSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith("crontab -l", "/tmp", null, null, 1000, array());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Hexmedia\Symfony\FakeProcess\FakeProcess');
    }

    function it_runs()
    {
        $cron = "* * * * * ./test.sh";
        $exitCode = 0;

        $this->addCommand(
            "crontab -l",
            function () use ($cron) {
                return $cron;
            },
            $exitCode
        )->shouldReturn(null);

        $this->run()->shouldReturn($exitCode);

        $this->getOutput()->shouldReturn($cron);
        $this->getExitCode()->shouldReturn($exitCode);
    }

    function it_throws_when_no_command_mocked()
    {
        $this->shouldThrow('Exception')->duringRun();
        $this->getOutput()->shouldReturn(null);
        $this->getExitCode()->shouldReturn(null);
    }
}
