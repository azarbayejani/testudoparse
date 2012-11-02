var jsdom = require('jsdom')
var fs = require('fs');
var async = require('async');
var $ = require('jquery');


var array = fs.readFileSync('deptcodes').toString().split("\n");

fullSchedule = {};


var returnArray = function(errors,window,callback){
  
  var $listOfClasses = $($("td font")[2]).children("font");

  var classes = new Object();

  // iterate over list of classes
  $.each($listOfClasses,
      function(){

        
        var name = $(this).children("b")[0];
        if(name !== undefined ){
          name = name.textContent;

          // create an object for this class
          classes[name] = [];
          $sectionsForCourse = $($($(this).next()).next());

          // iterate over sections
          $.each($sectionsForCourse.children("b"),
            function(){

              sectionName = $(this).find("dl").text().split("\n")[1];
              //console.log(name + ": " + sectionName);

              // TODO: some of these have more than one dd (do we care)
              var scheduleInfo = $(this).find("dd").text();
              var tmp = scheduleInfo.split(/\.+\s?|\s\(|\)/);

              var daysOfWeek = tmp[0];
              var time = tmp[1];
              var buildingInfo = tmp[2];

              var currSection = {
                "section" : sectionName,
                "meetingTime" : [tmp[1],tmp[0]],
                "location" : buildingInfo 
              };

              classes[name].push(currSection);
              //console.log(name + ": " + currSection);

            });
        }
      }
  );
  return classes;
  callback();
}


var doParse = function(departmentCode,callback){
  jsdom.env({
    html: 'http://www.sis.umd.edu/bin/soc?term=201301&crs=' + departmentCode,
    src: [
    ],
    done: 
      function(errors,window){
        if(errors){
          callback(errors);
          return;
        }
        fullSchedule[departmentCode] = returnArray(errors,window,callback);
      }
  });
}

async.forEach(array,doParse,function(err){
  console.log(fullSchedule);
});



