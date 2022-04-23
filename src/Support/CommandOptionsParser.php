<?php
namespace BladeCLI\Support;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class CommandOptionsParser
{

    /**
     * The options to parse.
     *
     * @var array
     */
    protected array $options = [];


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
     * Parse a token for an input option or raise exception if
     * it does not meet --option or --option=value format.
     *
     * @param string $token
     * @throws InvalidArgumentException
     * @return array
     */
    protected function parseOptionOrFail(string $token)
    {
        // match for a --option or --option=value string.
        preg_match("/--([^=]+)=?(.*)/", $token, $match);

        if (!$match) {
            throw new InvalidArgumentException("Invalid or unaccepted option: $token");
        }

        return $match;
    }

    /**
     * Parse the set options.
     *
     * @return array
     */
    public function parse()
    {
        $options = [];

        // parse options to be used as template/var data.
        foreach ($this->options as $token) {
            $match = $this->parseOptionOrFail($token);

            $name = $match[1];
            $value = $match[2] ?? false;

            $optionExists = array_key_exists($name, $options);

            if($value && $optionExists){
                $options[$name] = InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY;
            }else if($value){
                $options[$name] = InputOption::VALUE_REQUIRED;
            }else if(!$optionExists){
                $options[$name] = InputOption::VALUE_NONE;
            }else{
                throw new InvalidArgumentException("The '$name' option has already been provided.");
            }
        }

        return $options;
    }
}