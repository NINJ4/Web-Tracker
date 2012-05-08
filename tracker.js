var searchdata;
var fd;
$(document).ready(function(){
	
	$("#GULclear").click(function(e){
		e.preventDefault();
		$("#GULout").html("");
	});
	$(".GUL_gen").live( "click", function(e) {
		e.preventDefault();
		$("#GULloading").show();
		GULinitiate({ "target": $(this).attr("value") }, $(this).attr("value"));
	});
	$('#GULform').submit(function(e) {
		e.preventDefault();
		$("#GULloading").show();
		GULinitiate($("#GULform").serialize(), $("#GULtarget").val());
		$("#GULtarget").val("");
		$('#GULscroll').scrollTop($('#GULscroll'));
	});
	function GULinitiate(formdata, target) {
		$.post("tracker.php", formdata, function(result){
			$("#GULloading").hide();
			GULProcessOutput(result);
		}, "json");
	}
	function GULgeolocate(ip, target) {
		var location;
		$.getJSON("http://freegeoip.net/json/"+ ip +"?callback=?", function(json) {
			location = "<a href='http://maps.google.com/maps?q="+ json.city +" "+ json.region_name +" "+ json.country_code +"' target='_blank'>"+ json.city +", "+ json.region_name +" ("+ json.country_code +")</a>";  
			$("#GULgeo_"+target).html("This player's most recent IP address maps to approximately: "+ location +"<br />\n");
		});
	}
	function GULProcessOutput(result) {
		var report = "";
		if ( result.success ) {
			if ( result.wildcard ) 
				report = "No exact results for your search term, only partial match was <b>"+ result.target +"</b><br />\n";
			report += "<h3>Report for "+ result.target +"</h3>\n<p>";
			
			report += "<span id='GULgeo_"+ result.target +"'></span>";
			
			for ( ip in result.body ) {
				report += "&bull; Connected from <a "+
							"href='http://dnstools.com/?count=2&lookup=on&wwwhois=on&arin=on&checkp=on&portNum=80&ping=on&all=on&target="+
							ip +"&submit=Go!'>"+ ip +"</a> most recently at "+ result['body'][ip]['time'];
		
				report += ".<br />\n";
				
				if ( result['body'][ip]['total'] > 0 ) {
					report += "<span class='assoc_acct'>";
					for (var i = 0; i < result['body'][ip]['matches'].length; i++) {
						report += "&raquo; This IP is also associated with the account <a href='#newreport' class='GUL_gen' value='"+
									result['body'][ip]['matches'][i] +"'>"+ result['body'][ip]['matches'][i] +"</a><br />\n";
					}
					report += "</span>";
				}
			}
			report += "</p>";
			$("#GULout").prepend(report);
			
			GULgeolocate(result.lastip, result.target);
			searchdata = result.map;
			if ( fd != null ) {
				fd.loadJSON(searchdata);
				  fd.computeIncremental({
					iter: 60,
					property: 'end',
					onComplete: function(){
					  fd.animate({
						modes: ['linear'],
						transition: $jit.Trans.Elastic.easeOut,
						duration: 2500
					  });
					}
				  });
			}
			else {
				$("#playerweb").show();
				init();
			}
		}
		else {
			report = "<h3 style='color:red;'>"+ result.head +"</h3><br />\n";
			report += result.body +"<br /><hr>\n";
			$("#GULout").prepend(report);
		}
		//console.log(result);
	}
	function GULfetchdata(target){
		$('#GULscroll').scrollTop($('#GULscroll'));
		
		var targetIP = $('#GULlatestIP-' + target).text();
		$.getJSON("http://freegeoip.net/json/"+ targetIP +"?callback=?",
			function(json) {
				$('#GULgeo-'+ target).html("<a href='http://maps.google.com/maps?q="+ json.city +" "+ json.region_name +" "+ json.country_code +"' target='_blank'>&raquo;"+ json.city +", "+ json.region_name +" ("+ json.country_code +")</a>");  	
		  });
	}

});

function selectCode(a)
{
	// Get ID of code block
	var e = a.parentNode.parentNode.getElementsByTagName('CODE')[0];

	// Not IE
	if (window.getSelection)
	{
		var s = window.getSelection();
		// Safari
		if (s.setBaseAndExtent)
		{
			s.setBaseAndExtent(e, 0, e, e.innerText.length - 1);
		}
		// Firefox and Opera
		else
		{
			// workaround for bug # 42885
			if (window.opera && e.innerHTML.substring(e.innerHTML.length - 4) == '<BR>')
			{
				e.innerHTML = e.innerHTML + '&nbsp;';
			}

			var r = document.createRange();
			r.selectNodeContents(e);
			s.removeAllRanges();
			s.addRange(r);
		}
	}
	// Some older browsers
	else if (document.getSelection)
	{
		var s = document.getSelection();
		var r = document.createRange();
		r.selectNodeContents(e);
		s.removeAllRanges();
		s.addRange(r);
	}
	// IE
	else if (document.selection)
	{
		var r = document.body.createTextRange();
		r.moveToElementText(e);
		r.select();
	}
}


var labelType, useGradients, nativeTextSupport, animate;

(function() {
  var ua = navigator.userAgent,
      iStuff = ua.match(/iPhone/i) || ua.match(/iPad/i),
      typeOfCanvas = typeof HTMLCanvasElement,
      nativeCanvasSupport = (typeOfCanvas == 'object' || typeOfCanvas == 'function'),
      textSupport = nativeCanvasSupport 
        && (typeof document.createElement('canvas').getContext('2d').fillText == 'function');
  //I'm setting this based on the fact that ExCanvas provides text support for IE
  //and that as of today iPhone/iPad current text support is lame
  labelType = (!nativeCanvasSupport || (textSupport && !iStuff))? 'Native' : 'HTML';
  nativeTextSupport = labelType == 'Native';
  useGradients = nativeCanvasSupport;
  animate = !(iStuff || !nativeCanvasSupport);
})();

var Log = {
  elem: false,
  write: function(text){
    if (!this.elem) 
      this.elem = document.getElementById('log');
    this.elem.innerHTML = text;
    this.elem.style.left = (500 - this.elem.offsetWidth / 2) + 'px';
  }
};

function init(){
  // init ForceDirected
  fd = new $jit.ForceDirected({
    //id of the visualization container
    injectInto: 'playerweb',
    //Enable zooming and panning
    //by scrolling and DnD
    Navigation: {
      enable: true,
      //Enable panning events only if we're dragging the empty
      //canvas (and not a node).
      panning: 'avoid nodes',
      zooming: 50 //zoom speed. higher is more sensible
    },
    // Change node and edge styles such as
    // color and width.
    // These properties are also set per node
    // with dollar prefixed data-properties in the
    // JSON structure.
    Node: {
      overridable: true
    },
    Edge: {
      overridable: true,
      color: '#23A4FF',
      lineWidth: 0.4
    },
    //Native canvas text styling
    Label: {
      type: labelType, //Native or HTML
      size: 10,
      style: 'bold'
    },
    //Add Tips
    Tips: {
      enable: true,
      onShow: function(tip, node) {
        //count connections
        var count = 0;
        node.eachAdjacency(function() { count++; });
        //display node info in tooltip
        tip.innerHTML = "<div class=\"tip-title\">" + node.name + "</div>"
          + "<div class=\"tip-text\"><b>connections:</b> " + count + "</div>";
      }
    },
    // Add node events
    Events: {
      enable: true,
      type: 'Native',
      //Change cursor style when hovering a node
      onMouseEnter: function() {
        fd.canvas.getElement().style.cursor = 'move';
      },
      onMouseLeave: function() {
        fd.canvas.getElement().style.cursor = '';
      },
      //Update node positions when dragged
      onDragMove: function(node, eventInfo, e) {
          var pos = eventInfo.getPos();
          node.pos.setc(pos.x, pos.y);
          fd.plot();
      },
      //Implement the same handler for touchscreens
      onTouchMove: function(node, eventInfo, e) {
        $jit.util.event.stop(e); //stop default touchmove event
        this.onDragMove(node, eventInfo, e);
      },
      /*/Add also a click handler to nodes
      onClick: function(node) {
        if(!node) return;
        // Build the right column relations list.
        // This is done by traversing the clicked node connections.
        var html = "<h4>" + node.name + "</h4><b> connections:</b><ul><li>",
            list = [];
        node.eachAdjacency(function(adj){
          list.push(adj.nodeTo.name);
        });
        //append connections information
        $jit.id('inner-details').innerHTML = html + list.join("</li><li>") + "</li></ul>";
      }*/
    },
    //Number of iterations for the FD algorithm
    iterations: 200,
    //Edge length
    levelDistance: 130,
    // Add text to the labels. This method is only triggered
    // on label creation and only for DOM labels (not native canvas ones).
    onCreateLabel: function(domElement, node){
      domElement.innerHTML = node.name;
      var style = domElement.style;
      style.fontSize = "0.9em";
      style.color = "#111";
    },
    // Change node styles when DOM labels are placed
    // or moved.
    onPlaceLabel: function(domElement, node){
      var style = domElement.style;
      var left = parseInt(style.left);
      var top = parseInt(style.top);
      var w = domElement.offsetWidth;
      style.left = (left - w / 2) + 'px';
      style.top = (top + 10) + 'px';
      style.display = '';
    }
  });
  // load JSON data.
  fd.loadJSON(searchdata);
  // compute positions incrementally and animate.
  fd.computeIncremental({
    iter: 60,
    property: 'end',
    onComplete: function(){
      fd.animate({
        modes: ['linear'],
        transition: $jit.Trans.Elastic.easeOut,
        duration: 2500
      });
    }
  });
  // end
}
