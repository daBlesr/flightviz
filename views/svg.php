<style type="text/css">

.water {
  fill:   #cce0ff;
}

.land {
  fill:  #4d94ff;
  stroke: #001433;
  stroke-width: 0.7px;
}

.arcs {
  opacity:0.6;
  stroke: green;
  stroke-width: 3;
  fill:none;
}

.flyers {
  /*stroke-width:2;*/
  opacity: 0.9;
  stroke: red; 
}

.domestic {
  stroke: green !important;
}
.straight {
  stroke: yellow !important;
  opacity: 1;
  stroke-width: 3px;
}

.flyer {
  stroke-linejoin: round;
  fill:none;
}

select {
  position: absolute;
  top: 30px;
  left: 760px;
  border: solid #ccc 1px;
  padding: 3px;
  box-shadow: inset 1px 1px 2px #ddd8dc;
  border: 1px solid #003d99;
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

    var svg = d3.select("#svg")
    .attr("width", width)
    .attr("height", height);

    var traffic = d3.svg.line()
      .x(function(d) { return d[0] })
      .y(function(d) { return d[1] })
      .interpolate("cardinal")
      .tension(.0);

    var skyScale = 1.2;

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

    var links = [];

    var airportTooltip = d3.select("#globe").append("div").attr("class", "airportTooltip"),
    airportList = d3.select("#globe").append("select").attr("name", "airports");

    var airports, flight_counts;

    var compareToAirport = false;

    queue()
    .defer(d3.json, "<?php echo $_GLOBALS['BASE_URL'];?>/assets/world-110m.json")
    .defer(d3.tsv, "<?php echo $_GLOBALS['BASE_URL'];?>/assets/world-country-names.tsv")
    .defer(d3.json, "<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=airports&a=400")
    .await(ready);

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
        .attr("stroke-width",function(d){return Math.round(d.traffic * 8) + 1;});

      redrawAirports();
    }

    function ready(error, world, countryData, airports) {

      var airportById = {},
      countries = topojson.feature(world, world.objects.countries).features;

      //Adding countries to select

      //airports.sort(function(a, b){return a.city > b.city});
      //console.log(airports);
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
        .attr("fill",'#003d99')
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
          dc.globeAirportHovered(d);
          ci.globeAirportHovered(d);
        })
        .on("mouseout", function(d) {
          airportTooltip.style("opacity", 0)
          .style("display", "none");
          d3.select(this).attr({
            fill: "#003d99",
            r: airportRadius(d.total_traffic)
          });
          dc.globeAirportHoverCancelled(d);
          ci.globeAirportHoverCancelled(d);
        })
        .on("mousemove", function(d) {
          airportTooltip.style("left", (d3.event.pageX + 7) + "px")
          .style("top", (d3.event.pageY - 15) + "px");
        })
        .on('click',function(d){
          compareToAirport = d.airport;
          updateAirportSelection(d, airports);
        })
        .on("contextmenu", function(d) {
          d3.event.preventDefault();
          if(d.country != "United States"){
            alert("Airport must be within USA");
          }

          d3.json("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=connections-to-airport&from="+compareToAirport+"&to="+d.airport, function(data){
            showTransferLines(data, compareToAirport, d.airport);
          });
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

      $.post("<?php echo $_GLOBALS['BASE_URL'];?>/controllers/query.php?q=flights&iata="+d.airport,{
          "airports": airports.map(function(airport) {
            return airport.airport;
          })
        }, 
        function( data ) {
          var x = JSON.parse(data);
          dc.airportsUpdated(x, "dest");
          ci.airportsUpdated(x, "dest");
          links = [];
          
          d3.select('.flyers').remove();
          d3.select('.flyer').remove();
          d3.select('.arcs').remove();
          
          max_traffic = 0;
          for(var i = 0; i < x.length; i++){
            if(parseFloat(x[i].total_traffic) > max_traffic) max_traffic = parseFloat(x[i].total_traffic);
          }

          d3.selectAll("circle").attr("fill","#003d99");

          for(var i = 0; i < x.length; i++){
            var ap = x[i];
            links.push({
              source: [parseFloat(ap.longitude1),parseFloat(ap.latitude1)],
              target: [parseFloat(ap.longitude2),parseFloat(ap.latitude2)],
              traffic: parseFloat(ap.total_traffic) / max_traffic,
            });
          }

          d3.selectAll("circle").each(function(){
            var f = d3.select(this);
            for(var i = 0; i < x.length; i++){
              if(d3.select(this).datum().airport == x[i].dest){
                f.attr("fill","red").attr("opacity","0.9");
              }
            }
          })

          svg.append("g").attr("class","flyers")
            .selectAll("path.flightscurved").data(links)
            .enter().append("path")
            .attr("class","flyer");
          redraw();
      });

    }

    function showTransferLines(x, from, to){
      dc.airportsUpdated(x,"transfer");
      ci.airportsUpdated(x,"transfer");
      d3.select('.flyers').remove();
      d3.select('.arcs').remove();
      ifl_flights = []; 
      domestic_flights = [];
      straight_flight = [];

      max_traffic = 0;
      for(var i = 0; i < x.length; i++){
        if(parseFloat(x[i].total_traffic) > max_traffic) max_traffic = parseFloat(x[i].total_traffic);
      }

      d3.selectAll("circle").attr("fill","#003d99");

      d3.selectAll("circle").each(function(){
        var f = d3.select(this);
        
        for(var i = 0; i < x.length; i++){
          var airport_data = f.datum();
          var ap = x[i];
          if(airport_data.airport == ap.transfer){
            f.attr("fill","red").attr("opacity","0.9");
            if(ap.longitude2 != null){
              ifl_flights.push({
                source: [parseFloat(ap.longitude1),parseFloat(ap.latitude1)],
                target: [parseFloat(ap.longitude2),parseFloat(ap.latitude2)],
                traffic: parseFloat(ap.total_traffic) / max_traffic,
              });

              var feature =   { "type": "Feature", "geometry": { "type": "LineString", "coordinates": [
                [parseFloat(ap.longitude2),parseFloat(ap.latitude2)],
                [parseFloat(ap.longitude3),parseFloat(ap.latitude3)]
              ] }};
              domestic_flights.push(feature);
            }
          } else if(ap.transfer == null && parseFloat(ap.total_traffic) > 0){
            straight_flight.push({
              source: [parseFloat(ap.longitude1),parseFloat(ap.latitude1)],
              target: [parseFloat(ap.longitude3),parseFloat(ap.latitude3)],
              traffic: parseFloat(ap.total_traffic) / max_traffic,
            });
          }
        }

      })

      if(domestic_flights.length == 0 && straight_flight.length == 0 && ifl_flights.length == 0){
        alert('there is no 1 transfer path from ' + from + " to " + to);
      } else{
        var g = svg.append("g").attr("class","flyers");

        g.attr("class","flyers")
          .selectAll("path.flightscurved").data(ifl_flights)
          .enter().append("path")
          .attr("class","flyer");

        svg.append("g").attr("class","arcs")
          .selectAll("path.flights").data(domestic_flights)
          .enter().append("path")
          .attr("class","arc")
          .attr("d",path);

        g.attr("class","flyers")
          .selectAll("path.flightscurved").data(straight_flight)
          .enter().append("path")
          .attr("class","flyer straight");

        redraw();
      }
    }
  });

  </script>
