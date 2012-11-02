// array of classes (<font>) and their sections (<blockquote>)
var $classesAndSections = $("font b").parent();

Array.zipArray = function(arr){
	if(arr.length % 2 != 0 ){
		return null;
	}
	var created = [];
	for(i = 0; i < arr.length/2; i+=2){
		created.push([arr[i],arr[i+1]]);
	}
	return created;
}

var zipped = Array.zipArray($classesAndSections);
classMap = {};

$.each(zipped,
	function(){

		// this has a space in its name
		className = $(this[0]).children("b")[0].textContent;
		sectionMap = {};

		var $sections = $(this[1]).find('dl');
		$.each($sections,
			function(){
				var sectionInfo = $(this).text().split(/\n+/).slice(1,4);
				var sectionNo = sectionInfo[0].slice(0,sectionInfo[0].indexOf("("));
				var classLocation = sectionInfo[2].slice(
					sectionInfo[2].indexOf("("));
				sectionMap[sectionNo] = classLocation;
			}
		)
		classMap[className] = sectionMap;
	}
);

console.log(classMap);