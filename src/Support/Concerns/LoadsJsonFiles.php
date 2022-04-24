<?php
namespace BladeCLI\Support\Concerns;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use JsonException;

trait LoadsJsonFiles
{
    /**
     * Get a more helpful json error.
     *
     * @param string $error
     * @return string
     */
    protected function getJsonParseError($error)
    {
        switch ($error) {
            case JSON_ERROR_DEPTH:
                return " - Maximum stack depth exceeded";
            case JSON_ERROR_STATE_MISMATCH:
                return " - Underflow or the modes mismatch";
            case JSON_ERROR_CTRL_CHAR:
                return " - Unexpected control character found";
            case JSON_ERROR_SYNTAX:
                return " - Syntax error, malformed JSON";
            case JSON_ERROR_UTF8:
                return " - Malformed UTF-8 characters, possibly incorrectly encoded";
            default:
                return " - Unknown error";
        }
    }
    /**
     * Loads a json file and returns parsed data as an array.
     *
     * @param string $path
     * @return array
     */
    protected function loadJsonFile(string $path)
    {
        if(!file_exists($path)){
            throw new FileNotFoundException("The $path json file does not exist.");
        }
        $data = json_decode(file_get_contents($path), JSON_OBJECT_AS_ARRAY);

        $error = json_last_error();

        if (JSON_ERROR_NONE !== $error) {
            throw new JsonException($this->getJsonParseError(json_last_error()));
        }

        return $data;
    }
}
