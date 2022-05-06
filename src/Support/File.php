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

        // so we need to force recompile so that
        // our custom compiler compiles with our custom logic
        $compiler->compile($this->path);

        // save new results
        $compiledContents = file_get_contents(
            $compiledPath = $compiler->getCompiledPath($this->path)
        );

        file_put_contents($compiledPath, $compiledContents);

        // return the new results
        return $this->engine->get($this->path, $this->gatherData());
    }


}
