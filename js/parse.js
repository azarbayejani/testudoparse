var jsdom = require('jsdom');
var fs = require('fs');
var async = require('async');
var jquery = fs.readFileSync("./jquery.min.js").toString();

var array = fs.readFileSync('../deptcodes').toString().split("\n");

fullSchedule = {};

Array.zipArray = function(arr){
  var created = [];
  for(i = 0; i < arr.length/2; i+=2){
    created.push([arr[i],arr[i+1]]);
  }
  return created;
};

Array.makeAssoc = function(arr){
  var created = [];
  for(i = 0; i < arr.length; i++){
    //console.log(arr[i].nodeName);
    if( i+1 < arr.length && 1 ){
      if( arr[i+1].nodeName === "BLOCKQUOTE" ){
        created.push([arr[i],arr[i+1]]);
        i++;
      }
      //console.log(arr[i].nodeName);
    }else{
      created.push([arr[i]]);
    }
  }
  return created;
};

var returnArray = function(errors,window,callback){
  $ = window.jQuery;

  var $classesAndSections = $("font b").parent();

  var zipped = Array.zipArray($classesAndSections);
  classMap = {};

  // iterate over list of classes
  $.each(zipped,
    function(){

    // this has a space in its name
    className = $(this[0]).children("b")[0].textContent;
    console.log(className);
    sectionMap = {};

    var $sections = $(this[1]).find('dl');

    $.each($sections,
      function(){
        var sectionInfo = $(this).text().split(/\n+/).slice(1,4);
        var sectionNo = sectionInfo[0].slice(0,sectionInfo[0].indexOf("("));
        var classLocation = sectionInfo[2].slice(sectionInfo[2].indexOf("("));
        sectionMap[sectionNo] = classLocation;
      }
    );
    classMap[className] = sectionMap;
  });

  return classMap;
};

var returnArrayFix = function(errors,window,callback){
  $ = window.jQuery;

  var $classesAndSections = $("font b").parents();

  console.log($classesAndSections.length);


  var zipped = Array.makeAssoc($classesAndSections);
  classMap = {};

  // iterate over list of classes
  $.each(zipped,
    function(){

    // this has a space in its name
    className = $(this[0]).children("b")[0].textContent;

    if(this.length == 2){
      sectionMap = {};
      var $sections = $(this[1]).find('dl');

      $.each($sections,
        function(){
          var sectionInfo = $(this).text().split(/\n+/).slice(1,4);
          var sectionNo = sectionInfo[0].slice(0,sectionInfo[0].indexOf("("));
          var classLocation = sectionInfo[2].slice(sectionInfo[2].indexOf("("));
          sectionMap[sectionNo] = classLocation;
        }
      );
      classMap[className] = sectionMap;
    }else{
      classMap[className] = 1;
    }
  });

  return classMap;
};

var doParse = function(departmentCode,callback){
  jsdom.env({
    //html: 'http://www.sis.umd.edu/bin/soc?term=201301&crs=' + departmentCode,
    html : '../aasp.html',
    src: [
    jquery
    ],
    done: function(errors,window){
      if(errors){
        callback(errors);
        return;
      }
      fullSchedule[departmentCode] = returnArrayFix(errors,window);
      callback();
    }
  });
};

async.forEach(["aasp"],doParse,function(err){
  console.log(fullSchedule);
});


