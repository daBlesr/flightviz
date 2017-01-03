<!DOCTYPE html>
<meta charset="utf-8">
<style>

.bar {
  fill: steelblue;
}

.bar:hover {
  fill: brown;
}

.axis--x path {
  display: none;
}

</style>
<svg id="barchart" width="960" height="500"></svg>
<script>

window.addEventListener('load',function(){
  var svg = d3.select("#barchart"),
      margin = {top: 20, right: 20, bottom: 30, left: 40},
      width = +svg.attr("width") - margin.left - margin.right,
      height = 450;

  var x = d3.scale.ordinal().rangeRoundBands([0, width],0.1),
      y = d3.scale.linear().range([height, 0]);

  var g = svg.append("g")
      .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  d3.tsv("<?php echo $_GLOBALS['BASE_URL'];?>/assets/data.tsv", function(d) {
    d.frequency = +d.frequency;
    return d;
  }, function(error, data) {
    if (error) throw error;

    x.domain(data.map(function(d) { return d.letter; }));

    var xAxis = d3.svg.axis()
      .scale(x)
      .orient("bottom");

    var yAxis = d3.svg.axis()
      .scale(y)
      .orient("left")
      .ticks(10);


    y.domain([0, d3.max(data, function(d) { return d.frequency; })]);

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
        .attr("text-anchor", "end")
        .text("Frequency");

    g.selectAll(".bar")
      .data(data)
      .enter().append("rect")
        .attr("class", "bar")
        .attr("x", function(d) { return x(d.letter); })
        .attr("y", function(d) { return y(d.frequency); })
        .attr("width", x.rangeBand())
        .attr("height", function( d ) { return height - y(d.frequency); });
  });
});


</script>
