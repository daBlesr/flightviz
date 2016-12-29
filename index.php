<!DOCTYPE html>
<meta charset="utf-8">
<style>

h1 {
  position: absolute;
  top: 500px;
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  font-size: 18px;
  text-align: center;
  width: 960px;
}

</style>
<h1></h1>
<script src="//d3js.org/d3.v3.min.js"></script>
<script src="//d3js.org/queue.v1.min.js"></script>
<script src="//d3js.org/topojson.v1.min.js"></script>
<script src="jquery-3.1.1.min.js"></script>
<script>

var width = 960,
    height = 960;

var scale0 = (width / 2 - 20);

var projection = d3.geo.orthographic()
    .translate([width / 2, height / 2])
    .scale(width / 2 - 20)
    .clipAngle(90)
    .precision(0.6);

var svg = d3.select("body").append("svg")
    .attr("width", width)
    .attr("height", height);

var g = svg.append("g");

var path = d3.geo.path()
    .projection(projection);

queue()
    .defer(d3.json, "world-110m.json")
    .defer(d3.tsv, "world-country-names.tsv")
    .await(ready);

function x(world, names, airports, flights){
  var globe = {type: "Sphere"},
      land = topojson.feature(world, world.objects.land),
      countries = topojson.feature(world, world.objects.countries).features,
      borders = topojson.mesh(world, world.objects.countries, function(a, b) { return a !== b; }),
      i = -1,
      n = countries.length;

  countries = countries.filter(function(d) {
    return names.some(function(n) {
      if (d.id == n.id) return d.name = n.name;
    });
  }).sort(function(a, b) {
    return a.name.localeCompare(b.name);
  });

  function redraw(airports){

    function mousedownAirport(d){
      console.log(d);
    }

    //c.clearRect(0, 0, width, height);
    g.selectAll("path")
      .data(borders)
      .enter()
      .append("path")
      .attr("d", path);

    g.selectAll("path")
      .data(land)
      .enter()
      .append("path")
      .attr("d", path);

    g.selectAll("path")
      .data(countries)
      .enter()
      .append("path")
      .attr("d", path)

    /**
    c.fillStyle = "rgba(102, 204, 255,0.5)", c.beginPath(), path(globe), c.fill();
    c.fillStyle = "rgb(255, 153, 102)", c.beginPath(), path(land), c.fill();
    c.strokeStyle = "#fff", c.lineWidth = .5, c.beginPath(), path(borders), c.stroke();

    var m = 0;
    for(var i = 0; i < flights.length; i++){
      if(flights[i].total_traffic > m) m = flights[i].total_traffic;
    }
    var coordinates = [];


    for(var i = 0; i < flights.length; i++){
      c.strokeStyle = "red", c.lineWidth = flights[i].total_traffic / m, c.beginPath();
      var flight = flights[i];

      for(var j = 0; j < airports.length; j++){
        if(airports[j].airport == flight.dest){
          airp1 = airports[j];
        }
        if(airports[j].airport == flight.dest2){
          airp2 = airports[j];
        }
      }
      path({
        type: "LineString", 
        coordinates: [
          [parseFloat(airp1.longitude), parseFloat(airp1.latitude)],
          [parseFloat(airp2.longitude), parseFloat(airp2.latitude)]
        ]
      });
      c.stroke();
    }

    circles=[];
    var m = 0;
    for(var i = 0; i < airports.length; i++){
      if(airports[i].total_traffic > m) m = airports[i].total_traffic;
    }

    for(var i = 0; i < airports.length; i++){
      circles.push(
        d3.geo.circle()
          .angle(airports[i].total_traffic / m)
          .origin([parseFloat(airports[i].longitude), parseFloat(airports[i].latitude)])()
      );
    }

    c.beginPath();
    path({type: "GeometryCollection", geometries: circles});
    c.fillStyle = "rgba(0,100,0,.5)";
    c.fill();
    c.lineWidth = .2;
    c.strokeStyle = "#000";
    c.stroke();
    */
}

  dragBehaviour = d3.behavior.drag()
    .on('drag', function(){
        var dx = d3.event.dx;
        var dy = d3.event.dy;

        var rotation = projection.rotate();
        var radius = projection.scale();
        var scale = d3.scale.linear()
            .domain([-1 * radius, radius])
            .range([-90, 90]);
        var degX = scale(dx);
        var degY = scale(dy);
        rotation[0] += degX;
        rotation[1] -= degY;
        if (rotation[1] > 90)   rotation[1] = 90;
        if (rotation[1] < -90)  rotation[1] = -90;

        if (rotation[0] >= 180) rotation[0] -= 360;
        projection.rotate(rotation);

        redraw(airports);
    });

    function zoomed() {
      projection.scale(zoom.scale());
      redraw(airports);
    }


    var zoom = d3.behavior.zoom()
      .translate([width / 2, height / 2])
      .scale(scale0)
      .scaleExtent([scale0, 8 * scale0])
      .on("zoom", zoomed);

  svg.call(dragBehaviour).call(zoom).call(zoom.event);
  

  redraw(airports);
}

function ready(error, world, names) {
  if (error) throw error;
  var url = "query.php?q=airports&a=20";
  d3.json(url, function (json) {
    $.post("query.php?q=flights-by-airport",{
        "airports": json.map(function(airport) {
          return airport.airport;
        })
      }, 
      function( data ) {
        x(world, names, json, JSON.parse(data));
      });
  });
  
}

d3.select(self.frameElement).style("height", height + "px");

</script>