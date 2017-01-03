<style type="text/css">

.water {
  fill: rgba(102, 204, 255,1);
}

.land {
  fill: rgb(255, 153, 102);
  stroke: #FFF;
  stroke-width: 0.7px;
}


.arcs {
  opacity:.1;
  stroke: gray;
  stroke-width: 3;
}
.flyers {
  /*stroke-width:2;*/
  opacity: 0.6;
  stroke: green; 
}
.arc, .flyer {
  stroke-linejoin: round;
  fill:none;
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

  <svg id="svg"></svg>
  <div id="analysis">
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

    var traffic = d3.svg.line()
      .x(function(d) { return d[0] })
      .y(function(d) { return d[1] })
      .interpolate("cardinal")
      .tension(.0);

    var skyScale = 1.1;

    var sky = d3.geo.orthographic()
    .translate([width / 2, height / 2])
    .clipAngle(90)
    .rotate([0, 0])
    .scale((width / 2 - 20) * skyScale);

    //Adding water

    var water = svg.append("path")
    .datum({type: "Sphere"})
    .attr("class", "water")
    .attr("d", path);

    var links = [], arcLines = [];

    var airportTooltip = d3.select("body").append("div").attr("class", "airportTooltip"),
    airportList = d3.select("body").append("select").attr("name", "airports");

    var airports, flight_counts;


    var analysis = document.getElementById('analysis');

    queue()
    .defer(d3.json, "world-110m.json")
    .defer(d3.tsv, "world-country-names.tsv")
    .defer(d3.json, "query.php?q=airports&a=200")
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
        
        svg.selectAll(".flyer")
          .attr("d", function(d) { return traffic(flying_arc(d)) })
          .attr("opacity", function(d) {
            return fade_at_edge(d)
          })
          .attr("stroke-width",function(d){return Math.round(d.traffic * 4) + 1;});

        redrawAirports();
      }

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
        if(parseInt(airports[i].total_traffic) > m) m = parseInt(airports[i].total_traffic);
      }

      //Drawing countries on the globe

      function zoomed() {
        //console.log(zoom.scale(), zoom2.scale());
        projection.scale(zoom.scale());
        sky.scale(zoom.scale() * skyScale);
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
          sky.rotate([d3.event.x * sens, -d3.event.y * sens, rotate[2]]);
          projection.rotate([d3.event.x * sens, -d3.event.y * sens, rotate[2]]);
          redraw();
        }))
        .call(zoom);

      var world = svg.selectAll("path.land")
      .data(countries)
      .enter().append("path")
      .attr("class", "land")
      .attr("d", path)

      .call(d3.behavior.drag()
        .origin(function() { var r = projection.rotate(); return {x: r[0] / sens, y: -r[1] / sens}; })
        .on("drag", function() {
          var rotate = projection.rotate();
          sky.rotate([d3.event.x * sens, -d3.event.y * sens, rotate[2]]);
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

        updateAirportSelection(focusedAirport, airports);
      });

      function airportRadius(total_traffic){
        var g = parseInt(total_traffic) / m * 10 + 3;
        if(g > 10) return 10;
        return g;
      }

      var circles_svg = svg.selectAll("circle")
        .data(airports)
        .enter()
        .append("circle")
        .attr("class","airport")
        .attr("cx", function (d) { return projection([parseFloat(d.longitude),parseFloat(d.latitude)])[0]; })
        .attr("cy", function (d) { return projection([parseFloat(d.longitude),parseFloat(d.latitude)])[1]; })
        .attr("r", function (d) {return airportRadius(d.total_traffic); })
        .on("mouseover", function(d) {
          airportTooltip.text((d.city + ": " + d.name).replace("Intl",""))
          .style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px")
          .style("display", "block")
          .style("opacity", 1);
          d3.select(this).attr({
            fill: "green",
            r: airportRadius(d.total_traffic) * 2
          });
          updateAirportSelection(d, airports);
        })
        .on("mouseout", function(d) {
          airportTooltip.style("opacity", 0)
          .style("display", "none");
          d3.select(this).attr({
            fill: "black",
            r: airportRadius(d.total_traffic)
          });
        })
        .on("mousemove", function(d) {
          airportTooltip.style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px");
        });

      

      redraw();

      function selectedAirport(cnt, sel) { 
        for(var i = 0, l = cnt.length; i < l; i++) {
          if(cnt[i].airport == sel.value) {return cnt[i];}
        }
      };

    };

    function location_along_arc(start, end, loc) {
      var interpolator = d3.geo.interpolate(start,end);
      return interpolator(loc)
    }

    function flying_arc(pts) {
      var source = pts.source,
          target = pts.target;

      var mid = location_along_arc(source, target, .5);
      var result = [ projection(source),
                     sky(mid),
                     projection(target) ];
      return result;
    }

    function fade_at_edge(d) {
      var centerPos = projection.invert([width/2,height/2]),
          arc = d3.geo.greatArc(),
          start, end;
      // function is called on 2 different data structures..
      if (d.source) {
        start = d.source, 
        end = d.target;  
      }
      else {
        start = d.geometry.coordinates[0];
        end = d.geometry.coordinates[1];
      }
      
      var start_dist = 1.57 - arc.distance({source: start, target: centerPos}),
          end_dist = 1.57 - arc.distance({source: end, target: centerPos});
        
      var fade = d3.scale.linear().domain([-.1,0]).range([0,.1]) 
      var dist = start_dist < end_dist ? start_dist : end_dist; 

      return fade(dist)
    }


    function updateAirportSelection(d, airports){
      airportName.innerText = d.city;

      $.post("query.php?q=flights&iata="+d.airport,{
          "airports": airports.map(function(airport) {
            return airport.airport;
          })
        }, 
        function( data ) {
          var x = JSON.parse(data);
          links = []; arcLines = [];
          d3.select('.arcs').remove();
          d3.select('.flyers').remove();
          
          max_traffic = 0;
          for(var i = 0; i < x.length; i++){
            if(parseFloat(x[i].total_traffic) > max_traffic) max_traffic = parseFloat(x[i].total_traffic);
          }

          for(var i = 0; i < x.length; i++){
            var ap = x[i];
            var p1 = projection([parseFloat(ap.longitude1),parseFloat(ap.latitude1)]);
            var p2 = projection([parseFloat(ap.longitude2),parseFloat(ap.latitude2)]);
            links.push({
              source: [parseFloat(ap.longitude1),parseFloat(ap.latitude1)],
              target: [parseFloat(ap.longitude2),parseFloat(ap.latitude2)],
              traffic: parseFloat(ap.total_traffic) / max_traffic,
            });
          }

          links.forEach(function(e,i,a) {
            var feature =   { "type": "Feature", "geometry": { "type": "LineString", "coordinates": [e.source,e.target]}}
            arcLines.push(feature)
          });


          svg.append("g").attr("class","flyers")
            .selectAll("path.flightscurved").data(links)
            .enter().append("path")
            .attr("class","flyer")
            .attr("d", function(d) { return traffic(flying_arc(d)); })
            .attr("opacity", function(d) {
              return fade_at_edge(d)
            });

          redraw();
      });

    }
  });

  </script>
