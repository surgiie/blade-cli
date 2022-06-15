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
     * to the start of the line should they have leading whitespace
     * which is problematic for files that have semantical/spacing
     * requirements, such as yaml files.
     *
     * PHP tags may leave behind unwanted whitespace:
     *
     * @see https://www.php.net/manual/en/language.basic-syntax.phptags.php
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

        // move @ directives that are nested to start of line
        // this will preserve nesting once file is rendered.
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
