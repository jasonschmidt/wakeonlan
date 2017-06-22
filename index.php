<?PHP /*
Remote Wake/Sleep-On-LAN Server
https://github.com/sciguy14/Remote-Wake-Sleep-On-LAN-Server
Author: Jeremy E. Blum (http://www.jeremyblum.com)
License: GPL v3 (http://www.gnu.org/licenses/gpl.html)
*/ 

//You should not need to edit this file. Adjust Parameters in the config file:
require_once('config.php');

//Uncomment to report PHP errors.
error_reporting(E_ALL);
ini_set('display_errors', '1');
			
// Enable flushing
ini_set('implicit_flush', true);
ob_implicit_flush(true);
ob_end_flush();

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Remote Wake/Sleep-On-LAN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A utility for remotely waking/sleeping a Windows computer via a Raspberry Pi">
    <meta name="author" content="Jeremy Blum">

    <!-- Le styles -->
    <link href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 40px !important;
        padding-bottom: 40px;
        background-color: #000;
      }

      .form-signin {
        max-width: 600px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type="text"],
      .form-signin input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }

    </style>
    <link href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/ico/apple-touch-icon-114-precomposed.png">
      <link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/ico/apple-touch-icon-72-precomposed.png">
                    <link rel="apple-touch-icon-precomposed" href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/ico/apple-touch-icon-57-precomposed.png">
                                   <link rel="shortcut icon" href="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/ico/favicon.png">
  </head>

  <body>

    <div class="container">
        <div class="row text-center">
            <img src="Hal9000.jpg" alt="HAL" />
        </div>
    	<form class="form-signin" method="post">
        	<h3 class="form-signin-heading">
			<?php
			
				$approved_wake = false;
				$approved_sleep = false;
				if ( isset($_POST['password']) )
                {
                    $hash = hash("sha256", $_POST['password']);
                    if ($hash == $APPROVED_HASH)
                    {
						if ($_POST['submit'] == "wake")
						{
							$approved_wake = true;
						}
						elseif ($_POST['submit'] == "sleep")
						{
							$approved_sleep = true;
						}
					}
				}
			 
				if ($approved_wake) echo "Waking Up, Dave!";
				elseif ($approved_sleep) echo "Going to Sleep!";
				else echo "Good Morning, Dave";
			?>
            </h3>
            <?php
				if (!isset($_POST['submit']) || (isset($_POST['submit']) && !$approved_wake && !$approved_sleep))
				{
					echo "<h5 id='wait'>Querying Computer State. Please Wait...</h5>";
					$pinginfo = exec("ping -c 1 " . $COMPUTER_LOCAL_IP);
					?><script>
						document.getElementById('wait').style.display = 'none';
                    </script><?php
					if ($pinginfo == "")
					{
						$asleep = true;
						echo "<h5>The remote computer is presently asleep.</h5>";
					}
					else
					{
						$asleep = false;
						echo "<h5>The remote computer is presently awake.</h5>";
					}
				}
				                
                $show_form = true;
                
                if ($approved_wake)
                {
                	echo "<p>Approved. Sending WOL Command...</p>";
					exec ('wakeonlan ' . $COMPUTER_MAC);
					echo "<p>Command Sent. Waiting for computer to wake up...</p><p>";
					$count = 1;
					$down = true;
					while ($count <= $MAX_PINGS && $down == true)
					{
						echo "Ping " . $count . "...";
						$pinginfo = exec("ping -c 1 " . $COMPUTER_LOCAL_IP);
						$count++;
						if ($pinginfo != "")
						{
							$down = false;
							echo "<span style='color:#00CC00;'><b>It's Alive!</b></span><br />";
							$show_form = false;
						}
						else
						{
							echo "<span style='color:#CC0000;'><b>Still Down.</b></span><br />";
						}	
					}
					echo "</p>";
					if ($down == true)
					{
						echo "<p style='color:#CC0000;'><b>FAILED!</b> The remote computer doesn't seem to be waking up... Try again?</p>";
					}
				}
				elseif ($approved_sleep)
				{
					echo "<p>Approved. Sending Sleep Command...</p>";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "http://" . $COMPUTER_LOCAL_IP . ":" . $COMPUTER_SLEEP_CMD_PORT . "/suspend");
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
					
					if (curl_exec($ch) === false)
					{
						echo "<p><span style='color:#CC0000;'><b>Command Failed:</b></span> " . curl_error($ch) . "</p>";
					}
					else
					{
						echo "<p><span style='color:#00CC00;'><b>Command Succeeded!</b></span> Waiting for computer to go to sleep...</p><p>";
						$count = 1;
						$down = false;
						while ($count <= $MAX_PINGS && $down == false)
						{
							echo "Ping " . $count . "...";
							$pinginfo = exec("ping -c 1 " . $COMPUTER_LOCAL_IP);
							$count++;
							if ($pinginfo == "")
							{
								$down = true;
								echo "<span style='color:#00CC00;'><b>It's Asleep!</b></span><br />";
								$show_form = false;
							}
							else
							{
								echo "<span style='color:#CC0000;'><b>Still Awake.</b></span><br />";
							}
							sleep(3);
						}
						echo "</p>";
						if ($down == false)
						{
							echo "<p style='color:#CC0000;'><b>FAILED!</b> The remote computer doesn't seem to be falling asleep... Try again?</p>";
						}
					}
					curl_close($ch);
				}
				elseif (isset($_POST['submit']))
				{
					echo "<p style='color:#CC0000;'><b>Invalid Passphrase. Request Denied.</b></p>";
				}		
                
                if ($show_form)
                {
            ?>
        			<input type="password" class="input-block-level" placeholder="Enter Passphrase" name="password">
                    <?php if ( (isset($_POST['submit']) && $_POST['submit'] == "wake") || (!isset($_POST['submit']) && $asleep) ) {?>
        				<button class="btn btn-large btn-primary" type="submit" name="submit" value="wake">Wake Up!</button>
                    <?php } else { ?>
                    <button class="btn btn-large btn-primary" type="submit" name="submit" value="sleep">Go to Sleep!</button>
                    <?php } ?>
	
			<?php
				}
			?>
		</form>            
    </div> <!-- /container -->
    <script src="<?php echo $BOOTSTRAP_LOCATION_PREFIX; ?>bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>
