<?php

/**
 * @author Tomasz Madeyski <tomasz.madeyski@makingwaves.pl>
 *
 */
require 'autoload.php';

$cli = eZCLI::instance();

$script = eZScript::instance(array('description' => ("\nChecks template usage\n"),
    'use-session' => false,
    'use-modules' => false,
    'use-extensions' => false));

$options = $script->getOptions(
    '[url:][depth:][input-file:]',
    '',
    array('url' => 'Entry url',
        'depth' => 'How deep crawler should go. Default value is set to 2',
        'input-file'  => 'Path to file containing list of urls to visit (each url in separate line)'
    )
);

$output = new ezcConsoleOutput();
$output->formats->error->style = array('bold');
$output->formats->error->color = 'red';

error_reporting(E_ERROR);

$depth = $options["depth"] ? $options["depth"] : 2;
$siteIni = eZINI::instance("site.ini");
try{
    if (!$options["url"] && !$options["input-file"]){
        throw new Exception("One of options 'url' of 'input-file' is required");
    }

    $crawler = new MWPageCrawler($siteIni);

    $output->outputLine("Starting script...");
    if ($options["input-file"]) {
        if (!file_exists($options["input-file"])){
            throw new Exception("File {$options["input-file"]} doesn't exists or is not readable");
        }
        $hosts = explode("\n", file_get_contents($options["input-file"]));


        foreach ($hosts as $key => $host) {
            if ($host) {
                $output->outputLine("Checking url {$host}");
                $crawler->crawlPage($host, $depth, true);
            }
        }

    } else {
        $crawler->crawlPage($options["url"], $depth);
    }

    $crawler->dumpTemplateUsage();

}
Catch (Exception $e){
    $output->outputText($e->getMessage(), 'error');
    $output->outputLine();
    $script->shutdown($e->getCode());
}


