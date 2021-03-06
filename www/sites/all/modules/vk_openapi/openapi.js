// $Id: openapi.js,v 1.14 2010/10/06 19:35:51 romka Exp $
var
  vk_members_data = {},
  lastCommentsResponse,
  lastCommentsPage = null,
  baseURL = window.location.protocol + '//' + window.location.hostname + '/';

function array_unique(ar){
  if (ar.length && typeof ar !== 'string') {
    var sorter = {};
    var out = [];
    for (var i=0, j=ar.length; i<j; i++) {
      if(!sorter[ar[i]+typeof ar[i]]){
        out.push(ar[i]);
        sorter[ar[i]+typeof ar[i]]=true;
      }
    }
  }
  return out || ar;
}

function doLogin() {
  VK.Auth.login(getInitData);
}

function doUnite() {
  VK.Auth.login(uniteGetInitData);
}

/*
function doLogout() {
  VK.Auth.logout(logoutOpenAPI);
}
*/
function loginOpenAPI() {
  getInitData();
}

function logoutOpenAPI() {
  window.location = baseURL;
}

function getInitData() {
  var code;
  code = 'return {';
  code += 'me: API.getProfiles({uids: API.getVariable({key: 1280})})[0]';
  /*
    ,sex,bdate,city,country,timezone,photo,photo_medium,photo_big,has_mobile,rate,contacts,education,home_phone,mobile_phone,university,university_name,faculty,faculty_name,graduation
  */
  code += '};';
  VK.Api.call('execute', {'code': code}, onGetInitData);
}

function uniteGetInitData() {
  var code;
  code = 'return {';
  code += 'me: API.getProfiles({uids: API.getVariable({key: 1280})})[0]';
  /*
    ,sex,bdate,city,country,timezone,photo,photo_medium,photo_big,has_mobile,rate,contacts,education,home_phone,mobile_phone,university,university_name,faculty,faculty_name,graduation
  */
  code += '};';
  VK.Api.call('execute', {'code': code}, uniteOnGetInitData);
}


function onGetInitData(data) {
  var r, i, j, html;
  if (data.response) {
    r = data.response;
    /* Insert user info */
    if (r.me) {
      $.ajax({
        cache: false,
        data: {uid: r.me.uid, first_name: r.me.first_name, last_name: r.me.last_name, nickname: r.me.nickname},
        dataType: 'json',
        success: function(data) { succesLogin(data); },
        error: function(data) { errorLogin(data); },
        type: 'POST',
		    url: Drupal.settings.basePath + 'vk/login'
      });
    }    
  } else {
    window.location = baseURL + 'vk/login/error';
  }
}

function uniteOnGetInitData(data) {
  var r, i, j, html;
  if (data.response) {
    r = data.response;
    /* Insert user info */
    if (r.me) {
      $.ajax({
        cache: false,
        data: {uid: r.me.uid, first_name: r.me.first_name, last_name: r.me.last_name, nickname: r.me.nickname},
        dataType: 'json',
        success: function(data) { succesLogin(data); },
        error: function(data) { errorLogin(data); },
        type: 'POST',
		    url: Drupal.settings.basePath + 'vk/succesfull-unite'
      });
    }    
  } else {
    window.location = baseURL + 'vk/error-unite';
  }
}

function succesLogin(data) {
	if(data.error) {
		alert(data.message);
	}
	window.location = baseURL + data.redirect_url;
}

function errorLogin(data) {
	if(data.error) {
		alert(data.message);
	}
	alert('Error connecting to server.');
	//window.location = baseURL + data.redirect_url;
}

/*
function onGetStatus(data) {
  alert('try to set status');
  if (data.response) {
    r = data.response;
    alert(r.me.activity);
  }
}
*/