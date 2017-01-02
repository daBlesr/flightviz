<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Earth globe</title>
<script src="d3.v3.min.js"></script>
<script src="topojson.v1.min.js"></script>
<script src="queue.v1.min.js"></script>
<script src="jquery-3.1.1.min.js"></script>
</head>
<style type="text/css">

.water {
  fill: rgba(102, 204, 255,1);
}

.land {
  fill: rgb(255, 153, 102);
  stroke: #FFF;
  stroke-width: 0.7px;
}


select {
  position: absolute;
  top: 20px;
  left: 580px;
  border: solid #ccc 1px;
  padding: 3px;
  box-shadow: inset 1px 1px 2px #ddd8dc;
}

.airportTooltip {
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

</style>
<body>
  <svg id="svg" style="float:left;"></svg>
  <div id="analysis" style="float:left;width: 800px; height: 300px">
  <h1 id="airportName"></h1>

  </div>
  <script>
  window.addEventListener('load',function(){
    var width = 960,
    height = 960,
    sens = 0.15,
    scale0 = (width / 2 - 20),
    focused;

    //Setting projection

    var projection = d3.geo.orthographic()
    .scale(width / 2 - 20)
    .rotate([0, 0])
    .translate([width / 2, height / 2])
    .clipAngle(90)
    .precision(0.6);

    var path = d3.geo.path()
    .projection(projection);

    //SVG container

    var svg = d3.select("#svg")
    .attr("width", width)
    .attr("height", height);

    var traffic = [];

    //Adding water

    var water = svg.append("path")
    .datum({type: "Sphere"})
    .attr("class", "water")
    .attr("d", path);

    var airportTooltip = d3.select("body").append("div").attr("class", "airportTooltip"),
    airportList = d3.select("body").append("select").attr("name", "airports");

    var airports, flight_counts;


    var analysis = document.getElementById('analysis');

    queue()
    .defer(d3.json, "world-110m.json")
    .defer(d3.tsv, "world-country-names.tsv")
    .defer(d3.json, "query.php?q=airports&a=20")
    .await(loadFlightData);

    function loadFlightData(error, world, countryData, airports){
      $.post("query.php?q=flights-by-airport",{
          "airports": airports.map(function(airport) {
            return airport.airport;
          })
        }, 
        function( data ) {
          ready(error, world, countryData, airports, JSON.parse(data));
      });
    }

    //Main function

    function ready(error, world, countryData, airports, flights) {

      var airportById = {},
      countries = topojson.feature(world, world.objects.countries).features;

      //Adding countries to select

      airports.forEach(function(d) {
        airportById[d.airport] = d.airport;
        option = airportList.append("option");
        option.text(d.city + ": "+d.airport);
        option.property("value", d.airport);
      });


      var m = 0;
      for(var i = 0; i < airports.length; i++){
        if(airports[i].total_traffic > m) m = airports[i].total_traffic;
      }

      //Drawing countries on the globe

      function zoomed() {
        projection.scale(zoom.scale());
        redraw();
      }

      var zoom = d3.behavior.zoom()
        .translate([width / 2, height / 2])
        .scale(scale0)
        .scaleExtent([scale0, 8 * scale0])
        .on("zoom", zoomed);

      var globe = svg.selectAll("path")
        .call(d3.behavior.drag()
        .origin(function() { var r = projection.rotate(); return {x: r[0] / sens, y: -r[1] / sens}; })
        .on("drag", function() {
          var rotate = projection.rotate();
          projection.rotate([d3.event.x * sens, -d3.event.y * sens, rotate[2]]);
          redraw();
        }))
        .call(zoom);

      console.log(countries);

      var world = svg.selectAll("path.land")
      .data(countries)
      .enter().append("path")
      .attr("class", "land")
      .attr("d", path)

      .call(d3.behavior.drag()
        .origin(function() { var r = projection.rotate(); return {x: r[0] / sens, y: -r[1] / sens}; })
        .on("drag", function() {
          var rotate = projection.rotate();
          projection.rotate([d3.event.x * sens, -d3.event.y * sens, rotate[2]]);
          redraw();
        }))
      
      .call(zoom);

      //Country focus on option select

       d3.select("select").on("change", function() {
        var rotate = projection.rotate(),
        focusedAirport = selectedAirport(airports, this),
        p = [focusedAirport.longitude, focusedAirport.latitude];

      //Globe rotating

      (function transition() {
        d3.transition()
        .duration(2500)
        .tween("rotate", function() {
          var r = d3.interpolate(projection.rotate(), [-p[0], -p[1]]);
          return function(t) {
            projection.rotate(r(t));
            svg.selectAll("path").attr("d", path);
            redrawAirports();
          };
        })
        })();

        updateAirportSelection(focusedAirport);
      });

      var circles_svg = svg.selectAll("circle")
        .data(airports)
        .enter()
        .append("circle")
        .attr("class","airport")
        .attr("cx", function (d) { return projection([parseFloat(d.longitude),parseFloat(d.latitude)])[0]; })
        .attr("cy", function (d) { return projection([parseFloat(d.longitude),parseFloat(d.latitude)])[1]; })
        .attr("r", function (d) {return (parseFloat(10 * d.total_traffic / m ))+ "px"; })
        .on("mouseover", function(d) {
          airportTooltip.text((d.city + ": " + d.name).replace("Intl",""))
          .style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px")
          .style("display", "block")
          .style("opacity", 1);
          d3.select(this).attr({
            fill: "green",
            r: (parseFloat(10 * d.total_traffic / m )) * 2
          });
          updateAirportSelection(d, airports);
        })
        .on("mouseout", function(d) {
          airportTooltip.style("opacity", 0)
          .style("display", "none");
          d3.select(this).attr({
            fill: "black",
            r: (parseFloat(10 * d.total_traffic / m ))
          });
        })
        .on("mousemove", function(d) {
          airportTooltip.style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px");
        });

      function redrawAirports(){
        var arc = d3.geo.greatArc();
        var centerPos = projection.invert([width/2,height/2]);
        
        svg.selectAll("circle")
          .attr("cx", function (d) { return projection([parseFloat(d.longitude),parseFloat(d.latitude)])[0]; })
          .attr("cy", function (d) { return projection([parseFloat(d.longitude),parseFloat(d.latitude)])[1]; })
          .attr("opacity", function(d) {
             var x = [parseFloat(d.longitude),parseFloat(d.latitude)];
            var dis = arc.distance({source: x, target: centerPos});
            return (dis > 1.57) ? '0' : '1';
          });
      }

      function redraw(){
        svg.selectAll("path").attr("d", path);
        svg.selectAll(".focused").classed("focused", focused = false); 
        redrawAirports();
      }

      redraw();

      function selectedAirport(cnt, sel) { 
        for(var i = 0, l = cnt.length; i < l; i++) {
          if(cnt[i].airport == sel.value) {return cnt[i];}
        }
      };

    };

    function updateAirportSelection(d, airports){
      airportName.innerText = d.city;

      $.post("query.php?q=flights&iata="+d.airport,{
          "airports": airports.map(function(airport) {
            return airport.airport;
          })
        }, 
        function( data ) {
          var x = JSON.parse(data);

          for(var i = 0; i < x.length; i++){
            var line = d3.svg.line();
            //line.interpolate();
            svg.select(".traffic").attr("d", line);
          }

          svg.selectAll("path.traffic").append("path").datum([])
      });

    }
  });

  </script>

</body>
</html>