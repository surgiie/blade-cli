<?php

namespace Surgiie\BladeCLI\Support;

use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\PhpEngine;
use Throwable;

class FileCompilerEngine extends CompilerEngine
{
    /**
     * Overwritten to not ltrim but to rtrim outbutput buffer.
     *
     * This assists with preserving spacing/indentation from compiled file.
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

        return rtrim(ob_get_clean());
    }

    /**
     * Handle a view exception.
     *
     * @param  \Throwable  $e
     * @param  int  $obLevel
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleViewException(Throwable $e, $obLevel)
    {
        $class = get_class($e);

        PhpEngine::handleViewException(new $class($this->getMessage($e)), $obLevel);
    }

    /**
     * Get the exception message for an exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function getMessage(Throwable $e)
    {
        $msg = $e->getMessage();

        return $msg . " (File: " . realpath(end($this->lastCompiled)) . ")";
    }
}
