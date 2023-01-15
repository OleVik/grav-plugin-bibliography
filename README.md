# [Grav](http://getgrav.org/) Bibliography Plugin

Reads a Bibliography-file (`.json`) with academic references and renders it as footnotes at the end of the page. Allows for a variety of styles and languages using [CSL](http://citationstyles.org/).

Uses [Citeproc-PHP](https://github.com/seboettg/citeproc-php) for parsing and formatting. `JSON` is a more reliable format than Bibtex, Ris, and EndNote, and conversion to it should be possible with most modern bibliography managers (eg. [Zotero](https://www.zotero.org/)).

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-bibliography/archive/master.zip) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `bibliography`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/bibliography

The plugin is enabled by default, and can be disabled by copying `user/plugins/bibliography/bibliography.yaml` into `user/config/plugins/bibliography.yaml` and setting `enabled: false`.

Markdown Extra must be enabled in your Grav-config (`system.yaml`) for footnotes to function. That is, it should look like this:

```
pages:
  markdown:
    extra: true
```

## Usage

In the plugin's settings, use the upload-field to add any bibliography-files. These are stored in `/user/data/bibliography`, and can be selected in the Admin-interface under the Options tab, or set manually with a FrontMatter-variable, like so:

```
---
title: Home
bibliography: citations.json
---
```

Or whatever you named the file that was uploaded. For example, using [this example](https://github.com/seboettg/citeproc-php#get-the-metadata-of-your-publications) as `citations.json`, with the default options, returns this:

```
[^ITEM-1]: Knuth, D. E. (1998). Digital Typography. Center for the Study of Language and Information.
[^ITEM-2]: Friedl, J. E. F. (2006). Mastering Regular Expressions (3rd ed.). O'Reilly Media.
[^ITEM-3]: Sprowl, J. A. (1975). The Westlaw System: A Different Approach to Computer-Assisted Legal Research. Jurimetrics Journal, 16, 142. Retrieved from http://heinonline.org/HOL/Page?handle=hein.journals/juraba16&id=152&div=&collection=journals
[^ITEM-4]: Jarvis, R. M. (2008). John B. West: Founder of the West Publishing Company. American Journal Of Legal History, 50(1), 1-22. http://doi.org/10.2307/25664481
```

You reference these by their identifier, for example `[^ITEM-1]` (see [Markdown Extra](http://parsedown.org/extra/)), and a link to the citation will be inserted in your text. From the way Markdown is parsed, only referenced citations are shown.

## Options

| FrontMatter key    | Default           | Description                                                                                                                                                           |
| ------------------ | ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| bibliography       |                   | Filename of a bibliography file, for example `citations.json`, set on a page-level.                                                                                   |
| enabled            | true              | `true` to enable the plugin, `false` to disable it.                                                                                                                   |
| bibliography_style | "apa-5th-edition" | Style to use for formatting citations, must match a [.csl](http://citationstyles.org/styles/)-file in `/vendor/citation-style-language/styles-distribution`.          |
| bibliography_lang  | "en-US"           | Language to use for formatting citations, must match a [.xml](https://packagist.org/packages/academicpuma/locales)-file in `/vendor/citation-style-language/locales`. |

The included style and locale are only the defaults, and more can be installed into `/your/site/grav/user/plugins/bibliography/vendor/citation-style-language`. Locale-files go into `... /vendor/citation-style-language/locales` and styles go into `... /vendor/citation-style-language/styles-distribution`, and are available from [here](https://github.com/citation-style-language/locales) and [here](https://github.com/citation-style-language/styles-distribution), respectively.

## Caveats

Performance may be slow on initial load with a large bibliography, but if cached the impact is negligible - it is just a bunch Markdown returned as HTML. Some simple tests, using Grav v1.1.3:

| References | Time     | Filesize |
| ---------- | -------- | -------- |
| 4          | 107.54ms | 2.43 KB  |
| 59         | 395.03ms | 33.8 KB  |
| 100        | 885.29ms | 58.4 KB  |
| 1000       | 3.45s    | 581 KB   |
| 10.000     | 49.66s   | 5.69 MB  |

These numbers are the time needed by the plugin to read and render a JSON-file with a bibliography, and do not represent the load impact on a live, cached site. If developing with the Debugger enabled, the Bibliography-rendering is logged into the Timeline.

MIT License 2018-2023 by [Ole Vik](https://olevik.me/).
