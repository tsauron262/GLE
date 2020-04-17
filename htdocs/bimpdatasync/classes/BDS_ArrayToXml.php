<?php

class BDS_ArrayToXml
{

    protected $xml;
    protected $nt = -1;

    public function __construct()
    {
        $this->xml = '';
    }

    protected function tab()
    {
        $tab = '';
        for ($i = 1; $i <= $this->nt; $i++) {
            $tab .= "\t";
        }
        return $tab;
    }
    
    public function getXml(){
        return '<?xml version="1.0" encoding="utf-8"?>'."\n".$this->xml;
    }

    public function writeNodes($nodes)
    {
        $this->nt++;
        foreach ($nodes as $inut => $node) {
            
            
            if (!is_array($node)){//on est sur une feuille
                $tab = array();
                $tab['tag'] = $inut;
                if($node != "")
                    $tab['text'] = $node;
                $node = $tab;
            }
            
            if (isset($node['tag'])) {
                $this->xml .= $this->tab() . '<' . $node['tag'];

                if (isset($node['attrs']) && count($node['attrs'])) {
                    foreach ($node['attrs'] as $name => $value) {
                        $this->xml .= ' ' . $name . '="' . $value . '"';
                    }
                }

                if (isset($node['text']) || isset($node['children'])) {
                    $this->xml .= '>';

                    if (isset($node['text']) && $node['text']) {
                        $this->nt++;
                        $this->xml .= $node['text'];
                        $this->nt--;
                    } elseif (isset($node['children']) && $node['children']) {
                        $this->xml  .= "\n";
                        $this->writeNodes($node['children']);
                        $this->xml  .= $this->tab();
                    }
                    $this->xml .= '</' . $node['tag'] . '>' . "\n";
                } else {
                    $this->xml .= '/>' . "\n";
                }

            }
        }
        $this->nt--;
    }
}
