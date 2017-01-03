<!DOCTYPE html>
<meta charset="utf-8">
<style>

#leftpanel{
  background-color: grey;
  width: 200px;
  height: 400px;
  top: 0px;
  left: 0px;
}

#globe{
float: right;
}

#rightpanel{
  top: 0px;
  height: 400px;
  background-color: grey;
  width: 400px;
  float: right;
  clear: right;
}



</style>

<div id="leftpanel"> Leftpanel  </div>
<div id="globe"> <?php include('svg.php');?></div>
<div id="rightpanel"> Rightpanel </div>

<script src="//d3js.org/d3.v3.min.js"></script>
<script src="//d3js.org/queue.v1.min.js"></script>
<script src="//d3js.org/topojson.v1.min.js"></script>
<script src="jquery-3.1.1.min.js"></script>
