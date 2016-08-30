<?php
namespace Grav\Plugin;

use Grav\Common\Data;
use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
require __DIR__ . '/vendor/autoload.php';
use AcademicPuma\CiteProc\CiteProc;

class BibliographyPlugin extends Plugin
{

    public static function getSubscribedEvents() {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }
    public function onPluginsInitialized() {
        $this->enable([
            'onPageContentRaw' => ['onPageContentRaw', 0],
        ]);
    }

    public function onPageContentRaw(Event $event) {
        $page = $event['page'];
        $pluginsobject = (array) $this->config->get('plugins');
        $systemobject = (array) $this->config->get('system');
        if ($systemobject['debugger']['enabled']) {
            $this->grav['debugger']->startTimer('bibliography', 'Bibliography');
        }
        $pageobject = $this->grav['page'];
        if ($systemobject['pages']['markdown']['extra']) {
            if (isset($pluginsobject['bibliography'])) {
                if ($pluginsobject['bibliography']['enabled']) {
                    if (isset($pageobject->header()->bibliography)) {
                        $bibliography_file = $pageobject->path() . '/' . $pageobject->header()->bibliography;
                        if (file_exists($bibliography_file)) {
                            $fileinfo = pathinfo($bibliography_file);
                            if ($fileinfo['extension'] == 'json') {
                                $raw = $page->getRawContent();
                                
                                $bibliographyStyleName = $pluginsobject['bibliography']['bibliography_style'];
                                $lang = $pluginsobject['bibliography']['bibliography_lang'];
                                $csl = CiteProc::loadStyleSheet($bibliographyStyleName);
                                $citeProc = new CiteProc($csl, $lang);
                                $file = file_get_contents($bibliography_file);

                                $data = json_decode($file);
                                $biblio = "\n";
                                foreach ($data as $key => $item) {
                                    $biblio .= '[^' . $key . ']: ' . $citeProc->render($item) . "\n";
                                }
                                $biblio = strip_tags($biblio, '<a>');
                                $raw .= $biblio;
                                $page->setRawContent($raw);
                            }
                        }
                    }
                }
            }
        }
        if ($systemobject['debugger']['enabled']) {
            $this->grav['debugger']->stopTimer('bibliography');
        }
    }
}