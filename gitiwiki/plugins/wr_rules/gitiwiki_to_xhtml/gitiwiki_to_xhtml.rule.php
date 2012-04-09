<?php


/**
* @package   gitiwiki
* @subpackage
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

require_once(LIB_PATH.'wikirenderer/rules/dokuwiki_to_xhtml.php');



class  gitiwiki_to_xhtml extends dokuwiki_to_xhtml  {

    public $defaultTextLineContainer = 'WikiHtmlTextLine';

    public $textLineContainers = array(
            'WikiHtmlTextLine'=>array( 'dkxhtml_strong','dkxhtml_emphasis','dkxhtml_underlined','dkxhtml_monospaced',
        'dkxhtml_subscript', 'dkxhtml_superscript', 'dkxhtml_del', 'dkxhtml_link', 'dkxhtml_footnote', 'dkxhtml_image',
        'dkxhtml_nowiki_inline', 'gtwxhtml_code'),
            'dkxhtml_table_row'=>array( 'dkxhtml_strong','dkxhtml_emphasis','dkxhtml_underlined','dkxhtml_monospaced',
        'dkxhtml_subscript', 'dkxhtml_superscript', 'dkxhtml_del', 'dkxhtml_link', 'dkxhtml_footnote', 'dkxhtml_image',
        'dkxhtml_nowiki_inline', 'gtwxhtml_code',));

    /**
    * liste des balises de type bloc reconnus par WikiRenderer.
    */
    public $bloctags = array('dkxhtml_title', 'dkxhtml_list', 'dkxhtml_blockquote','dkxhtml_table', 'dkxhtml_pre',
          'dkxhtml_syntaxhighlight', 'dkxhtml_file', 'dkxhtml_nowiki', 'dkxhtml_html', 'dkxhtml_php', 'dkxhtml_para',
          'gtwxhtml_alternatelang', 'gtwxhtml_bookcontents', 'gtwxhtml_bookinfos', 'gtwxhtml_notinbook',
          'gtwxhtml_bookpagelegalnotice', 'gtwxhtml_booklegalnotice'
    );

    public $basePath;

    public $pagePath;

    public $extractedData = array();

}





class gtwxhtml_bookcontents extends WikiRendererBloc {

    public $type='bookcontents';
    protected $_openTag='<div class="book-contents">';
    protected $_closeTag='</div>';
    protected $isOpen = false;
    protected $dktag='bookcontents';

    public function open(){
        $this->isOpen = true;
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        $this->engine->getConfig()->extractedData['bookContent'] = $this->currentContents;
        return $this->_closeTag;
    }

    public function getRenderedLine(){
        return $this->_htmlcontent;
    }

    protected $currentLevel = false;
    protected $levelStack = array();
    protected $currentContents = array();
    protected $_htmlcontent = '';

    public function detect($string){
        if ($this->isOpen) {
            $this->_htmlcontent = '';
            if(preg_match('/^\s*<\/'.$this->dktag.'>\s*$/',$string,$m)){
                // end tag
                $this->isOpen = false;
                // merge all contents stored in the stack
                for ($i=count($this->levelStack)-1; $i >= 0; $i --) {
                    $this->levelStack[$i][1] = $this->currentContents;
                    if ($i>0) {
                        $j = count($this->levelStack[$i-1][1]) -1;
                        $this->levelStack[$i-1][1][$j][3] = $this->currentContents;
                        $this->currentContents = $this->levelStack[$i-1][1];
                        $this->_htmlcontent .= '</li></ul>';
                    }
                    unset($this->levelStack[$i]);
                }
                $this->_htmlcontent .= '</li></ul>';
            }
            else if(preg_match("/^(\s*)\-\s*(foreword|part|chapter|section)\s*\:\s*\[\[([\w\-\/\.]+)\s*\|(.*)\]\]/", $string, $m)) {
                list(,$level, $type, $pageId, $title) = $m;

                $level = strlen($level);
/*
array(
    array( type, pageId, title,
            array(
                array(type, pageId, title,
                    array(
                        array(type, pageId, title,
                            array(
                            )
                        )
                    )
                )
            )
        ),
);*/

                if ($this->currentLevel === false) {
                    // first line
                    $this->currentLevel = $level;
                    $this->levelStack[0] = array($this->currentLevel, $this->currentContents);
                    $this->currentContents[] = array($type, $pageId, $title, array());
                    $this->_htmlcontent = '<li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                }
                else {
                    if ($this->currentLevel == $level) {
                        // same level, we add the content in the current list of item
                        $this->currentContents[] = array($type, $pageId, $title, array());
                        $this->_htmlcontent = '</li><li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                    }
                    else if ($this->currentLevel < $level) {
                        // level increases
                        // we store in the stack the current values
                        $l = count($this->levelStack) -1;
                        $this->levelStack[$l][1] = $this->currentContents;
                        // new list of items
                        $this->currentContents = array( array($type, $pageId, $title, array()));
                        // we start a new level
                        $this->levelStack[$l+1] = array( $level, $this->currentContents);
                        $this->currentLevel = $level;
                        $this->_htmlcontent = '<ul><li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';

                    }
                    else {
                        // level decreases
                        // update contents of all contents in stack that have a higher level
                        for ($i=count($this->levelStack)-1; $i >= 0; $i --) {
                            if ($this->levelStack[$i][0] > $level) {
                                $this->levelStack[$i][1] = $this->currentContents;
                                if ( $i > 0) {
                                    $this->currentLevel = $this->levelStack[$i-1][0];
                                    $j = count($this->levelStack[$i-1][1]) -1;
                                    $this->levelStack[$i-1][1][$j][3] = $this->currentContents;
                                    $this->currentContents = $this->levelStack[$i-1][1];
                                    $this->_htmlcontent .= '</li></ul>';
                                }
                                else {
                                    $contents = $this->currentContents;
                                    $this->_htmlcontent .= '</li></ul>';
                                }
                                unset($this->levelStack[$i]);
                                continue;
                            }
                            else if ($this->levelStack[$i][0] == $level) {
                                $this->currentContents [] = array($type, $pageId, $title, array());
                                $this->_htmlcontent .= '</li><li class="'.htmlspecialchars($type).'"><a href="'.$this->createLink($pageId).'">'.htmlspecialchars($title).'</a>';
                                break;
                            }
                            else {
                                $this->levelStack[$i+1] = array( $level, array());
                                $this->currentContents = array(array($type, $pageId, $title, array()));
                                $this->currentLevel = $level;
                                break;
                            }
                        }
                    }
                }
            }
            return true;
        }
        else if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>\s*$/',$string,$m)){
            $this->_closeNow = false;
            $this->_htmlcontent = '<ul class="bookcontents">';
            return true;
        }
        else {
            return false;
        }
    }

    protected function createLink($url) {
        if(preg_match("/^[a-zA-Z]+\:\/\//", $url)) {
            return htmlspecialchars($url);
        }
        else if (substr($url, 0,2) == '//') {
            return htmlspecialchars( substr($url, 1));
        }
        else  if (substr($url, 0,1) == '/') {
            $url = $this->engine->getConfig()->basePath . ltrim($url, '/');
        }
        else {
            $c = $this->engine->getConfig();
            $url = $c->basePath . ltrim($c->pagePath, '/') . $url;
        }
        return htmlspecialchars($url);
    }
}


class gtwxhtml_bookinfos extends WikiRendererBloc {

    public $type='bookinfo';
    protected $_openTag='<div class="bookinfos">';
    protected $_closeTag='</div>';
    protected $isOpen = false;
    protected $dktag='bookinfo';
    protected $bookInfos;

    public function open(){
        $this->isOpen = true;
        $this->bookInfos = array(
            'title'=>'',
            'subtitle'=>'',
            'title_short'=>'',
            'authors'=>array(),
            'edition'=>'',
            'copyright'=>array('years'=>array(), 'holders'=>array()),
        );
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        $this->engine->getConfig()->extractedData['bookInfos'] = $this->bookInfos;
        return $this->_closeTag;
    }

    public function getRenderedLine(){
        return $this->_htmlcontent;
    }

    protected $_htmlcontent = '';

    public function detect($string){
        if ($this->isOpen) {
            $this->_htmlcontent = '';
            if(preg_match('/^\s*<\/'.$this->dktag.'>\s*$/',$string,$m)){
                // end tag
                $this->isOpen = false;
                $this->_htmlcontent = "<h1>".htmlspecialchars($this->bookInfos['title'])."</h1>\n";
                if ($this->bookInfos['subtitle'])
                    $this->_htmlcontent = "<h2>".htmlspecialchars($this->bookInfos['subtitle'])."</h2>\n";
                $this->_htmlcontent .=  "<div class=\"authors\">written by <ul>";
                foreach($this->bookInfos['authors'] as $author) {
                    $this->_htmlcontent .=  '<li>'.$author[0].' '.$author[1];
                    $this->_htmlcontent .=  '</li>';
                }
                $this->_htmlcontent .=  '</ul></div>';

                $this->_htmlcontent .=  "<div class=\"copyright\">Copyright ";
                $this->_htmlcontent .=  implode(', ', $this->bookInfos['copyright']['years'])."<br/>";
                $this->_htmlcontent .=  implode(', ', $this->bookInfos['copyright']['holders'])." </div>\n";
                $this->_htmlcontent .=  "</div>\n";
            }
            else if(preg_match("/^\s*(title|subtitle|title_short|author|edition|copyright_years|copyright_holder)\s*=\s*(.*)/", $string, $m)){
                list(,$name,$value)=$m;
                if ($name == 'title') {
                    $this->bookInfos['title'] = $value;
                }elseif($name == 'subtitle') {
                    $this->bookInfos['subtitle'] = $value;
                }elseif($name == 'title_short') {
                    $this->bookInfos['title_short'] = $value;
                }else if($name == 'author') {
                    $this->bookInfos['authors'][] =explode('|', $value);
                }else if($name == 'edition') {
                    $this->bookInfos['edition'] = $value;
                }else if($name == 'copyright_years') {
                    $this->bookInfos['copyright']['years'] = preg_split("/\s*,\s*/", $value);
                }else if($name == 'copyright_holder') {
                    $this->bookInfos['copyright']['holders'][] = $value;
                }
            }
            return true;
        }
        else if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>\s*$/',$string,$m)){
            $this->_closeNow = false;
            $this->_htmlcontent =  "<div class=\"bookinfos\">\n";
            return true;
        }
        else {
            return false;
        }
    }
}


/**
 * ignore <notinbook> tag, only relevant for docbook convertion
 */
class gtwxhtml_notinbook extends WikiRendererBloc {

    public $type='notinbook';
    protected $_openTag='';
    protected $_closeTag='';
    protected $isOpen = false;
    protected $dktag='notinbook';

    public function open(){
        $this->isOpen = true;
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        return $this->_closeTag;
    }

    public function detect($string){
        if($this->isOpen){
            if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$string,$m)){
                $this->_detectMatch=$m[1];
                $this->isOpen=false;
            }else{
                $this->_detectMatch=$string;
            }
            return true;

        }else{
            if(preg_match('/^\s*<'.$this->dktag.'( \w+)?>(.*)/',$string,$m)){
                if(preg_match('/(.*)<\/'.$this->dktag.'>\s*$/',$m[2],$m2)){
                    $this->_closeNow = true;
                    $this->_detectMatch=$m2[1];
                }
                else {
                    $this->_closeNow = false;
                    $this->_detectMatch=$m[2];
                }
                return true;
            }else{
                return false;
            }
        }
    }

    public function getRenderedLine(){
       return $this->_renderInlineTag($this->_detectMatch);
    }
}

class gtwxhtml_bookpagelegalnotice extends gtwxhtml_notinbook {

    public $type='bookpagelegalnotice';
    protected $_openTag='<div class="booklegalnotice bookpagelegalnotice">';
    protected $_closeTag='</div>';
    protected $dktag='bookpagelegalnotice';
    protected $storageName = 'bookPageLegalNotice';
    protected $legalNotice = '';

    public function open(){
        $this->isOpen = true;
        $this->legalNotice = '';
        return $this->_openTag;
    }

    public function close(){
        $this->isOpen=false;
        $this->engine->getConfig()->extractedData[$this->storageName] = $this->legalNotice;
        return $this->_closeTag;
    }

    public function getRenderedLine(){
        $html = $this->_renderInlineTag($this->_detectMatch);
        $this->legalNotice .= $html;
        return ''; // we don't want display on the first page
    }
}

class gtwxhtml_booklegalnotice extends gtwxhtml_bookpagelegalnotice {
    public $type='booklegalnotice';
    protected $_openTag='<div class="booklegalnotice">';
    protected $dktag='booklegalnotice';
    protected $storageName = 'bookLegalNotice';
    public function getRenderedLine(){
        $html = $this->_renderInlineTag($this->_detectMatch);
        $this->legalNotice .= $html;
        return $html;
    }
}



class gtwxhtml_alternatelang extends WikiRendererBloc {

    public $type='alternatelang';
    protected $regexp="/^\s*~~LANG:([^~]*)~~\s*$/";

    protected $_openTag='';
    protected $_closeTag='';
    protected $_closeNow = true;



    public function getRenderedLine(){
        // Syntax is :   LANG@id:page,LANG2@id:page2
        $langs = preg_split('/ *, */',trim($this->_detectMatch[1]));
        $data = array();

        foreach ($langs as $langdesc){
          if(preg_match('/^(\w+)@(.+)$/', $langdesc, $m)) {
            $data[$m[1]] = $m[2];
          }
        }
        $conf = $this->engine->getConfig();
        if(isset($conf->extractedData['relative_page_lang']))
            $conf->extractedData['relative_page_lang'] = array_merge( $conf->extractedData['relative_page_lang'], $data);
        else
            $conf->extractedData['relative_page_lang'] = $data;
        return '';
    }

}


class gtwxhtml_code extends WikiTag {
    protected $name='code';
    public $beginTag='@@';
    public $endTag='@@';

    public function getContent(){
        $match = $this->wikiContentArr[0];
        $tag='<code>';
        $endtag ='</code>';
        if(strlen($match) > 2 && $match[1] == '@') {
            $code = substr($match,2);
            $tag=$endtag='';
            $type= $match[0];
            if($type=='V') {
                $tag='<var>';
                $endtag='</var>';
            }
            else if($type=='K'){
                $tag='<kbd>';
                $endtag='</kbd>';
            }
            else if(isset($this->code_types[$type])) {
                $tag = '<code class="'.$this->code_types[$type].'">';
                $endtag ='</code>';
            }
            else {
                $tag='<code>';
                $code = substr($match,2,-2);
                $endtag ='</code>';
            }
        }
        else {
            $code = $match;
            $tag='<code>';
            $endtag ='</code>';
        }
        return $tag.htmlspecialchars($code).$endtag;
    }
    protected $code_types = array(
        'A'=>'attribute', //tag class="attribute"
        'C'=>'classname',
        'T'=>'constant',
        'c'=>'command',
        'E'=>'element', //tag class="element"
        'e'=>'envar',
        'F'=>'filename', // class="devicefile|directory"
        'f'=>'function',
        'I'=>'interfacename',
        'K'=>'keycode',
        'L'=>'literal',
        'M'=>'methodname',
        'P'=>'property',
        'R'=>'returnvalue',
        'V'=>'varname',
    );
    public function isOtherTagAllowed() {
        return false;
    }
}
