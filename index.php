<html>
<head>
<meta charset="utf-8">
<title>Earth globe</title>
<script src="d3.v3.min.js"></script>
<script src="topojson.v1.min.js"></script>
<script src="queue.v1.min.js"></script>
<script src="jquery-3.1.1.min.js"></script>
</head>
<body>
<style>
*{margin:0;}
body{ overflow: hidden; }

#leftpanel{
  background-color: grey;
  width: 200px;
  height: 100%;
  float:left;
}

#globe{
  float:left;
  height:100%;
  width: 960px;
}

#rightpanel{
  top: 0px;
  background-color: grey;
  width: calc(100% - 1160px);
  height:100%;
  float:left;
}



</style>

<div style="width: 100%; height:100%; margin-left: auto; margin-right: auto">
  <div id="leftpanel"> Leftpanel  </div>
  <div id="globe"> <?php include('svg.php');?></div>
  <div id="rightpanel"> Rightpanel </div> 
</div>

</body>

</html>