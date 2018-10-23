<?php
/**
 * job's name: clean
 * job's description: fix things and clean them
 *
 * @return new content to update, or null to cancel and skip
 * Some ideas were taken from cosmetic_changes.py
 *
 * Statbility: not tested much - 60% stable
 */


 $JOBS_NAMES['clean'] = 'صيانة وتنظيف';



function clean($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler, useful to get access to other functions, like links etc.

	$regex_replace = $simple_replace = $regex_replace_callback = array();

	//-------------------> [1]
	# re-add spaces that were pulled out of the link.
	# Examples:
	#   text[[ title ]]text        -> text [[title]] text [done]
	#   text[[ title | name ]]text -> text [[title|name]] text [done]
	#   text[[ title |name]]text   -> text[[title|name]]text [done]
	#   text[[title| name]]text    -> text [[title|name]]text [done]
	#
	#
	$simple_replace = array(
		'{{المراجع}}' => '{{مراجع}}',
	);

	#dirty regex, but works
	$regex_replace['!([\w\h]*)\[\[(\h+)([\w\h\|]+)(\h+)\]\]([\w\h]*)!u'] = '$1$2[[$3]]$4$5'; # Y [[ X ]] Y => Y  [[X]]  Y
	$regex_replace['!([\w\h]+)\[\[(\h*)([\w\h\|]+)(\h*)\]\]([\w\h]+)!u'] = '$1$2[[$3]]$4$5';# Y [[X ]] Y => Y [[X]]  Y
	$regex_replace['!\[\[([\w\h]+)\|(\h+)([\w\h]+)\]\]!u'] = '$2[[$1|$3]]';# [[X| Y]] => [[X|Y]]
	$regex_replace['!\[\[([\w\h]+)\|([\w\h]+)(\h+)\]\]!u'] = '[[$1|$2]]$3';# [[X|Y ]] => [[X|Y]]
	$regex_replace['!\[\[([\w\h]+)\h+\|([\w\h]+)\]\]!u'] = '[[$1|$2]]';# [[X |Y]] => [[X|Y]]
	$regex_replace['!\[\[\h*تصنيف\h*\:([\w\h]+)\h*\]\]!u'] = '[[تصنيف:$1]]';# [[X:Y ]] => [[X:Y]]



	//-------------------> [2]
	#remove Useless Spaces
	#\h is existed since php 5.2.4
	#TODO check this
	// $regex_replace[replace_except_inside('\h{2,}', array('comment', 'math', 'nowiki', 'pre', 'startspace', 'table', 'template'))] = ' ';# X  Y => X Y
	$regex_replace['!\]\]\h{2,4}(\w+)!u'] = ']] $1';# ]]  Y => ]] Y
	$regex_replace['!(\w+)\h{2,4}\[\[!u'] = '$1 [[';# X  [[ => X [[



	//-------------------> [3]
	#For better readability of section header source code, puts a space
	#between the equal signs and the title. aslo remove colon if exsists
	#Example: ==Section title== becomes == Section title ==
	$regex_replace['!(={1,6})(\h{0,4})([\w\h]+)(\h{0,4})\g<1>!u'] = '$1 $3 $1';# ==X== => == X ==
	$regex_replace['!(={1,6})\h{0,4}([\w\h]+)\h{0,4}\:\h{0,}\g<1>!u'] = '$1 $2 $1';# ==X :== => == X ==



	//-------------------> [4]
	#html_entity_decode — Convert all HTML entities to their applicable characters
	$convert_this_html_chars = array('npsb', 'ndash', 'copy');
	$regex_replace_callback['!\&('.implode('|', $convert_this_html_chars).');!'] = function($m) { return html_entity_decode('&'.$m[1].';');} ;# &ndash; => –


	//-------------------> [duplicate headings]
	$regex_replace_callback['!(={1,3})\h*([\w\h]+)\h*\g<1>(?:<br>|\s+)*\g<1>\h*([\w\h]+)\h*\g<1>!u'] = function($m) {
		if($m[2] == $m[3]){
			return $m[1] . ' ' . trim($m[2]) . ' ' . $m[1];
		}

		return $m[0];
	 };


	//TODO
	//- remove empty sections
	//- remove empty refs
	//- add most of https://phabricator.wikimedia.org/diffusion/PWBO/browse/master/cosmetic_changes.py

	$new_content = str_replace(array_keys($simple_replace), array_values($simple_replace), $content);
	$new_content = preg_replace(array_keys($regex_replace), array_values($regex_replace), $new_content);
	if(sizeof($regex_replace_callback)){
		foreach($regex_replace_callback as $k=>$v){
				$new_content = preg_replace_callback($k, $v, $new_content);
			}
	}

	//------------------> [6]
	//use included Peachy functions
	#$new_content = PeachyAWBFunctions::fixHTML($new_content);
	#$new_content = PeachyAWBFunctions::fixHyperlinking($new_content); #this might need to be imported here and change some terms to arabic
	#$new_content = PeachyAWBFunctions::fixCitations($new_content); #this might need to be imported here and change some terms to arabic


	if($content == $new_content){
		return null;
	}

	return $new_content;
}




//for testing only
if(isset($_GET['test'])){


	$text = '
	{{يتيمة|تاريخ=أغسطس 2013}}

	== عبدالكريم داود البرغوثي Abdel Karim Barghouthi ==
	=== مقدمة ===
	عبدالكريم داود [[عائلة البرغوثي|البرغوثي]]  Abdel Karim Barghouthi من [[رجالات الأردن وفلسطين]] – كان له دور فعال في [[فلسطين]] ومن ثم [[الأردن]] في القرن الماضي على عدة اصعدة، ففي مجال الخدمة المهنية كان من كبار رجالات [[الشرطة الفلسطينية]] الذين ساهموا في بناء المؤسسة العسكرية في [[فلسطين]] تاركا بصماته الادارية في معظم المراكز التي شغلها في مدن [[فلسطين]] في احلك فترات تاريخها قبل الاغتصاب وأُجهضت هذه المؤسسة مع احتلال [[فلسطين]] عام [[1948]] حيث انتقل إلى [[الجيش العربي]] في [[الأردن]] وكان من مؤسسي [[المباحث الأردنية]] التي تطورت إلى [[دائرة المخابرات العامة]] ولاحقا كان من مؤسسي [[الأمن العام الأردني|مديرية الأمن العام]].

	بعد تقاعده من الخدمة العسكرية عمل سنوات عديدة في ادارة عددا من الفنادق الكبرى في [[مدينة عمان]] وكان له نشاطا اجتماعيا مرموقا ومشاركا في العديد من النشاطات الاجتماعية والخيرية.

	كان من دعاة [[الوحدة العربية]] والوحدة الوطنية والولاء للوطن وساهم بجمع شمل [[عائلة البرغوثي]] في [[الأردن]] و[[فلسطين]] والشتات وقام بالاجتماع بابناء العائلة في رحلات إلى دول المهجر المختلفة وتمكن مع من عدد من رجالات العائلة بتأسيس [[رابطة ال البرغوثي]] في اوائل [[الثمانينات]] من القرن الماضي ورأسها عدة دورات وتوفاه الله عام [[1985]].

	=== السيرة الحياتية ===
	ولد في قرية [[ديرغسانة]] من قضاء [[رام الله]] – [[فلسطين]] عام [[1912]]
	التحق مع [[سلاح الفرسان]] بعد انهائه الدراسة في مدرسة القرية.
	التحق مع الشرطة الفلسطينية وتدرج في الرتب العسكرية، حيث خدم في [[الناصره]]، [[صفد]]، [[بيسان]]، [[طبريا]]، [[سلفيت]] و[[طوباس]] إلى أن اصبح مديراً لشرطة [[يافا]].
	التحق في كلية القانون في [[يافا]] وحصل على [[شهادة المحاماة]].
	التحق ب[[الجيش العربي]] الأردني برتبة رائد عام في 1/6/[[1948]] وعمل مساعداً لقائد منطقة [[نابلس]] ثم مساعداً لقائد منطقة [[الخليل]] ثم قائداً لمنطقة [[معان]] ثم [[السلط]] ثم [[الخليل]] ثم [[اربد]] – ومديراً [[للجنة الهدنة]] في القدس ونقل من ملاك [[الجيش العربي]] إلى ملاك [[الأمن العام الأردني|الأمن العام]] بتاريخ 22/9/[[1956]] حيث تدرج بالمناصب لغاية مساعدا لمدير [[الأمن العام الأردني|الأمن العام]]
	اشترك في وضع قوانين [[الأمن العام الأردني|الأمن العام]] المعمول بها حالياً .
	التحق بدورة تدريبية في بريطانيا عام [[1954]]/[[1955]] (High Command Police Officers).
	كان عضواً فاعلاً ومحايداً وأمينا في [[المحكمة العسكرية]] ب[[الأردن]] التي شكلت في 11/4/[[1957]] برئاسة [[عكاش الزبن]] وعضويته إضافة لعضوية [[احمد بسلان]].
	رمج من الخدمة العسكرية خلال خدمته بتاريخ 10/3/[[1959]]
	احتصل خلال خدمته العسكرية على عدة أوسمة منها [[وسام الإستقلال]] من الدرجة الثالثة.

	عمل مديراً لشركة الفنادق والسياحة الأردنية وأشرف على بناء [[فندق إنتركونتيننتال الأردن]] في [[عمان (مدينة)|مدينة عمان]] خلال هذه الفترة لغاية إعادته إلى الخدمة في جهاز [[الأمن العام الأردني|الأمن العام]] بتاريخ 3/3/[[1962]] حيث خدم كمساعد لمدير [[الأمن العام الأردني|الأمن العام]] لحين إحالته للتقاعد برتبة [[عميد]] اعتباراً من 1/12/[[1964]].

	بعد تقاعده من الخدمة العسكرية عام [[1964]] عمل مرة اخرى في القطاع الخاص وتحديدا قطاع الفنادق حيث كان له دور قيادي في إدارة عدد من الفنادق الجديدة في مدينة [[عمان (مدينة)]] ومنها [[فندق إنتركونتيننتال الأردن]] ومديراً ل[[فندق الشرق الاوسط]] ثم [[فندق القدس الدولي]] وشغل خلالها رئيساً ل[[جمعية الفنادق الأردنية]].

	في بداية [[الثمانينات]] من القرن الماضي عمل مع عدد من ابناء العائلة على لم شمل العائلة في [[الأردن]] والمهجر حيث قام بزيارات لهم في [[السعودية]] و[[الخليج العربي]] وعقد اجتماعات معهم توجت بتأسيس [[رابطة ال البرغوثي]] عام [[1982]] حيث كان رئيسها لعدة دورات.

	توفي في [[مدينة عمان]] عام [[1985]].

	=== الخدمة في [[فلسطين]] ===
	خدم كضابط في الشرطة ال[[فلسطين]]ية خلال فترة الانتداب البريطاني في معظم مدن [[فلسطين]] حيث كان في [[سلفيت]] ([[1936]]) و[[طوباس]] ([[1937]]) ثم [[غزة|غزَة]] ( [[1938]]-[[1939]] ) ثم [[صفد]] ([[1940]]-[[1941]]) ثم [[الناصره]] ([[1943]]) و[[بيسان]] و[[طبريا]] عام ([[1944]]) و[[عكا]] ([[1945]]) واخيراً [[يافا]] ( [[1946]]-[[1948]]).

	=== الخدمة في [[الجيش العربي]] ===
	[[1948]] بعد وقوع [[نكبة فلسطين]] انتقل إلى [[طولكرم]] بدلاً من [[لبنان]] (كما كان مقترحاً) والتحق مع [[الجيش العربي]] هناك (قائد مقاطعة [[طولكرم]])
	[[1948]]-[[1949]] حبس 40 يوما لعدم اطاعة الأوامر بإطلاق الرصاص على المتظاهرين بعد [[نكبة فلسطين]] .
	نقل إلى منطقة السامرة - بتاريخ 1/6/[[1948]] حيث عمل مساعدا لقائد منطقة [[نابلس]] انذاك [[محمد المعايطة]]
	نقل إلى قيادة الحرس الوطني بتاريخ 1/5/[[1950]] ثم إلى القيادة العليا بتاريخ 3/7/[[1950]] واعيد إلى منطقة السامرة بتاريخ 6/7/[[1950]]
	نقل إلى منطقة [[معان]] حيث عين قائداً للمنطقة بتاريخ 5/10/[[1950]]
	نقل إلى القيادة العليا بتاريخ 11/11/[[1951]] ومنها قائداً لمنطقة [[البلقاء]] بتاريخ 20/1/[[1952]] وكان رئيس الوزراء انذاك [[توفيق أبو الهدى]]
	نقل إلى منطقة [[الخليل]] حيث عين قائداً للمنطقة بتاريخ 15/1/[[1953]]
	نقل إلى [[اربد]] - منطقة [[عجلون]] حيث عين قائداً للمنطقة بتاريخ 23/3/[[1955]]
	عين قائدا ل[[مدرسة تدريب الشرطة والدرك الأردنية]] بتاريخ 1/2/[[1956]] وكان رئيس الوزراء انذاك [[سمير الرفاعي]]
	عين مديرا [[المباحث العامة الأردنية]] بتاريخ 5/6/[[1956]] وكان رئيس الوزراء انذاك [[سعيد المفتي]]
	وعين مديرا للمباحث العسكرية بتاريخ 1/8/[[1956]] وكان رئيس الوزراء انذاك [[إبراهيم هاشم]]

	=== دوره في المحكمة العسكرية عام 1957 ===
	[[ملف:https://commons.wikimedia.org/wiki/File:Family_(18).jpg|تصغير|يمين|]]
	في عام 1957 كان رئيس المحكمة العسكرية لمحاكمة الشباب المتهمين بالتخطيط بعدة تهم في [[الأردن]] و[[التنظيم غير المشروع]] والتامر على نظام الحكم ، ومحاولة تغيير الكيان ، وحيازة منشورات ممنوعة. كان من المتهمين عددا من شباب [[حركة القوميين العرب|القوميين العرب]] دعاة [[الوحدة العربية|الوحدة]] بين [[الأردن]] و[[سوريا]] وقرأ رئيس المحكمة العسكرية عبدالكريم البرغوثي قرار المحكمة \'\'\' ببراءة المتهمين\'\'\' والذي تضمن تبريرا قانونيا بتبرئتنهم - ليس لعدم وجود ادلة ، ولكن لان الدعوة [[الوحدة العربية|للوحدة العربية]] وخاصة بين [[سورية]] و[[الأردن]] لاتعتبر جريمة ، بل تعتبر مطلبا قوميا - واشار كذلك إلى ادبيات التنظيم التي لم يجد فيها اي نص يخالف ذلك وتمت تبرئة جميع المتهمين سوى واحد حكم عليه بغرامة خمسين ديناراً بتهمة حيازة كتب ممنوعة.
	<ref name="Jaredah" >[
	http://www.aljaredah.com/paper.php?source=akbar&mlf=interpage&sid=11778 جريدة الجريدة
	]</ref>

	كذلك كان عضو المحكمة العسكرية برئاسة [[عكاش الزبن]] لمحاكمة الضباط المتهمين بالتخطيط لانقلاب في [[الأردن]] والمعروفين في حينه ب[[حركة الضباط الأحرار الأردنيين]] حسب رواية [[ضافي الجمعاني|ضافي موسى الجمعاني]]
	<ref name="aklaam" >[
	>[
	http://www.aklaam.net/forum/showthread.php?p=255154 مجلة اقلام الثقافية
	]</ref>

	=== الخدمة في [[الأمن العام الأردني|الأمن العام]] ===
	بعد تعريب [[الجيش العربي]] جرت تعديلات في [[القوات المسلحة الأردنية]] حيث فصلت قوات الشرطة عن قوات [[الجيش العربي]] بتاريخ 14 تموز [[1956]] ونقل إلى ملاك [[الأمن العام الأردني|الأمن العام]] الجديد (الشرطة) بتاريخ 22/9/[[1956]] ورقمه العسكري ( 5 خمسة) وكان [[إبراهيم هاشم]] رئيسا للوزراء في حينه.

	خدم في ديوان [[الأمن العام الأردني|الأمن العام]] من تاريخ 22/9/[[1956]] لحين تعيينه رئيسا ل[[لجنة الهدنة الأردنية]] بتاريخ 21/8/[[1958]] وكان رئيس الوزراء انذاك [[سمير الرفاعي]]

	نقل إلى [[السلط]] قائدا لمنطقة [[البلقاء]] بتاريخ 21/12/[[1958]] حيث رمج من الخدمة بتاريخ 10/3/[[1959]] عندما كان [[بهجت طبارة]] مديرا للامن العام وكان [[سمير الرفاعي]] رئيسا للوزراء.
	<ref name="PSD1" >[
	http://www.psd.gov.jo/arabic/index.php?option=com_content&task=view&id=30&Itemid=99 مدراء الامن العام
	]</ref>
	[[ملف:https://commons.wikimedia.org/wiki/File:Family_(10).jpg|تصغير|يمين|]]اعيد إلى الخدمة في جهاز [[الأمن العام الأردني|الأمن العام]] بتاريخ 3/3/[[1962]] خلال شهر من تكليف الشهيد [[وصفي التل]] بتشكيل وزارته وكان [[كريم اوهان]] مديرا للامن العام.
	<ref name="PSD1" >[http://www.psd.gov.jo/arabic/index.php?option=com_content&task=view&id=30&Itemid=99 مدراء الامن العام ]</ref>

	[[ملف:https://commons.wikimedia.org/wiki/File:As_brigadier_general.jpg|تصغير|يمين|]]
	عين مساعدا لمدير [[الأمن العام الأردني|الأمن العام]] للمنطقة الشمالية بتاريخ 17/12/[[1962]] وكان [[محمد هاشم]] مديرا للامن العام ومساعد مدير [[الأمن العام الأردني|الأمن العام]] للإدارة – بتاريخ 29/6/1963 (وكان [[حكمت مهيار]] حينها مديرا للامن العام) اضافة إلى كونه قائدا لمنطقة [[عجلون]] بتاريخ 7/12/[[1963]] حيث عمل مع رفاقه على رسم [[قانون الأمن العام الأردني|الأمن العام]] الجديد والذي حدد الواجبات ونظم القوة وكان [[الشريف حسين بن ناصر]] رئيسا للوزراء. (صدر القانون تحت رقم 38 لسنة [[1965]]).
	<ref name="PSD3">[
	http://www.psd.gov.jo/arabic/index.php?option=com_content&task=view&id=571&Itemid=384 مراحل تطوير الامن العام
	]</ref>
	<ref name="PSD3">[
	http://www.psd.gov.jo/arabic/index.php?option=com_content&task=view&id=571&Itemid=385 المرحلة الاولى لتطوير الامن العام
	]</ref>

	أحيل للتقاعد برتبة [[عميد]] اعتباراً من 1/12/[[1964]] عندما كان [[راضي العبدالله]] مديرا للامن العام و[[بهجت التلهوني]] رئيسا للوزراء.

	=== العمل في القطاع الخاص ===[[ملف:https://commons.wikimedia.org/wiki/File:%D8%B9%D8%A8%D8%AF%D8%A7%D9%84%D9%83%D8%B1%D9%8A%D9%85_%D8%A7%D9%84%D8%A8%D8%B1%D8%BA%D9%88%D8%AB%D9%8A_%D9%81%D9%8A_%D9%81%D9%86%D8%AF%D9%82_%D8%A7%D9%84%D8%A7%D8%B1%D8%AF%D9%86.jpg|تصغير|يمين|]]
	بعد تقاعده من الخدمة العسكرية عام [[1964]] عمل في القطاع الخاص وتحديدا قطاع الفنادق حيث شغل في ادارة عدد من الفنادق في [[مدينة عمان]] منها [[فندق الانتركونتيننتال الأردن]] ومديرا ل[[فندق الشرق الاوسط]] ثم [[فندق القدس الدولي – ميليا]] وشغل خلالها رئيسا ل[[جمعية الفنادق الأردنية]].
	في بداية [[الثمانينات]] من القرن الماضي عمل مع عدد من ابناء العائلة على لمِ شمل العائلة في [[الأردن]] والمهجر حيث قام بزيارات لهم في السعودية والخليج وعقد اجتماعات معهم توجت بتأسيس [[رابطة ال البرغوثي]] عام [[1982]] حيث كان رئيسها لعدة دورات.

	=== العائلة الشخصية ===
	[[ملف:https://commons.wikimedia.org/wiki/File:Family_(15).jpg|تصغير|يمين|]]
	ولد عام [[1912]] في [[ديرغسانة]] قضاء [[رام الله]] في [[فلسطين]] ووالده داود ابن حسين علي إبراهيم (القاق) [[عائلة البرغوثي|البرغوثي]]  ووالدته ايسة بنت صالح زميروق [[عائلة البرغوثي|البرغوثي]]  من قرية [[ديرغسانة]] وله أخ واحد عبدالله الداود [[عائلة البرغوثي|البرغوثي]]  وكان أكبر منه بنحو سنتين وقد توفي في [[ديرغسانة]] عام [[1989]] وكان لهما اخت واحدة توفيت وهي طفلة صغيرة.

	عام [[1936]] تزوج زينب بنت محمود العشوة [[عائلة البرغوثي|البرغوثي]]  ووالدها من قرية [[ديرغسانة]] ووالدتها صفية [[عائلة البرغوثي|البرغوثي]]  وهي أيضاً من قرية [[ديرغسانة]] ، واخوان واخوات زينب هم محمد ، سليمان ، سعود ومحرم - توفيت زينب عام [[1940]] وله منها زهوة (مواليد [[1937]]) وجهاد (مواليد [[1939]]) .

	بعد وفاة زوجته الاولى زينب وفي عام [[1942]]، تزوج كوثر بنت [[علي الأحمد الزعبي]] ووالدها من سكان [[الناصرة]] ووالدتها صروة وهي من عكا، واخوان واخوات كوثر هم إدريس ، أحمد ، توفيق ، صالح ، صلاح، صبحي ، فاطمة ، نجيَه، سميحة وميَسر. توفيت كوثر عام [[1951]] وله منها سناء (مواليد [[1943]]) وسمير (مواليد [[1945]])

	بعد وفاة زوجته الثانية كوثر ، تزوج نجلاء بنت [[محمد احمد البرغوثي]] عام [[1952]] ووالدها من قرية [[ديرغسانة]] ووالدتها شريفة الزير [[عائلة البرغوثي|البرغوثي]]  من قرية [[كوبر]]، واخوان واخوات نجلاء هم أحمد وشوقي وعدلة وليلى وعبلة وسلوى وله منها اسامه (مواليد [[1953]]) وسهاد (مواليد [[1954]]) واياد (مواليد [[1958]])

	توفي [[عبدالكريم البرغوثي]] (ابوجهاد) في [[مدينة عمان]] [[الأردن]] في 29/1/[[1985]] واقيمت له جنازة عسكرية وشعبية ضخمة ومهيبة شارك فيها المئات من ابناء مدن وقرى [[الأردن]] و[[فلسطين]] الذين عرفوه عن قرب خلال مسيرة حياته المهنية والشخصية وأم المئات من شمال البلاد إلى جنوبها ومن شرقها إلى غربها بيوت العزاء المختلفة التي اقيمت له في [[الأردن]] و[[فلسطين]] و[[الكويت]] و[[المملكة العربية السعودية]].

	=== إشارات مرجعية ===
	{{مراجع}}

	{{شريط بوابات|الأردن|أعلام}}

	[[تصنيف:عسكريون أردنيون]]
	[[تصنيف:قادة عسكريون]]
	[[تصنيف:الجيش الأردني]]
	[[تصنيف:تاريخ الأردن العسكري]]
	[[تصنيف:الضباط الأحرار الأردنيون]]
	[[تصنيف:قوميون عرب]]
	[[تصنيف:صفحات تحتاج تصنيف سنة الوفاة]]
	[[تصنيف:رجالات فلسطين]]
	[[تصنيف:مواليد 1912]]
	[[تصنيف:مواليد ديرغسانة]]
	[[تصنيف:وفيات 1985]]
	[[تصنيف:الأمن العام]]
	[[تصنيف:رجالات الأردن]]
	[[تصنيف:عسكرية الأردن]]
	[[تصنيف:آل البرغوثي]]
	';
	echo clean($text);
}
