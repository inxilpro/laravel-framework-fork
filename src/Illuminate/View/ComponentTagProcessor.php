<?php

namespace Illuminate\View;

use Illuminate\Support\Collection;

class ComponentTagProcessor
{
    /**
     * Process the opening tags within the given string.
     *
     * @param  string  $value
     * @param  callable(array{'component':string,'attributes':string}):string  $callback
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function processOpeningTags(string $value, callable $callback)
    {
        $pattern = "/
            <
                \s*
                x[-\:](?<component>[\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Process the self-closing tags within the given string.
     *
     * @param  string  $value
     * @param  callable(array{'component':string,'attributes':string}):string  $callback
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function processSelfClosingTags(string $value, callable $callback)
    {
        $pattern = "/
            <
                \s*
                x[-\:](?<component>[\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
            \/>
        /x";

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the closing tags within the given string.
     *
     * @param  string  $value
     * @param  callable(array{'component':string}):string  $callback
     * @return string
     */
    public function processClosingTags(string $value, callable $callback)
    {
        return preg_replace_callback("/<\/\s*x[-\:](?<component>[\w\-\:\.]*)\s*>/", $callback, $value);
    }

    /**
     * Process the opening slot tags within the given string.
     *
     * @param  string  $value
     * @param  callable(array{'inlineName':string,'name':string,'boundName':string,'attributes':string}):string  $callback
     * @return string
     */
    public function processOpeningSlotTags(string $value, callable $callback)
    {
        $pattern = "/
            <
                \s*
                x[\-\:]slot
                (?:\:(?<inlineName>\w+(?:-\w+)*))?
                (?:\s+name=(?<name>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?
                (?:\s+\:name=(?<boundName>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Process the closing slot tags within the given string.
     *
     * @param  string  $value
     * @param  callable(array):string  $callback
     * @return string
     */
    public function processClosingSlotTags(string $value, callable $callback)
    {
        return preg_replace_callback('/<\/\s*x[\-\:]slot[^>]*>/', $callback, $value);
    }

    /**
     * Process the given attribute string into an array of attributes.
     *
     * @param  string  $value
     * @param  callable(array{'attribute':string,'value':string|null):array  $callback
     * @return array
     */
    public function processAttributeString(string $value, callable $callback)
    {
        $value = $this->parseShortAttributeSyntax($value);
        $value = $this->parseAttributeBag($value);
        $value = $this->parseComponentTagClassStatements($value);
        $value = $this->parseComponentTagStyleStatements($value);
        $value = $this->parseBindAttributes($value);

        $pattern = '/
            (?<attribute>[\w\-:.@%]+)
            (
                =
                (?<value>
                    (
                        \"[^\"]+\"
                        |
                        \\\'[^\\\']+\\\'
                        |
                        [^\s>]+
                    )
                )
            )?
        /x';

        if (! preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return Collection::make($matches)->mapWithKeys($callback)->toArray();
    }

    /**
     * Parses a short attribute syntax like :$foo into a fully-qualified syntax like :foo="$foo".
     *
     * @param  string  $value
     * @return string
     */
    protected function parseShortAttributeSyntax(string $value)
    {
        $pattern = "/\s\:\\\$(\w+)/x";

        return preg_replace_callback($pattern, fn(array $matches) => " :{$matches[1]}=\"\${$matches[1]}\"", $value);
    }

    /**
     * Parse the attribute bag in a given attribute string into its fully-qualified syntax.
     *
     * @param  string  $value
     * @return string
     */
    protected function parseAttributeBag(string $value)
    {
        $pattern = "/
            (?:^|\s+)                                        # start of the string or whitespace between attributes
            \{\{\s*(\\\$attributes(?:[^}]+?(?<!\s))?)\s*\}\} # exact match of attributes variable being echoed
        /x";

        return preg_replace($pattern, ' :attributes="$1"', $value);
    }

    /**
     * Parse @class statements in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $value
     * @return string
     */
    protected function parseComponentTagClassStatements(string $value)
    {
        return preg_replace_callback('/@(class)(\( ( (?>[^()]+) | (?2) )* \))/x', function ($match) {
            if ($match[1] === 'class') {
                $match[2] = str_replace('"', "'", $match[2]);

                return ":class=\"\Illuminate\Support\Arr::toCssClasses{$match[2]}\"";
            }

            return $match[0];
        }, $value);
    }

    /**
     * Parse @style statements in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $value
     * @return string
     */
    protected function parseComponentTagStyleStatements(string $value)
    {
        return preg_replace_callback('/@(style)(\( ( (?>[^()]+) | (?2) )* \))/x', function ($match) {
            if ($match[1] === 'style') {
                $match[2] = str_replace('"', "'", $match[2]);

                return ":style=\"\Illuminate\Support\Arr::toCssStyles{$match[2]}\"";
            }

            return $match[0];
        }, $value);
    }

    /**
     * Parse the "bind" attributes in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $value
     * @return string
     */
    protected function parseBindAttributes(string $value)
    {
        $pattern = "/
            (?:^|\s+)     # start of the string or whitespace between attributes
            :(?!:)        # attribute needs to start with a single colon
            ([\w\-:.@]+)  # match the actual attribute name
            =             # only match attributes that have a value
        /xm";

        return preg_replace($pattern, ' bind:$1=', $value);
    }
}
