<?php
namespace BladeCLI\Support;


use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class OptionsParser
{

    /**
     * The options to parse.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Parse options for registration.
     */
    const REGISTRATION_MODE = 1;

    /**
     * Parse options with values.
     */
    const VALUE_MODE = 2;

    /**
     * Construct new instance.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * Set options to parse.
     *
     * @param array $options
     * @var static
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Parse a token for an --option or --option=value format.
     *
     * @param string $token
     * @return array
     */
    protected function parseOption(string $token)
    {
        // match for a --option or --option=value string.
        preg_match("/--([^=]+)=?(.*)/", $token, $match);

        return $match;
    }

    /**
     * Escapes a token through escapeshellarg if it contains unsafe chars.
     *
     * @see Symfony\Component\Console\Input\Input@escapeToken.
     */
    public function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }

    /**
     * Parse the set options.
     *
     *
     * @param int $mode - Return options with value or just options with Input modes for registration with symfony input binding.
     * @throws \InvalidArgumentException
     * @return array
     */
    public function parse(int $mode =1)
    {
        if(!in_array($mode, [static::REGISTRATION_MODE, static::VALUE_MODE])){
            throw new InvalidArgumentException("Invalid parsing mode given");
        }

        $options = [];

        // parse options to be used as template/var data.
        foreach ($this->options as $token) {

            $match = $this->parseOption($token);

            if(!$match){
                throw new InvalidArgumentException("Encountered invalid '$token' as it is not --option or --option=value format.");
            }

            $name = $match[1];
            $value = $match[2] ?? false;

            $optionExists = array_key_exists($name, $options);

            if($value && $optionExists){
                $value = $mode == static::REGISTRATION_MODE ? InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY : $this->escapeToken($value);
                $options[$name] = $value;
            }else if($value){
                $value = $mode == static::REGISTRATION_MODE ? InputOption::VALUE_REQUIRED: $this->escapeToken($value);
                $options[$name] = $value;
            }else if(!$optionExists){
                $value = $mode == static::REGISTRATION_MODE ? InputOption::VALUE_NONE: true;
                $options[$name] = $value;
            }else{
                throw new InvalidArgumentException("The '$name' option has already been provided.");
            }
        }

        return $options;
    }
}