<style>
.axis--x path {
  display: none;
}

.line {
  fill: none;
  stroke: steelblue;
  stroke-width: 1.5px;
}

</style>
<svg id="delayChart" width="960" height="500"></svg>
<script>

function delayChart(){
  var svg = d3.select("#delayChart"),
    margin = {top: 20, right: 80, bottom: 30, left: 50},
    width = svg.attr("width") - margin.left - margin.right,
    height = svg.attr("height") - margin.top - margin.bottom,
    g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  var parseTime = d3.time.format("%Y-%m-%d");

  var x = d3.time.scale().range([0, width]),
      y = d3.scale.linear().range([height, 0]),
      z = d3.scale.ordinal(d3.schemeCategory10);

  var xAxis = d3.svg.axis()
      .scale(x)
      .orient("bottom");

    var yAxis = d3.svg.axis()
      .scale(y)
      .orient("left")
      .ticks(10);


  var line = d3.svg.line()
     // .curve(d3.curveBasis)
      .x(function(d) { return x(d.date); })
      .y(function(d) { return y(d.delay); });

  function parseDate(d){
    var dateStringToList = d.split("-");
    return new Date(dateStringToList[2], dateStringToList[1] - 1, dateStringToList[0]);
  }

  d3.json("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=compute-flight-delays-for-all-airports", function(error, data) {
    if (error) throw error;

    var origins = {};
    var delays = [];
    
    for(var i = 0; i < data.length; i++){
      var d = data[i];

      if(!(d.origin_city_name in origins)) {
        origins[d.origin_city_name] = [{
          delay: parseFloat(d.delay),
          date: parseDate(d.fl_date)
        }];  
      } else{
        origins[d.origin_city_name].push({
          delay: parseFloat(d.delay),
          date: parseDate(d.fl_date)
        });
      }
    }

    for(var o in origins){
      delays.push({
        id: o,
        values: origins[o]
      });
    }

    console.log(delays);

    x.domain(d3.extent(data, function(d) {return parseDate(d.fl_date); }));

    y.domain([
      d3.min(delays, function(c) { return d3.min(c.values, function(d) { return d.delay; }); }),
      d3.max(delays, function(c) { return d3.max(c.values, function(d) { return d.delay; }); })
    ]);

    z.domain(delays.map(function(c) { return c.id; }));

    g.append("g")
        .attr("class", "axis axis--x")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    g.append("g")
        .attr("class", "axis axis--y")
        .call(yAxis)
      .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", "0.71em")
        .attr("fill", "#000")
        .text("Temperature, ºF");

    var airport = g.selectAll(".city")
      .data(delays)
      .enter().append("g")
        .attr("class", "city");

    airport.append("path")
        .attr("class", "line")
        .attr("d", function(d) { return line(d.values); })
        .style("stroke", function(d) { return z(d.id); });

    airport.append("text")
        .datum(function(d) { return {id: d.id, value: d.values[d.values.length - 1]}; })
        .attr("transform", function(d) {return "translate(" + x(d.value.date) + "," + y(d.value.delay) + ")"; })
        .attr("x", 3)
        .attr("dy", "0.35em")
        .style("font", "10px sans-serif")
        .text(function(d) { return d.id; });
  });
}

delayChart = delayChart();
</script>