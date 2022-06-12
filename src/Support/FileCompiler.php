<?php

namespace BladeCLI\Support;

use Illuminate\View\Compilers\BladeCompiler;

class FileCompiler extends BladeCompiler
{
    /**
     * Array of opening and closing tags for raw echos.
     *
     * @var string[]
     */
    protected $rawTags = ['{{', '}}'];

    /**
     * Compile Blade statements that start with "@" to be shifted
     * to the start of the line should they be nested/have leading
     * whitespace which is problematic for files that have semantical/spacing
     * requirements, such as yaml files.
     *
     * PHP tags may leave behind unwanted whitespace:
     *
     * @see https://www.php.net/manual/en/language.basic-syntax.phptags.php
     *
     * "If a file contains only PHP code, it is preferable to omit the PHP closing tag at the end of the file.
     * This prevents accidental whitespace or new lines being added after the PHP closing tag, which may cause
     * unwanted effects because PHP will start output buffering when there is no intention from the programmer
     * to send any output at that point in the script."
     *
     *
     *
     * @param  string  $value
     * @return string
     */
    protected function compileStatements($value)
    {
        $keywords = implode('|', [
            'foreach',
            'endforeach',
            'if',
            'elseif',
            'else',
            'endif',
            'forelse',
            'endforelse',
            'for',
            'endfor',
            'while',
            'endwhile',
        ]);

        $value = preg_replace("/\\s+\@($keywords)/", "\n@$1", $value);

        return parent::compileStatements($value);
    }

    /**
     * Determine if the given view is expired.
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        // ensures that compiler compiles the file always
        return true;
    }
}
