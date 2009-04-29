/*
Script Name: DFE News Harvester JS functions
Description: Provides AJAX functions to the Harvester UI
Author: Tom Belknap
Author URI: http://holisticnetworking.net/
*/ 

// loadEvents: easy, cheesy.  Throws the right functions onto the right elements at window load.
function dfenhLoadEvents() {
	var a = document.getElementsByClassName('select');
	if (typeof(a) != 'undefined') {
		for(x = 0; x < a.length; x++) {
			addEvent(a[x], 'click', function() { dfenhOption2(this); });
		}
	}
	var b = document.getElementsByClassName('feature');
	if (typeof(b) != 'undefined') {
		for(y = 0; y < b.length; y++) {
			addEvent(b[y], 'click', function() { dfenhOption3(this); });
		}
	}
	var editbtnz = document.getElementsByClassName('editfeed');
	if (typeof(editbtnz) != 'undefined') {
		for(z = 0; z < editbtnz.length; z++) {
			addEvent(editbtnz[z], 'click', function() { dfenhEditFeed(this); });
		}
	}
	var deletebtnz = document.getElementsByClassName('deletefeed');
	if (typeof(deletebtnz) != 'undefined') {
		for(q = 0; q < deletebtnz.length; q++) {
			addEvent(deletebtnz[q], 'click', function() { return confirmButton('Are you sure you want to delete this feed?'); });
		}
	}
	var editsubmitbtnz = document.getElementsByClassName('editfeedsubmit');
	if (typeof(editsubmitbtnz) != 'undefined') {
		for(i = 0; i < editsubmitbtnz.length; i++) {
			addEvent(editsubmitbtnz[i], 'click', function() { return confirmButton('Are you sure you want to edit this feed?'); });
		}
	}
}

function dfenhOption2(target) {
	var info = document.getElementById(target.value + '_info');
	// Hiding and unhiding the second level of options when the current item is selected
	var option2 = info.getElementsByClassName('option2');
	for(var q = 0; q < option2.length; q++) {
		if (option2[q].style.display == 'block') {
			option2[q].style.display = 'none';
		} else {
			option2[q].style.display = 'block';
		}
		// Enabling and disabling second level inputs so we don't send unnecessary data
		inputs = option2[q].getElementsByTagName('input');
		for(var i = 0; i < inputs.length; i++) {
			if (inputs[i].disabled==false) {
				inputs[i].disabled=true;
			} else {
				inputs[i].disabled=false;
			}
		}
	}
	// Converting article link to a text field
	test = document.getElementById(target.value + '_dfenh_article-title');
	link = info.getElementsByClassName('dfenh_title');
	artTitle = link[0].getAttribute('title');
	if (test) {
		theParent = test.parentNode;
		if (theParent.style.display == 'block') {
			theParent.style.display = 'none';
			link[0].innerHTML = artTitle;
		} else {
			theParent.style.display = 'block';
			link[0].innerHTML = '(Link)&nbsp;';
		}
	} else {
		// Create the new field
		newField = document.createElement('input');
		newField.type = 'text';
		newField.name = target.value + '_dfenh_article-title';
		newField.id = target.value + '_dfenh_article-title';
		newField.value = artTitle;
		newField.size = '40';
		// Create a new label for the field:
		newLabel = document.createElement('label');
		newLabel.setAttribute('for', target.value + '_dfenh_article-title');
		newLabel.innerHTML = 'Primary Title:';
		// Create new 'dfenh_blocks' span for our new field/label
		dfenhBlck = document.createElement('span');
		dfenhBlck.setAttribute('class', 'dfenh_blocks');
		dfenhBlck.style.display = 'block';
		option1 = info.getElementsByClassName('option1');
		// Now, change the link to say "Link" and plug in our new field:
		link[0].innerHTML = '(Link)&nbsp;';
		option1[0].appendChild(dfenhBlck);
		dfenhBlck.appendChild(newLabel);
		dfenhBlck.appendChild(newField);
	}
}

function dfenhOption3(target) {
	var info = document.getElementById(target.id + '_info');
	// Hiding and unhiding the third level of options when the current item is selected
	var option3 = info.getElementsByClassName('option3');
	if (option3[0].style.display == 'block') {
		option3[0].style.display = 'none';
	} else {
		option3[0].style.display = 'block';
	}
	// Enabling and disabling third level inputs so we don't send unnecessary data
	inputs = option3[0].getElementsByTagName('input');
	for(var i = 0; i < inputs.length; i++) {
		if (inputs[i].disabled==false) {
			inputs[i].disabled=true;
		} else {
			inputs[i].disabled=false;
		}
	}
	textarea = option3[0].getElementsByTagName('textarea');
	if (textarea[0].disabled==false) {
		textarea[0].disabled=true;
	} else {
		textarea[0].disabled=false;
	}
}

function dfenhEditFeed(button) {
	var feed_row = button.parentNode.parentNode;
	var feed_id = feed_row.id;
	var feed_url_row = document.getElementById(feed_id+'_url');
	// Is the edit section open?
	if(feed_url_row.style.display == 'none') {
		feed_url_row.style.display = '';
		button.value = 'Cancel';
		// Hide static, reveal inputs:
		var static = feed_row.getElementsByTagName('P');
		for(g = 0; g<static.length; g++) {
			static[g].style.display='none';
		}
		var edit = feed_row.getElementsByTagName('INPUT');
		for(h = 0; h<edit.length; h++) {
			if(edit[h].type == 'text') {
				edit[h].style.display='inline';
				edit[h].disabled=false;
			}
		}
		var sel = feed_row.getElementsByTagName('SELECT');
		for(s = 0; s<sel.length; s++) {
			sel[s].style.display='inline';
			sel[s].disabled=false;
		}
	} else {
		feed_url_row.style.display = 'none';
		button.value = 'Edit';
		// Hide inputs, reveal static:
		var edit = feed_row.getElementsByTagName('INPUT');
		for(g = 0; g<edit.length; g++) {
			if(edit[g].type == 'text') {
				edit[g].style.display='none';
				edit[g].disabled=true;
			}
		}
		var sel = feed_row.getElementsByTagName('SELECT');
		for(s = 0; s<sel.length; s++) {
			sel[s].style.display='none';
			sel[s].disabled=true;
		}
		var static = feed_row.getElementsByTagName('P');
		for(h = 0; h<static.length; h++) {
			static[h].style.display='inline';
		}
	}
}


// confirmButton: simple function to test user response
function confirmButton(message) {
	var conf = confirm(message);
	if(conf) {
		return true;
	} else {
		return false;
	}
}


// addEvent: allows for unobtrusive JS.  Can be called to insert functions onto page where required.
if (typeof(addEvent) != 'function') {
	function addEvent(elm, evType, fn, useCapture) {
	    elm["on"+evType]=fn;return;
	}
}

// getElementsByClassName: extends and unifies this poorly-implemented function
document.getElementsByClassName = function(clsName){
	var retVal = new Array();
	var elements = document.getElementsByTagName("*");
	for(var i = 0;i < elements.length;i++){
		if(elements[i].className.indexOf(" ") >= 0){
			var classes = elements[i].className.split(" ");
			for(var j = 0;j < classes.length;j++){
				if(classes[j] == clsName)
					retVal.push(elements[i]);
			}
		}
		else if(elements[i].className == clsName)
			retVal.push(elements[i]);
	}
	return retVal;
}

addEvent(window, 'load', dfenhLoadEvents);