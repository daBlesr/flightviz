
<svg id="carrierInfo" width="960" height="400"></svg>
<script>

/**
function calculateCarrierInfo(data){
  var carriers = [];
  var barchartdata = [];

  console.log(data);
    
  for(var i = 0; i < data.length; i++){
    var d = data[i];
    
    if(carriers.indexOf(d.name) == -1){
      carriers.push(d.name);
    }

    var found_array = barchartdata.filter(function( x ) {
      return x.airport == d.origin;
    });

    if(found_array.length > 0){
      found_array[0][d.name] = parseFloat(d.c);
      found_array[0].total += parseFloat(d.c);
    } else{
      var obj = {};
      obj.airport = d.origin;
      obj.total = parseFloat(d.c);
      obj[d.name] = parseFloat(d.c);
      barchartdata.push(obj);
    }
  }

  barchartdata.columns = carriers;
  return barchartdata;
}*/

function calculateCarrierInfo(data){
  var carriers = [];
  var barchartdata = [];
  var origins = [];

  console.log(data);
    
  for(var i = 0; i < data.length; i++){
    var d = data[i];
    
    if(carriers.indexOf(d.name) == -1){
      carriers.push(d.name);
      var carrier_array = [];
      carrier_array.carrier = d.name;
      barchartdata.push(carrier_array);
    }

    if(origins.indexOf(d.origin) == -1){
      origins.push(d.origin);
    }
  }

  for(var i = 0; i < barchartdata.length; i++){
    for(var j = 0; j < origins.length; j++){
      barchartdata[i].push({
        x: origins[j],
        y: 0,
      })
    }
  }

  for(var i = 0; i < data.length; i++){
    var d = data[i];
    for(var j = 0; j < barchartdata.length; j++){
      if(barchartdata[j].carrier == d.name){
        for(var m = 0; m < barchartdata[j].length; m++){
          if(barchartdata[j][m].x == d.origin){
            barchartdata[j][m].y = parseFloat(d.c);
          }
        }
      }
    }
  }

  barchartdata.columns = carriers;
  console.log(barchartdata);
  return barchartdata;
}

var Carrier = function(){
  var svg = d3.select("#carrierInfo");
  var margin = {top: 20, right: 20, bottom: 30, left: 40};
  var width = +svg.attr("width") - margin.left - margin.right;
  var height = +svg.attr("height") - margin.top - margin.bottom;
  var g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  var x = d3.scale.ordinal().rangeRoundBands([0, width], .05);

  var y = d3.scale.linear().rangeRound([height, 0]);

  var z = d3.scale.ordinal().range(["#98abc5", "#8a89a6", "#7b6888", "#6b486b", "#a05d56", "#d0743c", "#ff8c00"]);

  var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

  var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left")
    .ticks(null, "s");

  redrawBarChart = function(data){
    var data = calculateCarrierInfo(data);
    var keys = data.columns;
    console.log(keys);

    //data.sort(function(a, b) { return b.total - a.total; });
    var layers = d3.layout.stack()(data);
    console.log(layers);
    x.domain(layers[0].map(function(d) { return d.x; }));
    y.domain([0, d3.max(layers[layers.length - 1], function(d) {return d.y0 + d.y; })]).nice();
    z.domain(keys);

    g.append("g")
    .selectAll("g")
    .data(d3.layout.stack()(layers))
    .enter().append("g")
      .attr("fill", function(d) {return z(d.carrier); })
    .selectAll("rect")
    .data(function(d) { return d; })
    .enter().append("rect")
      .attr("x", function(d) {return x(d.x); })
      .attr("y", function(d) {return y(d.y + d.y0); })
      .attr("height", function(d) { return y(d.y0) - y(d.y + d.y0); })
      .attr("width", x.rangeBand());

    g.append("g")
      .attr("class", "axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis)
        .selectAll("text")  
        .style("text-anchor", "end")
        .attr("font-size", 11)
        .attr("dx", "-.8em")
        .attr("dy", "-.15em")
        .attr("transform", function(d) {
            return "rotate(-65)" 
            });

    g.append("g")
        .attr("class", "axis")
        .call(yAxis)
      .append("text")
        .attr("x", 2)
        .attr("y", y(y.ticks().pop()) + 0.5)
        .attr("dy", "0.32em")
        .attr("fill", "#000")
        .attr("font-weight", "bold")
        .attr("text-anchor", "start")
        .text("Population");

    var legend = g.append("g")
        .attr("font-family", "sans-serif")
        .attr("font-size", 10)
        .attr("text-anchor", "end")
      .selectAll("g")
      .data(keys.slice().reverse())
      .enter().append("g")
        .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });

    legend.append("rect")
        .attr("x", width - 19)
        .attr("width", 19)
        .attr("height", 19)
        .attr("fill", z);

    legend.append("text")
        .attr("x", width - 24)
        .attr("y", 9.5)
        .attr("dy", "0.32em")
        .text(function(d) { return d; });
  }

  d3.json("http://localhost/flightviz/controllers/query.php?q=compute-flight-carriers-for-all-airports", function(error, data) {
    redrawBarChart(data);
  });
}

var ci = new Carrier();

</script>