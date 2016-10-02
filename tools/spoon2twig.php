<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * The Spoon2Twig Convert is a command line file converter
 * to rebuild your old templates to new twig compatible templates
 */
class spoon2twig
{
    private $interationNr = 0;
    private $previousTimeStamp = 0;
    private $start = "{# This file is generated by the Spoon2Twig Converter #}\n\n";
    private $webroot;
    private $extension = '.html.twig';
    private $startTime;
    private $errors;
    private $type;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->webroot = __DIR__.'/../';
    }

    /**
     * Start Converter
     *
     * @param  array $argv  A list of arguments from php command line
     */
    public function start(array $argv)
    {
        // OUR INPUT AND REPLACE CODE
        if (!isset($argv[1])) {
            $this->error('no arguments given');

            return;
        }

        // bool
        $force = (isset($argv[2]) && $argv[2] === '-f');
        $this->type['module'] = (isset($argv[2]) && $argv[2] === '-m');
        $this->type['theme'] = (isset($argv[2]) && $argv[2] === '-t');
        $this->type['backend'] = (isset($argv[2]) && $argv[2] === '-b');
        $input = (string) $argv[1];
        $source = $this->getCorrectSourceVersion();

        $path['templates'] = array('/Layout/Templates', '/Layout/Widgets', '/Layout/Templates/Mails');

        if ($input === '-all') {
            $path['base'] = array('Frontend/Themes', 'Backend/Modules', 'Frontend/Modules', 'Frontend');
            $this->convertAllFiles($force, $path);

            return;
        }

        if ($input === '-backend') {
            $path['base'] = array('Backend/Modules');
            $this->convertAllFiles($force, $path, $input);

            return;
        }

        if ($this->type['module']) {
            $input = ucfirst($input);
            $path['base'] = array('Backend/Modules', 'Frontend/Modules');
            if (!is_dir($this->webroot.$source.'Frontend/Modules/'.$input)) {
                $this->error('unknown module folder '.$input);

                return;
            }
            $this->convertAllFiles($force, $path, $input);

            return;
        }

        if ($this->type['theme']) {
            $input = strtolower($input);
            $path['base'] = array('Frontend/Themes');
            if (!is_dir($this->webroot.$source.'Frontend/Themes/'.$input)) {
                $this->error('unknown theme folder '.$input);

                return;
            }
            $this->convertAllFiles($force, $path, $input);

            return;
        }

        if ($this->isFile($input) && $force === true) {
            $this->write($input, $this->ruleParser($this->getFile($input)));

            return;
        }

        if (!file_exists(str_replace('.html.twig', $this->extension, $input))) {
            $this->write($input, $this->ruleParser($this->getFile($input)));

            return;
        }

        $this->error('twig version of ' . $input . ' exists, use the "-f" parameter to overwrite');
    }

    /**
     * Error or notice collector
     *
     * @param  string $message
     */
    public function error($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Displays all Errors or notices
     */
    public function displayErrors()
    {
        if ($this->errors) {
            foreach ($this->errors as $error) {
                echo $error .  PHP_EOL;
            }
        }
    }

    /**
     * Stamps the time it takes from start to finnish
     *
     * @param  int $int how precise you wish to measure
     */
    public function timestamp($int = null)
    {
        return (float) substr(microtime(true) - $this->startTime, 0, (int) $int + 5) * 1000;
    }

    /**
     * Project file converter
     * Will locate ever file in the project and convert in automagicly
     *
     * @param  bool $force allow forced overwrite
     */
    public function convertAllFiles($force, $path, $input = null)
    {
        foreach ($path['base'] as $BPath) {
            $templatePaths = $this->findFiles($BPath, $path, $input);
        }

        if (!empty($templatePaths)) {
            $this->buildFiles($templatePaths, $force);
        }
    }

    /**
     * Find files in given paths
     *
     * @param  string $BPath  pas
     * @param  array  $path   base & themeplate paths
     * @param  string $input  argument
     *
     * @return array        found files
     */
    private function findFiles($BPath, array $path, $input = null)
    {
        $templatePaths = array();
        $source = $this->getCorrectSourceVersion();
        $excludes = array('.', '..', '.DS_Store');
        $possiblePath = $source . $BPath;

        if (is_dir($this->webroot . $possiblePath)) {
            // single module or theme?
            if ($this->type['theme'] || $this->type['module']) {
                $tpls[] = $input;
            } else {
                $tpls = array_diff(scandir($this->webroot . $possiblePath), $excludes);
            }

            // core tpl
            $coreTplPath = $this->webroot .$possiblePath .'/../Core/Layout/Templates';
            if (is_dir($coreTplPath)) {
                $coreTpls = array_diff(scandir($coreTplPath), $excludes);
                if (!empty($coreTpls)) {
                    // append full path
                    foreach ($coreTpls as $coreTpl) {
                        if (strpos($coreTpl, '.html.twig') !== false) {
                            $templatePaths[] = $possiblePath .'/../Core/Layout/Templates/' . $coreTpl;
                        }
                    }
                }
            }

            foreach ($tpls as $tpl) {
                // theme exception
                if ($BPath === 'Frontend/Themes') {
                    $themeModule = $possiblePath . '/' . $tpl . '/Modules';
                    $tplsh = array_diff(scandir($themeModule), $excludes);

                    if (is_array($tplsh)) {
                        foreach ($tplsh as $themeModuleName) {
                            $path['templates'][] = '/Modules/' . $themeModuleName . '/Layout/Templates';
                            $path['templates'][] = '/Modules/' . $themeModuleName . '/Layout/Widgets';
                        }
                    }
                }

                foreach ($path['templates'] as $template) {
                    $possibletpl = $possiblePath . '/' . $tpl . $template;
                    if (is_dir($this->webroot . $possibletpl)) {
                        $tplsz = array_diff(scandir($this->webroot . $possibletpl), $excludes);
                        if (!empty($tplsz)) {
                            // append full path
                            foreach ($tplsz as $tpl_Z) {
                                if (strpos($tpl_Z, '.html.twig') !== false) {
                                    $templatePaths[] = $possibletpl . '/' . $tpl_Z;
                                }
                            }
                        }
                    } else {
                        var_dump($this->webroot . $possibletpl);
                    }
                }
            }

            return $templatePaths;
        }
    }

    /**
     * Builds new Files from a paths array
     *
     * @param  array   $templatePaths paths array
     * @param  bool $force         forced
     */
    private function buildFiles(array $templatePaths, $force = false)
    {
        $excluded = array();
        foreach ($templatePaths as $templatePath) {
            if ($force === true) {
                $this->write($templatePath, $this->ruleParser($this->getFile($templatePath)));
            } else {
                if (!file_exists(str_replace('.html.twig', $this->extension, $templatePath))) {
                    $this->write($templatePath, $this->ruleParser($this->getFile($templatePath)));
                } else {
                    $excluded[] = $templatePath;
                }
            }
        }
        if (!empty($excluded)) {
            $this->error('not all files are converted, use "-f" to overwrite');
        }
    }

    /**
     * Get Correct version looks a the project version
     * to find and return it's source directory
     *
     * @return string returns the correct source dir
     */
    public function getCorrectSourceVersion()
    {
        // checking what version
        $version = $this->getFile('VERSION.md');
        switch (true) {
            case (strpos($version, '3.9.') !== false):
                $source = 'src/';
                break;

            case (strpos($version, '3.8.') !== false):
                $source = 'src/';
                break;

            default:
                $source = 'src/';
                break;
        }

        return $source;
    }

    /**
     * Write saves to content to a new file
     *
     * @param  string $input    file full path
     * @param  string $filedata file content
     */
    public function write($input, $filedata)
    {
        if (empty($this->errors)) {
            // OUR OUTPUT CODE
            $input = str_replace('.html.twig', $this->extension, $input);
            $file = $this->webroot . $input;
            $inputPath = pathinfo($input);

            file_put_contents($file, $this->start.$filedata);
            $time = $this->timestamp(2) - $this->previousTimeStamp;
            $this->previousTimeStamp = $this->timestamp(2);
            echo $inputPath['basename']. ' done in ' . $time . ' milliseconds' . PHP_EOL;
        }
    }

    /**
     * Return the file content of a given file
     *
     * @param  string $input file full path
     *
     * @return string        file content
     */
    public function getFile($input)
    {
        if ($this->isFile($input)) {
            // grab file from command line parameter
            $file = $this->webroot . $input;
            $stream = fopen($file, 'r');
            $filedata = stream_get_contents($stream);
            fclose($stream);

            return $filedata;
        }
    }

    /**
     * File checker
     *
     * @param  string  $file file full path
     *
     * @return bool
     */
    public function isFile($file)
    {
        if (file_exists($file)) {
            return true;
        }
        $this->error('Could not open input file: ' . $this->webroot . $file);

        return false;
    }

    /**
     * preg_replace sprint_f
     * Combines 2 function into one that's more ideal for parsing
     * as it string replaces any found matches with a new given value
     *
     * @param  string $regex    the regex
     * @param  string $format   the replace value
     * @param  string $filedata file content
     *
     * @return string           if successful returns file content with replaced data
     */
    public function pregReplaceSprintf($regex, $format, $filedata, $extra = null)
    {
        preg_match_all($regex, $filedata, $match);

        if (count($match)) {
            $values = array();
            foreach ($match[1] as $value) {
                if ($extra === 'snakeCase') {
                    $value = $this->fromCamelToSnake($value);
                } elseif ($extra === 'comma') {
                    $value = $this->comma($value);
                }
                $values[] = sprintf($format, $value);
            }

            return str_replace($match[0], $values, $filedata);
        }
        $this->error('no match found on the ' . $regex . ' line');
    }

    /**
     * Converts a noun until it's ready
     *
     * @param  string $noun a noun
     *
     * @return string       converted noun
     */
    public function dePluralize($noun)
    {
        $nouns = array(
            'modules' => 'module',
        );

        // shorten
        $new_plur = pathinfo($noun);
        if (isset($new_plur['extension'])) {
            $noun = $new_plur['extension'];
        }

        if (in_array($noun, array_keys($nouns))) {
            $noun = $nouns[$noun];
        } elseif (substr($noun, -2) == 'es') {
            $noun = substr($noun, 0, -2);
        } elseif (substr($noun, -1) == 's') {
            $noun = substr($noun, 0, -1);
        } else {
            $noun = '_itr_'.$this->interationNr;
            ++$this->interationNr;
        }

        return $noun;
    }

    public function fromCamelToSnake($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    public function comma($input)
    {
        return str_replace(':', ',', $input);
    }

    /**
     * Iteration Converter
     *
     * @param  string $filedata file to convert
     *
     * @return string           file in converted form
     */
    public function pregReplaceIterations($filedata)
    {
        preg_match_all('/{iteration:(.*?)}(.*){\/iteration:(.*?)}/si', $filedata, $match);

        if ($match[1]) {
            foreach ($match[1] as $value) {
                $new_val = $this->dePluralize($value);

                $prev_match = $match[0];
                $match[0] = str_replace('{iteration:'.$value.'}', '{% for '. $new_val . ' in ' . $value . '_ %}', $match[0]);
                $match[0] = str_replace('{/iteration:'.$value.'}', '{% endfor %}', $match[0]);
                $match[0] = str_replace($value, $new_val, $match[0]);
                $match[0] = str_replace($new_val.'_', $value, $match[0]);
                $filedata = str_replace($prev_match, $match[0], $filedata);

                return $this->pregReplaceIterations($filedata);
            }
        }

        return $filedata;
    }

    /** STRING CONVERSIONS START HERE **/
    public function ruleParser($filedata)
    {
        // Exceptions
        $filedata = $this->pregReplaceSprintf('/:{\$(.*?)}/ism', ':%s', $filedata);

        // iterations
        $filedata = $this->pregReplaceIterations($filedata);

        // variables
        $filedata = $this->pregReplaceSprintf('/{\$(.*?)\)}/', '{{ %s ) }}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{\$(.*?)}/ism', '{{ %s }}', $filedata);

        // filters
        $filedata = $this->pregReplaceSprintf('/\|date:(.*?)}/', '|spoondate(%s) }', $filedata, 'comma');
        $filedata = $this->pregReplaceSprintf('/\|date:(.*?)}/', '|date(%s) }', $filedata);
        $filedata = $this->pregReplaceSprintf('/\|substring:(.*?)}/', '|slice(%s) }', $filedata, 'comma');
        $filedata = $this->pregReplaceSprintf('/\|sprintf:(.*?)}/', '|format(%s)|raw }', $filedata);
        $filedata = $this->pregReplaceSprintf('/\|usersetting:(.*?)}/', '|usersetting(%s) }', $filedata);
        $filedata = $this->pregReplaceSprintf('/\var|geturlforblock:(.*?)}/', 'geturlforblock(%s) }', $filedata);
        $filedata = $this->pregReplaceSprintf('/\var|getnavigation:(.*?)}/', 'getnavigation(%s)|raw }', $filedata, 'comma');
        $filedata = $this->pregReplaceSprintf('/\var|getsubnavigation:(.*?)}/', 'getsubnavigation(%s)|raw }', $filedata, 'comma');
        $filedata = str_replace('/\|getmainnavigation}/', '|getmainnavigation|raw }', $filedata);
        $filedata = $this->pregReplaceSprintf('/\|truncate:(.*?)}/', '|truncate(%s) }', $filedata);
        $filedata = $this->pregReplaceSprintf('/\|geturl:(.*?)}/', '|geturl(%s) }', $filedata, 'comma');
        $filedata = $this->pregReplaceSprintf('/\|geturl:(.*?)}/', '|geturl(%s) }', $filedata);
        $filedata = str_replace('/Grid}/', 'Grid|raw }', $filedata);

        // string replacers
        $filedata = str_replace('*}', '#}', $filedata); // comments
        $filedata = str_replace('{*', '{#', $filedata); // comments
        $filedata = str_replace('|ucfirst', '|capitalize', $filedata);
        $filedata = str_replace('.html.twig', $this->extension, $filedata);
        $filedata = str_replace("\t", '  ', $filedata);

        // raw converter
        $filedata = str_replace('siteHTMLHeader', 'siteHTMLHeader|raw', $filedata);
        $filedata = str_replace('siteHTMLFooter', 'siteHTMLFooter|raw', $filedata);
        $filedata = str_replace(' metaCustom ', ' metaCustom|raw ', $filedata);
        $filedata = str_replace(' meta ', ' meta|raw ', $filedata);
        $filedata = str_replace('blockContent', 'blockContent|raw', $filedata);

        // includes
        $filedata = $this->pregReplaceSprintf('/{include:(.*)}/i', '{%% include "%s" %%}', $filedata); // for includes

        // operators
        $filedata = $this->pregReplaceSprintf('/{option:!(.*?)}/i', '{%% if not %s %%}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{\/option:(.*?)}/i', '{%% endif %%}', $filedata); // for {option: variable }
        $filedata = $this->pregReplaceSprintf('/{option:(.*?)}/i', '{%% if %s %%}', $filedata);

        //form values values are lowercase
        $filedata = $this->pregReplaceSprintf('/{\/form:(.*?)}/i', '{%% endform %%}', $filedata); // for {form:add}
        $filedata = $this->pregReplaceSprintf('/{form:(.*?)}/i', '{%% form %s %%}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ txt(.*?) }}/i', '{%% form_field %s %%}', $filedata, 'snakeCase');
        $filedata = $this->pregReplaceSprintf('/{{ file(.*?) }}/i', '{%% form_field %s %%}', $filedata, 'snakeCase');
        $filedata = $this->pregReplaceSprintf('/{{ ddm(.*?) }}/i', '{%% form_field %s %%}', $filedata, 'snakeCase');
        $filedata = $this->pregReplaceSprintf('/{{ chk(.*?) }}/i', '{%% form_field %s %%}', $filedata, 'snakeCase');
        $filedata = $this->pregReplaceSprintf('/form_field (.*?)_error/i', 'form_field_error %s', $filedata);

        // caching // disabled
        $filedata = $this->pregReplaceSprintf('/{\/cache:(.*?)}/i', '{# endcache #}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{cache:(.*?)}/i', '{# cache(%s) #}', $filedata);

        // labels
        $filedata = $this->pregReplaceSprintf('/{{ lbl(.*?) }}/i', '{{ lbl.%s }}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ msg(.*?) }}/i', '{{ msg.%s }}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ err(.*?) }}/i', '{{ err.%s }}', $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ act(.*?) }}/i', '{{ act.%s }}', $filedata);

        $filedata = $this->pregReplaceSprintf('/{{ lbl.(.*?)[!^|]/i', "{{ 'lbl.%s'|trans|", $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ act.(.*?)[!^|]/i', "{{ 'act.%s'|trans|", $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ msg.(.*?)[!^|]/i', "{{ 'msg.%s'|trans|", $filedata);
        $filedata = $this->pregReplaceSprintf('/{{ err.(.*?)[!^|]/i', "{{ 'err.%s'|trans|", $filedata);

        return $filedata;
    }
}

$converter = new spoon2twig();
$converter->start($argv);
$converter->displayErrors();
