import{q as Ut,s as Dt}from"./app-B-vILBvW.js";var G={},K={exports:{}},Q,ze;function Ct(){return ze||(ze=1,Q=function(f,d){return function(){for(var u=new Array(arguments.length),l=0;l<u.length;l++)u[l]=arguments[l];return f.apply(d,u)}}),Q}var Y,$e;function I(){if($e)return Y;$e=1;var n=Ct(),f=Object.prototype.toString;function d(e){return f.call(e)==="[object Array]"}function a(e){return typeof e>"u"}function u(e){return e!==null&&!a(e)&&e.constructor!==null&&!a(e.constructor)&&typeof e.constructor.isBuffer=="function"&&e.constructor.isBuffer(e)}function l(e){return f.call(e)==="[object ArrayBuffer]"}function i(e){return typeof FormData<"u"&&e instanceof FormData}function v(e){var r;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?r=ArrayBuffer.isView(e):r=e&&e.buffer&&e.buffer instanceof ArrayBuffer,r}function h(e){return typeof e=="string"}function t(e){return typeof e=="number"}function g(e){return e!==null&&typeof e=="object"}function E(e){if(f.call(e)!=="[object Object]")return!1;var r=Object.getPrototypeOf(e);return r===null||r===Object.prototype}function C(e){return f.call(e)==="[object Date]"}function y(e){return f.call(e)==="[object File]"}function p(e){return f.call(e)==="[object Blob]"}function P(e){return f.call(e)==="[object Function]"}function w(e){return g(e)&&P(e.pipe)}function U(e){return typeof URLSearchParams<"u"&&e instanceof URLSearchParams}function D(e){return e.trim?e.trim():e.replace(/^\s+|\s+$/g,"")}function N(){return typeof navigator<"u"&&(navigator.product==="ReactNative"||navigator.product==="NativeScript"||navigator.product==="NS")?!1:typeof window<"u"&&typeof document<"u"}function L(e,r){if(!(e===null||typeof e>"u"))if(typeof e!="object"&&(e=[e]),d(e))for(var o=0,m=e.length;o<m;o++)r.call(null,e[o],o,e);else for(var S in e)Object.prototype.hasOwnProperty.call(e,S)&&r.call(null,e[S],S,e)}function B(){var e={};function r(S,q){E(e[q])&&E(S)?e[q]=B(e[q],S):E(S)?e[q]=B({},S):d(S)?e[q]=S.slice():e[q]=S}for(var o=0,m=arguments.length;o<m;o++)L(arguments[o],r);return e}function c(e,r,o){return L(r,function(S,q){o&&typeof S=="function"?e[q]=n(S,o):e[q]=S}),e}function s(e){return e.charCodeAt(0)===65279&&(e=e.slice(1)),e}return Y={isArray:d,isArrayBuffer:l,isBuffer:u,isFormData:i,isArrayBufferView:v,isString:h,isNumber:t,isObject:g,isPlainObject:E,isUndefined:a,isDate:C,isFile:y,isBlob:p,isFunction:P,isStream:w,isURLSearchParams:U,isStandardBrowserEnv:N,forEach:L,merge:B,extend:c,trim:D,stripBOM:s},Y}var Z,Ge;function qt(){if(Ge)return Z;Ge=1;var n=I();function f(d){return encodeURIComponent(d).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}return Z=function(a,u,l){if(!u)return a;var i;if(l)i=l(u);else if(n.isURLSearchParams(u))i=u.toString();else{var v=[];n.forEach(u,function(g,E){g===null||typeof g>"u"||(n.isArray(g)?E=E+"[]":g=[g],n.forEach(g,function(y){n.isDate(y)?y=y.toISOString():n.isObject(y)&&(y=JSON.stringify(y)),v.push(f(E)+"="+f(y))}))}),i=v.join("&")}if(i){var h=a.indexOf("#");h!==-1&&(a=a.slice(0,h)),a+=(a.indexOf("?")===-1?"?":"&")+i}return a},Z}var ee,Qe;function Bt(){if(Qe)return ee;Qe=1;var n=I();function f(){this.handlers=[]}return f.prototype.use=function(a,u,l){return this.handlers.push({fulfilled:a,rejected:u,synchronous:l?l.synchronous:!1,runWhen:l?l.runWhen:null}),this.handlers.length-1},f.prototype.eject=function(a){this.handlers[a]&&(this.handlers[a]=null)},f.prototype.forEach=function(a){n.forEach(this.handlers,function(l){l!==null&&a(l)})},ee=f,ee}var te,Ye;function jt(){if(Ye)return te;Ye=1;var n=I();return te=function(d,a){n.forEach(d,function(l,i){i!==a&&i.toUpperCase()===a.toUpperCase()&&(d[a]=l,delete d[i])})},te}var re,Ze;function Ot(){return Ze||(Ze=1,re=function(f,d,a,u,l){return f.config=d,a&&(f.code=a),f.request=u,f.response=l,f.isAxiosError=!0,f.toJSON=function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:this.config,code:this.code}},f}),re}var ne,et;function Pt(){if(et)return ne;et=1;var n=Ot();return ne=function(d,a,u,l,i){var v=new Error(d);return n(v,a,u,l,i)},ne}var ie,tt;function kt(){if(tt)return ie;tt=1;var n=Pt();return ie=function(d,a,u){var l=u.config.validateStatus;!u.status||!l||l(u.status)?d(u):a(n("Request failed with status code "+u.status,u.config,null,u.request,u))},ie}var oe,rt;function Mt(){if(rt)return oe;rt=1;var n=I();return oe=n.isStandardBrowserEnv()?function(){return{write:function(a,u,l,i,v,h){var t=[];t.push(a+"="+encodeURIComponent(u)),n.isNumber(l)&&t.push("expires="+new Date(l).toGMTString()),n.isString(i)&&t.push("path="+i),n.isString(v)&&t.push("domain="+v),h===!0&&t.push("secure"),document.cookie=t.join("; ")},read:function(a){var u=document.cookie.match(new RegExp("(^|;\\s*)("+a+")=([^;]*)"));return u?decodeURIComponent(u[3]):null},remove:function(a){this.write(a,"",Date.now()-864e5)}}}():function(){return{write:function(){},read:function(){return null},remove:function(){}}}(),oe}var ae,nt;function Vt(){return nt||(nt=1,ae=function(f){return/^([a-z][a-z\d\+\-\.]*:)?\/\//i.test(f)}),ae}var se,it;function Ft(){return it||(it=1,se=function(f,d){return d?f.replace(/\/+$/,"")+"/"+d.replace(/^\/+/,""):f}),se}var ue,ot;function Ht(){if(ot)return ue;ot=1;var n=Vt(),f=Ft();return ue=function(a,u){return a&&!n(u)?f(a,u):u},ue}var ce,at;function Jt(){if(at)return ce;at=1;var n=I(),f=["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"];return ce=function(a){var u={},l,i,v;return a&&n.forEach(a.split(`
`),function(t){if(v=t.indexOf(":"),l=n.trim(t.substr(0,v)).toLowerCase(),i=n.trim(t.substr(v+1)),l){if(u[l]&&f.indexOf(l)>=0)return;l==="set-cookie"?u[l]=(u[l]?u[l]:[]).concat([i]):u[l]=u[l]?u[l]+", "+i:i}}),u},ce}var le,st;function _t(){if(st)return le;st=1;var n=I();return le=n.isStandardBrowserEnv()?function(){var d=/(msie|trident)/i.test(navigator.userAgent),a=document.createElement("a"),u;function l(i){var v=i;return d&&(a.setAttribute("href",v),v=a.href),a.setAttribute("href",v),{href:a.href,protocol:a.protocol?a.protocol.replace(/:$/,""):"",host:a.host,search:a.search?a.search.replace(/^\?/,""):"",hash:a.hash?a.hash.replace(/^#/,""):"",hostname:a.hostname,port:a.port,pathname:a.pathname.charAt(0)==="/"?a.pathname:"/"+a.pathname}}return u=l(window.location.href),function(v){var h=n.isString(v)?l(v):v;return h.protocol===u.protocol&&h.host===u.host}}():function(){return function(){return!0}}(),le}var fe,ut;function ct(){if(ut)return fe;ut=1;var n=I(),f=kt(),d=Mt(),a=qt(),u=Ht(),l=Jt(),i=_t(),v=Pt();return fe=function(t){return new Promise(function(E,C){var y=t.data,p=t.headers,P=t.responseType;n.isFormData(y)&&delete p["Content-Type"];var w=new XMLHttpRequest;if(t.auth){var U=t.auth.username||"",D=t.auth.password?unescape(encodeURIComponent(t.auth.password)):"";p.Authorization="Basic "+btoa(U+":"+D)}var N=u(t.baseURL,t.url);w.open(t.method.toUpperCase(),a(N,t.params,t.paramsSerializer),!0),w.timeout=t.timeout;function L(){if(w){var c="getAllResponseHeaders"in w?l(w.getAllResponseHeaders()):null,s=!P||P==="text"||P==="json"?w.responseText:w.response,e={data:s,status:w.status,statusText:w.statusText,headers:c,config:t,request:w};f(E,C,e),w=null}}if("onloadend"in w?w.onloadend=L:w.onreadystatechange=function(){!w||w.readyState!==4||w.status===0&&!(w.responseURL&&w.responseURL.indexOf("file:")===0)||setTimeout(L)},w.onabort=function(){w&&(C(v("Request aborted",t,"ECONNABORTED",w)),w=null)},w.onerror=function(){C(v("Network Error",t,null,w)),w=null},w.ontimeout=function(){var s="timeout of "+t.timeout+"ms exceeded";t.timeoutErrorMessage&&(s=t.timeoutErrorMessage),C(v(s,t,t.transitional&&t.transitional.clarifyTimeoutError?"ETIMEDOUT":"ECONNABORTED",w)),w=null},n.isStandardBrowserEnv()){var B=(t.withCredentials||i(N))&&t.xsrfCookieName?d.read(t.xsrfCookieName):void 0;B&&(p[t.xsrfHeaderName]=B)}"setRequestHeader"in w&&n.forEach(p,function(s,e){typeof y>"u"&&e.toLowerCase()==="content-type"?delete p[e]:w.setRequestHeader(e,s)}),n.isUndefined(t.withCredentials)||(w.withCredentials=!!t.withCredentials),P&&P!=="json"&&(w.responseType=t.responseType),typeof t.onDownloadProgress=="function"&&w.addEventListener("progress",t.onDownloadProgress),typeof t.onUploadProgress=="function"&&w.upload&&w.upload.addEventListener("progress",t.onUploadProgress),t.cancelToken&&t.cancelToken.promise.then(function(s){w&&(w.abort(),C(s),w=null)}),y||(y=null),w.send(y)})},fe}var de,lt;function Ce(){if(lt)return de;lt=1;var n=I(),f=jt(),d=Ot(),a={"Content-Type":"application/x-www-form-urlencoded"};function u(h,t){!n.isUndefined(h)&&n.isUndefined(h["Content-Type"])&&(h["Content-Type"]=t)}function l(){var h;return(typeof XMLHttpRequest<"u"||typeof process<"u"&&Object.prototype.toString.call(process)==="[object process]")&&(h=ct()),h}function i(h,t,g){if(n.isString(h))try{return(t||JSON.parse)(h),n.trim(h)}catch(E){if(E.name!=="SyntaxError")throw E}return(g||JSON.stringify)(h)}var v={transitional:{silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},adapter:l(),transformRequest:[function(t,g){return f(g,"Accept"),f(g,"Content-Type"),n.isFormData(t)||n.isArrayBuffer(t)||n.isBuffer(t)||n.isStream(t)||n.isFile(t)||n.isBlob(t)?t:n.isArrayBufferView(t)?t.buffer:n.isURLSearchParams(t)?(u(g,"application/x-www-form-urlencoded;charset=utf-8"),t.toString()):n.isObject(t)||g&&g["Content-Type"]==="application/json"?(u(g,"application/json"),i(t)):t}],transformResponse:[function(t){var g=this.transitional,E=g&&g.silentJSONParsing,C=g&&g.forcedJSONParsing,y=!E&&this.responseType==="json";if(y||C&&n.isString(t)&&t.length)try{return JSON.parse(t)}catch(p){if(y)throw p.name==="SyntaxError"?d(p,this,"E_JSON_PARSE"):p}return t}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,validateStatus:function(t){return t>=200&&t<300}};return v.headers={common:{Accept:"application/json, text/plain, */*"}},n.forEach(["delete","get","head"],function(t){v.headers[t]={}}),n.forEach(["post","put","patch"],function(t){v.headers[t]=n.merge(a)}),de=v,de}var he,ft;function Wt(){if(ft)return he;ft=1;var n=I(),f=Ce();return he=function(a,u,l){var i=this||f;return n.forEach(l,function(h){a=h.call(i,a,u)}),a},he}var pe,dt;function Tt(){return dt||(dt=1,pe=function(f){return!!(f&&f.__CANCEL__)}),pe}var ve,ht;function Xt(){if(ht)return ve;ht=1;var n=I(),f=Wt(),d=Tt(),a=Ce();function u(l){l.cancelToken&&l.cancelToken.throwIfRequested()}return ve=function(i){u(i),i.headers=i.headers||{},i.data=f.call(i,i.data,i.headers,i.transformRequest),i.headers=n.merge(i.headers.common||{},i.headers[i.method]||{},i.headers),n.forEach(["delete","get","head","post","put","patch","common"],function(t){delete i.headers[t]});var v=i.adapter||a.adapter;return v(i).then(function(t){return u(i),t.data=f.call(i,t.data,t.headers,i.transformResponse),t},function(t){return d(t)||(u(i),t&&t.response&&(t.response.data=f.call(i,t.response.data,t.response.headers,i.transformResponse))),Promise.reject(t)})},ve}var me,pt;function At(){if(pt)return me;pt=1;var n=I();return me=function(d,a){a=a||{};var u={},l=["url","method","data"],i=["headers","auth","proxy","params"],v=["baseURL","transformRequest","transformResponse","paramsSerializer","timeout","timeoutMessage","withCredentials","adapter","responseType","xsrfCookieName","xsrfHeaderName","onUploadProgress","onDownloadProgress","decompress","maxContentLength","maxBodyLength","maxRedirects","transport","httpAgent","httpsAgent","cancelToken","socketPath","responseEncoding"],h=["validateStatus"];function t(y,p){return n.isPlainObject(y)&&n.isPlainObject(p)?n.merge(y,p):n.isPlainObject(p)?n.merge({},p):n.isArray(p)?p.slice():p}function g(y){n.isUndefined(a[y])?n.isUndefined(d[y])||(u[y]=t(void 0,d[y])):u[y]=t(d[y],a[y])}n.forEach(l,function(p){n.isUndefined(a[p])||(u[p]=t(void 0,a[p]))}),n.forEach(i,g),n.forEach(v,function(p){n.isUndefined(a[p])?n.isUndefined(d[p])||(u[p]=t(void 0,d[p])):u[p]=t(void 0,a[p])}),n.forEach(h,function(p){p in a?u[p]=t(d[p],a[p]):p in d&&(u[p]=t(void 0,d[p]))});var E=l.concat(i).concat(v).concat(h),C=Object.keys(d).concat(Object.keys(a)).filter(function(p){return E.indexOf(p)===-1});return n.forEach(C,g),u},me}const Kt="0.21.4",zt={version:Kt};var ye,vt;function $t(){if(vt)return ye;vt=1;var n=zt,f={};["object","boolean","number","function","string","symbol"].forEach(function(i,v){f[i]=function(t){return typeof t===i||"a"+(v<1?"n ":" ")+i}});var d={},a=n.version.split(".");function u(i,v){for(var h=v?v.split("."):a,t=i.split("."),g=0;g<3;g++){if(h[g]>t[g])return!0;if(h[g]<t[g])return!1}return!1}f.transitional=function(v,h,t){var g=h&&u(h);function E(C,y){return"[Axios v"+n.version+"] Transitional option '"+C+"'"+y+(t?". "+t:"")}return function(C,y,p){if(v===!1)throw new Error(E(y," has been removed in "+h));return g&&!d[y]&&(d[y]=!0,console.warn(E(y," has been deprecated since v"+h+" and will be removed in the near future"))),v?v(C,y,p):!0}};function l(i,v,h){if(typeof i!="object")throw new TypeError("options must be an object");for(var t=Object.keys(i),g=t.length;g-- >0;){var E=t[g],C=v[E];if(C){var y=i[E],p=y===void 0||C(y,E,i);if(p!==!0)throw new TypeError("option "+E+" must be "+p);continue}if(h!==!0)throw Error("Unknown option "+E)}}return ye={isOlderVersion:u,assertOptions:l,validators:f},ye}var ge,mt;function Gt(){if(mt)return ge;mt=1;var n=I(),f=qt(),d=Bt(),a=Xt(),u=At(),l=$t(),i=l.validators;function v(h){this.defaults=h,this.interceptors={request:new d,response:new d}}return v.prototype.request=function(t){typeof t=="string"?(t=arguments[1]||{},t.url=arguments[0]):t=t||{},t=u(this.defaults,t),t.method?t.method=t.method.toLowerCase():this.defaults.method?t.method=this.defaults.method.toLowerCase():t.method="get";var g=t.transitional;g!==void 0&&l.assertOptions(g,{silentJSONParsing:i.transitional(i.boolean,"1.0.0"),forcedJSONParsing:i.transitional(i.boolean,"1.0.0"),clarifyTimeoutError:i.transitional(i.boolean,"1.0.0")},!1);var E=[],C=!0;this.interceptors.request.forEach(function(L){typeof L.runWhen=="function"&&L.runWhen(t)===!1||(C=C&&L.synchronous,E.unshift(L.fulfilled,L.rejected))});var y=[];this.interceptors.response.forEach(function(L){y.push(L.fulfilled,L.rejected)});var p;if(!C){var P=[a,void 0];for(Array.prototype.unshift.apply(P,E),P=P.concat(y),p=Promise.resolve(t);P.length;)p=p.then(P.shift(),P.shift());return p}for(var w=t;E.length;){var U=E.shift(),D=E.shift();try{w=U(w)}catch(N){D(N);break}}try{p=a(w)}catch(N){return Promise.reject(N)}for(;y.length;)p=p.then(y.shift(),y.shift());return p},v.prototype.getUri=function(t){return t=u(this.defaults,t),f(t.url,t.params,t.paramsSerializer).replace(/^\?/,"")},n.forEach(["delete","get","head","options"],function(t){v.prototype[t]=function(g,E){return this.request(u(E||{},{method:t,url:g,data:(E||{}).data}))}}),n.forEach(["post","put","patch"],function(t){v.prototype[t]=function(g,E,C){return this.request(u(C||{},{method:t,url:g,data:E}))}}),ge=v,ge}var we,yt;function Lt(){if(yt)return we;yt=1;function n(f){this.message=f}return n.prototype.toString=function(){return"Cancel"+(this.message?": "+this.message:"")},n.prototype.__CANCEL__=!0,we=n,we}var Se,gt;function Qt(){if(gt)return Se;gt=1;var n=Lt();function f(d){if(typeof d!="function")throw new TypeError("executor must be a function.");var a;this.promise=new Promise(function(i){a=i});var u=this;d(function(i){u.reason||(u.reason=new n(i),a(u.reason))})}return f.prototype.throwIfRequested=function(){if(this.reason)throw this.reason},f.source=function(){var a,u=new f(function(i){a=i});return{token:u,cancel:a}},Se=f,Se}var Ee,wt;function Yt(){return wt||(wt=1,Ee=function(f){return function(a){return f.apply(null,a)}}),Ee}var be,St;function Zt(){return St||(St=1,be=function(f){return typeof f=="object"&&f.isAxiosError===!0}),be}var Et;function er(){if(Et)return K.exports;Et=1;var n=I(),f=Ct(),d=Gt(),a=At(),u=Ce();function l(v){var h=new d(v),t=f(d.prototype.request,h);return n.extend(t,d.prototype,h),n.extend(t,h),t}var i=l(u);return i.Axios=d,i.create=function(h){return l(a(i.defaults,h))},i.Cancel=Lt(),i.CancelToken=Qt(),i.isCancel=Tt(),i.all=function(h){return Promise.all(h)},i.spread=Yt(),i.isAxiosError=Zt(),K.exports=i,K.exports.default=i,K.exports}var Re,bt;function tr(){return bt||(bt=1,Re=er()),Re}var Rt;function rr(){return Rt||(Rt=1,function(n){function f(c){return c&&typeof c=="object"&&"default"in c?c.default:c}var d=f(tr()),a=Dt(),u=f(Ut());function l(){return(l=Object.assign?Object.assign.bind():function(c){for(var s=1;s<arguments.length;s++){var e=arguments[s];for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&(c[r]=e[r])}return c}).apply(this,arguments)}var i,v={modal:null,listener:null,show:function(c){var s=this;typeof c=="object"&&(c="All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.<hr>"+JSON.stringify(c));var e=document.createElement("html");e.innerHTML=c,e.querySelectorAll("a").forEach(function(o){return o.setAttribute("target","_top")}),this.modal=document.createElement("div"),this.modal.style.position="fixed",this.modal.style.width="100vw",this.modal.style.height="100vh",this.modal.style.padding="50px",this.modal.style.boxSizing="border-box",this.modal.style.backgroundColor="rgba(0, 0, 0, .6)",this.modal.style.zIndex=2e5,this.modal.addEventListener("click",function(){return s.hide()});var r=document.createElement("iframe");if(r.style.backgroundColor="white",r.style.borderRadius="5px",r.style.width="100%",r.style.height="100%",this.modal.appendChild(r),document.body.prepend(this.modal),document.body.style.overflow="hidden",!r.contentWindow)throw new Error("iframe not yet ready.");r.contentWindow.document.open(),r.contentWindow.document.write(e.outerHTML),r.contentWindow.document.close(),this.listener=this.hideOnEscape.bind(this),document.addEventListener("keydown",this.listener)},hide:function(){this.modal.outerHTML="",this.modal=null,document.body.style.overflow="visible",document.removeEventListener("keydown",this.listener)},hideOnEscape:function(c){c.keyCode===27&&this.hide()}};function h(c,s){var e;return function(){var r=arguments,o=this;clearTimeout(e),e=setTimeout(function(){return c.apply(o,[].slice.call(r))},s)}}function t(c,s,e){for(var r in s===void 0&&(s=new FormData),e===void 0&&(e=null),c=c||{})Object.prototype.hasOwnProperty.call(c,r)&&E(s,g(e,r),c[r]);return s}function g(c,s){return c?c+"["+s+"]":s}function E(c,s,e){return Array.isArray(e)?Array.from(e.keys()).forEach(function(r){return E(c,g(s,r.toString()),e[r])}):e instanceof Date?c.append(s,e.toISOString()):e instanceof File?c.append(s,e,e.name):e instanceof Blob?c.append(s,e):typeof e=="boolean"?c.append(s,e?"1":"0"):typeof e=="string"?c.append(s,e):typeof e=="number"?c.append(s,""+e):e==null?c.append(s,""):void t(e,c,s)}function C(c){return new URL(c.toString(),window.location.toString())}function y(c,s,e,r){r===void 0&&(r="brackets");var o=/^https?:\/\//.test(s.toString()),m=o||s.toString().startsWith("/"),S=!m&&!s.toString().startsWith("#")&&!s.toString().startsWith("?"),q=s.toString().includes("?")||c===n.Method.GET&&Object.keys(e).length,O=s.toString().includes("#"),b=new URL(s.toString(),"http://localhost");return c===n.Method.GET&&Object.keys(e).length&&(b.search=a.stringify(u(a.parse(b.search,{ignoreQueryPrefix:!0}),e),{encodeValuesOnly:!0,arrayFormat:r}),e={}),[[o?b.protocol+"//"+b.host:"",m?b.pathname:"",S?b.pathname.substring(1):"",q?b.search:"",O?b.hash:""].join(""),e]}function p(c){return(c=new URL(c.href)).hash="",c}function P(c,s){return document.dispatchEvent(new CustomEvent("inertia:"+c,s))}(i=n.Method||(n.Method={})).GET="get",i.POST="post",i.PUT="put",i.PATCH="patch",i.DELETE="delete";var w=function(c){return P("finish",{detail:{visit:c}})},U=function(c){return P("navigate",{detail:{page:c}})},D=typeof window>"u",N=function(){function c(){this.visitId=null}var s=c.prototype;return s.init=function(e){var r=e.resolveComponent,o=e.swapComponent;this.page=e.initialPage,this.resolveComponent=r,this.swapComponent=o,this.isBackForwardVisit()?this.handleBackForwardVisit(this.page):this.isLocationVisit()?this.handleLocationVisit(this.page):this.handleInitialPageVisit(this.page),this.setupEventListeners()},s.handleInitialPageVisit=function(e){this.page.url+=window.location.hash,this.setPage(e,{preserveState:!0}).then(function(){return U(e)})},s.setupEventListeners=function(){window.addEventListener("popstate",this.handlePopstateEvent.bind(this)),document.addEventListener("scroll",h(this.handleScrollEvent.bind(this),100),!0)},s.scrollRegions=function(){return document.querySelectorAll("[scroll-region]")},s.handleScrollEvent=function(e){typeof e.target.hasAttribute=="function"&&e.target.hasAttribute("scroll-region")&&this.saveScrollPositions()},s.saveScrollPositions=function(){this.replaceState(l({},this.page,{scrollRegions:Array.from(this.scrollRegions()).map(function(e){return{top:e.scrollTop,left:e.scrollLeft}})}))},s.resetScrollPositions=function(){var e;window.scrollTo(0,0),this.scrollRegions().forEach(function(r){typeof r.scrollTo=="function"?r.scrollTo(0,0):(r.scrollTop=0,r.scrollLeft=0)}),this.saveScrollPositions(),window.location.hash&&((e=document.getElementById(window.location.hash.slice(1)))==null||e.scrollIntoView())},s.restoreScrollPositions=function(){var e=this;this.page.scrollRegions&&this.scrollRegions().forEach(function(r,o){var m=e.page.scrollRegions[o];m&&(typeof r.scrollTo=="function"?r.scrollTo(m.left,m.top):(r.scrollTop=m.top,r.scrollLeft=m.left))})},s.isBackForwardVisit=function(){return window.history.state&&window.performance&&window.performance.getEntriesByType("navigation").length>0&&window.performance.getEntriesByType("navigation")[0].type==="back_forward"},s.handleBackForwardVisit=function(e){var r=this;window.history.state.version=e.version,this.setPage(window.history.state,{preserveScroll:!0,preserveState:!0}).then(function(){r.restoreScrollPositions(),U(e)})},s.locationVisit=function(e,r){try{window.sessionStorage.setItem("inertiaLocationVisit",JSON.stringify({preserveScroll:r})),window.location.href=e.href,p(window.location).href===p(e).href&&window.location.reload()}catch{return!1}},s.isLocationVisit=function(){try{return window.sessionStorage.getItem("inertiaLocationVisit")!==null}catch{return!1}},s.handleLocationVisit=function(e){var r,o,m,S,q=this,O=JSON.parse(window.sessionStorage.getItem("inertiaLocationVisit")||"");window.sessionStorage.removeItem("inertiaLocationVisit"),e.url+=window.location.hash,e.rememberedState=(r=(o=window.history.state)==null?void 0:o.rememberedState)!=null?r:{},e.scrollRegions=(m=(S=window.history.state)==null?void 0:S.scrollRegions)!=null?m:[],this.setPage(e,{preserveScroll:O.preserveScroll,preserveState:!0}).then(function(){O.preserveScroll&&q.restoreScrollPositions(),U(e)})},s.isLocationVisitResponse=function(e){return e&&e.status===409&&e.headers["x-inertia-location"]},s.isInertiaResponse=function(e){return e==null?void 0:e.headers["x-inertia"]},s.createVisitId=function(){return this.visitId={},this.visitId},s.cancelVisit=function(e,r){var o=r.cancelled,m=o!==void 0&&o,S=r.interrupted,q=S!==void 0&&S;!e||e.completed||e.cancelled||e.interrupted||(e.cancelToken.cancel(),e.onCancel(),e.completed=!1,e.cancelled=m,e.interrupted=q,w(e),e.onFinish(e))},s.finishVisit=function(e){e.cancelled||e.interrupted||(e.completed=!0,e.cancelled=!1,e.interrupted=!1,w(e),e.onFinish(e))},s.resolvePreserveOption=function(e,r){return typeof e=="function"?e(r):e==="errors"?Object.keys(r.props.errors||{}).length>0:e},s.visit=function(e,r){var o=this,m=r===void 0?{}:r,S=m.method,q=S===void 0?n.Method.GET:S,O=m.data,b=O===void 0?{}:O,x=m.replace,j=x!==void 0&&x,F=m.preserveScroll,k=F!==void 0&&F,J=m.preserveState,_=J!==void 0&&J,qe=m.only,W=qe===void 0?[]:qe,Oe=m.headers,Pe=Oe===void 0?{}:Oe,Te=m.errorBag,M=Te===void 0?"":Te,Ae=m.forceFormData,Le=Ae!==void 0&&Ae,xe=m.onCancelToken,Ie=xe===void 0?function(){}:xe,Ne=m.onBefore,Ue=Ne===void 0?function(){}:Ne,De=m.onStart,Be=De===void 0?function(){}:De,je=m.onProgress,ke=je===void 0?function(){}:je,Me=m.onFinish,xt=Me===void 0?function(){}:Me,Ve=m.onCancel,It=Ve===void 0?function(){}:Ve,Fe=m.onSuccess,He=Fe===void 0?function(){}:Fe,Je=m.onError,_e=Je===void 0?function(){}:Je,We=m.queryStringArrayFormat,z=We===void 0?"brackets":We,V=typeof e=="string"?C(e):e;if(!function R(T){return T instanceof File||T instanceof Blob||T instanceof FileList&&T.length>0||T instanceof FormData&&Array.from(T.values()).some(function(A){return R(A)})||typeof T=="object"&&T!==null&&Object.values(T).some(function(A){return R(A)})}(b)&&!Le||b instanceof FormData||(b=t(b)),!(b instanceof FormData)){var Xe=y(q,V,b,z),Nt=Xe[1];V=C(Xe[0]),b=Nt}var H={url:V,method:q,data:b,replace:j,preserveScroll:k,preserveState:_,only:W,headers:Pe,errorBag:M,forceFormData:Le,queryStringArrayFormat:z,cancelled:!1,completed:!1,interrupted:!1};if(Ue(H)!==!1&&function(R){return P("before",{cancelable:!0,detail:{visit:R}})}(H)){this.activeVisit&&this.cancelVisit(this.activeVisit,{interrupted:!0}),this.saveScrollPositions();var Ke=this.createVisitId();this.activeVisit=l({},H,{onCancelToken:Ie,onBefore:Ue,onStart:Be,onProgress:ke,onFinish:xt,onCancel:It,onSuccess:He,onError:_e,queryStringArrayFormat:z,cancelToken:d.CancelToken.source()}),Ie({cancel:function(){o.activeVisit&&o.cancelVisit(o.activeVisit,{cancelled:!0})}}),function(R){P("start",{detail:{visit:R}})}(H),Be(H),d({method:q,url:p(V).href,data:q===n.Method.GET?{}:b,params:q===n.Method.GET?b:{},cancelToken:this.activeVisit.cancelToken.token,headers:l({},Pe,{Accept:"text/html, application/xhtml+xml","X-Requested-With":"XMLHttpRequest","X-Inertia":!0},W.length?{"X-Inertia-Partial-Component":this.page.component,"X-Inertia-Partial-Data":W.join(",")}:{},M&&M.length?{"X-Inertia-Error-Bag":M}:{},this.page.version?{"X-Inertia-Version":this.page.version}:{}),onUploadProgress:function(R){b instanceof FormData&&(R.percentage=Math.round(R.loaded/R.total*100),function(T){P("progress",{detail:{progress:T}})}(R),ke(R))}}).then(function(R){var T;if(!o.isInertiaResponse(R))return Promise.reject({response:R});var A=R.data;W.length&&A.component===o.page.component&&(A.props=l({},o.page.props,A.props)),k=o.resolvePreserveOption(k,A),(_=o.resolvePreserveOption(_,A))&&(T=window.history.state)!=null&&T.rememberedState&&A.component===o.page.component&&(A.rememberedState=window.history.state.rememberedState);var $=V,X=C(A.url);return $.hash&&!X.hash&&p($).href===X.href&&(X.hash=$.hash,A.url=X.href),o.setPage(A,{visitId:Ke,replace:j,preserveScroll:k,preserveState:_})}).then(function(){var R=o.page.props.errors||{};if(Object.keys(R).length>0){var T=M?R[M]?R[M]:{}:R;return function(A){P("error",{detail:{errors:A}})}(T),_e(T)}return P("success",{detail:{page:o.page}}),He(o.page)}).catch(function(R){if(o.isInertiaResponse(R.response))return o.setPage(R.response.data,{visitId:Ke});if(o.isLocationVisitResponse(R.response)){var T=C(R.response.headers["x-inertia-location"]),A=V;A.hash&&!T.hash&&p(A).href===T.href&&(T.hash=A.hash),o.locationVisit(T,k===!0)}else{if(!R.response)return Promise.reject(R);P("invalid",{cancelable:!0,detail:{response:R.response}})&&v.show(R.response.data)}}).then(function(){o.activeVisit&&o.finishVisit(o.activeVisit)}).catch(function(R){if(!d.isCancel(R)){var T=P("exception",{cancelable:!0,detail:{exception:R}});if(o.activeVisit&&o.finishVisit(o.activeVisit),T)return Promise.reject(R)}})}},s.setPage=function(e,r){var o=this,m=r===void 0?{}:r,S=m.visitId,q=S===void 0?this.createVisitId():S,O=m.replace,b=O!==void 0&&O,x=m.preserveScroll,j=x!==void 0&&x,F=m.preserveState,k=F!==void 0&&F;return Promise.resolve(this.resolveComponent(e.component)).then(function(J){q===o.visitId&&(e.scrollRegions=e.scrollRegions||[],e.rememberedState=e.rememberedState||{},(b=b||C(e.url).href===window.location.href)?o.replaceState(e):o.pushState(e),o.swapComponent({component:J,page:e,preserveState:k}).then(function(){j||o.resetScrollPositions(),b||U(e)}))})},s.pushState=function(e){this.page=e,window.history.pushState(e,"",e.url)},s.replaceState=function(e){this.page=e,window.history.replaceState(e,"",e.url)},s.handlePopstateEvent=function(e){var r=this;if(e.state!==null){var o=e.state,m=this.createVisitId();Promise.resolve(this.resolveComponent(o.component)).then(function(q){m===r.visitId&&(r.page=o,r.swapComponent({component:q,page:o,preserveState:!1}).then(function(){r.restoreScrollPositions(),U(o)}))})}else{var S=C(this.page.url);S.hash=window.location.hash,this.replaceState(l({},this.page,{url:S.href})),this.resetScrollPositions()}},s.get=function(e,r,o){return r===void 0&&(r={}),o===void 0&&(o={}),this.visit(e,l({},o,{method:n.Method.GET,data:r}))},s.reload=function(e){return e===void 0&&(e={}),this.visit(window.location.href,l({},e,{preserveScroll:!0,preserveState:!0}))},s.replace=function(e,r){var o;return r===void 0&&(r={}),console.warn("Inertia.replace() has been deprecated and will be removed in a future release. Please use Inertia."+((o=r.method)!=null?o:"get")+"() instead."),this.visit(e,l({preserveState:!0},r,{replace:!0}))},s.post=function(e,r,o){return r===void 0&&(r={}),o===void 0&&(o={}),this.visit(e,l({preserveState:!0},o,{method:n.Method.POST,data:r}))},s.put=function(e,r,o){return r===void 0&&(r={}),o===void 0&&(o={}),this.visit(e,l({preserveState:!0},o,{method:n.Method.PUT,data:r}))},s.patch=function(e,r,o){return r===void 0&&(r={}),o===void 0&&(o={}),this.visit(e,l({preserveState:!0},o,{method:n.Method.PATCH,data:r}))},s.delete=function(e,r){return r===void 0&&(r={}),this.visit(e,l({preserveState:!0},r,{method:n.Method.DELETE}))},s.remember=function(e,r){var o,m;r===void 0&&(r="default"),D||this.replaceState(l({},this.page,{rememberedState:l({},(o=this.page)==null?void 0:o.rememberedState,(m={},m[r]=e,m))}))},s.restore=function(e){var r,o;if(e===void 0&&(e="default"),!D)return(r=window.history.state)==null||(o=r.rememberedState)==null?void 0:o[e]},s.on=function(e,r){var o=function(m){var S=r(m);m.cancelable&&!m.defaultPrevented&&S===!1&&m.preventDefault()};return document.addEventListener("inertia:"+e,o),function(){return document.removeEventListener("inertia:"+e,o)}},c}(),L={buildDOMElement:function(c){var s=document.createElement("template");s.innerHTML=c;var e=s.content.firstChild;if(!c.startsWith("<script "))return e;var r=document.createElement("script");return r.innerHTML=e.innerHTML,e.getAttributeNames().forEach(function(o){r.setAttribute(o,e.getAttribute(o)||"")}),r},isInertiaManagedElement:function(c){return c.nodeType===Node.ELEMENT_NODE&&c.getAttribute("inertia")!==null},findMatchingElementIndex:function(c,s){var e=c.getAttribute("inertia");return e!==null?s.findIndex(function(r){return r.getAttribute("inertia")===e}):-1},update:h(function(c){var s=this,e=c.map(function(r){return s.buildDOMElement(r)});Array.from(document.head.childNodes).filter(function(r){return s.isInertiaManagedElement(r)}).forEach(function(r){var o=s.findMatchingElementIndex(r,e);if(o!==-1){var m,S=e.splice(o,1)[0];S&&!r.isEqualNode(S)&&(r==null||(m=r.parentNode)==null||m.replaceChild(S,r))}else{var q;r==null||(q=r.parentNode)==null||q.removeChild(r)}}),e.forEach(function(r){return document.head.appendChild(r)})},1)},B=new N;n.Inertia=B,n.createHeadManager=function(c,s,e){var r={},o=0;function m(){var q=Object.values(r).reduce(function(O,b){return O.concat(b)},[]).reduce(function(O,b){if(b.indexOf("<")===-1)return O;if(b.indexOf("<title ")===0){var x=b.match(/(<title [^>]+>)(.*?)(<\/title>)/);return O.title=x?""+x[1]+s(x[2])+x[3]:b,O}var j=b.match(/ inertia="[^"]+"/);return j?O[j[0]]=b:O[Object.keys(O).length]=b,O},{});return Object.values(q)}function S(){c?e(m()):L.update(m())}return{createProvider:function(){var q=function(){var O=o+=1;return r[O]=[],O.toString()}();return{update:function(O){return function(b,x){x===void 0&&(x=[]),b!==null&&Object.keys(r).indexOf(b)>-1&&(r[b]=x),S()}(q,O)},disconnect:function(){return function(O){O!==null&&Object.keys(r).indexOf(O)!==-1&&(delete r[O],S())}(q)}}}}},n.hrefToUrl=C,n.mergeDataIntoQueryString=y,n.shouldIntercept=function(c){var s=c.currentTarget.tagName.toLowerCase()==="a";return!(c.target&&c!=null&&c.target.isContentEditable||c.defaultPrevented||s&&c.which>1||s&&c.altKey||s&&c.ctrlKey||s&&c.metaKey||s&&c.shiftKey)},n.urlWithoutHash=p}(G)),G}var ir=rr();export{ir as d};
