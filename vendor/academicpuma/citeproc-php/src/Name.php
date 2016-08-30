<?php

/*
 * Copyright (C) 2015 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AcademicPuma\CiteProc;

/**
 * Description of csl_name
 *
 * @author sebastian
 */

class Name extends Format implements Renderable
{

    private $name_parts = array();
    private $attr_init = FALSE;

    public function __construct($domNode, $citeProc = null)
    {

        $tags = $domNode->getElementsByTagName('name-part');
        if ($tags) {
            foreach ($tags as $tag) {
                $name_part = $tag->getAttribute('name');
                $tag->removeAttribute('name');
                for ($i = 0; $i < $tag->attributes->length; $i++) {
                    $value = $tag->attributes->item($i)->value;
                    $name = str_replace(' ', '_', $tag->attributes->item($i)->name);
                    $this->name_parts[$name_part][$name] = $value;
                }
            }
        }

        parent::__construct($domNode, $citeProc);
    }

    public function render($names, $mode = null)
    {

        $authors = array();
        $count = 0;
        $auth_count = 0;
        $et_al_triggered = false;

        if (!$this->attr_init || $this->attr_init != $mode)
            $this->initAttributes($mode);

        $initialize_with = $this->{'initialize-with'};

        foreach ($names as $rank => $name) {
            $count++;
            //$given = (!empty($name->firstname)) ? $name->firstname : '';
            if (!empty($name->given) && isset($initialize_with)) {
                $name->given = preg_replace("/([$this->upper])[$this->lower]+/$this->patternModifiers", '\\1', $name->given);
                $name->given = preg_replace("/(?<=[-$this->upper]) +(?=[-$this->upper])/$this->patternModifiers", "", $name->given);
                if (isset($name->initials)) {
                    $name->initials = $name->given . $name->initials;
                }
                $name->initials = $name->given;
            }
            if (isset($name->initials)) {
                // within initials, remove any dots:
                $name->initials = preg_replace("/([$this->upper])\.+/$this->patternModifiers", "\\1", $name->initials);
                // within initials, remove any spaces *between* initials:
                $name->initials = preg_replace("/(?<=[-$this->upper]) +(?=[-$this->upper])/$this->patternModifiers", "", $name->initials);
                if ($this->citeProc->style->{'initialize-with-hyphen'} == 'false') {
                    $name->initials = preg_replace("/-/", '', $name->initials);
                }
                // within initials, add a space after a hyphen, but only if ...
                if (preg_match("/ $/", $initialize_with)) {// ... the delimiter that separates initials ends with a space
                    // $name->initials = preg_replace("/-(?=[$this->upper])/$this->patternModifiers", " -", $name->initials);
                }
                // then, separate initials with the specified delimiter:
                $name->initials = preg_replace("/([$this->upper])(?=[^$this->lower]+|$)/$this->patternModifiers", "\\1$initialize_with", $name->initials);

                //      $shortenInitials = (isset($options['numberOfInitialsToKeep'])) ? $options['numberOfInitialsToKeep'] : false;
                //      if ($shortenInitials) $given = drupal_substr($given, 0, $shortenInitials);

                if (isset($initialize_with)) {
                    $name->given = $name->initials;
                } elseif (!empty($name->given)) {
                    $name->given = $name->given . ' ' . $name->initials;
                } elseif (empty($name->given)) {
                    $name->given = $name->initials;
                }
            }

            $ndp = (isset($name->{'non-dropping-particle'})) ? $name->{'non-dropping-particle'} . ' ' : '';
            $suffix = (isset($name->{'suffix'})) ? ' ' . $name->{'suffix'} : '';

            if (isset($name->given)) {
                $given = $this->format($name->given, 'given');
            } else {
                $given = '';
            }
            if (isset($name->family)) {
                $name->family = $this->format($name->family, 'family');
                if ($this->form == 'short') {
                    $text = $ndp . $name->family;
                } else {
                    switch ($this->{'name-as-sort-order'}) {
                        case 'first' && $rank == 0:
                        case 'all':
                            $text = $ndp . $name->family . $this->sort_separator . $given;
                            break;
                        default:
                            $text = $given . ' ' . $ndp . $name->family . $suffix;
                    }
                }
                $authors[] = trim($this->format($text));
            }
            if (isset($this->{'et-al-min'}) && $count >= $this->{'et-al-min'})
                break;
        }
        if (isset($this->{'et-al-min'}) &&
            $count >= $this->{'et-al-min'} &&
            isset($this->{'et-al-use-first'}) &&
            $count >= $this->{'et-al-use-first'} &&
            count($names) > $this->{'et-al-use-first'}
        ) {
            if ($this->{'et-al-use-first'} < $this->{'et-al-min'}) {
                for ($i = $this->{'et-al-use-first'}; $i < $count; $i++) {
                    unset($authors[$i]);
                }
            }
            if ($this->etal) {
                $etal = $this->etal->render();
            } else {
                $etal = $this->citeProc->getLocale()->locale('term', 'et-al');
            }
            $et_al_triggered = true;
        }

        if (!empty($authors) && !$et_al_triggered) {
            $auth_count = count($authors);
            if (isset($this->and) && $auth_count > 1) {
                $authors[$auth_count - 1] = $this->and . ' ' . $authors[$auth_count - 1]; //stick an "and" in front of the last author if "and" is defined
            }
        }

        $text = implode($this->delimiter, $authors);

        if (!empty($authors) && $et_al_triggered) {
            switch ($this->{'delimiter-precedes-et-al'}) {
                case 'never':
                    $text = $text . " $etal";
                    break;
                case 'always':
                    $text = $text . "$this->delimiter$etal";
                    break;
                default:
                    $text = count($authors) == 1 ? $text . " $etal" : $text . "$this->delimiter$etal";
            }
        }

        if ($this->form == 'count') {
            if (!$et_al_triggered) {
                return (int)count($authors);
            } else {
                return (int)(count($authors) - 1);
            }
        }
        // strip out the last delimiter if not required
        if (isset($this->and) && $auth_count > 1) {
            $last_delim = strrpos($text, $this->delimiter . $this->and);
            switch ($this->dpl) { //dpl == delimiter proceeds last
                case 'always':
                    return $text;
                    break;
                case 'never':
                    return substr_replace($text, ' ', $last_delim, strlen($this->delimiter));
                    break;
                case 'contextual':
                default:
                    if ($auth_count < 3) {
                        return substr_replace($text, ' ', $last_delim, strlen($this->delimiter));
                    }
            }
        }
        return $text;
    }

    private function initAttributes($mode)
    {
        //   $and = $this->get_attributes('and');
        if (isset($this->citeProc)) {
            $styleAttrs = $this->citeProc->style->getHierAttributes();
            $modeAttrs = $this->citeProc->{$mode}->getHierAttributes();
            $this->attributes = array_merge($styleAttrs, $modeAttrs, $this->attributes);
        }
        if (isset($this->and)) {
            if ($this->and == 'text') {
                $this->and = $this->citeProc->getLocale()->locale('term', 'and');
            } elseif ($this->and == 'symbol') {
                $this->and = '&';
            }
        }
        if (!isset($this->delimiter)) {
            $this->delimiter = $this->{'name-delimiter'};
        }
        if (!isset($this->alnum)) {
            list($this->alnum, $this->alpha, $this->cntrl, $this->dash,
                $this->digit, $this->graph, $this->lower, $this->print,
                $this->punct, $this->space, $this->upper, $this->word,
                $this->patternModifiers) = $this->get_regex_patterns();
        }
        $this->dpl = $this->{'delimiter-precedes-last'};
        $this->sort_separator = isset($this->{'sort-separator'}) ? $this->{'sort-separator'} : ', ';

        $this->delimiter = isset($this->{'name-delimiter'}) ? $this->{'name-delimiter'} : (isset($this->delimiter) ? $this->delimiter : ', ');

        $this->form = isset($this->{'name-form'}) ? $this->{'name-form'} : (isset($this->form) ? $this->form : 'long');

        $this->attr_init = $mode;
    }

    function get_regex_patterns()
    {
        // Checks if PCRE is compiled with UTF-8 and Unicode support
        if (!@preg_match('/\pL/u', 'a')) {
            // probably a broken PCRE library
            return $this->get_latin1_regex();
        } else {
            // Unicode safe filter for the value
            return $this->get_utf8_regex();
        }
    }

    private function get_latin1_regex()
    {
        $alnum = "[:alnum:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";
        // Matches ISO-8859-1 letters:
        $alpha = "[:alpha:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";
        // Matches ISO-8859-1 control characters:
        $cntrl = "[:cntrl:]";
        // Matches ISO-8859-1 dashes & hyphens:
        $dash = "-–";
        // Matches ISO-8859-1 digits:
        $digit = "[\d]";
        // Matches ISO-8859-1 printing characters (excluding space):
        $graph = "[:graph:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";
        // Matches ISO-8859-1 lower case letters:
        $lower = "[:lower:]äåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";
        // Matches ISO-8859-1 printing characters (including space):
        $print = "[:print:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";
        // Matches ISO-8859-1 punctuation:
        $punct = "[:punct:]";
        // Matches ISO-8859-1 whitespace (separating characters with no visual representation):
        $space = "[\s]";
        // Matches ISO-8859-1 upper case letters:
        $upper = "[:upper:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆ";
        // Matches ISO-8859-1 "word" characters:
        $word = "_[:alnum:]ÄÅÁÀÂÃÇÉÈÊËÑÖØÓÒÔÕÜÚÙÛÍÌÎÏÆäåáàâãçéèêëñöøóòôõüúùûíìîïæÿß";
        // Defines the PCRE pattern modifier(s) to be used in conjunction with the above variables:
        // More info: <http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php>
        $patternModifiers = "";

        return array($alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower,
            $print, $punct, $space, $upper, $word, $patternModifiers);
    }

    private function get_utf8_regex()
    {
        // Matches Unicode letters & digits:
        $alnum = "\p{Ll}\p{Lu}\p{Lt}\p{Lo}\p{Nd}"; // Unicode-aware equivalent of "[:alnum:]"
        // Matches Unicode letters:
        $alpha = "\p{Ll}\p{Lu}\p{Lt}\p{Lo}"; // Unicode-aware equivalent of "[:alpha:]"
        // Matches Unicode control codes & characters not in other categories:
        $cntrl = "\p{C}"; // Unicode-aware equivalent of "[:cntrl:]"
        // Matches Unicode dashes & hyphens:
        $dash = "\p{Pd}";
        // Matches Unicode digits:
        $digit = "\p{Nd}"; // Unicode-aware equivalent of "[:digit:]"
        // Matches Unicode printing characters (excluding space):
        $graph = "^\p{C}\t\n\f\r\p{Z}"; // Unicode-aware equivalent of "[:graph:]"
        // Matches Unicode lower case letters:
        $lower = "\p{Ll}\p{M}"; // Unicode-aware equivalent of "[:lower:]"
        // Matches Unicode printing characters (including space):
        $print = "\P{C}"; // same as "^\p{C}", Unicode-aware equivalent of "[:print:]"
        // Matches Unicode punctuation (printing characters excluding letters & digits):
        $punct = "\p{P}"; // Unicode-aware equivalent of "[:punct:]"
        // Matches Unicode whitespace (separating characters with no visual representation):
        $space = "\t\n\f\r\p{Z}"; // Unicode-aware equivalent of "[:space:]"
        // Matches Unicode upper case letters:
        $upper = "\p{Lu}\p{Lt}"; // Unicode-aware equivalent of "[:upper:]"
        // Matches Unicode "word" characters:
        $word = "_\p{Ll}\p{Lu}\p{Lt}\p{Lo}\p{Nd}"; // Unicode-aware equivalent of "[:word:]" (or "[:alnum:]" plus "_")
        // Defines the PCRE pattern modifier(s) to be used in conjunction with the above variables:
        // More info: <http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php>
        $patternModifiers = "u"; // the "u" (PCRE_UTF8) pattern modifier causes PHP/PCRE to treat pattern strings as UTF-8
        return array($alnum, $alpha, $cntrl, $dash, $digit, $graph, $lower,
            $print, $punct, $space, $upper, $word, $patternModifiers);
    }

    protected function format($text, $part = 'base')
    {

        if (empty($text) || $this->noOp[$part])
            return $text;
        if (isset($this->{$part}['text-case'])) {
            switch ($this->{$part}['text-case']) {
                case 'uppercase':
                    $text = mb_strtoupper($text);
                    break;
                case 'lowercase':
                    $text = mb_strtolower($text);
                    break;
                case 'capitalize-all':
                    $text = mb_convert_case($text, MB_CASE_TITLE);
                    break;
                case 'capitalize-first':
                    $chr1 = mb_strtoupper(mb_substr($text, 0, 1));
                    $text = $chr1 . mb_substr($text, 1);
                    break;
            }
        }
        $open_quote = isset($this->{$part}['open-quote']) ? $this->{$part}['open-quote'] : '';
        $close_quote = isset($this->{$part}['close-quote']) ? $this->{$part}['close-quote'] : '';
        $prefix = isset($this->{$part}['prefix']) ? $this->{$part}['prefix'] : '';
        $suffix = isset($this->{$part}['suffix']) ? $this->{$part}['suffix'] : '';
        if ($text[(strlen($text) - 1)] == $suffix)
            unset($suffix);
        if (!empty($this->format[$part])) {
            $text = '<span style="' . $this->format[$part] . '">' . $text . '</span>';
        }
        return $prefix . $open_quote . $text . $close_quote . $suffix;
    }

    protected function initFormatting()
    {
        $this->noOp = array();
        $this->format = array();
        $this->base = $this->getAttributes();
        $this->format['base'] = '';
        $this->format['family'] = '';
        $this->format['given'] = '';
        $this->noOp['base'] = true;
        $this->noOp['family'] = true;
        $this->noOp['given'] = true;

        if (isset($this->prefix)) {
            $this->noOp['base'] = false;
        }
        if (isset($this->suffix)) {
            $this->noOp['base'] = false;
        }
        $this->initFormat($this->base);


        if (!empty($this->name_parts)) {
            foreach ($this->name_parts as $name => $formatting) {
                $this->initFormat($formatting, $name);
            }
        }
    }

    private function initFormat($attribs, $part = 'base')
    {
        if (!isset($this->{$part})) {
            $this->{$part} = array();
        }
        if (isset($attribs['quotes']) && strtolower($attribs['quotes']) == 'true') {
            $this->{$part}['open-quote'] = $this->citeProc->getLocale()->locale('term', 'open-quote');
            $this->{$part}['close-quote'] = $this->citeProc->getLocale()->locale('term', 'close-quote');
            $this->{$part}['open-inner-quote'] = $this->citeProc->getLocale()->locale('term', 'open-inner-quote');
            $this->{$part}['close-inner-quote'] = $this->citeProc->getLocale()->locale('term', 'close-inner-quote');
            $this->noOp[$part] = false;
        }

        if (isset($attribs['prefix']))
            $this->{$part}['prefix'] = $attribs['prefix'];
        if (isset($attribs['suffix']))
            $this->{$part}['suffix'] = $attribs['suffix'];

        $this->format[$part] .= (isset($attribs['font-style'])) ? 'font-style: ' . $attribs['font-style'] . ';' : '';
        $this->format[$part] .= (isset($attribs['font-family'])) ? 'font-family: ' . $attribs['font-family'] . ';' : '';
        $this->format[$part] .= (isset($attribs['font-weight'])) ? 'font-weight: ' . $attribs['font-weight'] . ';' : '';
        $this->format[$part] .= (isset($attribs['font-variant'])) ? 'font-variant: ' . $attribs['font-variant'] . ';' : '';
        $this->format[$part] .= (isset($attribs['text-decoration'])) ? 'text-decoration: ' . $attribs['text-decoration'] . ';' : '';
        $this->format[$part] .= (isset($attribs['vertical-align'])) ? 'vertical-align: ' . $attribs['vertical-align'] . ';' : '';

        if (isset($attribs['text-case'])) {
            $this->noOp[$part] = false;
            $this->{$part}['text-case'] = $attribs['text-case'];
        }
        if (!empty($this->format[$part]))
            $this->noOp[$part] = false;
    }

}