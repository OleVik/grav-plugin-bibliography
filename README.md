# [Grav](http://getgrav.org/) Bibliography Plugin

Reads a Bibliography-file (`.json`) with academic references and renders it as footnotes at the end of the page. Allows for a variety of styles and languages using [CSL](http://citationstyles.org/).

Uses [Citepro-PHP](https://github.com/seboettg/citeproc-php) for parsing and formatting. `JSON` is a more reliable format than Bibtex, Ris, and EndNote, and conversion to it should be possible with most modern bibliography managers.

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-bibliography) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `bibliography`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/bibliography

The plugin is enabled by default, and can be disabled by copying `user/plugins/bibliography/bibliography.yaml` into `user/config/plugins/bibliography.yaml` and setting `enabled: false`.

Markdown Extra must be enabled in your `system.yaml` for footnotes to function. That is, it should look like this:

```
pages:
  markdown:
    extra: true
```

## Usage

Copy your properly formatted bibliography-file to the same folder as the page you which to display it on, and set a FrontMatter-variable to point to the file, like so:

```
---
title: Home
bibliography: citations.json
---
```

For example, using [this file](https://bitbucket.org/fbennett/citeproc-js/src/2b552c68ca2a891d3869ebdfa5167115cc5e546f/demo/citations.json?at=default&fileviewer=file-view-default) as `citations.json`, with the default options, returns this:

```
[^Item-1]: Knuth, D. E. (1998). Digital Typography. Center for the Study of Language and Information.
[^Item-2]: Friedl, J. E. F. (2006). Mastering Regular Expressions (3rd ed.). O'Reilly Media.
[^Item-3]: Sprowl, J. A. (1975). The Westlaw System: A Different Approach to Computer-Assisted Legal Research. Jurimetrics Journal, 16, 142. Retrieved from http://heinonline.org/HOL/Page?handle=hein.journals/juraba16&id=152&div=&collection=journals
[^Item-4]: Jarvis, R. M. (2008). John B. West: Founder of the West Publishing Company. American Journal Of Legal History, 50(1), 1-22. http://doi.org/10.2307/25664481
```

You reference these by their identifier, for example `[^Item-1]` (see [Markdown Extra](http://parsedown.org/extra/)), and a link to the citation will be inserted in your text. From the way Markdown is parsed, only referenced citations are shown.

## Options

| FrontMatter key | Default | Description |
|--------------------|---------|------------------------------------------------------------------------------------------------------------------------------------------------------------|
| bibliography |  | Filename of a bibliography file, for example `citations.json`, set on a page-level. |
| enabled | true | `true` to enable the plugin, `false` to disable it. |
| bibliography_style | "apa" | Style to use for formatting citations, must match a [.csl](http://citationstyles.org/styles/)-file in `/vendor/academicpuma/styles`. |
| bibliography_lang | "en-US" | Language to use for formatting citations, must match a [.xml](https://packagist.org/packages/academicpuma/locales)-file in `/vendor/academicpuma/locales`. |

The included styles and locales are ample and should cover most use-cases, but more can be installed into the relevant directories (see above links to sources).

## Caveats

Performance may be slow on initial load with a large bibliography, but if cached the impact is negligible - it is just a bunch Markdown returned as HTML. Some simple tests, using Grav v1.1.3:

| References | Time | Filesize |
|------------|----------|---------|
| 4 | 107.54ms | 2.43 KB |
| 59 | 395.03ms | 33.8 KB |
| 100 | 885.29ms | 58.4 KB |
| 1000 | 3.45s | 581 KB |
| 10.000 | 49.66s | 5.69 MB |

These numbers are the time needed by the plugin to read and render a JSON-file with a bibliography, and do not represent the load impact on a live, cached site.

MIT License 2016 by [Ole Vik](https://olevik.me/).