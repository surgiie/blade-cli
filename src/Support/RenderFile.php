<?php
namespace BladeCLI\Support;


use Illuminate\View\View as BaseView;

class RenderFile extends BaseView
{
    /**
     * Get the evaluated contents of the view.
     *
     * @return string
     */
    protected function getContents()
    {
        // we have a bit of custom compiling to do on the
        // compiled file that blade has generated.
        $compiler = $this->engine->getCompiler();

        $compiler->compile($this->path);

        $compiledContents = file_get_contents($compiledPath = $compiler->getCompiledPath($this->path));

        $compiledContents = $this->modifyCompiledContent($compiledContents);

        file_put_contents($compiledPath, $compiledContents);

        // return the new results
        return $this->engine->get($this->path, $this->gatherData());
    }

    /**
     * Modify compiled contents.
     *
     * @param string $contents
     * @return void
     */
    protected function modifyCompiledContent(string $contents)
    {
        // Nested <?php tags that have closing ?\> tags will tab inner content by extra space, this requires devs to put @if or @foreach directives at the
        // start of the file Which makes template files harder to read if the content of these directives serves semantical meaning (e.g yaml files)
        // This functon will allow certain directives to be nested by replacing nested spaces with empty strings
        // (ie implictly place compile directive tags at start of line for the dev's convenience of not having to do this themselves).
        $contents = preg_replace('/\\s+\<\?php (\$__currentLoopData|endforeach)(.*) \?\>/', "<?php $1$2 ?>\n", $contents);
        $contents = preg_replace('/\\s+\<\?php (if|endif)(.*) \?\>/', "<?php $1$2 ?>\n", $contents);
        return $contents;
    }


}