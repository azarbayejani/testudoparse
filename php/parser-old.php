<?php
	#require_once 'top.php';
	#mysql_query ("START TRANSACTION;");
	#mysql_query("UPDATE courses_data SET down = 1");
	
	error_reporting(E_ALL ^ E_NOTICE);
	#$getSemesters = 'SELECT * FROM courses_semesters WHERE `default` = 1 ORDER BY `default` DESC, id DESC';
	#$res = mysql_query ($getSemesters);

	function isSection ($str) {
		if (strlen($str) < 3)
			return false;
		for ($a = 0; $a <3; $a++)
			if (!is_numeric($str{$a}))
				return false;
		return true;
	}
	function parseDate($temp){
		$size = strlen($temp);
		$index = 0;
		$days = array();
		while($index<$size){
			$curr = $temp{$index};
			if($curr=='u')
				array_push($days,'Tu');
			elseif ($curr=='h')
				array_push($days,'Th');
			elseif($curr!='T')
				array_push($days,$temp{$index});
			$index+=1;
		}
		return $days;
	}
	function parseTime($temp){
		$size = strlen($temp);
		$hour;
		$minutes;
		$ampm = 0;
		if($temp{1}==':'){
			$hour = $temp{0};
			$minutes = $temp{2}.$temp{3};
			if($temp{4}=='p')
				$ampm=12;
		}
		else{
			$hour = $temp{0}.$temp{1};
			$minutes = $temp{0};
			$minutes = $temp{3}.$temp{4};
			if($temp{5}=='p' &&!($temp{1}==2))
				$ampm=12;
		}
		return $hour+($minutes/60)+$ampm;
	}

	function convertToNewTeacher($teacher) {
		$nTeach = explode(' ', $teacher);
		$last = $nTeach[1];
		$first = str_replace('.','', $nTeach[0]);
		return trim($last) . ', ' . trim($first);
	}

	#while ($take = mysql_fetch_array($res)) {

  #$SEM = $take['id'];
  $SEM = "201301"
	#mysql_query ('DELETE FROM courses_sections WHERE semester = ' . $SEM);
	#mysql_query ('DELETE FROM courses_times WHERE semester = ' . $SEM);
	
	#$query = mysql_query ("SELECT * FROM courses_list WHERE id > 0 ORDER BY id ASC") or die (mysql_error());
	#echo 'b';
	#while ($row = mysql_fetch_array ($query)) {
    $
    
    $file = "http://www.sis.umd.edu/bin/soc?term={$take['testudo']}&crs={$row['name']}";
		$al = file ($file);
		$parsingLine = 0;
		$parsingTitle = 0;
		$parsingCredits = -1;
		$multiLine = 'no';
		$core1 = '';
		$core2 = '';
		$core3 = '';
		$startDescription = 'no';
		$description = '';
		$i = 1;
		$startSectionParsing = false;
		$section = 0;
		$unique = 0;
		
		foreach ($al as $line) {
			 if ($startSectionParsing) {
				 if (isSection(trim($line))) {
					$section = explode ('(', $line);
					$unique = explode (')', $section[1]);
					$section = $section[0];
					$unique = $unique[0];
					$al = 'gotonextline';
				}
				else if ($al == 'gotonextline' && strstr ($line,'Seats=') && !stristr($line, 'Meet')) {
					$teach = explode ('(', $line);
					$teacher = str_replace('</a>','',$teach[0]);
					$teacher = convertToNewTeacher($teacher);
					$teach = explode ('Seats=', $teach[1]);
					$teach = explode (', Open=', $teach[1]);
					$seats = $teach[0];
					$teach = explode (', Waitlist=', $teach[1]);
					$open = $teach[0];
					$teach = explode (')', $teach[1]);
					$waitlist = $teach[0];
					$al = 'keepGoing';
				}
				else if ($al == 'keepGoing' && stristr ($line, 'meets')) {
					
				}
				else if (strstr($line, '<dd>') && !strstr($line,'arranged') && $al == 'keepGoing') {
					if ($parsingDate != 'true') {
						$parsingLine = str_replace ('</b>', '', $parsingLine);
						$parsingLine = trim(str_replace ("\n",'',$parsingLine));
						$parsingLine = mysql_real_escape_string ($parsingLine);
						$teacher = trim(mysql_real_escape_string ($teacher));
						$fyi = mysql_fetch_array(mysql_query ( "SELECT * FROM courses_courses WHERE dept = {$row['id']} AND course = '$parsingLine' ORDER BY id DESC"));
						if ($fyi['id'] == 0) {
							echo $row['name'] . $parsingLine . '<br />';
							$parsingLine = 0;
							$parsingTitle = '';
							$parsingCredits = -1;
							$core1 = '';
							$core2 = '';
							$core3 = '';
							$description = '';
							$startSectionParsing = false;
							continue;
						}
						$courseName = $row['name'] . $parsingLine;
						mysql_query ("INSERT INTO courses_sections SET course = {$fyi['id']}, number = '$section', unique_num = '$unique', 
							professor = '$teacher', seats = $seats, open = $open, waitlist = '$waitlist', semester = $SEM") or die ("ERROR " . mysql_error());
						mysql_query ("INSERT INTO courses_tracker SET course = '$courseName', section = '$section', num = '$open', max = '$seats'") or die (mysql_error());

					}
					$parsingDate = 'true';
					$strtok = explode ('(<a href=', $line);
					$um = explode ('..', str_replace('<dd>','',$strtok[0]));
					$date = explode ('-', str_replace('.','',str_replace('<dd>','',$strtok[0])));
					$date[0] = ereg_replace ('[MTuWThFS]', '', $date[0]);
					$arrDates = parseDate ($um[0]) ;
					foreach ($arrDates as $datey) {
						$startTime = parseTime (trim(str_replace('-','',$date[0])));
						$endTime = parseTime (trim($date[1]));
						if (count($strtok) > 0 && stristr($strtok[1], 'bld_code')) {
							$building = explode ('bld_code=', $strtok[1]);
							$building = explode ('">', $building[1]);
							$building = $building[0];
						}
						else {
							$building = explode ('Buildings/', $strtok[1]);
							$building = explode ('/', $building[1]);	
							$building = $building[0];
						}
						$room = explode ('</a>', $strtok[1]);
						$room = explode (')', $room[1]);
						$room = $room[0];
					if (!($datey != 'M' && $datey != 'Tu' && $datey != 'W' && $datey != 'Th' &&
							$datey != 'F' && $datey != 'S' && $datey != 'Su' && $datey != 'Sa'))
						mysql_query ("INSERT INTO courses_times SET section  = '$unique', day = '$datey',
									start = $startTime, end = $endTime, building = '$building', room = '$room', semester = $SEM") or die (mysql_error());
					}
				}
				else if (strstr($line, '</dl>')) {
					$parsingDate = 'false';
					$al = '';
				}
				else if (strstr($line,'</blockquote>')) {
					$parsingLine = 0;
					$parsingTitle = '';
					$parsingCredits = -1;
					$core1 = '';
					$core2 = '';
					$core3 = '';
					$description = '';
					$startSectionParsing = false;
				}
			}
			else if (strstr($line, "<b>{$row['name']}")) {
				$parsingLine = 0;
				$parsingTitle = '';
				$parsingCredits = -1;
				$core1 = '';
				$core2 = '';
				$core3 = '';
				$description = '';
				$startSectionParsing = false;
				
				$number = explode ($row['name'], $line);
				$number = explode (' </b>', $number[1]);
				$parsingLine = $number[0];
			}
			else if ($parsingLine > 0) {
				if ($parsingTitle == '') {
					if (!strstr ($line, '</b>'))
						$multiLine = 'yes';
					$parsingTitle = str_replace ("\n", '', str_replace(';','',str_replace('</b>','',str_replace('<b>', '', $line))));
				}			
				else if ($multiLine == 'yes') {
					if (strstr ($line, '</b>'))
						$multiLine = 'no';
					$parsingTitle .= str_replace ("\n", '', str_replace(';','',str_replace('</b>','',str_replace('<b>', '', $line))));
				}
				else if ($parsingCredits == -1) {
					if (stristr($line,'No credit')){
						$parsingCredits = 0;
						$maxCredits = 0;
					}
					else {
						$number = explode('(', $line);
						$number = explode (' ', $number[1]);
						$number = explode ('-', $number[0]);
						$parsingCredits = $number[0];
						if (count($number) > 1)
							$maxCredits = $number[1];
						else
							$maxCredits = $parsingCredits;
					}
				}
				else if (strstr ($line, 'CORE ') && $core1 == '' && $startDescription == 'no') {
					$number = explode('(', $line);
					$number = explode (')', $number[1]);
					$core1 = $number[0];
				}
				else if (strstr ($line, 'CORE ') && $core2 == '' && $startDescription == 'no') {
					$number = explode('(', $line);
					$number = explode (')', $number[1]);
					$core2 = $number[0];
				}
				else if (strstr ($line, 'CORE ') && $core3 == '' && $startDescription == 'no') {
					$number = explode('(', $line);
					$number = explode (')', $number[1]);
					$core3 = $number[0];
				}
				else if (trim($line) == '<br>')
					$startDescription = 'yes';
				else if (strstr($line, '<blockquote>'))
					$startSectionParsing = true;
				else if (strstr ($line, '</font>')) {
					$startDescription = 'no';
				}
				else if ($startDescription == 'yes') {
					$description .= str_replace ("\n", '', $line);
				}
			}
		}
	}
	mysql_query ("DELETE FROM courses_times WHERE day != 'M' AND day != 'Tu' AND day != 'W' AND day != 'Th' AND day != 'F'");

	}
	require_once 'populate_crs.php';
	require_once 'gpa_population.php';
	require_once 'time.php';
	mysql_query ("UPDATE courses_data SET down = 0;");
	mysql_query ("UPDATE courses_data SET parser_run = NOW();");
	mysql_query ("COMMIT;");
	require_once 'bookparser.php';
?>
