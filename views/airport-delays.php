<style>


.line {
  fill: none;
  stroke: steelblue;
  stroke-width: 2px;
}

.airportLineTooltip {
  position: absolute;
  display: none;
  pointer-events: none;
  background: #fff;
  padding: 5px;
  text-align: left;
  border: solid #ccc 1px;
  color: #666;
  font-size: 14px;
  font-family: sans-serif;
}
.airport {
  stroke-width: 12px;
}

</style>
<svg id="delayChart" width="960" height="500"></svg>
<div id="ap"></div>
<script>


function transitionRGB(percentage){
  percentage = percentage / 12;
  var g = ["#cce6ff", "#b3d9ff", "#99ccff", "#80bfff", "#66b3ff", "#4da6ff","#3399ff","#1a8cff","#0073e6","#004d99","#001a33"];
  var result = Math.round(percentage * percentage * 10);
  if(result > g.length - 1){
    result = g.length - 1;
  }
  return g[result];

}

function calculateDelays(data){
    var origins = {};
    var delays = [];
    var airport_names = {};
    if(data[0].hasOwnProperty('carrier')){
      carrier = true;  
    } else{
      carrier = false;
    }
    
  for(var i = 0; i < data.length; i++){
    var d = data[i];

    if(!(d.origin_city_name in origins)) {
      origins[d.origin_city_name] = [{
        delay: parseFloat(d.delay),
        date: parseDate(d.fl_date)
      }];
      airport_names[d.origin_city_name] = d.origin;
    } else{
      origins[d.origin_city_name].push({
        delay: parseFloat(d.delay),
        date: parseDate(d.fl_date)
      });
    }
  }

  for(var o in origins){
    var total = 0;
    origins[o].map(function(x){
      total += x.delay;
    });
    var f = {
      id: o,
      values: origins[o],
      average : total / origins[o].length,
      airport : airport_names[o]
    };
    if(carrier){f.carrier = true};
    delays.push(f);
  }
  return delays;
}


function parseDate(d){
  var dateStringToList = d.split("-");
  return new Date(dateStringToList[0], dateStringToList[1] - 1, dateStringToList[2]);
}

var delayChart = function(){
  CLICKED_AIRPORT = undefined;
  var svg = d3.select("#delayChart");
  var margin = {top: 20, right: 80, bottom: 30, left: 50};
  var width = svg.attr("width") - margin.left - margin.right;
  var height = svg.attr("height") - margin.top - margin.bottom;
  var g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  var airportTooltip = d3.select("#ap").append("div").attr("class", "airportLineTooltip");

  var x = d3.time.scale().range([0, width]);
  var y = d3.scale.linear().range([height, 0]);

  var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

  var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left")
    .ticks(10);


  var line = d3.svg.line()
    .interpolate("basis")
    .x(function(d) { return x(d.date); })
    .y(function(d) { return y(d.delay); });
    
  drawData = function(data){
    g.selectAll('.city').remove();
    g.selectAll('.axis').remove();

    var delays = calculateDelays(data);

    x.domain(d3.extent(data, function(d) {return parseDate(d.fl_date); }));

    y.domain([
      d3.min(delays, function(c) { return d3.min(c.values, function(d) { return d.delay; }); }),
      d3.max(delays, function(c) { return d3.max(c.values, function(d) { return d.delay; }); })
    ]);

    g.append("g")
        .attr("class", "axis axis--x")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    g.append("g")
        .attr("class", "axis axis--y")
        .call(yAxis)
      .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 10)
        .attr("dy", "20px")
        .attr("dx", "-200px")
        .attr("fill", "#000")
        .text("Average departure delays, min");

    airport = g.selectAll(".city")
      .data(delays)
      .enter().append("g")
      .attr("class", "city");

    airport.append("path")
      .attr("class", "line")
      .attr("d", function(d) { return line(d.values); })
      .style("stroke", function(d) { return transitionRGB(d.average);})
      .on("mouseover", function(d){
        airportTooltip.text(d.id)
          .style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px")
          .style("display", "block")
          .style("opacity", 1);

        d3.select(this).style("stroke","#4da6ff").style("stroke-width","6px");

        d3.selectAll("circle").each(function(){
          var f = d3.select(this);
          if(f.datum().airport == d.airport){
            f.attr("fill",transitionRGB(d.average / 100)).attr("stroke","blue");
          }
        });

        if(d.hasOwnProperty('carrier')){
          ci.globeCarrierHovered(d.airport, CLICKED_AIRPORT);
        }
      }).on("mouseout",function(d){
        d3.select(this).style("stroke", function(d) { return transitionRGB(d.average);}).style("stroke-width","2px");;
        d3.selectAll("circle").each(function(){
          var f = d3.select(this);
          if(f.datum().airport == d.airport){
            f.attr("stroke",null);
          }
        });

        if(d.hasOwnProperty('carrier')){
          ci.globeCarrierCancelled(d.airport);
        }
      }).on("click",function(d){
        CLICKED_AIRPORT = d.airport;
        d3.json("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=compute-flight-delays-by-airline&a="+d.airport, function(error, data) {
          this.drawData(data);
        });
      });

    airport.append("text")
      .datum(function(d) { return {id: d.id, value: d.values[d.values.length - 1]}; })
      .attr("transform", function(d) {return "translate(" + x(d.value.date) + "," + y(d.value.delay) + ")"; })
      .attr("x", 3)
      .attr("dy", "0.35em")
      .style("font", "10px sans-serif")
      .text(function(d) { return d.id; });
  }

  d3.json("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=compute-flight-delays-for-all-airports", function(error, data) {
    if (error) throw error;
    drawData(data);
  });
}

delayChart.prototype.airportsUpdated = function(airports, prop){
  airports_str = airports.map(function(x){return x[prop]}).join(",");

  d3.json("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=compute-flight-delays-airports&a="+airports_str, function(error, data) {
    this.drawData(data);
  });
}

delayChart.prototype.globeAirportHovered = function(airport){
  d3.selectAll('.line').each(function(d){
    if(d.airport == airport.airport){
      d3.select(this).style("stroke","#4da6ff").style("stroke-width","6px");
    }
  });
}

delayChart.prototype.globeAirportHoverCancelled = function(airport){
  d3.selectAll('.line').each(function(d){
    if(d.airport == airport.airport){
      d3.select(this).style("stroke", function(d) { return transitionRGB(d.average);}).style("stroke-width","2px");;
    }
  });
}

var dc = new delayChart();
</script>