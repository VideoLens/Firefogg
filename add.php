<?php
$videoId = $_GET['id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $title = $_POST['title'];
  $description = $_POST['description'];
  $videoId = 'fixme';
  $videoUrl = "video.php?id=" . $videoId;
  $url = "add.php?id=" . $videoId;
  echo '{"result": "ok", "video_put_url": "'. $url .'", "video_url": "'. $videoUrl .'"}';
  exit();
}
else if($_SERVER['REQUEST_METHOD'] == 'PUT') {
  $fname = 'videos/' . $videoId . '.ogv';
  if(file_exists($fname)) {
    echo '{"result": "failed"}';
    exit();
  }
  $fp = fopen($fname, "w");
  $putdata = fopen("php://input", "r");
  while ($data = fread($putdata, 1024))
    fwrite($fp, $data);

  /* Close the streams */
  fclose($fp);
  fclose($putdata);
  echo '{"result": "ok"}';
  exit();
}

$title = 'fixme';
$description = 'fixme';

?>
<!DOCTYPE html>
<html>
  <head>
      <title>Add Video</title>
      <style>
        #progress {
          width: 200px;
          height: 20px;
          background-color: #eee;
        }
        #progressbar {
          height: 20px;
          background-color: #00f;
        }
      </style>
      <script src="http://firefogg.org/js/jquery.js"></script>
      <script>
        $(document).ready(function(){
            $('#submit').hide();
            $('#progress').hide();
        });

        if(typeof(Firefogg) == 'undefined') {
          alert('You dont have Firefogg, plese go to http://firefogg.org to install it');
          document.location.href = 'http://firefogg.org';
        }
        var ogg = new Firefogg();

        function selectVideo() {
          if(ogg.selectVideo()) {
            $('#selectVideoButton').hide();
            $('#submit').show();
          }
        }

        function submitForm() {
          var data = $('#addVideo').serializeArray();
          var callback = function(json) {
            if(json.result == 'ok') {
              $('#addVideo').hide();
              $('#progress').show();
              encode_and_upload(json.video_put_url, json.video_url);  
            } else {
              alert('please fix form data');
            }
          };
          $.post(window.location.href, data, callback, "json");
        }
        function encode_and_upload(uploadUrl, videoUrl) {
          var options = JSON.stringify({'maxSize': 320, 'videoBitrate': 500});
          ogg.encode(options);
          var encodingStatus = function() {
            var status = ogg.status();
            var progress = ogg.progress();

            //do something with status and progress, i.e. set progressbar width:
            var progressbar = document.getElementById('progressbar');
            progressbar.style.width= parseInt(progress*200) +'px';
            $('#progressstatus').html(parseInt(progress*100) + '% - ' + status);

            //loop to get new status if still encoding
            if(ogg.state == 'encoding') {
              setTimeout(encodingStatus, 500);
            }
            //encoding sucessfull, state can also be 'encoding failed'
            else if (ogg.state == 'encoding done') {
              ogg.upload(uploadUrl);
              var uploadStatus = function() {
                var status = ogg.status();
                var progress = ogg.progress();

                //do something with status and progress, i.e. set progressbar width:
                var progressbar = document.getElementById('progressbar');
                progressbar.style.width= parseInt(progress*200) +'px';
                $('#progressstatus').html(parseInt(progress*100) + '% - ' + status);

                //loop to get new status if still uploading
                if(ogg.state == 'uploading') {
                  setTimeout(uploadStatus, 500);
                }
                //upload sucessfull, state can also be 'upload failed'
                else if(ogg.state == 'upload done') {
                  progressbar.innerHTML = 'Upload done.';
                  document.location.href = videoUrl;
                }
              }
              uploadStatus();
            }
          }
          encodingStatus()
        }
      </script>
  </head>
  <body>
    <h1>Add Video</h1>
    <p>
      <div id="progress">
        <div id="progressbar"></div>
        <div id="progressstatus"></div>
      </div>
    </p>
    <p>
      <form id="addVideo">
        <p>Title: <input type="text" name="title" value="" /></p>
        <p>Description: <textarea name="description"></textarea></p>
        <input type="button" value="Select Video..." id="selectVideoButton" onclick="selectVideo()" />
        <input type="button" value="Submit" id="submit" onclick="submitForm()" />
      </form>
    </p>
  </body>
</html>
