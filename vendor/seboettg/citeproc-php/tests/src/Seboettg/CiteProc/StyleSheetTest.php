<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2017 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace src\Seboettg\CiteProc;

use PHPUnit\Framework\TestCase;
use Seboettg\CiteProc\StyleSheet;

class StyleSheetTest extends TestCase
{

    /**
     * @coversNothing
     */
    public function testLoadStyleSheet()
    {

        $style = StyleSheet::loadStyleSheet("din-1505-2");
        $xmlStyle = new \SimpleXMLElement($style);
        foreach ($xmlStyle as $child) {
            if ($child->getName() === "info") {
                foreach ($child as $subChild) {
                    if ($subChild->getName() === "id") {
                        $this->assertEquals("http://www.zotero.org/styles/din-1505-2", (string) $subChild);
                        break;
                    }
                }
                break;
            }
        }

    }

    /**
     * @coversNothing
     */
    public function testLoadLocales()
    {

        $locales = StyleSheet::loadLocales("de-DE");
        $xmlLocales = new \SimpleXMLElement($locales);
        foreach ($xmlLocales as $child) {
            if ($child->getName() === "terms") {
                foreach ($child as $term) {
                    echo $term["name"];
                    if ("and" === (string) $term["name"]) {
                        $this->assertEquals("und", (string) $term);
                        break;
                    }
                }
                break;
            }
        }

    }
}
