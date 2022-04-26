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
     * Whether or not to fail on tokens that are not
     * in --option or --option=value format.
     *
     * @var boolean
     */
    protected bool $failOnInvalidFormatTokens = true;
    /**
     * Construct new instance.
     *
     * @param array $options
     */
    public function __construct(array $options, bool $failOnInvalidFormatTokens = true)
    {
        $this->setOptions($options);
        $this->failOnInvalidFormatTokens = $failOnInvalidFormatTokens;
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

            if(!$match && $this->failOnInvalidFormatTokens){
                throw new InvalidArgumentException("Ignored encountered '$token' as it is not --option or --option=value format.");
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