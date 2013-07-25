<?php

/**
 * Class MWPageCrawler
 *
 * @author Tomasz Madeyski <tomasz.madeyski@makingwaves.pl>
 */
class MWPageCrawler{

    private $seen = array();
    private $templateUsageArray = array();
    private $entryUrl;
    private $entryUrlParts;

    public function __construct(eZINI $siteIni){
        if ($siteIni->variable("TemplateSettings","ShowUsedTemplates") != "enabled"){
            throw new Exception("ShowUsedTemplates setting is set to disabled. Check your site.ini.append.php");
        }
    }
    public function crawlPage($url, $depth, $reset = false){
        if (sizeof($this->seen) == 0 || $reset){
            $this->entryUrl = $url;
            $this->entryUrlParts = parse_url($this->entryUrl);
        }

        if (in_array($url,$this->seen) || $depth === 0){
            return true;
        }

        $dom = new DOMDocument('1.0');
        $dom->loadHTMLFile($url);

        $xpath = new DOMXPath($dom);

        $templateUsageTable = $xpath->query("//*[@id='templateusage']/tr[@class='data']");

        /** @var DOMElement $tableRow */
        foreach ($templateUsageTable as $tableRow) {

            $templateName = $tableRow->childNodes->item(3)->textContent;
            if (!array_key_exists($templateName,$this->templateUsageArray)){
                $this->templateUsageArray[$templateName] = array($url);
            }
            else{
                array_push($this->templateUsageArray[$templateName],$url);
            }
            /*
                Templateusage table markup contains anchors to sites to edit template (/visual/templateview/...),
                so we need to remove it so these anchors don't get on list of anchors to visit

            */
            $parentNode = $tableRow->parentNode;
            $parentNode->removeChild($tableRow);
        }

        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $element) {

            $href = $element->getAttribute('href');

            if (strpos($href, 'http') !== 0) {

                $path = '/' . ltrim($href, '/');
                $parts = parse_url($url);

                if (!$this->shouldICheckPage($path,$url)){
                    continue;
                }

                $href = $parts['scheme'] . '://';
                if (isset($parts['user']) && isset($parts['pass'])) {
                    $href .= $parts['user'] . ':' . $parts['pass'] . '@';
                }
                $href .= $parts['host'];
                if (isset($parts['port'])) {
                    $href .= ':' . $parts['port'];
                }
                if ($href == $url){
                    continue;
                }
                $href .= $path;
            }
            if (!in_array($url,$this->seen)){
                array_push($this->seen, $url);
            }

            $this->crawlPage($href, ($depth == 0 ? 0 : ($depth - 1)));
        }

    }

    public function dumpTemplateUsage(){
        echo "TemplateUsage:" . PHP_EOL;
        print_r($this->templateUsageArray);
    }

    public function dumpUrlsVisited(){
        print_r($this->seen);
    }

    private function shouldICheckPage($path, $url){

        $parts = parse_url($url);

        if (strpos($path, "/mailto:") === 0){
            return false;
        }

        if (strpos($path, "/#") === 0){
            return false;
        }

        if (strpos($path, "/javascript:") === 0){
            return false;
        }

        if ($parts["host"] != $this->entryUrlParts["host"]){
            return false;
        }

        return true;
    }
}
