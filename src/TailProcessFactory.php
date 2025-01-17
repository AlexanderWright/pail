<?php

namespace Laravel\Pail;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Pail\Printers\CliPrinter;
use Symfony\Component\Console\Output\OutputInterface;

class TailProcessFactory
{
    /**
     * Creates a new instance of the tail process factory.
     */
    public function run(TailedFile $file, OutputInterface $output, string $basePath, TailOptions $options): void
    {
        $printer = new CliPrinter($output, $basePath);

        Process::timeout(3600)
            ->tty(false)
            ->run(
                $this->command($file),
                function (string $type, string $buffer) use ($options, $printer): void {
                    /** @var array<int, string> $lines */
                    $lines = Str::of($buffer)
                        ->explode("\n")
                        ->filter(fn (string $line): bool => $line !== '')
                        ->when(
                            is_string($options->filter),
                            fn (Collection $lines) => $lines->filter(
                                fn (string $line): bool => str_contains($line, $options->filter) // @phpstan-ignore-line
                            )
                        )->values();

                    foreach ($lines as $line) {
                        $printer->print($options, $line);
                    }
                }
            );
    }

    /**
     * Returns the raw command.
     */
    protected function command(TailedFile $file): string
    {
        return '\\tail -F "'.$file->__toString().'"';
    }
}
