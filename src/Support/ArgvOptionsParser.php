<?php
namespace BladeCLI\Support;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class ArgvOptionsParser
{

    /**
     * The options to parse.
     *
     * @var array
     */
    protected array $options = [];

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
     * Parse the set options.
     *
     * @return array
     */
    public function parse()
    {
        $options = [];

        // parse options to be used as template/var data.
        foreach ($this->options as $token) {

            $match = $this->parseOption($token);

            if(!$match){
                trigger_error("Ignored encountered '$token' as it is not --option or --option=value format.");
                continue;
            }

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