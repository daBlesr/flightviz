<html>
<head>
<meta charset="utf-8">
<title>Earth globe</title>
<script src="<?php echo $_GLOBALS['BASE_URL'];?>/assets/d3.v3.min.js"></script>
<script src="<?php echo $_GLOBALS['BASE_URL'];?>/assets/topojson.v1.min.js"></script>
<script src="<?php echo $_GLOBALS['BASE_URL'];?>/assets/queue.v1.min.js"></script>
<script src="<?php echo $_GLOBALS['BASE_URL'];?>/assets/jquery-3.1.1.min.js"></script>
</head>
<body>
<style>
*{margin:0;}
body{ overflow: hidden; }

#buttons{
  padding-top: 100px;
}

#globe{
  background-color: white;
  float:left;
  height:100%;
  width: 960px;
}

#rightpanel{
  top: 0px;
  background-color: white;
  color: #D6E2EE ;
  width: calc(100% - 960px);
  height:100%;
  float:left;
}


#delays{
  float:left;
  height:450px;
  width: 960px;
}

#topView{
  width: 100%;
  height: 50%;
}
#botView{
  width:100%;
  height: 50%;
}

</style>

<div style="width: 100%; height:100%;">
  <div id="globe"> <?php include('svg.php');?></div>
  <div id="rightpanel">
    <div id="topView">
      <?php include('airport-delays.php');?>
    </div> 
    <div id="botView">
      <?php include('carrier-information.php');?>
    </div> 
  </div>
</div>

</body>

</html>