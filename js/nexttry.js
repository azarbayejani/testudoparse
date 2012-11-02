/*

This is the thought process:

	1. Get a department's page and extract list of classes
		http://www.sis.umd.edu/bin/soc?term=${TERM}&crs=${DEPT}
	2. For each class on the department page - get the page of the course alone
		http://www.sis.umd.edu/bin/soc?term=${TERM}&crs=${DEPT}${COURSE}
	3. Parse each section for the course.

This ends up being a lot of requests for a simple problem, but it is a pretty good
divide and conquer approach

 */

var jsdom = require('jsdom');
var fs = require('fs');
var async = require('async');
var jquery = fs.readFileSync("./jquery.min.js").toString();
var _ = require('underscore');

var array = fs.readFileSync('../deptcodes').toString().split("\n");

var returnClassArray = function(errors,window){
	$ = window.jQuery;

	if(errors){
		return null;
	}

	$classesAndNames = $('font b:not(:has(*))');
	classArray = [];

	for( i = 0 ; i < $classesAndNames.length ; i++){
		if(i % 3 === 0){
			classArray.push($classesAndNames[i].textContent.trim());
		}
	}

	return classArray;

};

var returnSectionArray = function(errors, window){
	$ = window.jQuery;

	if(errors){
		return null;
	}

	$sections = $('dl');

	sectionArr = [];

	_.each($sections,function($el){
		myText = $el.textContent;
		textArr = _.compact(myText.split('\n'));

		sectionNumbers = textArr[0].split(/\(|\)/);
		sectionNumber = sectionNumbers[0];
		uniqueNumber = sectionNumbers[1];

		classLocation = textArr[2].split(/\(|\)/)[0];

		sectionArr.push(
			{
				'sectionNumber' : sectionNumber,
				'unique' : uniqueNumber,
				'location' : classLocation
			}
		);
	});

	return sectionArr;
};


// returns the sections of a class.
var getSectionsFromClass = function(className,callback){
	jsdom.env({
		html: 'http://www.sis.umd.edu/bin/soc?term=201301&crs=' + className,
		src : [ jquery ],
		done : function(errors,window){
			if(errors){
				callback(errors);
				return;
			}

			console.log(className);


			sections = returnSectionArray(errors,window);

			callback(null,sections);
		}
	});
};

var getClasses = function(departmentCode,callback){
	jsdom.env({
		html : 'http://www.sis.umd.edu/bin/soc?term=201301&crs=' + departmentCode,
		src : [ jquery ],
		done : function(errors, window){
			if(errors){
				callback(errors);
				return;
			}

			var classes = returnClassArray(errors,window);


			// classesAndSections = {};
			
			// async.map(classes,getSectionsFromClass,function(err,results){
			//   classesAndSections = _.object(classes,results);
			// });

			//callback(null,classesAndSections);

			callback(null,classes);
		}
	});
};

_.each(array,function(dept){
	async.waterfall([
		function(callback){

		}
	]);
});


count = 0;

async.waterfall([
	function(callback){
		async.map(array,getClasses,function(err,results){
			count++;
			callback(null, _.flatten(results));
		});
	},
	function(classes,callback){
		async.map(classes,getSectionsFromClass,function(err,results){
			count++;
			classesAndSections = _.object(classes,results);
			callback(null, classesAndSections);
		});
	}

],function(err, results){
	console.log(count);
	fs.writeFileSync('outfile', JSON.stringify(results));
});