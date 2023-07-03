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
        if ($this->config->get('system.debugger.enabled')) {
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
        if ($this->config->get('system.debugger.enabled')) {
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
        $page = $this->grav['page'];
        $extra = [
            "csl-entry" => function ($cslItem, $renderedText) {
                return '[^' . trim($cslItem->id, "[]") . ']: ' . $renderedText;
            }
        ];

        if (!$this->config->get('system.pages.markdown.extra')) {
            $this->grav['log']->warning('Bibliography: Markdown Extra disabled, returning ...');
            return;
        }
        if (!isset($page->header()->bibliography) || $page->header()->bibliography == "") {
            $this->grav['log']->warning('Bibliography: No bibliography set, returning ...');
            return;
        }
        $bibliography = $page->header()->bibliography;
        $library = 'user://data/bibliography/' . $bibliography;
        if (!file_exists($library)) {
            $this->grav['log']->warning('Bibliography: Referenced library does not exist, returning ...');
            return;
        }
        $fileinfo = pathinfo($library);
        if (!$fileinfo['extension'] == 'json') {
            $this->grav['log']->warning('Bibliography: Referenced library is not JSON, returning ...');
            return;
        }
        $raw = $page->getRawContent();
        $Style = $page->header()->bibliography_style ?? $this->config->get('plugins.bibliography.style') ?? 'apa-5th-edition';
        $Locale = $page->header()->bibliography_lang ?? $this->config->get('plugins.bibliography.locale') ?? 'en-US';
        $style = StyleSheet::loadStyleSheet($Style);
        $citeProc = new CiteProc($style, $Locale, $extra);
        $file = file_get_contents($library);
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
