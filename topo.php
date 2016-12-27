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
<script>

var width = 960,
    height = 960;

var projection = d3.geo.orthographic()
    .translate([width / 2, height / 2])
    .scale(width / 2 - 20)
    .clipAngle(90)
    .precision(0.6);

var canvas = d3.select("body").append("canvas")
    .attr("width", width)
    .attr("height", height);

var c = canvas.node().getContext("2d");

var path = d3.geo.path()
    .projection(projection)
    .context(c);

queue()
    .defer(d3.json, "world-110m.json")
    .defer(d3.tsv, "world-country-names.tsv")
    .await(ready);

function x(world, names, airports){
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
  c.clearRect(0, 0, width, height);
  c.fillStyle = "#ccc", c.beginPath(), path(land), c.fill();
  c.strokeStyle = "#fff", c.lineWidth = .5, c.beginPath(), path(borders), c.stroke();
  c.strokeStyle = "#000", c.lineWidth = 2, c.beginPath(), path(globe), c.stroke();

  circles=[];
  var m = 0;
  for(var i = 0; i < airports.length; i++){
    if(airports[i].total_traffic > m) m = airports[i].total_traffic;
  }

  for(var i = 0; i < airports.length; i++){
    circles.push(d3.geo.circle().angle(airports[i].total_traffic / m).origin([parseFloat(airports[i].longitude), parseFloat(airports[i].latitude)])());
  }

  c.beginPath();
  path({type: "GeometryCollection", geometries: circles});
  c.fillStyle = "rgba(0,100,0,.5)";
  c.fill();
  c.lineWidth = .2;
  c.strokeStyle = "#000";
  c.stroke();

  
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

        //var url = "query.php?q=airports";
        //d3.json(url, function (json) {
        //  redraw(json);
        //});
        redraw(airports);
    });

  d3.select("body").select('canvas').call(dragBehaviour);
  

  redraw(airports);
}

function ready(error, world, names) {
  if (error) throw error;
  var url = "query.php?q=airports";
  d3.json(url, function (json) {
    x(world, names, json);
  });
  
}

d3.select(self.frameElement).style("height", height + "px");

</script>