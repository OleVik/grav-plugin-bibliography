<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering;

use PHPUnit\Framework\TestCase;
use Seboettg\CiteProc\Locale\Locale;
use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Context;
use Seboettg\CiteProc\TestSuiteTestCaseTrait;

class NumberTest extends TestCase
{
    use TestSuiteTestCaseTrait;

    public function setUp()
    {
        parent::setUp();
        $context = new Context();
        $context->setLocale(new Locale("en-GB"));
        CiteProc::setContext($context);
    }

    public function testRenderOrdinal()
    {

        $number = new Number(new \SimpleXMLElement('<number variable="edition" form="ordinal"/>'));
        $data = json_decode("{\"title\": \"Ein Buch\", \"edition\": 3}");

        $this->assertEquals("3rd", $number->render($data));
    }

    public function testRenderLongOrdinal()
    {
        $number = new Number(new \SimpleXMLElement('<number variable="edition" form="long-ordinal"/>'));
        $data = json_decode("{\"title\": \"Ein Buch\", \"edition\": 3}");

        $this->assertEquals("third", $number->render($data));
    }

    public function testRenderRoman()
    {
        $number = new Number(new \SimpleXMLElement('<number variable="edition" form="roman"/>'));
        $data = json_decode("{\"title\": \"Ein Buch\", \"edition\": 4}");

        $this->assertEquals("iv", $number->render($data));

        $data = json_decode("{\"title\": \"Ein Buch\", \"edition\": 1678}");
        $this->assertEquals("mdclxxviii", $number->render($data));
    }

    public function testRenderAffixesTextCase()
    {
        $number = new Number(new \SimpleXMLElement('<number variable="edition" form="roman" text-case="uppercase" prefix="[" suffix="]"/>'));
        $data = json_decode("{\"title\": \"Ein Buch\", \"edition\": 16}");
        $this->assertEquals("[XVI]", $number->render($data));
    }

    public function testNumberAffixes()
    {
        $this->_testRenderTestSuite("number_affixes");
    }

    public function testNumberFontStyle()
    {
        $this->_testRenderTestSuite("number_font-style");
    }

    public function testNumberFontWeight()
    {
        $this->_testRenderTestSuite("number_font-weight");
    }

    public function testNumberFontVariant()
    {
        $this->_testRenderTestSuite("number_font-variant");
    }

    public function testNumberFormat()
    {
        $this->_testRenderTestSuite("number_format");
    }

    public function testNumberSpacing()
    {
        $this->_testRenderTestSuite("number_spacing");
    }

    public function testNumberTextCase()
    {
        $this->_testRenderTestSuite("number_text-case");
    }

    public function testNumberTextDecoration()
    {
        $this->_testRenderTestSuite("number_text-decoration");
    }

    public function testNumberVerticalAlign()
    {
        $this->_testRenderTestSuite("number_vertical-align");
    }

    /*
    public function testNumberPlainHyphenOrEnDashAlwaysPlural()
    {
        $this->_testRenderTestSuite("number_PlainHyphenOrEnDashAlwaysPlural");
    }
    */

    public function testNumberSimpleRoman()
    {
        $this->_testRenderTestSuite("number_SimpleNumberRoman");
    }
}
