<?php
/**
 * job's name: fix_broken_images
 * job's description: to fix links to broken, not-existed files
 *
 * @return new content to update, or null to cancel and skip
 */

 $JOBS_NAMES['fix_broken_images'] = 'إزالة الصور المعطوبة';




function fix_broken_images($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler

	if(empty($xvalues) || sizeof($xvalues) < 2){
		return null;
	}

	$regex_replace = $simple_replace = $regex_replace_callback = array();

	$broken_image = array();
	$broken_image['original']  = $xvalues[1];

	$default_image = 'DefautAr.svg';

	/*
	* Mediawiki uses \xE2\x80\x8E is the left-to-right marker and
	* \xE2\x80\x8F is the right-to-left marker.
	* so we should make sure we have those too in our search word
	* @see https://gerrit.wikimedia.org/r/#/c/24888/
	*/
	if(strpos($broken_image['original'], '،_') !== false){
		$broken_image['rlm'] = str_replace('،_', "،_\xE2\x80\x8F", $broken_image['original']);
	}

	/**
	 * check if there is any latin chars. as áéíóúÁÉÍÓÚñÑ & make it as wikipedia
	 */
	 if(preg_match('![áéíóúÁÉÍÓÚñÑ]!u', $broken_image['original'])){
		 if(!empty($broken_image['rlm'])){
			 $broken_image['latin_rlm'] = urlencode(rawurldecode($broken_image['rlm']));
		 }
		 $broken_image['latin'] = urlencode(rawurldecode($broken_image['original']));
	 }

	 #well! sometimes different.
	 if(preg_match('![\W]!u', $broken_image['original'])){
		$broken_image['rawurlencode'] = rawurlencode($broken_image['original']);
 	}

	$first_char = function_exists('mb_substr') ?  mb_substr($broken_image['original'], 0, 1) : substr($broken_image['original'], 0, 1);
	if(preg_match('![a-z]!i', $first_char)){
		$func = strtolower($first_char) == $first_char ? 'ucfirst' : 'lcfirst';

		if(!empty($broken_image['rlm'])){
			$broken_image['lucase_rlm'] = $func($broken_image['rlm']);
		}
		if(!empty($broken_image['latin'])){
			$broken_image['lucase_latin'] = $func($broken_image['latin']);
		}

		if(!empty($broken_image['latin_rlm'])){
			$broken_image['lucase_latin_rlm'] = $func($broken_image['latin_rlm']);
		}

		$broken_image['lucase'] = $func($broken_image['original']);

	}

	#\p{C} is hidden rlm & lrm unicode chars
	$broken_image = array_map(function($v){
		return str_replace(array('_', '\'',), array('\p{C}*(\h{1,2}|_|\%20)\p{C}*', '(\'|\&\#39;|%27)'), preg_quote($v));
	}, $broken_image);



	//NOTE:
	//given title is always with _ underscore but this underscore can be :
	//- a space ! like name_name => name name
	//- %20 which is also a space
	//- or just an underscore

	//أبو_عبد_الرحمن_البيلاوي ? not working
	//أبکنار_(تشهار_فريضة) : fixed before
	//أتاون_(غيبوثكوا) :  it has rtl mark, not our problem
	// آين_راند : fixed before

	#------> [1]
	# this one is to replace the image inside an info templates, usually is a bar | followed by a word or two, then an equal sign then the image
	#-> | صورة           = [[File:لانا القسوس - يسعد صباحك.jpg|thumb|برنامج يسعد صباحك]]
	#-> |الصورة = [[ملف:SpacePowerLogo.png‏]]
	#-> |صورة المدينة        = file:///C:/Documents%20and%20Settings/Metro-PC/Desktop/Capture.JPG
	#-> | شعار        = '''شبابية...عربية'''
	#-> |صورة البلدة      = xxxxx
	#-> |شعار              = 13423793_116928615396858_3054192231346875588_n
	#-> | غلاف                   = [[ملف:SHl front.jpg|300px]]
	#-> |خريطة المدينة      = Zaragoza, Spain location.png
	#->  | image       = Adam Mitchell (Doctor Who).jpg
	#->  | image_shield        = Castiglione_Messer_Raimondo-Stemma.png
	#->  | صورة الشعار = Agence Spatiale Algerienne.jpg
	#->  | صورة المدينة   =Aguilasmurcia.JPG، ‏Aguilas.jpg
	#->(two lines)  | الصورة               =
	// [[ملف:Al-Nasr Sports Club.png|تصغير|شعار نادي النصر دبي]]


	$template_attributes_accept_images = array('coat of arms',
											'علم بلد لاحق\d',
											'خريطة',
											'صورة شعار',
											'صورة علم',
											'علم بلد سابق\d',
											'الغلاف',
											'image_flag',
											'image_alt',
											'image_skyline',
											'map',
											'لقطة',
											'صورة',
											'ملف',
											'صورة الشعار',
											'صورة المدينة',
											'خريطة المدينة',
											'الصورة',
											'صورة البلدة',
											'شعار',
											'الشعار',
											'شعار\d',
											'غلاف',
											'Image\d',
											'Image',
											'image',
											'image_seal',
											'image_map',
											'image_map\d',
											'image\d',
											'صورة\d',
											'image_shield',
											'logo_image',
											'علم',
											'العلم',
											'اسم_صورة',
											'علامة_الشركة',
											'image_photo',
											'logo',
											'التوقيع',
											'توقيع',
											'coa_pic',
											'house\d',
											'structure\d',
											'motto',
											'شعار الحزب',
											'شعار_الحزب',
											'kitimage',
											'الخريطة',
											'cover',
											'image_plan',
											'ribbon',
											'علامة_الشركة',
											'أيقونة توضيحية',
											'خريطة الانتشار',
											'الختم',
											// 'burmesename',

										);

	#experimental, baad decision I admit
	// $regex_replace['!\p{C}*\-\p{C}*!u'] = '-';
	// $regex_replace['!\p{C}*\:\p{C}*!u'] = ':';


	#([\w\d_ ]+)
	$regex_replace['!\|(\h*)('.implode('|', $template_attributes_accept_images).')(\h*)=\s*[File\:|file\:|ملف\:|Image\:|image\:]*('.implode('|', $broken_image).')\h*([^\=\n]+(?=[\|\n]))*!u'] = '|$1$2$3= '; //$1 = DefautAr.svg

//	| صورة              = File:test img 0.jpg|thu,qwfmx-b,w|wqwfqw=wqfwqf|qwfqw212=qwfqwf

	// preg_match('!\|(\h*)('.implode('|', $template_attributes_accept_images).')(\h*)=\s*[File\:|file\:|ملف\:|Image\:|image\:]*(?:'.implode('|', $broken_image).')(?:\h*\|[^=]+(?<bar>\|+))*!u', $content, $m);
	// print_r($m);

	//FIAV_111111_.svg
	//{{صندوق معلومات علم
	if(preg_match('!^FIAV_(.*?)_*\.svg$!', $xvalues[1], $matches)){
		$regex_replace['!\|(\h*)Use(\d*)(\h*)=\s*'.preg_quote(str_replace('_', ' ', $matches[1])).'!u'] = '|$1Use$2$3= '; //$1 = DefautAr.svg
	}

	if(strpos($content, '{{Infobox Burmesestatedivision') !== false){
		$word = trim($xvalues[1]);
		if(($flagstrpos = strrpos($xvalues[1], 'flag.png')) > -1){
			$word = substr($xvalues[1], 0, $flagstrpos);
		}

		$regex_replace['!\|(\h*)flag(\h*)=\s*'.str_replace('_', '( |_)', preg_quote($word)).'!u'] = '|$1flag$2= '; //$1 = DefautAr.svg
	}
//

	//footbal&basketball stuff
	if(preg_match('!^Kit_(left|body|right|shorts|socks)(_arm)*(.*?)\.(svg|png)$!', $xvalues[1], $matches)){
		//  var_export($matches);
		$p = array('left'=>'la', 'body'=>'b', 'right'=>'ra', 'shorts'=>'sh?', 'socks'=>'so');
		$regex_replace['!\|(\h*)([a|h]{0,1}_{0,1}pattern_'.$p[$matches[1]].'\d*)(\h*)=\s*'.str_replace('_', '(\h{1,2}|_)', preg_quote($matches[3])).'!u'] = '|$1$2$3= '; //$1 = DefautAr.svg
	}

	#------> [2]
	#the image/file template
	#-> [[ملف:100 riyals 2.jpg|190x190بك]]
	#-> [[ملف:404083 262541717172186 1257219408 n.jpg|تصغير|علامات الوقف والضبط في المصحف الشريف]]
	#->  [[ملف:Al-Hasakeh3.jpg|تصغير|300بك|ملعب تشرين بالحسكة (البلدي)]].
	#->  [[ملف:نمایی از ورزشگاه 12 هزار نفری.JPG|تصغير|يسار|ملعب آزادي المغلق]]
	#->  [[ملف:واجهة الكتاب ج 01|تصغير|[[File:واجهة 01.png|thumb|]]]]
	#-> [[File:ElBalttagi 1 (25399707812).jpg|thumb|محمود عزت & [[آسر ياسين]]<br>مسلسل البلطجي]]

	$regex_replace['!\[\[\h*(ملف|File|file|Image|صورة)[\h\p{C}]*\:[\h\p{C}]*('.implode('|', $broken_image).')\]\]!u'] = '';# [[ملف:DefautAr.svg$3]]



	#Internal link to an image or media file without displaying the file contents
	#[[ميديا:الصهيونية]]
	$regex_replace['!\[\[\h*(\:file|\:File|media|Media|\:Image|\:image|\:صورة|\:ملف|ميديا)\h*\:\h*('.implode('|', $broken_image).')\h*\]\]!u'] = '$2';#
	$regex_replace['!\[\[\h*(\:file|\:File|media|Media|\:Image|\:image|\:صورة|\:ملف|ميديا)\h*\:\h*('.implode('|', $broken_image).')\h*\|\h*([^\]]*)\]\]!u'] = '$3';



	#------> [3]
	#mostly flags, between {{...}}
	#-> {{صورة رمز علم|2016 علم جلمدج.png}}
	#-> {{معرض صور|Otto_Paul_Hermann_Diels.jpg|70|أوتو ديلس|كيميائي}}
	#-> {{صوت|Dak Lak.ogg|listen|help=no}}
	$regex_replace['!\{\{\h*(audio|صورة رمز علم|معرض صور|صوت|صورة إيقونة علم|flagicon image|ص\.م\/صورة)\h*\|\h*(ملف\:|File\:|file\:|ميديا\:|Image\:)*\h*('.implode('|', $broken_image).')([^\}\}]*+|(?R))*\}\}!u'] = '';# {{$1|DefautAr.svg$3}}






	//well! last try
	// $regex_replace['!(\[\[|\:|\||\=)\h*(ملف|File|file|صورة)\h*\:\h*('.implode('|', $broken_image).')\h*\|+!u'] = '$1$2:'.$default_image.'|';# [[ملف:DefautAr.svg$3]]



//
	#------> [4]
	#images inside the gallery tag! if i kill myself, this tag is the reason
	#-><gallery>
	// file:///C:/Users/LENOVO/Documents/papa.jpg
	// </gallery>
	// <gallery>
	// Example.jpg|تعليق1
	// Example.jpg|تعليق2
	// </gallery>
	// <gallery>
	// Exampl
	// <gallery>
	// Examp
	// <gallery>
	// <gallery>
	// ملف:0 Avos 982 Macao.jpg|0 آفوس، سنة 982
	// ملف:0 Avos 988 Macao.jpg|0 آفوس، سنة 988
	// ملف:20 Avos 982 Macao.jpg|20 Avos, 982
	// </gallery>
	// <gallery>
	// ملف:0849 pilar ebro 2004.png|سرقسطة من نهر [[إبرة (توضيح)|]]
	// ملف:La Aljafería - Patio de Santa Isabel 03.JPG|[[قصر الجعفرية]]
	// </gallery>


	$regex_replace_callback['!<gallery([^>]*)>(.*?)<\/gallery>!us'] = function($m) use ($broken_image){
		// return 'xxxx';
		if(trim($m[2]) == ''){
			return $m[0];
		}

		return '<gallery'.$m[1].'>'.preg_replace('![file\:|ملف\:|Image\:|ميديا\:|File\:|صورة\:]*('.implode('|', $broken_image).')([^\n]*)!u', '', $m[2], 1) . '</gallery>'; # ملف:DefautAr.svg$2
	 };






	$new_content = str_replace(array_keys($simple_replace), array_values($simple_replace), $content);
	$new_content = preg_replace(array_keys($regex_replace), array_values($regex_replace), $new_content, 1);
	if(sizeof($regex_replace_callback)){
		foreach($regex_replace_callback as $k=>$v){
				$new_content = preg_replace_callback($k, $v, $new_content);
			}
	}

	# [[Image:image.jpg|thumb|[[link]] in description]]
	# complicated and can not be done using regex
	preg_match('!\[\[\h*(ملف|File|file|Image|صورة)[\h\p{C}]*\:[\h\p{C}]*('.implode('|', $broken_image).')\|!u', $content, $img_starts, PREG_OFFSET_CAPTURE);
	if(!empty($img_starts) && $img_starts[0][1]> 0){
		// echo $img_starts[0][0]."\n";
		// $img_brackets_start_from = $img_starts[0][1];
		$img_brackets_start_from = mb_strlen(substr($content, 0, $img_starts[0][1]));

		preg_match('!\]\]!u', $content, $bsqw, PREG_OFFSET_CAPTURE, $img_starts[0][1] + strlen($img_starts[0][0]));
		$first_end_Bracket_after_offset = 0;
		if(!empty($bsqw)){
			var_export($bsqw);
			$first_end_Bracket_after_offset = intval($bsqw[0][1]);
			if($first_end_Bracket_after_offset > 0){
				$first_end_Bracket_after_offset = mb_strlen(substr($content, 0, $first_end_Bracket_after_offset))+2;
			}
		}

		preg_match_all('!\[\[!u', $content, $link_starts, PREG_OFFSET_CAPTURE, $img_starts[0][1] + strlen($img_starts[0][0]));
		preg_match_all('!\]\]!u', $content, $link_ends, PREG_OFFSET_CAPTURE, $img_starts[0][1] + strlen($img_starts[0][0]));

		$link_brackets_start_from = array_map(function($x) use($content) { return mb_strlen(substr($content, 0, $x[1])); }, $link_starts[0]);
		$link_brackets_end_at = array_map(function($x)  use($content) { return mb_strlen(substr($content, 0, $x[1])); }, $link_ends[0]);

		$img_brackets_end_at = $first_end_Bracket_after_offset;
		if(sizeof($link_brackets_end_at)){
			if(sizeof($link_brackets_start_from) == 0){
				$img_brackets_end_at  = $link_brackets_end_at[0]+2;
			}else{
				if($link_brackets_end_at[0] < $link_brackets_start_from[0]){
					$img_brackets_end_at = $link_brackets_end_at[0]+2;
				}
			}
		}

		if(sizeof($link_brackets_start_from)){
			if($first_end_Bracket_after_offset > 0 && $first_end_Bracket_after_offset < $link_brackets_start_from[0]){
				$link_brackets_start_from = array();
			}

			$last_start = 0;
			for ($i = 0; $i < sizeof($link_brackets_start_from); $i++) {

				#start 247
				#end 268
				#end 290 <--- wanted
				#end 31
				#start 293
				if($last_start > 0 && $last_start > $img_brackets_start_from){
					$output = array_intersect($link_brackets_end_at , range($last_start, $link_brackets_start_from[$i]));
					if(sizeof($output) > 1){
						$img_brackets_end_at = array_pop($output)+2;
						break;
					}
				}

				$last_start = $link_brackets_start_from[$i];
			}
		}

		if($img_brackets_end_at != 0 && $img_brackets_start_from != 0){
			// echo "\n".'$img_brackets_start_from:'. $img_brackets_start_from . "\n";
			// echo "\n".'$img_brackets_end_at:'. $img_brackets_end_at . "\n";
			#1: make sure that difference btw $img_brackets_end_at and $img_brackets_start_from is not big
			#2: replace it with none
			#since there is no mb_substr_replace, we use this approache
			if($img_brackets_end_at > $img_brackets_start_from && $img_brackets_end_at - $img_brackets_start_from < 300){
				$first_con = mb_substr($content, 0, $img_brackets_start_from);
				$rest_con = mb_substr($content, $img_brackets_end_at);
				$new_content =  $first_con.''.$rest_con;
			}
		}
	}

	if($content === $new_content){
		return null;
	}else{

		// $new_content = preg_replace('!^\h*\|{0,1}\h*(صورة|ملف|صورة الشعار|صورة المدينة|خريطة المدينة|الصورة|صورة البلدة|شعار|غلاف|image|image_shield|logo_image|علم)\h*(\:|=)\h*$!um', '', $new_content);
		//if there is empty galleries after change, remove 'em
		$new_content = preg_replace('!<(gallery)[^>]*>([\s\n]*)<\/(gallery)>!u', '', $new_content);
	}

	return $new_content;
}

