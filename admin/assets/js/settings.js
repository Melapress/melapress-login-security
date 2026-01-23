// used to hold data in a higher scope for popup notices.
var ppmwpNoticeData = {};

var promptInputVal = ''

/*! jQuery Timepicker Addon - v1.6.3 - 2016-04-20
* http://trentrichardson.com/examples/timepicker
* Copyright (c) 2016 Trent Richardson; Licensed MIT */
!function(a){"function"==typeof define&&define.amd?define(["jquery","jquery-ui"],a):a(jQuery)}(function($){if($.ui.timepicker=$.ui.timepicker||{},!$.ui.timepicker.version){$.extend($.ui,{timepicker:{version:"1.6.3"}});var Timepicker=function(){this.regional=[],this.regional[""]={currentText:"Now",closeText:"Done",amNames:["AM","A"],pmNames:["PM","P"],timeFormat:"HH:mm",timeSuffix:"",timeOnlyTitle:"Choose Time",timeText:"Time",hourText:"Hour",minuteText:"Minute",secondText:"Second",millisecText:"Millisecond",microsecText:"Microsecond",timezoneText:"Time Zone",isRTL:!1},this._defaults={showButtonPanel:!0,timeOnly:!1,timeOnlyShowDate:!1,showHour:null,showMinute:null,showSecond:null,showMillisec:null,showMicrosec:null,showTimezone:null,showTime:!0,stepHour:1,stepMinute:1,stepSecond:1,stepMillisec:1,stepMicrosec:1,hour:0,minute:0,second:0,millisec:0,microsec:0,timezone:null,hourMin:0,minuteMin:0,secondMin:0,millisecMin:0,microsecMin:0,hourMax:23,minuteMax:59,secondMax:59,millisecMax:999,microsecMax:999,minDateTime:null,maxDateTime:null,maxTime:null,minTime:null,onSelect:null,hourGrid:0,minuteGrid:0,secondGrid:0,millisecGrid:0,microsecGrid:0,alwaysSetTime:!0,separator:" ",altFieldTimeOnly:!0,altTimeFormat:null,altSeparator:null,altTimeSuffix:null,altRedirectFocus:!0,pickerTimeFormat:null,pickerTimeSuffix:null,showTimepicker:!0,timezoneList:null,addSliderAccess:!1,sliderAccessArgs:null,controlType:"slider",oneLine:!1,defaultValue:null,parse:"strict",afterInject:null},$.extend(this._defaults,this.regional[""])};$.extend(Timepicker.prototype,{$input:null,$altInput:null,$timeObj:null,inst:null,hour_slider:null,minute_slider:null,second_slider:null,millisec_slider:null,microsec_slider:null,timezone_select:null,maxTime:null,minTime:null,hour:0,minute:0,second:0,millisec:0,microsec:0,timezone:null,hourMinOriginal:null,minuteMinOriginal:null,secondMinOriginal:null,millisecMinOriginal:null,microsecMinOriginal:null,hourMaxOriginal:null,minuteMaxOriginal:null,secondMaxOriginal:null,millisecMaxOriginal:null,microsecMaxOriginal:null,ampm:"",formattedDate:"",formattedTime:"",formattedDateTime:"",timezoneList:null,units:["hour","minute","second","millisec","microsec"],support:{},control:null,setDefaults:function(a){return extendRemove(this._defaults,a||{}),this},_newInst:function($input,opts){var tp_inst=new Timepicker,inlineSettings={},fns={},overrides,i;for(var attrName in this._defaults)if(this._defaults.hasOwnProperty(attrName)){var attrValue=$input.attr("time:"+attrName);if(attrValue)try{inlineSettings[attrName]=eval(attrValue)}catch(err){inlineSettings[attrName]=attrValue}}overrides={beforeShow:function(a,b){return $.isFunction(tp_inst._defaults.evnts.beforeShow)?tp_inst._defaults.evnts.beforeShow.call($input[0],a,b,tp_inst):void 0},onChangeMonthYear:function(a,b,c){$.isFunction(tp_inst._defaults.evnts.onChangeMonthYear)&&tp_inst._defaults.evnts.onChangeMonthYear.call($input[0],a,b,c,tp_inst)},onClose:function(a,b){tp_inst.timeDefined===!0&&""!==$input.val()&&tp_inst._updateDateTime(b),$.isFunction(tp_inst._defaults.evnts.onClose)&&tp_inst._defaults.evnts.onClose.call($input[0],a,b,tp_inst)}};for(i in overrides)overrides.hasOwnProperty(i)&&(fns[i]=opts[i]||this._defaults[i]||null);tp_inst._defaults=$.extend({},this._defaults,inlineSettings,opts,overrides,{evnts:fns,timepicker:tp_inst}),tp_inst.amNames=$.map(tp_inst._defaults.amNames,function(a){return a.toUpperCase()}),tp_inst.pmNames=$.map(tp_inst._defaults.pmNames,function(a){return a.toUpperCase()}),tp_inst.support=detectSupport(tp_inst._defaults.timeFormat+(tp_inst._defaults.pickerTimeFormat?tp_inst._defaults.pickerTimeFormat:"")+(tp_inst._defaults.altTimeFormat?tp_inst._defaults.altTimeFormat:"")),"string"==typeof tp_inst._defaults.controlType?("slider"===tp_inst._defaults.controlType&&"undefined"==typeof $.ui.slider&&(tp_inst._defaults.controlType="select"),tp_inst.control=tp_inst._controls[tp_inst._defaults.controlType]):tp_inst.control=tp_inst._defaults.controlType;var timezoneList=[-720,-660,-600,-570,-540,-480,-420,-360,-300,-270,-240,-210,-180,-120,-60,0,60,120,180,210,240,270,300,330,345,360,390,420,480,525,540,570,600,630,660,690,720,765,780,840];null!==tp_inst._defaults.timezoneList&&(timezoneList=tp_inst._defaults.timezoneList);var tzl=timezoneList.length,tzi=0,tzv=null;if(tzl>0&&"object"!=typeof timezoneList[0])for(;tzl>tzi;tzi++)tzv=timezoneList[tzi],timezoneList[tzi]={value:tzv,label:$.timepicker.timezoneOffsetString(tzv,tp_inst.support.iso8601)};return tp_inst._defaults.timezoneList=timezoneList,tp_inst.timezone=null!==tp_inst._defaults.timezone?$.timepicker.timezoneOffsetNumber(tp_inst._defaults.timezone):-1*(new Date).getTimezoneOffset(),tp_inst.hour=tp_inst._defaults.hour<tp_inst._defaults.hourMin?tp_inst._defaults.hourMin:tp_inst._defaults.hour>tp_inst._defaults.hourMax?tp_inst._defaults.hourMax:tp_inst._defaults.hour,tp_inst.minute=tp_inst._defaults.minute<tp_inst._defaults.minuteMin?tp_inst._defaults.minuteMin:tp_inst._defaults.minute>tp_inst._defaults.minuteMax?tp_inst._defaults.minuteMax:tp_inst._defaults.minute,tp_inst.second=tp_inst._defaults.second<tp_inst._defaults.secondMin?tp_inst._defaults.secondMin:tp_inst._defaults.second>tp_inst._defaults.secondMax?tp_inst._defaults.secondMax:tp_inst._defaults.second,tp_inst.millisec=tp_inst._defaults.millisec<tp_inst._defaults.millisecMin?tp_inst._defaults.millisecMin:tp_inst._defaults.millisec>tp_inst._defaults.millisecMax?tp_inst._defaults.millisecMax:tp_inst._defaults.millisec,tp_inst.microsec=tp_inst._defaults.microsec<tp_inst._defaults.microsecMin?tp_inst._defaults.microsecMin:tp_inst._defaults.microsec>tp_inst._defaults.microsecMax?tp_inst._defaults.microsecMax:tp_inst._defaults.microsec,tp_inst.ampm="",tp_inst.$input=$input,tp_inst._defaults.altField&&(tp_inst.$altInput=$(tp_inst._defaults.altField),tp_inst._defaults.altRedirectFocus===!0&&tp_inst.$altInput.css({cursor:"pointer"}).focus(function(){$input.trigger("focus")})),(0===tp_inst._defaults.minDate||0===tp_inst._defaults.minDateTime)&&(tp_inst._defaults.minDate=new Date),(0===tp_inst._defaults.maxDate||0===tp_inst._defaults.maxDateTime)&&(tp_inst._defaults.maxDate=new Date),void 0!==tp_inst._defaults.minDate&&tp_inst._defaults.minDate instanceof Date&&(tp_inst._defaults.minDateTime=new Date(tp_inst._defaults.minDate.getTime())),void 0!==tp_inst._defaults.minDateTime&&tp_inst._defaults.minDateTime instanceof Date&&(tp_inst._defaults.minDate=new Date(tp_inst._defaults.minDateTime.getTime())),void 0!==tp_inst._defaults.maxDate&&tp_inst._defaults.maxDate instanceof Date&&(tp_inst._defaults.maxDateTime=new Date(tp_inst._defaults.maxDate.getTime())),void 0!==tp_inst._defaults.maxDateTime&&tp_inst._defaults.maxDateTime instanceof Date&&(tp_inst._defaults.maxDate=new Date(tp_inst._defaults.maxDateTime.getTime())),tp_inst.$input.bind("focus",function(){tp_inst._onFocus()}),tp_inst},_addTimePicker:function(a){var b=$.trim(this.$altInput&&this._defaults.altFieldTimeOnly?this.$input.val()+" "+this.$altInput.val():this.$input.val());this.timeDefined=this._parseTime(b),this._limitMinMaxDateTime(a,!1),this._injectTimePicker(),this._afterInject()},_parseTime:function(a,b){if(this.inst||(this.inst=$.datepicker._getInst(this.$input[0])),b||!this._defaults.timeOnly){var c=$.datepicker._get(this.inst,"dateFormat");try{var d=parseDateTimeInternal(c,this._defaults.timeFormat,a,$.datepicker._getFormatConfig(this.inst),this._defaults);if(!d.timeObj)return!1;$.extend(this,d.timeObj)}catch(e){return $.timepicker.log("Error parsing the date/time string: "+e+"\ndate/time string = "+a+"\ntimeFormat = "+this._defaults.timeFormat+"\ndateFormat = "+c),!1}return!0}var f=$.datepicker.parseTime(this._defaults.timeFormat,a,this._defaults);return f?($.extend(this,f),!0):!1},_afterInject:function(){var a=this.inst.settings;$.isFunction(a.afterInject)&&a.afterInject.call(this)},_injectTimePicker:function(){var a=this.inst.dpDiv,b=this.inst.settings,c=this,d="",e="",f=null,g={},h={},i=null,j=0,k=0;if(0===a.find("div.ui-timepicker-div").length&&b.showTimepicker){var l=" ui_tpicker_unit_hide",m='<div class="ui-timepicker-div'+(b.isRTL?" ui-timepicker-rtl":"")+(b.oneLine&&"select"===b.controlType?" ui-timepicker-oneLine":"")+'"><dl><dt class="ui_tpicker_time_label'+(b.showTime?"":l)+'">'+b.timeText+'</dt><dd class="ui_tpicker_time '+(b.showTime?"":l)+'"><input class="ui_tpicker_time_input" '+(b.timeInput?"":"disabled")+"/></dd>";for(j=0,k=this.units.length;k>j;j++){if(d=this.units[j],e=d.substr(0,1).toUpperCase()+d.substr(1),f=null!==b["show"+e]?b["show"+e]:this.support[d],g[d]=parseInt(b[d+"Max"]-(b[d+"Max"]-b[d+"Min"])%b["step"+e],10),h[d]=0,m+='<dt class="ui_tpicker_'+d+"_label"+(f?"":l)+'">'+b[d+"Text"]+'</dt><dd class="ui_tpicker_'+d+(f?"":l)+'"><div class="ui_tpicker_'+d+"_slider"+(f?"":l)+'"></div>',f&&b[d+"Grid"]>0){if(m+='<div style="padding-left: 1px"><table class="ui-tpicker-grid-label"><tr>',"hour"===d)for(var n=b[d+"Min"];n<=g[d];n+=parseInt(b[d+"Grid"],10)){h[d]++;var o=$.datepicker.formatTime(this.support.ampm?"hht":"HH",{hour:n},b);m+='<td data-for="'+d+'">'+o+"</td>"}else for(var p=b[d+"Min"];p<=g[d];p+=parseInt(b[d+"Grid"],10))h[d]++,m+='<td data-for="'+d+'">'+(10>p?"0":"")+p+"</td>";m+="</tr></table></div>"}m+="</dd>"}var q=null!==b.showTimezone?b.showTimezone:this.support.timezone;m+='<dt class="ui_tpicker_timezone_label'+(q?"":l)+'">'+b.timezoneText+"</dt>",m+='<dd class="ui_tpicker_timezone'+(q?"":l)+'"></dd>',m+="</dl></div>";var r=$(m);for(b.timeOnly===!0&&(r.prepend('<div class="ui-widget-header ui-helper-clearfix ui-corner-all"><div class="ui-datepicker-title">'+b.timeOnlyTitle+"</div></div>"),a.find(".ui-datepicker-header, .ui-datepicker-calendar").hide()),j=0,k=c.units.length;k>j;j++)d=c.units[j],e=d.substr(0,1).toUpperCase()+d.substr(1),f=null!==b["show"+e]?b["show"+e]:this.support[d],c[d+"_slider"]=c.control.create(c,r.find(".ui_tpicker_"+d+"_slider"),d,c[d],b[d+"Min"],g[d],b["step"+e]),f&&b[d+"Grid"]>0&&(i=100*h[d]*b[d+"Grid"]/(g[d]-b[d+"Min"]),r.find(".ui_tpicker_"+d+" table").css({width:i+"%",marginLeft:b.isRTL?"0":i/(-2*h[d])+"%",marginRight:b.isRTL?i/(-2*h[d])+"%":"0",borderCollapse:"collapse"}).find("td").click(function(a){var b=$(this),e=b.html(),f=parseInt(e.replace(/[^0-9]/g),10),g=e.replace(/[^apm]/gi),h=b.data("for");"hour"===h&&(-1!==g.indexOf("p")&&12>f?f+=12:-1!==g.indexOf("a")&&12===f&&(f=0)),c.control.value(c,c[h+"_slider"],d,f),c._onTimeChange(),c._onSelectHandler()}).css({cursor:"pointer",width:100/h[d]+"%",textAlign:"center",overflow:"hidden"}));if(this.timezone_select=r.find(".ui_tpicker_timezone").append("<select></select>").find("select"),$.fn.append.apply(this.timezone_select,$.map(b.timezoneList,function(a,b){return $("<option />").val("object"==typeof a?a.value:a).text("object"==typeof a?a.label:a)})),"undefined"!=typeof this.timezone&&null!==this.timezone&&""!==this.timezone){var s=-1*new Date(this.inst.selectedYear,this.inst.selectedMonth,this.inst.selectedDay,12).getTimezoneOffset();s===this.timezone?selectLocalTimezone(c):this.timezone_select.val(this.timezone)}else"undefined"!=typeof this.hour&&null!==this.hour&&""!==this.hour?this.timezone_select.val(b.timezone):selectLocalTimezone(c);this.timezone_select.change(function(){c._onTimeChange(),c._onSelectHandler(),c._afterInject()});var t=a.find(".ui-datepicker-buttonpane");if(t.length?t.before(r):a.append(r),this.$timeObj=r.find(".ui_tpicker_time_input"),this.$timeObj.change(function(){var a=c.inst.settings.timeFormat,b=$.datepicker.parseTime(a,this.value),d=new Date;b?(d.setHours(b.hour),d.setMinutes(b.minute),d.setSeconds(b.second),$.datepicker._setTime(c.inst,d)):(this.value=c.formattedTime,this.blur())}),null!==this.inst){var u=this.timeDefined;this._onTimeChange(),this.timeDefined=u}if(this._defaults.addSliderAccess){var v=this._defaults.sliderAccessArgs,w=this._defaults.isRTL;v.isRTL=w,setTimeout(function(){if(0===r.find(".ui-slider-access").length){r.find(".ui-slider:visible").sliderAccess(v);var a=r.find(".ui-slider-access:eq(0)").outerWidth(!0);a&&r.find("table:visible").each(function(){var b=$(this),c=b.outerWidth(),d=b.css(w?"marginRight":"marginLeft").toString().replace("%",""),e=c-a,f=d*e/c+"%",g={width:e,marginRight:0,marginLeft:0};g[w?"marginRight":"marginLeft"]=f,b.css(g)})}},10)}c._limitMinMaxDateTime(this.inst,!0)}},_limitMinMaxDateTime:function(a,b){var c=this._defaults,d=new Date(a.selectedYear,a.selectedMonth,a.selectedDay);if(this._defaults.showTimepicker){if(null!==$.datepicker._get(a,"minDateTime")&&void 0!==$.datepicker._get(a,"minDateTime")&&d){var e=$.datepicker._get(a,"minDateTime"),f=new Date(e.getFullYear(),e.getMonth(),e.getDate(),0,0,0,0);(null===this.hourMinOriginal||null===this.minuteMinOriginal||null===this.secondMinOriginal||null===this.millisecMinOriginal||null===this.microsecMinOriginal)&&(this.hourMinOriginal=c.hourMin,this.minuteMinOriginal=c.minuteMin,this.secondMinOriginal=c.secondMin,this.millisecMinOriginal=c.millisecMin,this.microsecMinOriginal=c.microsecMin),a.settings.timeOnly||f.getTime()===d.getTime()?(this._defaults.hourMin=e.getHours(),this.hour<=this._defaults.hourMin?(this.hour=this._defaults.hourMin,this._defaults.minuteMin=e.getMinutes(),this.minute<=this._defaults.minuteMin?(this.minute=this._defaults.minuteMin,this._defaults.secondMin=e.getSeconds(),this.second<=this._defaults.secondMin?(this.second=this._defaults.secondMin,this._defaults.millisecMin=e.getMilliseconds(),this.millisec<=this._defaults.millisecMin?(this.millisec=this._defaults.millisecMin,this._defaults.microsecMin=e.getMicroseconds()):(this.microsec<this._defaults.microsecMin&&(this.microsec=this._defaults.microsecMin),this._defaults.microsecMin=this.microsecMinOriginal)):(this._defaults.millisecMin=this.millisecMinOriginal,this._defaults.microsecMin=this.microsecMinOriginal)):(this._defaults.secondMin=this.secondMinOriginal,this._defaults.millisecMin=this.millisecMinOriginal,this._defaults.microsecMin=this.microsecMinOriginal)):(this._defaults.minuteMin=this.minuteMinOriginal,this._defaults.secondMin=this.secondMinOriginal,this._defaults.millisecMin=this.millisecMinOriginal,this._defaults.microsecMin=this.microsecMinOriginal)):(this._defaults.hourMin=this.hourMinOriginal,this._defaults.minuteMin=this.minuteMinOriginal,this._defaults.secondMin=this.secondMinOriginal,this._defaults.millisecMin=this.millisecMinOriginal,this._defaults.microsecMin=this.microsecMinOriginal)}if(null!==$.datepicker._get(a,"maxDateTime")&&void 0!==$.datepicker._get(a,"maxDateTime")&&d){var g=$.datepicker._get(a,"maxDateTime"),h=new Date(g.getFullYear(),g.getMonth(),g.getDate(),0,0,0,0);(null===this.hourMaxOriginal||null===this.minuteMaxOriginal||null===this.secondMaxOriginal||null===this.millisecMaxOriginal)&&(this.hourMaxOriginal=c.hourMax,this.minuteMaxOriginal=c.minuteMax,this.secondMaxOriginal=c.secondMax,this.millisecMaxOriginal=c.millisecMax,this.microsecMaxOriginal=c.microsecMax),a.settings.timeOnly||h.getTime()===d.getTime()?(this._defaults.hourMax=g.getHours(),this.hour>=this._defaults.hourMax?(this.hour=this._defaults.hourMax,this._defaults.minuteMax=g.getMinutes(),this.minute>=this._defaults.minuteMax?(this.minute=this._defaults.minuteMax,this._defaults.secondMax=g.getSeconds(),this.second>=this._defaults.secondMax?(this.second=this._defaults.secondMax,this._defaults.millisecMax=g.getMilliseconds(),this.millisec>=this._defaults.millisecMax?(this.millisec=this._defaults.millisecMax,this._defaults.microsecMax=g.getMicroseconds()):(this.microsec>this._defaults.microsecMax&&(this.microsec=this._defaults.microsecMax),this._defaults.microsecMax=this.microsecMaxOriginal)):(this._defaults.millisecMax=this.millisecMaxOriginal,this._defaults.microsecMax=this.microsecMaxOriginal)):(this._defaults.secondMax=this.secondMaxOriginal,this._defaults.millisecMax=this.millisecMaxOriginal,this._defaults.microsecMax=this.microsecMaxOriginal)):(this._defaults.minuteMax=this.minuteMaxOriginal,this._defaults.secondMax=this.secondMaxOriginal,this._defaults.millisecMax=this.millisecMaxOriginal,this._defaults.microsecMax=this.microsecMaxOriginal)):(this._defaults.hourMax=this.hourMaxOriginal,this._defaults.minuteMax=this.minuteMaxOriginal,this._defaults.secondMax=this.secondMaxOriginal,this._defaults.millisecMax=this.millisecMaxOriginal,this._defaults.microsecMax=this.microsecMaxOriginal)}if(null!==a.settings.minTime){var i=new Date("01/01/1970 "+a.settings.minTime);this.hour<i.getHours()?(this.hour=this._defaults.hourMin=i.getHours(),this.minute=this._defaults.minuteMin=i.getMinutes()):this.hour===i.getHours()&&this.minute<i.getMinutes()?this.minute=this._defaults.minuteMin=i.getMinutes():this._defaults.hourMin<i.getHours()?(this._defaults.hourMin=i.getHours(),this._defaults.minuteMin=i.getMinutes()):this._defaults.hourMin===i.getHours()===this.hour&&this._defaults.minuteMin<i.getMinutes()?this._defaults.minuteMin=i.getMinutes():this._defaults.minuteMin=0}if(null!==a.settings.maxTime){var j=new Date("01/01/1970 "+a.settings.maxTime);this.hour>j.getHours()?(this.hour=this._defaults.hourMax=j.getHours(),this.minute=this._defaults.minuteMax=j.getMinutes()):this.hour===j.getHours()&&this.minute>j.getMinutes()?this.minute=this._defaults.minuteMax=j.getMinutes():this._defaults.hourMax>j.getHours()?(this._defaults.hourMax=j.getHours(),this._defaults.minuteMax=j.getMinutes()):this._defaults.hourMax===j.getHours()===this.hour&&this._defaults.minuteMax>j.getMinutes()?this._defaults.minuteMax=j.getMinutes():this._defaults.minuteMax=59}if(void 0!==b&&b===!0){var k=parseInt(this._defaults.hourMax-(this._defaults.hourMax-this._defaults.hourMin)%this._defaults.stepHour,10),l=parseInt(this._defaults.minuteMax-(this._defaults.minuteMax-this._defaults.minuteMin)%this._defaults.stepMinute,10),m=parseInt(this._defaults.secondMax-(this._defaults.secondMax-this._defaults.secondMin)%this._defaults.stepSecond,10),n=parseInt(this._defaults.millisecMax-(this._defaults.millisecMax-this._defaults.millisecMin)%this._defaults.stepMillisec,10),o=parseInt(this._defaults.microsecMax-(this._defaults.microsecMax-this._defaults.microsecMin)%this._defaults.stepMicrosec,10);this.hour_slider&&(this.control.options(this,this.hour_slider,"hour",{min:this._defaults.hourMin,max:k,step:this._defaults.stepHour}),this.control.value(this,this.hour_slider,"hour",this.hour-this.hour%this._defaults.stepHour)),this.minute_slider&&(this.control.options(this,this.minute_slider,"minute",{min:this._defaults.minuteMin,max:l,step:this._defaults.stepMinute}),this.control.value(this,this.minute_slider,"minute",this.minute-this.minute%this._defaults.stepMinute)),this.second_slider&&(this.control.options(this,this.second_slider,"second",{min:this._defaults.secondMin,max:m,step:this._defaults.stepSecond}),this.control.value(this,this.second_slider,"second",this.second-this.second%this._defaults.stepSecond)),this.millisec_slider&&(this.control.options(this,this.millisec_slider,"millisec",{min:this._defaults.millisecMin,max:n,step:this._defaults.stepMillisec}),this.control.value(this,this.millisec_slider,"millisec",this.millisec-this.millisec%this._defaults.stepMillisec)),this.microsec_slider&&(this.control.options(this,this.microsec_slider,"microsec",{min:this._defaults.microsecMin,max:o,step:this._defaults.stepMicrosec}),this.control.value(this,this.microsec_slider,"microsec",this.microsec-this.microsec%this._defaults.stepMicrosec))}}},_onTimeChange:function(){if(this._defaults.showTimepicker){var a=this.hour_slider?this.control.value(this,this.hour_slider,"hour"):!1,b=this.minute_slider?this.control.value(this,this.minute_slider,"minute"):!1,c=this.second_slider?this.control.value(this,this.second_slider,"second"):!1,d=this.millisec_slider?this.control.value(this,this.millisec_slider,"millisec"):!1,e=this.microsec_slider?this.control.value(this,this.microsec_slider,"microsec"):!1,f=this.timezone_select?this.timezone_select.val():!1,g=this._defaults,h=g.pickerTimeFormat||g.timeFormat,i=g.pickerTimeSuffix||g.timeSuffix;"object"==typeof a&&(a=!1),"object"==typeof b&&(b=!1),"object"==typeof c&&(c=!1),"object"==typeof d&&(d=!1),"object"==typeof e&&(e=!1),"object"==typeof f&&(f=!1),a!==!1&&(a=parseInt(a,10)),b!==!1&&(b=parseInt(b,10)),c!==!1&&(c=parseInt(c,10)),d!==!1&&(d=parseInt(d,10)),e!==!1&&(e=parseInt(e,10)),f!==!1&&(f=f.toString());var j=g[12>a?"amNames":"pmNames"][0],k=a!==parseInt(this.hour,10)||b!==parseInt(this.minute,10)||c!==parseInt(this.second,10)||d!==parseInt(this.millisec,10)||e!==parseInt(this.microsec,10)||this.ampm.length>0&&12>a!=(-1!==$.inArray(this.ampm.toUpperCase(),this.amNames))||null!==this.timezone&&f!==this.timezone.toString();if(k&&(a!==!1&&(this.hour=a),b!==!1&&(this.minute=b),c!==!1&&(this.second=c),d!==!1&&(this.millisec=d),e!==!1&&(this.microsec=e),f!==!1&&(this.timezone=f),this.inst||(this.inst=$.datepicker._getInst(this.$input[0])),this._limitMinMaxDateTime(this.inst,!0)),this.support.ampm&&(this.ampm=j),this.formattedTime=$.datepicker.formatTime(g.timeFormat,this,g),this.$timeObj&&(this.$timeObj.val(h===g.timeFormat?this.formattedTime+i:$.datepicker.formatTime(h,this,g)+i),this.$timeObj[0].setSelectionRange)){var l=this.$timeObj[0].selectionStart,m=this.$timeObj[0].selectionEnd;this.$timeObj[0].setSelectionRange(l,m)}this.timeDefined=!0,k&&this._updateDateTime()}},_onSelectHandler:function(){var a=this._defaults.onSelect||this.inst.settings.onSelect,b=this.$input?this.$input[0]:null;a&&b&&a.apply(b,[this.formattedDateTime,this])},_updateDateTime:function(a){a=this.inst||a;var b=a.currentYear>0?new Date(a.currentYear,a.currentMonth,a.currentDay):new Date(a.selectedYear,a.selectedMonth,a.selectedDay),c=$.datepicker._daylightSavingAdjust(b),d=$.datepicker._get(a,"dateFormat"),e=$.datepicker._getFormatConfig(a),f=null!==c&&this.timeDefined;this.formattedDate=$.datepicker.formatDate(d,null===c?new Date:c,e);var g=this.formattedDate;if(""===a.lastVal&&(a.currentYear=a.selectedYear,a.currentMonth=a.selectedMonth,a.currentDay=a.selectedDay),this._defaults.timeOnly===!0&&this._defaults.timeOnlyShowDate===!1?g=this.formattedTime:(this._defaults.timeOnly!==!0&&(this._defaults.alwaysSetTime||f)||this._defaults.timeOnly===!0&&this._defaults.timeOnlyShowDate===!0)&&(g+=this._defaults.separator+this.formattedTime+this._defaults.timeSuffix),this.formattedDateTime=g,this._defaults.showTimepicker)if(this.$altInput&&this._defaults.timeOnly===!1&&this._defaults.altFieldTimeOnly===!0)this.$altInput.val(this.formattedTime),this.$input.val(this.formattedDate);else if(this.$altInput){this.$input.val(g);var h="",i=null!==this._defaults.altSeparator?this._defaults.altSeparator:this._defaults.separator,j=null!==this._defaults.altTimeSuffix?this._defaults.altTimeSuffix:this._defaults.timeSuffix;this._defaults.timeOnly||(h=this._defaults.altFormat?$.datepicker.formatDate(this._defaults.altFormat,null===c?new Date:c,e):this.formattedDate,h&&(h+=i)),h+=null!==this._defaults.altTimeFormat?$.datepicker.formatTime(this._defaults.altTimeFormat,this,this._defaults)+j:this.formattedTime+j,this.$altInput.val(h)}else this.$input.val(g);else this.$input.val(this.formattedDate);this.$input.trigger("change")},_onFocus:function(){if(!this.$input.val()&&this._defaults.defaultValue){this.$input.val(this._defaults.defaultValue);var a=$.datepicker._getInst(this.$input.get(0)),b=$.datepicker._get(a,"timepicker");if(b&&b._defaults.timeOnly&&a.input.val()!==a.lastVal)try{$.datepicker._updateDatepicker(a)}catch(c){$.timepicker.log(c)}}},_controls:{slider:{create:function(a,b,c,d,e,f,g){var h=a._defaults.isRTL;return b.prop("slide",null).slider({orientation:"horizontal",value:h?-1*d:d,min:h?-1*f:e,max:h?-1*e:f,step:g,slide:function(b,d){a.control.value(a,$(this),c,h?-1*d.value:d.value),a._onTimeChange()},stop:function(b,c){a._onSelectHandler()}})},options:function(a,b,c,d,e){if(a._defaults.isRTL){if("string"==typeof d)return"min"===d||"max"===d?void 0!==e?b.slider(d,-1*e):Math.abs(b.slider(d)):b.slider(d);var f=d.min,g=d.max;return d.min=d.max=null,void 0!==f&&(d.max=-1*f),void 0!==g&&(d.min=-1*g),b.slider(d)}return"string"==typeof d&&void 0!==e?b.slider(d,e):b.slider(d)},value:function(a,b,c,d){return a._defaults.isRTL?void 0!==d?b.slider("value",-1*d):Math.abs(b.slider("value")):void 0!==d?b.slider("value",d):b.slider("value")}},select:{create:function(a,b,c,d,e,f,g){for(var h='<select class="ui-timepicker-select ui-state-default ui-corner-all" data-unit="'+c+'" data-min="'+e+'" data-max="'+f+'" data-step="'+g+'">',i=a._defaults.pickerTimeFormat||a._defaults.timeFormat,j=e;f>=j;j+=g)h+='<option value="'+j+'"'+(j===d?" selected":"")+">",h+="hour"===c?$.datepicker.formatTime($.trim(i.replace(/[^ht ]/gi,"")),{hour:j},a._defaults):"millisec"===c||"microsec"===c||j>=10?j:"0"+j.toString(),h+="</option>";return h+="</select>",b.children("select").remove(),$(h).appendTo(b).change(function(b){a._onTimeChange(),a._onSelectHandler(),a._afterInject()}),b},options:function(a,b,c,d,e){var f={},g=b.children("select");if("string"==typeof d){if(void 0===e)return g.data(d);f[d]=e}else f=d;return a.control.create(a,b,g.data("unit"),g.val(),f.min>=0?f.min:g.data("min"),f.max||g.data("max"),f.step||g.data("step"))},value:function(a,b,c,d){var e=b.children("select");return void 0!==d?e.val(d):e.val()}}}}),$.fn.extend({timepicker:function(a){a=a||{};var b=Array.prototype.slice.call(arguments);return"object"==typeof a&&(b[0]=$.extend(a,{timeOnly:!0})),$(this).each(function(){$.fn.datetimepicker.apply($(this),b)})},datetimepicker:function(a){a=a||{};var b=arguments;return"string"==typeof a?"getDate"===a||"option"===a&&2===b.length&&"string"==typeof b[1]?$.fn.datepicker.apply($(this[0]),b):this.each(function(){var a=$(this);a.datepicker.apply(a,b)}):this.each(function(){var b=$(this);b.datepicker($.timepicker._newInst(b,a)._defaults)})}}),$.datepicker.parseDateTime=function(a,b,c,d,e){var f=parseDateTimeInternal(a,b,c,d,e);if(f.timeObj){var g=f.timeObj;f.date.setHours(g.hour,g.minute,g.second,g.millisec),f.date.setMicroseconds(g.microsec)}return f.date},$.datepicker.parseTime=function(a,b,c){var d=extendRemove(extendRemove({},$.timepicker._defaults),c||{}),e=(-1!==a.replace(/\'.*?\'/g,"").indexOf("Z"),function(a,b,c){var d,e=function(a,b){var c=[];return a&&$.merge(c,a),b&&$.merge(c,b),c=$.map(c,function(a){return a.replace(/[.*+?|()\[\]{}\\]/g,"\\$&")}),"("+c.join("|")+")?"},f=function(a){var b=a.toLowerCase().match(/(h{1,2}|m{1,2}|s{1,2}|l{1}|c{1}|t{1,2}|z|'.*?')/g),c={h:-1,m:-1,s:-1,l:-1,c:-1,t:-1,z:-1};if(b)for(var d=0;d<b.length;d++)-1===c[b[d].toString().charAt(0)]&&(c[b[d].toString().charAt(0)]=d+1);return c},g="^"+a.toString().replace(/([hH]{1,2}|mm?|ss?|[tT]{1,2}|[zZ]|[lc]|'.*?')/g,function(a){var b=a.length;switch(a.charAt(0).toLowerCase()){case"h":return 1===b?"(\\d?\\d)":"(\\d{"+b+"})";case"m":return 1===b?"(\\d?\\d)":"(\\d{"+b+"})";case"s":return 1===b?"(\\d?\\d)":"(\\d{"+b+"})";case"l":return"(\\d?\\d?\\d)";case"c":return"(\\d?\\d?\\d)";case"z":return"(z|[-+]\\d\\d:?\\d\\d|\\S+)?";case"t":return e(c.amNames,c.pmNames);default:return"("+a.replace(/\'/g,"").replace(/(\.|\$|\^|\\|\/|\(|\)|\[|\]|\?|\+|\*)/g,function(a){return"\\"+a})+")?"}}).replace(/\s/g,"\\s?")+c.timeSuffix+"$",h=f(a),i="";d=b.match(new RegExp(g,"i"));var j={hour:0,minute:0,second:0,millisec:0,microsec:0};return d?(-1!==h.t&&(void 0===d[h.t]||0===d[h.t].length?(i="",j.ampm=""):(i=-1!==$.inArray(d[h.t].toUpperCase(),$.map(c.amNames,function(a,b){return a.toUpperCase()}))?"AM":"PM",j.ampm=c["AM"===i?"amNames":"pmNames"][0])),-1!==h.h&&("AM"===i&&"12"===d[h.h]?j.hour=0:"PM"===i&&"12"!==d[h.h]?j.hour=parseInt(d[h.h],10)+12:j.hour=Number(d[h.h])),-1!==h.m&&(j.minute=Number(d[h.m])),-1!==h.s&&(j.second=Number(d[h.s])),-1!==h.l&&(j.millisec=Number(d[h.l])),-1!==h.c&&(j.microsec=Number(d[h.c])),-1!==h.z&&void 0!==d[h.z]&&(j.timezone=$.timepicker.timezoneOffsetNumber(d[h.z])),j):!1}),f=function(a,b,c){try{var d=new Date("2012-01-01 "+b);if(isNaN(d.getTime())&&(d=new Date("2012-01-01T"+b),isNaN(d.getTime())&&(d=new Date("01/01/2012 "+b),isNaN(d.getTime()))))throw"Unable to parse time with native Date: "+b;return{hour:d.getHours(),minute:d.getMinutes(),second:d.getSeconds(),millisec:d.getMilliseconds(),microsec:d.getMicroseconds(),timezone:-1*d.getTimezoneOffset()}}catch(f){try{return e(a,b,c)}catch(g){$.timepicker.log("Unable to parse \ntimeString: "+b+"\ntimeFormat: "+a)}}return!1};return"function"==typeof d.parse?d.parse(a,b,d):"loose"===d.parse?f(a,b,d):e(a,b,d)},$.datepicker.formatTime=function(a,b,c){c=c||{},c=$.extend({},$.timepicker._defaults,c),b=$.extend({hour:0,minute:0,second:0,millisec:0,microsec:0,timezone:null},b);var d=a,e=c.amNames[0],f=parseInt(b.hour,10);return f>11&&(e=c.pmNames[0]),d=d.replace(/(?:HH?|hh?|mm?|ss?|[tT]{1,2}|[zZ]|[lc]|'.*?')/g,function(a){switch(a){case"HH":return("0"+f).slice(-2);case"H":return f;case"hh":return("0"+convert24to12(f)).slice(-2);case"h":return convert24to12(f);case"mm":return("0"+b.minute).slice(-2);case"m":return b.minute;case"ss":return("0"+b.second).slice(-2);case"s":return b.second;case"l":return("00"+b.millisec).slice(-3);case"c":return("00"+b.microsec).slice(-3);case"z":return $.timepicker.timezoneOffsetString(null===b.timezone?c.timezone:b.timezone,!1);case"Z":return $.timepicker.timezoneOffsetString(null===b.timezone?c.timezone:b.timezone,!0);case"T":return e.charAt(0).toUpperCase();case"TT":return e.toUpperCase();case"t":return e.charAt(0).toLowerCase();case"tt":return e.toLowerCase();default:return a.replace(/'/g,"")}})},$.datepicker._base_selectDate=$.datepicker._selectDate,$.datepicker._selectDate=function(a,b){var c,d=this._getInst($(a)[0]),e=this._get(d,"timepicker");e&&d.settings.showTimepicker?(e._limitMinMaxDateTime(d,!0),c=d.inline,d.inline=d.stay_open=!0,this._base_selectDate(a,b),d.inline=c,d.stay_open=!1,this._notifyChange(d),this._updateDatepicker(d)):this._base_selectDate(a,b)},$.datepicker._base_updateDatepicker=$.datepicker._updateDatepicker,$.datepicker._updateDatepicker=function(a){var b=a.input[0];if(!($.datepicker._curInst&&$.datepicker._curInst!==a&&$.datepicker._datepickerShowing&&$.datepicker._lastInput!==b||"boolean"==typeof a.stay_open&&a.stay_open!==!1)){this._base_updateDatepicker(a);var c=this._get(a,"timepicker");c&&c._addTimePicker(a)}},$.datepicker._base_doKeyPress=$.datepicker._doKeyPress,$.datepicker._doKeyPress=function(a){var b=$.datepicker._getInst(a.target),c=$.datepicker._get(b,"timepicker");if(c&&$.datepicker._get(b,"constrainInput")){var d=c.support.ampm,e=null!==c._defaults.showTimezone?c._defaults.showTimezone:c.support.timezone,f=$.datepicker._possibleChars($.datepicker._get(b,"dateFormat")),g=c._defaults.timeFormat.toString().replace(/[hms]/g,"").replace(/TT/g,d?"APM":"").replace(/Tt/g,d?"AaPpMm":"").replace(/tT/g,d?"AaPpMm":"").replace(/T/g,d?"AP":"").replace(/tt/g,d?"apm":"").replace(/t/g,d?"ap":"")+" "+c._defaults.separator+c._defaults.timeSuffix+(e?c._defaults.timezoneList.join(""):"")+c._defaults.amNames.join("")+c._defaults.pmNames.join("")+f,h=String.fromCharCode(void 0===a.charCode?a.keyCode:a.charCode);return a.ctrlKey||" ">h||!f||g.indexOf(h)>-1}return $.datepicker._base_doKeyPress(a)},$.datepicker._base_updateAlternate=$.datepicker._updateAlternate,$.datepicker._updateAlternate=function(a){var b=this._get(a,"timepicker");if(b){var c=b._defaults.altField;if(c){var d=(b._defaults.altFormat||b._defaults.dateFormat,this._getDate(a)),e=$.datepicker._getFormatConfig(a),f="",g=b._defaults.altSeparator?b._defaults.altSeparator:b._defaults.separator,h=b._defaults.altTimeSuffix?b._defaults.altTimeSuffix:b._defaults.timeSuffix,i=null!==b._defaults.altTimeFormat?b._defaults.altTimeFormat:b._defaults.timeFormat;f+=$.datepicker.formatTime(i,b,b._defaults)+h,b._defaults.timeOnly||b._defaults.altFieldTimeOnly||null===d||(f=b._defaults.altFormat?$.datepicker.formatDate(b._defaults.altFormat,d,e)+g+f:b.formattedDate+g+f),$(c).val(a.input.val()?f:"")}}else $.datepicker._base_updateAlternate(a)},$.datepicker._base_doKeyUp=$.datepicker._doKeyUp,$.datepicker._doKeyUp=function(a){var b=$.datepicker._getInst(a.target),c=$.datepicker._get(b,"timepicker");
	if(c&&c._defaults.timeOnly&&b.input.val()!==b.lastVal)try{$.datepicker._updateDatepicker(b)}catch(d){$.timepicker.log(d)}return $.datepicker._base_doKeyUp(a)},$.datepicker._base_gotoToday=$.datepicker._gotoToday,$.datepicker._gotoToday=function(a){var b=this._getInst($(a)[0]);this._base_gotoToday(a);var c=this._get(b,"timepicker");if(c){var d=$.timepicker.timezoneOffsetNumber(c.timezone),e=new Date;e.setMinutes(e.getMinutes()+e.getTimezoneOffset()+parseInt(d,10)),this._setTime(b,e),this._setDate(b,e),c._onSelectHandler()}},$.datepicker._disableTimepickerDatepicker=function(a){var b=this._getInst(a);if(b){var c=this._get(b,"timepicker");$(a).datepicker("getDate"),c&&(b.settings.showTimepicker=!1,c._defaults.showTimepicker=!1,c._updateDateTime(b))}},$.datepicker._enableTimepickerDatepicker=function(a){var b=this._getInst(a);if(b){var c=this._get(b,"timepicker");$(a).datepicker("getDate"),c&&(b.settings.showTimepicker=!0,c._defaults.showTimepicker=!0,c._addTimePicker(b),c._updateDateTime(b))}},$.datepicker._setTime=function(a,b){var c=this._get(a,"timepicker");if(c){var d=c._defaults;c.hour=b?b.getHours():d.hour,c.minute=b?b.getMinutes():d.minute,c.second=b?b.getSeconds():d.second,c.millisec=b?b.getMilliseconds():d.millisec,c.microsec=b?b.getMicroseconds():d.microsec,c._limitMinMaxDateTime(a,!0),c._onTimeChange(),c._updateDateTime(a)}},$.datepicker._setTimeDatepicker=function(a,b,c){var d=this._getInst(a);if(d){var e=this._get(d,"timepicker");if(e){this._setDateFromField(d);var f;b&&("string"==typeof b?(e._parseTime(b,c),f=new Date,f.setHours(e.hour,e.minute,e.second,e.millisec),f.setMicroseconds(e.microsec)):(f=new Date(b.getTime()),f.setMicroseconds(b.getMicroseconds())),"Invalid Date"===f.toString()&&(f=void 0),this._setTime(d,f))}}},$.datepicker._base_setDateDatepicker=$.datepicker._setDateDatepicker,$.datepicker._setDateDatepicker=function(a,b){var c=this._getInst(a),d=b;if(c){"string"==typeof b&&(d=new Date(b),d.getTime()||(this._base_setDateDatepicker.apply(this,arguments),d=$(a).datepicker("getDate")));var e,f=this._get(c,"timepicker");d instanceof Date?(e=new Date(d.getTime()),e.setMicroseconds(d.getMicroseconds())):e=d,f&&e&&(f.support.timezone||null!==f._defaults.timezone||(f.timezone=-1*e.getTimezoneOffset()),d=$.timepicker.timezoneAdjust(d,$.timepicker.timezoneOffsetString(-d.getTimezoneOffset()),f.timezone),e=$.timepicker.timezoneAdjust(e,$.timepicker.timezoneOffsetString(-e.getTimezoneOffset()),f.timezone)),this._updateDatepicker(c),this._base_setDateDatepicker.apply(this,arguments),this._setTimeDatepicker(a,e,!0)}},$.datepicker._base_getDateDatepicker=$.datepicker._getDateDatepicker,$.datepicker._getDateDatepicker=function(a,b){var c=this._getInst(a);if(c){var d=this._get(c,"timepicker");if(d){void 0===c.lastVal&&this._setDateFromField(c,b);var e=this._getDate(c),f=null;return f=d.$altInput&&d._defaults.altFieldTimeOnly?d.$input.val()+" "+d.$altInput.val():"INPUT"!==d.$input.get(0).tagName&&d.$altInput?d.$altInput.val():d.$input.val(),e&&d._parseTime(f,!c.settings.timeOnly)&&(e.setHours(d.hour,d.minute,d.second,d.millisec),e.setMicroseconds(d.microsec),null!=d.timezone&&(d.support.timezone||null!==d._defaults.timezone||(d.timezone=-1*e.getTimezoneOffset()),e=$.timepicker.timezoneAdjust(e,d.timezone,$.timepicker.timezoneOffsetString(-e.getTimezoneOffset())))),e}return this._base_getDateDatepicker(a,b)}},$.datepicker._base_parseDate=$.datepicker.parseDate,$.datepicker.parseDate=function(a,b,c){var d;try{d=this._base_parseDate(a,b,c)}catch(e){if(!(e.indexOf(":")>=0))throw e;d=this._base_parseDate(a,b.substring(0,b.length-(e.length-e.indexOf(":")-2)),c),$.timepicker.log("Error parsing the date string: "+e+"\ndate string = "+b+"\ndate format = "+a)}return d},$.datepicker._base_formatDate=$.datepicker._formatDate,$.datepicker._formatDate=function(a,b,c,d){var e=this._get(a,"timepicker");return e?(e._updateDateTime(a),e.$input.val()):this._base_formatDate(a)},$.datepicker._base_optionDatepicker=$.datepicker._optionDatepicker,$.datepicker._optionDatepicker=function(a,b,c){var d,e=this._getInst(a);if(!e)return null;var f=this._get(e,"timepicker");if(f){var g,h,i,j,k=null,l=null,m=null,n=f._defaults.evnts,o={};if("string"==typeof b){if("minDate"===b||"minDateTime"===b)k=c;else if("maxDate"===b||"maxDateTime"===b)l=c;else if("onSelect"===b)m=c;else if(n.hasOwnProperty(b)){if("undefined"==typeof c)return n[b];o[b]=c,d={}}}else if("object"==typeof b){b.minDate?k=b.minDate:b.minDateTime?k=b.minDateTime:b.maxDate?l=b.maxDate:b.maxDateTime&&(l=b.maxDateTime);for(g in n)n.hasOwnProperty(g)&&b[g]&&(o[g]=b[g])}for(g in o)o.hasOwnProperty(g)&&(n[g]=o[g],d||(d=$.extend({},b)),delete d[g]);if(d&&isEmptyObject(d))return;if(k?(k=0===k?new Date:new Date(k),f._defaults.minDate=k,f._defaults.minDateTime=k):l?(l=0===l?new Date:new Date(l),f._defaults.maxDate=l,f._defaults.maxDateTime=l):m&&(f._defaults.onSelect=m),k||l)return j=$(a),i=j.datetimepicker("getDate"),h=this._base_optionDatepicker.call($.datepicker,a,d||b,c),j.datetimepicker("setDate",i),h}return void 0===c?this._base_optionDatepicker.call($.datepicker,a,b):this._base_optionDatepicker.call($.datepicker,a,d||b,c)};var isEmptyObject=function(a){var b;for(b in a)if(a.hasOwnProperty(b))return!1;return!0},extendRemove=function(a,b){$.extend(a,b);for(var c in b)(null===b[c]||void 0===b[c])&&(a[c]=b[c]);return a},detectSupport=function(a){var b=a.replace(/'.*?'/g,"").toLowerCase(),c=function(a,b){return-1!==a.indexOf(b)?!0:!1};return{hour:c(b,"h"),minute:c(b,"m"),second:c(b,"s"),millisec:c(b,"l"),microsec:c(b,"c"),timezone:c(b,"z"),ampm:c(b,"t")&&c(a,"h"),iso8601:c(a,"Z")}},convert24to12=function(a){return a%=12,0===a&&(a=12),String(a)},computeEffectiveSetting=function(a,b){return a&&a[b]?a[b]:$.timepicker._defaults[b]},splitDateTime=function(a,b){var c=computeEffectiveSetting(b,"separator"),d=computeEffectiveSetting(b,"timeFormat"),e=d.split(c),f=e.length,g=a.split(c),h=g.length;return h>1?{dateString:g.splice(0,h-f).join(c),timeString:g.splice(0,f).join(c)}:{dateString:a,timeString:""}},parseDateTimeInternal=function(a,b,c,d,e){var f,g,h;if(g=splitDateTime(c,e),f=$.datepicker._base_parseDate(a,g.dateString,d),""===g.timeString)return{date:f};if(h=$.datepicker.parseTime(b,g.timeString,e),!h)throw"Wrong time format";return{date:f,timeObj:h}},selectLocalTimezone=function(a,b){if(a&&a.timezone_select){var c=b||new Date;a.timezone_select.val(-c.getTimezoneOffset())}};$.timepicker=new Timepicker,$.timepicker.timezoneOffsetString=function(a,b){if(isNaN(a)||a>840||-720>a)return a;var c=a,d=c%60,e=(c-d)/60,f=b?":":"",g=(c>=0?"+":"-")+("0"+Math.abs(e)).slice(-2)+f+("0"+Math.abs(d)).slice(-2);return"+00:00"===g?"Z":g},$.timepicker.timezoneOffsetNumber=function(a){var b=a.toString().replace(":","");return"Z"===b.toUpperCase()?0:/^(\-|\+)\d{4}$/.test(b)?("-"===b.substr(0,1)?-1:1)*(60*parseInt(b.substr(1,2),10)+parseInt(b.substr(3,2),10)):parseInt(a,10)},$.timepicker.timezoneAdjust=function(a,b,c){var d=$.timepicker.timezoneOffsetNumber(b),e=$.timepicker.timezoneOffsetNumber(c);return isNaN(e)||a.setMinutes(a.getMinutes()+-d- -e),a},$.timepicker.timeRange=function(a,b,c){return $.timepicker.handleRange("timepicker",a,b,c)},$.timepicker.datetimeRange=function(a,b,c){$.timepicker.handleRange("datetimepicker",a,b,c)},$.timepicker.dateRange=function(a,b,c){$.timepicker.handleRange("datepicker",a,b,c)},$.timepicker.handleRange=function(a,b,c,d){function e(e,f){var g=b[a]("getDate"),h=c[a]("getDate"),i=e[a]("getDate");if(null!==g){var j=new Date(g.getTime()),k=new Date(g.getTime());j.setMilliseconds(j.getMilliseconds()+d.minInterval),k.setMilliseconds(k.getMilliseconds()+d.maxInterval),d.minInterval>0&&j>h?c[a]("setDate",j):d.maxInterval>0&&h>k?c[a]("setDate",k):g>h&&f[a]("setDate",i)}}function f(b,c,e){if(b.val()){var f=b[a].call(b,"getDate");null!==f&&d.minInterval>0&&("minDate"===e&&f.setMilliseconds(f.getMilliseconds()+d.minInterval),"maxDate"===e&&f.setMilliseconds(f.getMilliseconds()-d.minInterval)),f.getTime&&c[a].call(c,"option",e,f)}}d=$.extend({},{minInterval:0,maxInterval:0,start:{},end:{}},d);var g=!1;return"timepicker"===a&&(g=!0,a="datetimepicker"),$.fn[a].call(b,$.extend({timeOnly:g,onClose:function(a,b){e($(this),c)},onSelect:function(a){f($(this),c,"minDate")}},d,d.start)),$.fn[a].call(c,$.extend({timeOnly:g,onClose:function(a,c){e($(this),b)},onSelect:function(a){f($(this),b,"maxDate")}},d,d.end)),e(b,c),f(b,c,"minDate"),f(c,b,"maxDate"),$([b.get(0),c.get(0)])},$.timepicker.log=function(){window.console&&window.console.log&&window.console.log.apply&&window.console.log.apply(window.console,Array.prototype.slice.call(arguments))},$.timepicker._util={_extendRemove:extendRemove,_isEmptyObject:isEmptyObject,_convert24to12:convert24to12,_detectSupport:detectSupport,_selectLocalTimezone:selectLocalTimezone,_computeEffectiveSetting:computeEffectiveSetting,_splitDateTime:splitDateTime,_parseDateTimeInternal:parseDateTimeInternal},Date.prototype.getMicroseconds||(Date.prototype.microseconds=0,Date.prototype.getMicroseconds=function(){return this.microseconds},Date.prototype.setMicroseconds=function(a){return this.setMilliseconds(this.getMilliseconds()+Math.floor(a/1e3)),this.microseconds=a%1e3,this}),$.timepicker.version="1.6.3"}});

// Callback function for adding questions.
function addQuestion( promptInputVal ) {
	var $id = promptInputVal.replace( /[^A-Za-z0-9]/g, '' ).toLowerCase().substring( 0,30 );
	var itemMarkup =  '<li class="question-list-item custom"><span class="dashicons dashicons-sort"></span><label>' + promptInputVal + '</label> <input type="checkbox" id="'+ $id +'" name="mls_options[enabled_questions]['+ $id +']" value="'+ promptInputVal +'" checked><a href="#remove">Remove</a></li>';
	jQuery( '#questions-wrapper' ).append( itemMarkup );
}

jQuery( 'document' ).ready( function( $ ) {
	if( jQuery('#notice_modal' ).length > 0 ) {
		tb_show( jQuery('#notice_modal' ).data( 'windowtitle' ) , '#TB_inline?height=130&width=400&inlineId=notice_modal');
	}

	function display( value, id ) {
		var $li_item = jQuery( "<li>" )
			.addClass( 'ppm-exempted-list-item user-btn button button-secondary' )
			.attr( 'data-id', id )
			.append( '<a href="#" class="remove remove-item"></a>' );

		if ( parseInt( id ) > 0 ) {
			$existing_val = jQuery( "#ppm-exempted-users" ).val();
			if ( $existing_val.indexOf( id ) === -1 ) {
				$li_item.prepend( value ).prependTo( "ul#ppm-exempted-list" )
			}
			add_exemption( $li_item, id, 'users' );
		} else {
			$existing_val = jQuery( "#ppm-exempted-roles" ).val();
			if ( $existing_val.indexOf( id ) === -1 ) {
				$li_item.prepend( value ).prependTo( "ul#ppm-exempted-list" )
			}
			add_exemption( $li_item, id, 'roles' );
		}
		jQuery( "#ppm-exempted-list" ).scrollTop( 0 );
	}

	function add_exemption( $li_item, $id, $type ) {
		var $existing_val;
		$li_item.addClass( "ppm-exempted-" + $type );
		$existing_val = jQuery( "#ppm-exempted-" + $type ).val();

		if ( $existing_val === '' ) {
			$existing_val = [ ];
		} else {
			$existing_val = JSON.parse( $existing_val );
		}

		$existing_val.indexOf( $id ) === -1 ? $existing_val.push( $id ) : alert( 'Item already exmpt' );

		jQuery( "#ppm-exempted-" + $type ).val( JSON.stringify( $existing_val ) );

	}

	function remove_exemption( $id, $type ) {
		var $existing_val;
		$existing_val = jQuery( "#ppm-exempted-" + $type ).val();

		if ( $existing_val === '' ) {
			return;
		} else {
			$existing_val = JSON.parse( $existing_val );
			var index = $existing_val.indexOf( $id );
			if ( index > -1 ) {
				$existing_val.splice( index, 1 );
			}
		}
		jQuery( "#ppm-exempted-" + $type ).val( JSON.stringify( $existing_val ) );
	}

	jQuery( "#ppm-exempted, .reset-user-search" ).autocomplete( {
		source: function( request, response ) {
			$.get( {
				url: ppm_ajax.ajax_url,
				dataType: 'json',
				data: {
					action: 'get_users_roles',
					search_str: request.term,
					user_role: jQuery( '#ppm-exempted-role' ).val(),
					exclude_users: JSON.stringify( jQuery( "#ppm-exempted-users" ).val() ),
					_wpnonce: ppm_ajax.settings_nonce
				},
				success: function( data ) {
					response( data );
				}
			} );
		},
		minLength: 2,
		select: function( event, ui ) {
			display( ui.item.value, ui.item.id );
			jQuery( this ).val( "" );
			return false;

		}
	} );

	jQuery( '#ppm-exempted' ).on( 'keypress', function( e ) {
		var code = ( e.keyCode ? e.keyCode : e.which );
		if ( code == 13 ) { //Enter keycode
			return false;
		}
	} );

	jQuery( '#ppm-custom_login_url, #ppm-custom_login_redirect' ).on( 'keypress', function( e ) {
		var code = ( e.keyCode ? e.keyCode : e.which );
		if (e.keyCode >= 48 && e.keyCode <= 57 || e.keyCode == 189 || e.keyCode == 45 || (e.charCode >= 65 && e.charCode <= 90) || (e.charCode >= 97 && e.charCode <= 122) || (e.charCode == 32)) {
			return true;
		} else {
			return false;
		}
	} );

	jQuery( "#ppm-exempted-list" ).on( 'click', 'a.remove', function( event ) {
		event.preventDefault();
		var $list_item = jQuery( this ).closest( 'li.ppm-exempted-list-item' );

		var $id = $list_item.data( 'id' ).toString();

		if ( $list_item.hasClass( 'ppm-exempted-users' ) ) {
			remove_exemption( $id, 'users' );
		} else {
			remove_exemption( $id, 'roles' );
		}

		$list_item.remove();

	} );

	// Toggle areas based on data attr.
	hideShowToggleables();
	jQuery( '[data-toggle-target]' ).change(function() {
		hideShowToggleables();
	});

	function hideShowToggleables() {
		if ( jQuery( '[data-toggle-target]' ).length > 0 ) {
			jQuery( '[data-toggle-target]' ).each( function () {
				jQuery( jQuery( this ).attr( 'data-toggle-target' ) ).addClass( 'disabled' );
				if ( this.checked ) {
					jQuery( jQuery( this ).attr( 'data-toggle-target' ) ).removeClass( 'disabled' );
				}
			});
		}
	}

	// Custom function for handling prompts and user inputs.
	function createPrompt( type = 'notice', text, confirmLabel = 'Ok', cancelLabel = 'Cancel', callback ) {
		if ( type == 'notice' ) {
			var markup = '<div id="c4wp-prompt-wrapper" class="type-' + type + '"><div id="c4wp-prompt"><p>' + text + '</p><br><a href="#confirm-prompt" class="button button-primary">' + confirmLabel + '</a></div></div>';
		} else if ( type == 'prompt' ) {
			var markup = '<div id="c4wp-prompt-wrapper" class="type-' + type + '"><div id="c4wp-prompt"><p>' + text + '</p><input type="text" id="prompt-value"><br><br><a href="#confirm-prompt" class="button button-primary" data-cb="' + callback + '">' + confirmLabel + '</a> <a href="#cancel-prompt" class="button button-secondary">' + cancelLabel + '</a></div></div>';
		}		
		jQuery( markup ).appendTo('body');
	}

	// Handle prompt actions
	jQuery( document ).on( 'click', '#c4wp-prompt-wrapper a', function( event ) {
		event.preventDefault();
		var wrapper       = jQuery( this ).closest( '#c4wp-prompt-wrapper' );
		var currentButton = jQuery( this );
		if ( jQuery( wrapper ).hasClass( 'type-notice' ) ) {
			if ( jQuery( currentButton).attr( 'href' ) == '#confirm-prompt' ) {
				jQuery( wrapper ).fadeOut( 300 );
				setTimeout( function() {
					jQuery( wrapper ).remove();
				}, 300 );
			}
		} else if ( jQuery( wrapper ).hasClass( 'type-prompt' ) ) {
			if ( jQuery( currentButton ).attr( 'href' ) == '#confirm-prompt' ) {
				var promptInputVal = jQuery( wrapper ).find( '#prompt-value' ).val();
				window[ jQuery( currentButton ).attr( 'data-cb' ) ].call( this, promptInputVal );
				jQuery( wrapper ).fadeOut( 300 );
				setTimeout( function() {
					jQuery( wrapper ).remove();
				}, 300 );
			} else if ( jQuery( currentButton ).attr( 'href' ) == '#cancel-prompt' ) {
				jQuery( wrapper ).fadeOut( 300 ).remove();
			}
		}
		updateQuestionMaxCount()
	});

	jQuery( document ).on( 'click', '#questions-wrapper a[href="#disable"]', function( event ) {
		event.preventDefault();
		var ourLink = jQuery( this );
		var ourCheck = jQuery( ourLink ).parent().find( 'input[type="checkbox"]' );;
		if ( jQuery( ourCheck  ).is( ':checked' ) ) {
			jQuery( ourCheck  ).prop( "checked", false );
			jQuery( ourLink ).text( 'Enable' );
			jQuery( ourLink ).parent().addClass( 'disabled-question' );
		} else {
			jQuery( ourCheck  ).prop( "checked", true );
			jQuery( ourLink ).text( 'Disable' );
			jQuery( ourLink ).parent().removeClass( 'disabled-question' );
		}
		updateQuestionMaxCount()
	});

	jQuery( document ).on( 'click', '#questions-wrapper a[href="#remove"]', function( event ) {
		event.preventDefault();
		var ourLink = jQuery( this );
		var ourCheck = jQuery( ourLink ).parent().find( 'input[type="checkbox"]' );;
		if ( jQuery( ourCheck  ).is( ':checked' ) ) {
			jQuery( ourCheck  ).prop( "checked", false );
			//jQuery( ourLink ).text( 'Enable' );
			//jQuery( ourLink ).parent().addClass( 'disabled-question' );
			jQuery( ourLink ).parent().slideUp();
		} else {
			jQuery( ourCheck  ).prop( "checked", true );
			jQuery( ourLink ).text( 'Disable' );
			jQuery( ourLink ).parent().removeClass( 'disabled-question' );
		}
		updateQuestionMaxCount()
	});


	

	// Security questions settings.
	jQuery( "#questions-wrapper" ).sortable({ 
		opacity: 0.6, 
		cursor: 'move'  
	});

	jQuery( document ).on( 'click', 'a[href="#add-question"]', function( event ) {
		createPrompt( 'prompt', 'Enter your new question and click add to continue.', 'Add question', 'Canel', 'addQuestion' );
	});

	// Inactive exempted.
	function display_inactive_exempted( value, id ) {
		var $li_item = jQuery( "<li>" )
			.addClass( 'ppm-exempted-list-item user-btn button button-secondary' )
			.attr( 'data-id', id )
			.append( '<a href="#" class="remove remove-item"></a>' );

		$li_item.prepend( value ).prependTo( "ul#ppm-inactive-exempted-list" );

		if ( parseInt( id ) > 0 ) {
			add_inactive_exemption( $li_item, id, 'users' );
		} else {
			add_inactive_exemption( $li_item, id, 'roles' );
		}
		jQuery( "#ppm-inactive-exempted-list" ).scrollTop( 0 );
	}

	function add_inactive_exemption( $li_item, $id, $type ) {
		var $existing_val;
		$li_item.addClass( "ppm-exempted-user" );
		$existing_val = jQuery( "#ppm-inactive-exempted" ).val();
		if ( $existing_val === '' ) {
			$existing_val = [ ];
		} else {
			$existing_val = JSON.parse( $existing_val );
		}
		$existing_val.indexOf( $id ) === -1 ? $existing_val.push( $id ) : alert( 'Item already exempt' );
		jQuery( "#ppm-inactive-exempted" ).val( JSON.stringify( $existing_val ) );
	}

	jQuery( "#ppm-inactive-exempted-search" ).autocomplete( {
		source: function( request, response ) {
			$.get( {
				url: ppm_ajax.ajax_url,
				dataType: 'json',
				data: {
					action: 'get_users_roles',
					search_str: request.term,
					_wpnonce: ppm_ajax.settings_nonce
				},
				success: function( data ) {
					response( data );
				}
			} );
		},
		minLength: 2,
		select: function( event, ui ) {
			display_inactive_exempted( ui.item.value, ui.item.value );
			jQuery( this ).val( "" );
			return false;
		}
	} );

	jQuery( "#ppm-inactive-exempted-list" ).on( 'click', 'a.remove', function( event ) {
		event.preventDefault();
		var $list_item = jQuery( this ).closest( 'li.ppm-exempted-list-item' );
		var $id = $list_item.text().trim().toString();
		remove_inactive_exemption( $id, 'users' );
		$list_item.remove();
	} );

	function remove_inactive_exemption( $id, $type ) {
		var $existing_val;
		$existing_val = jQuery( "#ppm-inactive-exempted" ).val();
		if ( $existing_val === '' ) {
			return;
		} else {
			$existing_val = JSON.parse( $existing_val );
			var index = $existing_val.indexOf( $id );
			if ( index > -1 ) {
				$existing_val.splice( index, 1 );
			}
		}
		jQuery( "#ppm-inactive-exempted" ).val( JSON.stringify( $existing_val ) );
	}

	jQuery( '#ppm-wp-test-email' ).on( 'click', function ( event ) {
		jQuery( this ).prop( 'disabled', true );
		jQuery( '#ppm-wp-test-email-loading' ).css( 'visibility', 'visible' );
		$.get( {
			url: ppm_ajax.ajax_url,
			dataType: 'json',
			data: {
				action: 'mls_send_test_email',
				_wpnonce: ppm_ajax.test_email_nonce
			},
			success: function ( data ) {
				jQuery( '.ppm-email-notice' ).remove();
				jQuery( '#ppm-wp-test-email-loading' ).css( 'visibility', 'hidden' );
				jQuery( "html, body" ).animate( { scrollTop: 0 } );
				if ( data.success ) {
					jQuery( '.wrap .page-head h2' ).after( '<div class="notice notice-success ppm-email-notice"><p>' + data.data.message + '</p></div>' );
				} else {
					jQuery( '.wrap .page-head h2' ).after( '<div class="notice notice-error ppm-email-notice"><p>' + data.data.message + '</p></div>' );
				}
				jQuery( '#ppm-wp-test-email' ).prop( 'disabled', false );
			}
		} );
	} );

	jQuery('#ppm_master_switch').change(function() {
		if ( jQuery( this ).parents( 'table' ).data( 'id' ) !='' ) {
			if( jQuery(this).is(':checked') ) {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_mls_global_reset_button], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').attr('disabled', 'disabled');
				jQuery('.mls-settings').slideUp( 300 ).addClass('disabled');
				jQuery(this).val( 1 );
				jQuery( '#inherit_policies' ).val( 1 );
			}
			else {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_mls_global_reset_button], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').removeAttr('disabled');
				jQuery('.mls-settings').slideDown( 300 ).removeClass('disabled');
				jQuery(this).val( 0 );
				jQuery( '#inherit_policies' ).val( 0 );
			}
		} else {
			if( jQuery(this).is(':checked') ) {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_mls_global_reset_button], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').removeAttr('disabled');
				jQuery(' .nav-tab-wrapper').fadeIn( 300 ).removeClass('disabled');
				jQuery('.mls-settings').slideDown( 300 ).removeClass('disabled');
				jQuery(this).val( 1 );
			}
			else {
				jQuery('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_mls_global_reset_button], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').attr('disabled', 'disabled');
				jQuery('.nav-tab-wrapper').fadeOut( 300 ).addClass('disabled');
				jQuery('.mls-settings').slideUp( 300 ).addClass('disabled');
				jQuery(this).val( 0 );
			}
		}
		jQuery(this).removeAttr('disabled');
		jQuery('#ppm-wp-settings input[type="hidden"]').removeAttr('disabled');
		// trigger change so it's disabled state is not broken by the code above.
		jQuery( '#ppm-exclude-special' ).change();
		// trigger a change to ensure initial state of inactive users is correct.
		jQuery( '#ppm-expiry-value' ).change();

		// Check status of failed login options.
		disable_enabled_failed_login_options();
	}).change();

	// enforce password
	jQuery( '#ppm_enforce_password' ).change( function() {
		if ( jQuery( this ).is( ':checked' ) ) {
			jQuery( this ).parents( 'form' ).find( 'input, select, button' ).not('input[name=_ppm_save],input[type="hidden"], input#_mls_global_reset_button').not( this ).attr( 'disabled', 'disabled' );
			jQuery('.mls-settings, .master-switch').addClass('disabled');
			jQuery( '#inherit_policies' ).val( 0 );
		} else {
			if ( jQuery( '#inherit_policies' ).val() == 0 ) {
				// Set value
				if ( jQuery( '#ppm_master_switch' ).is( ':checked' ) ) {
					jQuery( '#inherit_policies' ).val( 1 );
					jQuery( this ).parents( 'form' ).find( 'button, #ppm_master_switch' ).removeAttr( 'disabled' );
					jQuery('.master-switch').removeClass('disabled');
				} else {
					jQuery( '#inherit_policies' ).val( 0 );
					jQuery('input[id!=ppm_enforce_password][name!=_ppm_save][name!=_mls_global_reset_button], select, button','#ppm-wp-settings').removeAttr('disabled');
					jQuery('.mls-settings, .master-switch').removeClass('disabled');
				}
			}
		}
	} ).change();

	// Exclude Special Characters Input.
	jQuery( '#ppm-exclude-special' ).change(
		function() {
			if ( jQuery( '.mls-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( '#ppm_master_switch' ).is( ':checked' ) && jQuery( this ).is( ':checked' ) ) {
				jQuery( '#ppm-excluded-special-chars' ).prop( 'disabled', false );
			} else if ( jQuery( '#ppm_master_switch' ).is( ':checked' ) ) {
				jQuery( '#ppm-excluded-special-chars' ).prop( 'disabled', true );
			}
		}
	).change();

	jQuery( '#ppm-inactive-users-reset-on-unlock' ).change(
		function() {
			if ( jQuery( '.mls-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.disabled-deactivated-message-wrapper' ).removeClass( 'disabled' );
			} else {
				jQuery( '.disabled-deactivated-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	jQuery( '#ppm-inactive-users-disable-reset' ).change(
		function() {
			if ( jQuery( '.mls-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.disabled-self-reset-message-wrapper' ).removeClass( 'disabled' );
			} else {
				jQuery( '.disabled-self-reset-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	jQuery( '#disable-self-reset' ).change(
		function() {
			if ( jQuery( '.mls-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( jQuery( this ).is( ':checked' ) ) {
				jQuery( '.disabled-reset-message-wrapper' ).removeClass( 'disabled' );
			} else {
				jQuery( '.disabled-reset-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	// trigger change so it's initial state is set.
	jQuery( '#ppm-exclude-special' ).change();

	// trigger a change to ensure initial state of inactive users is correct.
	jQuery( '#ppm-expiry-value' ).change();

	hideShowResetSettings();
	jQuery( '[name="reset_type"]' ).change(function() {
		hideShowResetSettings();
	});

	function hideShowResetSettings() {
		if ( jQuery( '[name="reset_type"]' ).length > 0 ) {
			jQuery( '[data-active-shows-setting]' ).each( function () {
				jQuery( jQuery( this ).attr( 'data-active-shows-setting' ) ).addClass( 'hidden' );
			});
			
			var currVal = jQuery( '[name="reset_type"]:checked' ).val();
			jQuery( jQuery( '[name="reset_type"]:checked' ).attr( 'data-active-shows-setting' ) ).removeClass( 'hidden' );
		
		}
	}	

	setRequiredValue();
	jQuery( '#ppm-enable-expiry-notify' ).change(function() {
		setRequiredValue();
	});

	function setRequiredValue() {
		var currVal = jQuery( '#ppm-enable-expiry-notify:checked' ).val();
		if ( ! currVal ) {
			jQuery( '[name="mls_options[notify_password_expiry_days]"]' ).removeAttr( 'required' );
		} else {
			jQuery( 'input[name="mls_options[notify_password_expiry_days]"]' ).prop( 'required', true ) ;
		}
		if ( jQuery( '[name="mls_options[notify_password_expiry_days]"]' ).val() == '0' ) {
			jQuery( '[name="mls_options[notify_password_expiry_days]"]' ).val( '3' );
		}
	}

	setRestrictLoginOptions();
	jQuery( 'input[type=radio][name="mls_options[restrict_login_credentials]"]' ).change(function() {
		setRestrictLoginOptions();
	});

	function setRestrictLoginOptions() {
		var currVal = jQuery( '[name="mls_options[restrict_login_credentials]"]:checked' ).val();
		if ( 'default' == currVal ) {
			jQuery( '.restrict-message-field' ).slideUp( 300 );
		} else {
			jQuery( '.restrict-message-field' ).slideDown( 300 );
		}
	}

	// Mass reset.
	jQuery( 'input#_mls_global_reset_button' ).on( 'click', function( event ) {
		event.preventDefault;
		hideShowResetSettings();

		// If check class exists OR not
		if ( jQuery( '#ppm-wp-settings' ).hasClass( 'ppm_reset_all' ) ) return true;

		jQuery( '#reset-all-modal' ).addClass( 'show' );

		// Remove current user field
		jQuery( '#ppm-wp-settings' ).find( '.current_user' ).remove();
		return false;
	} );

	jQuery( 'a[href="#modal-cancel"]' ).on( 'click', function( event ) {
		jQuery( jQuery( this ).attr( 'data-modal-close-target' ) ).removeClass( 'show' );
		var attr = jQuery(this).attr('data-reload-on-close');
		if ( typeof attr !== 'undefined' && attr !== false ) {
			setTimeout( function() {
				window.location.reload();
			}, 300 );
		}
	
	});

	// Proceed with PW reset.
	jQuery( 'a[href="#modal-proceed"]' ).on( 'click', function( event ) {
		var currVal        = jQuery( '[name="reset_type"]:checked' ).val();
		var sendResetEmail = jQuery( '#send_reset_email' ).is( ':checked' );
		var includeSelf    = jQuery( '#include_reset_self' ).is( ':checked' );
		var killSessions   = jQuery( '#terminate_sessions_on_reset' ).is( ':checked' );
		var resetWhen      = jQuery( '[name="reset_when"]:checked' ).val();
		var nonce = jQuery( this ).attr( 'data-reset-nonce' );
		
		var role     = false;
		var users    = [];
		var fileText = false;

		if ( currVal == 'reset-role' ) {
			var role = jQuery( '#reset-role-select option:selected' ).val();
		} else if ( currVal == 'reset-users' ) {
			var users = [];
			jQuery( '.reset-user-list li' ).each(function () {
				users.push( jQuery(this).attr( 'data-id' ) );
			});

			if ( users.length == 0 ) {
				jQuery( '.reset-user-search' ).css( 'border-color', 'red' );
				setTimeout( function() {					
					jQuery( '.reset-user-search' ).css( 'border-color', '#8c8f94' );
				}, 300 );				
				return;
			}
		} else if ( currVal == 'reset-csv' ) {
			var fileInput = document.getElementById( "users-reset-file" );
			var extention = jQuery( '#users-reset-file' ).val().split('.').pop().toLowerCase();
			var fileExtensions = ['csv','txt'];
			if ( jQuery.inArray( extention, fileExtensions ) != -1 ) {
				// File ok.
			} else {
				jQuery( '.reset-users-file' ).after('<div id="csvWarning" style="color: red;">' +  ppm_ajax.csv_file_error + '</div>');
				setTimeout( function() {
					jQuery( '#csvWarning' ).slideUp( 300 ).delay( 400 ).remove();
				}, 3000 );				
				return;
			}

			var reader    = new FileReader();
			reader.readAsText( fileInput.files[0] );

			reader.onload = function () {
				var fileText = reader.result;
				var isValid = /^[0-9,]*$/.test(fileText);
				var lengthError = false;

				if ( ! isValid ) {
					var trySplit = fileText.split(/\r?\n/);
					var idArr    = trySplit.join();
					isValid = ( idArr.length > 0 );
					if ( ! isValid ) {
						lengthError = true;
					}
				}

				if ( isValid ) {
					jQuery.ajax({
						type: 'POST',
						url: ajaxurl,
						async: true,
						data: {
							action: 'mls_process_reset',
							nonce : nonce,
							reset_type: currVal,
							role : role,
							users : users,
							send_reset : sendResetEmail,
							kill_sessions : killSessions,
							include_self : includeSelf,
							reset_when : resetWhen,
							file_text : fileText,
						},
						success: function ( result ) {	
							if ( result.success ) {
								jQuery( 'a[href="#modal-proceed"]' ).remove();
								jQuery( 'a[href="#modal-cancel"]' ).text( 'Close' );
								jQuery( '.mls-modal-content-wrapper' ).slideUp( 300 );
								setTimeout( function() {
									jQuery( '.mls-modal-content-wrapper' ).html( '<h3>' +  ppm_ajax.reset_done_title + '</h3><p class="description">' +  ppm_ajax.reset_done_title + '</p><br>' );
								}, 300 );
								setTimeout( function() {
									jQuery( '.mls-modal-content-wrapper' ).slideDown( 300 );
								}, 350 );
							} else {
								jQuery( '.reset-users-file' ).after('<div id="csvWarning" style="color: red;">' + result.data + '</div>');
								setTimeout( function() {
									jQuery( '#csvWarning' ).slideUp( 300 ).delay( 400 ).remove();
								}, 3000 );
							}					
						}
					});
				} else {
					jQuery( '.reset-users-file' ).after('<div id="csvWarning" style="color: red;">' +  ppm_ajax.csv_error + '</div>');
					setTimeout( function() {
						jQuery( '#csvWarning' ).slideUp( 300 ).delay( 400 ).remove();
					}, 3000 );
					if ( lengthError ) {
						jQuery( '.reset-users-file' ).after('<div id="csvLengthWarning" style="color: red;">' +  ppm_ajax.csv_error_length + '</div>');
						setTimeout( function() {
							jQuery( '#csvLengthWarning' ).slideUp( 300 ).delay( 400 ).remove();
						}, 3000 );
					}
				}

			}
			
			return true;
		}

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'mls_process_reset',
				nonce : nonce,
				reset_type: currVal,
				role : role,
				users : users,
				send_reset : sendResetEmail,
				kill_sessions : killSessions,
				include_self : includeSelf,
				reset_when : resetWhen,
				file_text : fileText,
			},
			success: function ( result ) {		
				jQuery( 'a[href="#modal-proceed"]' ).remove();
				jQuery( 'a[href="#modal-cancel"]' ).text( 'Close' ).attr( 'data-reload-on-close', true );
				jQuery( '.mls-modal-content-wrapper' ).slideUp( 300 );
				setTimeout( function() {
					jQuery( '.mls-modal-content-wrapper' ).html( '<h3>' +  ppm_ajax.reset_done_title + '</h3><p class="description">' +  ppm_ajax.reset_done_title + '</p><br>' );
				}, 300 );
				setTimeout( function() {
					jQuery( '.mls-modal-content-wrapper' ).slideDown( 300 );
				}, 350 );
			}
		});
	});

	disable_enabled_failed_login_options();
	jQuery( '#ppm-failed-login-policies-enabled' ).change(function() {
		disable_enabled_failed_login_options();
	});

	disable_enabled_gdpr_options();
	jQuery( '#ppm_enable_gdpr_banner' ).change(function() {
		disable_enabled_gdpr_options();
	});
	
	disable_enabled_inactive_users_options();
	jQuery( '#ppm-inactive-users-enabled' ).change(function() {
		disable_enabled_inactive_users_options();
	});

	disable_enabled_timed_login_options();
	jQuery( '#ppm-timed-logins' ).change(function() {
		disable_enabled_timed_login_options();
	});

	disable_enabled_restrict_login_ip_options();
	jQuery( '#mls-restrict-login-ip' ).change(function() {
		disable_enabled_restrict_login_ip_options();
	});

	jQuery( '[data-toggle-other-areas]' ).each(function () {
		handleToggleArea( jQuery( this ) );
	});
	jQuery( '[data-toggle-other-areas]' ).change(function() {
		handleToggleArea( jQuery( this ) );
	});

	// Handle multiple role setting.
	check_multiple_roles_status();
	jQuery( '#ppm-users-have-multiple-roles' ).change( check_multiple_roles_status ).change();

	jQuery( "#roles_sortable" ).sortable({
		update: function(event, ui) {       
			var roles = [];
			jQuery( '#roles_sortable [data-role-key]' ).each(function () {
				roles.push( '"' + jQuery(this).attr( 'data-role-key') + '"' );
			});
			jQuery( '#multiple-role-order' ).val( jQuery.parseJSON( '[' + roles + ']' ) );
		},
	});
	jQuery( "#roles_sortable" ).disableSelection();

	// Correct times if something bad is entered.
	jQuery( '.timed-logins-tr [type="number"]:not([name="mls_options[restrict_login_ip_count]"])' ).change(function( e ) {		
		var val = parseInt( jQuery( this )[0]['value'] );
		var minval = parseInt( jQuery( this )[0]['min'] );
		var maxval = parseInt( jQuery( this )[0]['max'] );

		if ( val >= minval && val <= maxval  ) {
			if ( val < 10  ) {
				jQuery( this ).val( '0' + val );
			}
			return;
		} else {
			if ( val < minval ) {
				jQuery( this ).val( minval );
			} else if ( val > maxval ) {				
				jQuery( this ).val( maxval );
			}
		}
		
	});

	jQuery( '.timed-logins-tr [type="number"]:not([name="mls_options[restrict_login_ip_count]"])' ).each(function () {
		var val = parseInt( jQuery( this )[0]['value'] );
		if ( val < 10  ) {
			jQuery( this ).val( '0' + val );
		}
	});

	jQuery( '.timed-logins-tr select' ).change(function( e ) {	
		var ourName = jQuery( this ).attr( 'name' ).toString();
		var isFromSelect = false

		if( ourName.toLowerCase().includes( 'from_am_or_pm' ) ) {
			isFromSelect = true;
		}

		var ourCurrentVal = jQuery( this ).val();
		var theOtherCurrentVal = jQuery( this ).parent().find( 'select' ).not( this ).val();

		if ( isFromSelect ) {
			if ( ourCurrentVal == 'pm' && theOtherCurrentVal == 'am' ) {
				jQuery( this ).val( 'am' );
			}
		} else {
			if ( ourCurrentVal == 'am' && theOtherCurrentVal == 'pm' ) {
				jQuery( this ).val( 'pm' );
			}
		}
	});

	jQuery( '.timed-login-option input[type="checkbox"]' ).each(function () {
		if ( jQuery( this ).prop('checked') ) {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).removeClass( 'disabled' );
		} else {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).addClass( 'disabled' );
		}
	});

	jQuery( '.timed-login-option input[type="checkbox"]' ).change(function() {
		if ( jQuery( this ).prop('checked') ) {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).removeClass( 'disabled' );
		} else {
			jQuery( this ).parent().find( 'input, select, span' ).not( this ).addClass( 'disabled' );
		}
	});

	jQuery( '#prompt-counter' ).change(function() {
		updateQuestionMaxCount();
	});

	if ( jQuery('.user-login-policies-heading').length && jQuery('.user-login-policies-heading').length > 1 ) {
		jQuery('.user-login-policies-heading').not(":eq(0)").hide();
	}	
	
	jQuery('body').on('click', 'a#add-login_denied-countries', function(e) {
		e.preventDefault();
		var newIP = jQuery('#login_geo_countries_input').val().toUpperCase();
		var possibleCodes = getCodesList(true);
		var found = possibleCodes.includes(newIP);
		var currentVal = jQuery('#login_geo_countries').val();

		if ( currentVal.indexOf(newIP) != -1 ) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: green;">Already added</span>').insertAfter('a#add-denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (!found) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error">Code not found</span>').insertAfter('a#add-comment_denied-countries');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if ( ! jQuery('#login_geo_countries').val() ) {
			jQuery('#login_geo_countries').val( newIP ).trigger("change");
		} else {
			var newVal = jQuery('#login_geo_countries').val() + ',' +  newIP;
			jQuery('#login_geo_countries').val( newVal ).trigger("change");
		}
		jQuery('#login_geo_countries_input').val('');
	});

	jQuery('body').on('click', 'a#add-restrict_login_allowed_ips', function(e) {
		e.preventDefault();
		var newIP = jQuery('#restrict_login_allowed_ips_input').val().toUpperCase();
		var currentVal = jQuery('#restrict_login_allowed_ips').val();

		if ( currentVal.indexOf(newIP) != -1 ) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: red; margin-left: 10px;">Already added</span>').insertAfter('a#add-restrict_login_allowed_ips');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if ( ! ValidateIPaddress( newIP ) ) {
			if (!jQuery('#c4wp-not-found-error').length) {
				jQuery('<span id="c4wp-not-found-error" style="color: red; margin-left: 10px">Invalid</span>').insertAfter('a#add-restrict_login_allowed_ips');
				setTimeout(function() {
					jQuery('#c4wp-not-found-error').fadeOut(300).remove();
				}, 1000);
			}
			return;
		}

		if (newIP.length < 2) {
			return;
		}

		if ( ! jQuery('#restrict_login_allowed_ips').val() ) {
			jQuery('#restrict_login_allowed_ips').val( newIP ).trigger("change");
		} else {
			var newVal = jQuery('#restrict_login_allowed_ips').val() + ',' +  newIP;
			jQuery('#restrict_login_allowed_ips').val( newVal ).trigger("change");
		}
		jQuery('#restrict_login_allowed_ips_input').val('');
	});

	jQuery('body').on('click', 'span#remove-restricted-ip', function(e) {
		var removingIP = jQuery(this).attr('data-value');
		var textareaValue = jQuery('#restrict_login_allowed_ips').val();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');
		
		jQuery('#restrict_login_allowed_ips').val( newValue ).trigger("change");
		jQuery(this).parent().remove();
	});

	jQuery('body').on('click', 'span#remove-denied-country', function(e) {
		var removingIP = jQuery(this).attr('data-value');
		var textareaValue = jQuery('#login_geo_countries').val();

		if (textareaValue.indexOf(',' + removingIP) > -1) {
			var newValue = textareaValue.replace(',' + removingIP, '');
		} else {
			var newValue = textareaValue.replace(removingIP, '');
		}
		newValue = newValue.replace(/^,/, '');
		
		jQuery('#login_geo_countries').val( newValue ).trigger("change");
		jQuery(this).parent().remove();
	});

	jQuery('body').on("change", '#login_geo_countries', function(e) {
		buildDeniedCountries();
	});
	buildDeniedCountries();

	jQuery('body').on("change", '#restrict_login_allowed_ips', function(e) {
		buildIpList();
	});
	buildIpList();
} );

function check_multiple_roles_status() {
	if ( jQuery( '#ppm-users-have-multiple-roles' ).prop('checked') ) {
		jQuery( '#sortable_roles_holder' ).removeClass( 'disabled' ).slideDown( 300 );
	} else {
		jQuery( '#sortable_roles_holder' ).slideUp( 300 );
	}
}

function disable_enabled_failed_login_options() {
	jQuery( '.ppmwp-login-block-options' ).addClass( 'disabled' );
	jQuery( '.ppmwp-login-block-options :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-failed-login-policies-enabled' ).prop('checked') ) {
		jQuery( '.ppmwp-login-block-options' ).removeClass( 'disabled' );
		jQuery( '.ppmwp-login-block-options :input' ).prop( 'disabled', false );
	}
}

function disable_enabled_gdpr_options() {
	jQuery( '#gdpr-row' ).addClass( 'disabled' );
	jQuery( '#gdpr-row :input' ).prop( 'disabled', true );

	if ( jQuery( '#ppm_enable_gdpr_banner' ).prop('checked') ) {
		jQuery( '#gdpr-row' ).removeClass( 'disabled' );
		jQuery( '#gdpr-row :input' ).prop( 'disabled', false );
	}
}


function disable_enabled_inactive_users_options() {
	jQuery( '#ppmwp-inactive-setting-reset-pw-row, #ppmwp-inactive-setting-row' ).addClass( 'disabled' );
	jQuery( '#ppmwp-inactive-setting-reset-pw-row :input,  #ppmwp-inactive-setting-row :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-inactive-users-enabled' ).prop('checked') ) {
		jQuery( '#ppmwp-inactive-setting-reset-pw-row, #ppmwp-inactive-setting-row' ).removeClass( 'disabled' );
		jQuery( '#ppmwp-inactive-setting-reset-pw-row :input,  #ppmwp-inactive-setting-row :input' ).prop( 'disabled', false );
	}
}

function disable_enabled_timed_login_options() {
	jQuery( '.timed-login-option' ).addClass( 'disabled' );
	jQuery( '.timed-login-option :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-timed-logins' ).prop('checked') ) {
		jQuery( '.timed-login-option' ).removeClass( 'disabled' );
		jQuery( '.timed-login-option :input' ).prop( 'disabled', false );
	}
}

function disable_enabled_restrict_login_ip_options() {
	jQuery( '.restrict-login-option' ).addClass( 'disabled' );
	jQuery( '.restrict-login-option :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#mls-restrict-login-ip' ).prop('checked') ) {
		jQuery( '.restrict-login-option' ).removeClass( 'disabled' );
		jQuery( '.restrict-login-option :input' ).prop( 'disabled', false );
	}
}

function handleToggleArea( element ) {
	var target = element.attr( 'data-toggle-other-areas' );
	jQuery( target ).addClass( 'disabled' );
	jQuery( target + ' :input' ).prop( 'disabled', true );

	if ( jQuery( element ).prop('checked') ) {
		jQuery( target ).removeClass( 'disabled' );
		jQuery( target + ' :input' ).prop( 'disabled', false );
	}
}

function updateQuestionMaxCount() {
	var count = jQuery( '#questions-wrapper li' ).not( '.disabled-question' ).length;
	jQuery( '#prompt-counter' ).attr( 'max' , count );
}

/**
 * Shows confirm dialog after click on checkbox with two types of messages: one for checked stated and one for unchecked state.
 *
 * @param obj 		Should be the html input tag
 * @param message_disable		Message to show if checkbox is in checked state and user trying to uncheck it
 * @param message_enable 		Message to show if checkbox is in unchecked state and user trying to check it
 * @returns {boolean}
 */
function confirm_custom_messages(obj, message_disable, message_enable){
	var message;
	if( jQuery(obj).is(':checked') ){
		message = message_enable;
	}
	else{
		message = message_disable;
	}
	return confirm(message);
}

/**
 * Allow only a set of predefined characters to be typed into the input.
 */
function accept_only_special_chars_input( event ) {
	var ch     = String.fromCharCode( event.charCode );
	var filter = new RegExp( ppm_ajax.special_chars_regex );
	if ( ! filter.test( ch ) || event.target.value.indexOf( ch ) > -1 ) {
		event.preventDefault();
	}
}

/**
 * Warn admin to exclude themselves if needed.
 */
function admin_lockout_check( event ) {
	var expiryVal = document.getElementById('ppm-expiry-value').value;	
	if ( expiryVal > 0 && event.target.checked ) {
		tb_show( '' , '#TB_inline?height=110&width=500&inlineId=mls_admin_lockout_notice_modal' );
	}
}

/**
 * Closes the thickbox or redirects users depending on what type of notice is
 * currently on display.
 *
 * @method mls_close_thickbox
 * @since  2.1.0
 * @param  {string} redirect a url to redirect users to on clicking ok.
 */
function mls_close_thickbox( redirect ) {
	if ( 'undefined' !== typeof redirect && redirect.length > 0 ) {
		window.location = redirect;
	} else {
		tb_remove();
	}
}

function getCodesList(justReturnCodes = false) {
	var availableCodes = {
		'Afghanistan': 'AF',
		'Åland Islands': 'AX',
		'Albania': 'AL',
		'Algeria': 'DZ',
		'American Samoa': 'AS',
		'Andorra': 'AD',
		'Angola': 'AO',
		'Anguilla': 'AI',
		'Antarctica': 'AQ',
		'Antigua and Barbuda': 'AG',
		'Argentina': 'AR',
		'Armenia': 'AM',
		'Aruba': 'AW',
		'Australia': 'AU',
		'Austria': 'AT',
		'Azerbaijan': 'AZ',
		'Bahamas': 'BS',
		'Bahrain': 'BH',
		'Bangladesh': 'BD',
		'Barbados': 'BB',
		'Belarus': 'BY',
		'Belgium': 'BE',
		'Belize': 'BZ',
		'Benin': 'BJ',
		'Bermuda': 'BM',
		'Bhutan': 'BT',
		'Bolivia, Plurinational State of': 'BO',
		'Bonaire, Sint Eustatius and Saba': 'BQ',
		'Bosnia and Herzegovina': 'BA',
		'Botswana': 'BW',
		'Bouvet Island': 'BV',
		'Brazil': 'BR',
		'British Indian Ocean Territory': 'IO',
		'Brunei Darussalam': 'BN',
		'Bulgaria': 'BG',
		'Burkina Faso': 'BF',
		'Burundi': 'BI',
		'Cambodia': 'KH',
		'Cameroon': 'CM',
		'Canada': 'CA',
		'Cape Verde': 'CV',
		'Cayman Islands': 'KY',
		'Central African Republic': 'CF',
		'Chad': 'TD',
		'Chile': 'CL',
		'China': 'CN',
		'Christmas Island': 'CX',
		'Cocos (Keeling) Islands': 'CC',
		'Colombia': 'CO',
		'Comoros': 'KM',
		'Congo': 'CG',
		'Congo, the Democratic Republic of the': 'CD',
		'Cook Islands': 'CK',
		'Costa Rica': 'CR',
		'Côte d Ivoire': 'CI',
		'Croatia': 'HR',
		'Cuba': 'CU',
		'Curaçao': 'CW',
		'Cyprus': 'CY',
		'Czech Republic': 'CZ',
		'Denmark': 'DK',
		'Djibouti': 'DJ',
		'Dominica': 'DM',
		'Dominican Republic': 'DO',
		'Ecuador': 'EC',
		'Egypt': 'EG',
		'El Salvador': 'SV',
		'Equatorial Guinea': 'GQ',
		'Eritrea': 'ER',
		'Estonia': 'EE',
		'Ethiopia': 'ET',
		'Falkland Islands (Malvinas)': 'FK',
		'Faroe Islands': 'FO',
		'Fiji': 'FJ',
		'Finland': 'FI',
		'France': 'FR',
		'French Guiana': 'GF',
		'French Polynesia': 'PF',
		'French Southern Territories': 'TF',
		'Gabon': 'GA',
		'Gambia': 'GM',
		'Georgia': 'GE',
		'Germany': 'DE',
		'Ghana': 'GH',
		'Gibraltar': 'GI',
		'Greece': 'GR',
		'Greenland': 'GL',
		'Grenada': 'GD',
		'Guadeloupe': 'GP',
		'Guam': 'GU',
		'Guatemala': 'GT',
		'Guernsey': 'GG',
		'Guinea': 'GN',
		'Guinea-Bissau': 'GW',
		'Guyana': 'GY',
		'Haiti': 'HT',
		'Heard Island and McDonald Islands': 'HM',
		'Holy See (Vatican City State)': 'VA',
		'Honduras': 'HN',
		'Hong Kong': 'HK',
		'Hungary': 'HU',
		'Iceland': 'IS',
		'India': 'IN',
		'Indonesia': 'ID',
		'Iran, Islamic Republic of': 'IR',
		'Iraq': 'IQ',
		'Ireland': 'IE',
		'Isle of Man': 'IM',
		'Israel': 'IL',
		'Italy': 'IT',
		'Jamaica': 'JM',
		'Japan': 'JP',
		'Jersey': 'JE',
		'Jordan': 'JO',
		'Kazakhstan': 'KZ',
		'Kenya': 'KE',
		'Kiribati': 'KI',
		'Korea, Democratic Peoples Republic of': 'KP',
		'Korea, Republic of': 'KR',
		'Kuwait': 'KW',
		'Kyrgyzstan': 'KG',
		'Lao Peoples Democratic Republic': 'LA',
		'Latvia': 'LV',
		'Lebanon': 'LB',
		'Lesotho': 'LS',
		'Liberia': 'LR',
		'Libya': 'LY',
		'Liechtenstein': 'LI',
		'Lithuania': 'LT',
		'Luxembourg': 'LU',
		'Macao': 'MO',
		'Macedonia, the Former Yugoslav Republic of': 'MK',
		'Madagascar': 'MG',
		'Malawi': 'MW',
		'Malaysia': 'MY',
		'Maldives': 'MV',
		'Mali': 'ML',
		'Malta': 'MT',
		'Marshall Islands': 'MH',
		'Martinique': 'MQ',
		'Mauritania': 'MR',
		'Mauritius': 'MU',
		'Mayotte': 'YT',
		'Mexico': 'MX',
		'Micronesia, Federated States of': 'FM',
		'Moldova, Republic of': 'MD',
		'Monaco': 'MC',
		'Mongolia': 'MN',
		'Montenegro': 'ME',
		'Montserrat': 'MS',
		'Morocco': 'MA',
		'Mozambique': 'MZ',
		'Myanmar': 'MM',
		'Namibia': 'NA',
		'Nauru': 'NR',
		'Nepal': 'NP',
		'Netherlands': 'NL',
		'New Caledonia': 'NC',
		'New Zealand': 'NZ',
		'Nicaragua': 'NI',
		'Niger': 'NE',
		'Nigeria': 'NG',
		'Niue': 'NU',
		'Norfolk Island': 'NF',
		'Northern Mariana Islands': 'MP',
		'Norway': 'NO',
		'Oman': 'OM',
		'Pakistan': 'PK',
		'Palau': 'PW',
		'Palestine, State of': 'PS',
		'Panama': 'PA',
		'Papua New Guinea': 'PG',
		'Paraguay': 'PY',
		'Peru': 'PE',
		'Philippines': 'PH',
		'Pitcairn': 'PN',
		'Poland': 'PL',
		'Portugal': 'PT',
		'Puerto Rico': 'PR',
		'Qatar': 'QA',
		'Réunion': 'RE',
		'Romania': 'RO',
		'Russian Federation': 'RU',
		'Rwanda': 'RW',
		'Saint Barthélemy': 'BL',
		'Saint Helena, Ascension and Tristan da Cunha': 'SH',
		'Saint Kitts and Nevis': 'KN',
		'Saint Lucia': 'LC',
		'Saint Martin (French part)': 'MF',
		'Saint Pierre and Miquelon': 'PM',
		'Saint Vincent and the Grenadines': 'VC',
		'Samoa': 'WS',
		'San Marino': 'SM',
		'Sao Tome and Principe': 'ST',
		'Saudi Arabia': 'SA',
		'Senegal': 'SN',
		'Serbia': 'RS',
		'Seychelles': 'SC',
		'Sierra Leone': 'SL',
		'Singapore': 'SG',
		'Sint Maarten (Dutch part)': 'SX',
		'Slovakia': 'SK',
		'Slovenia': 'SI',
		'Solomon Islands': 'SB',
		'Somalia': 'SO',
		'South Africa': 'ZA',
		'South Georgia and the South Sandwich Islands': 'GS',
		'South Sudan': 'SS',
		'Spain': 'ES',
		'Sri Lanka': 'LK',
		'Sudan': 'SD',
		'Suriname': 'SR',
		'Svalbard and Jan Mayen': 'SJ',
		'Swaziland': 'SZ',
		'Sweden': 'SE',
		'Switzerland': 'CH',
		'Syrian Arab Republic': 'SY',
		'Taiwan, Province of China': 'TW',
		'Tajikistan': 'TJ',
		'Tanzania, United Republic of': 'TZ',
		'Thailand': 'TH',
		'Timor-Leste': 'TL',
		'Togo': 'TG',
		'Tokelau': 'TK',
		'Tonga': 'TO',
		'Trinidad and Tobago': 'TT',
		'Tunisia': 'TN',
		'Turkey': 'TR',
		'Turkmenistan': 'TM',
		'Turks and Caicos Islands': 'TC',
		'Tuvalu': 'TV',
		'Uganda': 'UG',
		'Ukraine': 'UA',
		'United Arab Emirates': 'AE',
		'United Kingdom': 'GB',
		'United States': 'US',
		'United States Minor Outlying Islands': 'UM',
		'Uruguay': 'UY',
		'Uzbekistan': 'UZ',
		'Vanuatu': 'VU',
		'Venezuela, Bolivarian Republic of': 'VE',
		'Viet Nam': 'VN',
		'Virgin Islands, British': 'VG',
		'Virgin Islands, U.S.': 'VI',
		'Wallis and Futuna': 'WF',
		'Western Sahara': 'EH',
		'Yemen': 'YE',
		'Zambia': 'ZM',
		'Zimbabwe': 'ZW',
	};

	if (justReturnCodes) {
		var list = getCodesList();
		var justCodes = [];

		jQuery.each(list, function(key, value) {
			justCodes.push(value);
		});

		availableCodes = justCodes;
	}

	return availableCodes;
};

function buildDeniedCountries() {
	if ( jQuery('#login_geo_countries').val() ) {
		var text = jQuery('#login_geo_countries').val();
		var output = text.split(',');
		jQuery( '#login_geo_countries-countries-userfacing').html('<ul>' + jQuery.map(output, function(v) {
			return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-denied-country" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
		}).join('') + '</ul>');
	}
}

function buildIpList() {
	if ( jQuery('#restrict_login_allowed_ips').val() ) {
		var text = jQuery('#restrict_login_allowed_ips').val();
		var output = text.split(',');
		jQuery( '#restrict_login_allowed_ips-userfacing').html('<ul>' + jQuery.map(output, function(v) {
			return '<li class="c4wp-buttony-list">' + v + ' <span id="remove-restricted-ip" class="dashicons dashicons-no-alt" data-value="' + v + '"></span></li>';
		}).join('') + '</ul>');
	}
}

function ValidateIPaddress(ipaddress) {  
	if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ipaddress)) {  
	  return (true)  
	}  
	return (false)  
  } 

/**
 * Temprary Logins admin.
 */
jQuery( 'document' ).ready( function( $ ) {

	jQuery( '#mls-datepicker' ).datepicker({
		dateFormat : 'dd/mm/yy'
	});

	jQuery( '#mls-timepicker' ).timepicker({
		timeFormat : 'HH:mm'
	});

	jQuery( document ).on( 'click', '.mls-create-login-link', function( event ) {
		event.preventDefault();
		jQuery( '#new-temp-login-form' ).slideDown( 300 );
	} );

	jQuery( document ).on( 'click', '#cancel-mls-create-login', function( event ) {
		event.preventDefault();
		jQuery( '#new-temp-login-form' ).slideUp( 300 );
	} );

	jQuery( document ).on( 'click', '[data-mls-copy-link]', function( event ) {
		event.preventDefault();		
		var ourElem     = jQuery( this );
		var text         = jQuery( this ).attr( 'data-mls-copy-link' );		
		var tempTextarea = jQuery('<textarea>');
        jQuery('body').append(tempTextarea);
        tempTextarea.val(text).select();	
		document.execCommand('copy');
        tempTextarea.remove();

		jQuery( ourElem ).after('<div id="copied" class="mls-inline-notice notice notice-success"><p class="description">Link copied to clipboard</p></div>');
		setTimeout(function () {
			jQuery( '#copied' ).slideUp(500).delay( 500 ).remove();
		}, 2000); 

		if ( jQuery( ourElem ).hasClass('close-form') ) {
			jQuery( '#create-result' ).slideUp(500).delay( 500 ).remove();
			jQuery( '#new-temp-login-form' ).delay( 500 ).slideUp(500);	
		}
	} );

	jQuery( document ).on( 'click', '[data-mls-email-temp-link]', function( event ) {
		event.preventDefault();
		var ourElem     = jQuery( this );
		var email = jQuery( this ).attr( 'data-mls-email-temp-link' );
		var user_id = jQuery( this ).attr( 'data-user-id' );
		var link = jQuery( this ).parent().find( '[data-mls-copy-link]' ).attr( 'data-mls-copy-link' );
		var nonce = jQuery( this ).attr( 'data-nonce' );		

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'mls_send_login_link',
				nonce : nonce,
				email : email,
				user_id : user_id,
				link : link,
			},
			success: function ( result ) {
				if ( result.success ) {			
					jQuery( ourElem ).after('<div id="send-result" class="mls-inline-notice notice notice-success"><p class="description">'+ result.data.message +'</p></div>');
					setTimeout(function () {
						jQuery( '#send-result' ).slideUp(500).delay( 500 ).remove();
						jQuery( '#new-temp-login-form' ).delay( 500 ).slideUp(500);		
					}, 2000); 
				} else {
					jQuery( ourElem ).after('<div id="send-result" class="mls-inline-notice notice notice-error"><p class="description">'+ result.data.message +'</p></div>');
					setTimeout(function () {
						jQuery( '#send-result' ).slideUp(500).delay( 500 ).remove();
					}, 2000); 
				}
			}
		});
	} );	
	
	jQuery( document ).on( 'click', '#mls-create-login-submit', function( event ) {
		event.preventDefault();
		var formData = jQuery( '#new-temp-login-form' ).serializeArray();
		var nonce = jQuery( this ).attr( 'data-nonce' );	
		
		if ( ! mlsIsEmail( jQuery( '#user_email' ).val() ) ) {
			jQuery( '#user_email' ).addClass( 'errored' );
			setTimeout(function () {
				jQuery( '#user_email' ).removeClass( 'errored' );
			}, 2000); 
			return;
		}

		if ( jQuery('input[name="login_expire"]:checked').val() == 'custom_expiry' && '' == jQuery( '#mls-datepicker' ).val() ) {
			jQuery( '#mls-datepicker' ).addClass( 'errored' );
			setTimeout(function () {
				jQuery( '#mls-datepicker' ).removeClass( 'errored' );
			}, 2000); 
			return true;
		}

		if ( jQuery('input[name="login_expire"]:checked').val() == 'custom_expiry' && '' == jQuery( '#mls-timepicker' ).val() ) {
			jQuery( '#mls-timepicker' ).addClass( 'errored' );
			setTimeout(function () {
				jQuery( '#mls-timepicker' ).removeClass( 'errored' );
			}, 2000); 
			return true;;
		}

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			async: true,
			data: {
				action: 'mls_create_login_link',
				nonce : nonce,
				form_data : formData,
			},
			success: function ( result ) {
				if ( result.success ) {		
					jQuery( '#new-temp-login-form' )[0].reset();	
					jQuery( '#melapress_temp_logins' ).load( location.href+' #melapress_temp_logins >*', '' );	
					var link_markup = '';
					if ( result.data.link ) {
						var link_markup = '<p class="description"><input id="new-link" type="text" value="'+ result.data.link +'" readonly></input><br><a href="#" class="button button-primary close-form" data-mls-copy-link="'+ result.data.link +'">Copy link and close</a><a href="#close" class="button button-secondary">Close & Continue</a></p>';
					}
				
						jQuery( '#mls-create-login-result' ).after('<div id="create-result" class="notice notice-success"><p class="description">'+ result.data.message +'</p>'+ link_markup +'</div>');
						// Close the new temp login form on successful creation
						jQuery( '#new-temp-login-form' ).slideUp(300);
						if ( link_markup == '' ) {
							setTimeout(function () {
								jQuery( '#create-result' ).slideUp(500).delay( 500 ).remove();
							}, 2000); 
						}

				} else {
					jQuery( '#mls-create-login-result' ).after('<div id="create-result" class="notice notice-error"><p class="description">'+ result.data.message +'</p></div>');
					setTimeout(function () {
						jQuery( '#create-result' ).slideUp(500).delay( 500 ).remove();
					}, 2000); 
				}
			}
		});
	} );

	jQuery( document ).on( 'click', 'a[href="#close"]', function( event ) {
		event.preventDefault();
		jQuery( '#create-result' ).slideUp(500).delay( 500 ).remove();
		jQuery( '#new-temp-login-form' ).delay( 500 ).slideUp(500);		
	} );
	
});

function mlsIsEmail(email) {
	var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test(email);
  }