<?php

namespace Grav\Plugin;

use Grav\Common\Data;
use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Data\Blueprints;
use RocketTheme\Toolbox\Event\Event;
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

/**
 * Bibliography Plugin Class
 * 
 * Reads a Bibliography-file (.json) with academic references 
 * and renders it as footnotes at the end of the page. 
 * Allows for a variety of styles and languages using CSL.
 *
 * Class BibliographyPlugin
 * 
 * @package Grav\Plugin
 * @return  string Formatted Markdown Footnotes
 * @author  Ole Vik <git@olevik.me>
 * @license MIT License by Ole Vik
 */
class BibliographyPlugin extends Plugin
{
    /**
     * Register events
     *
     * @return void
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Composer autoload.
     *
     * @return \Composer\Autoload\ClassLoader
     */
    public function autoload(): \Composer\Autoload\ClassLoader
    {
        return include __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize events
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        $system = (array) $this->config->get('system');
        if ($system['debugger']['enabled']) {
            $this->grav['debugger']->startTimer('bibliography', 'Bibliography');
        }
        if ($this->isAdmin()) {
            $this->active = false;
            $this->enable(
                [
                    'onBlueprintCreated' => ['onBlueprintCreated', 0]
                ]
            );
        } else {
            $this->enable(
                [
                    'onPageContentRaw' => ['onPageContentRaw', 0]
                ]
            );
        }
        if ($system['debugger']['enabled']) {
            $this->grav['debugger']->stopTimer('bibliography');
        }
    }

    /**
     * Process raw Markdown and footnotes
     *
     * @param Event $event RocketTheme\Toolbox\Event\Event
     * 
     * @return void
     */
    public function onPageContentRaw(Event $event)
    {
        $page = $event['page'];
        $plugin = (array) $this->config->get('plugins');
        $system = (array) $this->config->get('system');

        $extra = [
            "csl-entry" => function ($cslItem, $renderedText) {
                return '[^' . trim($cslItem->id, "[]") . ']: ' . $renderedText;
            }
        ];

        $page = $this->grav['page'];
        if (!$system['pages']['markdown']['extra']) {
            return;
        }
        if (!isset($plugin['bibliography']) || !$plugin['bibliography']['enabled']) {
            return;
        }
        if (!isset($page->header()->bibliography)) {
            return;
        }
        $bibliography = $page->header()->bibliography;
        $bibfile = 'user://data/bibliography/' . $bibliography;
        if (!file_exists($bibfile)) {
            return;
        }
        $fileinfo = pathinfo($bibfile);
        if (!$fileinfo['extension'] == 'json') {
            return;
        }
        $raw = $page->getRawContent();
        $styleName = $plugin['bibliography']['style'];
        $lang = $plugin['bibliography']['locale'];
        $style = StyleSheet::loadStyleSheet($styleName);
        $citeProc = new CiteProc($style, $lang, $extra);
        $file = file_get_contents($bibfile);
        $data = json_decode($file);
        $biblio = $citeProc->render($data, "bibliography");
        $biblio = strip_tags($biblio);
        $biblio = implode("\n", array_map('trim', explode("\n", $biblio)));
        $raw .= "\n\n" . $biblio;
        $page->setRawContent($raw);
    }

    /**
     * Register blueprints
     *
     * @param Event $event RocketTheme\Toolbox\Event\Event
     * 
     * @return void
     */
    public function onBlueprintCreated(Event $event)
    {
        $blueprint = $event['blueprint'];
        $blueprints = new Blueprints(__DIR__ . '/blueprints/');
        $extends = $blueprints->get($this->name);
        $blueprint->extend($extends, true);
    }
}
