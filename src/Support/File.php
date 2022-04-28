<?php

namespace BladeCLI\Support;

use Illuminate\View\View;

class File extends View
{
    /**
     * Get the evaluated contents of the compiled file.
     *
     * @return string
     */
    protected function getContents()
    {
        // we have a bit of custom compiling to do on the
        // compiled file that blade has generated.
        $compiler = $this->engine->getCompiler();

        $compiler->compile($this->path);

        $compiledContents = file_get_contents(
            $compiledPath = $compiler->getCompiledPath($this->path)
        );

        $compiledContents = $this->modifyCompiledContent($compiledContents);

        file_put_contents($compiledPath, $compiledContents);

        // return the new results
        return $this->engine->get($this->path, $this->gatherData());
    }

    /**
     * Modify compiled contents to modify php tags to previous lines.
     *
     * @param string $contents
     * @return string
     */
    protected function modifyCompiledContent(string $contents)
    {
        // moving open/end tags to the end of the previous line allow nesting to be preserved which
        // is important for files like yaml or files that have semantical/nesting formatting requirements.
        $contents = preg_replace(
            '/\\s+\<\?php (\$__currentLoopData|endforeach)(.*) \?\>/',
            "<?php $1$2 ?>\n",
            $contents
        );

        $contents = preg_replace("/\\s+\<\?php (if|endif)(.*) \?\>/", "<?php $1$2 ?>\n", $contents);

        return $contents;
    }
}
