<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Rendering;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Context;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\RenderingState;
use Seboettg\CiteProc\Styles\AffixesTrait;
use Seboettg\CiteProc\Styles\ConsecutivePunctuationCharacterTrait;
use Seboettg\CiteProc\Styles\DisplayTrait;
use Seboettg\CiteProc\Styles\FormattingTrait;
use Seboettg\CiteProc\Styles\QuotesTrait;
use Seboettg\CiteProc\Styles\TextCaseTrait;
use Seboettg\CiteProc\Util\CiteProcHelper;
use Seboettg\CiteProc\Util\NumberHelper;
use Seboettg\CiteProc\Util\PageHelper;
use Seboettg\CiteProc\Util\StringHelper;


/**
 * Class Term
 * @package Seboettg\CiteProc\Node\Style
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Text implements Rendering
{
    use FormattingTrait,
        AffixesTrait,
        TextCaseTrait,
        DisplayTrait,
        ConsecutivePunctuationCharacterTrait,
        QuotesTrait;

    /**
     * @var string
     */
    private $toRenderType;

    /**
     * @var string
     */
    private $toRenderTypeValue;

    /**
     * @var string
     */
    private $form = "long";

    /**
     * Text constructor.
     * @param \SimpleXMLElement $node
     */
    public function __construct(\SimpleXMLElement $node)
    {
        foreach ($node->attributes() as $attribute) {
            $name = $attribute->getName();
            if (in_array($name, ['value', 'variable', 'macro', 'term'])) {
                $this->toRenderType = $name;
                $this->toRenderTypeValue = (string) $attribute;
            }
            if ($name === "form") {
                $this->form = (string) $attribute;
            }
        }
        $this->initFormattingAttributes($node);
        $this->initDisplayAttributes($node);
        $this->initTextCaseAttributes($node);
        $this->initAffixesAttributes($node);
        $this->initQuotesAttributes($node);
    }

    /**
     * @param \stdClass $data
     * @param int|null $citationNumber
     * @return string
     */
    public function render($data, $citationNumber = null)
    {
        $lang = (isset($data->language) && $data->language != 'en') ? $data->language : 'en';

        $renderedText = "";
        switch ($this->toRenderType) {
            case 'value':
                $renderedText = $this->applyTextCase($this->toRenderTypeValue, $lang);
                break;
            case 'variable':
                if ($this->toRenderTypeValue === "citation-number") {
                    $renderedText = $this->renderCitationNumber($data, $citationNumber);
                    break;
                } else if ($this->toRenderTypeValue === "page") {
                    $renderedText = $this->renderPage($data);
                    // for test sort_BibliographyCitationNumberDescending.json
                } else {
                    $renderedText = $this->renderVariable($data, $lang);
                }
                if (CiteProc::getContext()->getRenderingState()->getValue() === RenderingState::SUBSTITUTION) {
                    unset($data->{$this->toRenderTypeValue});
                }
                $renderedText = $this->applyAdditionalMarkupFunction($data, $renderedText);
                break;
            case 'macro':
                $renderedText = $this->renderMacro($data);
                break;
            case 'term':
                $term = CiteProc::getContext()->getLocale()->filter("terms", $this->toRenderTypeValue, $this->form)->single;
                $renderedText = !empty($term) ? $this->applyTextCase($term, $lang) : "";
        }
        if (!empty($renderedText)) {
            $renderedText = $this->formatRenderedText($renderedText);
        }
        return $renderedText;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->toRenderType;
    }

    /**
     * @return string
     */
    public function getVariable()
    {
        return $this->toRenderTypeValue;
    }

    private function renderPage($data)
    {
        if (empty($data->page)) {
            return "";
        }

        if (preg_match(NumberHelper::PATTERN_COMMA_AMPERSAND_RANGE, $data->page)) {
            $data->page = $this->normalizeDateRange($data->page);
            $ranges = preg_split("/[-–]/", trim($data->page));
            if (count($ranges) > 1) {
                if (!empty(CiteProc::getContext()->getGlobalOptions()) && !empty(CiteProc::getContext()->getGlobalOptions()->getPageRangeFormat())) {
                    return PageHelper::processPageRangeFormats($ranges, CiteProc::getContext()->getGlobalOptions()->getPageRangeFormat());
                }
                list($from, $to) = $ranges;
                return "$from-$to";
            }
        }
        return $data->page;
    }

    private function normalizeDateRange($page)
    {
        if (preg_match("/^(\d+)--(\d+)$/", trim($page), $matches)) {
            return $matches[1]."-".$matches[2];
        }
        return $page;
    }

    /**
     * @param $data
     * @param $renderedText
     * @return mixed
     */
    private function applyAdditionalMarkupFunction($data, $renderedText)
    {
        return CiteProcHelper::applyAdditionMarkupFunction($data, $this->toRenderTypeValue, $renderedText);
    }

    /**
     * @param $data
     * @param $lang
     * @return string
     */
    private function renderVariable($data, $lang)
    {
        // check if there is an attribute with prefix short or long e.g. shortTitle or longAbstract
        // test case group_ShortOutputOnly.json
        $renderedText = "";
        if (in_array($this->form, ["short", "long"])) {
            $attrWithPrefix = $this->form . ucfirst($this->toRenderTypeValue);
            $attrWithSuffix = $this->toRenderTypeValue . "-" . $this->form;
            if (isset($data->{$attrWithPrefix}) && !empty($data->{$attrWithPrefix})) {
                $renderedText = $this->applyTextCase(StringHelper::clearApostrophes($data->{$attrWithPrefix}), $lang);
            } else {
                if (isset($data->{$attrWithSuffix}) && !empty($data->{$attrWithSuffix})) {
                    $renderedText = $this->applyTextCase(StringHelper::clearApostrophes($data->{$attrWithSuffix}),
                        $lang);
                } else {
                    if (isset($data->{$this->toRenderTypeValue})) {
                        $renderedText = $this->applyTextCase(StringHelper::clearApostrophes($data->{$this->toRenderTypeValue}),
                            $lang);
                    }

                }
            }
        } else {
            if (!empty($data->{$this->toRenderTypeValue})) {
                $renderedText = $this->applyTextCase(StringHelper::clearApostrophes($data->{$this->toRenderTypeValue}),
                    $lang);
            }
        }
        return $renderedText;
    }

    /**
     * @param $renderedText
     * @return string
     */
    private function formatRenderedText($renderedText)
    {
        $text = $this->format($renderedText);
        $res = $this->addAffixes($text);
        if (!empty($res)) {
            $res = $this->removeConsecutiveChars($res);
        }
        $res = $this->addSurroundingQuotes($res);
        return $this->wrapDisplayBlock($res);
    }

    /**
     * @param $data
     * @param $citationNumber
     * @return int|mixed
     */
    private function renderCitationNumber($data, $citationNumber)
    {
        $renderedText = $citationNumber + 1;
        $renderedText = $this->applyAdditionalMarkupFunction($data, $renderedText);
        return $renderedText;
    }

    /**
     * @param $data
     * @return string
     */
    private function renderMacro($data)
    {
        $macro = CiteProc::getContext()->getMacro($this->toRenderTypeValue);
        if (is_null($macro)) {
            try {
                throw new CiteProcException("Macro \"" . $this->toRenderTypeValue . "\" does not exist.");
            } catch (CiteProcException $e) {
                $renderedText = "";
            }
        } else {
            $renderedText = $macro->render($data);
        }
        return $renderedText;
    }
}
