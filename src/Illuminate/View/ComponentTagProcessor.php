<?php

namespace Illuminate\View;

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
}
