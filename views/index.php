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

#leftpanel{
  background-color: #2D3E4E;
  color: #D6E2EE ;
  width: 200px;
  height: 100%;
  float:left;
}

#buttons{
  padding-top: 100px;
}

#leftpanel button{
  left: 0px;
  float: left;
  width: 199px;
  height: 50px;
  background-color: #253341;
  color: #D6E2EE;
  font-family: arial;   
  font-size: 20px;
  border-width: 1px;
  border-top: solid #D6E2EE 2px;
  border: solid #D6E2EE 1px;
}

#globe{
  background-image: url("<?php echo $_GLOBALS['BASE_URL'];?>/assets/bg.jpg");
  float:left;
  height:100%;
  width: 960px;
}

#rightpanel{
  top: 0px;
  background-color: #2D3E4E;
  color: #D6E2EE ;
  width: calc(100% - 1160px);
  height:100%;
  float:left;
}

.tabcontent {
  display: none;
}

#delays{
  float:left;
  height:450px;
  width: 960px;
}


</style>

<div style="width: 100%; height:100%; margin-left: auto; margin-right: auto">
  <div id="leftpanel"> 
    <div id="buttons">
      <button id="defaultOpen" class="tablinks" onclick="tabs(event, 'globe')"> Globe </button>
      <button class="tablinks" onclick="tabs(event, 'delays')"> Flight Delays </button>
    </div>
   </div>

  <div id="globe" class = "tabcontent"> <?php include('svg.php');?></div>
  <div id="delays" class = "tabcontent"> <?php include('delays.php');?></div>
  <div id="rightpanel"> Rightpanel </div> 
</div>

<script>

document.getElementById("defaultOpen").click();

  //Overview and Details tabs in the interface
  function tabs(evt, page) {
      // Declare all variables
      var i, tabcontent, tablinks;

      // Get all elements with class="tabcontent" and hide them
      tabcontent = document.getElementsByClassName("tabcontent");
      for (i = 0; i < tabcontent.length; i++) {
          tabcontent[i].style.display = "none";
      }

      // Get all elements with class="tablinks" and remove the class "active"
      tablinks = document.getElementsByClassName("tablinks");
      for (i = 0; i < tablinks.length; i++) {
          tablinks[i].className = tablinks[i].className.replace(" active", "");
      }

      // Show the current tab, and add an "active" class to the link that opened the tab
      document.getElementById(page).style.display = "block";
      evt.currentTarget.className += " active";
      }
</script>
</body>

</html>