<?php

namespace Surgiie\BladeCLI\Support;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Surgiie\BladeCLI\Support\Exceptions\DuplicateDataException;
use Symfony\Component\Console\Input\InputOption;

class OptionsParser
{
    /**
     * Parsing mode for parsing options to pass to symfony's input binding.
     */
    public const REGISTRATION_MODE = 1;

    /**
     * Parsing mode for parsing options with their values. Mostly useful for testing.
     */
    public const VALUE_MODE = 2;

    /**
     * The options to parse.
     */
    protected array $options = [];

    /**
     * Construct new instance.
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * Set the options to parse.
     */
    public function setOptions(array $options): static
    {
        $this->options = array_filter($options);

        return $this;
    }

    /**
     * Parse a token for an --option or --option=value format.
     */
    protected function parseOption(string $token): array
    {
        // match for a --option or --option=value string.
        preg_match("/--([^=]+)=?(.*)/", $token, $match);

        return $match;
    }

    /**
     * Parse the set options.
     */
    public function parse(int $mode = 1): array
    {
        if (! in_array($mode, [static::REGISTRATION_MODE, static::VALUE_MODE])) {
            throw new InvalidArgumentException("Invalid parsing mode given");
        }

        $options = [];

        foreach ($this->options as $token) {
            $match = $this->parseOption($token);

            if (! $match) {
                throw new InvalidArgumentException("Encountered invalid '$token' as it is not --option or --option=value format.");
            }

            $name = $match[1];
            $value = $match[2] ?? false;

            $optionExists = array_key_exists($name, $options);

            if ($value && $optionExists && $mode == static::REGISTRATION_MODE) {
                $options[$name] = InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY;
            } elseif ($value && $optionExists && $mode == static::VALUE_MODE) {
                $options[$name] = Arr::wrap($options[$name]);
                $options[$name][] = $value;
            } elseif ($value) {
                $value = $mode == static::REGISTRATION_MODE ? InputOption::VALUE_REQUIRED : $value;
                $options[$name] = $value;
            } elseif (! $optionExists) {
                $value = $mode == static::REGISTRATION_MODE ? InputOption::VALUE_NONE : true;
                $options[$name] = $value;
            } else {
                throw new DuplicateDataException("The '$name' option has already been provided.");
            }
        }

        return $options;
    }
}
