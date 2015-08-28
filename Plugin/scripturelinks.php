<?php
/***************************************************************************************************
 Title          ScriptureLinks - Content Plugin for Joomla
 Author         Mike Leeper
 Version        2.5x
 Copyright      © by Mike Leeper
 License        This is free software and you may redistribute it under the GPL.
                Scripture_Links comes with absolutely no warranty. For details, see the 
                license at http://www.gnu.org/licenses/gpl.txt
                YOU ARE NOT REQUIRED TO KEEP COPYRIGHT NOTICES IN
                THE HTML OUTPUT OF THIS SCRIPT. YOU ARE NOT ALLOWED
                TO REMOVE COPYRIGHT NOTICES FROM THE SOURCE CODE.
  Description:  Creates links to bible references in your content items on your site.
                Clicking on the resulting link will pop-up the verse in an online bible database.
                Optional audio link and bible commentaries as well.
                See ScriptureLinks configuration for usage information.
  Credits:
              * Bible references provided by BibleGateway (http://www.biblegateway.com)
              * Commentaries provided by EWordToday (http://ewordtoday.com)
              * Tooltips by WalterZorn.de (http://www.walterzorn.de/en/tooltip/tooltip_e.htm)              
***************************************************************************************************
*/
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );
class plgContentScriptureLinks extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$document	= JFactory::getDocument();
		$docType = $document->getType();
		if($docType != 'html') return; 
     $document	= JFactory::getDocument();
      $document->addCustomTag('<script type="text/javascript">');
      $document->addCustomTag('    var WStyle1 = "width=800,height=500,scrollbars=1";');
      $document->addCustomTag('    var WStyle2 = "width=400,height=200";');
      $document->addCustomTag('</script>');
    $use_tooltip = $this->params->get('use_tooltip');
    if($use_tooltip) {
      ?>
      <script type="text/javascript" src="plugins/content/scripturelinks/scripturelinks/wz_tooltip/wz_tooltip.js"></script>
      <script type="text/javascript" src="plugins/content/scripturelinks/scripturelinks/wz_tooltip/tip_balloon.js"></script>
      <?php
    }
		$this->loadLanguage();
	}
	public function onContentPrepare($context, &$row, &$params, $page = 0)
  {
    $bible_version = $this->params->get( 'bible_version' );
    $replace_all = $this->params->get( 'replace_all' );
    $row->text = $this->sl_utf8dec($row->text);
    if($replace_all){
      $books = $this->sl_getBooksRegex($bible_version);
      $st = "/(?=\S)(\{bible\s\d{1,2}\})?\b(".$books.")(\s)(\d{1,3})([:,](?=\d))?(\d{1,3})?[-]?(\d{1,3})?([,](?=\d))?(\d{1,3})?([:](?=\d))?(\d{1,3})?[-]?(\d{1,3})?(\{\/bible\})?/iu";
      $row->text = preg_replace_callback( $st, array( &$this, 'sl_link_insert'), $row->text );
      $row->text = html_entity_decode($row->text, ENT_COMPAT, 'UTF-8');
      return true;
    } else {
      $st =  "#{bible\s*(.*?)}(.*?){/bible}#";
      $row->text = preg_replace_callback( $st, array( &$this, 'sl_link_insert'), $row->text );
      $row->text = html_entity_decode($row->text, ENT_COMPAT, 'UTF-8');
      return true;
    }
   }
  private function sl_link_insert(&$matches)
  {
    $bible_version = $this->params->get( 'bible_version' );
    $replace_all = $this->params->get( 'replace_all' );
    $audio_link = $this->params->get( 'audio_link' );
    $audio_source_aid = $this->params->get( 'audio_source_aid' );
    $com_link = $this->params->get( 'com_link' );
    $com_source = $this->params->get( 'com_source' );
    $audio_source_aid = explode(",",$audio_source_aid);
    $source = $audio_source_aid[0];
    $aid = $audio_source_aid[1];
    $use_sb = $this->params->get( 'use_sb' );
    $use_tooltip = $this->params->get( 'use_tooltip' );
    $tooltip_type = $this->params->get( 'tooltip_type' );
    $fontcolor = $this->params->get( 'font_color', '#333' );
    $bgcolor = $this->params->get( 'bg_color', '#ddd' );
    if($use_sb){
  		JHtml::_('behavior.modal');
      $linkattribs['onclick'] = "SqueezeBox.open(this, {handler: \'iframe\', size: {x: 800, y: 500}});return false;";
      $linkattribs['title'] = '{STRING}';
      $alinkattribs['onclick'] = "SqueezeBox.open(this, {handler: \'iframe\', size: {x: 400, y: 200}});return false;";
      $alinkattribs['title'] = JText::_('PLG_SCRIPTURELINKS_AUDIO').' {STRING}';
    } else {
      $document	= JFactory::getDocument();
      $document->addCustomTag('<script type="text/javascript">');
      $document->addCustomTag('    var WStyle1 = "width=800,height=500,scrollbars=1";');
      $document->addCustomTag('    var WStyle2 = "width=400,height=200";');
      $document->addCustomTag('</script>');
      $linkattribs['onclick'] = "window.open(this.href,this.target,WStyle1);return false;";
      $linkattribs['rel'] = 'nofollow';
      $linkattribs['title'] = '{STRING}';
      $alinkattribs['onclick'] = "window.open(this.href,this.target,WStyle2);return false;";
      $alinkattribs['rel'] = 'nofollow';
      $alinkattribs['title'] = JText::_('PLG_SCRIPTURELINKS_AUDIO').' {STRING}';
    }
    if($replace_all){
      $scripture = $matches[0];
      $btst = "#{bible\s*(.*?)}#";
      preg_match($btst, $scripture, $btmatches);
      if(count($btmatches)>0){ 
        $bible_version = $btmatches[1];
        $scripture = preg_replace('#{\/?bible\s?\d?\d?}#i','',$scripture);
        if($btmatches[1] == 0) return $scripture;
      }
    } elseif(!$replace_all){
      if($matches[1] == "" || $matches[1] == 0){
        if($matches[1] == 0) $scripture = preg_replace('#{\/?bible\s?\d?\d?}#i','',$scripture);
        return $scripture;
      }
      $bible_version = $matches[1];
      $scripture = $matches[2];
    }
    $books = $this->sl_getBooksRegex($bible_version);
    $st = "/(?=\S)(".$books.")(&nbsp;|\s)(\d{1,3})(:\d{1,3}-\d{1,3}|:\d{1,3}|&nbsp;|\s)?[-,]?(\d{1,3})?[,]?(\d{1,3})?[:]?(\d{1,3})?(&nbsp;|\s|-)?(\d{1,3})?\b/iu";
    $alink = preg_replace_callback( $st, array( &$this, 'sl_split_verse'), str_replace(".","",$scripture) );
    $alink = explode(",",$alink); 
    $scripture_link = "";
    $chklang = "";
    $biblebooks = $this->sl_getBooks($bible_version);
    if($use_tooltip){
      preg_match('/(\p{N}*)?\s?(\p{L}*)\.?\s?([\p{N}\.\:\,\-]*)?/i', $scripture, $smatch);
      $key = $this->sl_array_search_recursive( $smatch[1].' '.$smatch[2], $biblebooks, true, $bible_version);
      if(!empty($key[0])) {$ucslinkstrg = ucwords($biblebooks[$key[0]]['book']).' '.$smatch[3];}else{$ucslinkstrg = $scripture;}
      $slinktext = JText::_('PLG_SCRIPTURELINKS_READVERSE')." ".$ucslinkstrg;
      $linkattribs['title'] = $slinktext;
      $slink = '<center>'.JHTML::_('link', JRoute::_('http://www.biblegateway.com/passage/index.php?search='.urlencode($ucslinkstrg).';&version='.$bible_version.';&interface=print'), $ucslinkstrg, $linkattribs).'&nbsp;{CSTRING}{ASTRING}</center>';
      $chklang = $this->sl_getLang($bible_version);
      if($com_link && $chklang == 'EN'){
        $clink = preg_replace_callback( $st, array(&$this, 'sl_getverse_com'), $scripture );
        $clink = explode(",",$clink); 
        $clink[1] == 'index' ? $clink_chap = '' : $clink_chap = ' '.$clink[1];
        $clinkstr = str_replace(".","",$clink[0]);
        $clinkstrg = preg_replace('/(\d)\s?(\w+)/i', '${1} $2', $clinkstr);
        $key = $this->sl_array_search_recursive($clinkstrg, $biblebooks, true, $bible_version);
        $linkattribs['title'] = $slinktext;
        $comtext = JText::_('PLG_SCRIPTURELINKS_COMMENTARY');
        $linkattribs['title'] = $comtext." ".ucwords($biblebooks[$key[0]]['book']).$clink_chap;
        if(!empty($key[0])) {$ucclinkstrg = str_replace(" ","",$biblebooks[$key[0]]['book']);}else{$ucclinkstrg = str_replace(" ","",$clinkstrg);}
        $comlink = ' '.JHTML::_('link', JRoute::_('http://ewordtoday.com/comments/'.$ucclinkstrg.'/'.$com_source.'/'.$ucclinkstrg.$clink[1].'.htm'), '<img style="width:16px; height:12px;" src="'.JURI::base().'plugins/content/scripturelinks/scripturelinks/comments.gif" border="0" />', $linkattribs);
        $slink = str_replace('{CSTRING}',$comlink,$slink);
      } else {
        $slink = str_replace('{CSTRING}','',$slink);
      }
      if($audio_link){
        $alink[0] == $alink[1] ? $averse = $alink[0] : $averse = $alink[0].'-'.$alink[1];
        $audtext = JText::_('PLG_SCRIPTURELINKS_AUDIO');
        if(!empty($alink[2])) {$ucslinkstrg = ucwords($biblebooks[$alink[2]]['book']).' '.$smatch[3];}else{$ucslinkstrg = $alink[3]." ".$averse;}
        $alinkattribs['title'] = $audtext." ".$ucslinkstrg;
        $audlink = ' '.JHTML::_('link', JRoute::_('http://www.biblegateway.com/resources/audio/flash_play.php?source='.$source.'&aid='.$aid.'&book='.rtrim($alink[2]).'&chapter='.rtrim($alink[0],":").'&end_chapter='.rtrim($alink[1],":")), '<img style="width:16px; height:9px;" src="http://www.biblegateway.com/resources/audio/images/sound.gif" border="0" />', $alinkattribs);
        $slink = str_replace('{ASTRING}',$audlink,$slink);
      } else {
        $slink = str_replace('{ASTRING}','',$slink);
      }
      $slink = htmlspecialchars(htmlentities($slink,ENT_COMPAT,'UTF-8'));
      $balloonimgpath = JURI::base().'plugins/content/scripturelinks/scripturelinks/wz_tooltip/tip_balloon/';
      $linkattribs['onmouseover'] = "Tip('".$slink."',STICKY,true,DURATION,8000,BALLOON,".$tooltip_type.",BALLOONIMGPATH,'$balloonimgpath',ABOVE,true,CLICKCLOSE,true,CENTERMOUSE,true,FADEIN,800,FADEOUT,800,FONTCOLOR,'$fontcolor',BGCOLOR,'$bgcolor');";
      $linkattribs['onmouseout'] = "UnTip();";
      $linkattribs['class'] = 'modal';
      $linkattribs['onclick'] = '';
      $linkattribs['rel'] = "{handler: 'iframe', size: {x: 800, y: 500}}";
      $scripture_link = JHTML::_('link', JRoute::_('http://www.biblegateway.com/passage/index.php?search={QUERY};&version='.$bible_version.';&interface=print'), '{STRING}', $linkattribs);
      $scripture_link = str_replace('{STRING}', !$this->sl_checkutf8($bible_version) ? htmlentities(trim($scripture)) : trim($scripture), $scripture_link);
      $scripture_link = str_replace('{QUERY}', !$this->sl_checkutf8($bible_version) ? htmlentities(str_replace('&nbsp;',' ',urlencode($this->sl_customCharHandling($ucslinkstrg,$bible_version)))) : str_replace('&nbsp;',' ',urlencode($this->sl_customCharHandling($ucslinkstrg,$bible_version))), $scripture_link);
    } else {
      if($use_sb){
        $linkattribs['onclick'] = '';
        $linkattribs['class'] = 'modal';
        $linkattribs['rel'] = "{handler: 'iframe', size: {x: 800, y: 500}}";
        $alinkattribs['onclick'] = '';
        $alinkattribs['class'] = 'modal'; 
        $alinkattribs['rel'] = "{handler: 'iframe', size: {x: 400, y: 200}}";
      }
      preg_match('/(\p{N}*)?\s?(\p{L}*)\.?\s?([\p{N}\.\:\,\-]*)?/i', $scripture, $smatch);
      $key = $this->sl_array_search_recursive( $smatch[1].' '.$smatch[2], $biblebooks, true, $bible_version);
      if(!empty($key[0])) {$ucslinkstrg = ucwords($biblebooks[$key[0]]['book']).' '.$smatch[3];}else{$ucslinkstrg = $scripture;}
      $slinktext = JText::_('PLG_SCRIPTURELINKS_READVERSE')." ".$ucslinkstrg;
      $linkattribs['title'] = $slinktext;
      $scripture_link = JHTML::_('link', JRoute::_('http://www.biblegateway.com/passage/index.php?search={QUERY};&version='.$bible_version.';&interface=print'), '{STRING}', $linkattribs);
      $scripture_link = str_replace('{STRING}', !$this->sl_checkutf8($bible_version) ? htmlentities(trim($scripture)) : trim($scripture), $scripture_link);
      $scripture_link = str_replace('{QUERY}', !$this->sl_checkutf8($bible_version) ? htmlentities(str_replace('&nbsp;',' ',urlencode($this->sl_customCharHandling($ucslinkstrg,$bible_version)))) : str_replace('&nbsp;',' ',urlencode($this->sl_customCharHandling($ucslinkstrg,$bible_version))), $scripture_link);
      if($audio_link){
        $scripture_link .= ' '.JHTML::_('link', JRoute::_('http://www.biblegateway.com/resources/audio/flash_play.php?source='.$source.'&aid='.$aid.'&book='.rtrim($alink[2]).'&chapter='.rtrim($alink[0],":").'&end_chapter='.rtrim($alink[1],":")), '<img style="width:16px; height:9px;" src="http://www.biblegateway.com/resources/audio/images/sound.gif" border="0" />', $alinkattribs);
        $alink[0] == $alink[1] ? $averse = $alink[0] : $averse = $alink[0].'-'.$alink[1];
        if(!empty($alink[2])) {$ucslinkstrg = ucwords($biblebooks[$alink[2]]['book']).' '.$smatch[3];}else{$ucslinkstrg = $alink[3]." ".$averse;}
        $scripture_link = str_replace('{STRING}', $ucslinkstrg, $scripture_link);
      }
      $chklang = $this->sl_getLang($bible_version);
      if($com_link && $chklang == 'EN'){
        $clink = preg_replace_callback( $st, array(&$this, 'sl_getverse_com'), str_replace(".","",$scripture) );
        $clink = explode(",",$clink); 
        $clink[1] == 'index' ? $clink_chap = '' : $clink_chap = ' '.$clink[1];
        $clinkstrg = preg_replace('/(\d+)\s?(\w+)/i', '${1} $2', $clink[0]);
        $key = $this->sl_array_search_recursive($clinkstrg, $biblebooks, true, $bible_version);
        preg_match('/(\d)?\s?(\w+)\.?\s?([\d\.\:\,\-]+)?/i', $scripture, $smatch);
        $linkattribs['title'] = JText::_('PLG_SCRIPTURELINKS_COMMENTARY').' '.ucwords($biblebooks[$key[0]]['book']).' '.$smatch[3];
        if(!empty($key[0])) {$ucclinkstrg = str_replace(" ","",$biblebooks[$key[0]]['book']);}else{$ucclinkstrg = str_replace(" ","",$clinkstrg);}
        $scripture_link .= ' '.JHTML::_('link', JRoute::_('http://ewordtoday.com/comments/'.$ucclinkstrg.'/'.$com_source.'/'.$ucclinkstrg.$clink[1].'.htm'), '<img style="width:16px; height:12px;" src="'.JURI::base().'plugins/content/scripturelinks/scripturelinks/comments.gif" border="0" />', $linkattribs);
      }
    }
   return $scripture_link;
  }
  private function sl_split_verse(&$match)
  {
    $bible_version = $this->params->get( 'bible_version' );
    $book = strtolower($match[1]);
    $biblebooks = $this->sl_getBooks($bible_version);
    $key = $this->sl_array_search_recursive($book, $biblebooks, true, $bible_version);
    $cstart = $match[3];
    if(isset($match[6]) && $match[6] != ""){
      $cstop = $match[6];
    }elseif(isset($match[5]) && $match[5] != ""){
      $cstop = $match[5];
    }else{
      $cstop = $match[3];
    }
    $linkstr = "";
    $linkstr = $cstart.','.$cstop.','.$key[0].','.$match[1];
    return $linkstr;
  }
  private function sl_getverse_com(&$match)
  {
    $bible_version = $this->params->get( 'bible_version' );
    $book = preg_replace("/\s|&nbsp;/","",strtolower($match[1]));
    if($book == 'songofsongs') $book = 'song';
    $cstart = $match[3];
    if(!isset($match[3]) && $match[3] != "") $cstart = 1;
    $comlinkstr = "";
    $comlinkstr = $book.','.$cstart.','.$match[1].','.$match[0];
    return $comlinkstr;
  }
  private function sl_array_search_recursive( $needle, $haystack, $type=false, $bible_version )
  {
     $path = NULL;
     $keys = array_keys($haystack);
     while (!$path && (list($toss,$k)=each($keys))) {
        $v = $haystack[$k];
        if (is_scalar($v)) {
          if($bible_version == 19) $v = htmlentities($v,ENT_COMPAT,'UTF-8');
          if(!$this->sl_checkutf8($bible_version))  $v = htmlentities($v);
           if (strtolower($v) === strtolower(trim($needle))) {
              $path = array($k);
           }
        } elseif (is_array($v)) {
           if ($path = $this->sl_array_search_recursive( $needle, $v, true, $bible_version )) {
              array_unshift($path,$k);
           }
        }
     }
     return $path;
  }
  private function sl_getBooksRegex($bible_version){
    $langfile = strtolower($this->sl_getLang($bible_version));
    $lang = JFactory::getLanguage();
    $lang->load( 'plg_scripturelinks_biblebooks', JPATH_ROOT.'/plugins/content/scripturelinks/scripturelinks', $langfile); 
    $books = ''.JText::_('SLBIBLEBOOK1').'|'.JText::_('SLBIBLEBOOK1ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK2').'|'.JText::_('SLBIBLEBOOK2ABR').'\.?|'.                          
            ''.JText::_('SLBIBLEBOOK3').'|'.JText::_('SLBIBLEBOOK3ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK4').'|'.JText::_('SLBIBLEBOOK4ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK5').'|'.JText::_('SLBIBLEBOOK5ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK6').'|'.JText::_('SLBIBLEBOOK6ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK7').'|'.JText::_('SLBIBLEBOOK7ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK8').'|'.JText::_('SLBIBLEBOOK8ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK9').'|'.JText::_('SLBIBLEBOOK9ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK10').'|'.JText::_('SLBIBLEBOOK10ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK11').'|'.JText::_('SLBIBLEBOOK11ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK12').'|'.JText::_('SLBIBLEBOOK12ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK13').'|'.JText::_('SLBIBLEBOOK13ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK14').'|'.JText::_('SLBIBLEBOOK14ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK15').'|'.JText::_('SLBIBLEBOOK15ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK16').'|'.JText::_('SLBIBLEBOOK16ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK17').'|'.JText::_('SLBIBLEBOOK17ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK18').'|'.JText::_('SLBIBLEBOOK18ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK19').'|'.JText::_('SLBIBLEBOOK19ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK20').'|'.JText::_('SLBIBLEBOOK20ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK21').'|'.JText::_('SLBIBLEBOOK21ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK22').'|'.JText::_('SLBIBLEBOOK22ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK23').'|'.JText::_('SLBIBLEBOOK23ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK24').'|'.JText::_('SLBIBLEBOOK24ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK25').'|'.JText::_('SLBIBLEBOOK25ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK26').'|'.JText::_('SLBIBLEBOOK26ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK27').'|'.JText::_('SLBIBLEBOOK27ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK28').'|'.JText::_('SLBIBLEBOOK28ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK29').'|'.JText::_('SLBIBLEBOOK29ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK30').'|'.JText::_('SLBIBLEBOOK30ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK31').'|'.JText::_('SLBIBLEBOOK31ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK32').'|'.JText::_('SLBIBLEBOOK32ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK33').'|'.JText::_('SLBIBLEBOOK33ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK34').'|'.JText::_('SLBIBLEBOOK34ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK35').'|'.JText::_('SLBIBLEBOOK35ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK36').'|'.JText::_('SLBIBLEBOOK36ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK37').'|'.JText::_('SLBIBLEBOOK37ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK38').'|'.JText::_('SLBIBLEBOOK38ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK39').'|'.JText::_('SLBIBLEBOOK39ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK40').'|'.JText::_('SLBIBLEBOOK40ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK41').'|'.JText::_('SLBIBLEBOOK41ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK42').'|'.JText::_('SLBIBLEBOOK42ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK43').'|'.JText::_('SLBIBLEBOOK43ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK44').'|'.JText::_('SLBIBLEBOOK44ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK45').'|'.JText::_('SLBIBLEBOOK45ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK46').'|'.JText::_('SLBIBLEBOOK46ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK47').'|'.JText::_('SLBIBLEBOOK47ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK48').'|'.JText::_('SLBIBLEBOOK48ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK49').'|'.JText::_('SLBIBLEBOOK49ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK50').'|'.JText::_('SLBIBLEBOOK50ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK51').'|'.JText::_('SLBIBLEBOOK51ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK52').'|'.JText::_('SLBIBLEBOOK52ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK53').'|'.JText::_('SLBIBLEBOOK53ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK54').'|'.JText::_('SLBIBLEBOOK54ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK55').'|'.JText::_('SLBIBLEBOOK55ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK56').'|'.JText::_('SLBIBLEBOOK56ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK57').'|'.JText::_('SLBIBLEBOOK57ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK58').'|'.JText::_('SLBIBLEBOOK58ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK59').'|'.JText::_('SLBIBLEBOOK59ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK60').'|'.JText::_('SLBIBLEBOOK60ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK61').'|'.JText::_('SLBIBLEBOOK61ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK62').'|'.JText::_('SLBIBLEBOOK62ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK63').'|'.JText::_('SLBIBLEBOOK63ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK64').'|'.JText::_('SLBIBLEBOOK64ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK65').'|'.JText::_('SLBIBLEBOOK65ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK66').'|'.JText::_('SLBIBLEBOOK66ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK67').'|'.JText::_('SLBIBLEBOOK67ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK68').'|'.JText::_('SLBIBLEBOOK68ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK69').'|'.JText::_('SLBIBLEBOOK69ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK70').'|'.JText::_('SLBIBLEBOOK70ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK71').'|'.JText::_('SLBIBLEBOOK71ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK72').'|'.JText::_('SLBIBLEBOOK72ABR').'\.?|'.
            ''.JText::_('SLBIBLEBOOK73').'|'.JText::_('SLBIBLEBOOK73ABR').'\.?|';
    $this->sl_checkutf8($bible_version) ? $bookstr = $books : $bookstr = htmlentities($books);
    if($bible_version == 19) return htmlentities($bookstr,ENT_COMPAT,'UTF-8');
    return $bookstr;
  }
  private function sl_getBooks($bible_version)
  {
    $langfile = strtolower($this->sl_getLang($bible_version));
    $lang = Jfactory::getLanguage();
    $lang->load( 'plg_scripturelinks_biblebooks', JPATH_ROOT.'/plugins/content/scripturelinks/scripturelinks', $langfile); 
    $biblebooks = array (
        1 => array ('abrev' => ''.JText::_('SLBIBLEBOOK1ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK1').''),         
        2 => array ('abrev' => ''.JText::_('SLBIBLEBOOK2ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK2').''),                
        3 => array ('abrev' => ''.JText::_('SLBIBLEBOOK3ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK3').''),
        4 => array ('abrev' => ''.JText::_('SLBIBLEBOOK4ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK4').''),         
        5 => array ('abrev' => ''.JText::_('SLBIBLEBOOK5ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK5').''),         
        6 => array ('abrev' => ''.JText::_('SLBIBLEBOOK6ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK6').''),
        7 => array ('abrev' => ''.JText::_('SLBIBLEBOOK7ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK7').''),         
        8 => array ('abrev' => ''.JText::_('SLBIBLEBOOK8ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK8').''),               
        9 => array ('abrev' => ''.JText::_('SLBIBLEBOOK9ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK9').''),               
        10 => array ('abrev' => ''.JText::_('SLBIBLEBOOK10ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK10').''),               
        11 => array ('abrev' => ''.JText::_('SLBIBLEBOOK11ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK11').''),
        12 => array ('abrev' => ''.JText::_('SLBIBLEBOOK12ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK12').''),
        13 => array ('abrev' => ''.JText::_('SLBIBLEBOOK13ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK13').''),      
        14 => array ('abrev' => ''.JText::_('SLBIBLEBOOK14ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK14').''),      
        15 => array ('abrev' => ''.JText::_('SLBIBLEBOOK15ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK15').''),      
        16 => array ('abrev' => ''.JText::_('SLBIBLEBOOK16ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK16').''),             
        17 => array ('abrev' => ''.JText::_('SLBIBLEBOOK17ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK17').''),
        18 => array ('abrev' => ''.JText::_('SLBIBLEBOOK18ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK18').''),
        19 => array ('abrev' => ''.JText::_('SLBIBLEBOOK19ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK19').''),      
        20 => array ('abrev' => ''.JText::_('SLBIBLEBOOK20ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK20').''),      
        21 => array ('abrev' => ''.JText::_('SLBIBLEBOOK21ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK21').''),      
        22 => array ('abrev' => ''.JText::_('SLBIBLEBOOK22ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK22').''),      
        23 => array ('abrev' => ''.JText::_('SLBIBLEBOOK23ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK23').''),           
        24 => array ('abrev' => ''.JText::_('SLBIBLEBOOK24ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK24').''),            
        25 => array ('abrev' => ''.JText::_('SLBIBLEBOOK25ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK25').''),
        26 => array ('abrev' => ''.JText::_('SLBIBLEBOOK26ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK26').''),  
        27 => array ('abrev' => ''.JText::_('SLBIBLEBOOK27ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK27').''),               
        28 => array ('abrev' => ''.JText::_('SLBIBLEBOOK28ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK28').''),               
        29 => array ('abrev' => ''.JText::_('SLBIBLEBOOK29ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK29').''),               
        30 => array ('abrev' => ''.JText::_('SLBIBLEBOOK30ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK30').''),
        31 => array ('abrev' => ''.JText::_('SLBIBLEBOOK31ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK31').''),    
        32 => array ('abrev' => ''.JText::_('SLBIBLEBOOK32ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK32').''),             
        33 => array ('abrev' => ''.JText::_('SLBIBLEBOOK33ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK33').''),             
        34 => array ('abrev' => ''.JText::_('SLBIBLEBOOK34ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK34').''),
        35 => array ('abrev' => ''.JText::_('SLBIBLEBOOK35ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK35').''),      
        36 => array ('abrev' => ''.JText::_('SLBIBLEBOOK36ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK36').''),      
        37 => array ('abrev' => ''.JText::_('SLBIBLEBOOK37ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK37').''),      
        38 => array ('abrev' => ''.JText::_('SLBIBLEBOOK38ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK38').''),        
        39 => array ('abrev' => ''.JText::_('SLBIBLEBOOK39ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK39').''),      
        40 => array ('abrev' => ''.JText::_('SLBIBLEBOOK40ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK40').''),      
        41 => array ('abrev' => ''.JText::_('SLBIBLEBOOK41ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK41').''),      
        42 => array ('abrev' => ''.JText::_('SLBIBLEBOOK42ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK42').''),             
        43 => array ('abrev' => ''.JText::_('SLBIBLEBOOK43ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK43').''),
        44 => array ('abrev' => ''.JText::_('SLBIBLEBOOK44ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK44').''),          
        45 => array ('abrev' => ''.JText::_('SLBIBLEBOOK45ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK45').''),           
        46 => array ('abrev' => ''.JText::_('SLBIBLEBOOK46ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK46').''),
        47 => array ('abrev' => ''.JText::_('SLBIBLEBOOK47ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK47').''),        
        48 => array ('abrev' => ''.JText::_('SLBIBLEBOOK48ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK48').''),      
        49 => array ('abrev' => ''.JText::_('SLBIBLEBOOK49ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK49').''),      
        50 => array ('abrev' => ''.JText::_('SLBIBLEBOOK50ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK50').''),      
        51 => array ('abrev' => ''.JText::_('SLBIBLEBOOK51ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK51').''),      
        52 => array ('abrev' => ''.JText::_('SLBIBLEBOOK52ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK52').''),               
        53 => array ('abrev' => ''.JText::_('SLBIBLEBOOK53ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK53').''),
        54 => array ('abrev' => ''.JText::_('SLBIBLEBOOK54ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK54').''),
        55 => array ('abrev' => ''.JText::_('SLBIBLEBOOK55ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK55').''),       
        56 => array ('abrev' => ''.JText::_('SLBIBLEBOOK56ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK56').''),            
        57 => array ('abrev' => ''.JText::_('SLBIBLEBOOK57ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK57').''),
        58 => array ('abrev' => ''.JText::_('SLBIBLEBOOK58ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK58').''),      
        59 => array ('abrev' => ''.JText::_('SLBIBLEBOOK59ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK59').''),       
        60 => array ('abrev' => ''.JText::_('SLBIBLEBOOK60ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK60').''),       
        61 => array ('abrev' => ''.JText::_('SLBIBLEBOOK61ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK61').''),
        62 => array ('abrev' => ''.JText::_('SLBIBLEBOOK62ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK62').''),
        63 => array ('abrev' => ''.JText::_('SLBIBLEBOOK63ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK63').''),
        64 => array ('abrev' => ''.JText::_('SLBIBLEBOOK64ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK64').''),     
        65 => array ('abrev' => ''.JText::_('SLBIBLEBOOK65ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK65').''),              
        66 => array ('abrev' => ''.JText::_('SLBIBLEBOOK66ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK66').''),
        67 => array ('abrev' => ''.JText::_('SLBIBLEBOOK67ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK67').''),
        68 => array ('abrev' => ''.JText::_('SLBIBLEBOOK68ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK68').''),
        69 => array ('abrev' => ''.JText::_('SLBIBLEBOOK69ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK69').''),
        70 => array ('abrev' => ''.JText::_('SLBIBLEBOOK70ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK70').''),
        71 => array ('abrev' => ''.JText::_('SLBIBLEBOOK71ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK71').''),
        72 => array ('abrev' => ''.JText::_('SLBIBLEBOOK72ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK72').''),
        73 => array ('abrev' => ''.JText::_('SLBIBLEBOOK73ABR').'', 'book' => ''.JText::_('SLBIBLEBOOK73').''));
    return $biblebooks;
  }
  private function sl_getLang($bv)
  {
        $langarray = array (
            1 => array('booknum' => '94','lang' => 'AMU'),
            2 => array('booknum' => '28','lang' => 'AR'),
            3 => array('booknum' => 'ERV-AR','lang' => 'AR'),
            4 => array('booknum' => '82','lang' => 'BG'),
            5 => array('booknum' => '21','lang' => 'BG'),
            6 => array('booknum' => 'ERV-BG','lang' => 'BG'),
            7 => array('booknum' => '90','lang' => 'CCO'),
            8 => array('booknum' => '98','lang' => 'CKW'),
            9 => array('booknum' => '23','lang' => 'CPF'),
            10 => array('booknum' => '29','lang' => 'CS'),
            11 => array('booknum' => 'B21','lang' => 'CS'),
            12 => array('booknum' => '11','lang' => 'DA'),
            13 => array('booknum' => '54','lang' => 'DE'),
            14 => array('booknum' => '33','lang' => 'DE'),
            15 => array('booknum' => 'NGU-DE','lang' => 'DE'),
            16 => array('booknum' => 'SCH1951','lang' => 'DE'),
            17 => array('booknum' => 'SCH2000','lang' => 'DE'),
            18 => array('booknum' => '10','lang' => 'DE'),
            19 => array('booknum' => '48','lang' => 'EN'),
            20 => array('booknum' => '8','lang' => 'EN'),
            21 => array('booknum' => '45','lang' => 'EN'),
            22 => array('booknum' => '46','lang' => 'EN'),
            23 => array('booknum' => '16','lang' => 'EN'),
            24 => array('booknum' => '63','lang' => 'EN'),
            25 => array('booknum' => '47','lang' => 'EN'),
            26 => array('booknum' => '77','lang' => 'EN'),
            27 => array('booknum' => '9','lang' => 'EN'),
            28 => array('booknum' => '49','lang' => 'EN'),
            29 => array('booknum' => '78','lang' => 'EN'),
            30 => array('booknum' => '76','lang' => 'EN'),
            31 => array('booknum' => '31','lang' => 'EN'),
            32 => array('booknum' => '64','lang' => 'EN'),
            33 => array('booknum' => '50','lang' => 'EN'),
            34 => array('booknum' => '74','lang' => 'EN'),
            35 => array('booknum' => '51','lang' => 'EN'),
            36 => array('booknum' => '65','lang' => 'EN'),
            37 => array('booknum' => '72','lang' => 'EN'),
            38 => array('booknum' => 'WYC','lang' => 'EN'),
            39 => array('booknum' => '15','lang' => 'EN'),
            40 => array('booknum' => 'CEB','lang' => 'EN'),
            41 => array('booknum' => 'ERV','lang' => 'EN'),
            42 => array('booknum' => 'ESVUK','lang' => 'EN'),
            43 => array('booknum' => 'GW','lang' => 'EN'),
            44 => array('booknum' => 'GNT','lang' => 'EN'),
            45 => array('booknum' => 'LEB','lang' => 'EN'),
            46 => array('booknum' => 'NCV','lang' => 'EN'),
            47 => array('booknum' => 'NIV1984','lang' => 'EN'),
            48 => array('booknum' => 'PHILLIPS','lang' => 'EN'),
            49 => array('booknum' => '57','lang' => 'ES'),
            50 => array('booknum' => '41','lang' => 'ES'),
            51 => array('booknum' => '58','lang' => 'ES'),
            52 => array('booknum' => '59','lang' => 'ES'),
            53 => array('booknum' => '42','lang' => 'ES'),
            54 => array('booknum' => '60','lang' => 'ES'),
            55 => array('booknum' => '61','lang' => 'ES'),
            56 => array('booknum' => '6','lang' => 'ES'),
            57 => array('booknum' => 'NBLH','lang' => 'ES'),
            58 => array('booknum' => 'NTV','lang' => 'ES'),
            59 => array('booknum' => 'PDT','lang' => 'ES'),
            60 => array('booknum' => 'RVC','lang' => 'ES'),
            61 => array('booknum' => 'TLA','lang' => 'ES'),
            62 => array('booknum' => '32','lang' => 'FR'),
            63 => array('booknum' => '2','lang' => 'FR'),
            64 => array('booknum' => 'NEG1979','lang' => 'FR'),
            65 => array('booknum' => 'SG21','lang' => 'FR'),
            66 => array('booknum' => '69','lang' => 'GRC'),
            67 => array('booknum' => '68','lang' => 'GRC'),
            68 => array('booknum' => 'SBLGNT','lang' => 'GRC'),
            69 => array('booknum' => '70','lang' => 'GRC'),
            70 => array('booknum' => '81','lang' => 'HE'),
            71 => array('booknum' => '71','lang' => 'HIL'),
            72 => array('booknum' => '62','lang' => 'HR'),
            73 => array('booknum' => '17','lang' => 'HU'),
            74 => array('booknum' => 'ERV-HU','lang' => 'HU'),
            75 => array('booknum' => 'HWP','lang' => 'HU'),
            76 => array('booknum' => '18','lang' => 'IS'),
            77 => array('booknum' => '3','lang' => 'IT'),
            78 => array('booknum' => '55','lang' => 'IT'),
            79 => array('booknum' => '34','lang' => 'IT'),
            80 => array('booknum' => 'NR1994','lang' => 'IT'),
            81 => array('booknum' => 'NR2006','lang' => 'IT'),
            82 => array('booknum' => '103','lang' => 'JAC'),
            83 => array('booknum' => '104','lang' => 'KEK'),
            84 => array('booknum' => '20','lang' => 'KO'),
            85 => array('booknum' => '4','lang' => 'LA'),
            86 => array('booknum' => '122','lang' => 'MK'),
            87 => array('booknum' => '88','lang' => 'MVC'),
            88 => array('booknum' => '107','lang' => 'MVJ'),
            89 => array('booknum' => '56','lang' => 'NDS'),
            90 => array('booknum' => '109','lang' => 'NGU'),
            91 => array('booknum' => '30','lang' => 'NL'),
            92 => array('booknum' => '5','lang' => 'NO'),
            93 => array('booknum' => '35','lang' => 'NO'),
            94 => array('booknum' => '25','lang' => 'PT'),
            95 => array('booknum' => '37','lang' => 'PT'),
            96 => array('booknum' => 'VFL','lang' => 'PT'),
            97 => array('booknum' => '111','lang' => 'QUT'),
            98 => array('booknum' => '14','lang' => 'RO'),
            99 => array('booknum' => 'RMNN','lang' => 'RO'),
            100 => array('booknum' => '13','lang' => 'RU'),
            101 => array('booknum' => '39','lang' => 'RU'),
            102 => array('booknum' => 'ERV-RU','lang' => 'RU'),
            103 => array('booknum' => '40','lang' => 'SK'),
            104 => array('booknum' => '1','lang' => 'SQ'),
            105 => array('booknum' => 'ERV-SR','lang' => 'SR'),
            106 => array('booknum' => '44','lang' => 'SV'),
            107 => array('booknum' => '7','lang' => 'SV'),
            108 => array('booknum' => 'SFB','lang' => 'SV'),
            109 => array('booknum' => '75','lang' => 'SW'),
            110 => array('booknum' => 'ERV-TH','lang' => 'TH'),
            111 => array('booknum' => '43','lang' => 'TL'),
            112 => array('booknum' => '27','lang' => 'UK'),
            113 => array('booknum' => 'ERV-UK','lang' => 'UK'),
            114 => array('booknum' => '113','lang' => 'USP'),
            115 => array('booknum' => '19','lang' => 'VI'),
            116 => array('booknum' => 'BPT','lang' => 'VI'),
            117 => array('booknum' => 'ERV-ZH','lang' => 'ZH'),
            118 => array('booknum' => '80','lang' => 'ZH'),
            119 => array('booknum' => '22','lang' => 'ZH'));
    $keyarr = $this->sl_array_search_recursive($bv, $langarray, true, $bv);
    $key = $keyarr[0];
    $langfile = $langarray[$key]['lang'];
    return $langfile;
  }
  private function sl_checkutf8($bv)
  {
    $arr = array(13,18,19,'BPT',20,21,'ERV-BG',22,27,'ERV-UK',28,'ERV-AR',62,68,69,'SBLGNT',70,80,'ERV-ZH',81,82,122,'ERV-TH');
    if(in_array($bv,$arr)) return true;
    return false;
  }
  private function sl_utf8dec( $s_String )
  {
    $s_String = htmlentities($s_String." ", ENT_COMPAT, 'UTF-8');
    return substr($s_String, 0, strlen($s_String)-1);
  }
  private function sl_customCharHandling($str,$bv){
    if($bv == 88) $str = str_replace("'",'%CB%88',$str);
    return $str;
  }
}
?>