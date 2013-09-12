/*
Combination of js files in crop
Equals to

		<script type="text/javascript" src="<?php echo VMFEU_PLUGIN_URL."/includes/crop/"; ?>uploadify/swfobject.js"></script>
    	<script type="text/javascript" src="<?php echo VMFEU_PLUGIN_URL."/includes/crop/"; ?>uploadify/jquery.uploadify.v2.1.0.min.js"></script>
  		<script type="text/javascript" src="<?php echo VMFEU_PLUGIN_URL."/includes/crop/"; ?>jcrop/jquery.Jcrop.min.js"></script>


*/


/*	SWFObject v2.2 <http://code.google.com/p/swfobject/> 
	is released under the MIT License <http://www.opensource.org/licenses/mit-license.php> 
*/
var swfobject=function(){var D="undefined",r="object",S="Shockwave Flash",W="ShockwaveFlash.ShockwaveFlash",q="application/x-shockwave-flash",R="SWFObjectExprInst",x="onreadystatechange",O=window,j=document,t=navigator,T=false,U=[h],o=[],N=[],I=[],l,Q,E,B,J=false,a=false,n,G,m=true,M=function(){var aa=typeof j.getElementById!=D&&typeof j.getElementsByTagName!=D&&typeof j.createElement!=D,ah=t.userAgent.toLowerCase(),Y=t.platform.toLowerCase(),ae=Y?/win/.test(Y):/win/.test(ah),ac=Y?/mac/.test(Y):/mac/.test(ah),af=/webkit/.test(ah)?parseFloat(ah.replace(/^.*webkit\/(\d+(\.\d+)?).*$/,"$1")):false,X=!+"\v1",ag=[0,0,0],ab=null;if(typeof t.plugins!=D&&typeof t.plugins[S]==r){ab=t.plugins[S].description;if(ab&&!(typeof t.mimeTypes!=D&&t.mimeTypes[q]&&!t.mimeTypes[q].enabledPlugin)){T=true;X=false;ab=ab.replace(/^.*\s+(\S+\s+\S+$)/,"$1");ag[0]=parseInt(ab.replace(/^(.*)\..*$/,"$1"),10);ag[1]=parseInt(ab.replace(/^.*\.(.*)\s.*$/,"$1"),10);ag[2]=/[a-zA-Z]/.test(ab)?parseInt(ab.replace(/^.*[a-zA-Z]+(.*)$/,"$1"),10):0}}else{if(typeof O.ActiveXObject!=D){try{var ad=new ActiveXObject(W);if(ad){ab=ad.GetVariable("$version");if(ab){X=true;ab=ab.split(" ")[1].split(",");ag=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}}catch(Z){}}}return{w3:aa,pv:ag,wk:af,ie:X,win:ae,mac:ac}}(),k=function(){if(!M.w3){return}if((typeof j.readyState!=D&&j.readyState=="complete")||(typeof j.readyState==D&&(j.getElementsByTagName("body")[0]||j.body))){f()}if(!J){if(typeof j.addEventListener!=D){j.addEventListener("DOMContentLoaded",f,false)}if(M.ie&&M.win){j.attachEvent(x,function(){if(j.readyState=="complete"){j.detachEvent(x,arguments.callee);f()}});if(O==top){(function(){if(J){return}try{j.documentElement.doScroll("left")}catch(X){setTimeout(arguments.callee,0);return}f()})()}}if(M.wk){(function(){if(J){return}if(!/loaded|complete/.test(j.readyState)){setTimeout(arguments.callee,0);return}f()})()}s(f)}}();function f(){if(J){return}try{var Z=j.getElementsByTagName("body")[0].appendChild(C("span"));Z.parentNode.removeChild(Z)}catch(aa){return}J=true;var X=U.length;for(var Y=0;Y<X;Y++){U[Y]()}}function K(X){if(J){X()}else{U[U.length]=X}}function s(Y){if(typeof O.addEventListener!=D){O.addEventListener("load",Y,false)}else{if(typeof j.addEventListener!=D){j.addEventListener("load",Y,false)}else{if(typeof O.attachEvent!=D){i(O,"onload",Y)}else{if(typeof O.onload=="function"){var X=O.onload;O.onload=function(){X();Y()}}else{O.onload=Y}}}}}function h(){if(T){V()}else{H()}}function V(){var X=j.getElementsByTagName("body")[0];var aa=C(r);aa.setAttribute("type",q);var Z=X.appendChild(aa);if(Z){var Y=0;(function(){if(typeof Z.GetVariable!=D){var ab=Z.GetVariable("$version");if(ab){ab=ab.split(" ")[1].split(",");M.pv=[parseInt(ab[0],10),parseInt(ab[1],10),parseInt(ab[2],10)]}}else{if(Y<10){Y++;setTimeout(arguments.callee,10);return}}X.removeChild(aa);Z=null;H()})()}else{H()}}function H(){var ag=o.length;if(ag>0){for(var af=0;af<ag;af++){var Y=o[af].id;var ab=o[af].callbackFn;var aa={success:false,id:Y};if(M.pv[0]>0){var ae=c(Y);if(ae){if(F(o[af].swfVersion)&&!(M.wk&&M.wk<312)){w(Y,true);if(ab){aa.success=true;aa.ref=z(Y);ab(aa)}}else{if(o[af].expressInstall&&A()){var ai={};ai.data=o[af].expressInstall;ai.width=ae.getAttribute("width")||"0";ai.height=ae.getAttribute("height")||"0";if(ae.getAttribute("class")){ai.styleclass=ae.getAttribute("class")}if(ae.getAttribute("align")){ai.align=ae.getAttribute("align")}var ah={};var X=ae.getElementsByTagName("param");var ac=X.length;for(var ad=0;ad<ac;ad++){if(X[ad].getAttribute("name").toLowerCase()!="movie"){ah[X[ad].getAttribute("name")]=X[ad].getAttribute("value")}}P(ai,ah,Y,ab)}else{p(ae);if(ab){ab(aa)}}}}}else{w(Y,true);if(ab){var Z=z(Y);if(Z&&typeof Z.SetVariable!=D){aa.success=true;aa.ref=Z}ab(aa)}}}}}function z(aa){var X=null;var Y=c(aa);if(Y&&Y.nodeName=="OBJECT"){if(typeof Y.SetVariable!=D){X=Y}else{var Z=Y.getElementsByTagName(r)[0];if(Z){X=Z}}}return X}function A(){return !a&&F("6.0.65")&&(M.win||M.mac)&&!(M.wk&&M.wk<312)}function P(aa,ab,X,Z){a=true;E=Z||null;B={success:false,id:X};var ae=c(X);if(ae){if(ae.nodeName=="OBJECT"){l=g(ae);Q=null}else{l=ae;Q=X}aa.id=R;if(typeof aa.width==D||(!/%$/.test(aa.width)&&parseInt(aa.width,10)<310)){aa.width="310"}if(typeof aa.height==D||(!/%$/.test(aa.height)&&parseInt(aa.height,10)<137)){aa.height="137"}j.title=j.title.slice(0,47)+" - Flash Player Installation";var ad=M.ie&&M.win?"ActiveX":"PlugIn",ac="MMredirectURL="+O.location.toString().replace(/&/g,"%26")+"&MMplayerType="+ad+"&MMdoctitle="+j.title;if(typeof ab.flashvars!=D){ab.flashvars+="&"+ac}else{ab.flashvars=ac}if(M.ie&&M.win&&ae.readyState!=4){var Y=C("div");X+="SWFObjectNew";Y.setAttribute("id",X);ae.parentNode.insertBefore(Y,ae);ae.style.display="none";(function(){if(ae.readyState==4){ae.parentNode.removeChild(ae)}else{setTimeout(arguments.callee,10)}})()}u(aa,ab,X)}}function p(Y){if(M.ie&&M.win&&Y.readyState!=4){var X=C("div");Y.parentNode.insertBefore(X,Y);X.parentNode.replaceChild(g(Y),X);Y.style.display="none";(function(){if(Y.readyState==4){Y.parentNode.removeChild(Y)}else{setTimeout(arguments.callee,10)}})()}else{Y.parentNode.replaceChild(g(Y),Y)}}function g(ab){var aa=C("div");if(M.win&&M.ie){aa.innerHTML=ab.innerHTML}else{var Y=ab.getElementsByTagName(r)[0];if(Y){var ad=Y.childNodes;if(ad){var X=ad.length;for(var Z=0;Z<X;Z++){if(!(ad[Z].nodeType==1&&ad[Z].nodeName=="PARAM")&&!(ad[Z].nodeType==8)){aa.appendChild(ad[Z].cloneNode(true))}}}}}return aa}function u(ai,ag,Y){var X,aa=c(Y);if(M.wk&&M.wk<312){return X}if(aa){if(typeof ai.id==D){ai.id=Y}if(M.ie&&M.win){var ah="";for(var ae in ai){if(ai[ae]!=Object.prototype[ae]){if(ae.toLowerCase()=="data"){ag.movie=ai[ae]}else{if(ae.toLowerCase()=="styleclass"){ah+=' class="'+ai[ae]+'"'}else{if(ae.toLowerCase()!="classid"){ah+=" "+ae+'="'+ai[ae]+'"'}}}}}var af="";for(var ad in ag){if(ag[ad]!=Object.prototype[ad]){af+='<param name="'+ad+'" value="'+ag[ad]+'" />'}}aa.outerHTML='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'+ah+">"+af+"</object>";N[N.length]=ai.id;X=c(ai.id)}else{var Z=C(r);Z.setAttribute("type",q);for(var ac in ai){if(ai[ac]!=Object.prototype[ac]){if(ac.toLowerCase()=="styleclass"){Z.setAttribute("class",ai[ac])}else{if(ac.toLowerCase()!="classid"){Z.setAttribute(ac,ai[ac])}}}}for(var ab in ag){if(ag[ab]!=Object.prototype[ab]&&ab.toLowerCase()!="movie"){e(Z,ab,ag[ab])}}aa.parentNode.replaceChild(Z,aa);X=Z}}return X}function e(Z,X,Y){var aa=C("param");aa.setAttribute("name",X);aa.setAttribute("value",Y);Z.appendChild(aa)}function y(Y){var X=c(Y);if(X&&X.nodeName=="OBJECT"){if(M.ie&&M.win){X.style.display="none";(function(){if(X.readyState==4){b(Y)}else{setTimeout(arguments.callee,10)}})()}else{X.parentNode.removeChild(X)}}}function b(Z){var Y=c(Z);if(Y){for(var X in Y){if(typeof Y[X]=="function"){Y[X]=null}}Y.parentNode.removeChild(Y)}}function c(Z){var X=null;try{X=j.getElementById(Z)}catch(Y){}return X}function C(X){return j.createElement(X)}function i(Z,X,Y){Z.attachEvent(X,Y);I[I.length]=[Z,X,Y]}function F(Z){var Y=M.pv,X=Z.split(".");X[0]=parseInt(X[0],10);X[1]=parseInt(X[1],10)||0;X[2]=parseInt(X[2],10)||0;return(Y[0]>X[0]||(Y[0]==X[0]&&Y[1]>X[1])||(Y[0]==X[0]&&Y[1]==X[1]&&Y[2]>=X[2]))?true:false}function v(ac,Y,ad,ab){if(M.ie&&M.mac){return}var aa=j.getElementsByTagName("head")[0];if(!aa){return}var X=(ad&&typeof ad=="string")?ad:"screen";if(ab){n=null;G=null}if(!n||G!=X){var Z=C("style");Z.setAttribute("type","text/css");Z.setAttribute("media",X);n=aa.appendChild(Z);if(M.ie&&M.win&&typeof j.styleSheets!=D&&j.styleSheets.length>0){n=j.styleSheets[j.styleSheets.length-1]}G=X}if(M.ie&&M.win){if(n&&typeof n.addRule==r){n.addRule(ac,Y)}}else{if(n&&typeof j.createTextNode!=D){n.appendChild(j.createTextNode(ac+" {"+Y+"}"))}}}function w(Z,X){if(!m){return}var Y=X?"visible":"hidden";if(J&&c(Z)){c(Z).style.visibility=Y}else{v("#"+Z,"visibility:"+Y)}}function L(Y){var Z=/[\\\"<>\.;]/;var X=Z.exec(Y)!=null;return X&&typeof encodeURIComponent!=D?encodeURIComponent(Y):Y}var d=function(){if(M.ie&&M.win){window.attachEvent("onunload",function(){var ac=I.length;for(var ab=0;ab<ac;ab++){I[ab][0].detachEvent(I[ab][1],I[ab][2])}var Z=N.length;for(var aa=0;aa<Z;aa++){y(N[aa])}for(var Y in M){M[Y]=null}M=null;for(var X in swfobject){swfobject[X]=null}swfobject=null})}}();return{registerObject:function(ab,X,aa,Z){if(M.w3&&ab&&X){var Y={};Y.id=ab;Y.swfVersion=X;Y.expressInstall=aa;Y.callbackFn=Z;o[o.length]=Y;w(ab,false)}else{if(Z){Z({success:false,id:ab})}}},getObjectById:function(X){if(M.w3){return z(X)}},embedSWF:function(ab,ah,ae,ag,Y,aa,Z,ad,af,ac){var X={success:false,id:ah};if(M.w3&&!(M.wk&&M.wk<312)&&ab&&ah&&ae&&ag&&Y){w(ah,false);K(function(){ae+="";ag+="";var aj={};if(af&&typeof af===r){for(var al in af){aj[al]=af[al]}}aj.data=ab;aj.width=ae;aj.height=ag;var am={};if(ad&&typeof ad===r){for(var ak in ad){am[ak]=ad[ak]}}if(Z&&typeof Z===r){for(var ai in Z){if(typeof am.flashvars!=D){am.flashvars+="&"+ai+"="+Z[ai]}else{am.flashvars=ai+"="+Z[ai]}}}if(F(Y)){var an=u(aj,am,ah);if(aj.id==ah){w(ah,true)}X.success=true;X.ref=an}else{if(aa&&A()){aj.data=aa;P(aj,am,ah,ac);return}else{w(ah,true)}}if(ac){ac(X)}})}else{if(ac){ac(X)}}},switchOffAutoHideShow:function(){m=false},ua:M,getFlashPlayerVersion:function(){return{major:M.pv[0],minor:M.pv[1],release:M.pv[2]}},hasFlashPlayerVersion:F,createSWF:function(Z,Y,X){if(M.w3){return u(Z,Y,X)}else{return undefined}},showExpressInstall:function(Z,aa,X,Y){if(M.w3&&A()){P(Z,aa,X,Y)}},removeSWF:function(X){if(M.w3){y(X)}},createCSS:function(aa,Z,Y,X){if(M.w3){v(aa,Z,Y,X)}},addDomLoadEvent:K,addLoadEvent:s,getQueryParamValue:function(aa){var Z=j.location.search||j.location.hash;if(Z){if(/\?/.test(Z)){Z=Z.split("?")[1]}if(aa==null){return L(Z)}var Y=Z.split("&");for(var X=0;X<Y.length;X++){if(Y[X].substring(0,Y[X].indexOf("="))==aa){return L(Y[X].substring((Y[X].indexOf("=")+1)))}}}return""},expressInstallCallback:function(){if(a){var X=c(R);if(X&&l){X.parentNode.replaceChild(l,X);if(Q){w(Q,true);if(M.ie&&M.win){l.style.display="block"}}if(E){E(B)}}a=false}}}}();

/*
Uploadify v2.1.4
Release Date: November 8, 2010

Copyright (c) 2010 Ronnie Garcia, Travis Nickels

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

if(jQuery){(function(a){a.extend(a.fn,{uploadify:function(b){a(this).each(function(){var f=a.extend({id:a(this).attr("id"),uploader:"uploadify.swf",script:"uploadify.php",expressInstall:null,folder:"",height:30,width:120,cancelImg:"cancel.png",wmode:"opaque",scriptAccess:"sameDomain",fileDataName:"Filedata",method:"POST",queueSizeLimit:999,simUploadLimit:1,queueID:false,displayData:"percentage",removeCompleted:true,onInit:function(){},onSelect:function(){},onSelectOnce:function(){},onQueueFull:function(){},onCheck:function(){},onCancel:function(){},onClearQueue:function(){},onError:function(){},onProgress:function(){},onComplete:function(){},onAllComplete:function(){}},b);a(this).data("settings",f);var e=location.pathname;e=e.split("/");e.pop();e=e.join("/")+"/";var g={};g.uploadifyID=f.id;g.pagepath=e;if(f.buttonImg){g.buttonImg=escape(f.buttonImg)}if(f.buttonText){g.buttonText=escape(f.buttonText)}if(f.rollover){g.rollover=true}g.script=f.script;g.folder=escape(f.folder);if(f.scriptData){var h="";for(var d in f.scriptData){h+="&"+d+"="+f.scriptData[d]}g.scriptData=escape(h.substr(1))}g.width=f.width;g.height=f.height;g.wmode=f.wmode;g.method=f.method;g.queueSizeLimit=f.queueSizeLimit;g.simUploadLimit=f.simUploadLimit;if(f.hideButton){g.hideButton=true}if(f.fileDesc){g.fileDesc=f.fileDesc}if(f.fileExt){g.fileExt=f.fileExt}if(f.multi){g.multi=true}if(f.auto){g.auto=true}if(f.sizeLimit){g.sizeLimit=f.sizeLimit}if(f.checkScript){g.checkScript=f.checkScript}if(f.fileDataName){g.fileDataName=f.fileDataName}if(f.queueID){g.queueID=f.queueID}if(f.onInit()!==false){a(this).css("display","none");a(this).after('<div id="'+a(this).attr("id")+'Uploader"></div>');swfobject.embedSWF(f.uploader,f.id+"Uploader",f.width,f.height,"9.0.24",f.expressInstall,g,{quality:"high",wmode:f.wmode,allowScriptAccess:f.scriptAccess},{},function(i){if(typeof(f.onSWFReady)=="function"&&i.success){f.onSWFReady()}});if(f.queueID==false){a("#"+a(this).attr("id")+"Uploader").after('<div id="'+a(this).attr("id")+'Queue" class="uploadifyQueue"></div>')}else{a("#"+f.queueID).addClass("uploadifyQueue")}}if(typeof(f.onOpen)=="function"){a(this).bind("uploadifyOpen",f.onOpen)}a(this).bind("uploadifySelect",{action:f.onSelect,queueID:f.queueID},function(k,i,j){if(k.data.action(k,i,j)!==false){var l=Math.round(j.size/1024*100)*0.01;var m="KB";if(l>1000){l=Math.round(l*0.001*100)*0.01;m="MB"}var n=l.toString().split(".");if(n.length>1){l=n[0]+"."+n[1].substr(0,2)}else{l=n[0]}if(j.name.length>20){fileName=j.name.substr(0,20)+"..."}else{fileName=j.name}queue="#"+a(this).attr("id")+"Queue";if(k.data.queueID){queue="#"+k.data.queueID}a(queue).append('<div id="'+a(this).attr("id")+i+'" class="uploadifyQueueItem"><div class="cancel"><a href="javascript:jQuery(\'#'+a(this).attr("id")+"').uploadifyCancel('"+i+'\')"><img src="'+f.cancelImg+'" border="0" /></a></div><span class="fileName">'+fileName+" ("+l+m+')</span><span class="percentage"></span><div class="uploadifyProgress"><div id="'+a(this).attr("id")+i+'ProgressBar" class="uploadifyProgressBar"><!--Progress Bar--></div></div></div>')}});a(this).bind("uploadifySelectOnce",{action:f.onSelectOnce},function(i,j){i.data.action(i,j);if(f.auto){if(f.checkScript){a(this).uploadifyUpload(null,false)}else{a(this).uploadifyUpload(null,true)}}});a(this).bind("uploadifyQueueFull",{action:f.onQueueFull},function(i,j){if(i.data.action(i,j)!==false){alert("The queue is full.  The max size is "+j+".")}});a(this).bind("uploadifyCheckExist",{action:f.onCheck},function(n,m,l,k,p){var j=new Object();j=l;j.folder=(k.substr(0,1)=="/")?k:e+k;if(p){for(var i in l){var o=i}}a.post(m,j,function(s){for(var q in s){if(n.data.action(n,s,q)!==false){var r=confirm("Do you want to replace the file "+s[q]+"?");if(!r){document.getElementById(a(n.target).attr("id")+"Uploader").cancelFileUpload(q,true,true)}}}if(p){document.getElementById(a(n.target).attr("id")+"Uploader").startFileUpload(o,true)}else{document.getElementById(a(n.target).attr("id")+"Uploader").startFileUpload(null,true)}},"json")});a(this).bind("uploadifyCancel",{action:f.onCancel},function(n,j,m,o,i,l){if(n.data.action(n,j,m,o,l)!==false){if(i){var k=(l==true)?0:250;a("#"+a(this).attr("id")+j).fadeOut(k,function(){a(this).remove()})}}});a(this).bind("uploadifyClearQueue",{action:f.onClearQueue},function(k,j){var i=(f.queueID)?f.queueID:a(this).attr("id")+"Queue";if(j){a("#"+i).find(".uploadifyQueueItem").remove()}if(k.data.action(k,j)!==false){a("#"+i).find(".uploadifyQueueItem").each(function(){var l=a(".uploadifyQueueItem").index(this);a(this).delay(l*100).fadeOut(250,function(){a(this).remove()})})}});var c=[];a(this).bind("uploadifyError",{action:f.onError},function(m,i,l,k){if(m.data.action(m,i,l,k)!==false){var j=new Array(i,l,k);c.push(j);a("#"+a(this).attr("id")+i).find(".percentage").text(" - "+k.type+" Error");a("#"+a(this).attr("id")+i).find(".uploadifyProgress").hide();a("#"+a(this).attr("id")+i).addClass("uploadifyError")}});if(typeof(f.onUpload)=="function"){a(this).bind("uploadifyUpload",f.onUpload)}a(this).bind("uploadifyProgress",{action:f.onProgress,toDisplay:f.displayData},function(k,i,j,l){if(k.data.action(k,i,j,l)!==false){a("#"+a(this).attr("id")+i+"ProgressBar").animate({width:l.percentage+"%"},250,function(){if(l.percentage==100){a(this).closest(".uploadifyProgress").fadeOut(250,function(){a(this).remove()})}});if(k.data.toDisplay=="percentage"){displayData=" - "+l.percentage+"%"}if(k.data.toDisplay=="speed"){displayData=" - "+l.speed+"KB/s"}if(k.data.toDisplay==null){displayData=" "}a("#"+a(this).attr("id")+i).find(".percentage").text(displayData)}});a(this).bind("uploadifyComplete",{action:f.onComplete},function(l,i,k,j,m){if(l.data.action(l,i,k,unescape(j),m)!==false){a("#"+a(this).attr("id")+i).find(".percentage").text(" - Completed");if(f.removeCompleted){a("#"+a(l.target).attr("id")+i).fadeOut(250,function(){a(this).remove()})}a("#"+a(l.target).attr("id")+i).addClass("completed")}});if(typeof(f.onAllComplete)=="function"){a(this).bind("uploadifyAllComplete",{action:f.onAllComplete},function(i,j){if(i.data.action(i,j)!==false){c=[]}})}})},uploadifySettings:function(f,j,c){var g=false;a(this).each(function(){if(f=="scriptData"&&j!=null){if(c){var i=j}else{var i=a.extend(a(this).data("settings").scriptData,j)}var l="";for(var k in i){l+="&"+k+"="+i[k]}j=escape(l.substr(1))}g=document.getElementById(a(this).attr("id")+"Uploader").updateSettings(f,j)});if(j==null){if(f=="scriptData"){var b=unescape(g).split("&");var e=new Object();for(var d=0;d<b.length;d++){var h=b[d].split("=");e[h[0]]=h[1]}g=e}}return g},uploadifyUpload:function(b,c){a(this).each(function(){if(!c){c=false}document.getElementById(a(this).attr("id")+"Uploader").startFileUpload(b,c)})},uploadifyCancel:function(b){a(this).each(function(){document.getElementById(a(this).attr("id")+"Uploader").cancelFileUpload(b,true,true,false)})},uploadifyClearQueue:function(){a(this).each(function(){document.getElementById(a(this).attr("id")+"Uploader").clearFileUploadQueue(false)})}})})(jQuery)};

/**
 * Jcrop v.0.9.8 (minimized)
 * (c) 2008 Kelly Hallman and DeepLiquid.com
 * More information: http://deepliquid.com/content/Jcrop.html
 * Released under MIT License - this header must remain with code
 */
(function($){$.Jcrop=function(obj,opt)
{var obj=obj,opt=opt;if(typeof(obj)!=='object')obj=$(obj)[0];if(typeof(opt)!=='object')opt={};if(!('trackDocument'in opt))
{opt.trackDocument=$.browser.msie?false:true;if($.browser.msie&&$.browser.version.split('.')[0]=='8')
opt.trackDocument=true;}
if(!('keySupport'in opt))
opt.keySupport=$.browser.msie?false:true;var defaults={trackDocument:false,baseClass:'jcrop',addClass:null,bgColor:'black',bgOpacity:.6,borderOpacity:.4,handleOpacity:.5,handlePad:5,handleSize:9,handleOffset:5,edgeMargin:14,aspectRatio:0,keySupport:true,cornerHandles:true,sideHandles:true,drawBorders:true,dragEdges:true,boxWidth:0,boxHeight:0,boundary:8,animationDelay:20,swingSpeed:3,allowSelect:true,allowMove:true,allowResize:true,minSelect:[0,0],maxSize:[0,0],minSize:[0,0],onChange:function(){},onSelect:function(){}};var options=defaults;setOptions(opt);var $origimg=$(obj);var $img=$origimg.clone().removeAttr('id').css({position:'absolute'});$img.width($origimg.width());$img.height($origimg.height());$origimg.after($img).hide();presize($img,options.boxWidth,options.boxHeight);var boundx=$img.width(),boundy=$img.height(),$div=$('<div />').width(boundx).height(boundy).addClass(cssClass('holder')).css({position:'relative',backgroundColor:options.bgColor}).insertAfter($origimg).append($img);;if(options.addClass)$div.addClass(options.addClass);var $img2=$('<img />').attr('src',$img.attr('src')).css('position','absolute').width(boundx).height(boundy);var $img_holder=$('<div />').width(pct(100)).height(pct(100)).css({zIndex:310,position:'absolute',overflow:'hidden'}).append($img2);var $hdl_holder=$('<div />').width(pct(100)).height(pct(100)).css('zIndex',320);var $sel=$('<div />').css({position:'absolute',zIndex:300}).insertBefore($img).append($img_holder,$hdl_holder);var bound=options.boundary;var $trk=newTracker().width(boundx+(bound*2)).height(boundy+(bound*2)).css({position:'absolute',top:px(-bound),left:px(-bound),zIndex:290}).mousedown(newSelection);var xlimit,ylimit,xmin,ymin;var xscale,yscale,enabled=true;var docOffset=getPos($img),btndown,lastcurs,dimmed,animating,shift_down;var Coords=function()
{var x1=0,y1=0,x2=0,y2=0,ox,oy;function setPressed(pos)
{var pos=rebound(pos);x2=x1=pos[0];y2=y1=pos[1];};function setCurrent(pos)
{var pos=rebound(pos);ox=pos[0]-x2;oy=pos[1]-y2;x2=pos[0];y2=pos[1];};function getOffset()
{return[ox,oy];};function moveOffset(offset)
{var ox=offset[0],oy=offset[1];if(0>x1+ox)ox-=ox+x1;if(0>y1+oy)oy-=oy+y1;if(boundy<y2+oy)oy+=boundy-(y2+oy);if(boundx<x2+ox)ox+=boundx-(x2+ox);x1+=ox;x2+=ox;y1+=oy;y2+=oy;};function getCorner(ord)
{var c=getFixed();switch(ord)
{case'ne':return[c.x2,c.y];case'nw':return[c.x,c.y];case'se':return[c.x2,c.y2];case'sw':return[c.x,c.y2];}};function getFixed()
{if(!options.aspectRatio)return getRect();var aspect=options.aspectRatio,min_x=options.minSize[0]/xscale,min_y=options.minSize[1]/yscale,max_x=options.maxSize[0]/xscale,max_y=options.maxSize[1]/yscale,rw=x2-x1,rh=y2-y1,rwa=Math.abs(rw),rha=Math.abs(rh),real_ratio=rwa/rha,xx,yy;if(max_x==0){max_x=boundx*10}
if(max_y==0){max_y=boundy*10}
if(real_ratio<aspect)
{yy=y2;w=rha*aspect;xx=rw<0?x1-w:w+x1;if(xx<0)
{xx=0;h=Math.abs((xx-x1)/aspect);yy=rh<0?y1-h:h+y1;}
else if(xx>boundx)
{xx=boundx;h=Math.abs((xx-x1)/aspect);yy=rh<0?y1-h:h+y1;}}
else
{xx=x2;h=rwa/aspect;yy=rh<0?y1-h:y1+h;if(yy<0)
{yy=0;w=Math.abs((yy-y1)*aspect);xx=rw<0?x1-w:w+x1;}
else if(yy>boundy)
{yy=boundy;w=Math.abs(yy-y1)*aspect;xx=rw<0?x1-w:w+x1;}}
if(xx>x1){if(xx-x1<min_x){xx=x1+min_x;}else if(xx-x1>max_x){xx=x1+max_x;}
if(yy>y1){yy=y1+(xx-x1)/aspect;}else{yy=y1-(xx-x1)/aspect;}}else if(xx<x1){if(x1-xx<min_x){xx=x1-min_x}else if(x1-xx>max_x){xx=x1-max_x;}
if(yy>y1){yy=y1+(x1-xx)/aspect;}else{yy=y1-(x1-xx)/aspect;}}
if(xx<0){x1-=xx;xx=0;}else if(xx>boundx){x1-=xx-boundx;xx=boundx;}
if(yy<0){y1-=yy;yy=0;}else if(yy>boundy){y1-=yy-boundy;yy=boundy;}
return last=makeObj(flipCoords(x1,y1,xx,yy));};function rebound(p)
{if(p[0]<0)p[0]=0;if(p[1]<0)p[1]=0;if(p[0]>boundx)p[0]=boundx;if(p[1]>boundy)p[1]=boundy;return[p[0],p[1]];};function flipCoords(x1,y1,x2,y2)
{var xa=x1,xb=x2,ya=y1,yb=y2;if(x2<x1)
{xa=x2;xb=x1;}
if(y2<y1)
{ya=y2;yb=y1;}
return[Math.round(xa),Math.round(ya),Math.round(xb),Math.round(yb)];};function getRect()
{var xsize=x2-x1;var ysize=y2-y1;if(xlimit&&(Math.abs(xsize)>xlimit))
x2=(xsize>0)?(x1+xlimit):(x1-xlimit);if(ylimit&&(Math.abs(ysize)>ylimit))
y2=(ysize>0)?(y1+ylimit):(y1-ylimit);if(ymin&&(Math.abs(ysize)<ymin))
y2=(ysize>0)?(y1+ymin):(y1-ymin);if(xmin&&(Math.abs(xsize)<xmin))
x2=(xsize>0)?(x1+xmin):(x1-xmin);if(x1<0){x2-=x1;x1-=x1;}
if(y1<0){y2-=y1;y1-=y1;}
if(x2<0){x1-=x2;x2-=x2;}
if(y2<0){y1-=y2;y2-=y2;}
if(x2>boundx){var delta=x2-boundx;x1-=delta;x2-=delta;}
if(y2>boundy){var delta=y2-boundy;y1-=delta;y2-=delta;}
if(x1>boundx){var delta=x1-boundy;y2-=delta;y1-=delta;}
if(y1>boundy){var delta=y1-boundy;y2-=delta;y1-=delta;}
return makeObj(flipCoords(x1,y1,x2,y2));};function makeObj(a)
{return{x:a[0],y:a[1],x2:a[2],y2:a[3],w:a[2]-a[0],h:a[3]-a[1]};};return{flipCoords:flipCoords,setPressed:setPressed,setCurrent:setCurrent,getOffset:getOffset,moveOffset:moveOffset,getCorner:getCorner,getFixed:getFixed};}();var Selection=function()
{var start,end,dragmode,awake,hdep=370;var borders={};var handle={};var seehandles=false;var hhs=options.handleOffset;if(options.drawBorders){borders={top:insertBorder('hline').css('top',$.browser.msie?px(-1):px(0)),bottom:insertBorder('hline'),left:insertBorder('vline'),right:insertBorder('vline')};}
if(options.dragEdges){handle.t=insertDragbar('n');handle.b=insertDragbar('s');handle.r=insertDragbar('e');handle.l=insertDragbar('w');}
options.sideHandles&&createHandles(['n','s','e','w']);options.cornerHandles&&createHandles(['sw','nw','ne','se']);function insertBorder(type)
{var jq=$('<div />').css({position:'absolute',opacity:options.borderOpacity}).addClass(cssClass(type));$img_holder.append(jq);return jq;};function dragDiv(ord,zi)
{var jq=$('<div />').mousedown(createDragger(ord)).css({cursor:ord+'-resize',position:'absolute',zIndex:zi});$hdl_holder.append(jq);return jq;};function insertHandle(ord)
{return dragDiv(ord,hdep++).css({top:px(-hhs+1),left:px(-hhs+1),opacity:options.handleOpacity}).addClass(cssClass('handle'));};function insertDragbar(ord)
{var s=options.handleSize,o=hhs,h=s,w=s,t=o,l=o;switch(ord)
{case'n':case's':w=pct(100);break;case'e':case'w':h=pct(100);break;}
return dragDiv(ord,hdep++).width(w).height(h).css({top:px(-t+1),left:px(-l+1)});};function createHandles(li)
{for(i in li)handle[li[i]]=insertHandle(li[i]);};function moveHandles(c)
{var midvert=Math.round((c.h/2)-hhs),midhoriz=Math.round((c.w/2)-hhs),north=west=-hhs+1,east=c.w-hhs,south=c.h-hhs,x,y;'e'in handle&&handle.e.css({top:px(midvert),left:px(east)})&&handle.w.css({top:px(midvert)})&&handle.s.css({top:px(south),left:px(midhoriz)})&&handle.n.css({left:px(midhoriz)});'ne'in handle&&handle.ne.css({left:px(east)})&&handle.se.css({top:px(south),left:px(east)})&&handle.sw.css({top:px(south)});'b'in handle&&handle.b.css({top:px(south)})&&handle.r.css({left:px(east)});};function moveto(x,y)
{$img2.css({top:px(-y),left:px(-x)});$sel.css({top:px(y),left:px(x)});};function resize(w,h)
{$sel.width(w).height(h);};function refresh()
{var c=Coords.getFixed();Coords.setPressed([c.x,c.y]);Coords.setCurrent([c.x2,c.y2]);updateVisible();};function updateVisible()
{if(awake)return update();};function update()
{var c=Coords.getFixed();resize(c.w,c.h);moveto(c.x,c.y);options.drawBorders&&borders['right'].css({left:px(c.w-1)})&&borders['bottom'].css({top:px(c.h-1)});seehandles&&moveHandles(c);awake||show();options.onChange(unscale(c));};function show()
{$sel.show();$img.css('opacity',options.bgOpacity);awake=true;};function release()
{disableHandles();$sel.hide();$img.css('opacity',1);awake=false;};function showHandles()
{if(seehandles)
{moveHandles(Coords.getFixed());$hdl_holder.show();}};function enableHandles()
{seehandles=true;if(options.allowResize)
{moveHandles(Coords.getFixed());$hdl_holder.show();return true;}};function disableHandles()
{seehandles=false;$hdl_holder.hide();};function animMode(v)
{(animating=v)?disableHandles():enableHandles();};function done()
{animMode(false);refresh();};var $track=newTracker().mousedown(createDragger('move')).css({cursor:'move',position:'absolute',zIndex:360})
$img_holder.append($track);disableHandles();return{updateVisible:updateVisible,update:update,release:release,refresh:refresh,setCursor:function(cursor){$track.css('cursor',cursor);},enableHandles:enableHandles,enableOnly:function(){seehandles=true;},showHandles:showHandles,disableHandles:disableHandles,animMode:animMode,done:done};}();var Tracker=function()
{var onMove=function(){},onDone=function(){},trackDoc=options.trackDocument;if(!trackDoc)
{$trk.mousemove(trackMove).mouseup(trackUp).mouseout(trackUp);}
function toFront()
{$trk.css({zIndex:450});if(trackDoc)
{$(document).mousemove(trackMove).mouseup(trackUp);}}
function toBack()
{$trk.css({zIndex:290});if(trackDoc)
{$(document).unbind('mousemove',trackMove).unbind('mouseup',trackUp);}}
function trackMove(e)
{onMove(mouseAbs(e));};function trackUp(e)
{e.preventDefault();e.stopPropagation();if(btndown)
{btndown=false;onDone(mouseAbs(e));options.onSelect(unscale(Coords.getFixed()));toBack();onMove=function(){};onDone=function(){};}
return false;};function activateHandlers(move,done)
{btndown=true;onMove=move;onDone=done;toFront();return false;};function setCursor(t){$trk.css('cursor',t);};$img.before($trk);return{activateHandlers:activateHandlers,setCursor:setCursor};}();var KeyManager=function()
{var $keymgr=$('<input type="radio" />').css({position:'absolute',left:'-30px'}).keypress(parseKey).blur(onBlur),$keywrap=$('<div />').css({position:'absolute',overflow:'hidden'}).append($keymgr);function watchKeys()
{if(options.keySupport)
{$keymgr.show();$keymgr.focus();}};function onBlur(e)
{$keymgr.hide();};function doNudge(e,x,y)
{if(options.allowMove){Coords.moveOffset([x,y]);Selection.updateVisible();};e.preventDefault();e.stopPropagation();};function parseKey(e)
{if(e.ctrlKey)return true;shift_down=e.shiftKey?true:false;var nudge=shift_down?10:1;switch(e.keyCode)
{case 37:doNudge(e,-nudge,0);break;case 39:doNudge(e,nudge,0);break;case 38:doNudge(e,0,-nudge);break;case 40:doNudge(e,0,nudge);break;case 27:Selection.release();break;case 9:return true;}
return nothing(e);};if(options.keySupport)$keywrap.insertBefore($img);return{watchKeys:watchKeys};}();function px(n){return''+parseInt(n)+'px';};function pct(n){return''+parseInt(n)+'%';};function cssClass(cl){return options.baseClass+'-'+cl;};function getPos(obj)
{var pos=$(obj).offset();return[pos.left,pos.top];};function mouseAbs(e)
{return[(e.pageX-docOffset[0]),(e.pageY-docOffset[1])];};function myCursor(type)
{if(type!=lastcurs)
{Tracker.setCursor(type);lastcurs=type;}};function startDragMode(mode,pos)
{docOffset=getPos($img);Tracker.setCursor(mode=='move'?mode:mode+'-resize');if(mode=='move')
return Tracker.activateHandlers(createMover(pos),doneSelect);var fc=Coords.getFixed();var opp=oppLockCorner(mode);var opc=Coords.getCorner(oppLockCorner(opp));Coords.setPressed(Coords.getCorner(opp));Coords.setCurrent(opc);Tracker.activateHandlers(dragmodeHandler(mode,fc),doneSelect);};function dragmodeHandler(mode,f)
{return function(pos){if(!options.aspectRatio)switch(mode)
{case'e':pos[1]=f.y2;break;case'w':pos[1]=f.y2;break;case'n':pos[0]=f.x2;break;case's':pos[0]=f.x2;break;}
else switch(mode)
{case'e':pos[1]=f.y+1;break;case'w':pos[1]=f.y+1;break;case'n':pos[0]=f.x+1;break;case's':pos[0]=f.x+1;break;}
Coords.setCurrent(pos);Selection.update();};};function createMover(pos)
{var lloc=pos;KeyManager.watchKeys();return function(pos)
{Coords.moveOffset([pos[0]-lloc[0],pos[1]-lloc[1]]);lloc=pos;Selection.update();};};function oppLockCorner(ord)
{switch(ord)
{case'n':return'sw';case's':return'nw';case'e':return'nw';case'w':return'ne';case'ne':return'sw';case'nw':return'se';case'se':return'nw';case'sw':return'ne';};};function createDragger(ord)
{return function(e){if(options.disabled)return false;if((ord=='move')&&!options.allowMove)return false;btndown=true;startDragMode(ord,mouseAbs(e));e.stopPropagation();e.preventDefault();return false;};};function presize($obj,w,h)
{var nw=$obj.width(),nh=$obj.height();if((nw>w)&&w>0)
{nw=w;nh=(w/$obj.width())*$obj.height();}
if((nh>h)&&h>0)
{nh=h;nw=(h/$obj.height())*$obj.width();}
xscale=$obj.width()/nw;yscale=$obj.height()/nh;$obj.width(nw).height(nh);};function unscale(c)
{return{x:parseInt(c.x*xscale),y:parseInt(c.y*yscale),x2:parseInt(c.x2*xscale),y2:parseInt(c.y2*yscale),w:parseInt(c.w*xscale),h:parseInt(c.h*yscale)};};function doneSelect(pos)
{var c=Coords.getFixed();if(c.w>options.minSelect[0]&&c.h>options.minSelect[1])
{Selection.enableHandles();Selection.done();}
else
{Selection.release();}
Tracker.setCursor(options.allowSelect?'crosshair':'default');};function newSelection(e)
{if(options.disabled)return false;if(!options.allowSelect)return false;btndown=true;docOffset=getPos($img);Selection.disableHandles();myCursor('crosshair');var pos=mouseAbs(e);Coords.setPressed(pos);Tracker.activateHandlers(selectDrag,doneSelect);KeyManager.watchKeys();Selection.update();e.stopPropagation();e.preventDefault();return false;};function selectDrag(pos)
{Coords.setCurrent(pos);Selection.update();};function newTracker()
{var trk=$('<div></div>').addClass(cssClass('tracker'));$.browser.msie&&trk.css({opacity:0,backgroundColor:'white'});return trk;};function animateTo(a)
{var x1=a[0]/xscale,y1=a[1]/yscale,x2=a[2]/xscale,y2=a[3]/yscale;if(animating)return;var animto=Coords.flipCoords(x1,y1,x2,y2);var c=Coords.getFixed();var animat=initcr=[c.x,c.y,c.x2,c.y2];var interv=options.animationDelay;var x=animat[0];var y=animat[1];var x2=animat[2];var y2=animat[3];var ix1=animto[0]-initcr[0];var iy1=animto[1]-initcr[1];var ix2=animto[2]-initcr[2];var iy2=animto[3]-initcr[3];var pcent=0;var velocity=options.swingSpeed;Selection.animMode(true);var animator=function()
{return function()
{pcent+=(100-pcent)/velocity;animat[0]=x+((pcent/100)*ix1);animat[1]=y+((pcent/100)*iy1);animat[2]=x2+((pcent/100)*ix2);animat[3]=y2+((pcent/100)*iy2);if(pcent<100)animateStart();else Selection.done();if(pcent>=99.8)pcent=100;setSelectRaw(animat);};}();function animateStart()
{window.setTimeout(animator,interv);};animateStart();};function setSelect(rect)
{setSelectRaw([rect[0]/xscale,rect[1]/yscale,rect[2]/xscale,rect[3]/yscale]);};function setSelectRaw(l)
{Coords.setPressed([l[0],l[1]]);Coords.setCurrent([l[2],l[3]]);Selection.update();};function setOptions(opt)
{if(typeof(opt)!='object')opt={};options=$.extend(options,opt);if(typeof(options.onChange)!=='function')
options.onChange=function(){};if(typeof(options.onSelect)!=='function')
options.onSelect=function(){};};function tellSelect()
{return unscale(Coords.getFixed());};function tellScaled()
{return Coords.getFixed();};function setOptionsNew(opt)
{setOptions(opt);interfaceUpdate();};function disableCrop()
{options.disabled=true;Selection.disableHandles();Selection.setCursor('default');Tracker.setCursor('default');};function enableCrop()
{options.disabled=false;interfaceUpdate();};function cancelCrop()
{Selection.done();Tracker.activateHandlers(null,null);};function destroy()
{$div.remove();$origimg.show();};function interfaceUpdate(alt)
{options.allowResize?alt?Selection.enableOnly():Selection.enableHandles():Selection.disableHandles();Tracker.setCursor(options.allowSelect?'crosshair':'default');Selection.setCursor(options.allowMove?'move':'default');$div.css('backgroundColor',options.bgColor);if('setSelect'in options){setSelect(opt.setSelect);Selection.done();delete(options.setSelect);}
if('trueSize'in options){xscale=options.trueSize[0]/boundx;yscale=options.trueSize[1]/boundy;}
xlimit=options.maxSize[0]||0;ylimit=options.maxSize[1]||0;xmin=options.minSize[0]||0;ymin=options.minSize[1]||0;if('outerImage'in options)
{$img.attr('src',options.outerImage);delete(options.outerImage);}
Selection.refresh();};$hdl_holder.hide();interfaceUpdate(true);var api={animateTo:animateTo,setSelect:setSelect,setOptions:setOptionsNew,tellSelect:tellSelect,tellScaled:tellScaled,disable:disableCrop,enable:enableCrop,cancel:cancelCrop,focus:KeyManager.watchKeys,getBounds:function(){return[boundx*xscale,boundy*yscale];},getWidgetSize:function(){return[boundx,boundy];},release:Selection.release,destroy:destroy};$origimg.data('Jcrop',api);return api;};$.fn.Jcrop=function(options)
{function attachWhenDone(from)
{var loadsrc=options.useImg||from.src;var img=new Image();img.onload=function(){$.Jcrop(from,options);};img.src=loadsrc;};if(typeof(options)!=='object')options={};this.each(function()
{if($(this).data('Jcrop'))
{if(options=='api')return $(this).data('Jcrop');else $(this).data('Jcrop').setOptions(options);}
else attachWhenDone(this);});return this;};})(jQuery);


/**
From here starts the actual script that handles the avatar 
in the Verkkomuikku Front End User plugin extension.

*/
jQuery(document).ready(function() {
	
	jQuery("#vmfeu_avatar_uploadify").uploadify({
		'uploader'		: VMFEUAjax.pluginurl+'/includes/crop/uploadify/uploadify.swf',
		'script'		: VMFEUAjax.ajaxurl,
		'cancelImg'		: VMFEUAjax.pluginurl+'/includes/crop/uploadify/cancel.png',
		'width'			: '19',
		'height'		: '19',
		'queueID'		: 'vmfeu_avatar_fileQueue',
		'fileDataName'	: 'vmfeu_avatar_file',
		'auto'			: true,
		'queueSizeLimit' : 1,
		'multi'			: false,
		'fileDesc'		: 'jpg, png',
		'fileExt'		: '*.jpg;*.png',
		'sizeLimit'		: '8192000',//max size bytes - around 8mb
	    'scriptData'	: {
			'action' 	: 'vmfeu_avatar_upload'
	    },	
	    'onOpen'      : function(event,ID,fileObj) {
	    	jQuery("#vmfeu_avatar_ajaxload").show();
	    	
	    }, 
		'onComplete'  	 : function(event, ID, fileObj, json, data) {

    		//console.log(json);

	    	jQuery("#vmfeu_avatar_ajaxload").hide();
			jQuery('#vmfeu_avatar_uploadifyUploader').hide();
			var response = jQuery.parseJSON(json);
			
			if(response.result == '1'){
				
				// Hide remove button if it was visible
				jQuery('#vmfeu_avatar_remove').hide();
				
				jQuery('#vmfeu_avatar_image_container').show();
				jQuery('#vmfeu_avatar_crop_buttons').show();
				jQuery('#vmfeu_avatar_crop_nag').show();
				jQuery('.update_profile_nag').hide();
				jQuery('#vmfeu_avatar_tempfile').val(response.filename);				
				
				var img = new Image();
				// console.log(img);
	        	jQuery(img).load(function () {
		        	$imgpos.width  = response.imagewidth;
		            $imgpos.height = response.imageheight;
					jQuery("#vmfeu_avatar_cropbox").remove();
		        	jQuery(".vmfeu_avatar_jcrop-holder").remove();
		        	jQuery(this).attr('id','vmfeu_avatar_cropbox');
		            jQuery(this).hide();
		            jQuery('#vmfeu_avatar_image_container').append(this);
		            
		            jQuery(this).show();
		            vmfeu_avatar_jcrop = jQuery.Jcrop(jQuery(this), {
						onChange: showPreview,
						onSelect: showPreview,
						aspectRatio: 1,
						onSelect: updateCoords,
						setSelect: [ 0, 0, VMFEUAvatar.size, VMFEUAvatar.size ]
					});

		            // Hide the orginal avatar, or the one that was there before
		            // user uploaded this one
		            jQuery("#vmfeu_avatar_preview_container img.avatar").hide();

		            // Create image for crop preview
		            var _imgprev = jQuery(document.createElement('img')).attr('id', 'vmfeu_avatar_crop_preview').attr('src',response.file_url);
		            
		            jQuery('#vmfeu_avatar_preview_container').append(_imgprev);

		            // Put the save and cancel buttons
					if(!jQuery('#vmfeu_avatar__crop_bttn').length){		
		            	var _crop_bttn = jQuery(document.createElement('a')).attr('id','vmfeu_avatar__crop_bttn').attr('href', '#save').addClass('vmfeu_crop_button').html(VMFEUAvatar.save_button);
		            	var _crop_cancel_bttn = jQuery(document.createElement('a')).attr('id','vmfeu_avatar__crop_cancel_bttn').attr('href', '#cancel').addClass('vmfeu_crop_button').html(VMFEUAvatar.cancel_button);
		            	jQuery(_crop_cancel_bttn).click(function() { vmfeu_crop_cancel(); });	
						jQuery(_crop_bttn).click(function() { vmfeu_crop_save(); });	
		            	jQuery('#vmfeu_avatar_crop_buttons').append(_crop_bttn).append(_crop_cancel_bttn);
					}
	        	}).attr('src', response.file_url);
			} else {
				jQuery("#vmfeu_avatar_ajaxload").hide();
			}
		},				
		'onError'		 : function(){
			jQuery("#vmfeu_avatar_ajaxload").hide();
		},
		'onClose'		 : function(){
			jQuery("#vmfeu_avatar_ajaxload").hide();
		}
	});
	
	jQuery('#vmfeu_avatar_preview_container').hover(
		function () {
			jQuery('#vmfeu_avatar_overlay').show();
		  }, 
		function () {
			jQuery('#vmfeu_avatar_overlay').hide();
		}
	);
	
	// Activate the remove avatar button
	jQuery("#vmfeu_avatar_remove a").click(vmfeu_remove_avatar);
	
});   


function updateCoords(c) {
	vmfeu_avatar_x = c.x;
	vmfeu_avatar_y = c.y;
	vmfeu_avatar_w = c.w;
	vmfeu_avatar_h = c.h;
	//console.log(vmfeu_avatar_h, vmfeu_avatar_x, vmfeu_avatar_y);
};

/**
 * Check there are some coordinates 
 * 
 */
function checkCoords() {
	
	if (parseInt(vmfeu_avatar_w) && parseInt(vmfeu_avatar_h)){
		return true;
	}	
	
	vmfeu_avatar_x = 0;
	vmfeu_avatar_y = 0;
	vmfeu_avatar_w = VMFEUAvatar.size;
	vmfeu_avatar_h = VMFEUAvatar.size;
	
	return true;
};

/**
 * Update the avatar preview as user moves / resizes the selector
 * 
 */
function showPreview(coords)
{
	if (parseInt(coords.w) > 0)
	{
		var rx = 100 / coords.w;
		var ry = 100 / coords.h;
		
		jQuery('#vmfeu_avatar_crop_preview').css({
			width: Math.round(rx * $imgpos.width) + 'px',
			height: Math.round(ry * $imgpos.height) + 'px',
			marginLeft: '-' + Math.round(rx * coords.x) + 'px',
			marginTop: '-' + Math.round(ry * coords.y) + 'px'
		});
	}
};	

/**
 * Cancel button click function
 * 
 */
function vmfeu_crop_cancel() {

	// Hide the crop tools
	jQuery('#vmfeu_avatar_crop_buttons').hide();
	jQuery('#vmfeu_avatar_crop_nag').hide();
	
	// Destroy the Jcrop
	if (typeof vmfeu_avatar_jcrop === 'function')
		vmfeu_avatar_jcrop.destroy();
	
	// Destroy the preview avatar image
	jQuery('#vmfeu_avatar_crop_preview').remove();
	
    // Update the original avatar with the new one
    jQuery('#vmfeu_avatar_preview_container img.avatar').show();				
	
    // Restore the hidden upload button
    jQuery('#vmfeu_avatar_uploadifyUploader').show();
	
	// Show remove button
	if (jQuery('#vmfeu_avatar_remove').attr("id")) {
		jQuery('#vmfeu_avatar_remove').show();
	}
    
	jQuery('#vmfeu_avatar_image_container').html("").hide();
	
	
	return false;
}

/**
 * Save button click function
 * Save the cropped image as avatar
 * 
 */
function vmfeu_crop_save() {

	// Sanitize coordinates
	checkCoords();
	
	// Hide the crop tools
	jQuery('#vmfeu_avatar_crop_buttons').hide();
	jQuery('#vmfeu_avatar_crop_nag').hide();
	
	// Show ajax loader
	jQuery("#vmfeu_avatar_ajaxload").show();
	
	// Send ajax query with the info about cropped image
	jQuery.ajax({
		type: "POST",
		url: VMFEUAjax.ajaxurl,
		timeout : 30000,
		dataType: "json",
		data : {
			action : "vmfeu_avatar_crop",
			file	: jQuery('#vmfeu_avatar_tempfile').val(),
			x 		: vmfeu_avatar_x,
			y 		: vmfeu_avatar_y,
			w 		: vmfeu_avatar_w,
			h 		: vmfeu_avatar_h
		},
		success: function(response){
			
			// OK
			if(response.result == '1'){
			
				// Restore the divs and images etc. for next round ;)
				vmfeu_crop_cancel();
				
	            // Update the original avatar with the new one
	            jQuery('#vmfeu_avatar_preview_container img.avatar').attr('src', response.file_url);				
				
				// Save the file name as the input value
				jQuery("input#vmfeu_avatar").val(response.filename);
				
				if (!jQuery('.update_profile_nag').html()) {
					var nag = document.createElement('div')
					jQuery(nag).addClass('update_profile_nag');
					jQuery(".vmfeu_avatar_content").append(nag);
				} else {
					jQuery('.update_profile_nag').show();
				}
				
				jQuery('.update_profile_nag').html(response.message);

			}	
			jQuery('#vmfeu_avatar_ajaxload').hide();
							
		},
		error  : function(){
			//console.log("error sending the cropped image")
			jQuery('#vmfeu_avatar_ajaxload').hide();
		}
	});
	
	return false;
}

/**
 * Remove button click function
 * Remove the user avatar
 * 
 */
function vmfeu_remove_avatar() {

	// Show ajax loader
	jQuery("#vmfeu_avatar_ajaxload").show();
	
	// Figure out the user id. The form should have input 
	// field named edit_id (or none if own profile)
	var edit_user_id = 0;
	
	if (jQuery("input[name=edit_id]").attr("name")) {
		edit_user_id = jQuery("input[name=edit_id]").val();
	}
	
	// Figure out the size of the avatar (affects the response default avatar size)
	var avatar_size = jQuery('#vmfeu_avatar_preview_container img.avatar').width();
	
	// Send ajax query with the info about cropped image
	jQuery.ajax({
		type: "POST",
		url: VMFEUAjax.ajaxurl,
		timeout : 30000,
		dataType: "json",
		data : {
			action : "vmfeu_avatar_remove",
			user_id : edit_user_id,
			size : avatar_size
		},
		success: function(response){
			
			// OK
			if(response.result == '1'){
			
				// Restore the divs and images etc. for next round ;)
				vmfeu_crop_cancel();
				
				// Except, hide the remove button
				jQuery("#vmfeu_avatar_remove").hide();
				
	            // Update the original avatar with the new one.
				// The response contains the whole <img> since the PHP script
				// returns result of get_avatar()
	            jQuery('#vmfeu_avatar_preview_container img.avatar').replaceWith(response.avatar_img).addClass('avatar');				
				
				// Empty out the filename
				jQuery("input#vmfeu_avatar").val('');

			}
			jQuery('#vmfeu_avatar_ajaxload').hide();
							
		},
		error  : function(){
			//console.log("error sending the cropped image")
			jQuery('#vmfeu_avatar_ajaxload').hide();
		}
	});
	
	return false;
}

var vmfeu_avatar_jcrop;
var vmfeu_avatar_x;
var vmfeu_avatar_y;
var vmfeu_avatar_w;
var vmfeu_avatar_h;

var $imgpos = {
	    width	: '100',
	    height	: '100'
};