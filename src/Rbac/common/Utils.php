<?php
/**
 * Created by PhpStorm.
 * User: rebirth.huang
 * Date: 2018/9/11
 * Time: 19:44
 */

class Utils
{
    static function html($data)
    {
        $magic_quotes=get_magic_quotes_gpc();
        if(is_array($data))
        {
            foreach($data as $k=>$v)
                $data[$k]=self::html($v);
        }
        else
        {
            $data=trim($data);
            $data=strip_tags($data);         //除去字符串中的HTML和PHP标签
            if(!$magic_quotes) $data=addslashes($data);
            $data=htmlspecialchars($data, ENT_QUOTES);   //转换特殊HTML字符编码为字符
            $data=self::checkHtml($data);
        }
        return $data;
    }

    static function checkHtml($html) {
        preg_match_all("/\<([^\<]+)\>/is", $html, $ms);

        $searchs[] = '<';
        $replaces[] = '&lt;';
        $searchs[] = '>';
        $replaces[] = '&gt;';

        if($ms[1]) {
            $allowtags = 'img|a|font|div|table|tbody|caption|tr|td|th|br|p|b|strong|i|u|em|span|ol|ul|li|blockquote|object|param';
            $ms[1] = array_unique($ms[1]);
            foreach ($ms[1] as $value) {
                $searchs[] = "&lt;".$value."&gt;";

                $value = str_replace('&', '_uch_tmp_str_', $value);
                $value = self::dHtmlSpecialChars($value);
                $value = str_replace('_uch_tmp_str_', '&', $value);

                $value = str_replace(array('\\','/*'), array('.','/.'), $value);
                $skipkeys = array('onabort','onactivate','onafterprint','onafterupdate','onbeforeactivate','onbeforecopy','onbeforecut','onbeforedeactivate',
                    'onbeforeeditfocus','onbeforepaste','onbeforeprint','onbeforeunload','onbeforeupdate','onblur','onbounce','oncellchange','onchange',
                    'onclick','oncontextmenu','oncontrolselect','oncopy','oncut','ondataavailable','ondatasetchanged','ondatasetcomplete','ondblclick',
                    'ondeactivate','ondrag','ondragend','ondragenter','ondragleave','ondragover','ondragstart','ondrop','onerror','onerrorupdate',
                    'onfilterchange','onfinish','onfocus','onfocusin','onfocusout','onhelp','onkeydown','onkeypress','onkeyup','onlayoutcomplete',
                    'onload','onlosecapture','onmousedown','onmouseenter','onmouseleave','onmousemove','onmouseout','onmouseover','onmouseup','onmousewheel',
                    'onmove','onmoveend','onmovestart','onpaste','onpropertychange','onreadystatechange','onreset','onresize','onresizeend','onresizestart',
                    'onrowenter','onrowexit','onrowsdelete','onrowsinserted','onscroll','onselect','onselectionchange','onselectstart','onstart','onstop',
                    'onsubmit','onunload','javascript','script','eval','behaviour','expression', 'class');
                $skipstr = implode('|', $skipkeys);
                $value = preg_replace(array("/($skipstr)/i"), '.', $value);
                if(!preg_match("/^[\/|\s]?($allowtags)(\s+|$)/is", $value)) {
                    $value = '';
                }
                $replaces[] = empty($value)?'':"<".str_replace('&quot;', '"', $value).">";
            }
        }
        $html = str_replace($searchs, $replaces, $html);

        return $html;
    }

    /**
     * 把hmlt标签转化成实体格式
     * 参数可以为数组或者字符串
     * @param mix $string
     * @return mix
     */
    static function dHtmlSpecialChars($string) {
        if(is_array($string)) {
            foreach($string as $key => $val) {
                $string[$key] = self::dHtmlSpecialChars($val);
            }
        } else {
            $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1',
                str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
        }
        return $string;
    }
}
