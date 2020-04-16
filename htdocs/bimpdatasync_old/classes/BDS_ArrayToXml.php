<?php

class BDS_ArrayToXml
{

    protected $xml;
    protected $nt = 0;

    public function __construct()
    {
        $this->xml = '';
    }

    protected function tab()
    {
        $tab = '';
        for ($id = 1; $i <= $this->nt; $i++) {
            $tab .= "\t";
        }
        return $tab;
    }

    protected function writeNodes($nodes)
    {
        $this->nt++;
        foreach ($nodes as $node) {
            if (isset($node['tag'])) {
                $this->xml .= $this->tab() . '<' . $node['tag'];

                if (isset($node['attrs']) && count($node['attrs'])) {
                    foreach ($node['attrs'] as $name => $value) {
                        $this->xml .= ' ' . $name . '="' . $value . '"';
                    }
                }

                if (isset($node['text']) || isset($node['children'])) {
                    $this->xml .= '>' . "\n";

                    if (isset($node['text']) && $node['text']) {
                        $this->nt++;
                        $this->xml .= $this->tab() . $node['text'] . "\n";
                        $this->nt--;
                    } elseif (isset($node['children']) && $node['children']) {
                        $this->writeNodes($node['children']);
                    }
                } else {
                    $this->xml .= '/>' . "\n";
                }

                $this->xml .= $this->tab() . '</' . $node['tag'] . '>' . "\n";
            }
        }
        $this->nt--;
    }
}
