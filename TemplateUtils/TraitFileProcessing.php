<?php

namespace Core\TemplateUtils;


trait TraitFileProcessing
{

    static array $parse_cache = [];

    use TraitValueAccessors;


    private function processFile(string $file) : string
    {
        $tree = $this->parseFile($file);
        var_dump($tree);


        return "";
    }


    private function parseFile(string $file)
    {
        if(isset(self::$parse_cache[$file]))
            return self::$parse_cache[$file];

        $input = file_get_contents($file);
        $len = strlen($input);

        $tokens = [];
        $tag_stack = [];

        $pos = 0;

        while($pos < $len) {
            $tag = strpos($input, '${', $pos);
            $tend = strpos($input, '}', $pos);

            $stack_len = count($tag_stack);

            $reached = null;
            if($tag !== false && $tend === false)
                $reached = "tag";
            else if ($tag === false && $tend !== false)
                $reached = "tend";
            else if ($tag === false && $tend === false)
                $reached = null;
            else if ($tag < $tend)
                $reached = "tag";
            else
                $reached = "tend";

            if($reached == null)
                break;

            switch($reached) {
            case "tag":
                $text_raw = substr($input, $pos, $tag - $pos);
                if($text_raw != "") {
                    $text = new NodeString($text_raw);
                    if($stack_len == 0)
                        $tokens[] = $text;
                    else
                        $tag_stack[$stack_len - 1]->add($text);
                }

                $tag_stack[] = new NodeTag($tag);

                $pos = $tag + 2;
                break;

            case "tend":
                if($stack_len == 0)
                    throw new Exception("extra '}' in $file at byte $tend.");

                $text_raw = substr($input, $pos, $tend - $pos);
                if($text_raw != "") {
                    $text = new NodeString($text_raw);
                    $tag_stack[$stack_len - 1]->add($text);
                }

                $stack_len--;
                $node = array_splice($tag_stack, -1, 1)[0];

                if($stack_len == 0)
                    $tokens[] = $node;
                else 
                    $tag_stack[$stack_len - 1]->add($node);

                $pos = $tend + 1;
                break;
            }
        }


        $stack_len = count($tag_stack);
        if($stack_len != 0) {
            $err_msg = 'Unmatched ${ in ' . $file . ' at ';
            if($stack_len == 1) {
                $err_msg .= "byte {$tag_stack[0]->getPos()}.";
            } else {
                $err_msg .= "bytes ";
                foreach($tag_stack[0] as $_tag)
                    $err_msg .= $_tag->getPos() . ", ";
                $err_msg = substr($err_msg, 0, -2) . ".";
            }
            throw new Exception($err_msg);
        }

        $text_raw = substr($input, $pos);
        $text = new NodeString($text_raw);
        $tokens[] = $text;


        self::$parse_cache[$file] = $tokens;
        return $tokens;
    }

}

