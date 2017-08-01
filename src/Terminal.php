<?php

namespace Spatie\PhpUnitWatcher;

use Clue\React\Stdio\Stdio;
use Spatie\PhpUnitWatcher\Screens\Screen;
use Symfony\Component\Console\Formatter\OutputFormatter;

class Terminal
{
    /** @var \Clue\React\Stdio\Stdio */
    protected $io;

    /** @var \Spatie\PhpUnitWatcher\Screens\Screen */
    protected $previousScreen = null;

    /** @var \Spatie\PhpUnitWatcher\Screens\Screen */
    protected $currentScreen = null;

    protected $loop;

    public function __construct(Stdio $loop)
    {
        $this->io = new Stdio($io);

        $this->loop = $loop;
    }

    public function on(string $eventName, callable $callable)
    {
        $this->io->on($eventName, function ($line) use ($callable) {
            $callable($line);
        });
    }

    public function on2(string $eventName, callable $callable)
    {
        $stdin = new ReadableResourceStream(STDIN, $this->loop);

        $stream = new ControlCodeParser($stdin);

        $stream->on('data', function ($chunk) {
            var_dump($chunk);
        });
    }

    public function emptyLine()
    {
        $this->write('');

        return $this;
    }

    public function comment(string $message)
    {
        $this->write($message, 'comment');

        return $this;
    }

    public function write(string $message = '', $level = null)
    {
        if ($level != '') {
            $message = "<{$level}>$message</{$level}>";
        }

        $formattedMessage = (new OutputFormatter(true))->format($message);

        $this->io->writeln($formattedMessage);

        return $this;
    }

    public function displayScreen(Screen $screen, $clearScreen = true)
    {
        $this->previousScreen = $this->currentScreen;

        $this->currentScreen = $screen;

        $screen
            ->useTerminal($this)
            ->clearPrompt()
            ->removeAllListeners()
            ->registerListeners();

        if ($clearScreen) {
            $screen->clear();
        }

        $screen->draw();
    }

    public function goBack()
    {
        if (is_null($this->previousScreen)) {
            return;
        }

        $this->currentScreen = $this->previousScreen;

        return $this;
    }

    public function refreshScreen()
    {
        if (is_null($this->currentScreen)) {
            return;
        }

        $this->displayScreen($this->currentScreen);
    }

    public function isDisplayingScreen(string $screenClassName): bool
    {
        if (is_null($this->currentScreen)) {
            return false;
        }

        return $screenClassName === get_class($this->currentScreen);
    }

    public function removeAllListeners()
    {
        $this->io->removeAllListeners();

        return $this;
    }

    public function prompt(string $prompt)
    {
        $this->io->getReadline()->setPrompt($prompt);

        return $this;
    }

    public function clearPrompt()
    {
        $this->io->getReadline()->setPrompt('');

        return $this;
    }
}
