<?php
namespace BladeCLI\Support;


use Throwable;
use Illuminate\View\Engines\CompilerEngine as Compiler;

class CompilerEngine extends Compiler
{
    /**
     * Overwritten to not ltrim outbutput buffer, this makes includes preserve indentation as is.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    protected function evaluatePath($path, $data)
    {
        $obLevel = ob_get_level();

        ob_start();

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try {
            $this->files->getRequire($path, $data);
        } catch (Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ob_get_clean();
    }
}