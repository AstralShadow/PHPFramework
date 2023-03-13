<?php

namespace Core;


namespace TemplateUtils;
{

    interface Node
    {

    }

    class NodeString implements Node
    {
        private string $text;

        public function __construct(string $text)
        {
            $this->text = $text;
        }
    }

    class NodeTag implements Node
    {
        /** $pos is used for exception message composition */
        private int $pos;

        private array $nodes = [];


        public function __construct(int $pos)
        {
            $this->pos = $pos;
        }

        public function getPos() : int
        {
            return $this->pos;
        }

        public function add(Node $node)
        {
            $this->nodes[] = $node;
        }


    }
}

use TemplateUtils\Node;
use TemplateUtils\NodeString;
use TemplateUtils\NodeTag;


trait TempalteFileProcessing
{

    static array $parse_cache = [];

    use TemplateValueAccessors;


    private function processFile(string $file) : string
    {
        $tree = $this->parseFile($file);
        var_dump($tree);
        return "";
    }


    private function parseFile(string $file)
    {
        if(isset($this->parse_cache[$file]))
            return $this->parse_cache[$file];

        $input = file_get_contents($file);
        $len = strlen($input);

        $tokens = [];
        $tag_stack = [];

        $pos = 0;

        while($pos < $len) {
            $tag = strpos($input, '${', $pos);
            $tend = strpos($input, '}', $pos);

            $stack_len = count($tag_stack);

            if($tag !== false && $tag < $tend) // Begin tag
            {
                $text_raw = substr($input, $pos, $tag - $pos);
                $text = new NodeString($text_raw);

                if($stack_len == 0)
                    $tokens[] = $text;
                else
                    $tag_stack[$stack_len - 1]->add($text);

                $tag_stack[] = new NodeTag();

                $pos = $tag + 2;
            }
            else if ($tend !== false && $tend < $tag) // End tag
            {
                $text_raw = substr($input, $pos, $tend - $pos);
                $text = new NodeString($text_raw);

                if($tag_len == 0)
                    throw new Exception("extra '}' in $file at byte $tend.");

                $tag_stack[$stack_len - 1]->add($text);
                $tokens[] = array_splice($tag_stack, -1, 1);

                $pos = $tend + 1;
            }
            else // End of input
            {
                break;
            }
        }


        $stack_len = count($tag_stack);
        if($stack_len == 0) {
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


        $this->parse_cache[$file] = $tokens;
        return $tokens;
    }

}

